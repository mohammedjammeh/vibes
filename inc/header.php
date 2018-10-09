<?php
	require_once 'core/ini.php';

	//check if user is logged in
	if (!Session::exists('user') && !Session::exists('access_token')) {
		Redirect::to('login.php');
	}

	//check if access token exists
	if (!Session::exists('spotifyAccessToken')) {
		Redirect::to('spotify.php');
	} 

	//setting up spotify access
	$api = new SpotifyWebAPI\SpotifyWebAPI();
    try{
       	$api->setAccessToken(Session::get('spotifyAccessToken'));
        $user = $api->me();
    } catch (\Exception $e){
		$session->refreshAccessToken(Session::get('spotifyRefreshToken'));
		$accessToken = $session->getAccessToken();
		$api->setAccessToken(Session::get('spotifyAccessToken'));
		Session::put('spotifyAccessToken', $accessToken);
        $user = $api->me();
    }


    //if user searches
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		if (isset($_GET['searchBtn'])) {
			if(Token::check($_GET['tokenSearch'], 'tokenSearch')) {
				$search = preg_replace("/[^A-Za-z0-9 ]/", "", strtolower(trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING))));

				if (!empty($search)) {
					$results = $api->search($search, 'track');
					$searchHistorySql = 'SELECT searchedHistoryID, searched FROM searchedhistory WHERE searched = ?';
					$searchHistoryQuery = $handler->prepare($searchHistorySql);
					$searchHistoryQuery->bindParam(1, $search, PDO::PARAM_STR);
					$searchHistoryQuery->execute();

					while ($row = $searchHistoryQuery->fetch(PDO::FETCH_ASSOC)) {
						$db_searchHistoryID = $row['searchedHistoryID'];
					}

					if (isset($db_searchHistoryID)) {
						$alreadySearchedSql = 'INSERT INTO usersearchedhistory (searchedTime, searchedLocation, userID, searchedHistoryID) VALUES (?, ?, ?, ?)';
						$alreadySearchedQuery = $handler->prepare($alreadySearchedSql);  
						$alreadySearchedQuery->execute(array(date("Y-m-d H:i:s"), 'Hull', Session::get('user'), $db_searchHistoryID));

					} else {
						$newSearchSql = 'INSERT INTO searchedhistory (searched) VALUES (?)';
						$newSearchQuery = $handler->prepare($newSearchSql);  
						$newSearchQuery->execute(array($search));

						$lastInsertedID = $handler->lastInsertId();

						$newUserSearchSql = 'INSERT INTO usersearchedhistory (searchedTime, searchedLocation, userID, searchedHistoryID) VALUES (?, ?, ?, ?)';
						$newUserSearchQuery = $handler->prepare($newUserSearchSql);  
						$newUserSearchQuery->execute(array(date("Y-m-d H:i:s"), 'Hull', Session::get('user'), $lastInsertedID));

					}

					Redirect::to('search_results.php?search=' . rawurlencode($search));
				} else {
					echo 'Please search for a song or an artist.';
				}
			}
		}
	}

?>


<!DOCTYPE html>
<html>
	<head lang="en">
		<meta charset="utf-8">
		<title><?php echo $pageTitle; ?></title>
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
		<link href='https://fonts.googleapis.com/css?family=Droid+Serif' rel='stylesheet' type='text/css'>
		<link href="https://fonts.googleapis.com/css?family=Roboto+Slab:100,300,400" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="css/style.css">
	</head>

	<body class="main">
		<header class="cf">
			<h1><a href="index.php">Vibes</a></h1>

			<form method="get" name="searchForm" autocomplete="off">
				<input type="text" name="search" id="search" placeholder="Search.." value="<?php if(isset($search)) { echo escape($search); } ?>" autocomplete="off">
				<input type="hidden" name="tokenSearch" value="<?php echo Token::generate('tokenSearch')?>">
				<!-- <input type="submit" name="searchBtn" value="Search"> -->
				<button name="searchBtn" value="Search">
					<img src="svg/search.svg"> <!-- https://www.flaticon.com/free-icon/search_149852#term=search&page=1&position=1 -->
				</button> 
			</form>

			<ul>
				<li><a href="logout.php">Log Out</a></li>
				<li><a href="saved_tracks.php">Saved Songs</a></li>
			</ul>
		</header>

		<section class="cf">
			