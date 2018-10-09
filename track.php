<?php
	$trackNo = $_GET['number'];
	$trackSearch = $search = $_GET['search'];
	$trackArtist = $_GET['artist'];
	$trackName = $_GET['name'];

	$pageTitle = $trackName;
	require_once 'inc/header.php';
	require_once 'inc/simple_html_dom.php'; // http://simplehtmldom.sourceforge.net/


	if (!isset($trackNo) || !isset($trackSearch) || !isset($trackArtist) ||!isset($trackName)) {
		Redirect::to('index.php');
	} 

	//php curl function
	function curlFunc($searchURL, $findValue, $elementType, $index) {
		$searchCurl = curl_init();
		curl_setopt($searchCurl, CURLOPT_URL, $searchURL);
		curl_setopt($searchCurl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($searchCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($searchCurl, CURLOPT_RETURNTRANSFER, true);
		$searchResult = curl_exec($searchCurl);
		curl_close($searchCurl);

		$hrefs = array();
		$html = new simple_html_dom();
		$html->load($searchResult);

		foreach ($html->find($findValue) as $id) {
			$hrefs[] = $id->$elementType;
		}

		return $hrefs[$index];
	}


	// Learnt From: https://stackoverflow.com/questions/14379459/fetch-all-youtube-videos-using-curl
	function fetchYouTubeVid($href) {
		$url = 'https://www.youtube.com' . $href;
		$youtube = 'http://www.youtube.com/oembed?url=' . $url . '&format=json';
		$VidCurl = curl_init($youtube);
		curl_setopt($VidCurl, CURLOPT_RETURNTRANSFER, 1);
		$return = curl_exec($VidCurl);
		curl_close($VidCurl);
		$result = json_decode($return, true);
		echo $result['html'];
	}


	//User Signing into Twitter
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//register
		if (isset($_POST['signIntoTwitter'])) {

			if(Token::check($_POST['tokenTwitter'], 'tokenTwitter')) {
				Session::put('trackPage', $_SERVER['REQUEST_URI']);
				Redirect::to('twitterCallback.php');
			}
		}
	}









	//TRACK: Spotify
	$results = $api->search($trackSearch, 'track'); //getting track results

	//record track visited
	$trackSQL = 'SELECT * FROM trackviewed WHERE trackID = ?';
	$trackQuery = $handler->prepare($trackSQL);
	$trackQuery->bindParam(1, $results->tracks->items[$trackNo]->id, PDO::PARAM_STR);
	$trackQuery->execute();

	while ($row = $trackQuery->fetch(PDO::FETCH_ASSOC)) {
		$db_trackViewedID = $row['trackViewedID'];
		$db_spotifyTrackID = $row['trackID'];
	}

	if (isset($db_spotifyTrackID)) {
		$newUserSpotifySQL = 'INSERT INTO usertrackviewed (viewedTime, userID, trackViewedID, trackNo, searched) VALUES (?, ?, ?, ?, ?)';
		$newUserSpotifyQuery = $handler->prepare($newUserSpotifySQL);  
		$newUserSpotifyQuery->execute(array(date("Y-m-d H:i:s"), Session::get('user'), $db_trackViewedID, $trackNo, $trackSearch));

	} else {
		$newTrackSql = 'INSERT INTO trackviewed (trackID) VALUES (?)';
		$newTrackQuery = $handler->prepare($newTrackSql);  
		$newTrackQuery->execute(array($results->tracks->items[$trackNo]->id));

		$lastInsertedID = $handler->lastInsertId();

		$newUserSpotifySQL = 'INSERT INTO usertrackviewed (viewedTime, userID, trackViewedID, trackNo, searched) VALUES (?, ?, ?, ?, ?)';
		$newUserSpotifyQuery = $handler->prepare($newUserSpotifySQL);  
		$newUserSpotifyQuery->execute(array(date("Y-m-d H:i:s"), Session::get('user'), $lastInsertedID, $trackNo, $trackSearch));

	}



	//if first track, the prev button should take user to last track.. if not, take user backwards once
	if ($trackNo == 0) {
		$trackNoPrev = count($results->tracks->items) - 1;
	} else {
		$trackNoPrev = $trackNo - 1;
	}

	//if last track, the next button should take user to frst track.. if not, take user forwards once
	if ($trackNo == count($results->tracks->items) - 1) {
		$trackNoNext = 0;
	} else {	
		$trackNoNext = $trackNo + 1;
	}

	$trackBlock = '<img src="' . $results->tracks->items[$trackNo]->album->images[0]->url . '">';

	$trackBlock .= '<p>'. $results->tracks->items[$trackNo]->name . '</p>';

	for ($x=0; $x < count($results->tracks->items[$trackNo]->artists); $x++) {

		 if ($x == count($results->tracks->items[$trackNo]->artists) - 1) {
		 	if ($x == 0) {
		 		$trackBlock .= '<p>'. $results->tracks->items[$trackNo]->artists[$x]->name . '</p>';
		 	} else {
		 		$trackBlock .= ' ' . $results->tracks->items[$trackNo]->artists[$x]->name . '</p>';
		 	}
		 	
		 } else {
		 	$trackBlock .= '<p>'. $results->tracks->items[$trackNo]->artists[$x]->name . ',';
		 }

	}

	$trackBlock .= '<audio controls>';
	$trackBlock .= '<source src="' . $results->tracks->items[$trackNo]->preview_url . '" type="audio/mp3"></source>';
	$trackBlock .= '</audio>';


	//check if user song saved or not
	$trackBlock .='<form method="POST" name="myVibesForm">';
	$spotifyID = $results->tracks->items[$trackNo]->id;
	$userID = Session::get('user');

	$trackCheckSql = 'SELECT usertrack.userID, usertrack.trackID, track.trackID, track.spotifyID
		FROM usertrack 
		INNER JOIN track ON  usertrack.trackID = track.trackID
		WHERE usertrack.userID = ? AND track.SpotifyID = ?';

	$trackCheckQuery = $handler->prepare($trackCheckSql);
	$trackCheckQuery->bindParam(1, $userID, PDO::PARAM_INT);
	$trackCheckQuery->bindParam(2, $spotifyID, PDO::PARAM_STR);
	$trackCheckQuery->execute();

	while ($row = $trackCheckQuery->fetch(PDO::FETCH_ASSOC)) {
		$userSavedTrack = $row['spotifyID'];
	}

	if (isset($userSavedTrack)) {
		$trackBlock .= '<input type="submit" value="Remove from my songs" name="removeFromMyVibes">';
	} else {
		$trackBlock .= '<input type="submit" value="Save to my songs" name="addToMyVibes">';
	}

	$trackBlock .= '</form>';



	// when user clicks add to my Vibes
	$trackSql = 'SELECT trackID, spotifyID FROM track WHERE spotifyID = ?';
	$trackQuery = $handler->prepare($trackSql);
	$trackQuery->bindParam(1, $spotifyID, PDO::PARAM_STR);
	$trackQuery->execute();

	while ($row = $trackQuery->fetch(PDO::FETCH_ASSOC)) {
		$db_trackID = $row['trackID'];
		$db_spotifyID = $row['spotifyID'];
	}

	if (isset($_POST['addToMyVibes'])) {

		if (isset($db_trackID)) {
			$userTrackSql = 'INSERT INTO usertrack (savedTime, userID, trackID, trackNo, searched) VALUES (?, ?, ?, ?, ?)';
			$userTrackQuery = $handler->prepare($userTrackSql);  
			$userTrackQuery->execute(array(date("Y-m-d H:i:s"), Session::get('user'), $db_trackID, $trackNo, $trackSearch));
		} else {
			$newTrackSql = 'INSERT INTO track (spotifyID) VALUES (?)';
			$newTrackQuery = $handler->prepare($newTrackSql);  
			$newTrackQuery->execute(array($spotifyID));

			$lastInsertedID = $handler->lastInsertId();

			$userTrackSql = 'INSERT INTO usertrack (savedTime, userID, trackID, trackNo, searched) VALUES (?, ?, ?, ?, ?)';
			$userTrackQuery = $handler->prepare($userTrackSql);  
			$userTrackQuery->execute(array(date("Y-m-d H:i:s"), Session::get('user'), $lastInsertedID, $trackNo, $trackSearch));
		}

		header('Refresh:0');
	}


	// when user clicks remove from my vibes
	if (isset($_POST['removeFromMyVibes'])) {

		$deleteUserTrackSql = 'DELETE FROM usertrack WHERE userID = ? AND trackID = ?';

		$deleteUserTrackQuery = $handler->prepare($deleteUserTrackSql);
		$deleteUserTrackQuery->bindParam(1, $userID, PDO::PARAM_STR);
		$deleteUserTrackQuery->bindParam(2, $db_trackID, PDO::PARAM_STR);
		$deleteUserTrackQuery->execute();

		header('Refresh:0');
	}


















	//ARTIST: Spotify, Facebook, Twitter, Instagram and YouTube
	$artistID = $results->tracks->items[$trackNo]->artists[0]->id;

	//Artist Bio
	$artist = $api->getArtist($artistID);

	$artistBio = '<div id="artistBio">';
	$artistBio .= '<div><img src="' . $artist->images[0]->url . '"></div>';
	$artistBio .= '<p>' . $artist->name . '</p>';

	$artistBio .= '<p>' . number_format($artist->followers->total) . ' followers</p>';
	$artistBio .= '</div>';

	//Artist Tracks
	$tracks = $api->search($artist->name, 'track');
	$artistTracks = '<div id="#albumSongBlock" class="cf">';
	for ($i=0; $i < count($tracks->tracks->items); $i++) {
		$artistTracks .= '<div id="albumSongDiv">';
		$artistTracks .= '<audio controls>';
		$artistTracks .= '<source src="' . $tracks->tracks->items[$i]->preview_url  . '" type="audio/mp3"></source>';
		$artistTracks .= '</audio>';
		$artistTracks .= '<p>' . $tracks->tracks->items[$i]->name. '</p>';
		$artistTracks .= '</div>';

	}
	$artistTracks .= '</div>';


	// //Artist Social Media: https://www.flaticon.com/free-icon/instagram_145805
 	$artistSocialMedia = '<ul id="artistSocialMedia" class="cf">';

	$searchString = $results->tracks->items[$trackNo]->artists[0]->name;
	$fbSearchURL = 'https://www.google.com/search?q=' . rawurlencode($searchString . ' facebook');

	//facebook
	$fbLinkText = curlFunc($fbSearchURL, 'a[href^=/url?q]', 'href', 0);
	$fbLinkText = explode('=', $fbLinkText);
	$fbLinkText = explode('&', $fbLinkText[1]);
	$fbHref = $fbLinkText[0];
	$artistSocialMedia .= '<li><a href="' . $fbHref . '"><img src="svg/facebook.svg"></a></li>';

	//twitter
	$twitterSearchURL = 'https://www.google.com/search?q=' . rawurlencode($searchString . ' twitter');
	$twitterLinkText = curlFunc($twitterSearchURL, 'a[href^=/url?q]', 'plaintext', 0);
	$twitterLinkText = explode('@', $twitterLinkText);
	$twitterLinkText = explode(')', $twitterLinkText[1]);
	$twitterHref = 'https://twitter.com/' . $twitterLinkText[0];
	$artistSocialMedia .= '<li><a href="' . $twitterHref . '"><img src="svg/twitter.svg"></a></li>';

	//instagram
	$instaSearchURL = 'https://www.google.com/search?q=' . rawurlencode($searchString . ' instagram');

	$instaLinkText = curlFunc($instaSearchURL, 'a[href^=/url?q]', 'plaintext', 0);
	$instaLinkText = explode('@', $instaLinkText);
	$instaLinkText = explode(' ', $instaLinkText[1]);
	$instaLinkText = explode(')', $instaLinkText[0]);
	$instaHref = 'https://instagram.com/' . $instaLinkText[0];
	$artistSocialMedia .= '<li><a href="' . $instaHref . '"><img src="svg/instagram.svg"></a></li>';
	$artistSocialMedia .= '</ul>';

	

	// //Related Artists
	$relatedArtists = $api->getArtistRelatedArtists($artistID);
	$relatedArtistsBlock = '<ul id="relatedArtistsBlock" class="cf">';
	for ($i=0; $i < 8; $i++) { 

		$relatedArtistsTracks = $api->search($relatedArtists->artists[$i]->name, 'track');

		for ($x=0; $x < count($relatedArtistsTracks->tracks->items); $x++) {
			if (count($relatedArtistsTracks->tracks->items[$x]->artists) == 1) {

				$artistName = $relatedArtistsTracks->tracks->items[$x]->artists[0]->name;
				$songName = $relatedArtistsTracks->tracks->items[$x]->name;
				$songNo = $x;
				$searched = $relatedArtists->artists[$i]->name;
			}
		}

		$relatedArtistsBlock .= '<li>';
		$relatedArtistsBlock .= '<a href="';
		$relatedArtistsBlock .= 'track.php?number=' . $songNo . '&search=' . $searched . '&artist=' . rawurlencode($artistName) . '&name=' . rawurlencode($songName);
		$relatedArtistsBlock .= '">';

		$relatedArtistsBlock .= '<div><img src="';
		$relatedArtistsBlock .= $relatedArtists->artists[$i]->images[0]->url;
		$relatedArtistsBlock .= '"></div>';

		$relatedArtistsBlock .= '<p>';
		$relatedArtistsBlock .= $relatedArtists->artists[$i]->name;
		$relatedArtistsBlock .= '</p>';

		$relatedArtistsBlock .= '</a>';
		$relatedArtistsBlock .= '</li>';
	}

	$relatedArtistsBlock .= '</ul>';




	//Artist YouTube
	// $youtubeSearchString = $results->tracks->items[$trackNo]->artists[0]->name;
	$youtubeSearchURL = 'https://www.youtube.com/results?search_query=' . rawurlencode($searchString . 'music videos');

	$youtubeHref01 = curlFunc($youtubeSearchURL, 'a[href^=/watch?v]', 'href', 0);
	$youtubeHref02 = curlFunc($youtubeSearchURL, 'a[href^=/watch?v]', 'href', 3);
	$youtubeHref03 = curlFunc($youtubeSearchURL, 'a[href^=/watch?v]', 'href', 5);




	$artistBlock = $artistBio . $artistSocialMedia . $relatedArtistsBlock;
	$artistBlock = $artistBio . $artistSocialMedia . $artistTracks . $relatedArtistsBlock;
















	//ALBUM: Spotify
	$album = $api->getAlbum($results->tracks->items[$trackNo]->album->id);

	$albumInfoBlock = '<div id="albumInfoBlock">';
	$albumInfoBlock .= '<img src="' . $album->images[0]->url . '">';
	$albumInfoBlock .= '<p>' . $album->name . '</p>';
	$albumInfoBlock .= '<p>' . $album->release_date . '</p>';
	$albumInfoBlock .= '</div>';

	$albumSongBlock = '<div id="albumSongBlock" class="cf">';
	for ($i=0; $i < count($album->tracks->items); $i++) {
		$albumSongBlock .= '<div id="albumSongDiv">';
		$albumSongBlock .= '<audio controls>';
		$albumSongBlock .= '<source src="' . $album->tracks->items[$i]->preview_url . '" type="audio/mp3"></source>';
		$albumSongBlock .= '</audio>';
		$albumSongBlock .= '<p>' . $album->tracks->items[$i]->name . '</p>';
		$albumSongBlock .= '</div>';

	}

	$albumSongBlock .= '</div>';
	$albumSongBlock .= '<p>' . $album->label . '</p>';

	$albumBlock = $albumInfoBlock . $albumSongBlock;












	// VIDEO: Youtube
	$youtubeSearchString = $results->tracks->items[$trackNo]->artists[0]->name . ' ' . $results->tracks->items[$trackNo]->name;
	$youtubeSearchURL = 'https://www.youtube.com/results?search_query=' . rawurlencode($youtubeSearchString);
	$youtubeHref = curlFunc($youtubeSearchURL, 'a[href^=/watch?v]', 'href', 0);











	//TWITTER 
	if (Session::exists('status') && Session::get('status') == 'verified') {
		$screenName = $_SESSION['request_vars']['screen_name'];
		$twitterID = $_SESSION['request_vars']['user_id'];
		$oauth_token = $_SESSION['request_vars']['oauth_token'];
		$oauth_token_secret = $_SESSION['request_vars']['oauth_token_secret'];

		$settings = array(
			'oauth_access_token' => '891756394734641152-5ZAeGigPz9zSdtexOLy9w178JlVVLTx',
			'oauth_access_token_secret' => 'xoxN2YNp1ZvS5iQvyKL9lRv0mIATjbFkTr8ZJgoPieSyG',
			'consumer_key' => '1lYkjc42cUODjUaubrwVrGAUA',
			'consumer_secret' => 'bLC3mWYO0NLOZzYYSwed3mohR7Xfwwlf2QrzkkSrrPZk5CRjwk'
		);

		$userShow = 'https://api.twitter.com/1.1/users/show.json';
		$profileBanner = 'https://api.twitter.com/1.1/users/profile_banner.json';
		$followersList = 'https://api.twitter.com/1.1/followers/list.json';
		$tweets = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$postTweet = 'https://api.twitter.com/1.1/statuses/update.json';
		$search = 'https://api.twitter.com/1.1/search/tweets.json';
		$oembed = 'https://publish.twitter.com/oembed';

		$requestMethod = 'GET';
		$postMethod = 'POST';

		$trackTag = str_replace(' ', '', $results->tracks->items[$trackNo]->name);
		$artistTag = str_replace(' ', '', $results->tracks->items[$trackNo]->artists[0]->name);
		$albumTag = str_replace(' ', '', $results->tracks->items[$trackNo]->album->name);


		//tweet
		$tweetBtns = '<ul class="tweetBtns cf">';

		$tweetBtns .= '<li>';
		$tweetBtns .= '<a class="twitter-follow-button" href="https://twitter.com/intent/follow?screen_name=' . $twitterLinkText[0] . '">';
		$tweetBtns .= '</a>';
		$tweetBtns .= '</li>';

		$tweetBtns .= '<li>';
		$tweetBtns .= '<a class="twitter-hashtag-button" href="https://twitter.com/intent/tweet?button_hashtag=' .  $trackTag . '&hashtags=' . $albumTag . '">';
		$tweetBtns .= '</a>';
		$tweetBtns .= '</li>';

		$tweetBtns .= '<li>';
		$tweetBtns .= '<a class="twitter-mention-button" href="https://twitter.com/intent/tweet?screen_name=' . $twitterLinkText[0] . '">';
		$tweetBtns .= '</a>';
		$tweetBtns .= '</li>';

		$tweetBtns .= '</ul>';

		// echo $tweetBtns;


		//display tweets

		function tagSearchAndOembed($tag) {

			global $settings, $search, $requestMethod, $oembed, $tweetsDiv01, $tweetsDiv02, $tweetsDiv03;

			$getField = '?q=#' . $tag . '&result_type=recent';

			$twitter = new TwitterAPIExchange($settings);
			$twitter->setGetField($getField)
						->buildOauth($search, $requestMethod)
						->performRequest();

			$searchResponse = json_decode($twitter->setGetField($getField)
						->buildOauth($search, $requestMethod)
						->performRequest(), $assoc= TRUE);


			for ($i=0; $i < count($searchResponse['statuses']); $i++) { 

				if (!isset($searchResponse['statuses'][$i]['retweeted_status'])) { //ensures no repetition of retweets
					$statusID = $searchResponse['statuses'][$i]['id_str'];
					$statusUser = $searchResponse['statuses'][$i]['user']['screen_name'];

					$getOembedField = '?url=https://twitter.com/' . $statusUser . '/status/' . $statusID;



					$twitterOembed = new TwitterAPIExchange($settings);
					$twitterOembed->setGetField($getOembedField)
								->buildOauth($oembed, $requestMethod)
								->performRequest();

					$oembedResponse = json_decode($twitterOembed->setGetField($getOembedField)
								->buildOauth($oembed, $requestMethod)
								->performRequest(), $assoc= TRUE);


					if (isset($nextDiv)) {
						if ($nextDiv === 1) {
							$tweetsDiv01 .= $oembedResponse['html'] . '<p class="emptyTag">.</p>';
							$nextDiv = 2;
						} elseif ($nextDiv === 2) {
							$tweetsDiv02 .= $oembedResponse['html'] . '<p class="emptyTag">.</p>';
							$nextDiv = 3;
						} elseif ($nextDiv === 3) {
							$tweetsDiv03 .= $oembedResponse['html'] . '<p class="emptyTag">.</p>';
							$nextDiv = 1;
						}
					} else {
						$tweetsDiv01 .= $oembedResponse['html'] . '<p class="emptyTag">.</p>';
						$nextDiv = 2;
					}

				} 


			}
		}


	} else {
		$twitterSignInBlock = '<form method="POST">';
		$twitterSignInBlock .= '<input type="submit" name="signIntoTwitter" value="Sign In">';
		$twitterSignInBlock .= '<input type="hidden" name="tokenTwitter" value="' . Token::generate('tokenTwitter') . '">';

		$twitterSignInBlock .= '</form>';
	}












	//INSTAGRAM: Using Curl
	// $trackTag = str_replace(' ', '', $results->tracks->items[$trackNo]->name);
	// $trackTag = str_replace('/', '', $trackTag);

	// // $artistTag = str_replace(' ', '', $results->tracks->items[$trackNo]->artists[0]->name);
	// // $albumTag = str_replace(' ', '', $results->tracks->items[$trackNo]->album->name);

	// $instaTagSearchURL = 'https://www.instagram.com/explore/tags/' . $trackTag . '/';

	// $instaTagLinkText = curlFunc($instaTagSearchURL, 'img[src^="https://scontent"]', 'src', 0);
	// // $twitterLinkText = explode('@', $twitterLinkText);
	// // $twitterLinkText = explode(')', $twitterLinkText[1]);

	// // $twitterHref = 'https://twitter.com/' . $twitterLinkText[0];

	// // echo $twitterHref . '<br>';
	// echo $instaTagLinkText;


	//INSTAGRAM: Using Instagram API
	// $instagramLogin = '<a href="' . $instagram->getLoginURL() . '">Log Into Instagram</a>';
	// echo $instagramLogin;

	
?>
			<nav>
				<ul class="cf">
					<li><a href="#" class="trackBlock">Track</a></li>
					<li><a href="#" class="albumBlock">Album</a></li>
					<li><a href="#" class="artistBlock">Artist</a></li>
					<li><a href="#" class="youtubeBlock">Video</a></li>
					<li><a href="#" class="twitterBlock">Twitter</a></li>
					<li><a href="#" class="instagramBlock">Instagram</a></li>
				</ul>
			</nav>

			<div id="previousTrack">
				<?php 
				// https://www.flaticon.com/free-icon/fast-forward_860754#term=fast%20forward&page=1&position=19
					echo '<a href="' .	'track.php?number=' . $trackNoPrev . '&search=' . $trackSearch . '&artist=' . $results->tracks->items[$trackNoPrev]->artists[0]->name . '&name=' . $results->tracks->items[$trackNoPrev]->name . '"><img src="svg/fast-rewind.svg"></a>';
				?>
			</div>


			<div id="trackAPIs">

				<div id="trackBlock">
					<?php 
						if (isset($trackBlock)) { 
							echo $trackBlock; 
						} 
					?>
				</div>
				
				<div id="artistBlock">
					<?php 
						if (isset($artistBlock)) { 
							echo $artistBlock; 
							echo '<div id="videosBlock" class="cf">';

								echo '<div>';
								fetchYouTubeVid($youtubeHref01);
								echo '</div>';

									echo '<div>';
								fetchYouTubeVid($youtubeHref02);
								echo '</div>';


							echo '</div>';
						} 
					?>
				</div>

				<div id="albumBlock">
					<?php 
						if (isset($albumBlock)) { 
							echo $albumBlock; 
						} 
					?>
				</div>

				<div id="youtubeBlock">
					<?php 
						if (isset($youtubeHref)) {
							echo '<div id="mainVideoBlock" class="cf">';
								fetchYouTubeVid($youtubeHref);
							echo '</div>';
						}
					?>
				</div>

				<div id="twitterBlock">
					<?php 
						if (isset($tweetBtns)) {
							echo '<div>' . $tweetBtns . '</div>';

							echo '<div class="tweetsDiv cf">';

							$tweetsDiv01 = '<div class="tweetsDiv01">';
							$tweetsDiv02 = '<div class="tweetsDiv02">';
							$tweetsDiv03 = '<div class="tweetsDiv03">';

							tagSearchAndOembed($trackTag);
							tagSearchAndOembed($artistTag);
							tagSearchAndOembed($albumTag);

							$tweetsDiv01 .= '</div>';
							$tweetsDiv02 .= '</div>';
							$tweetsDiv03 .= '</div>';

							echo $tweetsDiv01;
							echo $tweetsDiv02;
							echo $tweetsDiv03;

							echo '</div>';


						} elseif(isset($twitterSignInBlock)) {
							echo $twitterSignInBlock;
						}
					?>
				</div>

			</div>

			<div id="nextTrack">
				<?php 
				// https://www.flaticon.com/free-icon/fast-forward_860754#term=fast%20forward&page=1&position=19
					echo '<a href="' . 'track.php?number=' . $trackNoNext . '&search=' . $trackSearch . '&artist=' . $results->tracks->items[$trackNoNext]->artists[0]->name . '&name=' . $results->tracks->items[$trackNoNext]->name . '"><img src="svg/fast-forward.svg"></a>' . '<br><br>';
				?>
			</div>

<?php
	require_once 'inc/footer.php';
?>