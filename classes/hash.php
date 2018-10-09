<?php 
//https://www.youtube.com/watch?v=G3hkHIoDi6M&list=PLfdtiltiRHWF5Rhuk7k4UAU1_yLAZzhWc&index=14
class Hash {
	public static function make($string, $salt = '') {
		return hash('sha256', $string . $salt);
	}

	public static function salt($length) {
		return random_bytes($length);
	}

	public static function unique() {
		return self::make(uniqid());
	}
}