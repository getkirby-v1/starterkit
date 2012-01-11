<?php

c::set('version', 0.923);
c::set('language', 'en');
c::set('charset', 'utf-8');
c::set('root', dirname(__FILE__));

/*

############### HELPER ###############

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

function status($response) {
  return a::get($response, 'status');
}

function msg($response) {
  return a::get($response, 'msg');
}

function error($response) {
  return (status($response) == 'error') ? true : false;
}

function success($response) {
  return !error($response);
}

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




/*

############### ARRAY ###############

*/
class a {

  static function get($array, $key, $default=null) {
    return (isset($array[ $key ])) ? $array[ $key ] : $default;
  }

  static function getall($array, $keys) {
    $result = array();
    foreach($keys as $key) $result[$key] = $array[$key];
    return $result;
  }

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

  static function inject($array, $position, $element='placeholder') {
    $start = array_slice($array, 0, $position);
    $end = array_slice($array, $position);
    return array_merge($start, (array)$element, $end);
  }

  static function show($array, $echo=true) {
    $output = '<pre>';
    $output .= htmlspecialchars(print_r($array, true));
    $output .= '</pre>';
    if($echo==true) echo $output;
    return $output;
  }

  static function json($array) {
    return @json_encode( (array)$array );
  }

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

  static function extract($array, $key) {
    $output = array();
    foreach($array AS $a) if(isset($a[$key])) $output[] = $a[ $key ];
    return $output;
  }

  static function shuffle($array) {
    $keys = array_keys($array); 
    shuffle($keys); 
    return array_merge(array_flip($keys), $array); 
  } 

  static function first($array) {
    return array_shift($array);
  }

  static function last($array) {
    return array_pop($array);
  }

  static function search($array, $search) {
    return preg_grep('#' . preg_quote($search) . '#i' , $array);
  }

  static function contains($array, $search) {
    $search = self::search($array, $search);
    return (empty($search)) ? false : true;
  }

  static function fill($array, $limit, $fill='placeholder') {
    if(count($array) < $limit) {
      $diff = $limit-count($array);
      for($x=0; $x<$diff; $x++) $array[] = $fill;
    }
    return $array;
  }

  static function missing($array, $required=array()) {
    $missing = array();
    foreach($required AS $r) {
      if(empty($array[$r])) $missing[] = $r;
    }
    return $missing;
  }

  static function sort() {

    $options = func_get_args();
    $array   = array_shift($options);    
    $cols    = array();
    
    foreach($options as $option) {
      $args  = str::split($option, ' ');
      $field = $args[0];
      $dir   = (@$args[1] == 'desc') ? SORT_DESC : SORT_ASC;
      $cols[ $field ] = array($dir, SORT_REGULAR);
    }
    
    $helper = array();
    
    // build a helper array for each field    
    foreach($cols as $col => $order) {
      $helper[$col] = array();
      foreach($array as $k => $row) { 
        $helper[$col]['_'.$k] = (is_object($row)) ? str::lower($row->$col) : $row[$col]; 
      }
    }

    $params = array();
    foreach($cols as $col => $order) {
      $params[] = &$helper[$col];
      $params = array_merge($params, (array)$order);
    }
    
    // sort the params array
    @call_user_func_array('array_multisort', $params);

    $result = array();
    $keys   = array();
    $first  = true;

    foreach($helper as $col => $arr) {
      foreach($arr as $k => $v) {
        if($first) $keys[$k] = substr($k,1);
        $k = $keys[$k];
        if(!isset($result[$k])) $result[$k] = $array[$k];
        
        if(is_object($result[$k])) {
          $result[$k]->$col = $array[$k]->$col;
        } else {
          $result[$k][$col] = $array[$k][$col];        
        }
        
      }
      $first = false;
    }

    return $result;
  
  }

}







/*

############### BROWSER ###############

*/
class browser {

  static public $ua = false;
  static public $name = false;
  static public $engine = false;
  static public $version = false;
  static public $platform = false;
  static public $mobile = false;
  static public $ios = false;
  static public $iphone = false;

