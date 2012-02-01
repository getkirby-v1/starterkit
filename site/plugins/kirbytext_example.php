<?php

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
