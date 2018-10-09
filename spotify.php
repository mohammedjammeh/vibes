<?php
	require 'vendor/autoload.php'; 
	$session = new SpotifyWebAPI\Session(
	    '81a8e07f444c4646abbfb396641829b8',
	    'b6452c3a07cb4361bba92b9d9d1933c6',
	    'http://vibes.com/spotifyCallback.php'
	);

	$options = [
	    'scope' => [
	        'playlist-read-private',
	        'user-read-private',
	    ],
	];

	header('Location: ' . $session->getAuthorizeUrl($options));
	die();

?>