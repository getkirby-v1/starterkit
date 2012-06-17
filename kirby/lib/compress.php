<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class co {
  // get collective links
  static function css() {
    $args = func_get_args();
    if(isset($args[0])) {
      $indent = $args[0];
      unset($args[0]);
    } else {
      $args = array('all', 'aural', 'braille', 'embossed', 'handheld', 'print', 'projection', 'screen', 'tty', 'tv', -1);
      $indent = '';
    }
    $return = "";
    foreach($args as $media) {
      if($media == -1) {
        $media = false;
      }
      $url = self::get(1, $media);
      if(is_array($url)) {
        foreach($url as $now) {
          if(!isset($now)) {
            continue;
          }
          $now = (str::contains($now, 'http://') || str::contains($now, 'https://')) ? $now : url(ltrim($now, '/'));
          if(!empty($media)) {
            $return .= $indent . '<link rel="stylesheet" media="' . $media . '" href="' . $now . '" />' . "\n";
          } else {
            $return .= $indent . '<link rel="stylesheet" href="' . $now . '" />' . "\n";
          }
        }
      } else {
        if(!empty($media) && $url != '') {
          $return .= $indent . '<link rel="stylesheet" media="' . $media . '" href="' . $url . '" />' . "\n";
        } else if($url != '') {
          $return .= $indent . '<link rel="stylesheet" href="' . $url . '" />' . "\n";
        }
      }
    }
    return $return;
  }
  static function js($indent="") {
    $url = self::get(0);
    if(is_array($url)) {
      $return = "";
      foreach($url as $now) {
        $now = (str::contains($now, 'http://') || str::contains($now, 'https://')) ? $now : url(ltrim($now, '/'));
        $return .= $indent . '<script src="' . $now . '"></script>' . "\n";
      }
      return $return;
    }
    return $indent . '<script src="' . $url . '"></script>' . "\n";
  }
  
  // compress css/js
  private function compress($url, $what, $less, $parsed=false) {
    global $lessc;
    if($parsed == true) {
      $buffer = $url;
    } else {
      $buffer = file_get_contents($url);
    }
    if($less == 1) {
      $buffer = $lessc->parse($buffer);
    }
    if(c::get("compress.$what")) {
      if($what == "css") {
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
        $buffer = preg_replace('/\s\s+/', ' ', $buffer);
        $buffer = str_replace(array(' { ', ' }', '; ', ', ', ': '), array('{', '}', ';', ',', ':'), $buffer);
      } else if($what == "js") {
        require_once(c::get('root.parsers') . '/jsmin.php');
        $buffer = JSMin::minify($buffer);
      } else {
        require_once(c::get('root.parsers') . '/htmlmin.php');
        $buffer = Minify_HTML::minify($buffer, array('cssMinifier' => 'co::compresscss', 'jsMinifier' => 'co::compressjs');
      }
      return $buffer;
    } else {
      return $buffer;
    }
  }
  static function compresscss($content) {
    return self::compress($content, 'css', 0, true);
  }
  static function compressjs($content) {
    return self::compress($content, 'js', 0, true);
  }
  static function compresshtml($content) {
    return self::compress($content, 'html', 0, true);
  }
  
  // build collective urls
  static function get($what, $media=false) {
    global $cssqueue, $jsqueue, $lessc;
    if(file_exists(c::get('root.cache') . '/compress.ser')) {
      $data = unserialize(file_get_contents(c::get('root.cache') . '/compress.ser'));
      $datab = $data;
    } else {
      $data = array();
      $datab = array();
    }
    if($what == 0) {
      $what = "js";
      $queue = $jsqueue;
    } else {
      $what = "css";
      $queue = $cssqueue;
    }
    if(isset($queue[0][$media]) && is_array($queue[0][$media]) && isset($queue[1][$media]) && is_array($queue[1][$media])) {
      $queue[0] = $queue[0][$media];
      $queue[1] = $queue[1][$media];
    } else if(isset($queue[0][$media]) && is_array($queue[0][$media])) {
      $queue[0] = $queue[0][$media];
      $queue[1] = array();
    } else if(isset($queue[1][$media]) && is_array($queue[1][$media])) {
      $queue[1] = $queue[1][$media];
      $queue[0] = array();
    } else {
      $queue[0] = array();
      $queue[1] = array();
    }
    $return = "";
    $currentfiles = array();
    if(isset($data["ids"])) {
      foreach($queue as $less => $array) {
        foreach($array as $id => $name) {
          if(!file_exists($name)) {
            unset($queue[$less][$id]);
            unset($url);
            continue;
          }
          $name = $queue[$less][$id];
          $currentfiles[$id] = md5($name) . '.' . $what;
        }
      }
      foreach($data["ids"] as $id => $files) {
        if($currentfiles == $files) {
          $uniqid = $id;
          break;
        }
      }
    }
    if(!c::get('cache') && isset($queue[0][0])) {
      return '/?assets=' . implode(",", $queue[0]);
    } else if(!c::get('cache')) {
      return '';
    }
    if(!isset($uniqid)) {
      $uniqid = uniqid();
    }
    foreach($queue as $less => $array) {
      foreach($array as $id => $url) {
        if(!file_exists($url)) {
          continue;
        }
        $md5 = md5_file($url);
        $cachename = md5($url);
        if(!isset($data["files"][$url]) || $data["files"][$url] != $md5 || !file_exists(c::get('root.cache') . '/' . $cachename . '.' . $what)) {
          $data["files"][$url] = $md5;
          f::write(c::get('root.cache') . '/' . $cachename . '.' . $what, self::compress($url, $what, $less));
        }
        if(isset($data["ids"][$uniqid])) {
          $flip = array_flip($data["ids"][$uniqid]);
        } else {
          $flip = array();
        }
        if(!isset($flip[$cachename . '.' . $what])) {
          $data["ids"][$uniqid][] = $cachename . '.' . $what;
        }
      }
    }
    f::write(c::get('root.cache') . '/compress.ser', serialize($data));
    $url = '/?assets=' . $uniqid;
    if($data == $datab && !(isset($queue[0][0]) || isset($queue[1][0]))) {
      return '';
    }
    return $url;
  }
  
  // collect compressed assets
  static function collect($what) {
    if(!c::get('cache')) {
      $files = explode(",", $what);
      $path = "";
    } else {
      if(file_exists(c::get('root.cache') . '/compress.ser')) {
        $data = @unserialize(file_get_contents(c::get('root.cache') . '/compress.ser')) or die();
      } else {
        return false;
      }
      $files = $data["ids"][$what];
      $path = c::get('root.cache') . '/';
    }
    if(!isset($files[0])) {
      die();
    }
    $pathinfo = pathinfo($path . $files[0]);
    $return = "";
    if($pathinfo["extension"] == "css") {
      $contenttype = "text/css";
    } else {
      $contenttype = "text/javascript";
    }
    header("Content-Type: " . $contenttype);
    foreach($files as $file) {
      if($file == "") {
        continue;
      }
      if(substr($file, 0, 1) != '/' && substr($file, 0, 4) != 'http' && $path == "") {
        $file = c::get('root') . '/' . $file;
      } else if(substr($file, 0, 1) != '/' && substr($file, 0, 4) != 'http') {
        $file = $path . $file;
      }
      $return .= @file_get_contents($file) or die();
    }
    return $return;
  }
}

?>