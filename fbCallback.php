<?php
	require_once 'core/ini.php';

	try {
		$accessToken = $helper->getAccessToken();
	} catch (\Facebook\Exceptions\FacebookResponseException $e) {
		echo 'Response Exception: ' . $e->getMessage();
		exit();
	} catch (\Facebook\Exceptions\FacebookSDKException $e) {
		echo 'SDK Exception: ' . $e->getMessage();
		exit();
	}


	if (!$accessToken) {
		Redirect::to('login.php');
	}

	$oAuth2Client = $fb->getOAuth2Client();
	if (!$accessToken->isLongLived()) {
		$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
		$response = $fb->get("/me?fields=id, first_name, last_name, email, picture.type(large)", $accessToken);
		$userData = $response->getGraphNode()->asArray();
		Session::put('userData', $userData);
		Session::put('access_token', (string) $accessToken);
		Redirect::to('index.php');
	}

?>