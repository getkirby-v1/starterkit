<?php

// direct access protection
if(!isset($root)) die('Direct access is not allowed');

// used for direct access protection
define('KIRBY', true);

// check for a proper phpversion
if(floatval(phpversion()) < 5.2) {
  die('Please upgrade to PHP 5.2 or higher');
}

// check for existing mbstring functions
if(!function_exists('mb_strtolower')) {
  require(dirname(__FILE__) . '/modals/mbstring.php');
  exit();
}

// include kirby
require_once($rootKirby . '/lib/kirby.php');

// set the root
c::set('root',         $root);
c::set('root.kirby',   $rootKirby);
c::set('root.site',    $rootSite);
c::set('root.content', $rootContent);

require_once($rootKirby . '/lib/load.php');

// load the rest of the system
load::lib();
load::config();
load::parsers();
load::plugins();

// check for an exisiting content dir 
if(!is_dir(c::get('root.content'))) die('The Kirby content directory could not be found');

// check for an exisiting site dir 
if(!is_dir(c::get('root.site'))) die('The Kirby site directory could not be found');

// set the timezone to make sure we 
// avoid errors in php 5.3
@date_default_timezone_set(c::get('timezone'));

// switch on errors
if(c::get('debug')) {
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
} else {
  error_reporting(0);
  ini_set('display_errors', 0);
}

if(c::get('troubleshoot')) {
  require_once(c::get('root.kirby') . '/modals/troubleshoot.php');
  exit();
}

$site = new site();
$site->load();

