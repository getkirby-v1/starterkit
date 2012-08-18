<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

function kirbytext($text, $markdown=true) {
  return kirbytext::init($text, $markdown);
}

// create an excerpt without html and kirbytext
function excerpt($text, $length=140, $markdown=true) {
  return str::excerpt(kirbytext::init($text, $markdown), $length);
}

function youtube($url, $width=false, $height=false, $class=false) {
  $name = kirbytext::classname();
  $obj  = new $name;
  return $obj->youtube(array(
    'youtube' => $url,
    'width'   => $width,
    'height'  => $height,
    'class'   => $class
  ));
}

function vimeo($url, $width=false, $height=false, $class=false) {
  $name = kirbytext::classname();
  $obj  = new $name;
  return $obj->vimeo(array(
    'vimeo'  => $url,
    'width'  => $width,
    'height' => $height,
    'class'  => $class
  ));
}

function flash($url, $width=false, $height=false) {
  $name = kirbytext::classname();
  $obj  = new $name;
  return $obj->flash($url, $width, $height);
}

function twitter($username, $text=false, $title=false, $class=false) {
  $name = kirbytext::classname();
  $obj  = new $name;
  return $obj->twitter(array(
    'twitter' => $username,
    'text'    => $text,
    'title'   => $title,
    'class'   => $class
  ));
}

function gist($url, $file=false) {
  $name = kirbytext::classname();
  $obj  = new $name;
  return $obj->gist(array(
    'gist' => $url,
    'file' => $file
  ));
}


class kirbytext {
  
  var $obj         = null;
  var $text        = null;
  var $mdown       = true;
  var $smartypants = true;
  var $tags        = array('gist', 'twitter', 'date', 'image', 'file', 'link', 'email', 'youtube', 'vimeo');
  var $attr        = array('text', 'file', 'width', 'height', 'link', 'popup', 'class', 'title', 'alt', 'rel', 'lang');

  static function init($text=false, $mdown=true, $smartypants=true) {
    
    $classname = self::classname();            
    $kirbytext = new $classname($text, $mdown, $smartypants);    
    return $kirbytext->get();    
              
  }

  function __construct($text=false, $mdown=true, $smartypants=true) {
      
    $this->text        = $text;  
    $this->mdown       = $mdown;
    $this->smartypants = $smartypants;
          
    // pass the parent page if available
    if(is_object($this->text)) $this->obj = $this->text->parent;

  }
  
  function get() {

    $text = preg_replace_callback('!(?=[^\]])\((' . implode('|', $this->tags) . '):(.*?)\)!i', array($this, 'parse'), (string)$this->text);
    $text = preg_replace_callback('!```(.*?)```!is', array($this, 'code'), $text);
    
    $text = ($this->mdown) ? markdown($text) : $text;
    $text = ($this->smartypants) ? smartypants($text) : $text;
    
    return $text;
    
  }

  function code($code) {
    
    $code = @$code[1];
    $lines = explode("\n", $code);
    $first = trim(array_shift($lines));
    $code  = implode("\n", $lines);
    $code  = trim($code);

    if(function_exists('highlight')) {
      $result  = '<pre class="highlight ' . $first . '">';
      $result .= '<code>';
      $result .= highlight($code, (empty($first)) ? 'php-html' : $first);
      $result .= '</code>';
      $result .= '</pre>';
    } else {
      $result  = '<pre class="' . $first . '">';
      $result .= '<code>';
      $result .= htmlspecialchars($code);
      $result .= '</code>';
      $result .= '</pre>';
    }
    
    return $result;
    
  }

