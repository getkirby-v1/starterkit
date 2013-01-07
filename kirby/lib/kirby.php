<?php

/**
 * Kirby -- A stripped down and easy to use toolkit for PHP
 *
 * @version 0.95
 * @author Bastian Allgeier <bastian@getkirby.com>
 * @link http://toolkit.getkirby.com
 * @copyright Copyright 2009-2012 Bastian Allgeier
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package Kirby
 */


c::set('version', 0.94);
c::set('language', 'en');
c::set('charset', 'utf-8');
c::set('root', dirname(__FILE__));


/**
 * Redirects the user to a new URL
 *
 * @param   string    $url The URL to redirect to
 * @param   boolean   $code The HTTP status code, which should be sent (301, 302 or 303)
 * @package Kirby
 */
function go($url=false, $code=false) {

  if(empty($url)) $url = c::get('url', '/');

  // send an appropriate header
  if($code) {
    switch($code) {
      case 301:
        header('HTTP/1.1 301 Moved Permanently');
        break;
      case 302:
        header('HTTP/1.1 302 Found');
        break;
      case 303:
        header('HTTP/1.1 303 See Other');
        break;
    }
  }
  // send to new page
  header('Location:' . $url);
  exit();
}

/**
 * Returns the status from a Kirby response
 *
 * @param   array    $response The Kirby response array
 * @return  string   "error" or "success"
 * @package Kirby
 */
function status($response) {
  return a::get($response, 'status');
}

/**
 * Returns the message from a Kirby response
 *
 * @param   array    $response The Kirby response array
 * @return  string   The message
 * @package Kirby
 */
function msg($response) {
  return a::get($response, 'msg');
}

/**
 * Checks if a Kirby response is an error response or not. 
 *
 * @param   array    $response The Kirby response array
 * @return  boolean  Returns true if the response is an error, returns false if no error occurred 
 * @package Kirby
 */
function error($response) {
  return (status($response) == 'error') ? true : false;
}

/**
 * Checks if a Kirby response is a success response. 
 *
 * @param   array    $response The Kirby response array
 * @return  boolean  Returns true if the response is a success, returns false if an error occurred
 * @package Kirby
 */
function success($response) {
  return !error($response);
}

/**
 * Loads additional PHP files
 * 
 * You can set the root directory with c::set('root', 'my/root');
 * By default the same directory in which the kirby toolkit file is located will be used.
 * 
 * @param   args     A list filenames as individual arguments
 * @return  array    Returns a Kirby response array. On error the response array includes the unloadable files (errors).
 * @package Kirby
 */
function load() {

  $root   = c::get('root');
  $files  = func_get_args();
  $errors = array();

  foreach((array)$files AS $f) {
    $file = $root . '/' . $f . '.php';
    if(file_exists($file)) {
      include_once($file);
    } else {
      $errors[] = $file;
    }
  }
  
  if(!empty($errors)) return array(
    'status' => 'error',
    'msg'    => 'some files could not be loaded',
    'errors' => $errors
  );
  
  return array(
    'status' => 'success',
    'msg'    => 'all files have been loaded'
  );

}




/**
 * 
 * Array 
 *
 * This class is supposed to simplify array handling
 * and make it more consistent. 
 * 
 * @package Kirby
 */
class a {

  /**
    * Gets an element of an array by key
    *
    * @param  array    $array The source array
    * @param  mixed    $key The key to look for
    * @param  mixed    $default Optional default value, which should be returned if no element has been found
    * @return mixed
    */
  static function get($array, $key, $default=null) {
    return (isset($array[ $key ])) ? $array[ $key ] : $default;
  }
  
  /**
    * Gets all elements for an array of key
    * 
    * @param  array    $array The source array
    * @keys   array    $keys An array of keys to fetch
    * @return array    An array of keys and matching values
    */
  static function getall($array, $keys) {
    $result = array();
    foreach($keys as $key) $result[$key] = $array[$key];
    return $result;
  }

  /**
    * Removes an element from an array
    * 
    * @param  array   $array The source array
    * @param  mixed   $search The value or key to look for
    * @param  boolean $key Pass true to search for an key, pass false to search for an value.   
    * @return array   The result array without the removed element
    */
  static function remove($array, $search, $key=true) {
    if($key) {
      unset($array[$search]);
    } else {
      $found_all = false;
      while(!$found_all) {
        $index = array_search($search, $array);
        if($index !== false) {
          unset($array[$index]);
        } else {
          $found_all = true;
        }
      }
    }
    return $array;
  }

  /**
    * Injects an element into an array
    * 
    * @param  array   $array The source array
    * @param  int     $position The position, where to inject the element
    * @param  mixed   $element The element, which should be injected
    * @return array   The result array including the new element
    */
  static function inject($array, $position, $element='placeholder') {
    $start = array_slice($array, 0, $position);
    $end = array_slice($array, $position);
    return array_merge($start, (array)$element, $end);
  }

  /**
    * Shows an entire array or object in a human readable way
    * This is perfect for debugging
    * 
    * @param  array   $array The source array
    * @param  boolean $echo By default the result will be echoed instantly. You can switch that off here. 
    * @return mixed   If echo is false, this will return the generated array output.
    */
  static function show($array, $echo=true) {
    $output = '<pre>';
    $output .= htmlspecialchars(print_r($array, true));
    $output .= '</pre>';
    if($echo==true) echo $output;
    return $output;
  }

  /**
    * Converts an array to a JSON string
    * It's basically a shortcut for json_encode()
    * 
    * @param  array   $array The source array
    * @return string  The JSON string
    */
  static function json($array) {
    return @json_encode( (array)$array );
  }

  /**
    * Converts an array to a XML string
    * 
    * @param  array   $array The source array
    * @param  string  $tag The name of the root element
    * @param  boolean $head Include the xml declaration head or not
    * @param  string  $charset The charset, which should be used for the header
    * @param  int     $level The indendation level
    * @return string  The XML string
    */
  static function xml($array, $tag='root', $head=true, $charset='utf-8', $tab='  ', $level=0) {
    $result  = ($level==0 && $head) ? '<?xml version="1.0" encoding="' . $charset . '"?>' . "\n" : '';
    $nlevel  = ($level+1);
    $result .= str_repeat($tab, $level) . '<' . $tag . '>' . "\n";
    foreach($array AS $key => $value) {
      $key = str::lower($key);
      if(is_array($value)) {
        $mtags = false;
        foreach($value AS $key2 => $value2) {
          if(is_array($value2)) {
            $result .= self::xml($value2, $key, $head, $charset, $tab, $nlevel);
          } else if(trim($value2) != '') {
            $value2  = (htmlspecialchars($value2) != $value2) ? '<![CDATA[' . $value2 . ']]>' : $value2;
            $result .= str_repeat($tab, $nlevel) . '<' . $key . '>' . $value2 . '</' . $key . '>' . "\n";
          }
          $mtags = true;
        }
        if(!$mtags && count($value) > 0) {
          $result .= self::xml($value, $key, $head, $charset, $tab, $nlevel);
        }
      } else if(trim($value) != '') {
        $value   = (htmlspecialchars($value) != $value) ? '<![CDATA[' . $value . ']]>' : $value;
        $result .= str_repeat($tab, $nlevel) . '<' . $key . '>' . $value . '</' . $key . '>' . "\n";
      }
    }
    return $result . str_repeat($tab, $level) . '</' . $tag . '>' . "\n";
  }

  /**
    * Extracts a single column from an array
    * 
    * @param  array   $array The source array
    * @param  string  $key The key name of the column to extract
    * @return array   The result array with all values from that column. 
    */
  static function extract($array, $key) {
    $output = array();
    foreach($array AS $a) if(isset($a[$key])) $output[] = $a[ $key ];
    return $output;
  }

  /**
    * Shuffles an array and keeps the keys
    * 
    * @param  array   $array The source array
    * @return array   The shuffled result array
    */
  static function shuffle($array) {
    $keys = array_keys($array); 
    shuffle($keys); 
    return array_merge(array_flip($keys), $array); 
  } 

  /**
    * Returns the first element of an array
    *
    * I always have to lookup the names of that function
    * so I decided to make this shortcut which is 
    * easier to remember.
    *
    * @param  array   $array The source array
    * @return mixed   The first element
    */
  static function first($array) {
    return array_shift($array);
  }

  /**
    * Returns the last element of an array
    *
    * I always have to lookup the names of that function
    * so I decided to make this shortcut which is 
    * easier to remember.
    * 
    * @param  array   $array The source array
    * @return mixed   The last element
    */
  static function last($array) {
    return array_pop($array);
  }

  /**
    * Search for elements in an array by regular expression
    *
    * @param  array   $array The source array
    * @param  string  $search The regular expression
    * @return array   The array of results
    */
  static function search($array, $search) {
    return preg_grep('#' . preg_quote($search) . '#i' , $array);
  }

  /**
    * Checks if an array contains a certain string
    *
    * @param  array   $array The source array
    * @param  string  $search The string to search for
    * @return boolean true: the array contains the string, false: it doesn't
    */
  static function contains($array, $search) {
    $search = self::search($array, $search);
    return (empty($search)) ? false : true;
  }

  /**
    * Fills an array up with additional elements to certain amount. 
    *
    * @param  array   $array The source array
    * @param  int     $limit The number of elements the array should contain after filling it up. 
    * @param  mixed   $fill The element, which should be used to fill the array
    * @return array   The filled-up result array
    */
  static function fill($array, $limit, $fill='placeholder') {
    if(count($array) < $limit) {
      $diff = $limit-count($array);
      for($x=0; $x<$diff; $x++) $array[] = $fill;
    }
    return $array;
  }

  /**
    * Checks for missing elements in an array
    *
    * This is very handy to check for missing 
    * user values in a request for example. 
    * 
    * @param  array   $array The source array
    * @param  array   $required An array of required keys
    * @return array   An array of missing fields. If this is empty, nothing is missing. 
    */
  static function missing($array, $required=array()) {
    $missing = array();
    foreach($required AS $r) {
      if(empty($array[$r])) $missing[] = $r;
    }
    return $missing;
  }

  /**
    * Sorts a multi-dimensional array by a certain column
    *
    * @param  array   $array The source array
    * @param  string  $field The name of the column
    * @param  string  $direction desc (descending) or asc (ascending)
    * @param  const   $method A PHP sort method flag. 
    * @return array   The sorted array
    */
  static function sort($array, $field, $direction='desc', $method=SORT_REGULAR) {

    $direction = (strtolower($direction) == 'desc') ? SORT_DESC : SORT_ASC;
    $helper    = array();

    foreach($array as $key => $row) {
      $helper[$key] = (is_object($row)) ? (method_exists($row, $field)) ? str::lower($row->$field()) : str::lower($row->$field) : str::lower($row[$field]);
    }
          
    array_multisort($helper, $direction, $method, $array);
    return $array;
  
  }

}







/**
 *
 * Browser 
 * 
 * Browser sniffing is bad - I know! 
 * But sometimes this class is very helpful to 
 * react on certain browsers and build browser-specific
 * css selectors for example. It's up to you to use it.
 * 
 * @package Kirby
 */
class browser {
  
  /** 
    * The entire user agent string
    *
    * @var string
    */
  static public $ua = false;

