<?php

function get_cheater_message () {
  return file_get_contents(__DIR__ . '/../conf/cheater.txt');
}

function get_random_quote () {
  $quotes = file(__DIR__ . '/../conf/quotes.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

  return $quotes[array_rand($quotes)];
}

function generate_comment ($quote, $markbot_mouth, $gh_username, $gh_repo, $cheater_message = '') {
  $text = file_get_contents(__DIR__ . '/../conf/markbot.txt');
  $replacements = [
    '${markbot_mouth}' => $markbot_mouth,
    '${quote}' => $quote,
    '${cheater_message}' => $cheater_message,
    '${gh_username}' => $gh_username,
    '${gh_repo}' => $gh_repo
  ];

  return trim(str_replace(array_keys($replacements), $replacements, $text));
}

function get_comment ($gh_username, $gh_repo, $cheater) {
  if ($cheater == 0) {
    $quote = get_random_quote();
    $cheater_message = '';
    $markbot_mouth = '◡';
  } else {
    $quote = 'CHEATER';
    $cheater_message = get_cheater_message();
    $markbot_mouth = '〜';
  }

  return generate_comment($quote, $markbot_mouth, $gh_username, $gh_repo, $cheater_message);
}
