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

// embed a stylesheet tag & add to queue
function css($url=false, $media=false, $queue=-1, $less=0) {
  global $cssqueue;
  if($url == false) {
    return co::css();
  }
  if((($queue == -1 || $queue == true) && c::get('compress.css')) || ($queue == true && !c::get('compress.css'))) {
	  $cssqueue[$less][$media][] = $url;
  } 
  $url = (str::contains($url, 'http://') || str::contains($url, 'https://')) ? $url : url(ltrim($url, '/'));
  if(!empty($media)) {
    return '<link rel="stylesheet" media="' . $media . '" href="' . $url . '" />' . "\n";
  } else {
    return '<link rel="stylesheet" href="' . $url . '" />' . "\n";
  }
}

// embed a stylesheet tag with LESS and cache the compiled CSS
function less($url=false, $media=false, $queue=-1) {
  if(!c::get('cache')) {
    return false;
  }
  global $lessc;
  require_once(c::get('root.parsers') . '/less.php');
  $lessc = new lessc();
  return css($url, $media, $queue, 1);
}

// embed a js tag & add to queue
function js($url=false, $queue=-1) {
  global $jsqueue;
  if($url == false) {
    return co::js();
  }
  if((($queue == -1 || $queue == true) && c::get('compress.js')) || ($queue == true && !c::get('compress.js'))) {
    $jsqueue[0][false][] = $url;
  } 
  $url = (str::contains($url, 'http://') || str::contains($url, 'https://')) ? $url : url(ltrim($url, '/'));
  return '<script src="' . $url . '"></script>' . "\n";
}

// fetch a param from the URI
function param($key, $default=false) {
  global $site;
  return $site->uri->params($key, $default);
}

?>