  /** 
    * The readable name of the browser
    * For example: "ie"
    * 
    * @var string
    */
  static public $name = false;

  /** 
    * The readable browser engine name
    * For example: "webkit"
    *
    * @var string
    */
  static public $engine = false;

  /** 
    * The browser version number
    * For example: "3.6"
    *
    * @var string
    */  
  static public $version = false;

  /** 
    * The platform name
    * For example: "mac"
    *
    * @var string
    */  
  static public $platform = false;

  /** 
    * True or false if it is a mobile device or not
    *
    * @var boolean
    */  
  static public $mobile = false;

  /** 
    * True or false if it is an iOS device or not
    *
    * @var boolean
    */  
  static public $ios = false;

  /** 
    * True or false if it is an iPhone or not
    *
    * @var boolean
    */  
  static public $iphone = false;

  /** 
    * Returns the name of the browser
    *
    * @param  string  $ua The user agent string
    * @return string  The browser name
    */  
  static function name($ua=null) {
    self::detect($ua);
    return self::$name;
  }

  /** 
    * Returns the browser engine
    *
    * @param  string  $ua The user agent string
    * @return string  The browser engine
    */  
  static function engine($ua=null) {
    self::detect($ua);
    return self::$engine;
  }

  /** 
    * Returns the browser version
    *
    * @param  string  $ua The user agent string
    * @return string  The browser version
    */  
  static function version($ua=null) {
    self::detect($ua);
    return self::$version;
  }

  /** 
    * Returns the platform
    *
    * @param  string  $ua The user agent string
    * @return string  The platform name
    */  
  static function platform($ua=null) {
    self::detect($ua);
    return self::$platform;
  }

  /** 
    * Checks if the user agent string is from a mobile device
    *
    * @param  string  $ua The user agent string
    * @return boolean True: mobile device, false: not a mobile device
    */  
  static function mobile($ua=null) {
    self::detect($ua);
    return self::$mobile;
  }

  /** 
    * Checks if the user agent string is from an iPhone
    *
    * @param  string  $ua The user agent string
    * @return boolean True: iPhone, false: not an iPhone
    */  
  static function iphone($ua=null) {
    self::detect($ua);
    return self::$iphone;
  }

  /** 
    * Checks if the user agent string is from an iOS device
    *
    * @param  string  $ua The user agent string
    * @return boolean True: iOS device, false: not an iOS device
    */  
  static function ios($ua=null) {
    self::detect($ua);
    return self::$ios;
  }

  /** 
    * Returns a browser-specific css selector string
    *
    * @param  string  $ua The user agent string
    * @param  boolean $array True: return an array, false: return a string
    * @return mixed 
    */  
  static function css($ua=null, $array=false) {
    self::detect($ua);
    $css[] = self::$engine;
    $css[] = self::$name;
    if(self::$version) $css[] = self::$name . str_replace('.', '_', self::$version);
    $css[] = self::$platform;
    return ($array) ? $css : implode(' ', $css);
  }

  /** 
    * The core detection method, which parses the user agent string
    *
    * @todo   add new browser versions
    * @param  string  $ua The user agent string
    * @return array   An array with all parsed info 
    */  
  static function detect($ua=null) {
    $ua = ($ua) ? str::lower($ua) : str::lower(server::get('http_user_agent'));

    // don't do the detection twice
    if(self::$ua == $ua) return array(
      'name'     => self::$name,
      'engine'   => self::$engine,
      'version'  => self::$version,
      'platform' => self::$platform,
      'agent'    => self::$ua,
      'mobile'   => self::$mobile,
      'iphone'   => self::$iphone,
      'ios'      => self::$ios,
    );

    self::$ua       = $ua;
    self::$name     = false;
    self::$engine   = false;
    self::$version  = false;
    self::$platform = false;

    // browser
    if(!preg_match('/opera|webtv/i', self::$ua) && preg_match('/msie\s(\d)/', self::$ua, $array)) {
      self::$version = $array[1];
      self::$name = 'ie';
      self::$engine = 'trident';
    } else if(strstr(self::$ua, 'firefox/3.6')) {
      self::$version = 3.6;
      self::$name = 'fx';
      self::$engine = 'gecko';
    } else if (strstr(self::$ua, 'firefox/3.5')) {
      self::$version = 3.5;
      self::$name = 'fx';
      self::$engine = 'gecko';
    } else if(preg_match('/firefox\/(\d+)/i', self::$ua, $array)) {
      self::$version = $array[1];
      self::$name = 'fx';
      self::$engine = 'gecko';
    } else if(preg_match('/opera(\s|\/)(\d+)/', self::$ua, $array)) {
      self::$engine = 'presto';
      self::$name = 'opera';
      self::$version = $array[2];
    } else if(strstr(self::$ua, 'konqueror')) {
      self::$name = 'konqueror';
      self::$engine = 'webkit';
    } else if(strstr(self::$ua, 'iron')) {
      self::$name = 'iron';
      self::$engine = 'webkit';
    } else if(strstr(self::$ua, 'chrome')) {
      self::$name = 'chrome';
      self::$engine = 'webkit';
      if(preg_match('/chrome\/(\d+)/i', self::$ua, $array)) { self::$version = $array[1]; }
    } else if(strstr(self::$ua, 'applewebkit/')) {
      self::$name = 'safari';
      self::$engine = 'webkit';
      if(preg_match('/version\/(\d+)/i', self::$ua, $array)) { self::$version = $array[1]; }
    } else if(strstr(self::$ua, 'mozilla/')) {
      self::$engine = 'gecko';
      self::$name = 'fx';
    }

    // platform
    if(strstr(self::$ua, 'j2me')) {
      self::$platform = 'mobile';
    } else if(strstr(self::$ua, 'iphone')) {
      self::$platform = 'iphone';
    } else if(strstr(self::$ua, 'ipod')) {
      self::$platform = 'ipod';
    } else if(strstr(self::$ua, 'ipad')) {
      self::$platform = 'ipad';
    } else if(strstr(self::$ua, 'mac')) {
      self::$platform = 'mac';
    } else if(strstr(self::$ua, 'darwin')) {
      self::$platform = 'mac';
    } else if(strstr(self::$ua, 'webtv')) {
      self::$platform = 'webtv';
    } else if(strstr(self::$ua, 'win')) {
      self::$platform = 'win';
    } else if(strstr(self::$ua, 'freebsd')) {
      self::$platform = 'freebsd';
    } else if(strstr(self::$ua, 'x11') || strstr(self::$ua, 'linux')) {
      self::$platform = 'linux';
    }

    self::$mobile = (self::$platform == 'mobile') ? true : false;
    self::$iphone = (in_array(self::$platform, array('ipod', 'iphone'))) ? true : false;
    self::$ios    = (in_array(self::$platform, array('ipod', 'iphone', 'ipad'))) ? true : false;

    return array(
      'name'     => self::$name,
      'engine'   => self::$engine,
      'version'  => self::$version,
      'platform' => self::$platform,
      'agent'    => self::$ua,
      'mobile'   => self::$mobile,
      'iphone'   => self::$iphone,
      'ios'      => self::$ios,
    );

  }

}







/**
 * 
 * Config 
 * 
 * This is the core class to handle 
 * configuration values/constants. 
 * 
 * @package Kirby
 */
class c {

  /** 
    * The static config array
    * It contains all config values
    * 
    * @var array
    */
  private static $config = array();
  
  /** 
    * Gets a config value by key
    *
    * @param  string  $key The key to look for. Pass false to get the entire config array
    * @param  mixed   $default The default value, which will be returned if the key has not been found
    * @return mixed   The found config value
    */  
  static function get($key=null, $default=null) {
    if(empty($key)) return self::$config;
    return a::get(self::$config, $key, $default);
  }

  /** 
    * Sets a config value by key
    *
    * @param  string  $key The key to define
    * @param  mixed   $value The value for the passed key
    */  
  static function set($key, $value=null) {
    if(is_array($key)) {
      // set all new values
      self::$config = array_merge(self::$config, $key);
    } else {
      self::$config[$key] = $value;
    }
  }

  /** 
    * Loads an additional config file 
    * Returns the entire configuration array
    *
    * @param  string  $file The path to the config file
    * @return array   The entire config array
    */  
  static function load($file) {
    if(file_exists($file)) require_once($file);
    return c::get();
  }

}






/**
 * 
 * Content
 * 
 * This class handles output buffering,
 * content loading and setting content type headers. 
 * 
 * @package Kirby
 */
class content {
  
  /**
    * Starts the output buffer
    * 
    */
  static function start() {
    ob_start();
  }

  /**
    * Stops the output buffer
    * and flush the content or return it.
    * 
    * @param  boolean  $return Pass true to return the content instead of flushing it 
    * @return mixed
    */
  static function end($return=false) {
    if($return) {
      $content = ob_get_contents();
      ob_end_clean();
      return $content;
    }
    ob_end_flush();
  }

  /**
    * Loads content from a passed file
    * 
    * @param  string  $file The path to the file
    * @param  boolean $return True: return the content of the file, false: echo the content
    * @return mixed
    */
  static function load($file, $return=true) {
    self::start();
    require_once($file);
    $content = self::end(true);
    if($return) return $content;
    echo $content;        
  }

  /**
    * Simplifies setting content type headers
    * 
    * @param  string  $ctype The shortcut for the content type. See the keys of the $ctypes array for all available shortcuts
    * @param  string  $charset The charset definition for the content type header. Default is "utf-8"
    */
  static function type() {
    $args = func_get_args();

    // shortcuts for content types
    $ctypes = array(
      'html' => 'text/html',
      'css'  => 'text/css',
      'js'   => 'text/javascript',
      'jpg'  => 'image/jpeg',
      'png'  => 'image/png',
      'gif'  => 'image/gif',
      'json' => 'application/json'
    );

    $ctype   = a::get($args, 0, c::get('content_type', 'text/html'));
    $ctype   = a::get($ctypes, $ctype, $ctype);
    $charset = a::get($args, 1, c::get('charset', 'utf-8'));

    header('Content-type: ' . $ctype . '; charset=' . $charset);

  }

}







/**
 * 
 * Cookie
 * 
 * This class makes cookie handling easy
 * 
 * @package Kirby
 */
class cookie {

  /**
    * Set a new cookie
    * 
    * @param  string  $key The name of the cookie
    * @param  string  $value The cookie content
    * @param  int     $expires The number of seconds until the cookie expires
    * @param  string  $domain The domain to set this cookie for. 
    * @return boolean true: the cookie has been created, false: cookie creation failed
    */
  static function set($key, $value, $expires=3600, $domain='/') {
    if(is_array($value)) $value = a::json($value);
    $_COOKIE[$key] = $value;
    return @setcookie($key, $value, time()+$expires, $domain);
  }

  /**
    * Get a cookie value
    * 
    * @param  string  $key The name of the cookie
    * @param  string  $default The default value, which should be returned if the cookie has not been found
    * @return mixed   The found value
    */
  static function get($key, $default=null) {
    return a::get($_COOKIE, $key, $default);
  }

  /**
    * Remove a cookie
    * 
    * @param  string  $key The name of the cookie
    * @param  string  $domain The domain of the cookie
    * @return mixed   true: the cookie has been removed, false: the cookie could not be removed
    */
  static function remove($key, $domain='/') {
    $_COOKIE[$key] = false;
    return @setcookie($key, false, time()-3600, $domain);
  }

}






