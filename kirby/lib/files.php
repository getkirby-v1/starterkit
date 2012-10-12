<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class file extends obj {
  
  var $meta = array();
  
  function __toString() {
    return '<a href="' . $this->url() . '">' . $this->url() . '</a>';  
  }

  function meta($code=false) {
    if(!$code) $code = c::get('lang.default');
    return new obj(a::get($this->meta, $code));
  }

  function next() {

    if($this->next) return $this->next;

    $parent = $this->parent();
    if(!$parent) return false;    
    $siblings = $parent->findByType($this->type);
    $index = $siblings->indexOf($this);
    if($index === false) return false;
    
    $siblings  = array_values($siblings->toArray());
    $nextIndex = $index+1;
    return a::get($siblings, $nextIndex);                
  }

  function hasNext() {
    return ($this->next()) ? true : false;     
  }
  
  function prev() {

    if($this->prev) return $this->prev;

    $parent = $this->parent();
    if(!$parent) return false;    
    $siblings = $parent->findByType($this->type);
    $index = $siblings->indexOf($this);
    if($index === false) return false;
    
    $siblings  = array_values($siblings->toArray());
    $prevIndex = $index-1;
    return a::get($siblings, $prevIndex);                
  }

  function hasPrev() {
    return ($this->prev()) ? true : false;       
  }

  function url() {
    return c::get('url') . '/' . $this->uri;  
  }
    
  function info() {

    if($this->info) return $this->info;

    $info = array(
      'size' => f::size($this->root),
      'mime' => (function_exists('mime_content_type')) ? @mime_content_type($this->root) : false
    );
    
    // set the nice size
    $info['niceSize'] = f::nice_size($info['size']);
    
    return $this->info = new obj($info);

  }

  function size() {
    $info = $this->info();
    return $info->size();
  }

  function niceSize() {
    $info = $this->info();
    return $info->niceSize();
  }
  
  function mime() {
    $info = $this->info();
    return $info->mime();
  }
      
}

class image extends file {

  function __construct($array=array()) {
    parent::__construct($array);
    $this->thumb = $this;
    $this->title = $this->name;
  }

  function width() {
    $info = $this->info();
    return $info->width();
  }

  function height() {
    $info = $this->info();
    return $info->height();
  }

  function fit($box, $force=false) {
    $size = size::fit($this->width(), $this->height(), $box, $force);    
    $this->info->width  = $size['width'];
    $this->info->height = $size['height'];
    return $this;
  }

  function fitWidth($width, $force=false) {
    $size = size::fit_width($this->width(), $this->height(), $width, $force);    
    $this->info->width  = $size['width'];
    $this->info->height = $size['height'];
    return $this;      
  }

  function fitHeight($height, $force=false) {
    $size = size::fit_height($this->width(), $this->height(), $height, $force);    
    $this->info->width  = $size['width'];
    $this->info->height = $size['height'];
    return $this;      
  }

  function info() {
    
    if($this->info) return $this->info;
    
    $info = parent::info();
    $size = @getimagesize($this->root);

    if(!$size) {
      $info->width  = false;
      $info->height = false;
    } else {
      $info->width  = $size[0];
      $info->height = $size[1];
      $info->mime   = $size['mime'];
    }

    return $this->info = $info;

  }
  
}

class video extends file {

  function __construct($array=array()) {
    parent::__construct($array);
  }

  function mime() {

    switch($this->extension) {
      case 'ogg':
      case 'ogv':
        return 'video/ogg';
      case 'webm':
        return 'video/webm';
      case 'mp4':
        return 'video/mp4';
    }

    $info = $this->info();
    return $info->mime();

  }

}


class files extends obj {

  var $pagination = null;

  function __toString() {
    $output = array();
    foreach($this->_ as $key => $file) {
      $output[] = $file . '<br />';          
    }    
    return implode("\n", $output);
  }
  
