<?php
	require_once 'core/ini.php';
	if (isset($_GET['error'])) {
		Redirect::to('index.php');
	}

	$data = $instagram->getAccessTokenAndUserDetails($_GET['code']);
	var_dump($data);
?>