/**
 * 
 * Database
 * 
 * Database handling sucks - not with this class :)
 * 
 * Configure your database connection like this:
 * 
 * <code>
 * c::set('db.host', 'localhost');
 * c::set('db.user', 'root');
 * c::set('db.password', '');
 * c::set('db.name', 'mydb');
 * c::set('db.prefix', '');
 * </code>
 * 
 * @package Kirby
 */
class db {

  /**
    * Traces all db queries
    *
    * @var array
    */
  public  static $trace = array();

  /**
    * The connection resource
    * 
    * @var mixed
    */
  private static $connection = false;

  /**
    * The selected database
    * 
    * @var string
    */
  private static $database = false;

  /** 
    * The used charset
    * Default is "utf8"
    * 
    * @var string
    */
  private static $charset = false;

  /** 
    * The last used query
    * 
    * @var string
    */
  private static $last_query = false;

  /** 
    * The number of affected rows 
    * for the last query
    * 
    * @var int
    */
  private static $affected = 0;

  /** 
    * The core connection method
    * Tries to connect to the server
    * Selects the database and sets the charset
    * 
    * It will only connect once and return
    * that same connection for all following queries
    * 
    * @return mixed
    */
  static function connect() {

    $connection = self::connection();
    $args       = func_get_args();
    $host       = a::get($args, 0, c::get('db.host', 'localhost'));
    $user       = a::get($args, 1, c::get('db.user', 'root'));
    $password   = a::get($args, 2, c::get('db.password'));
    $database   = a::get($args, 3, c::get('db.name'));
    $charset    = a::get($args, 4, c::get('db.charset', 'utf8'));

    // don't connect again if it's already done
    $connection = (!$connection) ? @mysql_connect($host, $user, $password) : $connection;

    // react on connection failures
    if(!$connection) return self::error(l::get('db.errors.connect', 'Database connection failed'), true);

    self::$connection = $connection;

    // select the database
    $database = self::database($database);
    if(error($database)) return $database;

    // set the right charset
    $charset = self::charset($charset);
    if(error($charset)) return $charset;

    return $connection;

  }

  /** 
    * Returns the current connection or false
    * 
    * @return mixed
    */
  static function connection() {
    return (is_resource(self::$connection)) ? self::$connection : false;
  }

  /** 
    * Disconnects from the server
    * 
    * @return boolean
    */
  static function disconnect() {

    if(!c::get('db.disconnect')) return false;

    $connection = self::connection();
    if(!$connection) return false;

    // kill the connection
    $disconnect = @mysql_close($connection);
    self::$connection = false;

    if(!$disconnect) return self::error(l::get('db.errors.disconnect', 'Disconnecting database failed'));
    return true;

  }

  /** 
    * Selects a database
    * 
    * @param  string $database
    * @return mixed
    */
  static function database($database) {

    if(!$database) return self::error(l::get('db.errors.missing_db_name', 'Please provide a database name'), true);

    // check if there is a selected database
    if(self::$database == $database) return true;

    // select a new database
    $select = @mysql_select_db($database, self::connection());

    if(!$select) return self::error(l::get('db.errors.missing_db', 'Selecting database failed'), true);

    self::$database = $database;

    return $database;

  }

  /** 
    * Sets the charset for all queries
    * The default and recommended charset is utf8
    *
    * @param  string $charset
    * @return mixed
    */
  static function charset($charset='utf8') {

    // check if there is a assigned charset and compare it
    if(self::$charset == $charset) return true;

    // set the new charset
    $set = @mysql_query('SET NAMES ' . $charset);

    if(!$set) return self::error(l::get('db.errors.setting_charset_failed', 'Setting database charset failed'));

    // save the new charset to the globals
    self::$charset = $charset;
    return $charset;

  }

  /** 
    * Runs a MySQL query. 
    * You can use any valid MySQL query here. 
    * This is also the fallback method if you
    * can't use one of the provided shortcut methods
    * from this class. 
    *
    * @param  string  $sql The sql query
    * @param  boolean $fetch True: apply db::fetch to the result, false: go without db::fetch
    * @return mixed
    */
  static function query($sql, $fetch=true) {

    $connection = self::connect();
    if(error($connection)) return $connection;

    // save the query
    self::$last_query = $sql;

    // execute the query
    $result = @mysql_query($sql, $connection);

    self::$affected = @mysql_affected_rows();
    self::$trace[] = $sql;

    if(!$result) return self::error(l::get('db.errors.query_failed', 'The database query failed'));
    if(!$fetch) return $result;

    $array = array();
    while($r = self::fetch($result)) array_push($array, $r);
    return $array;

  }

  /** 
    * Executes a MySQL query without result set.
    * This is used for queries like update, delete or insert
    *
    * @param  string  $sql The sql query
    * @return mixed
    */
  static function execute($sql) {

    $connection = self::connect();
    if(error($connection)) return $connection;

    // save the query
    self::$last_query = $sql;

    // execute the query
    $execute = @mysql_query($sql, $connection);

    self::$affected = @mysql_affected_rows();
    self::$trace[] = $sql;

    if(!$execute) return self::error(l::get('db.errors.query_failed', 'The database query failed'));
    
    $last_id = self::last_id();
    return ($last_id === false) ? self::$affected : self::last_id();
  }

  /** 
    * Returns the number of affected rows for the last query
    *
    * @return int
    */
  static function affected() {
      return self::$affected;
  }

  /** 
    * Returns the last returned insert id
    *
    * @return int
    */
  static function last_id() {
    $connection = self::connection();
    return @mysql_insert_id($connection);
  }

  /** 
    * Shortcut for mysql_fetch_array
    *
    * @param  resource  $result the unfetched result from db::query()
    * @param  const     $type PHP flag for mysql_fetch_array
    * @return array     The key/value result array 
    */
  static function fetch($result, $type=MYSQL_ASSOC) {
    if(!$result) return array();
    return @mysql_fetch_array($result, $type);
  }

  /** 
    * Returns an array of fields in a given table
    *
    * @param  string  $table The table name
    * @return array   The array of field names
    */
  static function fields($table) {

    $connection = self::connect();
    if(error($connection)) return $connection;

    $fields = @mysql_list_fields(self::$database, self::prefix($table), $connection);

    if(!$fields) return self::error(l::get('db.errors.listing_fields_failed', 'Listing fields failed'));

    $output = array();
    $count  = @mysql_num_fields($fields);

    for($x=0; $x<$count; $x++) {
      $output[] = @mysql_field_name($fields, $x);
    }

    return $output;

  }

  /** 
    * Runs a INSERT query
    *
    * @param  string  $table The table name
    * @param  mixed   $input Either a key/value array or a valid MySQL insert string 
    * @param  boolean $ignore Set this to true to ignore duplicates
    * @return mixed   The last inserted id if everything went fine or an error response. 
    */
  static function insert($table, $input, $ignore=false) {
    $ignore = ($ignore) ? ' IGNORE' : '';
    return self::execute('INSERT' . ($ignore) . ' INTO ' . self::prefix($table) . ' SET ' . self::values($input));
  }

  /** 
    * Runs a INSERT query with values
    *
    * @param  string  $table The table name
    * @param  array   $fields an array of field names
    * @param  array   $values an array of array of keys and values. 
    * @return mixed   The last inserted id if everything went fine or an error response. 
    */
  static function insert_all($table, $fields, $values) {
      
    $query = 'INSERT INTO ' . self::prefix($table) . ' (' . implode(',', $fields) . ') VALUES ';
    $rows  = array();
    
    foreach($values AS $v) {    
      $str = '(\'';
      $sep = '';
      
      foreach($v AS $input) {
        $str .= $sep . db::escape($input);            
        $sep = "','";  
      }

      $str .= '\')';
      $rows[] = $str;
    }
    
    $query .= implode(',', $rows);
    return db::execute($query);
  
  }

  /** 
    * Runs a REPLACE query
    *
    * @param  string  $table The table name
    * @param  mixed   $input Either a key/value array or a valid MySQL insert string 
    * @return mixed   The last inserted id if everything went fine or an error response. 
    */
  static function replace($table, $input) {
    return self::execute('REPLACE INTO ' . self::prefix($table) . ' SET ' . self::values($input));
  }

  /** 
    * Runs an UPDATE query
    *
    * @param  string  $table The table name
    * @param  mixed   $input Either a key/value array or a valid MySQL insert string 
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @return mixed   The number of affected rows or an error response
    */
  static function update($table, $input, $where) {
    return self::execute('UPDATE ' . self::prefix($table) . ' SET ' . self::values($input) . ' WHERE ' . self::where($where));
  }

  /** 
    * Runs a DELETE query
    *
    * @param  string  $table The table name
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @return mixed   The number of affected rows or an error response
    */
  static function delete($table, $where='') {
    $sql = 'DELETE FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);
    return self::execute($sql);
  }

  /** 
    * Returns multiple rows from a table
    *
    * @param  string  $table The table name
    * @param  mixed   $select Either an array of fields or a MySQL string of fields
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @param  string  $order Order clause without the order keyword. ie: "added desc"
    * @param  int     $page a page number
    * @param  int     $limit a number for rows to return
    * @param  boolean $fetch true: apply db::fetch(), false: don't apply db::fetch()
    * @return mixed      
    */
  static function select($table, $select='*', $where=null, $order=null, $page=null, $limit=null, $fetch=true) {

    if($limit === 0) return array();

    if(is_array($select)) $select = self::select_clause($select);

    $sql = 'SELECT ' . $select . ' FROM ' . self::prefix($table);

    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);
    if(!empty($order)) $sql .= ' ORDER BY ' . $order;
    if($page !== null && $limit !== null) $sql .= ' LIMIT ' . $page . ',' . $limit;

