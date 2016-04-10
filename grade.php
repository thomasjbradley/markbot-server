<?php

require_once 'user-map.php';
require_once 'config.php';

header('Content-type: text/html; charset=utf8');

function quit ($code = 400, $message = 'Incomplete or missing arguments—double check you have the most recent version of Markbot') {
  http_response_code($code);

  echo json_encode([
    'code' => $code,
    'message' => $message
  ]);

  exit;
}

function debug ($message) {
  echo '<pre>';
  print_r($message);
  echo '</pre>';
};

function hash_request ($conf, $params) {
  return hash('sha512', implode($params, '&') . $conf['passcode_hash']);
}

$gh_repo = filter_input(INPUT_GET, 'gh_repo', FILTER_SANITIZE_STRING);
$gh_username = filter_input(INPUT_GET, 'gh_username', FILTER_SANITIZE_STRING);
$canvas_course = filter_input(INPUT_GET, 'canvas_course', FILTER_SANITIZE_NUMBER_INT);
$canvas_assignment = filter_input(INPUT_GET, 'canvas_assignment', FILTER_SANITIZE_NUMBER_INT);
$markbot_version = filter_input(INPUT_GET, 'markbot_version', FILTER_SANITIZE_STRING);
$cheater = filter_input(INPUT_GET, 'cheater', FILTER_SANITIZE_NUMBER_INT);
$sig = filter_input(INPUT_GET, 'sig', FILTER_SANITIZE_STRING);

if (!$gh_repo || !$gh_username || !$canvas_course || !$canvas_assignment || !$markbot_version || !$sig || !in_array($cheater, [0, 1])) quit();

if (!version_compare($markbot_version, $config['min_markbot_version'], '>=')) {
  quit(400, "Markbot version too old, expecting >= {$config['min_markbot_version']}");
}

$generated_sig = hash_request($config, [
  "gh_repo=$gh_repo",
  "gh_username=$gh_username",
  "canvas_course=$canvas_course",
  "canvas_assignment=$canvas_assignment",
  "markbot_version=$markbot_version",
  "cheater=$cheater"
]);

if ($sig != $generated_sig) quit();

$cheater_message_template = <<<CHEATER

Markbot has deteched that you cheated by
performing one of these actions:
- changing the “.markbot.yml” file,
- removing the “.markbot.lock” file,
- modifying locked code files,
- modifying the screenshots,
- or modifying the Markbot application.

Because of this you’ve recieved a 0.

+++++++++++++++++++++++++++++++++++++++++

CHEATER;

$messages = [
  'BOOYAKASHA',
  'WAY TO GO',
  'SUPER-DUPER',
  'AWESOME',
  'COWABUNGA',
  'RAD',
  'AMAZEBALLS',
  'SWEET',
  'COOL',
  'NICE',
  'FANTASTIC',
  'GERONIMO',
  'WHAMO',
  'SUPERB',
  'STUPENDOUS',
  'MATHMATICAL'
];
$markbot_mouth = '◡';

if ($cheater == 0) {
  $message = $messages[array_rand($messages)];
  $cheater_message = '';
} else {
  $message = 'CHEATER';
  $cheater_message = $cheater_message_template;
  $markbot_mouth = '〜';
}

$canvas_user = $user_map[$gh_username];

$comment = <<<ROBOT
+++++++++++++++++++++++++++++++++++++++++
 └[ ◕ ${markbot_mouth} ◕ ]┘ MARKBOT SAYS, "{$message}!"
+++++++++++++++++++++++++++++++++++++++++
${cheater_message}
Repository URL:
https://github.com/{$gh_username}/{$gh_repo}

Website URL:
https://{$gh_username}.github.io/{$gh_repo}

+++++++++++++++++++++++++++++++++++++++++
ROBOT;

$check_graded_request = [
  'http' => [
    'method' => 'GET',
    'header' => implode("\r\n", [
      "Authorization: Bearer {$config['canvas_api_key']}",
      "Content-Type: application/json"
    ]),
    'verify_peer' => false
  ]
];
$check_graded_url = "https://{$config['canvas_base_url']}/api/v1/courses/{$canvas_course}/assignments/{$canvas_assignment}/submissions/{$canvas_user}";

$context = stream_context_create($check_graded_request);
$response = file_get_contents($check_graded_url, false, $context);

if (!$response) quit(501, 'There was a problem contacting Canvas—try again later');

$response_data = json_decode($response);

if (!$response_data) quit(501, 'There was a problem contacting Canvas—try again later');

if ($response_data->grade !== null || $response_data->score !== null) quit(400, 'This assignment has already been submitted');

$data = [
  'comment' => [
    'text_comment' => $comment
  ],
  'submission' => [
    'posted_grade' => ($cheater == 0) ? 1 : 0
  ]
];

$grade_request = [
  'http' => [
    'method' => 'PUT',
    'header' => implode("\r\n", [
      "Authorization: Bearer {$config['canvas_api_key']}",
      "Content-Type: application/json"
    ]),
    'content' => json_encode($data),
    'verify_peer' => false
  ]
];
$grade_url = "https://{$config['canvas_base_url']}/api/v1/courses/{$canvas_course}/assignments/{$canvas_assignment}/submissions/{$canvas_user}";

if ($config['DEBUG']) debug($data);
if ($config['DEBUG']) debug($url);
if ($config['DEBUG']) debug($request);

$context = stream_context_create($grade_request);
$response = file_get_contents($grade_url, false, $context);

if (!$response) quit(501, 'There was a problem contacting Canvas—try again later');
if ($config['DEBUG']) debug(json_decode($response));

quit(200, 'MARKBOT STATUS: SUCCESS');
