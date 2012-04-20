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