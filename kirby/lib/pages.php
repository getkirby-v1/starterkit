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

  function content($code=false) {
    
    // return the cached version if available
    if(!$code && is_a($this->content, 'obj')) return $this->content;

    // make sure there's the right code for lang support
    if(!$code && c::get('lang.support')) $code = c::get('lang.current');
    
    $content = ($code) ? $this->contents()->filterBy('languageCode', $code)->first() : $this->contents()->first();
    $result  = array();

    if($content) {

      foreach($content->variables as $key => $var) {
        $result[$key] = new variable($var, $this);
      }
      
      // pass on the variables object and the raw filecontent
      $result['variables']   = $content->variables;
      $result['filecontent'] = $content->filecontent;

      return new pagecontent($result);

    }
    
    return false;        
    
  }

  function children($offset=null, $limit=null, $sort='dirname', $direction='asc') {

    // if children have already been fetched return them from "cache"
    if(is_object($this->children)) return $this->children->sortBy($sort, $direction);
  
    $pages  = array();
    $ignore = array_merge(array('.svn', '.git', '.hg', '.htaccess'), (array)c::get('content.file.ignore', array()));
        
    foreach($this->children as $child) {

      $child = dir::inspect($this->root . '/' . $child, $ignore);
      $page  = page::fromDir($child, $this);

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

    $name = (!$this->intendedTemplate) ? c::get('tpl.default') : $this->intendedTemplate;
    
    // construct the template file 
    $file = c::get('root.templates') . '/' . $name . '.php';
    
    // check if the template file exists and go back to the fallback    
    if(!file_exists($file)) $name = c::get('tpl.default');

    return $name;
        
  }

  function hasTemplate() {
    return ($this->template() == $this->intendedTemplate()) ? true : false;
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
  
  function url($lang=false) {
    if($this->isHomePage() && !c::get('home.keepurl')) {
      return url(false, $lang);
    } else if(c::get('lang.support') && $lang) {

      $obj = $this;
      $cnt = $this->content($lang);
      $uri = $cnt ? $cnt->url_key() : false;
      if(!$uri) $uri = $this->uid;
                  
      while($parent = $obj->parent()) {
        
        $cnt = $parent->content($lang);
        $uid = ($cnt) ? $cnt->url_key() : false;
        if(!$uid) $uid = $parent->uid;

        $uri = $uid . '/' . $uri;
        $obj = $obj->parent();
      }
                
      $uri = $uri;
      return u($uri, $lang);      
                    
    } else {
      return u(($this->translatedURI != '') ? $this->translatedURI : $this->uri);
    }
  }

  function tinyurl() {
    return u(c::get('tinyurl.folder') . '/' . $this->hash());
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
    
    $p = (c::get('lang.support')) ? str::split($this->translatedURI(), '/'): str::split($this->uri(), '/');
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
    
  static function fromDir($dir, $parent) {
  
    // create a new page for this dir      
    $page   = new page();
    $parent = ($parent) ? $parent : new obj(); 
    
    $name = self::parseName($dir['name']);

    // apply all variables
    $page->parent   = $parent;
    $page->num      = $name['num'];
    $page->uid      = $name['uid'];
    $page->uri      = ltrim($parent->uri . '/' . $page->uid, '/');
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

    // fetch the content
    $content = $page->files()->contents();    
    
    if(c::get('lang.support')) {

      $fallback = $content->filterBy('languageCode', c::get('lang.default'))->first();
      if(!$fallback) $fallback = $content->first();
      
      // get the fallback variables
      $variables = ($fallback) ? $fallback->variables : array();
            
      $page->intendedTemplate = ($fallback) ? $fallback->template : false;
      
      if(c::get('lang.translated')) {

        // don't use url_key as fallback
        // the fallback should always be the folder name
        unset($variables['url_key']);

        $translation = $content->filterBy('languageCode', c::get('lang.current'))->first();
        $variables   = ($translation) ? array_merge($variables, $translation->variables) : $variables;
      }

    } else {
      
      $contentfile = $content->first();
      $variables   = ($contentfile) ? $contentfile->variables : array();

      $page->intendedTemplate = ($contentfile) ? $contentfile->template : false;
                
    }
        
    // merge all variables
    foreach($variables as $key => $var) {
      $page->_[$key] = new variable($var, $page);
    }
     
    // multi-language translatable urls
    if(c::get('lang.support') && $page->url_key != '') {
      $page->translatedUID = $page->url_key();
      $page->translatedURI = ltrim($parent->translatedURI . '/' . $page->url_key(), '/');    
    } else {
      $page->translatedUID = $page->uid;
      $page->translatedURI = ltrim($parent->translatedURI . '/' . $page->uid, '/');    
    }
    
    // attach a cached version of the default content
    // for backwards compatibility
    $page->content = $page->content();
                    
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

    if(c::get('root') == '/') {
      $base = ltrim($root, '/');
    } else {
      $base = ltrim(str_replace(c::get('root'), '', $root), '/');
    }

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

    // check if we need to search for translated urls
    $translated = c::get('lang.support');

    foreach($array as $p) {    

      $next = ($translated) ? $obj->findBy('translatedUID', $p) : $obj->{'_' . $p};
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

    if($page) {
      $pageURI = (c::get('lang.support')) ? $page->translatedURI() : $page->uri();
    } else {
      $pageURI = c::get('404');
    }
    
    if(!$page || $pageURI != $uri) {
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
  
  function filterBy() {

    $args     = func_get_args();
    $field    = a::get($args, 0);
    $operator = '=='; 
    $value    = a::get($args, 1);
    $split    = a::get($args, 2);
    
    if($value === '!=' || $value === '==' || $value === '*=') {
      $operator = $value;
      $value    = a::get($args, 2);
      $split    = a::get($args, 3);
    }          
    
    $pages = array();

    switch($operator) {

      // ignore matching elements
      case '!=':

        foreach($this->_ as $key => $page) {
          if($split) {
            $values = str::split((string)$page->$field(), $split);
            if(!in_array($value, $values)) $pages[$key] = $page;
          } else if($page->$field() != $value) {
            $pages[$key] = $page;
          }
        }
        break;    
      
      // search
      case '*=':
        
        foreach($this->_ as $key => $page) {
          if($split) {
            $values = str::split((string)$page->$field(), $split);
            foreach($values as $val) {
              if(str::contains($val, $value)) {
                $pages[$key] = $page;
                break;
              }
            }
          } else if(str::contains($page->$field(), $value)) {
            $pages[$key] = $page;
          }
        }
                            
      // take all matching elements          
      default:

        foreach($this->_ as $key => $page) {
          if($split) {
            $values = str::split((string)$page->$field(), $split);
            if(in_array($value, $values)) $pages[$key] = $page;
          } else if($page->$field() == $value) {
            $pages[$key] = $page;
          }
        }

        break;

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

    if($field == 'dirname') {
      $method = 'natural';
    } 
        
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

class pagecontent extends obj {
  
  function __toString() {
    return $this->filecontent;
  }
  
}