    return self::query($sql, $fetch);

  }

  /** 
    * Returns a single row from a table
    *
    * @param  string  $table The table name
    * @param  mixed   $select Either an array of fields or a MySQL string of fields
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @param  string  $order Order clause without the order keyword. ie: "added desc"
    * @return mixed      
    */
  static function row($table, $select='*', $where=null, $order=null) {
    $result = self::select($table, $select, $where, $order, 0,1, false);
    return self::fetch($result);
  }

  /** 
    * Returns all values from single column of a table
    *
    * @param  string  $table The table name
    * @param  string  $column The name of the column
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @param  string  $order Order clause without the order keyword. ie: "added desc"
    * @param  int     $page a page number
    * @param  int     $limit a number for rows to return
    * @return mixed      
    */
  static function column($table, $column, $where=null, $order=null, $page=null, $limit=null) {

    $result = self::select($table, $column, $where, $order, $page, $limit, false);

    $array = array();
    while($r = self::fetch($result)) array_push($array, a::get($r, $column));
    return $array;
  }

  /** 
    * Returns a single field value from a table
    *
    * @param  string  $table The table name
    * @param  string  $field The name of the field
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @param  string  $order Order clause without the order keyword. ie: "added desc"
    * @return mixed      
    */
  static function field($table, $field, $where=null, $order=null) {
    $result = self::row($table, $field, $where, $order);
    return a::get($result, $field);
  }

  /** 
    * Joins two tables and returns data from them
    *
    * @param  string  $table_1 The table name of the first table
    * @param  string  $table_2 The table name of the second table
    * @param  string  $on The MySQL ON clause without the ON keyword. ie: "user_id = comment_user" 
    * @param  mixed   $select Either an array of fields or a MySQL string of fields
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @param  string  $order Order clause without the order keyword. ie: "added desc"
    * @param  int     $page a page number
    * @param  int     $limit a number for rows to return
    * @param  string  $type The join type (JOIN, LEFT, RIGHT, INNER)
    * @return mixed      
    */
  static function join($table_1, $table_2, $on, $select, $where=null, $order=null, $page=null, $limit=null, $type="JOIN") {
      return self::select(
        self::prefix($table_1) . ' ' . $type . ' ' .
        self::prefix($table_2) . ' ON ' .
        self::where($on),
        $select,
        self::where($where),
        $order,
        $page,
        $limit
      );
  }

  /** 
    * Runs a LEFT JOIN
    *
    * @param  string  $table_1 The table name of the first table
    * @param  string  $table_2 The table name of the second table
    * @param  string  $on The MySQL ON clause without the ON keyword. ie: "user_id = comment_user" 
    * @param  mixed   $select Either an array of fields or a MySQL string of fields
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @param  string  $order Order clause without the order keyword. ie: "added desc"
    * @param  int     $page a page number
    * @param  int     $limit a number for rows to return
    * @return mixed      
    */
  static function left_join($table_1, $table_2, $on, $select, $where=null, $order=null, $page=null, $limit=null) {
      return self::join($table_1, $table_2, $on, $select, $where, $order, $page, $limit, 'LEFT JOIN');
  }

  /** 
    * Counts a number of rows in a table
    *
    * @param  string  $table The table name
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @return int      
    */
  static function count($table, $where='') {
    $result = self::row($table, 'count(*)', $where);
    return ($result) ? a::get($result, 'count(*)') : 0;
  }

  /** 
    * Gets the minimum value in a column of a table
    *
    * @param  string  $table The table name
    * @param  string  $column The name of the column
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @return mixed      
    */
  static function min($table, $column, $where=null) {

    $sql = 'SELECT MIN(' . $column . ') AS min FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

    $result = self::query($sql, false);
    $result = self::fetch($result);

    return a::get($result, 'min', 1);

  }

  /** 
    * Gets the maximum value in a column of a table
    *
    * @param  string  $table The table name
    * @param  string  $column The name of the column
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @return mixed      
    */
  static function max($table, $column, $where=null) {

    $sql = 'SELECT MAX(' . $column . ') AS max FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

    $result = self::query($sql, false);
    $result = self::fetch($result);

    return a::get($result, 'max', 1);

  }

  /** 
    * Gets the sum of values in a column of a table
    *
    * @param  string  $table The table name
    * @param  string  $column The name of the column
    * @param  mixed   $where Either a key/value array as AND connected where clause or a simple MySQL where clause string
    * @return mixed      
    */
  static function sum($table, $column, $where=null) {

    $sql = 'SELECT SUM(' . $column . ') AS sum FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

    $result = self::query($sql, false);
    $result = self::fetch($result);

    return a::get($result, 'sum', 0);

  }
  
  /** 
    * Adds a prefix to a table name if set in c::set('db.prefix', 'myprefix_');
    * This makes it possible to use table names in all methods without prefix
    * and it will still be applied automatically. 
    * 
    * @param  string $table The name of the table with or without prefix
    * @return string The sanitized table name. 
    */
  static function prefix($table) {
    $prefix = c::get('db.prefix');
    if(!$prefix) return $table;
    return (!str::contains($table,$prefix)) ? $prefix . $table : $table;
  }

  /** 
    * Strips table specific column prefixes from the result array
    *
    * If you use column names like user_username, user_id, etc. 
    * use this method on the result array to strip user_ of all fields
    * 
    * @param  array $array The result array
    * @return array The result array without those damn prefixes. 
    */
  static function simple_fields($array) {
    if(empty($array)) return false;
    $output = array();
    foreach($array AS $key => $value) {
      $key = substr($key, strpos($key, '_')+1);
      $output[$key] = $value;
    }
    return $output;
  }

  /** 
    * Makes it possible to use arrays for inputs instead of MySQL strings 
    * 
    * @param  array   $input
    * @return string  The final MySQL string, which will be used in the queries. 
    */
  static function values($input) {
    if(!is_array($input)) return $input;

    $output = array();
    foreach($input AS $key => $value) {
      if($value === 'NOW()')
        $output[] = $key . ' = NOW()';
      elseif(is_array($value))
        $output[] = $key . ' = \'' . a::json($value) . '\'';
      else
        $output[] = $key . ' = \'' . self::escape($value) . '\'';
    }
    return implode(', ', $output);

  }
    
  /**
    * Escapes unwanted stuff in values like slashes, etc. 
    *
    * @param  string $value
    * @return string Returns the escaped string
    */
  static function escape($value) {
    $value = str::stripslashes($value);
    return mysql_real_escape_string((string)$value, self::connect());
  }
  
  /**
    * A simplifier to build search clauses
    *
    * @param string  $search The search word
    * @param array   $fields An array of fields to search
    * @param string  $mode OR or AND
    * @return string Returns the final where clause
    */
  static function search_clause($search, $fields, $mode='OR') {

    if(empty($search)) return false;

    $arr = array();
    foreach($fields AS $f) {
      array_push($arr, $f . ' LIKE \'%' . $search . '%\'');
      //array_push($arr, $f . ' REGEXP "[[:<:]]' . db::escape($search) . '[[:>:]]"');
    }
    return '(' . implode(' ' . trim($mode) . ' ', $arr) . ')';

  }
  
  /**
    * An easy method to build a part of the where clause to find stuff by its first character
    *
    * @param string  $field The name of the field
    * @param string  $char The character to search for
    * @return string Returns the where clause part
    */  
  static function with($field, $char) {
    return 'LOWER(SUBSTRING(' . $field . ',1,1)) = "' . db::escape($char) . '"';
  }

  /**
    * Builds a select clause from a simple array
    *
    * @param  array   $field An array of field names
    * @return string  The MySQL string
    */    
  static function select_clause($fields) {
    return implode(', ', $fields);
  }

  /**
    * A simplifier to build IN clauses
    *
    * @param  array   $array An array of fieldnames
    * @return string  The MySQL string for the where clause
    */    
  static function in($array) {
    return '\'' . implode('\',\'', $array) . '\'';
  }

  /**
    * A handler to convert key/value arrays to an where clause
    *
    * @param  array   $array keys/values for the where clause
    * @param  string  $method AND or OR
    * @return string  The MySQL string for the where clause
    */    
  static function where($array, $method='AND') {

    if(!is_array($array)) return $array;

    $output = array();
    foreach($array AS $field => $value) {
      $output[] = $field . ' = \'' . self::escape($value) . '\'';
      $separator = ' ' . $method . ' ';
    }
    return implode(' ' . $method . ' ', $output);

  }

  /**
    * An internal error handler
    *
    * @param  string  $msg The error/success message to return
    * @param  boolean  $exit die after this error?
    * @return mixed
    */    
  static function error($msg=null, $exit=false) {

    $connection = self::connection();

    $error  = (mysql_error()) ? @mysql_error($connection) : false;
    $number = (mysql_errno()) ? @mysql_errno($connection) : 0;

    if(c::get('db.debugging')) {
      if($error) $msg .= ' -> ' . $error . ' (' . $number . ')';
      if(self::$last_query) $msg .= ' Query: ' . self::$last_query;
    } else $msg .= ' - ' . l::get('db.errors.msg', 'This will be fixed soon!');

    if($exit || c::get('db.debugging')) die($msg);

    return array(
      'status' => 'error',
      'msg' => $msg
    );

  }

}





/**
 * 
 * Directory
 * 
 * This class makes it easy to create/edit/delete 
 * directories on the filesystem
 * 
 * @package Kirby
 */
class dir {
  
  /**
   * Creates a new directory
   * 
   * @param   string  $dir The path for the new directory
   * @return  boolean True: the dir has been created, false: creating failed
   */
  static function make($dir) {
    if(is_dir($dir)) return true;
    if(!@mkdir($dir, 0755)) return false;
    @chmod($dir, 0755);
    return true;
  }

  /**
   * Reads all files from a directory and returns them as an array. 
   * It skips unwanted invisible stuff. 
   * 
   * @param   string  $dir The path of directory
   * @param   array   $ignore Optional array with filenames, which should be ignored
   * @return  mixed   An array of filenames or false
   */
  static function read($dir, $ignore=array()) {
    if(!is_dir($dir)) return false;
    $skip = array_merge(array('.', '..', '.DS_Store'), $ignore);
    return array_diff(scandir($dir),$skip);
  }

  /**
   * Reads a directory and returns a full set of info about it
   * 
   * @param   string  $dir The path of directory
   * @param   array   $ignore Optional array with filenames, which should be ignored
   * @return  mixed   An info array or false
   */  
  static function inspect($dir, $ignore=array()) {
    
    if(!is_dir($dir)) return array();

    $files    = dir::read($dir, $ignore);
    $modified = filemtime($dir);
    
    $data = array(
      'name'     => basename($dir),
      'root'     => $dir,
      'modified' => $modified,
      'files'    => array(),
      'children' => array()
    );

    foreach($files AS $file) {
      if(is_dir($dir . '/' . $file)) {
        $data['children'][] = $file;
      } else {
        $data['files'][] = $file;
      }   
    }
    
    return $data;   
          
  }

  /**
   * Moves a directory to a new location
   * 
   * @param   string  $old The current path of the directory
   * @param   string  $new The desired path where the dir should be moved to
   * @return  boolean True: the directory has been moved, false: moving failed
   */  
  static function move($old, $new) {
    if(!is_dir($old)) return false;
    return (@rename($old, $new) && is_dir($new)) ? true : false;
  }

  /**
   * Deletes a directory
   * 
   * @param   string   $dir The path of the directory
   * @param   boolean  $keep If set to true, the directory will flushed but not removed. 
   * @return  boolean  True: the directory has been removed, false: removing failed
   */  
  static function remove($dir, $keep=false) {
    if(!is_dir($dir)) return false;

    $handle = @opendir($dir);
    $skip  = array('.', '..');

    if(!$handle) return false;

    while($item = @readdir($handle)) {
      if(is_dir($dir . '/' . $item) && !in_array($item, $skip)) {
        self::remove($dir . '/' . $item);
      } else if(!in_array($item, $skip)) {
        @unlink($dir . '/' . $item);
      }
    }

    @closedir($handle);
    if(!$keep) return @rmdir($dir);
    return true;

  }

  /**
   * Flushes a directory
   * 
   * @param   string   $dir The path of the directory
   * @return  boolean  True: the directory has been flushed, false: flushing failed
   */  
  static function clean($dir) {
    return self::remove($dir, true);
  }

  /**
   * Gets the size of the directory and all subfolders and files
   * 
   * @param   string   $dir The path of the directory
   * @param   boolean  $recursive 
   * @param   boolean  $nice returns the size in a human readable size 
   * @return  mixed  
   */  
  static function size($path, $recursive=true, $nice=false) {
    if(!file_exists($path)) return false;
    if(is_file($path)) return self::size($path, $nice);
    $size = 0;
    foreach(glob($path."/*") AS $file) {
      if($file != "." && $file != "..") {
        if($recursive) {
          $size += self::size($file, true);
        } else {
          $size += f::size($path);
        }
      }
    }
    return ($nice) ? f::nice_size($size) : $size;
  }

