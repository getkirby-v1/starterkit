# Kirby

Kirby is a file-based CMS.   
Easy to setup, easy to use, flexible as hell.

<http://getkirby.com>

Subscribe to the Kirby newsletter: <http://tinyletter.com/kirby>    
or follow @getkirby on Twitter for updates: <http://twitter.com/getkirby>
Kirby even has a Facebook page: <http://facebook.com/getkirby>


## Buy

Though Kirby's source code is available on Github: <http://github.com/bastianallgeier/kirbycms>, you must purchase a license per website. Please go to <http://getkirby.com/buy> for more information.


## Installation

Installing Kirby couldn't be simpler. Just connect to your FTP-Server and upload all files contained in the Kirby package to the document root of your server – done!

Visit <http://yourdomain.com> (you should probably replace that with your url :)) and take a look at your brand new, Kirby-flavoured site.


### Adding your license code

After you've purchased a license for Kirby, please add your license code to site/config/config.php:

	c::set('license', 'put your license code here');


### Running Kirby in a subfolder of your domain

Kirby will try to automatically detect if your site is installed in a subfolder. I.e.: `http://yourdomain.com/subfolder`

Sometimes auto-detection might fail, depending on your server setup. In that case, go to `site/config/config.php` and make sure you manually set the url config variable:
	
	c::set('url', 'http://yourdomain.com/subfolder');

Afterwards make sure to also set the subfolder name:

	c::set('subfolder', 'mySubfolderName');

You probably also need to adjust the RewriteBase in the `.htaccess` file if you want to use mod_rewrite. You will find more information about all this in the default config file and the `.htaccess` file.


### URL Rewriting

If you are not allowed to have your own `.htaccess` file or to use mod_rewrite, go to `site/config/config.php` and search for the part where you can switch off url rewriting. 


#### Trouble with URL Rewriting?

If you're seeing 404 errors for pages other than your index page, you may need to make sure Apache is allowing mod_rewrite configuration in your `.htaccess` file.

Open up your site's VirtualHost file and make sure that the `AllowOverride` directive is set to `All`, or at least that it contains `FileInfo` and  `Options` for your site's directory.

**Here's an example VirtualHost:**

	<VirtualHost *:80>
		ServerName yourdomain.com
		DocumentRoot "/path/to/your/site"
		# ...
		<Directory /path/to/your/site>
			AllowOverride All
			# ...
		</Directory>
	</VirtualHost>


## Documentation
<http://getkirby.com/docs>


## Support
<http://getkirby.com/contact>


## Contact 
<mail@getkirby.com>


## Copyright

© 2009-2012 Bastian Allgeier (OpenWe GmbH)
<http://getkirby.com>
