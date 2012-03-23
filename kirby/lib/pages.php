<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class page extends obj {

  function __construct() {
    $this->children   = array();
    $this->online     = true; 
    $this->visible    = true; 
  }
  
  function __toString() {
    return '<a href="' . $this->url() . '">' . $this->url() . '</a>';  
  }

  function children($offset=null, $limit=null, $sort='dirname', $direction='asc') {

    // if children have already been fetched return them from "cache"
    if(is_object($this->children)) return $this->children->sortBy($sort, $direction);
  
    $pages = array();
    
    foreach($this->children as $child) {

      $child = dir::inspect($this->root . '/' . $child);
      $page  = page::fromDir($child, $this->uri);

      // add the parent page object
      $page->parent = $this;

      $pages[$page->uid] = $page;
      
    }
        
    $this->children = $children = new pages($pages);
    
    if($offset || $limit) $children = $children->slice($offset, $limit);    
    $children = $children->sortBy($sort, $direction);
        
    return $children;    

  }

  function hasChildren() {
    return ($this->countChildren() > 0) ? true : false;
  }

  function countChildren() {
    return $this->children()->count();
  }
  
  function siblings() {
    global $site;
    $parent = $this->parent();
    return (!$parent) ? $site->pages : $parent->children();    
  }

  function _next($siblings, $sort=false, $direction='asc') {
    if($sort) $siblings = $siblings->sortBy($sort, $direction);
    $index = $siblings->indexOf($this);
    if($index === false) return false;
    $siblings  = array_values($siblings->toArray());
    $nextIndex = $index+1;
    return $this->next = a::get($siblings, $nextIndex);                  
  }

  function _prev($siblings, $sort=false, $direction='asc') {
    if($sort) $siblings = $siblings->sortBy($sort, $direction);
    $index = $siblings->indexOf($this);
    if($index === false) return false;
    $siblings  = array_values($siblings->toArray());
    $prevIndex = $index-1;
    return $this->prev = a::get($siblings, $prevIndex);                
  }

  function next($sort=false, $direction='asc') {
    return $this->_next($this->siblings(), $sort, $direction);
  }
  
  function nextVisible($sort=false, $direction='asc') {
    return $this->_next($this->siblings()->visible(), $sort, $direction);    
  }
  
  function hasNext($sort=false, $direction='asc') {
    return ($this->next($sort, $direction)) ? true : false;   
  }

  function hasNextVisible($sort=false, $direction='asc') {
    return ($this->nextVisible($sort, $direction)) ? true : false;   
  }
  
  function prev($sort=false, $direction='asc') {
    return $this->_prev($this->siblings(), $sort, $direction);
  }

  function prevVisible($sort=false, $direction='asc') {
    return $this->_prev($this->siblings()->visible(), $sort, $direction);
  }
  
  function hasPrev($sort=false, $direction='asc') {
    return ($this->prev($sort, $direction)) ? true : false; 
  }

  function hasPrevVisible($sort=false, $direction='asc') {
    return ($this->prevVisible($sort, $direction)) ? true : false; 
  }

  function template() {

    $name = (!$this->content || !$this->content->name) ? c::get('tpl.default') : $this->content->name;
    
    // construct the template file 
    $file = c::get('root.templates') . '/' . $name . '.php';
    
    // check if the template file exists and go back to the fallback    
    if(!file_exists($file)) $name = c::get('tpl.default');

    return $name;
        
  }

  function depth() {
    $parent = $this->parent();
    return ($parent) ? ($parent->depth() + 1) : 1;
  }

  function hash() {
    if($this->hash) return $this->hash;

    // add a unique hash
    $checksum = sprintf('%u', crc32($this->uri));
    return $this->hash = base_convert($checksum, 10, 36);
  }
  
  function url() {
    if($this->isHomePage() && !c::get('home.keepurl')) {
      return u();
    } else {
      return u($this->uri);
    }
  }

  function tinyurl() {
    return u('x/' . $this->hash());
  }

  function date($format=false) {
    if(!$this->date) return false;
    $date = strtotime($this->_['date']);
    return ($format) ? date($format, $date) : $date;
  }
  
  function modified($format=false) {
    return ($format) ? date($format, $this->modified) : $this->modified;  
  }

  function isHomePage() {
    return ($this->uri === c::get('home')) ? true : false;    
  }

  function isErrorPage() {
    return ($this->uri === c::get('404')) ? true : false;
  }

  function isActive() {
    global $site;
    return ($site->pages->active() === $this);
  }

  function isOpen() {

    if($this->isOpen) return $this->isOpen;

    global $site;

    if($this->isActive()) return true;
    
    $p = str::split($this->uri(), '/');
    $u = $site->uri->path->toArray();
  
    for($x=0; $x<count($p); $x++) {
      if(a::get($p, $x) != a::get($u, $x)) return $this->isOpen = false;
    }
    
    return $this->isOpen = true;
    
  }

  function isVisible() {
    return $this->visible;
  }
  
  function isOnline() {
    return $this->online;  
  }

  function isChildOf($obj) {
    if($this === $obj); 
    return ($this->parent() === $obj);
  }

  function isAncestorOf($obj) {
    return $obj->isDescendantOf($this);
  }

  function isDescendantOf($obj) {
    
    if($this === $obj) return false;

    $parent = $this;

    while($parent = $parent->parent()) {
      if($parent === $obj) return true;
    } 
    
    return false;

  }

  function isDescendantOfActive() {

    global $site;
    
    $active = $site->pages()->active();

    return $this->isDescendantOf($active);
      
  }

  function files() {
    if($this->files) return $this->files;
    $this->files = new files();
    $this->files->init($this);
    return $this->files;
  }
  
  function hasFiles() {
    return ($this->files()->count() > 0) ? true : false;
  }
  
  function images() {
    return $this->files()->images();  
  }

  function hasImages() {
    return ($this->images()->count() > 0) ? true : false;
  }
  
  function videos() {
    return $this->files()->videos();    
  }

  function hasVideos() {
    return ($this->videos()->count() > 0) ? true : false;
  }
  
  function documents() {
    return $this->files()->documents();      
  }

  function hasDocuments() {
    return ($this->documents()->count() > 0) ? true : false;
  }

  function sounds() {
    return $this->files()->sounds();        
  }

  function hasSounds() {
    return ($this->sounds()->count() > 0) ? true : false;
  }
  
  function contents() {
    return $this->files()->contents();          
  }

  function hasContents() {
    return ($this->contents()->count() > 0) ? true : false;
  }
    
  static function fromDir($dir, $path) {
  
    // create a new page for this dir      
    $page = new page();
    
    $name = self::parseName($dir['name']);

    // apply all variables
    $page->num      = $name['num'];
    $page->uid      = $name['uid'];
    $page->uri      = ltrim($path . '/' . $page->uid, '/');
    $page->dirname  = $dir['name'];
    $page->modified = $dir['modified'];
    $page->root     = $dir['root'];
    $page->diruri   = self::parseDirURI($dir['root']);
    $page->rawfiles = $dir['files'];
    $page->children = $dir['children'];
    $page->visible  = empty($name['num']) ? false : true;

    // create a default title. we always need a title!
    $page->title = new variable($name['uid'], $page);

    // gather all files
    $page->files();

    // fetch the first content
    $page->content = $page->files()->contents()->first();    
    
    // merge all variables
    if($page->content && is_array($page->content->variables)) {
      foreach($page->content->variables as $key => $var) {
        $page->_[$key] = new variable($var, $page);
      }
    }
                
    return $page;
      
  } 

	static function parseName($name) {

    if(str::contains($name, '-')) {
      $match = str::match($name, '!^([0-9]+[\-]+)!', 0);	
      $uid   = str_replace($match, '', $name);
      $num   = trim(rtrim($match, '-'));
    } else {
      $num   = false;
      $uid   = $name;
    }
    
    return array('uid' => $uid, 'num' => $num);

	}

  static function parseDirURI($root) {
    $base = ltrim(str_replace(c::get('root'), '', $root), '/');
    return $base;    
  }
    
}