  /**
   * Recursively check when the dir and all 
   * subfolders have been modified for the last time. 
   * 
   * @param   string   $dir The path of the directory
   * @param   int      $modified internal modified store 
   * @return  int  
   */  
  static function modified($dir, $modified=false) {
    if($modified === false) $modified = filemtime($dir);
    $files = self::read($dir);
    foreach($files AS $file) {
      if(!is_dir($dir . '/' . $file)) continue;
      $filectime = filemtime($dir . '/' . $file);
      $modified  = ($filectime > $modified) ? $filectime : $modified;
      $modified  = self::modified($dir . '/' . $file, $modified);
    }
    return $modified;
  }

}






/**
 * 
 * File
 * 
 * This class makes it easy to 
 * create/edit/delete files
 * 
 * @package Kirby
 */
class f {
  
  /**
   * Creates a new file
   * 
   * @param  string  $file The path for the new file
   * @param  mixed   $content Either a string or an array. Arrays will be converted to JSON. 
   * @param  boolean $append true: append the content to an exisiting file if available. false: overwrite. 
   * @return boolean 
   */  
  static function write($file,$content,$append=false){
    if(is_array($content)) $content = a::json($content);
    $mode = ($append) ? FILE_APPEND : false;
    $write = @file_put_contents($file, $content, $mode);
    @chmod($file, 0666);
    return $write;
  }

  /**
   * Appends new content to an existing file
   * 
   * @param  string  $file The path for the file
   * @param  mixed   $content Either a string or an array. Arrays will be converted to JSON. 
   * @return boolean 
   */  
  static function append($file,$content){
    return self::write($file,$content,true);
  }
  
  /**
   * Reads the content of a file
   * 
   * @param  string  $file The path for the file
   * @param  mixed   $parse if set to true, parse the result with the passed method. See: "str::parse()" for more info about available methods. 
   * @return mixed 
   */  
  static function read($file, $parse=false) {
    $content = @file_get_contents($file);
    return ($parse) ? str::parse($content, $parse) : $content;
  }

  /**
   * Moves a file to a new location
   * 
   * @param  string  $old The current path for the file
   * @param  string  $new The path to the new location
   * @return boolean 
   */  
  static function move($old, $new) {
    if(!file_exists($old)) return false;
    return (@rename($old, $new) && file_exists($new)) ? true : false;
  }

  /**
   * Deletes a file
   * 
   * @param  string  $file The path for the file
   * @return boolean 
   */  
  static function remove($file) {
    return (file_exists($file) && is_file($file) && !empty($file)) ? @unlink($file) : false;
  }

  /**
   * Gets the extension of a file
   * 
   * @param  string  $file The filename or path
   * @return string 
   */  
  static function extension($filename) {
    $ext = str_replace('.', '', strtolower(strrchr(trim($filename), '.')));
    return url::strip_query($ext);
  }

  /**
   * Extracts the filename from a file path
   * 
   * @param  string  $file The path
   * @return string 
   */  
  static function filename($name) {
    return basename($name);
  }

  /**
   * Extracts the name from a file path or filename without extension
   * 
   * @param  string  $file The path or filename
   * @param  boolean $remove_path remove the path from the name
   * @return string 
   */  
  static function name($name, $remove_path = false) {
    if($remove_path == true) $name = self::filename($name);
    $dot=strrpos($name,'.');
    if($dot) $name=substr($name,0,$dot);
    return $name;
  }

  /**
   * Just an alternative for dirname() to stay consistent
   * 
   * @param  string  $file The path
   * @return string 
   */  
  static function dirname($file=__FILE__) {
    return dirname($file);
  }

  /**
   * Returns the size of a file.
   * 
   * @param  string  $file The path
   * @param  boolean $nice True: return the size in a human readable format
   * @return mixed
   */    
  static function size($file, $nice=false) {
    @clearstatcache();
    $size = @filesize($file);
    if(!$size) return false;
    return ($nice) ? self::nice_size($size) : $size;
  }

  /**
   * Converts an integer size into a human readable format
   * 
   * @param  int $size The file size
   * @return string
   */    
  static function nice_size($size) {
    $size = str::sanitize($size, 'int');
    if($size < 1) return '0 kb';

    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
  }
    
  /**
   * Convert the filename to a new extension
   * 
   * @param  string $name The file name
   * @param  string $type The new extension
   * @return string
   */    
  static function convert($name, $type='jpg') {
    return self::name($name) . $type;
  }

  /**
   * Sanitize a filename to strip unwanted special characters
   * 
   * @param  string $string The file name
   * @return string
   */    
  static function safe_name($string) {
    return str::urlify($string);
  }

}






/**
 * 
 * Globals
 * 
 * The Kirby Globals Class
 * Easy setting/getting of globals
 * 
 * @package Kirby
 */
class g {

  /**
    * Gets an global value by key
    *
    * @param  mixed    $key The key to look for. Pass false or null to return the entire globals array. 
    * @param  mixed    $default Optional default value, which should be returned if no element has been found
    * @return mixed
    */
  static function get($key=null, $default=null) {
    if(empty($key)) return $GLOBALS;
    return a::get($GLOBALS, $key, $default);
  }

  /** 
    * Sets a global by key
    *
    * @param  string  $key The key to define
    * @param  mixed   $value The value for the passed key
    */  
  static function set($key, $value=null) {
    if(is_array($key)) {
      // set all new values
      $GLOBALS = array_merge($GLOBALS, $key);
    } else {
      $GLOBALS[$key] = $value;
    }
  }

}






/**
 * 
 * Language
 * 
 * Some handy methods to handle multi-language support
 * 
 * @todo rework all but set() and get()
 * @package Kirby
 */
class l {
  
  /**
   * The global language array
   * 
   * @var array
   */
  public static $lang = array();

  /**
    * Gets a language value by key
    *
    * @param  mixed    $key The key to look for. Pass false or null to return the entire language array. 
    * @param  mixed    $default Optional default value, which should be returned if no element has been found
    * @return mixed
    */
  static function get($key=null, $default=null) {
    if(empty($key)) return self::$lang;
    return a::get(self::$lang, $key, $default);
  }

  /** 
    * Sets a language value by key
    *
    * @param  mixed   $key The key to define
    * @param  mixed   $value The value for the passed key
    */  
  static function set($key, $value=null) {
    if(is_array($key)) {
      self::$lang = array_merge(self::$lang, $key);
    } else {
      self::$lang[$key] = $value;
    }
  }
  
  /**
    * @todo rework
    */  
  static function change($language='en') {
    s::set('language', l::sanitize($language));
    return s::get('language');
  }

  /**
    * @todo rework
    */  
  static function current() {
    if(s::get('language')) return s::get('language');
    $lang = str::split(server::get('http_accept_language'), '-');
    $lang = str::trim(a::get($lang, 0));
    $lang = l::sanitize($lang);
    s::set('language', $lang);
    return $lang;
  }

  /**
    * @todo rework
    */  
  static function locale($language=false) {
    if(!$language) $language = l::current();
    $default_locales = array(
      'de' => array('de_DE.UTF8','de_DE@euro','de_DE','de','ge'),
      'fr' => array('fr_FR.UTF8','fr_FR','fr'),
      'es' => array('es_ES.UTF8','es_ES','es'),
      'it' => array('it_IT.UTF8','it_IT','it'),
      'pt' => array('pt_PT.UTF8','pt_PT','pt'),
      'zh' => array('zh_CN.UTF8','zh_CN','zh'),
      'en' => array('en_US.UTF8','en_US','en'),
    );
    $locales = c::get('locales', array());
    $locales = array_merge($default_locales, $locales);
    setlocale(LC_ALL, a::get($locales, $language, array('en_US.UTF8','en_US','en')));
    return setlocale(LC_ALL, 0);
  }

  /**
    * @todo rework
    */  
  static function load($file) {

    // replace the language variable
    $file = str_replace('{language}', l::current(), $file);

    // check if it exists
    if(file_exists($file)) {
      require($file);
      return l::get();
    }

    // try to find the default language file
    $file = str_replace('{language}', c::get('language', 'en'), $file);

    // check again if it exists
    if(file_exists($file)) require($file);
    return l::get();

  }

  /**
    * @todo rework
    */  
  static function sanitize($language) {
    if(!in_array($language, c::get('languages', array('en')) )) $language = c::get('language', 'en');
    return $language;
  }

}







/**
 * 
 * Request
 * 
 * Handles all incoming requests
 * 
 * @package Kirby
 */
class r {

  /**
    * Stores all sanitized request data
    * 
    * @var array
    */
  static private $_ = false;

  // fetch all data from the request and sanitize it
  static function data() {
    if(self::$_) return self::$_;
    return self::$_ = self::sanitize($_REQUEST);
  }

  /**
    * Sanitizes the incoming data
    * 
    * @param  array $data
    * @return array
    */
  static function sanitize($data) {
    foreach($data as $key => $value) {
      if(!is_array($value)) { 
        $value = trim(str::stripslashes($value));
      } else {
        $value = self::sanitize($value);
      }
      $data[$key] = $value;    
    }      
    return $data;  
  }

  /** 
    * Sets a request value by key
    *
    * @param  mixed   $key The key to define
    * @param  mixed   $value The value for the passed key
    */    
  static function set($key, $value=null) {
    $data = self::data();
    if(is_array($key)) {
      self::$_ = array_merge($data, $key);
    } else {
      self::$_[$key] = $value;
    }
  }

  /**
    * Gets a request value by key
    *
    * @param  mixed    $key The key to look for. Pass false or null to return the entire request array. 
    * @param  mixed    $default Optional default value, which should be returned if no element has been found
    * @return mixed
    */  
  static function get($key=false, $default=null) {
    $request = (self::method() == 'GET') ? self::data() : array_merge(self::data(), self::body());
    if(empty($key)) return $request;
    return a::get($request, $key, $default);
  }

  /**
    * Returns the current request method
    *
    * @return string POST, GET, DELETE, PUT
    */  
  static function method() {
    return strtoupper(server::get('request_method'));
  }

  /**
    * Returns the request body from POST requests for example
    *
    * @return array
    */    
  static function body() {
    @parse_str(@file_get_contents('php://input'), $body); 
    return self::sanitize((array)$body);
  }

  /**
    * Checks if the current request is an AJAX request
    * 
    * @return boolean
    */
  static function is_ajax() {
    return (strtolower(server::get('http_x_requested_with')) == 'xmlhttprequest') ? true : false;
  }

  /**
    * Checks if the current request is a GET request
    * 
    * @return boolean
    */  
  static function is_get() {
    return (self::method() == 'GET') ? true : false;
  }

  /**
    * Checks if the current request is a POST request
    * 
    * @return boolean
    */    
  static function is_post() {
    return (self::method() == 'POST') ? true : false; 
  }

  /**
    * Checks if the current request is a DELETE request
    * 
    * @return boolean
    */    
  static function is_delete() {
    return (self::method() == 'DELETE') ? true : false; 
  }

  /**
    * Checks if the current request is a PUT request
    * 
    * @return boolean
    */    
  static function is_put() {
    return (self::method() == 'PUT') ? true : false;  
  }

