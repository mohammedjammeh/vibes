<?php
	header('Content-Type: application/json');
	require_once 'core/ini.php';

	if (!isset($_GET['query'])) {
		echo json_encode([]);
		exit();
	} else {
		$query = $_GET['query'] . '%';
	}

	$searchSQL = 'SELECT searched FROM searchedhistory WHERE searched Like ?';
	$searchQuery = $handler->prepare($searchSQL);
	$searchQuery->bindParam(1, $query, PDO::PARAM_STR);
	$searchQuery->execute();

	echo json_encode($searchQuery->fetchAll());
?>