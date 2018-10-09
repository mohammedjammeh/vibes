<?php
	require_once 'core/ini.php';
	Session::delete('access_token');
	Session::delete('user');

	$gClient->revokeToken();
	
	session_destroy();
	Redirect::to('login.php');
?>