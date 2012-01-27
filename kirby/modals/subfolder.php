<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  
<title>Running Kirby in a subfolder</title>
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
  color: red;
  text-decoration: none;
}

p {
  margin-bottom: 20px;
}
h1 {
  margin-bottom: 50px;
}

pre {
  font-family: "Monaco", "Courier", monospace;
  position: relative;
  overflow: auto;
  background: #f9f9f9;
  -webkit-box-shadow: rgba(0,0,0, .05) 0px 2px 10px inset;
  -moz-box-shadow: rgba(0,0,0, .05) 0px 2px 10px inset;
  -o-box-shadow: rgba(0,0,0, .05) 0px 2px 10px inset;
  box-shadow: rgba(0,0,0, .05) 0px 2px 10px inset;
  padding: 20px;
  font-size: 13px;
  line-height: 22px;
  margin-bottom: 20px;
  white-space: nowrap;
}
pre code {
  font-family: "Monaco", "Courier", monospace;
  background: none;
  padding: 0;
}

strong {
  color: red;
}


</style>

</head>

<body>

<h1>Running Kirby in a subfolder</h1>

<p>
  It seems that you are trying to run Kirby in a subfolder of your domain <a href="<?php echo $url ?>"><?php echo $url ?></a>
</p>
<p>
  Please go to <b>site/config/config.php</b> and make sure to add the following rules:
</p>
<p>
  <code>
    <pre>
      c::set('url', '<?php echo $url ?>');
    </pre>
  </code>
</p>
<p>… and …</p>
<p>
  <code>
    <pre>
      c::set('subfolder', '<?php echo $subfolder ?>');<br />
    </pre>
  </code>
</p>
</p>
<p>
  You might also need to adjust your .htaccess file if you are using mod_rewrite<br />
</p>
<p>
  <code>
    <pre>
      RewriteBase /<?php echo $subfolder ?>
    </pre>
  </code>
</p>
<p>
  Read more about it in the Kirby Docs: <a href="http://getkirby.com/docs">http://getkirby.com/docs</a>
</p>

</body>

</html>