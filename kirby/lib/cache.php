<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class cache {
  
  static function file($file) {
    return c::get('root.cache') . '/' . $file;
  }
  
  static function set($file, $content, $raw=false) {
    if(!c::get('cache')) return false;
    if($raw == false) $content = @serialize($content);
    if($content) f::write(self::file($file), $content);      
  }
  
  static function get($file, $raw=false, $expires=false) {
    if(!c::get('cache')) return false;
    
    // check for an expired cache 
    if($expires && self::expired($file, $expires)) return false;

    $content = f::read(self::file($file));
    if($raw == false) $content = @unserialize($content);
    return $content;
  }  

  static function remove($file) {
    f::remove(self::$file);
  }

  static function flush() {
    $root = c::get('root.cache');
    if(!is_dir($root)) return $root;
    dir::clean($root);  
  }

  static function modified($file) {
    if(!c::get('cache')) return false;
    return @filectime(self::file($file));
  }
  
  static function expired($file, $time=false) {
    return (cache::modified($file) < time()-$time) ? true : false;
  }

}

?>