<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

/* 

---------------------------------------
License Setup
---------------------------------------

Please add your license key, which you've received 
via email after purchasing Kirby. 

If you have no license yet, please buy one: 
http://getkirby.com/buy and support an indie developer. 

You are not allowed to run a website without a valid license key. 
Please read the End User License Agreement for more information: 
http://getkirby.com/license

*/

c::set('license', 'your license key');


/* 

---------------------------------------
URL Setup
---------------------------------------

By default kirby tries to detect the correct url
for your site if this is set to false, but if this should fail 
or you need to set it on your own, do it like this:

c::set('url', 'http://yourdomain.com');

Make sure to write the url without a trailing slash.

To work with relative URLs, you can set the URL like this:

c::set('url', '/');
 
*/

c::set('url', false);


/* 

---------------------------------------
Subfolder Setup
---------------------------------------

Kirby will automatically try to detect the subfolder

i.e. http://yourdomain.com/subfolder

This might fail depending on your server setup. 
In such a case, please set the correct subfolder here. 

You must also set the right url then:

c::set('url', 'http://yoururl.com/subfolder');

if you are using the .htaccess file, make sure to 
set the right RewriteBase there as well:

RewriteBase /subfolder
 
*/

c::set('subfolder', false);


/* 

---------------------------------------
Rewrite URL Setup
---------------------------------------

Kirby uses apache's mod_rewrite to build nice 
urls like http://yourdomain.com/about by default. 
If you can't use mod_rewrite disable rewriting here.
Kirby will then switch to urls like this:

http://yourdomain.com/index.php/about

*/

c::set('rewrite', true);


/* 

---------------------------------------
Homepage Setup
---------------------------------------

By default the folder/uri for your homepage is "home".
Sometimes it makes sense to change that to make your blog
your homepage for example. Just change it here in that case.

*/

c::set('home', 'home');


/*

---------------------------------------
Force SSL 
---------------------------------------

If you want to make sure to force SSL on every
page, just set this setting to true.

Also make sure to include https in your url setup:
c::set('url', 'https://yourdomain.com');

*/

c::set('ssl', false);


/*

---------------------------------------
Kirbytext Setup 
---------------------------------------

set the default video width and height for 
embedded flash videos from youtube or vimeo

*/

c::set('kirbytext.video.width', 480); 
c::set('kirbytext.video.height', 358); 


/* 

---------------------------------------
Markdown Setup 
---------------------------------------

You can globally switch Markdown parsing 
on or off here. 

To disable automatic line breaks in markdown
set markdown.breaks to false. 

You can also switch between regular markdown
or markdown extra: http://michelf.com/projects/php-markdown/extra/

*/

c::set('markdown', true);
c::set('markdown.breaks', true);
c::set('markdown.extra', false);


/* 

---------------------------------------
Smartypants Setup 
---------------------------------------

Smartypants is a typography plugin, which
helps to improve things like quotes and ellipsises
and all those nifty little typography details. 

You can read more about it here: 
http://michelf.com/projects/php-smartypants/typographer/

Smartypants is switched off by default. 
As soon as it is switched on it will affect all 
texts which are parsed by kirbytext()

*/

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



/*

---------------------------------------
Tinyurl Setup 
---------------------------------------

KirbyCMS has built in tiny urls for every
page. Tinyurls look like this:

http://yourdomain.com/x/asd2qd1c 

the /x/ in the url is needed to detect tinyurls,
you can change the x to anything else but an existing page uri.

If you don't want to use tiny urls for your site
disable them here

*/

c::set('tinyurl.folder', 'x');
c::set('tinyurl.enabled', true);


