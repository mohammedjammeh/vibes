<?php
	require_once 'core/ini.php';

	// Request a access token using the code from Spotify
	$session->requestAccessToken($_GET['code']);
	$scopes = $session->getScope();
	$accessToken = $session->getAccessToken();
	$refreshToken = $session->getRefreshToken();

	// Store the access and refresh tokens somewhere. In a database for example.
	Session::put('spotifyAccessToken', $accessToken);
	Session::put('spotifyScopes', $scopes);
	Session::put('spotifyRefreshToken', $refreshToken);

	// Send the user along and fetch some data!
	Redirect::to('index.php');

?>