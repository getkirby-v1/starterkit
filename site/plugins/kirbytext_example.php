<?php

/**
 * Example implementation of a the KirbyTextPlugin
 *
 * This plugin in creates a new wikipedia plugin, with the parameters language and text. Just as the example given on the getkirby.de website.
 * 
 * The plugin implements the methods name, version and min_kirby_version, which are required for all plugins.
 *
 * Additional the the method tagname returns the implemented tag name (yes, it is only one per class) and attributes tag names of that tag.
 * The method parse_<tagname> implements the logic.
 * 
 * Finally, the Plugins::register() method registers the new plugin.
 */


class MyWikiKirbyTextPlugin implements KirbyTextPlugin {

	function name() {
		return 'Kirby Text Wikipedia Plugin';
	}
	
	function version() {
		return '1.0';
	}
	
	function min_kirby_version() {
		return '1.0.3';
	}
	
	public function tagname() {
		return 'wikipedia';
	}
	
	public function attributes() {
		return array('language', 'text');
	}
	
	function parse_wikipedia($params) {
	
		$search = $params['wikipedia'];

		// define default values for attributes
		$defaults = array(
			'language' => 'en',
			'text'     => $search
		);

		// merge the given parameters with the default values
		$options = array_merge($defaults, $params);

		// build the final url
		$url = 'http://' . $options['language'] . '.wikipedia.org/w/index.php?search=' . urlencode($search);

		// build the link tag
		return '<a class="wikipedia" href="' . $url . '">' . html($options['text']) . '</a>';    
	}
}

Plugins::register(new MyWikiKirbyTextPlugin());
