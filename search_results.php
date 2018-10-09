<?php
	$search = $_GET['search'];

	$pageTitle = 'Vibes Search Results';
	require_once 'inc/header.php';

	$results = $api->search($search, 'track');
	$tracksBlock = '<p class="searchTrackResults">Search results for ' . $search . '.</p>';
	$tracksBlock .= '<ul class="tracksBlock cf">';
	for ($i=0; $i < count($results->tracks->items); $i++) { 
		$tracksBlock .= '<li>';
		$tracksBlock .= '<a href="';
		$tracksBlock .= 'track.php?number=' . $i . '&search=' . $search . '&artist=' . preg_replace("/[^A-Za-z0-9 ]/", "", $results->tracks->items[$i]->artists[0]->name) . '&name=' . preg_replace("/[^A-Za-z0-9 ]/", "", $results->tracks->items[$i]->name);
		$tracksBlock .= '">';


		$tracksBlock .= '<img src="';
		$tracksBlock .= $results->tracks->items[$i]->album->images[0]->url;
		$tracksBlock .= '">';

		$tracksBlock .= '<p>';
		$tracksBlock .= $results->tracks->items[$i]->name;
		$tracksBlock .= '</p>';

		$tracksBlock .= '<p>';
		$tracksBlock .= $results->tracks->items[$i]->artists[0]->name;
		$tracksBlock .= '</p>';

		$tracksBlock .= '</a>';
		$tracksBlock .= '</li>';

	}
	
	$tracksBlock .= '</ul>';

	echo $tracksBlock;



	require_once 'inc/footer.php';

?>