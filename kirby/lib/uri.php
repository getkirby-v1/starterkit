<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

// default param separator
if(!c::get('uri.param.separator')) {
  // check for linux or windows
  c::set('uri.param.separator', (DIRECTORY_SEPARATOR == '/') ? ':' : ';');
}

class uri {

  function __construct($uri=false) {
    
    // set the defaults
    $this->path      = new uriPath();
    $this->params    = new uriParams();
    $this->query     = new uriQuery(str::parse(server::get('query_string'), 'query'));
    $this->extension = false;
    $this->original  = $_SERVER['REQUEST_URI'];
    $this->raw       = $this->raw($uri);
    $this->url       = url(ltrim($this->raw, '/'));
        
    // crawl the uri and get all elements   
    $this->crawl();
        
  }

  function __toString() {
    return $this->toString();
  }

  static function raw($uri=false) {
    $raw = ($uri) ? $uri : ltrim($_SERVER['REQUEST_URI'], '/');
    $raw = ltrim(str_replace('index.php', '', $raw), '/');

    // strip subfolders from uri    
    if(c::get('subfolder'))    $raw = ltrim(preg_replace('!^' . preg_quote(c::get('subfolder')) . '(\/|)!i', '/', $raw), '/');
    if(c::get('lang.support')) $raw = ltrim(preg_replace('!^' . preg_quote(c::get('lang.current')) . '(\/|)!i', '/', $raw), '/');
            
    return $raw;
  }

  function crawl() {

    $path = url::strip_query($this->raw);
    $path = (array)str::split($path, '/');
    if(a::first($path) == 'index.php') array_shift($path);
            
    // parse params
    foreach($path AS $p) {
      if(str::contains($p, c::get('uri.param.separator'))) {
        $parts = explode(c::get('uri.param.separator'), $p);
        if(count($parts) < 2) continue;
        $this->params->$parts[0] = $parts[1];
      } else {
        $this->path->_[] = $p;
      }
    }
        
    // get the extension from the last part of the path    
    $this->extension = f::extension($this->path->last());
      
    if($this->extension != false) {
      // remove the last part of the path
      $last = array_pop($this->path->_);
      $this->path->_[] = f::name($last);
    }

    return $this->path;

  }

  function path($key=false, $default=false) {
    if(!$key) return $this->path;
    return $this->path->find($key, $default);
  }

  function param($key=false, $default=false) {
    if(!$key) return $this->params;
    return $this->params->find($key, $default);
  }

  function params($key=false, $default=false) {
    return $this->param($key, $default);
  }
    
  function query($key=false, $default=false) {
    if(!$key) return $this->query;
    return $this->query->find($key, $default);
  }

  function toString($includeQuery=true) {
    
    $parts  = array();
    $path   = $this->path();
    $params = $this->params();
    $query  = $this->query();
        
    if(!empty($path->_))   $parts[] = (string)$path;
    if(!empty($params->_)) $parts[] = (string)$params;

    if($includeQuery && !empty($query->_)) $parts[] = '?' . $query;
        
    return implode('/', $parts);  
  
  }

  function toUrl($includeQuery=true) {
    return url($this->toString($includeQuery));
  }
  
  function toCacheID() {
    $url = $this->toURL();
    return md5($url);    
  }

  function stripPath() {
    $this->path = new uriPath();
    return $this;
  }

  function replaceParam($key, $value) {
    $this->params->{$key} = $value;
    return $this;
  }

  function removeParam($key) {
    unset($this->params->_[$key]);
    return $this;
  }

  function stripParams() {
    $this->params = new uriParams();
    return $this;
  }

  function urlWithoutParam($param) {
    $this->removeParam($param);
    return $this->toUrl();    
  }

  function replaceQueryKey($key, $value) {
    $this->query->{$key} = $value;
    return $this;
  }

  function removeQueryKey($key) {
    unset($this->query->_[$key]);
    return $this;
  }

  function urlWithoutQueryKey($key) {
    $this->removeQueryKey($key);
    return $this->toUrl();    
  }

  function stripQuery() {
    $this->query = new uriQuery();
    return $this;
  }

} 

class uriPath extends obj {
  
  function __toString() {
    return $this->toString();
  }
 
  function toString() {
    return implode('/', $this->_);  
  }
  
  function find($key=false, $default=false) {
    if($key===false) return $this->_;
    $key--;
    return a::get($this->_, $key, $default);
  }
  
}

class uriParams extends obj {

  function __toString() {
    return $this->toString();
  }

  function toString() {
    $output = array();
    foreach($this->_ as $key => $value) {
      $output[] = $key . c::get('uri.param.separator') . $value;
    }        
    return implode('/', $output);
  }

}

class uriQuery extends obj {
  
  function __toString() {
    return $this->toString();
  }

  function toString() {
    return http_build_query($this->_);    
  }
  
}