/*

---------------------------------------
Cache 
---------------------------------------

Enable or disable the cache. 
It is disabled by default. 

If you enable it, you need to make 
sure that the site/cache
directory is writable. 

You can also decide to disable/enable
either caching of the data structure 
or the final html. If you are caching
the final html, make sure to clean
the cache, once you've modified your
templates. It's better to keep this
off until your site is ready for production. 

With c::set('cache.autoupdate') you can set if 
Kirby will automatically check for updates in your 
content folder. Depending on the size of your site
this can slow down the performance, because the 
filesystem is accessed a lot. Switch this off to 
disabled autoupdating of cache files, but then you 
need to make sure to delete cache files yourself after
each update. 

With c::set('cache.ignore', array()); you can speficy
an array of URIs which should be skipped for caching.
If you got a search page for example you might not want
to cache each search result so you can add the URI of your
search site to the ignore array: 

c::set('cache.ignore', array('search', 'some/other/uri/to/ignore'));

*/

c::set('cache', false);
c::set('cache.autoupdate', true);
c::set('cache.data', true);
c::set('cache.html', true);
c::set('cache.ignore', array());


/*

---------------------------------------
Timezone Setup 
---------------------------------------

You can change the default timezone used for all 
date functions here. It is set to UTC by default. 

Please read more about it at: http://php.net/manual/en/function.date-default-timezone-set.php

*/ 

c::set('timezone', 'UTC');


/*

---------------------------------------
Troubleshooting
---------------------------------------

Kirby has a built-in troubleshooting screen 
with loads of information about your setup.

It's there to help you out when things don't work
as expected. Set it to true to activate it and
go to your homepage afterwards to display it on refresh. 

*/ 

c::set('troubleshoot', false);


/*

---------------------------------------
Debug 
---------------------------------------

Set this to true to enable php errors. 
Make sure to keep this disabled for your 
production site, so you won't get nasty 
php errors there.

*/

c::set('debug', true);


/* 

---------------------------------------
Your custom config file
---------------------------------------

this is your custom config file for your site.
you can set any variable here, which you want to reuse later.
setting custom config variables works like this:

c::set('yourvar', 'yourvalue');

you can access them later in your code like this

c::get('yourvar', 'some default value if the var is not set');

please be careful with existing config rules to not 
overwrite them accidentally. Maybe just namespace them
in doubt like: 

c::set('yourproject.yourvar', 'yourvalue');

*/


/*

---------------------------------------
Custom host setup
---------------------------------------

I've added a nice way to add different 
config files for different environments

Let's say you run a development version of your
site at http://dev.yoursite.com and a production
version of your site at http://yoursite.com, you
can easily setup two different config files
by adding two more files in this directory and name them
like this: 

config.dev.yoursite.com.php
config.yoursite.com.php

What happens is, that this global config.php
will be loaded first and afterwards only the
config file for the matching hostname will be 
attached. So you can easily overwrite your global
custom config by specific rules for that host. 

*/


/* 

---------------------------------------
Multi-Language support setup
---------------------------------------

If you want to run a site with multiple languages, 
enable support for it here. As soon as you set

c::set('lang.support', true); 

Kirby will automatically create language-dependent 
URLs like:

http://yourdomain.com/en/blog

or 

http://yourdomain.com/de/blog

Make sure to set the default language code and 
also the available language codes. 

If you keep…

c::set('lang.detect', true);

Kirby will try to detect the default language 
from the user agent string instead of using the
default language. 

*/

c::set('lang.support', false);
c::set('lang.default', 'en');
c::set('lang.available', array('en', 'de'));
c::set('lang.detect', true);


/* 

---------------------------------------
Content File Extension
---------------------------------------

Change the default file extension for your
content files here if you'd rather use something
else than txt. For example md or mdown. 

*/

c::set('content.file.extension', 'txt');


/*

---------------------------------------
Ignore Content Files
---------------------------------------

Sometimes it's necessary to ignore particular
content files/folders in all content folders. 
Just add them to the array here. By default
the following files are being ignored:

array('.', '..', '.DS_Store', '.svn', '.git', '.htaccess');

…so you don't have to add them. 

*/
c::set('content.file.ignore', array());
