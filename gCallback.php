<?php
	require_once 'core/ini.php';

	if (Session::exists('access_token')) {
		$gClient->setAccessToken(Session::get('access_token'));
	} elseif (isset($_GET['code'])) {
		$token = $gClient->fetchAccessTokenWithAuthCode($_GET['code']);
		Session::put('access_token', $token);
	} else {
		Redirect::to('login.php');
	}

	$oAuth = new Google_Service_Oauth2($gClient);
	$userData = $oAuth->userinfo_v2_me->get();

	$email = $userData['email'];
	$name = $userData['name'];
	$imageName = $userData['picture'];

	$emailSql = 'SELECT * FROM user WHERE email = ?';
	$emailQuery = $handler->prepare($emailSql);
	$emailQuery->bindParam(1, $email, PDO::PARAM_STR);
	$emailQuery->execute();

	while ($row = $emailQuery->fetch(PDO::FETCH_ASSOC)) {
		$db_userID = $row['userID'];
		$db_userType = $row['userType'];
		$db_email = $row['email'];
	}

	function registerAndLogInNewGoogleUser() {
		global $handler, $email, $name, $imageName;
		$newGoogleUserSql = 'INSERT INTO user (userType, email, name) VALUES (?, ?, ?)';
		$newGoogleUserQuery = $handler->prepare($newGoogleUserSql);
		$newGoogleUserQuery->execute(array('google', $email, $name));
		$lastInsertedID = $handler->lastInsertId();

		$newImageSql = 'INSERT INTO image (imageName, userID) VALUES (?, ?)';
		$newImageQuery = $handler->prepare($newImageSql);
		$newImageQuery->execute(array($imageName, $lastInsertedID));

		Session::put('user', $lastInsertedID);
		Redirect::to('index.php');
	}

	if (isset($db_userType)) {
		if ($db_userType == 'google' && $email == $db_email) {
			Session::put('user', $db_userID);
			Redirect::to('index.php');
		} else {
			registerAndLogInNewGoogleUser();
		}
	} else {
		registerAndLogInNewGoogleUser();
	}

?>