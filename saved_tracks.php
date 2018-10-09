<?php 
	$pageTitle = 'Vibes Home';
	require_once 'inc/header.php';
	$user = Session::get('user');


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





	require_once 'inc/footer.php';
?>