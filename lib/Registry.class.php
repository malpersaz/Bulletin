<?php
/**
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Date: 09.04.2011 - 14:27
 * File: Registry.class.php
 * Description: Core Registry Container Class (Registry Pattern)
 */
class Registry {
	static private $data = [];

	// Fetch a registered key
	static public function get($key) {
		return (isset(self::$data[$key]) ? self::$data[$key]  : NULL);
	}

	// Add/Register a new key
	static public function add($key, $value) {
		self::$data[$key] = $value;
	}

	// Check if key exists
	static public function has($key) {
    	return isset(self::$data[$key]);
  	}

	// Delete/Remove a registered key
	static public function remove($key){
		unset(self::$data[$key]);
	}

	// Update/Reset a registered key
	static public function set($key, $value){
		if(self::has($key)){
			self::remove($key);
		}
		self::add($key, $value);
	}

	// Dump all registered data for debugging
  	static public function all(){
  		require_once __DIR__ . '/dBug.php';
  		new dBug(self::$data);
  	}
}