  static function name($ua=null) {
    self::detect($ua);
    return self::$name;
  }

  static function engine($ua=null) {
    self::detect($ua);
    return self::$engine;
  }

  static function version($ua=null) {
    self::detect($ua);
    return self::$version;
  }

  static function platform($ua=null) {
    self::detect($ua);
    return self::$platform;
  }

  static function mobile($ua=null) {
    self::detect($ua);
    return self::$mobile;
  }

  static function iphone($ua=null) {
    self::detect($ua);
    return self::$iphone;
  }

  static function ios($ua=null) {
    self::detect($ua);
    return self::$ios;
  }

  static function css($ua=null, $array=false) {
    self::detect($ua);
    $css[] = self::$engine;
    $css[] = self::$name;
    if(self::$version) $css[] = self::$name . str_replace('.', '_', self::$version);
    $css[] = self::$platform;
    return ($array) ? $css : implode(' ', $css);
  }

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







/*

############### CONFIG ###############

*/
class c {

  private static $config = array();

  static function get($key=null, $default=null) {
    if(empty($key)) return self::$config;
    return a::get(self::$config, $key, $default);
  }

  static function set($key, $value=null) {
    if(is_array($key)) {
      // set all new values
      self::$config = array_merge(self::$config, $key);
    } else {
      self::$config[$key] = $value;
    }
  }

  static function load($file) {
    if(file_exists($file)) require_once($file);
    return c::get();
  }

  static function get_array($key, $default=null) {
    $keys = array_keys(self::$config);
    $n = array();
    foreach($keys AS $k) {
      $pos = strpos($key.'.', $k);
      if ($pos === 0) {
        $n[substr($k,strlen($key.'.'))] = self::$config[$k];
      }
    }
    return ($n) ? $n : $default;
  }
  
  static function set_array($key, $value=null) {
    if (!is_array($value)) {
      $m = self::get_sub($key);
      foreach($m AS $k => $v) {
        self::set($k, $value);
      }
    } else {
      foreach($value AS $k => $v) {
        self::set($key.'.'.$k, $v);
      }
    }
  }
}






/*

############### CONTENT ###############

*/
class content {

  static function start() {
    ob_start();
  }

  static function end($return=false) {
    if($return) {
      $content = ob_get_contents();
      ob_end_clean();
      return $content;
    }
    ob_end_flush();
  }

  static function load($file, $return=true) {
    self::start();
    require_once($file);
    $content = self::end(true);
    if($return) return $content;
    echo $content;        
  }

  static function type() {
    $args    = func_get_args();

    // shortcuts for content types
    $ctypes = array(
      'html' => 'text/html',
      'css'  => 'text/css',
      'js'   => 'text/javascript',
      'jpg'  => 'image/jpeg',
      'png'  => 'image/png',
      'gif'  => 'image/gif'
    );

    $ctype = a::get($args, 0, c::get('content_type', 'text/html'));
    $ctype = a::get($ctypes, $ctype, $ctype);

    $charset = a::get($args, 1, c::get('charset', 'utf-8'));
    header('Content-type: ' . $ctype . '; charset=' . $charset);
  }

}







/*

############### COOKIE ###############

*/
class cookie {

  static function set($key, $value, $expires=3600, $domain='/') {
    if(is_array($value)) $value = a::json($value);
    $_COOKIE[$key] = $value;
    return @setcookie($key, $value, time()+$expires, $domain);
  }

  static function get($key, $default=null) {
    return a::get($_COOKIE, $key, $default);
  }

  static function remove($key, $domain='/') {
    $_COOKIE[$key] = false;
    return @setcookie($key, false, time()-3600, $domain);
  }

}






/*

############### DATABASE ###############

*/
class db {

  public  static $trace = array();
  private static $connection = false;
  private static $database = false;
  private static $charset = false;
  private static $last_query = false;
  private static $affected = 0;

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

