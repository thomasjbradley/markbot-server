<?php

require_once 'user-map.php';
require_once 'config.php';
require_once 'lib/utils.php';
require_once 'lib/canvas-api.php';
require_once 'lib/comment-generator.php';
require_once 'lib/assignments.php';

header('Content-type: text/html; charset=utf8');

$gh_repo = filter_input(INPUT_GET, 'gh_repo', FILTER_SANITIZE_STRING);
$gh_username = filter_input(INPUT_GET, 'gh_username', FILTER_SANITIZE_STRING);
$canvas_course = filter_input(INPUT_GET, 'canvas_course', FILTER_SANITIZE_STRING);
$markbot_version = filter_input(INPUT_GET, 'markbot_version', FILTER_SANITIZE_STRING);
$cheater = filter_input(INPUT_GET, 'cheater', FILTER_SANITIZE_NUMBER_INT);
$sig = filter_input(INPUT_GET, 'sig', FILTER_SANITIZE_STRING);

if (!$gh_repo || !$gh_username || !$canvas_course || !$markbot_version || !$sig || !in_array($cheater, [0, 1])) quit();

if (!version_compare($markbot_version, $config['min_markbot_version'], '>=')) {
  quit(400, "Markbot version too old, expecting >= {$config['min_markbot_version']}");
}

$generated_sig = hash_request($config, [
  "gh_repo=$gh_repo",
  "gh_username=$gh_username",
  "canvas_course=$canvas_course",
  "markbot_version=$markbot_version",
  "cheater=$cheater"
]);

if ($sig != $generated_sig) quit();
if (!isset($config['courses'][$canvas_course])) quit();

if (!isset($user_map[$gh_username])) quit(401, 'Your GitHub username doesn’t match any approved users—double check the capitalization');

$canvas_user = $user_map[$gh_username];
$canvas = \Canvas\setup($config['canvas_base_url'], $config['canvas_api_key']);
$course = \Canvas\course($canvas, $config['courses'][$canvas_course]);

$assignments = $course('/assignments');

$assignment = find_assignment($course, $gh_repo);

if ($config['DEBUG']) debug($assignment);

if (!$assignment) quit(501, 'There was a problem contacting Canvas—try again later');

if (is_previously_graded($course, $assignment->id, $canvas_user)) quit(400, 'This assignment has already been graded');

$data = [
  'comment' => [
    'text_comment' => get_comment($gh_username, $gh_repo, $cheater),
  ],
  'submission' => [
    'posted_grade' => ($cheater == 0) ? 1 : 0,
  ]
];

$url = "/assignments/{$assignment->id}/submissions/{$canvas_user}";

if ($config['DEBUG']) debug($data);
if ($config['DEBUG']) debug($url);

$response = $course($url, $data, 'PUT');

if ($config['DEBUG']) debug($response);

if (!$response) quit(501, 'There was a problem contacting Canvas—try again later');

quit(200, 'MARKBOT STATUS: SUCCESS');