  /**
    * Returns the HTTP_REFERER
    * 
    * @param  string  $default Define a default URL if no referer has been found
    * @return string
    */  
  static function referer($default=null) {
    if(empty($default)) $default = '/';
    return server::get('http_referer', $default);
  }

}


/**
  * Shortcut for r::get()
  *
  * @param   mixed    $key The key to look for. Pass false or null to return the entire request array. 
  * @param   mixed    $default Optional default value, which should be returned if no element has been found
  * @return  mixed
  * @package Kirby
  */  
function get($key=false, $default=null) {
  return r::get($key, $default);
}







/**
 * 
 * Session
 * 
 * Handles all session fiddling
 * 
 * @package Kirby
 */
class s {

  /**
    * Returns the current session id
    * 
    * @return string
    */  
  static function id() {
    return @session_id();
  }

  /** 
    * Sets a session value by key
    *
    * @param  mixed   $key The key to define
    * @param  mixed   $value The value for the passed key
    */    
  static function set($key, $value=false) {
    if(!isset($_SESSION)) return false;
    if(is_array($key)) {
      $_SESSION = array_merge($_SESSION, $key);
    } else {
      $_SESSION[$key] = $value;
    }
  }

  /**
    * Gets a session value by key
    *
    * @param  mixed    $key The key to look for. Pass false or null to return the entire session array. 
    * @param  mixed    $default Optional default value, which should be returned if no element has been found
    * @return mixed
    */  
  static function get($key=false, $default=null) {
    if(!isset($_SESSION)) return false;
    if(empty($key)) return $_SESSION;
    return a::get($_SESSION, $key, $default);
  }

  /**
    * Removes a value from the session by key
    *
    * @param  mixed    $key The key to remove by
    * @return array    The session array without the value
    */  
  static function remove($key) {
    if(!isset($_SESSION)) return false;
    $_SESSION = a::remove($_SESSION, $key, true);
    return $_SESSION;
  }

  /**
    * Starts a new session
    *
    */  
  static function start() {
    @session_start();
  }

  /**
    * Destroys a session
    *
    */  
  static function destroy() {
    @session_destroy();
  }

  /**
    * Destroys a session first and then starts it again
    *
    */  
  static function restart() {
    self::destroy();
    self::start();
  }

}







/**
 * 
 * Server
 * 
 * Makes it more convenient to get variables
 * from the global server array
 * 
 * @package Kirby
 */
class server {

  /**
    * Gets a value from the _SERVER array
    *
    * @param  mixed    $key The key to look for. Pass false or null to return the entire server array. 
    * @param  mixed    $default Optional default value, which should be returned if no element has been found
    * @return mixed
    */  
  static function get($key=false, $default=null) {
    if(empty($key)) return $_SERVER;
    return a::get($_SERVER, str::upper($key), $default);
  }

}








/**
 * 
 * Size
 * 
 * Makes it easy to recalculate image dimensions
 *
 * @package Kirby
 */
class size {

  /**
    * Gets the ratio by width and height
    *
    * @param  int    $width
    * @param  int    $height
    * @return float
    */  
  static function ratio($width, $height) {
    return ($width / $height);
  }

  /**
    * Fits width and height into a defined box and keeps the ratio
    *
    * @param  int     $width
    * @param  int     $height
    * @param  int     $box
    * @param  boolean $force If width and height are smaller than the box this will force upscaling
    * @return array   An array with a key and value for width and height
    */  
  static function fit($width, $height, $box, $force=false) {

    if($width == 0 || $height == 0) return array('width' => $box, 'height' => $box);

    $ratio = self::ratio($width, $height);

    if($width > $height) {
      if($width > $box || $force == true) $width = $box;
      $height = floor($width / $ratio);
    } elseif($height > $width) {
      if($height > $box || $force == true) $height = $box;
      $width = floor($height * $ratio);
    } elseif($width > $box) {
      $width = $box;
      $height = $box;
    }

    $output = array();
    $output['width'] = $width;
    $output['height'] = $height;

    return $output;

  }

  /**
    * Fits width and height by a passed width and keeps the ratio
    *
    * @param  int     $width
    * @param  int     $height
    * @param  int     $fit The new width
    * @param  boolean $force If width and height are smaller than the box this will force upscaling
    * @return array   An array with a key and value for width and height
    */  
  static function fit_width($width, $height, $fit, $force=false) {
    if($width <= $fit && !$force) return array(
      'width' => $width,
      'height' => $height
    );
    $ratio = self::ratio($width, $height);
    return array(
      'width' => $fit,
      'height' => floor($fit / $ratio)
    );
  }

  /**
    * Fits width and height by a passed height and keeps the ratio
    *
    * @param  int     $width
    * @param  int     $height
    * @param  int     $fit The new height
    * @param  boolean $force If width and height are smaller than the box this will force upscaling
    * @return array   An array with a key and value for width and height
    */  
  static function fit_height($width, $height, $fit, $force=false) {
    if($height <= $fit && !$force) return array(
      'width' => $width,
      'height' => $height
    );
    $ratio = self::ratio($width, $height);
    return array(
      'width' => floor($fit * $ratio),
      'height' => $fit
    );

  }

}








/**
 * 
 * String
 * 
 * A set of handy string methods
 *
 * @package Kirby
 */
class str {