  static function connection() {
    return (is_resource(self::$connection)) ? self::$connection : false;
  }

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

  static function affected() {
      return self::$affected;
  }

  static function last_id() {
    $connection = self::connection();
    return @mysql_insert_id($connection);
  }

  static function fetch($result, $type=MYSQL_ASSOC) {
    if(!$result) return array();
    return @mysql_fetch_array($result, $type);
  }

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

  static function insert($table, $input, $ignore=false) {
    $ignore = ($ignore) ? ' IGNORE' : '';
    return self::execute('INSERT' . ($ignore) . ' INTO ' . self::prefix($table) . ' SET ' . self::values($input));
  }

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

  static function replace($table, $input) {
    return self::execute('REPLACE INTO ' . self::prefix($table) . ' SET ' . self::values($input));
  }

  static function update($table, $input, $where) {
    return self::execute('UPDATE ' . self::prefix($table) . ' SET ' . self::values($input) . ' WHERE ' . self::where($where));
  }

  static function delete($table, $where='') {
    $sql = 'DELETE FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);
    return self::execute($sql);
  }

  static function select($table, $select='*', $where=null, $order=null, $page=null, $limit=null, $fetch=true) {

    if($limit === 0) return array();

    if(is_array($select)) $select = self::select_clause($select);

    $sql = 'SELECT ' . $select . ' FROM ' . self::prefix($table);

    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);
    if(!empty($order)) $sql .= ' ORDER BY ' . $order;
    if($page !== null && $limit !== null) $sql .= ' LIMIT ' . $page . ',' . $limit;

    return self::query($sql, $fetch);

  }

  static function row($table, $select='*', $where=null, $order=null) {
    $result = self::select($table, $select, $where, $order, 0,1, false);
    return self::fetch($result);
  }

  static function column($table, $column, $where=null, $order=null, $page=null, $limit=null) {

    $result = self::select($table, $column, $where, $order, $page, $limit, false);

    $array = array();
    while($r = self::fetch($result)) array_push($array, a::get($r, $column));
    return $array;
  }

  static function field($table, $field, $where=null, $order=null) {
    $result = self::row($table, $field, $where, $order);
    return a::get($result, $field);
  }

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

  static function left_join($table_1, $table_2, $on, $select, $where=null, $order=null, $page=null, $limit=null) {
      return self::join($table_1, $table_2, $on, $select, $where, $order, $page, $limit, 'LEFT JOIN');
  }

  static function count($table, $where='') {
    $result = self::row($table, 'count(*)', $where);
    return ($result) ? a::get($result, 'count(*)') : 0;
  }

  static function min($table, $column, $where=null) {

    $sql = 'SELECT MIN(' . $column . ') AS min FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

    $result = self::query($sql, false);
    $result = self::fetch($result);

    return a::get($result, 'min', 1);

  }

  static function max($table, $column, $where=null) {

    $sql = 'SELECT MAX(' . $column . ') AS max FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

    $result = self::query($sql, false);
    $result = self::fetch($result);

    return a::get($result, 'max', 1);

  }

  static function sum($table, $column, $where=null) {

    $sql = 'SELECT SUM(' . $column . ') AS sum FROM ' . self::prefix($table);
    if(!empty($where)) $sql .= ' WHERE ' . self::where($where);

    $result = self::query($sql, false);
    $result = self::fetch($result);

    return a::get($result, 'sum', 0);

  }

  static function prefix($table) {
    $prefix = c::get('db.prefix');
    if(!$prefix) return $table;
    return (!str::contains($table,$prefix)) ? $prefix . $table : $table;
  }

  static function simple_fields($array) {
    if(empty($array)) return false;
    $output = array();
    foreach($array AS $key => $value) {
      $key = substr($key, strpos($key, '_')+1);
      $output[$key] = $value;
    }
    return $output;
  }

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

  static function escape($value) {
    $value = str::stripslashes($value);
    return mysql_real_escape_string((string)$value, self::connect());
  }

