<?php

require_once 'user-map.php';
require_once 'config.php';

header('Content-type: text/html; charset=utf8');

function quit ($code = 400, $message = 'Incomplete or missing arguments') {
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

if (!version_compare($markbot_version, $min_markbot_version, '>=')) {
  quit(400, "Markbot version too old, expecting >= $min_markbot_version");
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

if ($cheater == 0) {
  $message = $messages[array_rand($messages)];
  $cheater_message = '';
} else {
  $message = 'CHEATER';
  $cheater_message = $cheater_message_template;
}

$canvas_user = $user_map[$gh_username];

$comment = <<<ROBOT
+++++++++++++++++++++++++++++++++++++++++
 └[ ◕ 〜 ◕ ]┘ MARKBOT SAYS, "{$message}!"
+++++++++++++++++++++++++++++++++++++++++
${cheater_message}
Repository URL:
https://github.com/{$gh_username}/{$gh_repo}

Website URL:
https://{$gh_username}.github.io/{$gh_repo}

+++++++++++++++++++++++++++++++++++++++++
ROBOT;

$data = [
  'comment' => [
    'text_comment' => $comment
  ],
  'submission' => [
    'posted_grade' => ($cheater == 0) ? 1 : 0
  ]
];

$request = [
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
$url = "https://{$config['canvas_base_url']}/api/v1/courses/{$canvas_course}/assignments/{$canvas_assignment}/submissions/{$canvas_user}";

if ($config['DEBUG']) debug($data);
if ($config['DEBUG']) debug($url);
if ($config['DEBUG']) debug($request);

$context = stream_context_create($request);
$response = file_get_contents($url, false, $context);

if ($config['DEBUG']) debug(json_decode($response));

quit(200, 'MARKBOT STATUS: SUCCESS');
