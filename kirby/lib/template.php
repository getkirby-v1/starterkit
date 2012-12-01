<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class tpl {
  
  static public $vars = array();

  static function set($key, $value=false) {
    if(is_array($key)) {
      self::$vars = array_merge(self::$vars, $key);
    } else {
      self::$vars[$key] = $value;
    }
  }

  static function get($key=null, $default=null) {
    if($key===null) return (array)self::$vars;
    return a::get(self::$vars, $key, $default);       
  }

  static function load($template='default', $vars=array(), $return=false) {    
		if(file_exists(c::get('root.templates') . '/' . $template . '.php')) {
			$file = c::get('root.templates') . '/' . $template . '.php';
		} else {
			$filename = '';
			$files = scandir(c::get('root.templates'));
			foreach($files as $file) {
				$filepath = c::get('root.templates') . '/' . $file;
				if(is_dir($filepath)) {
					$filesInFolder = scandir($filepath);
					foreach($filesInFolder as $fileInFolder) {
						if($fileInFolder == $template . '.php') {
							$file = $filepath . '/' . $template . '.php';
							break 2;
						}
					}
				}
			}
		}
    return self::loadFile($file, $vars, $return);
  }
  
  static function loadFile($file, $vars=array(), $return=false) {
    if(!file_exists($file)) return false;
    @extract(self::$vars);
    @extract($vars);
    content::start();
    require($file);
    return content::end($return); 
  }

}

