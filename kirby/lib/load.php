<?php

class load {
  
  static function config() {
    $root = c::get('root.config');
    self::file($root . '/config.php');
    self::file($root . '/config.' . server::get('server_name') . '.php');
  }
  
  static function plugins() {
    $root  = c::get('root.plugins');
    $files = dir::read($root);    

    if(!is_array($files)) return false;
    
    foreach($files as $file) {
      if(f::extension($file) != 'php') continue;
      self::file($root . '/' . $file);
    }
    
  }

  static function parsers() {
    $root  = c::get('root.parsers');
    $files = dir::read($root);    
  
    if(!is_array($files)) return false;
            
    foreach($files as $file) {
      if(f::extension($file) != 'php') continue;
      self::file($root . '/' . $file);
    }
    
  }
  
  static function file($file) {
    if(!file_exists($file)) return false;
    require_once($file);    
  }

}

?>