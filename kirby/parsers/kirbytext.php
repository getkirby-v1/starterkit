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
  var $attr        = array('text', 'file', 'width', 'height', 'link', 'popup', 'class', 'title', 'alt', 'rel', 'lang', 'target');

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

  function url($url, $lang=false, $metadata=false) {

    $file = false;
    
    if(preg_match('!(http|https)\:\/\/!i', $url)) {
      return (!$metadata) ? $url : array(
        'url'  => $url, 
        'file' => $file
      );
    }
            
    if($files = $this->relatedFiles()) {
      $file = $files->find($url);
      $url  = ($file) ? $file->url() : url($url, $lang);
    }
            
    return (!$metadata) ? $url : array(
      'url'  => $url,
      'file' => $file
    );

  }

  // get the current related page object
  function relatedPage() {
    global $site;
    return ($this->obj) ? $this->obj : $site->pages()->active();
  }

  // get related files for the related page
  function relatedFiles() {
    $object = $this->relatedPage();
    return ($object) ? $object->files() : null;
  }

  function link($params) {

    $url = @$params['link'];

    // sanitize the url
    if(empty($url)) $url = '/';

    // language attribute is only allowed when lang support is activated
    $lang = (!empty($params['lang']) && c::get('lang.support')) ? $params['lang'] : false;

    // get the full href
    $href = $this->url($url, $lang);

    $linkAttributes = $this->attr(array(
      'href'   => $href,
      'rel'    => @$params['rel'], 
      'class'  => @$params['class'], 
      'title'  => html(@$params['title']),       
    ));

    // get the text
    $text = (empty($params['text'])) ? $href : $params['text'];
        
    return '<a ' . $linkAttributes . self::target($params) . '>' . html($text) . '</a>';

  }

  function image($params) {
        
    $url   = @$params['image'];
    $alt   = @$params['alt'];
    $title = @$params['title'];

    // alt is just an alternative for text
    if(!empty($params['text'])) $alt = $params['text'];
    
    // get metadata (url + file) for the image url
    $imageMeta = $this->url($url, $lang = false, $metadata = true);

    // try to get the title from the image object and use it as alt text
    if($imageMeta['file']) {
      
      if(empty($alt) && $imageMeta['file']->alt() != '') {
        $alt = $imageMeta['file']->alt();
      }

      if(empty($title) && $imageMeta['file']->title() != '') {
        $title = $imageMeta['file']->title();
      }

      // last resort for no alt text
      if(empty($alt)) $alt = $title;

    }

    $imageAttributes = $this->attr(array(
      'src'    => $imageMeta['url'],
      'width'  => @$params['width'], 
      'height' => @$params['height'], 
      'class'  => @$params['class'], 
      'title'  => html($title), 
      'alt'    => html($alt)
    ));
            
    $image = '<img ' . $imageAttributes . ' />';

    if(!empty($params['link'])) {

      // build the href for the link
      $href = ($params['link'] == 'self') ? $url : $params['link'];

      $linkAttributes = $this->attr(array(
        'href'   => $this->url($href),
        'rel'    => @$params['rel'], 
        'class'  => @$params['class'], 
        'title'  => html(@$params['title']), 
      ));
      
      return '<a ' . $linkAttributes . self::target($params) . '>' . $image . '</a>';
    
    }

    return $image;
      
  }

  function file($params) {

    $url    = @$params['file'];
    $text   = @$params['text'];
    $class  = @$params['class'];
    $title  = @$params['title'];
    $target = self::target($params);

    if(empty($text))   $text  = str_replace('_', '\_', $url); // ignore markdown italic underscores in filenames
    if(!empty($class)) $class = ' class="' . $class . '"';
    if(!empty($title)) $title = ' title="' . html($title) . '"';

    return '<a' . $target . $title . $class . ' href="' . $this->url($url) . '">' . html($text) . '</a>';

  }

  static function attr($name, $value = null) {
    if(is_array($name)) {
      $attributes = array();
      foreach($name as $key => $val) {
        $a = self::attr($key, $val);
        if($a) $attributes[] = $a;
      }
      return implode(' ', $attributes);
    }  

    if(empty($value)) return false;
    return $name . '="' . $value . '"';    
  }  
  
  static function date($params) {
    $format = @$params['date'];
    return (str::lower($format) == 'year') ? date('Y') : date($format);
  }

  static function target($params) {
    if(empty($params['popup']) && empty($params['target'])) return false;
    if(empty($params['popup'])) {
      return ' target="' . $params['target'] . '"';
    } else {
      return ' target="_blank"';
    }
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

    return '<div class="video-container"><iframe' . $class . ' width="' . $params['width'] . '" height="' . $params['height'] . '" src="' . $url . '" frameborder="0" allowfullscreen></iframe></div>';
  
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

    return '<div class="video-container"><iframe' . $class . ' src="' . $url . '" width="' . $params['width'] . '" height="' . $params['height'] . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>';
      
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

