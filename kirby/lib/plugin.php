<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class Plugins {

	private static $plugins = array();

	static function register(Plugin $plugin) {
		if (!in_array($plugin, self::$plugins)) {
			$req_version = $plugin->min_kirby_version();
			$kirby_version = c::get('version.string', '1.0.3');
			if (version_compare($kirby_version, $req_version) >= 0)	{
				self::$plugins[$plugin->name()] = $plugin;
			} else {
				throw new Exception($plugin->name() . ' requires a minimum Kirby CMS version of ' . $req_version . ', but you have only ' . $kirby_version . '.');
			}
		}
	}
	
	static function installed() {
		$result = array();
		foreach (self::$plugins as $name => $plugin) {
			$result[] = $name . ' (' . $plugin->version() . ')';
		}
		return $result;
	}
	
	static function invoke($type, $func, $args = array()) {
		if (!interface_exists($type)) {
			throw new Exception('Unkown Class Name: ' . $type);
		}
		
		$result = array();
		
		foreach (self::$plugins as $name => $plugin) {
			if (is_a($plugin, $type)) {
				$result[$name] = call_user_func_array(array($plugin, $func), array($args));
			}
		}
		
		return $result;
	}
	
}

interface Plugin {

	public function name();
	
	public function version();
	
	public function min_kirby_version();

}