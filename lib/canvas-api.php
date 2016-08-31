<?php

namespace Canvas;

function setup ($subdomain, $api_key) {
  return function ($path, $data = null, $method = 'GET', $path_prefix = null) use ($subdomain, $api_key) {
    return request($subdomain, $api_key, $path, $data, $method, $path_prefix);
  };
}

function course (callable $instance, $course_id) {
  return function ($path, $data = null, $method = 'GET') use ($instance, $course_id) {
    return $instance($path, $data, $method, '/courses/' . $course_id);
  };
}

function request ($subdomain, $api_key, $path, $data = null, $method = 'GET', $path_prefix = null) {
  $url = "https://$subdomain/api/v1";

  $request = [
    'http' => [
      'method' => $method,
      'header' => implode("\r\n", [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
      ]),
      'verify_peer' => false
    ]
  ];

  if (isset($data)) $request['http']['content'] = json_encode($data);

  if (isset($path_prefix)) $url .= $path_prefix;
  $url .= $path;
  if ($method == 'GET') $url .= '?per_page=100&include=user';

  $context = stream_context_create($request);

  return json_decode(file_get_contents($url, false, $context));
}

function sort_by_user ($response) {
  usort($response, function($a, $b) {
    return strcmp($a->user->sortable_name, $b->user->sortable_name);
  });

  return $response;
}