  function init($page) {
        
		foreach($page->rawfiles AS $key => $file) {
      
      // skip invisible files      
      if(preg_match('!^\.!', $file)) continue;
      
			$info = array(
			  'name'      => f::name($file),
				'filename'  => $file,
				'extension' => f::extension($file),
				'root'      => $page->root . '/' . $file,
				'uri'       => $page->diruri . '/' . $file,
				'parent'    => $this,
				'modified'  => @filectime($page->root . '/' . $file)
			);
				
      switch($info['extension']) {
        case 'jpg':
        case 'jpeg':
        case 'gif':
        case 'png':
          $info['type'] = 'image';
          $class = 'image';
          break;
        case 'pdf':
        case 'doc':
        case 'xls':
        case 'ppt':
          $info['type'] = 'document';
          $class = 'file';
          break;
        case 'mov':
        case 'avi':
        case 'ogg':
        case 'ogv':
        case 'webm':
        case 'flv':
        case 'swf':
        case 'mp4':
          $info['type'] = 'video';
          $class = 'video';
          break;
        case 'mp3':
          $info['type'] = 'sound';
          $class = 'file';
          break;
        case c::get('content.file.extension', 'txt'):
          $info['type'] = 'content';
          $class = 'variables';
          break;
        default:
          $info['type'] = 'other';
          $class = 'file';
      }			
    
      $this->$file = new $class($info);
    
    }

    $this->dispatchImages();
    $this->dispatchContent();
              
  }

  function dispatchImages() {
    
    foreach($this->images() as $key => $image) {
      
      // check for images with thumbnail naming      
      if(preg_match('!\.thumb!', $image->name)) {

        // get the rawFilename of the original file to which 
        // this thumb belongs to
        $rawFilename = str_replace('.thumb', '', $image->filename);
                
        // find the original size
        $original = $this->find($rawFilename);        
        
        // if there's no original skip this
        if(!$original) continue;
        
        // attach the thumbnail to the original
        $original->thumb = $image;
        
        // remove it from the list of files
        unset($this->_[$key]);
                                
      }
                      
    }
  
  }

  function dispatchContent() {

    $meta = array();

    $langSupport = c::get('lang.support');
    $translated  = c::get('lang.translated');
            
    foreach($this->contents() as $key => $content) {
            
      // split filenames (already without extension) by .
      $parts      = explode('.', $content->name);
      $countParts = count($parts);
      $lastPart   = a::last($parts);
      $firstPart  = a::first($parts);
      
      // home.txt
      if($countParts == 1) {

        // files without a language code
        // are considered to be the default language file            
        $content->languageCode = c::get('lang.default');

        // keep the entire name for the template (i.e. home)
        $content->template = $content->name;

      // home.en.txt 
      // myfile.jpg.txt 
      // article.video.txt
      } else if($countParts == 2) {

        // check for a matching file by the entire name        
        $file = $this->find($content->name);
        
        // myfile.jpg.txt
        if($file) {

          // change the filetype
          $content->type = 'meta';

          // files without a language code
          // are considered to be the default language file            
          $content->languageCode = c::get('lang.default');
          $file->meta[$content->languageCode] = $content->variables;
          
          // add this to the meta array
          $meta[] = $file;
                                                                    
        // home.en.txt
        // article.video.txt
        } else {
          
          // check for a valid language extension
          // home.en.txt
          if($langSupport && in_array($lastPart, c::get('lang.available', array()))) {         
            
            // use the first part for the template name (i.e. home)
            $content->template = $firstPart;

            // add the language code
            $content->languageCode = $lastPart;

          // plain content file with crazy name
          // article.video.txt
          } else {

            // files without a language code
            // are considered to be the default language file            
            $content->languageCode = c::get('lang.default');

            // use the entire name for the template (i.e. home)
            $content->template = $content->name;

          }

        }


      // myfile.jpg.de.txt
      // article.video.de.txt
      // something more absurd
      } else if($countParts > 2) {
                
        // check for a valid language extension
        // myfile.jpg.de.txt
        // article.video.de.txt
        if($langSupport && in_array($lastPart, c::get('lang.available', array()))) {         
          
          // name without the last part / language code
          $name = implode('.', array_slice($parts, 0, -1));
          
          // check for a matching file by the new name        
          $file = $this->find($name);
          
          // add the language code
          $content->languageCode = $lastPart;
          
          // myfile.jpg.de.txt
          if($file) {
            
            // change the filetype
            $content->type = 'meta';
            $file->meta[$content->languageCode] = $content->variables;

            // add this to the meta array
            $meta[] = $file;
                                    
          // article.video.de.txt
          } else {

            // use the already prepared name for the template (i.e. article.video)
            $content->template = $name;
                    
          }

        // something more absurd
        // article.video.whatever.txt
        // myfile.something.jpg.txt
        // or an invalid language code
        } else {

          // check for a matching file by the new name        
          $file = $this->find($content->name);

          // files without a language code
          // are considered to be the default language file
          $content->languageCode = c::get('lang.default');            
          
          if($file) {
            
            $content->type = 'meta';
            $file->meta[$content->languageCode] = $content->variables;
            
            // add this to the meta array
            $meta[] = $file;

          } else {
                      
            // use the entire name for the template (i.e. article.video.whatever)
            $content->template = $content->name;
          
          }
          
        }
            
      }
    
    }
    
    foreach($meta as $m) {
      
      if($langSupport) {      
        
        $variables = (array)a::get($m->meta, c::get('lang.default'));
                    
        if($translated) {
          $translation = (array)a::get($m->meta, c::get('lang.current'));
          $variables   = (!empty($translation)) ? array_merge($variables, $translation) : $variables;
        }
      
      } else {
        $variables = (array)@a::first($m->meta);
      }
      
      // merge the variables with the file object      
      $m->_ = array_merge($m->_, $variables);
          
    }
                                
  }
  
