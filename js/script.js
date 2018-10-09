// Based on typehead.js and learnt from https://www.youtube.com/watch?v=x4F6Kcbo3Vw
$(document).ready(function() {
	//search recommendations
	var searches = new Bloodhound({
		datumTokenizer: Bloodhound.tokenizers.obj.whitespace('searched'),
		queryTokenizer: Bloodhound.tokenizers.whitespace,
		remote: {
		    url: 'searches.php?query=%QUERY',
		    wildcard: '%QUERY'
		}﻿
	});

	searches.initialize();

	$('#search').typeahead({
		hint: true,
		highlight: true,
		minLength: 1
	}, {
		name: 'searches',
		displayKey: 'searched',
		source: searches.ttAdapter()
	});


	//track AJAX
	// $.ajax({
	// 	url: "spotifyResponse.php",
	// 	data: {
	// 		trackNo : 'lol',
	// 		trackSearch : 'lol',
	// 		trackArtist : 'lol',
	// 		trackName : 'lol'
	// 	},
	// 	type: "GET",
	// 	success: function(response) {
	// 		// checkingSeatsAndStandsData();
	
	// 		alert(response);
	// 	}
	// });


	// hide Blocks
	function hideAllBlocks() {
		$('#trackBlock').hide();
		$('#albumBlock').hide();
		$('#artistBlock').hide();
		$('#youtubeBlock').hide();
		$('#twitterBlock').hide();
		$('#instagramBlock').hide();
	}

	// //display track block
	$('.trackBlock').on('click', function() {
		hideAllBlocks();
		$('#trackBlock').show();
	});

	// //display album block
	$('.albumBlock').on('click', function() {
		hideAllBlocks();
		$('#albumBlock').show();
	});

	// //display artist block
	$('.artistBlock').on('click', function() {
		hideAllBlocks();
		$('#artistBlock').show();
	});

	//display youtube block
	$('.youtubeBlock').on('click', function() {
		hideAllBlocks();
		$('#youtubeBlock').show();
	});

	// //display twitter block
	$('.twitterBlock').on('click', function() {
		hideAllBlocks();
		$('#twitterBlock').show();
		$('#twitterBlock div li').show();
	});

	// //display instagram block
	$('.instagramBlock').on('click', function() {
		hideAllBlocks();
		$('#instagramBlock').show();
	});
	
});