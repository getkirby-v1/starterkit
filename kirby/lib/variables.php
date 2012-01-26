<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class variable {
  
  function __construct($value, $parent=false) {
    $this->value  = $value;
    $this->parent = $parent;
  }
  
  function __toString() {
    return (string)$this->value;
  }
  
}

class variables extends file {
    
  function __construct($array) {
    
    parent::__construct($array);
    
    $vars = self::fetch($this->root);
    $this->_['variables'] = array();

    if($vars) {
      foreach($vars as $key => $var) {
        $this->_['variables'][$key] = $var;
      }
    }
                
  }
  
  static function fetch($file) {
    if(!file_exists($file)) return array();
    $content  = f::read($file); 
    $content  = str_replace("\xEF\xBB\xBF", '', $content);    
    $sections = preg_split('![\r\n]+[-]{4,}!i', $content);
    $data     = array();
    foreach($sections AS $s) {
      $parts = explode(':', $s);  
      if(count($parts) == 1 && count($sections) == 1) {
        return $content;
      }
      $key = str::urlify($parts[0]);
      if(empty($key)) continue;
      $value = trim(implode(':', array_slice($parts, 1)));
      $data[$key] = $value;
    }
        
    return $data;
  }
    
}


?>