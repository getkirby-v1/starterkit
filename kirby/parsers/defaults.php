<?php

// create safe html
function html($text) {
  return str::html($text, false);
}

// shortcut for html
function h($text) {
  return html($text);
}

// create safe xml
function xml($text) {
  return str::xml($text);
}

// create multiline html
function multiline($text) {
  return nl2br(html($text));
}

?>