  static function search_clause($search, $fields, $mode='OR') {

    if(empty($search)) return false;

    $arr = array();
    foreach($fields AS $f) {
      array_push($arr, $f . ' LIKE \'%' . $search . '%\'');
      //array_push($arr, $f . ' REGEXP "[[:<:]]' . db::escape($search) . '[[:>:]]"');
    }
    return '(' . implode(' ' . trim($mode) . ' ', $arr) . ')';

  }
  
  static function with($field, $char) {
    return 'LOWER(SUBSTRING(' . $field . ',1,1)) = "' . db::escape($char) . '"';
  }

  static function select_clause($fields) {
    return implode(', ', $fields);
  }

  static function in($array) {
    return '\'' . implode('\',\'', $array) . '\'';
  }

  static function where($array, $method='AND') {

    if(!is_array($array)) return $array;

    $output = array();
    foreach($array AS $field => $value) {
      $output[] = $field . ' = \'' . self::escape($value) . '\'';
      $separator = ' ' . $method . ' ';
    }
    return implode(' ' . $method . ' ', $output);

  }

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





/*

############### DIR ###############

*/
class dir {

  static function make($dir) {
    if(is_dir($dir)) return true;
    if(!@mkdir($dir, 0777)) return false;
    @chmod($dir, 0777);
    return true;
  }

  static function read($dir) {
    if(!is_dir($dir)) return false;
    $skip = array('.', '..', '.DS_Store');
    return array_diff(scandir($dir),$skip);
  }
  
