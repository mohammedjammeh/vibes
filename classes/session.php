<?php 
//https://www.youtube.com/watch?v=3yrpRfdtYc4&list=PLfdtiltiRHWF5Rhuk7k4UAU1_yLAZzhWc&index=12
Class Session {
	public static function exists($name) {
		return (isset($_SESSION[$name])) ? true : false;
	}

	public static function put($name, $value) {
		return $_SESSION[$name] = $value;
	}

	public static function get($name) {
		return $_SESSION[$name];
	}

	public static function delete($name) {
		if(self::exists($name)) {
			unset($_SESSION[$name]);
		}
	}
}