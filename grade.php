<?php

require_once 'user-map.php';
require_once 'config.php';

header('Content-type: text/html; charset=utf8');

function quit () {
  http_response_code(400);
  echo 'HTTP/1.1 400 Bad request';
  exit;
}

function debug ($message) {
  echo '<pre>';
  print_r($message);
  echo '</pre>';
};

$DEBUG = false;

$gh_user = filter_input(INPUT_GET, 'gh_user', FILTER_SANITIZE_EMAIL);
$gh_repo = filter_input(INPUT_GET, 'gh_repo', FILTER_SANITIZE_EMAIL);
$gh_pr = filter_input(INPUT_GET, 'gh_pr', FILTER_SANITIZE_NUMBER_INT);
$canvas_course = filter_input(INPUT_GET, 'canvas_course', FILTER_SANITIZE_NUMBER_INT);
$canvas_assignment = filter_input(INPUT_GET, 'canvas_assignment', FILTER_SANITIZE_NUMBER_INT);

if (!$gh_user || !$gh_repo || !$gh_pr || !$canvas_course || !$canvas_assignment) quit();

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
$message = $messages[array_rand($messages)];

if (!exists($user_map[$gh_user])) quit();

$canvas_user = $user_map[$gh_user];
$repo_bits = explode('/', $gh_repo);

$comment = <<<ROBOT
.
.
+++++++++++++++++++++++++++++++++++++++++
 └[ ◕ 〜 ◕ ]┘ MARKBOT SAYS, "{$message}!"
+++++++++++++++++++++++++++++++++++++++++

Pull request:
https://github.com/{$gh_repo}/pull/{$gh_pr}

Website URL:
https://{$gh_user}.github.io/{$repo_bits[1]}

+++++++++++++++++++++++++++++++++++++++++
ROBOT;

$data = [
  'comment' => [
    'text_comment' => $comment
  ],
  'submission' => [
    'posted_grade' => 'complete'
  ]
];

$request = [
  'http' => [
    'method' => 'PUT',
    'header' => implode("\r\n", [
      "Authorization: Bearer $canvas_api_key",
      "Content-Type: application/json"
    ]),
    'content' => json_encode($data),
    'verify_peer' => false
  ]
];
$url = "https://algonquin.instructure.com/api/v1/courses/{$canvas_course}/assignments/{$canvas_assignment}/submissions/{$canvas_user}";

if ($DEBUG) debug($data);
if ($DEBUG) debug($url);
if ($DEBUG) debug($request);

$context = stream_context_create($request);
$response = file_get_contents($url, false, $context);

if ($DEBUG) debug(json_decode($response));

echo 'MARKBOT STATUS: COMPLETE';
