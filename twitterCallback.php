<?php
	require_once 'core/ini.php';
	if (isset($_REQUEST['oauth_token']) && Session::get('token') !== $_REQUEST['oauth_token']) {
		Session::delete('token');
		Redirect::to('index.php');
	} elseif (isset($_REQUEST['oauth_token']) && Session::get('token') == $_REQUEST['oauth_token']) {
		$twitterConnection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, Session::get('token'), Session::get('token_secret'));
		$twitterAccessToken = $twitterConnection->getAccessToken($_REQUEST['oauth_verifier']);

		if ($twitterConnection->http_code == '200') {
			Session::put('status', 'verified');
			Session::put('request_vars', $twitterAccessToken);

			Session::delete('token');
			Session::delete('token_secret');


			if (isset($_SESSION['trackPage'])) {
				Redirect::to($_SESSION['trackPage']);
			} else {
				Redirect::to('index.php');
			}

		} else {
			die('Error: Please try later.');
		}

	} else {
		if (isset($_GET['denied'])) {
			Redirect::to('index.php');
		}

		$twitterConnection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
		$request_token = $twitterConnection -> getRequestToken(OAUTH_CALLBACK);

		Session::put('token', $request_token['oauth_token']);
		Session::put('token_secret', $request_token['oauth_token_secret']);

		if ($twitterConnection->http_code == '200') {
			$twitter_url = $twitterConnection->getAuthorizeURL($request_token['oauth_token']);
			Redirect::to($twitter_url);
		} else {
			die('Error: Connecting to Twitter.');
		}
	}
?>