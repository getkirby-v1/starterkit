<?php

class cache {
  
  static function file($file) {
    return c::get('root.cache') . '/' . $file;
  }
  
  static function set($file, $content, $raw=false) {
    if(!c::get('cache')) return false;
    if($raw == false) $content = @serialize($content);
    if($content) f::write(self::file($file), $content);      
  }
  
  static function get($file, $raw=false) {
    if(!c::get('cache')) return false;
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

}

?>