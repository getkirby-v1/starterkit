<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class co {
  // get collective links
  static function css() {
    $args = func_get_args();
    $indent = $args[0];
    unset($args[0]);
    $return = "";
    foreach($args as $media) {
      if($media == -1) {
        $media = false;
      }
      $url = self::get(1, $media);
      if(is_array($url)) {
        foreach($url as $now) {
          $now = (str::contains($now, 'http://') || str::contains($now, 'https://')) ? $now : url(ltrim($now, '/'));
          if(!empty($media)) {
            $return .= $indent . '<link rel="stylesheet" media="' . $media . '" href="' . $now . '" />' . "\n";
          } else {
            $return .= $indent . '<link rel="stylesheet" href="' . $now . '" />' . "\n";
          }
        }
      } else {
        if(!empty($media)) {
          $return .= $indent . '<link rel="stylesheet" media="' . $media . '" href="' . $url . '" />' . "\n";
        } else {
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
  private function compress($url, $what) {
    if(c::get("compress.$what")) {
      $buffer = file_get_contents($url);
      if($what == "css") {
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t"), '', $buffer);
        $buffer = preg_replace('/\s\s+/', ' ', $buffer);
      } else {
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = preg_replace('!//[^\n\r]*!', '', $buffer);
        $buffer = str_replace("\t", "", $buffer);
        $buffer = preg_replace('/(\n)\n+/', '$1', $buffer);
        $buffer = preg_replace('/(\n)\ +/', '$1', $buffer);
        $buffer = preg_replace('/(\r)\r+/', '$1', $buffer);
        $buffer = preg_replace('/(\r\n)(\r\n)+/', '$1', $buffer);
        $buffer = preg_replace('/(\ )\ +/', '$1', $buffer);
      }
      return $buffer;
    } else {
      return file_get_contents($url);
    }
  }
  
  // build collective urls
  static function get($what, $media=false) {
    global $cssqueue, $jsqueue;
    if(file_exists(c::get('root.cache') . '/compress.ser')) {
      $data = unserialize(file_get_contents(c::get('root.cache') . '/compress.ser'));
    } else {
      $data = array();
    }
    if($what == 0) {
      $what = "js";
      $queue = $jsqueue;
    } else {
      $what = "css";
      $queue = $cssqueue;
    }
    if(isset($queue[$media]) && is_array($queue[$media])) {
      $queue = $queue[$media];
    } else {
      $queue = array();
    }
    if(!c::get('cache')) {
      return '/?assets=' . implode(",", $queue);
    }
    $return = "";
    if(isset($data["ids"])) {
      foreach($queue as $id => $name) {
        if(substr($name, 0, 1) != '/' && substr($name, 0, 4) != 'http') {
          $name = c::get('root') . '/' . $name;
          $queue[$id] = $name;
        } else {
          $name = $queue[$id];
        }
        $currentfiles[$id] = md5($name) . '.' . $what;
      }
      foreach($data["ids"] as $id => $files) {
        if($currentfiles == $files) {
          $uniqid = $id;
          break;
        }
      }
    }
    if(!isset($uniqid)) {
      $uniqid = uniqid();
    }
    foreach($queue as $id => $url) {
      $md5 = md5_file($url);
      $cachename = md5($url);
      if(!isset($data["files"][$url]) || $data["files"][$url] != $md5 || !file_exists(c::get('root.cache') . '/' . $cachename . '.' . $what)) {
        $data["files"][$url] = $md5;
        f::write(c::get('root.cache') . '/' . $cachename . '.' . $what, self::compress($url, $what));
      }
      if(isset($data["ids"][$uniqid])) {
        $flip = array_flip($data["ids"][$uniqid]);
      } else {
        array();
      }
      if(!isset($flip[$cachename . '.' . $what])) {
        $data["ids"][$uniqid][] = $cachename . '.' . $what;
      }
    }
    f::write(c::get('root.cache') . '/compress.ser', serialize($data));
    $url = '/?assets=' . $uniqid;
    return $url;
  }
  
  // collect compressed assets
  static function collect($what) {
    if(!c::get('cache')) {
      $files = explode(",", $what);
      $path = "";
    } else {
      if(file_exists(c::get('root.cache') . '/compress.ser')) {
        $data = unserialize(file_get_contents(c::get('root.cache') . '/compress.ser')) or die();
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
      if(substr($file, 0, 1) != '/' && substr($file, 0, 4) != 'http' && $path != "") {
        $file = c::get('root') . '/' . $file;
      }
      $return .= file_get_contents($file) or die();
    }
    return $return;
  }
}

?>