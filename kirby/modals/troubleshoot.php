<?php 

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

// Kirby Troubleshoot File

$modules   = (function_exists('apache_get_modules')) ? apache_get_modules() : array(); 
$rewrite   = in_array('mod_rewrite', $modules);
$subfolder = ltrim(dirname(server::get('script_name')), '/');
$root      = c::get('root');
$templates = c::get('root.templates');
$cache     = c::get('root.cache');

// auto-detect the url if it is not set
$url = (c::get('url') === false) ? c::get('scheme') . server::get('http_host') : rtrim(c::get('url'), '/');

// try to detect the subfolder      
$subfolder = (c::get('subfolder')) ? trim(c::get('subfolder'), '/') : trim(dirname($_SERVER['SCRIPT_NAME']), '/');

if(!empty($subfolder)) {
  // check if the url already contains the subfolder      
  // so it's not included twice
  if(!preg_match('!' . preg_quote($subfolder) . '$!i', $url)) $url .= '/' . $subfolder;
}

$compatible = true;
if(floatval(phpversion()) < 5.2) {
  $compatible = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  
<title>Kirby Troubleshooting</title>
<meta charset="utf-8" />
<meta name="robots" content="noindex, nofollow" />

<style>

* {
  padding: 0;
  margin: 0;
}

body {
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  font-size: 14px;
  line-height: 20px;
  color: #222;
  width: 500px;
  margin: 50px auto;
}

a {
  color: #777;
  text-decoration: none;
}

h1 {
  margin-bottom: 50px;
}

dt {
  font-weight: bold;
  padding-top: 5px;
}
dd {
  border-bottom: 1px solid #eee;
  padding-bottom: 5px;
  color: #777;
}

strong {
  color: red;
}


</style>

</head>

<body>

<h1>Kirby Troubleshooting</h1>

<dl>
  <dt>Kirby CMS Version</dt>
  <dd><?php echo c::get('version.number') . ' (' . c::get('version.string') . ')' ?></dd>

  <dt>Kirby Toolkit Version</dt>
  <dd><?php echo c::get('version') ?></dd>

  <dt>URL</dt>
  <?php if(c::get('url') && $url != c::get('url')): ?>
  <dd>
    <strong>The URL for your site seems to be setup incorrectly</strong><br />
    URL in your config: <strong><?php echo c::get('url') ?></strong><br />
    Detected URL: <strong><?php echo $url ?></strong>    
  </dd>
  <?php else: ?>
  <dd><a href="<?php echo $url ?>"><?php echo $url ?></a></dd>
  <?php endif ?>

  <dt>Subfolder</dt>
  <?php if($subfolder != c::get('subfolder')): ?>
  <dd>
    <strong>You might want to set the subfolder in your config file</strong><br />
    Subfolder in site/config/config.php: <strong><?php echo c::get('subfolder') ?></strong><br />
    Detected Subfolder: <strong><?php echo $subfolder ?></strong>
  </dd>
  <?php elseif(empty($subfolder)): ?>
  <dd>Your site seems not to be running in a subfolder</dd>
  <?php else: ?>
  <dd>Your site seems to be running in a subfolder</dd>
  <?php endif ?>

  <dt>Root</dt>
  <dd><?php echo c::get('root') ?></dd>

  <dt>System Folder</dt>
  <dd><?php echo c::get('root.kirby') ?></dd>

  <dt>Content Folder</dt>
  <dd><?php echo c::get('root.content') ?></dd>

  <dt>Site Folder</dt>
  <dd><?php echo c::get('root.site') ?></dd>

  <dt>Templates Folder</dt>
  <?php if(is_dir($templates)): ?>
  <dd><?php echo $templates ?></dd>
  <?php else: ?>
  <dd><strong>Your templates folder could not be found<br /><?php echo $templates ?></strong></dd>
  <?php endif; ?>

  <dt>Default Template</dt>
  <?php if(!file_exists($templates . '/' . c::get('tpl.default') . '.php')): ?>
  <dd>
    <strong>Your default template is missing<br /><?php echo $templates . '/' . c::get('tpl.default') . '.php' ?></strong>
  </dd>
  <?php else: ?>
  <dd><?php echo $templates . '/' . c::get('tpl.default') . '.php' ?></dd>
  <?php endif; ?>

  <dt>Cache Folder</dt>
  <?php if(!is_dir($cache)): ?>
  <dd><strong>Your cache folder could not be found<br /><?php echo $cache ?></strong></dd>
  <?php elseif(!is_writable($cache)): ?>
  <dd><strong>Your cache folder seems not to be writable<br /><?php echo $cache ?></strong></dd>
  <?php else: ?>
  <dd><?php echo $cache ?></dd>
  <?php endif; ?>

  <dt>Cache Data Structure</dt>
  <dd><?php echo (c::get('cache.data')) ? 'yes' : 'no' ?></dd>

  <dt>Cache HTML</dt>
  <dd><?php echo (c::get('cache.html')) ? 'yes' : 'no' ?></dd>

  <dt>URL-Rewriting</dt>
  <?php if(empty($modules)): ?>
  <dd>Can't detect url rewriting. You are probably not running Kirby on Apache. You might need to setup your own rewrite rules depending on your server setup.</dd>  
  <?php elseif($rewrite && c::get('rewrite')): ?>
  <dd>url rewriting is enabled</dd>  
  <?php elseif(c::get('rewrite')): ?>
  <dd><strong>mod_rewrite seems not to be available</strong></dd>  
  <?php else: ?>
  <dd>url rewriting is disabled</dd>    
  <?php endif ?>

  <dt>Your PHP Version</dt>
  <?php if($compatible): ?>
  <dd><?php echo phpversion() ?></dd>
  <?php else: ?>
  <dd><strong><?php echo phpversion() ?> - this version is not compatible!!!</strong></dd>
  <?php endif; ?>

  <dt>Your Server Software</dt>
  <dd><?php echo $_SERVER['SERVER_SOFTWARE'] ?></dd>

  <dt>Installed Plugins</dt>
  <dd><?php a::show(dir::read(c::get('root.plugins'))) ?></dd>
    
  <dt>Installed Snippets</dt>
  <dd><?php a::show(dir::read(c::get('root.snippets'))) ?></dd>

  <dt>Your config files</dt>
  <dd><?php a::show(dir::read(c::get('root.site') . '/config')) ?></dd>
    
  <dt>Your entire config</dt>
  <dd><?php a::show(c::get()) ?></dd>

  <dt>PHP Error Reporting</dt>
  <dd><?php echo (ini_get('display_errors')) ? 'yes' : 'no' ?></dd>

</ul>

</body>

</html>