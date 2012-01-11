<?php

function kirbytext($text, $markdown=true) {
  $text = kirbytext::get($text); 
  if($markdown) $text = markdown($text);
  return $text;  
}

// create an excerpt without html and kirbytext
function excerpt($text, $length=140) {
  return str::excerpt(kirbytext($text), $length);
}

function youtube($url, $width=false, $height=false) {
  return kirbytext::youtube(array(
    'youtube' => $url,
    'width'   => $width,
    'height'  => $height
  ));
}

function vimeo($url, $width=false, $height=false) {
  return kirbytext::vimeo(array(
    'vimeo'  => $url,
    'width'  => $width,
    'height' => $height
  ));
}

function flash($url, $width=false, $height=false) {
  return kirbytext::flash($url, $width, $height);
}

function twitter($username, $text=false) {
  return kirbytext::twitter(array(
    'twitter' => $username,
    'text'    => $text
  ));
}

function gist($url, $file=false) {
  return kirbytext::gist(array(
    'gist' => $url,
    'file' => $file
  ));
}


class kirbytext {
  
  static public $obj  = false;
  static public $tags = array('gist', 'twitter', 'date', 'image', 'file', 'link', 'email', 'youtube', 'vimeo');
  static public $attr = array('text', 'file', 'width', 'height', 'link', 'popup', 'class');
  
  static function get($text) {
    // pass the parent page if available
    if(is_object($text)) self::$obj = $text->parent;
    $text = preg_replace_callback('!(?=[^\]])\((' . implode('|', self::$tags) . '):(.*?)\)!i', 'kirbytext::parse', $text);
    $text = preg_replace_callback('!```(.*?)```!is', 'kirbytext::code', $text);
    return $text;       
  }

  static function code($code) {
    
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

  static function parse($args) {

    $method = strtolower(@$args[1]);
    $string = @$args[0];    
    
    if(empty($string)) return false;
    if(!method_exists('kirbytext', $method)) return $string;
    
    $replace = array('(', ')');            
    $string  = str_replace($replace, '', $string);
    $attr    = array_merge(self::$tags, self::$attr);
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

    return self::$method($result);
        
  }
  
  static function date($params) {
    
    $format = @$params['date'];
    
    if(str::lower($format) == 'year') {
      return date('Y');            
    } else {
      return date($format);        
    }
    
  }

  static function url($url) {
    if(str::contains($url, 'http://') || str::contains($url, 'https://')) return $url;

    if(!self::$obj) {
      global $site;
      
      // search for a matching 
      $files = $site->pages()->active()->files();
    } else {
      $files = self::$obj->files();
    }
            
    if($files) {
      $file = $files->find($url);
      $url = ($file) ? $file->url() : url($url);
    }

    return $url;
  }

  static function target($params) {
    if(empty($params['popup'])) return false;
    return ' target="_blank"';
  }

  static function link($params) {

    $url    = @$params['link'];
    $class  = @$params['class'];
    $target = self::target($params);
    
    if(empty($url)) return false;
    if(empty($params['text'])) return '<a' . $target . ' href="' . self::url($url) . '">' . h($url) . '</a>';

    // add a css class if available
    if(!empty($class)) $class = ' class="' . $class . '"';

    return '<a' . $target . $class . ' href="' . self::url($url) . '">' . h($params['text']) . '</a>';

  }

  static function email($params) {
    
    $url = @$params['email'];
    
    if(empty($url)) return false;
    return str::email($url, @$params['text']);

  }

  static function twitter($params) {
    
    $username = @$params['twitter'];
    $target = self::target($params);
    
    if(empty($username)) return false;

    $username = str_replace('@', '', $username);
    $url = 'http://twitter.com/' . $username;
    
    if(empty($params['text'])) return '<a' . $target . ' href="' . self::url($url) . '">@' . h($username) . '</a>';

    return '<a' . $target . ' href="' . self::url($url) . '">' . h($params['text']) . '</a>';

  }
  
  static function image($params) {
    
    global $site;
    
    $url    = @$params['image'];
    $text   = @$params['text'];
    $class  = @$params['class'];
    $target = self::target($params);

    // width/height
    $w = a::get($params, 'width');
    $h = a::get($params, 'height');

    if(!empty($w)) $w = ' width="' . $w . '"';
    if(!empty($h)) $h = ' height="' . $h . '"';
    
    // add a css class if available
    if(!empty($class)) $class = ' class="' . $class . '"';

    if(empty($text)) $text = $site->title();
            
    $image = '<img src="' . self::url($url) . '"' . $w . $h . $class . ' alt="' . h($text) . '" />';

    if(!empty($params['link'])) {
      return '<a class="image"' . $target . ' href="' . self::url($params['link']) . '">' . $image . '</a>';
    }
    
    return $image;
    
  }

  static function file($params) {

    $url    = @$params['file'];
    $text   = @$params['text'];
    $target = self::target($params);

    if(empty($text)) $text = h($url);

    return '<a class="file"' . $target . ' href="' . self::url($url) . '">' . h($text) . '</a>';

  }

  static function youtube($params) {

    $url = @$params['youtube'];
    $id  = false;
    
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
    if(!$params['width'])  $params['width']  = c::get('kirbytext.video.width');
    if(!$params['height']) $params['height'] = c::get('kirbytext.video.height');

    return '<iframe width="' . $params['width'] . '" height="' . $params['height'] . '" src="' . $url . '" frameborder="0" allowfullscreen></iframe>';
  
  }

  static function vimeo($params) {

    $url = @$params['vimeo'];
    
    // get the uid from the url
    @preg_match('!vimeo.com\/([a-z0-9_-]+)!i', $url, $array);
    $id = a::get($array, 1);
    
    // no id no result!    
    if(empty($id)) return false;    

    // build the embed url for the iframe    
    $url = 'http://player.vimeo.com/video/' . $id;

    // default width and height if no custom values are set
    if(!$params['width'])  $params['width']  = c::get('kirbytext.video.width');
    if(!$params['height']) $params['height'] = c::get('kirbytext.video.height');

    return '<iframe src="' . $url . '" width="' . $params['width'] . '" height="' . $params['height'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
      
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

}

?>