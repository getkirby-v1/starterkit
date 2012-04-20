<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

/**
 * Central Plugin class for registering and invoking Plugins 
 */
class Plugins {

	private static $plugins = array();

	/**
	 * Register a new Plugin Class. Always use the method to register new plugins.
	 *
	 * @param Plugin $plugin
	 *	Each plugin needs at least to implement the Plugin interface. Each plugin requires a unique name, otherwise it may be overridden by plugins with equal names.
	 */
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
	
	/**
	 * Returns a list of installed plugins
	 */
	static function installed() {
		$result = array();
		foreach (self::$plugins as $name => $plugin) {
			$result[] = $name . ' (' . $plugin->version() . ')';
		}
		return $result;
	}
	
	/**
	 * Invokes Plugin functions. Always use this method to invoke plugins.
	 *
	 * @param $type
	 *	The interface name of the Plugin
	 * @param
	 *	The function within the plugin class
	 * @param
	 *	Arguments to pass to the plugins
	 * @return
	 *	The results of all plugins as an hashmap having plugin names as keys and return values as values.
	 */
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

/**
 * The basic plugin interface. Use this to implement own plugin hooks.
 */
interface Plugin {

	/**
	 * Returns the name of the plugin
	 */
	public function name();
	
	/**
	 * Returns the version of the plugin
	 */
	public function version();
	
	/**
	 * Returns the minimum required Kirby version
	 */
	public function min_kirby_version();

}