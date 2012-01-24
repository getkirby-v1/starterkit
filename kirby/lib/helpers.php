<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

// easy url builder
function url($uri=false) {
  if(c::get('rewrite')) {
    return c::get('url') . '/' . $uri;
  } else {
    if(!$uri) return c::get('url');
    if(is_file(c::get('root') . '/' . $uri)) {
      return c::get('url') . '/' . $uri;
    } else {
      return c::get('url') . '/index.php/' . $uri;
    }
  }
}

function u($uri=false) {
  return url($uri);
}

// return the current url with all
// bells and whistles
function thisURL() {
  global $site;
  return $site->uri->toURL();
}

// go home
function home() {
  go(url());
}

// go to error page
function notFound() {
  go(url('error'));
}

// embed a template snippet from the snippet folder
function snippet($snippet, $data=array(), $return=false) {
  return tpl::loadFile(c::get('root.snippets') . '/' . $snippet . '.php', $data, $return);
}

// embed a stylesheet tag
function css($url, $media=false) {
  $url = (str::contains($url, 'http://') || str::contains($url, 'https://')) ? $url : url(ltrim($url, '/'));
  if(!empty($media)) {
    return '<link rel="stylesheet" media="' . $media . '" href="' . $url . '" />' . "\n";
  } else {
    return '<link rel="stylesheet" href="' . $url . '" />' . "\n";
  }
}

// embed a js tag
function js($url) {
  $url = (str::contains($url, 'http://') || str::contains($url, 'https://')) ? $url : url(ltrim($url, '/'));
  return '<script src="' . $url . '"></script>' . "\n";
}

// fetch a param from the URI
function param($key, $default=false) {
  global $site;
  return $site->uri->params($key, $default);
}

?>