  function content() {
    return $this->content;
  }
  
  function slice($offset=null, $limit=null) {
    if($offset === null && $limit === null) return $this;
    return new files(array_slice($this->_, $offset, $limit));
  }

  function limit($limit) {
    return $this->slice(0, $limit);
  }

  function offset($offset) {
    return $this->slice($offset);
  }

  function without($name) {
    $files = $this->_;
    unset($files[$name]);
    return new files($files);        
  }

  function not($name) {
    return $this->without($name);
  }

  function find() {
    
    $args = func_get_args();
    
    // find multiple files
    if(count($args) > 1) {
      $result = array();
      foreach($args as $arg) {
        $file = $this->find($arg);
        if($file) $result[$file->filename] = $file;
      }      
      return (empty($result)) ? false : new files($result);
    }    
    
    // find a single file
    $key = a::first($args);      
    if(!$key) return $this->_;
    return a::get($this->_, $key);
  }

  function findByExtension() {

    $args  = func_get_args();
    $count = count($args); 
    if($count == 0) return false;
    
    $files = array();
    foreach($this->_ as $key => $file) {
      if($count > 1) {
        if(in_array($file->extension, $args)) $files[$key] = $file;
      } else {
        if($file->extension == $args[0]) $files[$key] = $file;      
      }
    }   
    return new files($files);      
  }

  function findByType($type) {

    $args  = func_get_args();
    $count = count($args); 
    if($count == 0) return false;

    $files = array();
    foreach($this->_ as $key => $file) {
      if($count > 1) {
        if(in_array($file->type, $args)) $files[$key] = $file;
      } else {
        if($file->type == $args[0]) $files[$key] = $file;      
      }
    }   
    return new files($files);        
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
    
    $files = array();

    switch($operator) {

      // ignore matching elements
      case '!=':

        foreach($this->_ as $key => $file) {
          if($split) {
            $values = str::split((string)$file->$field(), $split);
            if(!in_array($value, $values)) $files[$key] = $file;
          } else if($file->$field() != $value) {
            $files[$key] = $file;
          }
        }
        break;    
      
      // search
      case '*=':
        
        foreach($this->_ as $key => $file) {
          if($split) {
            $values = str::split((string)$file->$field(), $split);
            foreach($values as $val) {
              if(str::contains($val, $value)) {
                $files[$key] = $file;
                break;
              }
            }
          } else if(str::contains($file->$field(), $value)) {
            $files[$key] = $file;
          }
        }
                            
      // take all matching elements          
      default:

        foreach($this->_ as $key => $file) {
          if($split) {
            $values = str::split((string)$file->$field(), $split);
            if(in_array($value, $values)) $files[$key] = $file;
          } else if($file->$field() == $value) {
            $files[$key] = $file;
          }
        }

        break;

    }

    return new files($files);    

  }

  function images() {
    return $this->findByType('image');
  }

  function videos() {
    return $this->findByType('video');
  }

  function documents() {
    return $this->findByType('document');
  }

  function sounds() {
    return $this->findByType('sound');
  }

  function contents() {
    return $this->findByType('content');
  }

  function meta() {
    return $this->findByType('meta');
  }

  function others() {
    return $this->findByType('other');
  }
  
  function totalSize() {
    $size = 0;
    foreach($this->_ as $file) {
      $size = $size + $file->size();
    }
    return $size;    
  }

  function niceTotalSize() {
    return f::nice_size($this->totalSize());
  }

  function flip() {
    $files = array_reverse($this->_, true);
    return new files($files);
  }

  function sortBy($field, $direction='asc', $method=SORT_REGULAR) {
    $files = a::sort($this->_, $field, $direction, $method);
    return new files($files);
  }

  function paginate($limit, $options=array()) {

    $pagination = new pagination($this, $limit, $options);
    $files= $this->slice($pagination->offset, $pagination->limit);
    $files->pagination = $pagination;

    return $files;

  }
  
  function pagination() {
    return $this->pagination;
  }
    
}

