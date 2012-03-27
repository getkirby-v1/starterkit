<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class site extends obj {
  
  var $modified = false;
  var $cacheEnabled = false;
  var $dataCacheEnabled = false;
  var $htmlCacheEnabled = false;
    
  function __construct() {

    // auto-detect the url if it is not set
    if(!c::get('url')) c::set('url', c::get('scheme') . server::get('http_host'));

    // check if the cache is enabled at all
    $this->cacheEnabled = (c::get('cache') && (c::get('cache.html') || c::get('cache.data'))) ? true : false;

    if($this->cacheEnabled) {

      $this->dataCacheEnabled = c::get('cache.data');
      $this->htmlCacheEnabled = c::get('cache.html');    

      if(c::get('cache.autoupdate')) {
        $this->modified = dir::modified(c::get('root.content'));
      } else {
        $this->modified = 0;
      }

    }

    $cacheID = 'site.php';
    $cacheModified = time();
    $cacheData = null;
        
    // data cache    
    if($this->dataCacheEnabled) {

      // find the latest modifed date from all content subdirs
      // if the cache is enabled and autoupdate is activated.
      // otherwise the last modified date will be false so the cache
      // will stay valid forever
      
      // check when the data cache has been modified
      $cacheModified = cache::modified($cacheID);

      // check if the cache is still valid
      if($cacheModified >= $this->modified) {
        $cacheData = cache::get($cacheID);
      } 
            
    }
                               
    if(empty($cacheData)) {

      // get the first set of pages
      $this->rootPages();
      // get the additional site info from content/site.txt
      $this->siteInfo();
            
      if($this->dataCacheEnabled) cache::set($cacheID, $this->_);
      
    } else {
      $this->_ = $cacheData;
    }
        
    // attach the uri after caching
    // this will definitely be variable :)
    $this->uri = new uri();
                                            
  }

  function __toString() {
    return '<a href="' . $this->url() . '">' . $this->url() . '</a>';
  }

  function load() {

    // initiate the site and make pages and page
    // globally available
    $pages = $this->pages;
    $page  = $this->pages->active();
    
    // check for ssl
    if(c::get('ssl')) {
      // if there's no https in the url
      if(!server::get('https')) go(str_replace('http://', 'https://', $page->url()));
    }
    
    // check for a misconfigured subfolder install
    if($page->isErrorPage()) {
      
      // get the subfolder in which the site is running
      $subfolder = ltrim(dirname(server::get('script_name')), '/');
      
      // if it is running in a subfolder and it does not match the config
      // send an error with some explanations how to fix that
      if(!empty($subfolder) && c::get('subfolder') != $subfolder) {
        
        // this main url        
        $url = 'http://' . server::get('http_host') . '/' . $subfolder;
        
        require_once(c::get('root.kirby') . '/modals/subfolder.php');
        exit();
        
      }    
      
      // if you want to store subfolders in the homefolder for blog articles i.e. and you
      // want urls like http://yourdomain.com/article-title you can set 
      // RedirectMatch 301 ^/home/(.*)$ /$1 in your htaccess file and those
      // next lines will take care of delivering the right pages. 
      $uri = c::get('home') . '/' . $this->uri->path();
        
      if($redirected = $this->pages()->find($uri)) {
        if($redirected->uri() == $uri) {
          $page = $redirected;
          $this->pages->active = $page;
          $this->uri = new uri($uri);
        }
      }
      
    }
    
    // redirect file urls (file:image.jpg)
    if($this->uri->param('file')) {
      // get the local file
      $file = $page->files()->find($this->uri->param('file'));
      if($file) go($file->url());
    }

    // redirect /home to /
    if($this->uri->path() == c::get('home')) go(url());
    
    // redirect tinyurls
    if($this->uri->path(1) == c::get('tinyurl.folder') && c::get('tinyurl.enabled')) {
      $hash = $this->uri->path(2);
      
      if(!empty($hash)) {
        $resolved = $this->pages->findByHash($hash)->first();
        // redirect to the original page
        if($resolved) go(url($resolved->uri));
      }  
      
    }
    
    // set the global template vars
    tpl::set('site',  $this);
    tpl::set('pages', $pages);
    tpl::set('page',  $page);

    $cacheID = $this->uri->toCacheID() . '.php';
    $cacheModified = time();
    $cacheData = null;
    
    if($this->htmlCacheEnabled) {
                        
      // check if the cache is disabled for some reason
      $this->htmlCacheEnabled = ($page->isErrorPage() || in_array($page->uri(), c::get('cache.ignore', array()))) ? false : true;
      
      // check for the last modified date of the cache file
      $cacheModified = cache::modified($cacheID);

      // check if the files have been modified
      // since the last html cache file has been written   
      if($this->htmlCacheEnabled && $cacheModified >= $this->modified) {
        $cacheData = cache::get($cacheID, true);
      } 

    }
    
    // send a 404 header if this is the error page
    if($page->isErrorPage()) header("HTTP/1.0 404 Not Found");
            
    if(empty($cacheData)) {
      // load the main template
      $html = tpl::load($page->template(), false, true);
      if($this->htmlCacheEnabled) cache::set($cacheID, (string)$html, true);
    } else {
      $html = $cacheData;
    }

    die($html);
    
  }
  
  function breadcrumb() {
    
    if($this->breadcrumb) return $this->breadcrumb;
  
    $uri   = $this->uri->path->toArray();	
    $crumb = array();
  
    foreach($uri AS $u) {
      $tmp  = implode('/', $uri);
      $data = $this->pages->find($tmp);
            
      if(!$data || $data->isErrorPage()) {
        // add the error page to the crumb
        $crumb[] = $this->pages->find(c::get('404'));
        // don't move on with subpages, because there won't be 
        // any if the first page hasn't been found at all
        break;
      } else {      
        $crumb[] = $data;
      }
      array_pop($uri);				
    }
    
    // we've been moving through the uri from tail to head
    // so we need to reverse the array to get a proper crumb    
    $crumb = array_reverse($crumb);		

    // add the homepage to the beginning of the crumb array
    array_unshift($crumb, $this->pages->find(c::get('home')));
    
    // make it a pages object so we can handle it
    // like we handle all pages on the site  
    return $this->breadcrumb = new pages($crumb);
  
  }

  function url() {
    return url();
  }

  function serialize() {
    return serialize($this);
  }

  function rootPages() {
  
    // get the first level in the content root
    $files = dir::inspect(c::get('root.content'));
    $pages = array();
    
    // build the first set of pages     
    foreach($files['children'] as $file) {

      $child = dir::inspect($files['root'] . '/' . $file);
      $page  = page::fromDir($child, false);
      
      // add false as parent page object because we are on the first level
      $page->parent = false;
      
      $pages[$page->uid] = $page;

    }

    $this->pages = new pages($pages);
  
  }

  function siteInfo() {
  
    $file = c::get('root.content') . '/site.txt';
    $info = variables::fetch($file);

    // merge the current site info with the additional
    // info from the info file(s)    
    $this->variables = $info;
    $this->_ = array_merge($this->_, $info);  
    
  }
  
  function modified() {
    return ($this->modified) ? $this->modified : time();
  }
          
}


?>