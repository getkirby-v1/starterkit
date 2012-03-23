<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

c::set('version.string', '1.0.9');
c::set('version.number', 1.09);

// define all directories
c::set('root.templates', c::get('root.site') . '/templates');
c::set('root.snippets',  c::get('root.site') . '/snippets');
c::set('root.plugins',   c::get('root.site') . '/plugins');
c::set('root.config',    c::get('root.site') . '/config');
c::set('root.cache',     c::get('root.site') . '/cache');
c::set('root.parsers',   c::get('root.kirby') . '/parsers');

// define the default site url
c::set('scheme', (server::get('https')) ? 'https://' : 'http://');
c::set('url', c::get('scheme') . server::get('http_host'));

// rewrite url setup
c::set('rewrite', true);

// define the home folder
c::set('home', 'home');

// define the 404 folder
c::set('404', 'error');

// default template name
c::set('tpl.default', 'default');

// enable php errors
c::set('debug', false);

// enable or disable cache
c::set('cache', true);
c::set('cache.autoupdate', true);
c::set('cache.data', false);
c::set('cache.html', false);
c::Set('cache.ignore', array());

// create line breaks in markdown
c::set('markdown.breaks', true);

// default definitions for the kirbytext parser
c::set('kirbytext.video.width', 480);
c::set('kirbytext.video.height', 358);

// tinyurl setup
c::set('tinyurl.enabled', true);
c::set('tinyurl.folder', 'x');

// default timezone
c::set('timezone', 'UTC');

// pagination setup
c::set('pagination.variable', 'page');
c::set('pagination.method', 'params');

?>