  static function inspect($dir) {
    
    if(!is_dir($dir)) return array();

    $files    = dir::read($dir);
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

  static function move($old, $new) {
    if(!is_dir($old)) return false;
    return (@rename($old, $new) && is_dir($new)) ? true : false;
  }

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

  static function clean($dir) {
    return self::remove($dir, true);
  }

  static function size($path, $recusive=true, $nice=false) {
    if(!file_exists($path)) return false;
    if(is_file($path)) return self::size($path, $nice);
    $size = 0;
    foreach(glob($path."/*") AS $file) {
      if($file != "." && $file != "..") {
        if($recusive) {
          $size += self::folder_size($file, true);
        } else {
          $size += self::size($path);
        }
      }
    }
    return ($nice) ? self::nice_size($size) : $size;
  }

  static function modified($dir, $modified=0) {
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






/*

############### FILE ###############

*/

class f {

  static function write($file,$content,$append=false){
    if(is_array($content)) $content = a::json($content);
    $mode = ($append) ? FILE_APPEND : false;
    $write = @file_put_contents($file, $content, $mode);
    @chmod($file, 0777);
    return $write;
  }

  static function append($file,$content){
    return self::write($file,$content,true);
  }
  
  static function read($file, $parse=false) {
    $content = @file_get_contents($file);
    return ($parse) ? str::parse($content, $parse) : $content;
  }

  static function move($old, $new) {
    if(!file_exists($old)) return false;
    return (@rename($old, $new) && file_exists($new)) ? true : false;
  }

  static function remove($file) {
    return (file_exists($file) && is_file($file) && !empty($file)) ? @unlink($file) : false;
  }

  static function extension($filename) {
    $ext = str_replace('.', '', strtolower(strrchr(trim($filename), '.')));
    return url::strip_query($ext);
  }

  static function filename($name) {
    return basename($name);
  }

  static function name($name, $remove_path = false) {
    if($remove_path == true) $name = self::filename($name);
    $dot=strrpos($name,'.');
    if($dot) $name=substr($name,0,$dot);
    return $name;
  }

  static function dirname($file=__FILE__) {
    return dirname($file);
  }
  
  static function size($file, $nice=false) {
    @clearstatcache();
    $size = @filesize($file);
    if(!$size) return false;
    return ($nice) ? self::nice_size($size) : $size;
  }

  static function nice_size($size) {
    $size = str::sanitize($size, 'int');
    if($size < 1) return '0 kb';

    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
  }

  static function convert($name, $type='jpg') {
    return self::name($name) . $type;
  }

  static function safe_name($string) {
    return str::urlify($string);
  }

}






/*

############### GLOBALS ###############

*/

class g {

  static function get($key=null, $default=null) {
    if(empty($key)) return $GLOBALS;
    return a::get($GLOBALS, $key, $default);
  }

  static function set($key, $value=null) {
    if(is_array($key)) {
      // set all new values
      $GLOBALS = array_merge($GLOBALS, $key);
    } else {
      $GLOBALS[$key] = $value;
    }
  }

}






/*

############### LANGUAGE ###############

*/

class l {

  public static $lang = array();

  static function set($key, $value=null) {
    if(is_array($key)) {
      self::$lang = array_merge(self::$lang, $key);
    } else {
      self::$lang[$key] = $value;
    }
  }

  static function get($key=null, $default=null) {
    if(empty($key)) return self::$lang;
    return a::get(self::$lang, $key, $default);
  }

  static function change($language='en') {
    s::set('language', l::sanitize($language));
    return s::get('language');
  }

  static function current() {
    if(s::get('language')) return s::get('language');
    $lang = str::split(server::get('http_accept_language'), '-');
    $lang = str::trim(a::get($lang, 0));
    $lang = l::sanitize($lang);
    s::set('language', $lang);
    return $lang;
  }

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

  static function sanitize($language) {
    if(!in_array($language, c::get('languages', array('en')) )) $language = c::get('language', 'en');
    return $language;
  }

}







/*

############### REQUEST ###############

*/

class r {

  static private $_ = false;

  // fetch all data from the request and sanitize it
  static function data() {
    if(self::$_) return self::$_;
    return self::$_ = self::sanitize($_REQUEST);
  }

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
  
  static function set($key, $value=null) {
    $data = self::data();
    if(is_array($key)) {
      self::$_ = array_merge($data, $key);
    } else {
      self::$_[$key] = $value;
    }
  }
  
  static function method() {
    return strtoupper(server::get('request_method'));
  }

  static function get($key=false, $default=null) {
    $request = (self::method() == 'GET') ? self::data() : array_merge(self::data(), self::body());
    if(empty($key)) return $request;
    return a::get($request, $key, $default);
  }

  static function body() {
    @parse_str(@file_get_contents('php://input'), $body); 
    return self::sanitize((array)$body);
  }

  static function parse() {
    $keep  = func_get_args();
    $result = array();
    foreach($keep AS $k) {
      $params       = explode(':', $k);
      $key          = a::get($params, 0);
      $type         = a::get($params, 1, 'str');
      $default      = a::get($params, 2, '');
      $result[$key] = str::sanitize( get($key, $default), $type );
    }
    return $result;
  }

  static function is_ajax() {
    return (strtolower(server::get('http_x_requested_with')) == 'xmlhttprequest') ? true : false;
  }
  
  static function is_get() {
    return (self::method() == 'GET') ? true : false;
  }
  
  static function is_post() {
    return (self::method() == 'POST') ? true : false; 
  }
  
  static function is_delete() {
    return (self::method() == 'DELETE') ? true : false; 
  }
  
  static function is_put() {
    return (self::method() == 'PUT') ? true : false;  
  }

  static function referer($default=null) {
    if(empty($default)) $default = '/';
    return server::get('http_referer', $default);
  }

}

function get($key=false, $default=null) {
  return r::get($key, $default);
}







/*

############### SESSION ###############

*/
class s {

  static function id() {
    return @session_id();
  }

  static function set($key, $value=false) {
    if(!isset($_SESSION)) return false;
    if(is_array($key)) {
      $_SESSION = array_merge($_SESSION, $key);
    } else {
      $_SESSION[$key] = $value;
    }
  }

  static function get($key=false, $default=null) {
    if(!isset($_SESSION)) return false;
    if(empty($key)) return $_SESSION;
    return a::get($_SESSION, $key, $default);
  }

  static function remove($key) {
    if(!isset($_SESSION)) return false;
    $_SESSION = a::remove($_SESSION, $key, true);
    return $_SESSION;
  }

  static function start() {
    @session_start();
  }

  static function destroy() {
    @session_destroy();
  }

  static function restart() {
    self::destroy();
    self::start();
  }

  static function expired($time) {
    // get the logged in seconds
    $elapsed_time = (time() - $time);
    // if the session has not expired yet
    return ($elapsed_time >= 0 && $elapsed_time <= c::get('session.expires')) ? false : true;
  }

}







/*

############### SERVER ###############

*/
class server {
  static function get($key=false, $default=null) {
    if(empty($key)) return $_SERVER;
    return a::get($_SERVER, str::upper($key), $default);
  }
}








/*

############### SIZE ###############

*/
class size {

  static function ratio($width, $height) {
    return ($width / $height);
  }

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








/*

############### STRING ###############

*/

class str {

  static function html($string, $keep_html=true) {
    if($keep_html) {
      return stripslashes(implode('', preg_replace('/^([^<].+[^>])$/e', "htmlentities('\\1', ENT_COMPAT, 'utf-8')", preg_split('/(<.+?>)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE))));
    } else {
      return htmlentities($string, ENT_COMPAT, 'utf-8');
    }
  }

  static function unhtml($string) {
    $string = strip_tags($string);
    return html_entity_decode($string, ENT_COMPAT, 'utf-8');
  }

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

  static function xml($text, $html=true) {

    // convert raw text to html safe text
    if($html) $text = self::html($text);

    // convert html entities to xml entities
    return strtr($text, self::entities());

  }

  static function unxml($string) {

    // flip the conversion table
    $table = array_flip(self::entities());

    // convert html entities to xml entities
    return strtr($string, $table);

  }

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

  static function encode($string) {
    $encoded = '';
    $length = str::length($string);
    for($i=0; $i<$length; $i++) {
      $encoded .= (rand(1,2)==1) ? '&#' . ord($string[$i]) . ';' : '&#x' . dechex(ord($string[$i])) . ';';
    }
    return $encoded;
  }

  static function email($email, $text=false) {
    if(empty($email)) return false;
    $email  = (string)$email;
    $string = (empty($text)) ? $email : $text;
    $email  = self::encode($email, 3);
    return '<a title="' . $email . '" class="email" href="mailto:' . $email . '">' . self::encode($string, 3) . '</a>';
  }

  static function link($link, $text=false) {
    $text = ($text) ? $text : $link;
    return '<a href="' . $link . '">' . str::html($text) . '</a>';
  }

  static function short($string, $chars, $rep='…') {
    if(str::length($string) <= $chars) return $string;
    $string = self::substr($string,0,($chars-str::length($rep)));
    $punctuation = '.!?:;,-';
    $string = (strspn(strrev($string), $punctuation)!=0) ? substr($string, 0, -strspn(strrev($string),  $punctuation)) : $string;
    return $string . $rep;
  }

  static function shorturl($url, $chars=false, $base=false, $rep='…') {
    return url::short($url, $chars, $base, $rep);
  }

  static function excerpt($string, $chars=140, $removehtml=true, $rep='…') {
    if($removehtml) $string = strip_tags($string);
    $string = str::trim($string);    
    $string = str_replace("\n", '', $string);
    if(str::length($string) <= $chars) return $string;
    return ($chars==0) ? $string : substr($string, 0, strrpos(substr($string, 0, $chars), ' ')) . $rep;
  }

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

  static function apostrophe($name) {
    return (substr($name,-1,1) == 's' || substr($name,-1,1) == 'z') ? $name .= "'" : $name .= "'s";
  }

  static function plural($count, $many, $one, $zero = '') {
    if($count == 1) return $one;
    else if($count == 0 && !empty($zero)) return $zero;
    else return $many;
  }

  function substr($str, $start, $end = null) {
    return mb_substr($str, $start, ($end == null) ? mb_strlen($str, 'UTF-8') : $end, 'UTF-8');
  }

  static function lower($str) {
    return mb_strtolower($str, 'UTF-8');
  }

  static function upper($str) {
    return mb_strtoupper($str, 'UTF-8');
  }

  static function length($str) {
    return mb_strlen($str, 'UTF-8');
  }

  static function contains($str, $needle) {
    return strstr($str, $needle);
  }

  static function match($string, $preg, $get=false, $placeholder=false) {
    $match = @preg_match($preg, $string, $array);
    if(!$match) return false;
    if($get === false) return $array;
    return a::get($array, $get, $placeholder);
  }

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

  static function split($string, $separator=',', $length=1) {

    if(is_array($string)) return $string;

    $string = trim($string, $separator);
    $parts  = explode($separator, $string);
    $out    = array();

    foreach($parts AS $p) {
      $p = self::trim($p);
      if(!empty($p) && str::length($p) >= $length) $out[] = $p;
    }

    return $out;

  }

  static function trim($string) {
    $string = preg_replace('/\s\s+/u', ' ', $string);
    return trim($string);
  }

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

  static function ucwords($str) {
    return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
  }

  static function ucfirst($str) {
    return str::upper(str::substr($str, 0, 1)) . str::substr($str, 1);
  }

  static function utf8($string) {
    $encoding = mb_detect_encoding($string,'UTF-8, ISO-8859-1, GBK');
    return ($encoding != 'UTF-8') ? iconv($encoding,'utf-8',$string) : $string;
  }

  static function stripslashes($string) {
    if(is_array($string)) return $string;
    return (get_magic_quotes_gpc()) ? stripslashes(stripslashes($string)) : $string;
  }

}








/*

############### URL ###############

*/

class url {

  static function current() {
    return 'http://' . server::get('http_host') . server::get('request_uri');
  }

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

  static function has_query($url) {
    return (str::contains($url, '?')) ? true : false;
  }

  static function strip_query($url) {
    return preg_replace('/\?.*$/is', '', $url);
  }

  static function strip_hash($url) {
    return preg_replace('/#.*$/is', '', $url);
  }

  static function valid($url) {
    return v::url($url);
  }

}







/*

############### VALID ###############

*/

class v {

  static function string($string, $options) {
    $format = null;
    $min_length = $max_length = 0;
    if(is_array($options)) extract($options);

    if($format && !preg_match('/^[$format]*$/is', $string)) return false;
    if($min_length && str::length($string) < $min_length)   return false;
    if($max_length && str::length($string) > $max_length)  return false;
    return true;
  }

  static function password($password) {
    return self::string($password, array('min_length' => 4));
  }

  static function passwords($password1, $password2) {

    if($password1 == $password2
      && self::password($password1)
      && self::password($password2)) {
      return true;
    } else {
      return false;
    }

  }

  static function date($date) {
    $time = strtotime($date);
    if(!$time) return false;

    $year = date('Y', $time);
    $month = date('m', $time);
    $day   = date('d', $time);

    return (checkdate($month, $day, $year)) ? $time : false;

  }

  static function email($email) {
    $regex = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';
    return (preg_match($regex, $email)) ? true : false;
  }

  static function url($url) {
    $regex = '/^(https?|ftp|rmtp|mms|svn):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i';
    return (preg_match($regex, $url)) ? true : false;
  }

  static function filename($string) {

    $options = array(
      'format' => 'a-zA-Z0-9_-',
      'min_length' => 2,
    );

    return self::string($string, $options);

  }

}







/*

############### XML ###############

*/
class x {

  static function parse($xml) {

    $xml = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$2$3', $xml);
    $xml = @simplexml_load_string($xml, null, LIBXML_NOENT);
    $xml = @json_encode($xml);
    $xml = @json_decode($xml, true);
    return (is_array($xml)) ? $xml : false;

  }

}

?>
