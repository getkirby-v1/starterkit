<?php

class re {
  static $activated = false;
  static $realplaceholders;
  static $set = false;
  
  static function apply_global_placeholders($output, $set = false) {
    global $placeholders, $currenttemplate;
    foreach($placeholders as $pname => $poptions) {
      if(isset($poptions["usage"]) && $poptions["usage"] == "global" && (!isset($poptions["templates"]) || isset($poptions["templates"][$currenttemplate["existing"]]) || isset($poptions["templates"][$currenttemplate["virtual"]])) && ($set == false || !isset($poptions["set"]) || isset($poptions["set"][$set]))) {
        $output = self::regex($pname, $poptions["with"], $output);
      }
    }
    return $output;
  }
  
  static function add($name, $with, $usage, $alias, $templates) {
	  global $placeholders;
	  $placeholders[$name] = array("with" => $with, "usage" => $usage, "alias" => $alias, "templates" => $templates);
	  return true;
  }
  
  static function a($name, $with, $usage, $alias, $templates) {
	  return self::add($name, $with, $usage, $alias, $templates);
  }
  
  static function remove($name) {
	  global $placeholders;
	  unset($placeholders[$name]);
	  return true;
  }
  
  static function r($name) {
	  return self::remove($name);
  }
  
  static function activate($seta = false) {
    global $activated, $set;
    if($seta != false) {
	    $set = $seta;
    } else {
	    $set = false;
    }
    if(c::get("replace.autouse") == false && $activated == false) {
	    ob_start();
	    $activated = true;
	    return true;
	  }
	  return false;
  }
  
  static function on($set = false) {
	  return self::activate($set);
  }
  
  static function apply($seta = false) {
    global $activated, $set;
    if($seta == false && $set == false) {
	    $set = false;
	    $setu = false;
    } else if($set != false) {
	    $setu = $set;
    } else if($seta != false) {
      $setu = $seta;
    } else {
	    $setu = false;
    }
    if(c::get("replace.autouse") == false && $activated == true) {
	    echo self::apply_global_placeholders(ob_get_clean(), $setu);
	    $activated = false;
	    return true;
	  }
	  return false;
  }
  
  static function off($set = false) {
	  return self::apply($set);
  }
  
  static function clear() {
	  global $realplaceholders, $placeholders;
	  $realplaceholders = $placeholders;
	  $placeholders = array();
	  return true;
  }
  
  static function restore() {
	  global $realplaceholders, $placeholders;
	  $placeholders = $realplaceholders;
	  return true;
  }
  
  static function regex($this, $that, $what) {
	  if(c::get('replace.regex') == 'string') {
		  return str_replace($this, $that, $what);
	  } else if(c::get('replace.regex') == 'regex') {
		  return preg_replace($this, $that, $what);
	  } else {
		  ini_set('track_errors', 'on');
      $php_errormsg = '';
      @preg_match($this, '');
      ini_set('track_errors', 'off');
      if($php_errormsg) {
        return str_replace($this, $that, $what);
      } else {
	      return preg_replace($this, $that, $what);
      }
	  }
  }
}

?>