  function parse($args) {

    $method = strtolower(@$args[1]);
    $string = @$args[0];    
    
    if(empty($string)) return false;
    if(!method_exists($this, $method)) return $string;
    
    $replace = array('(', ')');            
    $string  = str_replace($replace, '', $string);
    $attr    = array_merge($this->tags, $this->attr);
    $search  = preg_split('!(' . implode('|', $attr) . '):!i', $string, false, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
    $result  = array();
    $num     = 0;
    
    foreach($search AS $key) {
    
      if(!isset($search[$num+1])) break;
      
      $key   = trim($search[$num]);
      $value = trim($search[$num+1]);

      $result[ $key ] = $value;
      $num = $num+2;

    }

    return $this->$method($result);
        
  }

  function url($url, $lang=false) {
    if(str::contains($url, 'http://') || str::contains($url, 'https://')) return $url;

    if(!$this->obj) {
      global $site;
      
      // search for a matching 
      $files = $site->pages()->active()->files();
    } else {
      $files = $this->obj->files();
    }
            
    if($files) {
      $file = $files->find($url);
      $url = ($file) ? $file->url() : url($url, $lang);
    }
            
    return $url;
  }

  function link($params) {

    $url    = @$params['link'];
    $class  = @$params['class'];
    $rel    = @$params['rel'];
    $title  = @$params['title'];
    $lang   = @$params['lang'];
    $target = self::target($params);

    // language attribute is only allowed when lang support is activated
    if($lang && !c::get('lang.support')) $lang = false;

    // add a css class if available
    if(!empty($class)) $class = ' class="' . $class . '"';
    if(!empty($rel))   $rel   = ' rel="' . $rel . '"';
    if(!empty($title)) $title = ' title="' . html($title) . '"';
        
    if(empty($url)) $url = '/';
    if(empty($params['text'])) return '<a' . $target . $class . $rel . $title . ' href="' . $this->url($url, $lang) . '">' . html($url) . '</a>';

    return '<a' . $target . $class . $rel . $title . ' href="' . $this->url($url, $lang) . '">' . html($params['text']) . '</a>';

  }

  function image($params) {
    
    global $site;
    
    $url    = @$params['image'];
    $text   = @$params['text'];
    $class  = @$params['class'];
    $alt    = @$params['alt'];
    $title  = @$params['title'];
    $target = self::target($params);

    // alt is just an alternative for text
    if(!empty($text)) $alt = $text;

    // width/height
    $w = a::get($params, 'width');
    $h = a::get($params, 'height');

    if(!empty($w)) $w = ' width="' . $w . '"';
    if(!empty($h)) $h = ' height="' . $h . '"';
    
    // add a css class if available
    if(!empty($class)) $class = ' class="' . $class . '"';
    if(!empty($title)) $title = ' title="' . html($title) . '"';
    if(empty($alt))    $alt   = $site->title();
            
    $image = '<img src="' . $this->url($url) . '"' . $w . $h . $class . $title . ' alt="' . html($alt) . '" />';

    if(!empty($params['link'])) {
      if($params['link'] == 'self') $params['link'] = $url;
      return '<a' . $class . $target . $title . ' href="' . $this->url($params['link']) . '">' . $image . '</a>';
    }
    
    return $image;
    
  }

  function file($params) {

    $url    = @$params['file'];
    $text   = @$params['text'];
    $class  = @$params['class'];
    $title  = @$params['title'];
    $target = self::target($params);

    if(empty($text))   $text  = $url;
    if(!empty($class)) $class = ' class="' . $class . '"';
    if(!empty($title)) $title = ' title="' . html($title) . '"';

    return '<a' . $target . $title . $class . ' href="' . $this->url($url) . '">' . html($text) . '</a>';

  }
  
  static function date($params) {
    $format = @$params['date'];
    return (str::lower($format) == 'year') ? date('Y') : date($format);
  }

  static function target($params) {
    if(empty($params['popup'])) return false;
    return ' target="_blank"';
  }

  static function email($params) {
    
    $url   = @$params['email'];
    $class = @$params['class'];
    $title = @$params['title'];
    
    if(empty($url)) return false;
    return str::email($url, @$params['text'], $title, $class);

  }

  static function twitter($params) {
    
    $username = @$params['twitter'];
    $class    = @$params['class'];
    $title    = @$params['title'];
    $target   = self::target($params);
    
    if(empty($username)) return false;

    $username = str_replace('@', '', $username);
    $url = 'http://twitter.com/' . $username;

    // add a css class if available
    if(!empty($class)) $class = ' class="' . $class . '"';
    if(!empty($title)) $title = ' title="' . html($title) . '"';
    
    if(empty($params['text'])) return '<a' . $target . $class . $title . ' href="' . $url . '">@' . html($username) . '</a>';

    return '<a' . $target . $class . $title . ' href="' . $url . '">' . html($params['text']) . '</a>';

  }
  
  static function youtube($params) {

    $url   = @$params['youtube'];
    $class = @$params['class'];
    $id    = false;
    
    // http://www.youtube.com/embed/d9NF2edxy-M
    if(@preg_match('!youtube.com\/embed\/([a-z0-9_-]+)!i', $url, $array)) {
      $id = @$array[1];      
    // http://www.youtube.com/watch?feature=player_embedded&v=d9NF2edxy-M#!
    } elseif(@preg_match('!v=([a-z0-9_-]+)!i', $url, $array)) {
      $id = @$array[1];
    // http://youtu.be/d9NF2edxy-M
    } elseif(@preg_match('!youtu.be\/([a-z0-9_-]+)!i', $url, $array)) {
      $id = @$array[1];
    }
        
    // no id no result!    
    if(empty($id)) return false;
    
    // build the embed url for the iframe    
    $url = 'http://www.youtube.com/embed/' . $id;
    
    // default width and height if no custom values are set
    if(empty($params['width']))  $params['width']  = c::get('kirbytext.video.width');
    if(empty($params['height'])) $params['height'] = c::get('kirbytext.video.height');
    
    // add a classname to the iframe
    if(!empty($class)) $class = ' class="' . $class . '"';

    return '<iframe' . $class . ' width="' . $params['width'] . '" height="' . $params['height'] . '" src="' . $url . '" frameborder="0" allowfullscreen></iframe>';
  
  }

  static function vimeo($params) {

    $url   = @$params['vimeo'];
    $class = @$params['class'];
    
    // get the uid from the url
    @preg_match('!vimeo.com\/([a-z0-9_-]+)!i', $url, $array);
    $id = a::get($array, 1);
    
    // no id no result!    
    if(empty($id)) return false;    

    // build the embed url for the iframe    
    $url = 'http://player.vimeo.com/video/' . $id;

    // default width and height if no custom values are set
    if(empty($params['width']))  $params['width']  = c::get('kirbytext.video.width');
    if(empty($params['height'])) $params['height'] = c::get('kirbytext.video.height');

    // add a classname to the iframe
    if(!empty($class)) $class = ' class="' . $class . '"';

    return '<iframe' . $class . ' src="' . $url . '" width="' . $params['width'] . '" height="' . $params['height'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
      
  }

  static function flash($url, $w, $h) {

    if(!$w) $w = c::get('kirbytext.video.width');
    if(!$h) $h = c::get('kirbytext.video.height');

    return '<div class="video"><object width="' . $w . '" height="' . $h . '"><param name="movie" value="' . $url . '"><param name="allowFullScreen" value="true"><param name="allowScriptAccess" value="always"><embed src="' . $url . '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $w . '" height="' . $h . '"></embed></object></div>';  

  }

  static function gist($params) {
    $url  = @$params['gist'] . '.js';
    $file = @$params['file'];
    if(!empty($file)) {
      $url = $url .= '?file=' . $file;
    }
    return '<script src="' . $url . '"></script>';
  }

  static function classname() {
    return class_exists('kirbytextExtended') ? 'kirbytextExtended' : 'kirbytext';
  }

  function addTags() {
    $args = func_get_args();
    $this->tags = array_merge($this->tags, $args);
  }

  function addAttributes($attr) {
    $args = func_get_args();
    $this->attr = array_merge($this->attr, $args);      
  }
  
}

