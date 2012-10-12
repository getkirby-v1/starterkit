<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

// easy url builder
function url($uri=false, $lang=false) {
    
  // get the base url of the site
  $baseUrl = c::get('url');

  // url() can also be used to link to css, img or js files
  // so we need to make sure that this is not a link to a real
  // file. Otherwise it will be broken by the rest of the code. 
  if($uri && is_file(c::get('root') . '/' . $uri)) {
    return $baseUrl . '/' . $uri;          
  }
    
  // prepare the lang variable for later
  if(c::get('lang.support')) {
    $lang = ($lang) ? $lang : c::get('lang.current');
    
    // prepend the language code to the uri
    $uri = $lang . '/' . ltrim($uri, '/');
  } 

  // if rewrite is deactivated
  // index.php needs to be prepended
  // so urls will still work
  if(!c::get('rewrite') && $uri) {
    $uri = 'index.php/' . $uri;
  }
  
  // return the final url and make sure 
  // we don't get double slashes by triming the uri   
  return $baseUrl . '/' . ltrim($uri, '/');

}

function u($uri=false, $lang=false) {
  return url($uri, $lang);
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
  $url = (str::match($url, '~(^\/\/|^https?:\/\/)~'))? $url : url(ltrim($url, '/'));
  if(!empty($media)) {
    return '<link rel="stylesheet" media="' . $media . '" href="' . $url . '" />' . "\n";
  } else {
    return '<link rel="stylesheet" href="' . $url . '" />' . "\n";
  }
}

// embed a js tag
function js($url, $async = false) {
  $url   = (str::match($url, '~(^\/\/|^https?:\/\/)~'))? $url : url(ltrim($url, '/'));
  $async = ($async) ? ' async' : '';
  return '<script' . $async . ' src="' . $url . '"></script>' . "\n";
}

// fetch a param from the URI
function param($key, $default=false) {
  global $site;
  return $site->uri->params($key, $default);
}

// smart version of echo with an if condition as first argument
function ecco($condition, $echo, $alternative = false) {
  echo ($condition) ? $echo : $alternative;
}

function dump($var) {
  return a::show($var);
}