class pages extends obj {
  
  var $index = array();
  var $pagination = null;
  var $active = false;
  
  function __construct($array) {
    $_ = array();
    foreach($array as $key => $value) {
      $_['_' . $this->_key($key)] = $value;
    }
    $this->_ = $_;    
  }
  
  function __toString() {
    $output = array();
    foreach($this->_ as $key => $page) {
      $output[] = $page . '<br />';          
    }    
    return implode("\n", $output);
  }
  
  function _key($key) {
    return ltrim($key, '_');
  }
  
  function index($obj=null, $path=false) {
    
    if(!$obj) $obj = $this;

    foreach($obj->_ as $key => $page) {
      $newPath = ltrim($path . '/' . $page->uid() , '/');
      $this->index[$newPath] = $page;
      $this->index($page->children(), $newPath);
    }
    
    return $this->index;
            
  }
    
  function find() {
    
    $args = func_get_args();
    
    // find multiple pages
    if(count($args) > 1) {
      $result = array();
      foreach($args as $arg) {
        $page = $this->find($arg);
        if($page) $result[$page->uid] = $page;
      }      
      return (empty($result)) ? false : new pages($result);
    }    
    
    // find a single page
    $path  = a::first($args);      
    $array = str::split($path, '/');
    $obj   = $this;
    $page  = false;

    foreach($array as $p) {    
      $next = $obj->{'_' . $p};
      if(!$next) return $page;

      $page = $next;
      $obj  = $next->children();
    }
    return $page;    
  }
    
