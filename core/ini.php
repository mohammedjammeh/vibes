<?php
	session_start();


	//database
	try {
		$handler = new PDO('mysql:host=127.0.0.1;dbname=vibes', 'root', '');
		$handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
		die("Sorry, there has been an error.");
	}



	//classes
	require_once 'classes/hash.php';
	require_once 'classes/redirect.php';
	require_once 'classes/session.php';
	require_once 'classes/token.php';
	require_once 'functions/sanitize.php';



	//facebook: https://github.com/facebook/php-graph-sdk
	require_once 'Facebook/autoload.php';

	$fb = new \Facebook\Facebook([
		'app_id' => '2021714781418578',
		'app_secret' => '39141f211929fba901197bcc469c2d31',
		'default_graph_version' => 'v2.12'
	]);

	$helper = $fb->getRedirectLoginHelper();




	//google: https://github.com/google/google-api-php-client/releases/
	require_once 'GoogleAPI/vendor/autoload.php';
	$gClient = new Google_Client();
	$gClient->setClientId('930562542624-nf3b2hft7g4d18ghm2i8l056qbs12002.apps.googleusercontent.com');
	$gClient->setClientSecret('_3nJyBBlabRDyRich6dY_Kij');
	$gClient->setApplicationName('Vibes');
	$gClient->setRedirectUri('http://vibes.com/gCallback.php');
	$gClient->addScope("https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.email");




	//spotify: https://github.com/jwilsson/spotify-web-api-php
	require 'vendor/autoload.php'; 
	$session = new SpotifyWebAPI\Session(
	    '81a8e07f444c4646abbfb396641829b8',
	    'b6452c3a07cb4361bba92b9d9d1933c6',
	    'http://vibes.com/spotifyCallback.php'
	);




	//twitter: https://github.com/venkyymudaliar/Twitter-Api-Implementation
	require_once 'twitter/OAuth.php';
	require_once 'twitter/TwitterAPIExchange.php';
	require_once 'twitter/twitteroauth.php';

	define('CONSUMER_KEY', '1lYkjc42cUODjUaubrwVrGAUA');
	define('CONSUMER_SECRET', 'bLC3mWYO0NLOZzYYSwed3mohR7Xfwwlf2QrzkkSrrPZk5CRjwk');
	define('OAUTH_CALLBACK', 'http://vibes.com/twitterCallback.php');



	//instagram: https://www.youtube.com/watch?v=UzFE2FGkOj8
	$instagramSettings = array(
		'clientID' => 'db99baf9fd184b02944a6f3696ebff59', 
		'clientSecret' => '58bf2a584e884270883a0f9e1213a62e ', 
		'redirectURI' => 'http://vibes.com/instagramCallback.php' 
	);

	class InstagramAPI {
		var $clientID = '';
		var $clientSecret = '';
		var $redirectURI = '';

		public function __construct($instagramSettings = array()) {
			$this->clientID = $instagramSettings['clientID'];
			$this->clientSecret = $instagramSettings['clientSecret'];
			$this->redirectURI = $instagramSettings['redirectURI'];
		}

		public function getAccessTokenAndUserDetails($code) {
			$postFields = array(
				'client_id' => $this->clientID, 
				'client_secret' => $this->clientSecret, 
				'grant_type' => 'authorization_code', 
				'redirect_uri' => $this->redirectURI, 
				'code' => 'code'
			);

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, ' https://api.instagram.com/oauth/access_token');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
			$curlResponse = curl_exec($curl);
			curl_close($curl);

			return json_decode($curlResponse, true);

		}

		public function getLoginURL() {
			return 'https://api.instagram.com/oauth/authorize/?client_id=' . $this->clientID . '&redirect_uri=' . $this->redirectURI . '&response_type=code';
		}
	}

	$instagram = new InstagramAPI($instagramSettings);
?>