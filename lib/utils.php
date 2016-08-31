<?php

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
