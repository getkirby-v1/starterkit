<?php 

/*

---------------------------------------
Document root of your site
---------------------------------------
this should be identical with the directory 
in which your index.php is located

*/

$root = dirname(__FILE__);



/*

---------------------------------------
Kirby system folder
---------------------------------------

by default this is located inside the root directory
but if you want to share one system folder for 
multiple sites, you can easily change that here
and link to a shared kirby folder somewhere on your
server

*/

$rootKirby = $root . '/kirby';



/*

---------------------------------------
Your site folder
---------------------------------------

Your site folder contains all the site specific files
like templates and snippets. It is located in the root
directory by default, but you can move it if you want.

*/

$rootSite = $root . '/site';



/*

---------------------------------------
Your content folder
---------------------------------------

Your content folder is also located in the root 
directory by default. You can change this here.
It can also be changed later in your site/config.php

*/

$rootContent = $root . '/content';


// Try to load Kirby
if(!file_exists($rootKirby . '/system.php')) {
  die('The Kirby system could not be loaded');  
} 

require_once($rootKirby . '/system.php');