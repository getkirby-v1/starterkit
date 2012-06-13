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
    global $placeholders;
    $root = c::get('root.config');
    self::file($root . '/config.php');
    self::file($root . '/config.' . server::get('server_name') . '.php');
    
    $rootr = c::get('root.replace');
    ob_start();
    require($rootr . '/replace.php');
    $content = preg_replace('{(.)( )+usage: (.*?)}', '$1' . "\n" . '  usage: $3', ob_get_clean());
    $placeholders_general = yaml($content);
    if(file_exists($rootr . '/replace.' . server::get('server_name') . '.php')) {
      ob_start();
      require($rootr . '/replace.' . server::get('server_name') . '.php');
      $content = preg_replace('{(.)( )+usage: (.*?)}', '$1' . "\n" . '  usage: $3', ob_get_clean());
      $placeholders_site = yaml($content);
    } else {
      $placeholders_site = array();
    }
    $placeholders = array_merge($placeholders_general, $placeholders_site);
    foreach($placeholders as $placeholder => $options) {
	    if(isset($options["templates"]) && is_array($options["templates"])) {
	      $placeholders[$placeholder]["templates"] = array_flip($placeholders[$placeholder]["templates"]);
	    } else if(isset($options["templates"]) && is_string($options["templates"])) {
		    $placeholders[$placeholder] = array();
	    }
	  }
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
    require_once($root . '/replace.php');

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