<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class load {

  static function lib() {
    
    $root = c::get('root.kirby');
      
    require_once($root . '/defaults.php');
    require_once($root . '/lib/cache.php');
    require_once($root . '/lib/obj.php');
    require_once($root . '/lib/pagination.php');
    require_once($root . '/lib/files.php');
    require_once($root . '/lib/variables.php');
    require_once($root . '/lib/pages.php');
    require_once($root . '/lib/site.php');
    require_once($root . '/lib/uri.php');
    require_once($root . '/lib/helpers.php');
    require_once($root . '/lib/template.php');
    
  }
  
  static function config() {
    $root = c::get('root.config');
    $files = dir::read($root . '/');
    
    if(!is_array($files)) return false;
    
    self::file($root . '/config.php');
    self::file($root . '/config.' . server::get('server_name') . '.php');
    
    $names = array();
    foreach($files as $file) {
      preg_match('{(.*?)\.(.*)?\.?(.*)}', $file, $parts);
      $name = $parts[1];
      if($name == 'config') continue;
      
      $names[] = $name;
    }
    
    foreach($names as $name) {
      self::file($root . '/' . $name . '.php');
      self::file($root . '/' . $name . '.' . server::get('server_name') . '.php');
    }
  }
  
  static function plugins($folder='') {
    $root  = c::get('root.plugins');
    if($folder != '') {
      $files = dir::read($root . '/' . $folder);
    } else {
      $files = dir::read($root); 
    } 

    if(!is_array($files)) return false;
    
    foreach($files as $file) {
      if($file == 'config.php') continue;
      if(is_dir($root . '/' . $file)) {
        self::plugins($file . '/');
      }
      if(f::extension($file) != 'php' || $file == $folder . '.php') continue;
      self::file($root . '/' . $folder . $file);
    }
    self::file($root . '/' . $folder . $folder . '.php');
    if($folder != '' && !file_exists(c::get('root.config') . '/' . substr($folder, 0, -1) . '.php') && file_exists($root . '/' . $folder . 'config.php')) {
      @copy($root . '/' . $folder . 'config.php', c::get('root.config') . '/' . substr($folder, 0, -1) . '.php');
    }  
  }

  static function parsers() {
    $root  = c::get('root.parsers');

    require_once($root . '/defaults.php');
    require_once($root . '/yaml.php');
    require_once($root . '/kirbytext.php');

    if(c::get('markdown.extra')) {
      require_once($root . '/markdown.extra.php');
    } else {
      require_once($root . '/markdown.php');    
    }
    
  }
  
  static function file($file) {
    if(!file_exists($file)) return false;
    require_once($file);    
  }

}

?>