  function active() {
        
    global $site;

    if($this->active) return $this->active;

    $uri = (string)$site->uri->path();

    if(empty($uri)) $uri = c::get('home');

    $page = $this->find($uri);

    if(!$page || $page->uri() != $uri) {
      $page = $site->pages->find(c::get('404'));
    }
           
    return $this->active = $page;
                    
  }
  
  function findOpen() {
    foreach($this->_ as $key => $page) {
      if($page->isOpen()) return $page;
    }    
  }

  function findBy($key, $value) {
    if(is_array($value)) {
      $result = array();
      foreach($value as $arg) {
        $page = $this->findBy($key, $arg);
        if($page) $result[$page->uid] = $page;
      }      
      return (empty($result)) ? false : new pages($result);
    }
    if(empty($this->index)) $this->index();
    foreach($this->index as $page) {
      if($value == $page->$key()) return $page;
    }
    return false;        
  }

  function findByUID() {
    $args = func_get_args();
    return $this->findBy('uid', $args);
  }

  function findByDirname() {
    $args = func_get_args();
    return $this->findBy('dirname', $args);
  }
  
  function findByTitle() {
    $args = func_get_args();
    return $this->findBy('title', $args);  
  }

  function findByHash() {
    $args = func_get_args();
    return $this->findBy('hash', $args);  
  }
  
  function filterBy($field, $value, $split=false) {
    $pages = array();
    foreach($this->_ as $key => $page) {
      if($split) {
        $values = str::split((string)$page->$field(), $split);
        if(in_array($value, $values)) $pages[$key] = $page;
      } else if($page->$field() == $value) {
        $pages[$key] = $page;
      }
    }
    return new pages($pages);    
  }
    
  function visible() {
    return $this->filterBy('visible', true);
  }
  
  function countVisible() {
    return $this->visible()->count();
  }

  function invisible() {
    return $this->filterBy('visible', false);
  }
    
  function countInvisible() {
    return $this->invisible()->count();  
  }
    
  function without($uid) {
    $pages = $this->_;
    unset($pages['_' . $uid]);
    return new pages($pages);        
  }

  function not($uid) {
    return $this->without($uid);
  }

  function flip() {
    $pages = array_reverse($this->_, true);
    return new pages($pages);
  }

  function slice($offset=null, $limit=null) {
    if($offset === null && $limit === null) return $this;
    return new pages(array_slice($this->_, $offset, $limit));
  }
  
  function limit($limit) {
    return $this->slice(0, $limit);
  }

  function offset($offset) {
    return $this->slice($offset);
  }
  
  function sortBy($field, $direction='asc', $method=SORT_REGULAR) {
    $pages = a::sort($this->_, $field, $direction, $method);
    return new pages($pages);
  }

  function paginate($limit, $options=array()) {

    $pagination = new pagination($this, $limit, $options);
    $pages = $this->slice($pagination->offset, $pagination->limit);
    $pages->pagination = $pagination;

    return $pages;

  }
  
  function pagination() {
    return $this->pagination;
  }

  function children() {
    $result = array();
    foreach($this->_ as $page) {
      foreach($page->children() as $key => $child) {
        $result[$key] = $child;
      }
    }
    return new pages($result);
  }

  static function merge() {
    
    $objs   = func_get_args();
    $result = array();
    
    foreach($objs as $obj) {    
      foreach($obj as $key => $page) {
        $result[$key] = $page;
      }
    }

    return new pages($result);    
      
  }
            
}

?>