  /**
    * Converts a string to a html-safe string
    *
    * @param  string  $string
    * @param  boolean $keep_html True: lets stuff inside html tags untouched. 
    * @return string  The html string
    */  
  static function html($string, $keep_html=true) {
    if($keep_html) {
      return stripslashes(implode('', preg_replace('/^([^<].+[^>])$/e', "htmlentities('\\1', ENT_COMPAT, 'utf-8')", preg_split('/(<.+?>)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE))));
    } else {
      return htmlentities($string, ENT_COMPAT, 'utf-8');
    }
  }

  /**
    * Removes all html tags and encoded chars from a string
    *
    * @param  string  $string
    * @return string  The html string
    */  
  static function unhtml($string) {
    $string = strip_tags($string);
    return html_entity_decode($string, ENT_COMPAT, 'utf-8');
  }

  /**
    * An internal store for a html entities translation table
    *
    * @return array
    */  
  static function entities() {

    return array(
      '&nbsp;' => '&#160;', '&iexcl;' => '&#161;', '&cent;' => '&#162;', '&pound;' => '&#163;', '&curren;' => '&#164;', '&yen;' => '&#165;', '&brvbar;' => '&#166;', '&sect;' => '&#167;',
      '&uml;' => '&#168;', '&copy;' => '&#169;', '&ordf;' => '&#170;', '&laquo;' => '&#171;', '&not;' => '&#172;', '&shy;' => '&#173;', '&reg;' => '&#174;', '&macr;' => '&#175;',
      '&deg;' => '&#176;', '&plusmn;' => '&#177;', '&sup2;' => '&#178;', '&sup3;' => '&#179;', '&acute;' => '&#180;', '&micro;' => '&#181;', '&para;' => '&#182;', '&middot;' => '&#183;',
      '&cedil;' => '&#184;', '&sup1;' => '&#185;', '&ordm;' => '&#186;', '&raquo;' => '&#187;', '&frac14;' => '&#188;', '&frac12;' => '&#189;', '&frac34;' => '&#190;', '&iquest;' => '&#191;',
      '&Agrave;' => '&#192;', '&Aacute;' => '&#193;', '&Acirc;' => '&#194;', '&Atilde;' => '&#195;', '&Auml;' => '&#196;', '&Aring;' => '&#197;', '&AElig;' => '&#198;', '&Ccedil;' => '&#199;',
      '&Egrave;' => '&#200;', '&Eacute;' => '&#201;', '&Ecirc;' => '&#202;', '&Euml;' => '&#203;', '&Igrave;' => '&#204;', '&Iacute;' => '&#205;', '&Icirc;' => '&#206;', '&Iuml;' => '&#207;',
      '&ETH;' => '&#208;', '&Ntilde;' => '&#209;', '&Ograve;' => '&#210;', '&Oacute;' => '&#211;', '&Ocirc;' => '&#212;', '&Otilde;' => '&#213;', '&Ouml;' => '&#214;', '&times;' => '&#215;',
      '&Oslash;' => '&#216;', '&Ugrave;' => '&#217;', '&Uacute;' => '&#218;', '&Ucirc;' => '&#219;', '&Uuml;' => '&#220;', '&Yacute;' => '&#221;', '&THORN;' => '&#222;', '&szlig;' => '&#223;',
      '&agrave;' => '&#224;', '&aacute;' => '&#225;', '&acirc;' => '&#226;', '&atilde;' => '&#227;', '&auml;' => '&#228;', '&aring;' => '&#229;', '&aelig;' => '&#230;', '&ccedil;' => '&#231;',
      '&egrave;' => '&#232;', '&eacute;' => '&#233;', '&ecirc;' => '&#234;', '&euml;' => '&#235;', '&igrave;' => '&#236;', '&iacute;' => '&#237;', '&icirc;' => '&#238;', '&iuml;' => '&#239;',
      '&eth;' => '&#240;', '&ntilde;' => '&#241;', '&ograve;' => '&#242;', '&oacute;' => '&#243;', '&ocirc;' => '&#244;', '&otilde;' => '&#245;', '&ouml;' => '&#246;', '&divide;' => '&#247;',
      '&oslash;' => '&#248;', '&ugrave;' => '&#249;', '&uacute;' => '&#250;', '&ucirc;' => '&#251;', '&uuml;' => '&#252;', '&yacute;' => '&#253;', '&thorn;' => '&#254;', '&yuml;' => '&#255;',
      '&fnof;' => '&#402;', '&Alpha;' => '&#913;', '&Beta;' => '&#914;', '&Gamma;' => '&#915;', '&Delta;' => '&#916;', '&Epsilon;' => '&#917;', '&Zeta;' => '&#918;', '&Eta;' => '&#919;',
      '&Theta;' => '&#920;', '&Iota;' => '&#921;', '&Kappa;' => '&#922;', '&Lambda;' => '&#923;', '&Mu;' => '&#924;', '&Nu;' => '&#925;', '&Xi;' => '&#926;', '&Omicron;' => '&#927;',
      '&Pi;' => '&#928;', '&Rho;' => '&#929;', '&Sigma;' => '&#931;', '&Tau;' => '&#932;', '&Upsilon;' => '&#933;', '&Phi;' => '&#934;', '&Chi;' => '&#935;', '&Psi;' => '&#936;',
      '&Omega;' => '&#937;', '&alpha;' => '&#945;', '&beta;' => '&#946;', '&gamma;' => '&#947;', '&delta;' => '&#948;', '&epsilon;' => '&#949;', '&zeta;' => '&#950;', '&eta;' => '&#951;',
      '&theta;' => '&#952;', '&iota;' => '&#953;', '&kappa;' => '&#954;', '&lambda;' => '&#955;', '&mu;' => '&#956;', '&nu;' => '&#957;', '&xi;' => '&#958;', '&omicron;' => '&#959;',
      '&pi;' => '&#960;', '&rho;' => '&#961;', '&sigmaf;' => '&#962;', '&sigma;' => '&#963;', '&tau;' => '&#964;', '&upsilon;' => '&#965;', '&phi;' => '&#966;', '&chi;' => '&#967;',
      '&psi;' => '&#968;', '&omega;' => '&#969;', '&thetasym;' => '&#977;', '&upsih;' => '&#978;', '&piv;' => '&#982;', '&bull;' => '&#8226;', '&hellip;' => '&#8230;', '&prime;' => '&#8242;',
      '&Prime;' => '&#8243;', '&oline;' => '&#8254;', '&frasl;' => '&#8260;', '&weierp;' => '&#8472;', '&image;' => '&#8465;', '&real;' => '&#8476;', '&trade;' => '&#8482;', '&alefsym;' => '&#8501;',
      '&larr;' => '&#8592;', '&uarr;' => '&#8593;', '&rarr;' => '&#8594;', '&darr;' => '&#8595;', '&harr;' => '&#8596;', '&crarr;' => '&#8629;', '&lArr;' => '&#8656;', '&uArr;' => '&#8657;',
      '&rArr;' => '&#8658;', '&dArr;' => '&#8659;', '&hArr;' => '&#8660;', '&forall;' => '&#8704;', '&part;' => '&#8706;', '&exist;' => '&#8707;', '&empty;' => '&#8709;', '&nabla;' => '&#8711;',
      '&isin;' => '&#8712;', '&notin;' => '&#8713;', '&ni;' => '&#8715;', '&prod;' => '&#8719;', '&sum;' => '&#8721;', '&minus;' => '&#8722;', '&lowast;' => '&#8727;', '&radic;' => '&#8730;',
      '&prop;' => '&#8733;', '&infin;' => '&#8734;', '&ang;' => '&#8736;', '&and;' => '&#8743;', '&or;' => '&#8744;', '&cap;' => '&#8745;', '&cup;' => '&#8746;', '&int;' => '&#8747;',
      '&there4;' => '&#8756;', '&sim;' => '&#8764;', '&cong;' => '&#8773;', '&asymp;' => '&#8776;', '&ne;' => '&#8800;', '&equiv;' => '&#8801;', '&le;' => '&#8804;', '&ge;' => '&#8805;',
      '&sub;' => '&#8834;', '&sup;' => '&#8835;', '&nsub;' => '&#8836;', '&sube;' => '&#8838;', '&supe;' => '&#8839;', '&oplus;' => '&#8853;', '&otimes;' => '&#8855;', '&perp;' => '&#8869;',
      '&sdot;' => '&#8901;', '&lceil;' => '&#8968;', '&rceil;' => '&#8969;', '&lfloor;' => '&#8970;', '&rfloor;' => '&#8971;', '&lang;' => '&#9001;', '&rang;' => '&#9002;', '&loz;' => '&#9674;',
      '&spades;' => '&#9824;', '&clubs;' => '&#9827;', '&hearts;' => '&#9829;', '&diams;' => '&#9830;', '&quot;' => '&#34;', '&amp;' => '&#38;', '&lt;' => '&#60;', '&gt;' => '&#62;', '&OElig;' => '&#338;',
      '&oelig;' => '&#339;', '&Scaron;' => '&#352;', '&scaron;' => '&#353;', '&Yuml;' => '&#376;', '&circ;' => '&#710;', '&tilde;' => '&#732;', '&ensp;' => '&#8194;', '&emsp;' => '&#8195;',
      '&thinsp;' => '&#8201;', '&zwnj;' => '&#8204;', '&zwj;' => '&#8205;', '&lrm;' => '&#8206;', '&rlm;' => '&#8207;', '&ndash;' => '&#8211;', '&mdash;' => '&#8212;', '&lsquo;' => '&#8216;',
      '&rsquo;' => '&#8217;', '&sbquo;' => '&#8218;', '&ldquo;' => '&#8220;', '&rdquo;' => '&#8221;', '&bdquo;' => '&#8222;', '&dagger;' => '&#8224;', '&Dagger;' => '&#8225;', '&permil;' => '&#8240;',
      '&lsaquo;' => '&#8249;', '&rsaquo;' => '&#8250;', '&euro;' => '&#8364;'
    );

  }

  /**
    * Converts a string to a xml-safe string
    * Converts it to html-safe first and then it
    * will replace html entities to xml entities
    *
    * @param  string  $text
    * @param  boolean $html True: convert to html first
    * @return string
    */  
  static function xml($text, $html=true) {

    // convert raw text to html safe text
    if($html) $text = self::html($text);

    // convert html entities to xml entities
    return strtr($text, self::entities());

  }

  /**
    * Removes all xml entities from a string
    * and convert them to html entities first
    * and remove all html entities afterwards.
    *
    * @param  string  $string
    * @return string
    */  
  static function unxml($string) {

    // flip the conversion table
    $table = array_flip(self::entities());

    // convert xml entities to html entities
    $string = strtr($string, $table);
    return str::unhtml($string);

  }

  /**
    * Parses a string by a set of available methods
    *
    * Available methods:
    * - json
    * - xml
    * - url
    * - query
    * - php
    *
    * @param  string  $string
    * @param  string  $mode
    * @return string
    */  
  static function parse($string, $mode='json') {

    if(is_array($string)) return $string;

    switch($mode) {
      case 'json':
        $result = (array)@json_decode($string, true);
        break;
      case 'xml':
        $result = x::parse($string);
        break;
      case 'url':
        $result = (array)@parse_url($string);
        break;
      case 'query':
        if(url::has_query($string)) {
          $string = self::split($string, '?');
          $string = a::last($string);
        }
        @parse_str($string, $result);
        break;
      case 'php':
        $result = @unserialize($string);
        break;
      default:
        $result = $string;
        break;
    }

    return $result;

  }

  /**
    * Encode a string (used for email addresses)
    *
    * @param  string  $string
    * @return string
    */  
  static function encode($string) {
    $decoded = utf8_decode($string);
    $encoded = '';
    $length = str::length($string);
    for($i=0; $i<$length; $i++) {
      if($decoded[$i] === $string[$i]) {
        $encoded .= (rand(1,2)==1) ? '&#'.ord($string[$i]).';' : '&#x'.dechex(ord($string[$i])).';';
      } else {
        $encoded .= (rand(1,2)==1) ? '&#'.ord($decoded[$i]).';' : '&#x'.dechex(ord($decoded[$i])).';';
      }
    }
    return $encoded;
  }
  
  /**
    * Creates an encoded email address, including proper html-tags
    *
    * @param  string  $email The email address
    * @param  string  $text Specify a text for the email link. If false the email address will be used
    * @param  string  $title An optional title for the html tag. 
    * @param  string  $class An optional class name for the html tag. 
    * @return string  
    */  
  static function email($email, $text=false, $title=false, $class=false) {
    if(empty($email)) return false;
    $email  = (string)$email;
    $string = (empty($text)) ? $email : $text;
    $email  = self::encode($email, 3);
    
    if(!empty($class)) $class = ' class="' . $class . '"';
    if(!empty($title)) $title = ' title="' . html($title) . '"';
    
    return '<a' . $title . $class . ' href="mailto:' . $email . '">' . self::encode($string, 3) . '</a>';
  }

  /**
    * Creates a link tag
    *
    * @param  string  $link The URL
    * @param  string  $text Specify a text for the link tag. If false the URL will be used
    * @return string  
    */  
  static function link($link, $text=false) {
    $text = ($text) ? $text : $link;
    return '<a href="' . $link . '">' . str::html($text) . '</a>';
  }

  /**
    * Shortens a string and adds an ellipsis if the string is too long
    *
    * @param  string  $string The string to be shortened
    * @param  int     $chars The final number of characters the string should have
    * @param  string  $rep The element, which should be added if the string is too long. Ellipsis is the default.
    * @return string  The shortened string  
    */  
  static function short($string, $chars, $rep='…') {
    if($chars == 0) return $string;
    if(str::length($string) <= $chars) return $string;
    $string = self::substr($string,0,($chars-str::length($rep)));
    $punctuation = '.!?:;,-';
    $string = (strspn(strrev($string), $punctuation)!=0) ? substr($string, 0, -strspn(strrev($string),  $punctuation)) : $string;
    return $string . $rep;
  }

  /**
    * Shortens an URL
    * It removes http:// or https:// and uses str::short afterwards
    *
    * @param  string  $url The URL to be shortened
    * @param  int     $chars The final number of characters the URL should have
    * @param  boolean $base True: only take the base of the URL. 
    * @param  string  $rep The element, which should be added if the string is too long. Ellipsis is the default.
    * @return string  The shortened URL  
    */  
  static function shorturl($url, $chars=false, $base=false, $rep='…') {
    return url::short($url, $chars, $base, $rep);
  }

  /** 
    * Creates an exceprt of a string
    * It removes all html tags first and then uses str::short
    *
    * @param  string  $string The string to be shortened
    * @param  int     $chars The final number of characters the string should have
    * @param  boolean $removehtml True: remove the HTML tags from the string first 
    * @param  string  $rep The element, which should be added if the string is too long. Ellipsis is the default.
    * @return string  The shortened string  
    */
  static function excerpt($string, $chars=140, $removehtml=true, $rep='…') {
    if($removehtml) $string = strip_tags($string);
    $string = str::trim($string);    
    $string = str_replace("\n", ' ', $string);
    if(str::length($string) <= $chars) return $string;
    return ($chars==0) ? $string : substr($string, 0, strrpos(substr($string, 0, $chars), ' ')) . $rep;
  }

  /** 
    * Shortens a string by cutting out chars in the middle
    * This method mimicks the shortening which is used for filenames in the Finder
    *
    * @param  string  $string The string to be shortened
    * @param  int     $length The final number of characters the string should have
    * @param  string  $rep The element, which should be added if the string is too long. Ellipsis is the default.
    * @return string  The shortened string  
    */
  static function cutout($str, $length, $rep='…') {

    $strlength = str::length($str);
    if($length >= $strlength) return $str;

    // calc the how much we have to cut off
    $cut  = (($strlength+str::length($rep)) - $length);

    // divide it to cut left and right from the center
    $cutp = round($cut/2);

    // get the center of the string
    $strcenter = round($strlength/2);

    // get the start of the cut
    $strlcenter = ($strcenter-$cutp);

    // get the end of the cut
    $strrcenter = ($strcenter+$cutp);

    // cut and glue
    return str::substr($str, 0, $strlcenter) . $rep . str::substr($str, $strrcenter);

  }

  /** 
    * Adds an apostrohpe to a string/name if applicable
    *
    * @param  string  $name The string to be shortened
    * @return string  The string + apostrophe
    */
  static function apostrophe($name) {
    return (substr($name,-1,1) == 's' || substr($name,-1,1) == 'z') ? $name .= "'" : $name .= "'s";
  }

  /** 
    * A switch to display either one or the other string dependend on a counter
    * 
    * @param  int     $count The counter
    * @param  string  $many The string to be displayed for a counter > 1
    * @param  string  $one The string to be displayed for a counter == 1
    * @param  string  $zero The string to be displayed for a counter == 0
    * @return string  The string
    */
  static function plural($count, $many, $one, $zero = '') {
    if($count == 1) return $one;
    else if($count == 0 && !empty($zero)) return $zero;
    else return $many;
  }

  /** 
    * An UTF-8 safe version of substr()
    * 
    * @param  string  $str
    * @param  int     $start
    * @param  int     $end 
    * @return string  
    */
  static function substr($str, $start, $end = null) {
    return mb_substr($str, $start, ($end == null) ? mb_strlen($str, 'UTF-8') : $end, 'UTF-8');
  }

  /** 
    * An UTF-8 safe version of strtolower()
    * 
    * @param  string  $str
    * @return string  
    */
  static function lower($str) {
    return mb_strtolower($str, 'UTF-8');
  }

  /** 
    * An UTF-8 safe version of strotoupper()
    * 
    * @param  string  $str
    * @return string  
    */
  static function upper($str) {
    return mb_strtoupper($str, 'UTF-8');
  }

  /** 
    * An UTF-8 safe version of strlen()
    * 
    * @param  string  $str
    * @return string  
    */
  static function length($str) {
    return mb_strlen($str, 'UTF-8');
  }

  /** 
    * Checks if a str contains another string
    * 
    * @param  string  $str
    * @param  string  $needle
    * @param  boolean $i ignore upper/lowercase
    * @return string  
    */
  static function contains($str, $needle, $i=true) {
    if($i) {
      $str    = str::lower($str);
      $needle = str::lower($needle);
    }
    return (strstr($str, $needle)) ? true : false;
  }

  /** 
    * preg_match sucks! This tries to make it more convenient
    * 
    * @param  string  $string
    * @param  string  $preg Regular expression
    * @param  string  $get Which part should be returned from the result array
    * @param  string  $placeholder Default value if nothing will be found
    * @return mixed  
    */
  static function match($string, $preg, $get=false, $placeholder=false) {
    $match = @preg_match($preg, $string, $array);
    if(!$match) return false;
    if($get === false) return $array;
    return a::get($array, $get, $placeholder);
  }

  /** 
    * Generates a random string
    * 
    * @param  int  $length The length of the random string
    * @return string  
    */
  static function random($length=false) {
    $length = ($length) ? $length : rand(5,10);
    $chars  = range('a','z');
    $num  = range(0,9);
    $pool  = array_merge($chars, $num);
    $string = '';
    for($x=0; $x<$length; $x++) {
      shuffle($pool);
      $string .= current($pool);
    }
    return $string;
  }

  /** 
    * Convert a string to a safe version to be used in an URL
    * 
    * @param  string  $text The unsafe string
    * @return string  The safe string
    */
  static function urlify($text) {
    $text = trim($text);
    $text = str::lower($text);
    $text = str_replace('ä', 'ae', $text);
    $text = str_replace('ö', 'oe', $text);
    $text = str_replace('ü', 'ue', $text);
    $text = str_replace('ß', 'ss', $text);
    $text = preg_replace("![^a-z0-9]!i","-", $text);
    $text = preg_replace("![-]{2,}!","-", $text);
    $text = preg_replace("!-$!","", $text);
    return $text;
  }

  /** 
    * Better alternative for explode()
    * It takes care of removing empty values
    * and it has a built-in way to skip values
    * which are too short. 
    * 
    * @param  string  $string The string to split
    * @param  string  $separator The string to split by
    * @param  int     $length The min length of values. 
    * @return array   An array of found values
    */
  static function split($string, $separator=',', $length=1) {

    if(is_array($string)) return $string;

    $string = trim($string, $separator);
    $parts  = explode($separator, $string);
    $out    = array();

    foreach($parts AS $p) {
      $p = trim($p);
      if(str::length($p) > 0 && str::length($p) >= $length) $out[] = $p;
    }

    return $out;

  }

  /** 
    * A more brutal way to trim. 
    * It removes double spaces. 
    * Can be useful in some cases but 
    * be careful as it might remove too much. 
    * 
    * @param  string  $string The string to trim
    * @return string  The trimmed string
    */
  static function trim($string) {
    $string = preg_replace('/\s\s+/u', ' ', $string);
    return trim($string);
  }

  /** 
    * A set of sanitizer methods
    * 
    * @param  string  $string The string to sanitize
    * @param  string  $type The method
    * @param  string  $default The default value if the string will be empty afterwards
    * @return string  The sanitized string
    */
  static function sanitize($string, $type='str', $default=null) {

    $string = stripslashes((string)$string);
    $string = urldecode($string);
    $string = str::utf8($string);

    switch($type) {
      case 'int':
        $string = (int)$string;
        break;
      case 'str':
        $string = (string)$string;
        break;
      case 'array':
        $string = (array)$string;
        break;
      case 'nohtml':
        $string = self::unhtml($string);
        break;
      case 'noxml':
        $string = self::unxml($string);
        break;
      case 'enum':
        $string = (in_array($string, array('y', 'n'))) ? $string : $default;
        $string = (in_array($string, array('y', 'n'))) ? $string : 'n';
        break;
      case 'checkbox':
        $string = ($string == 'on') ? 'y' : 'n';
        break;
      case 'url':
        $string = (v::url($string)) ? $string : '';
        break;
      case 'email':
        $string = (v::email($string)) ? $string : '';
        break;
      case 'plain':
        $string = str::unxml($string);
        $string = str::unhtml($string);
        $string = str::trim($string);
        break;
      case 'lower':
        $string = str::lower($string);
        break;
      case 'upper':
        $string = str::upper($string);
        break;
      case 'words':
        $string = str::sanitize($string, 'plain');
        $string = preg_replace('/[^\pL]/u', ' ', $string);
      case 'tags':
        $string = str::sanitize($string, 'plain');
        $string = preg_replace('/[^\pL\pN]/u', ' ', $string);
        $string = str::trim($string);
      case 'nobreaks':
        $string = str_replace('\n','',$string);
        $string = str_replace('\r','',$string);
        $string = str_replace('\t','',$string);
        break;
      case 'url':
        $string = self::urlify($string);
        break;
      case 'filename':
        $string = f::save_name($string);
        break;
    }

    return trim($string);

  }

  /** 
    * An UTF-8 safe version of ucwords()
    * 
    * @param  string  $string 
    * @return string 
    */
  static function ucwords($str) {
    return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
  }

  /** 
    * An UTF-8 safe version of ucfirst()
    * 
    * @param  string  $string 
    * @return string 
    */
  static function ucfirst($str) {
    return str::upper(str::substr($str, 0, 1)) . str::substr($str, 1);
  }

  /** 
    * Converts a string to UTF-8
    * 
    * @param  string  $string 
    * @return string 
    */
  static function utf8($string) {
    $encoding = mb_detect_encoding($string,'UTF-8, ISO-8859-1, GBK');
    return ($encoding != 'UTF-8') ? iconv($encoding,'utf-8',$string) : $string;
  }

  /** 
    * A better way to strip slashes
    * 
    * @param  string  $string 
    * @return string 
    */
  static function stripslashes($string) {
    if(is_array($string)) return $string;
    return (get_magic_quotes_gpc()) ? stripslashes(stripslashes($string)) : $string;
  }

}








/**
 * 
 * URL
 * 
 * A bunch of handy methods to work with URLs
 * 
 * @package Kirby
 */
class url {
  
  /** 
    * Returns the current URL
    * 
    * @return string
    */
  static function current() {
    return 'http://' . server::get('http_host') . server::get('request_uri');
  }

  /**
    * Shortens an URL
    * It removes http:// or https:// and uses str::short afterwards
    *
    * @param  string  $url The URL to be shortened
    * @param  int     $chars The final number of characters the URL should have
    * @param  boolean $base True: only take the base of the URL. 
    * @param  string  $rep The element, which should be added if the string is too long. Ellipsis is the default.
    * @return string  The shortened URL  
    */  
  static function short($url, $chars=false, $base=false, $rep='…') {
    $url = str_replace('http://','',$url);
    $url = str_replace('https://','',$url);
    $url = str_replace('ftp://','',$url);
    $url = str_replace('www.','',$url);
    if($base) {
      $a = explode('/', $url);
      $url = a::get($a, 0);
    }
    return ($chars) ? str::short($url, $chars, $rep) : $url;
  }

  /** 
    * Checks if the URL has a query string attached
    * 
    * @param  string  $url
    * @return boolean
    */
  static function has_query($url) {
    return (str::contains($url, '?')) ? true : false;
  }

  /** 
    * Strips the query from the URL
    * 
    * @param  string  $url
    * @return string
    */
  static function strip_query($url) {
    return preg_replace('/\?.*$/is', '', $url);
  }

  /** 
    * Strips a hash value from the URL
    * 
    * @param  string  $url
    * @return string
    */
  static function strip_hash($url) {
    return preg_replace('/#.*$/is', '', $url);
  }

  /** 
    * Checks for a valid URL
    * 
    * @param  string  $url
    * @return boolean
    */
  static function valid($url) {
    return v::url($url);
  }

}







/**
 * 
 * Validator
 * 
 * Makes input validation easier
 * 
 * @package Kirby
 */
class v {

  /** 
    * Core method to create a new validator
    * 
    * @param  string  $string
    * @param  array   $options
    * @return boolean
    */
  static function string($string, $options) {
    $format = null;
    $min_length = $max_length = 0;
    if(is_array($options)) extract($options);

    if($format && !preg_match('/^[' . $format . ']*$/is', $string)) return false;
    if($min_length && str::length($string) < $min_length)   return false;
    if($max_length && str::length($string) > $max_length)  return false;
    return true;
  }

  /** 
    * Checks for a valid password
    * 
    * @param  string  $password
    * @return boolean
    */
  static function password($password) {
    return self::string($password, array('min_length' => 4));
  }

  /** 
    * Checks for two valid, matching password
    * 
    * @param  string  $password1
    * @param  string  $password2
    * @return boolean
    */
  static function passwords($password1, $password2) {

    if($password1 == $password2
      && self::password($password1)
      && self::password($password2)) {
      return true;
    } else {
      return false;
    }

  }

  /** 
    * Checks for valid date
    * 
    * @param  string  $date
    * @return boolean
    */
  static function date($date) {
    $time = strtotime($date);
    if(!$time) return false;

    $year = date('Y', $time);
    $month = date('m', $time);
    $day   = date('d', $time);

    return (checkdate($month, $day, $year)) ? $time : false;

  }

  /** 
    * Checks for valid email address
    * 
    * @param  string  $email
    * @return boolean
    */
  static function email($email) {
    $regex = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';
    return (preg_match($regex, $email)) ? true : false;
  }

  /** 
    * Checks for valid URL
    * 
    * @param  string  $url
    * @return boolean
    */
  static function url($url) {
    $regex = '/^(https?|ftp|rmtp|mms|svn):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i';
    return (preg_match($regex, $url)) ? true : false;
  }

  /** 
    * Checks for valid filename
    * 
    * @param  string  $string
    * @return boolean
    */
  static function filename($string) {

    $options = array(
      'format' => 'a-zA-Z0-9_-',
      'min_length' => 2,
    );

    return self::string($string, $options);

  }

}







/**
 * 
 * XML
 * 
 * The Kirby XML Parser Class
 * 
 * @package Kirby
 */
class x {

  /** 
    * Parses a XML string and returns an array
    * 
    * @param  string  $xml
    * @return mixed
    */
  static function parse($xml) {

    $xml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$2$3', $xml);
    $xml = @simplexml_load_string($xml, null, LIBXML_NOENT);
    $xml = @json_encode($xml);
    $xml = @json_decode($xml, true);
    return (is_array($xml)) ? $xml : false;

  }

}
