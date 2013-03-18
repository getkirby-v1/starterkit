<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class site extends obj {
  
  var $modified = false;
  var $cacheEnabled = false;
  var $dataCacheEnabled = false;
  var $htmlCacheEnabled = false;
    
  function __construct() {
              
    $this->urlSetup();                  
    $this->languageSetup();    
        
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

    $cacheID = $this->dataCacheID();
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
      $this->info();
            
      if($this->dataCacheEnabled) cache::set($cacheID, $this->_);
      
    } else {
      $this->_ = $cacheData;
    }
        
    // attach the uri after caching
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

    // check for index.php in rewritten urls and rewrite them
    if(c::get('rewrite') && preg_match('!index.php\/!i', $this->uri->original)) {
      go($page->url());    
    }
        
    // check for a misconfigured subfolder install
    if($page->isErrorPage()) {
                  
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
      
      // try to rewrite broken translated urls
      // this will only work for default uris
      if(c::get('lang.support')) {
        
        $path  = $this->uri->path->toArray();
        $obj   = $pages;
        $found = false;
    
        foreach($path as $p) {    
          
          // first try to find the page by uid
          $next = $obj->{'_' . $p};
                                        
          if(!$next) {
            
            // go through each translation for each child page 
            // and try to find the url_key or uid there
            foreach($obj as $child) {
              foreach(c::get('lang.available') as $lang) {
                $c = $child->content($lang);   
                // redirect to the url if a translated url has been found
                if($c && $c->url_key() == $p && !$child->isErrorPage()) $next = $child;
              }
            }

            if(!$next) break;
          }
    
          $found = $next;
          $obj   = $next->children();
        }
        
        if($found && !$found->isErrorPage()) go($found->url());
                
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

    $cacheID = $this->htmlCacheID();
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
    if($page->isErrorPage() && c::get('404.header')) header("HTTP/1.0 404 Not Found");
            
    if(empty($cacheData)) {
      // load the main template
      $html = tpl::load($page->template(), array(), true);
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

  function url($lang=false) {
    $url = c::get('url');
    return ($lang && c::get('lang.support') && in_array($lang, c::get('lang.available', array()))) ? url(false, $lang) : $url;
  }

  function serialize() {
    return serialize($this);
  }

  function rootPages() {
  
    // get the first level in the content root
    $ignore = array_merge(array('.svn', '.git', '.htaccess'), (array)c::get('content.file.ignore', array()));
    $files  = dir::inspect(c::get('root.content'), $ignore);
    $pages  = array();
    
    // build the first set of pages     
    foreach($files['children'] as $file) {

      $child = dir::inspect($files['root'] . '/' . $file, $ignore);
      $page  = page::fromDir($child, false);
      
      // add false as parent page object because we are on the first level
      $page->parent = false;
      
      $pages[$page->uid] = $page;

    }

    $this->pages = new pages($pages);
  
  }

  function info($lang=false) {
    
    // first run: fetch all the things we need    
    if(!$this->info) {              

      $root = c::get('root.content');

      if(c::get('lang.support')) {

        $defaultLang = c::get('lang.default');
        $currentLang = c::get('lang.current');
      
        foreach(c::get('lang.available') as $lang) {
          $file = $root . '/site.' . $lang . '.' . c::get('content.file.extension', 'txt');
          if(!file_exists($file)) continue;
  
          // fetch the site info from the defaulf file. 
          $fetched = variables::fetch($file);
          $data    = $fetched['data'];
          $data['filecontent'] = $fetched['raw'];
                  
          $this->_['info'][$lang] = $data;
          
        }
  
        // if there's no default language
        if(!isset($this->_['info'][$defaultLang])) {
          
          $file = $root . '/site.' . c::get('content.file.extension', 'txt');
  
          if(file_exists($file)) {
  
            // fetch the site info from the defaulf file. 
            $fetched = variables::fetch($file);
            $data    = $fetched['data'];
            $data['filecontent'] = $fetched['raw'];
                  
            $this->_['info'][$defaultLang] = $data;
          
          } else {
            $this->_['info'][$defaultLang] = array();
          }
          
        }
        
        foreach($this->_['info'] as $key => $value) {
          if($key == $defaultLang) continue;

          $merged = array_merge($this->_['info'][$defaultLang], $value);
          $this->_['info'][$key] = new siteinfo($merged);
        }
        
        // bake the default language stuff into an object finally
        $this->_['info'][$defaultLang] = new siteinfo($this->_['info'][$defaultLang]);        
        
        // get the current variables
        $current = (isset($this->_['info'][$currentLang])) ? $this->_['info'][$currentLang] : $this->_['info'][$defaultLang];
                                    
      } else {
  
        $file = $root . '/site.' . c::get('content.file.extension', 'txt');
  
        if(file_exists($file)) {
  
          // fetch the site info from the defaulf file. 
          $fetched = variables::fetch($file);
          $data    = $fetched['data'];
          $data['filecontent'] = $fetched['raw'];
                
          $this->_['info'] = new siteinfo($data);
        } else {
          $this->_['info'] = new siteinfo(array());
        }
        
        $current = $this->_['info'];
        
      }
      
      // add all variables        
      $vars = $current->_;
      
      // don't add the filecontent var, 
      // because this is not a custom var
      unset($vars['filecontent']);

      $this->variables = $vars;

      // merge the current site info with the additional
      // info from the info file(s)    
      $this->_ = array_merge($this->_, $current->_);  

    }

    // now get the stuff the user wants
    if(c::get('lang.support')) {
      
      $currentLang = c::get('lang.current');
      $defaultLang = c::get('lang.default');
      
      if($lang && in_array($lang, c::get('lang.available'))) {
        return (isset($this->info[$lang])) ? $this->info[$lang] : $this->info[$defaultLang];
      }
      
      return (isset($this->info[$currentLang])) ? $this->info[$currentLang] : $this->info[$defaultLang];

    } else {
      return $this->info;
    }
        
  }
  
  function modified() {
    return ($this->modified) ? $this->modified : time();
  }

  function dataCacheID() {
    return (c::get('lang.support')) ? 'site.' . c::get('lang.current') . '.php' : 'site.php';  
  }
  
  function htmlCacheID() {
    return (c::get('lang.support')) ? $this->uri->toCacheID() . '.' . c::get('lang.current') . '.php' : $this->uri->toCacheID() . '.php';  
  }

  function languageSetup() {

    // check for activated language support
    if(!c::get('lang.support')) return false;

    // get the available languages
    $available = c::get('lang.available');

    // sanitize the available languages
    if(!is_array($available)) {
      
      // switch off language support      
      c::set('lang.support', false);
      return false;      
            
    }

    // get the raw uri
    $uri = uri::raw();
       
    // get the current language code      
    $code = a::first(explode('/', $uri));

    // try to detect the language code if the code is empty
    if(empty($code)) {
      
      if(c::get('lang.detect')) {      
        // detect the current language
        $detected = str::split(server::get('http_accept_language'), '-');
        $detected = str::trim(a::first($detected));
        $detected = (!in_array($detected, $available)) ? c::get('lang.default') : $detected;

        // set the detected code as current code          
        $code = $detected;

      } else {
        $code = c::get('lang.default');
      }
      
      // go to the default homepage      
      go(url(false, $code));
          
    }
    
    // http://yourdomain.com/error
    // will redirect to http://yourdomain.com/en/error
    if($code == c::get('404')) go(url('error', c::get('lang.default')));
            
    // validate the code and switch back to the homepage if it is invalid
    if(!in_array($code, c::get('lang.available'))) go(url());

    // set the current language
    c::set('lang.current', $code);
    
    // mark if this is a translated version or the default version
    ($code != c::get('lang.default')) ? c::set('lang.translated', true) : c::set('lang.translated', false);
    
    // load the additional language files if available
    load::language();  

  }

  function urlSetup() {

    // auto-detect the url if it is not set
    $url = (c::get('url') === false) ? c::get('scheme') . server::get('http_host') : rtrim(c::get('url'), '/');
      
    // try to detect the subfolder      
    $subfolder = (c::get('subfolder')) ? trim(c::get('subfolder'), '/') : trim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    if($subfolder) {
      c::set('subfolder', $subfolder);
      
      // check if the url already contains the subfolder      
      // so it's not included twice
      if(!preg_match('!' . preg_quote($subfolder) . '$!i', $url)) $url .= '/' . $subfolder;
    }
            
    // set the final url
    c::set('url', $url);  

  }

  function hasPlugin($plugin) {
    return (file_exists(c::get('root.plugins') . '/' . $plugin . '.php') || file_exists(c::get('root.plugins') . '/' . $plugin . '/' . $plugin . '.php')) ? true : false;  
  }
         
}

class siteinfo extends obj {
  
  function __toString() {
    return $this->filecontent;
  }
  
}
