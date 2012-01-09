<?php

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
  tpl::loadFile(c::get('root.snippets') . '/' . $snippet . '.php', $data, $return);
}

// embed a stylesheet tag
function css($url, $media='all') {
  $url = (str::contains($url, 'http://') || str::contains($url, 'https://')) ? $url : url(ltrim($url, '/'));
  return '<link rel="stylesheet" type="text/css" media="' . $media . '" href="' . $url . '" />' . "\n";
}

// embed a js tag
function js($url) {
  $url = (str::contains($url, 'http://') || str::contains($url, 'https://')) ? $url : url(ltrim($url, '/'));
  return '<script type="text/javascript" src="' . $url . '"></script>' . "\n";
}

?>