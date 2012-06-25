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
    $this->filecontent = @$vars['raw'];

    if($vars && is_array($vars)) {
      foreach($vars['data'] as $key => $var) {
        $this->_['variables'][$key] = $var;
      }
    }
                
  }
  
  static function fetch($file) {
    if(!file_exists($file)) return array(
      'raw'  => false,
      'data' => array()
    );
    $content  = f::read($file); 
    $content  = str_replace("\xEF\xBB\xBF", '', $content);    
    $sections = preg_split('![\r\n]+[-]{4,}!i', $content);
    $data     = array();
    foreach($sections AS $s) {
      $parts = explode(':', $s);  
      if(count($parts) == 1 && count($sections) == 1) {
        return $content;
      }
      $key = str::lower(preg_replace('![^a-z0-9]+!i', '_', trim($parts[0])));
      if(empty($key)) continue;
      $value = trim(implode(':', array_slice($parts, 1)));
      $data[$key] = $value;
    }
        
    return array(
      'raw'  => $content,
      'data' => $data,
    );
  }
    
}

