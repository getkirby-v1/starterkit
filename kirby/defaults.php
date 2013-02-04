<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

c::set('version.string', '1.1.2');
c::set('version.number', 1.12);

// set a required panel version to make sure 
// core and panel will work together nicely
c::set('panel.min.version', 0.9);

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
c::set('404.header', true);

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
c::set('markdown', true);
c::set('markdown.breaks', true);
c::set('markdown.extra', false);

// smartypants
c::set('smartypants', false);
c::set('smartypants.attr', 1);
c::set('smartypants.doublequote.open', '&#8220;');
c::set('smartypants.doublequote.close', '&#8221;');
c::set('smartypants.space.emdash', ' ');
c::set('smartypants.space.endash', ' ');
c::set('smartypants.space.colon', '&#160;');
c::set('smartypants.space.semicolon', '&#160;');
c::set('smartypants.space.marks', '&#160;');
c::set('smartypants.space.frenchquote', '&#160;');
c::set('smartypants.space.thousand', '&#160;');
c::set('smartypants.space.unit', '&#160;');
c::set('smartypants.skip', 'pre|code|kbd|script|math');

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

// change the default kirby content file extension
c::set('content.file.extension', 'txt');