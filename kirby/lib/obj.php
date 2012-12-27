<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

/**
 * The Core Object
 *
 * Almost every other object in Kirby is based
 * on this class and their methods.
 * 
 * It implements magic getters, setters, 
 * helper methods and a custom iterator.
 * 
 * @package Kirby CMS
 */
class obj implements Iterator {

  public $_ = array();

  function __construct($array=array()) {
    $this->_ = $array;
  }
    
  function __set($n, $v) {
    $this->_[$n] = $v;
  }
  
  function __get($n) {
    return a::get($this->_, $n);
  }
  
  function __call($n, $args) {
    return a::get($this->_, $n);
  }

  function rewind() {
    reset($this->_);
  }

  function current() {
    return current($this->_);
  }

  function key() {
    return key($this->_);
  }

  function next() {
    return next($this->_);
  }

  function prev() {
    return prev($this->_);
  }

  function nth($n) {
    $array = array_values($this->_);
    return (isset($array[$n])) ? $array[$n] : false;
  }

  function valid() {
    $key = key($this->_);
    $var = ($key !== NULL && $key !== FALSE);
    return $var;
  }

  function find() {
    
    $args    = func_get_args();
    $key     = @$args[0];
    $default = @$args[1];

    if(!$key) return $this->_;
    return a::get($this->_, $key, $default);
  }
      
  function count() {
    return count($this->_);
  }  

  function first() {
    return a::first($this->_); 
  }

  function last() {
    return a::last($this->_); 
  }

  function indexOf($needle) {
    foreach(array_values($this->_) as $key => $value) {
      if($value === $needle) return $key;
    }
    return false;
  }

  function shuffle() {
    $this->_ = a::shuffle($this->_);
    return $this;
  }

  function toArray() {
    return $this->_;
  }
    
}

