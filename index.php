<?php 
	$pageTitle = 'Vibes Home';
	require_once 'inc/header.php';
	$user = Session::get('user');

	// Track Suggestions based on what user has looked at
	$suggestSql = 'SELECT usertrackviewed.viewedTime, usertrackviewed.userID, usertrackviewed.trackViewedID, usertrackviewed.trackNo, usertrackviewed.searched, trackViewed.trackViewedID, trackViewed.trackID 
				FROM usertrackviewed 
				INNER JOIN trackViewed ON usertrackviewed.trackViewedID = trackViewed.trackViewedID
				WHERE usertrackviewed.userID = ?
				ORDER BY RAND()';

	$suggestQuery = $handler->prepare($suggestSql);
	$suggestQuery->bindParam(1, $user, PDO::PARAM_INT);
	$suggestQuery->execute();

	$viewedTracksCount = 0;
	$viewedTunesIDs = array();

	$suggestionBlock = '<p class="suggestions">Suggestions</p>';
	$suggestionBlock .= '<ul class="suggestionBlock cf">';

	while ($row = $suggestQuery->fetch(PDO::FETCH_ASSOC)) {
		if (!in_array($row['trackID'], $viewedTunesIDs) && $viewedTracksCount < 10) {
			$track = $api->getTrack($row['trackID']);

			$relatedArtists = $api->getArtistRelatedArtists($track->artists[0]->id);
			$randomArtistIndex = rand(0, 10);
			$relatedArtistName = $relatedArtists->artists[$randomArtistIndex]->name; 

			$relatedArtistsTracks = $api->search($relatedArtistName, 'track');
			$randomTrackIndex = rand(0, 7);

			$suggestionBlock .= '<li>';
			$suggestionBlock .= '<a href="';
			$suggestionBlock .= 'track.php?number=' . $randomTrackIndex . '&search=' . rawurlencode($relatedArtistName) . '&artist=' . preg_replace("/[^A-Za-z0-9 ]/", "", $relatedArtistsTracks->tracks->items[$randomTrackIndex]->artists[0]->name) . '&name=' . preg_replace("/[^A-Za-z0-9 ]/", "", $relatedArtistsTracks->tracks->items[$randomTrackIndex]->name);
			$suggestionBlock .= '">';


			$suggestionBlock .= '<img src="';
			$suggestionBlock .= $relatedArtistsTracks->tracks->items[$randomTrackIndex]->album->images[0]->url;
			$suggestionBlock .= '">';

			$suggestionBlock .= '<p>';
			$suggestionBlock .= $relatedArtistsTracks->tracks->items[$randomTrackIndex]->name;
			$suggestionBlock .= '</p>';

			$suggestionBlock .= '<p>';
			$suggestionBlock .= $relatedArtistsTracks->tracks->items[$randomTrackIndex]->artists[0]->name;
			$suggestionBlock .= '</p>';

			$suggestionBlock .= '</a>';
			$suggestionBlock .= '</li>';


			$viewedTunesIDs[] = $row['trackID'];
			$viewedTracksCount++;
		}
	}

	$suggestionBlock .= '</ul>';
	echo $suggestionBlock;








	// Saved Songs
	$savedSql = 'SELECT usertrack.savedTime, usertrack.userID, usertrack.trackID, usertrack.trackNo, usertrack.searched, track.trackID, track.spotifyID 
				FROM usertrack 
				INNER JOIN track ON usertrack.trackID = track.trackID
				WHERE usertrack.userID = ?
				ORDER BY usertrack.savedTime DESC';
	$savedQuery = $handler->prepare($savedSql);
	$savedQuery->bindParam(1, $user, PDO::PARAM_INT);
	$savedQuery->execute();

	$savedTracksDisplayCount = 0;

	$savedBlock = '<p class="saved">Saved Tracks</p>';
	$savedBlock .= '<ul class="savedBlock cf">';
	while ($row = $savedQuery->fetch(PDO::FETCH_ASSOC)) {
		if ($savedTracksDisplayCount < 5) {
			$track = $api->getTrack($row['spotifyID']);
			$savedBlock .= '<li>';
			$savedBlock .= '<a href="';
			$savedBlock .= 'track.php?number=' . $row['trackNo'] . '&search=' . rawurlencode($row['searched']) . '&artist=' . rawurlencode($track->artists[0]->name) . '&name=' . rawurlencode($track->name);
			$savedBlock .= '">';


			$savedBlock .= '<img src="';
			$savedBlock .= $track->album->images[0]->url;
			$savedBlock .= '">';

			$savedBlock .= '<p>';
			$savedBlock .= $track->name;
			$savedBlock .= '</p>';

			$savedBlock .= '<p>';
			$savedBlock .= $track->artists[0]->name;
			$savedBlock .= '</p>';

			$savedBlock .= '</a>';
			$savedBlock .= '</li>';

			$savedTracksDisplayCount++;
		}
	}

	$savedBlock .= '</ul>';
	echo $savedBlock;









	// // Viewed Songs
	$viewedSql = 'SELECT usertrackviewed.viewedTime, usertrackviewed.userID, usertrackviewed.trackViewedID, usertrackviewed.trackNo, usertrackviewed.searched, trackViewed.trackViewedID, trackViewed.trackID 
				FROM usertrackviewed 
				INNER JOIN trackViewed ON usertrackviewed.trackViewedID = trackViewed.trackViewedID
				WHERE usertrackviewed.userID = ?
				ORDER BY usertrackviewed.viewedTime DESC';
	$viewedQuery = $handler->prepare($viewedSql);
	$viewedQuery->bindParam(1, $user, PDO::PARAM_INT);
	$viewedQuery->execute();

	$viewedTrackIDs = array();
	$viewedTracksDisplayCount = 0;

	$viewedBlock = '<p class="viewed">Viewed Tracks</p>';
	$viewedBlock .= '<ul class="viewedBlock cf">';

	while ($row = $viewedQuery->fetch(PDO::FETCH_ASSOC)) {
		if (!in_array($row['trackID'], $viewedTrackIDs) && $viewedTracksDisplayCount < 5) {
			$track = $api->getTrack($row['trackID']);
			$viewedBlock .= '<li>';
			$viewedBlock .= '<a href="';
			$viewedBlock .= 'track.php?number=' . $row['trackNo'] . '&search=' . rawurlencode($row['searched']) . '&artist=' . rawurlencode($track->artists[0]->name) . '&name=' . rawurlencode($track->name);
			$viewedBlock .= '">';


			$viewedBlock .= '<img src="';
			$viewedBlock .= $track->album->images[0]->url;
			$viewedBlock .= '">';

			$viewedBlock .= '<p>';
			$viewedBlock .= $track->name;
			$viewedBlock .= '</p>';

			$viewedBlock .= '<p>';
			$viewedBlock .= $track->artists[0]->name;
			$viewedBlock .= '</p>';

			$viewedBlock .= '</a>';
			$viewedBlock .= '</li>';

			$viewedTrackIDs[] = $row['trackID'];
			$viewedTracksDisplayCount++;
		}
	}

	$viewedBlock .= '</ul>';
	echo $viewedBlock;




	require_once 'inc/footer.php';
?>