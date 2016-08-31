<?php

function find_assignment ($canvas, $slug) {
  $assignments = $canvas('/assignments');

  if (!$assignments) return false;

  foreach ($assignments as $assignment) {
    if (!isset($assignment->allowed_extensions)) continue;
    if (empty($assignment->allowed_extensions)) continue;

    if (in_array($slug, $assignment->allowed_extensions)) return $assignment;
  }

  return false;
}

function is_previously_graded ($canvas, $assignment_id, $canvas_user) {
  $url = "/assignments/{$assignment_id}/submissions/{$canvas_user}";
  $submissions = $canvas($url);

  if (!$submissions) return true;
  if ($submissions->grade === null && $submissions->score === null) return false;

  return true;
}
