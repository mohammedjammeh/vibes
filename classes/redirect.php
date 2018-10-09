<?php 
//https://www.youtube.com/watch?v=VEzJHww-QwM&index=15&list=PLfdtiltiRHWF5Rhuk7k4UAU1_yLAZzhWc
class Redirect {
	public static function to ($location) {
		if($location) {
			header('Location:' . $location);
			exit();
		}
	}
}