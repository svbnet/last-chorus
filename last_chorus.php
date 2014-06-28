<?php

// Put your MusiXMatch API key here
define('MXM_API_KEY', '');

// Put your last.fm API key here
define('LFM_API_KEY', '');

// Put your last.fm username here
define('LFM_USERNAME', '');

/**
 * Downloads and decodes JSON using cURL
 * @param string url The URL to retrieve content from
 * @return array The decoded JSON structure
 */

function download_json($url) {
	// Download the webpage
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);

	// Error handling
	if ($response === false) {
		throw new Exception('cURL error: ' . curl_error($ch));
	}

	// Parse the JSON as an associative array
	$json = json_decode($response, true);
	if (json_last_error() > 0) {
		throw new Exception('JSON parse error #' . json_last_error());
	}

	curl_close($ch);
	return $json;
}

/**
 * Returns the latest last.fm track for the current user
 * @return array The JSON structure of the latest track
 */
function get_latest_lfm_track() {
	$url = 'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=' . LFM_USERNAME . '&api_key=' . LFM_API_KEY . '&format=json';
	$js = download_json($url);
	return $js['recenttracks']['track'][0];
}

/**
 * Performs a HTTP request to MusiXMatch
 * @param string method The method to call
 * @param array params The HTTP parameters to use
 * @return array The decoded JSON structure
 */
function mm_request($method, $params = array()) {
	$params = array_merge($params, array('apikey' => MXM_API_KEY, 'format' => 'json'));
	$qs = array();
	foreach ($params as $key => $value) {
		$qs[] = $key . '=' . urlencode($value);
	}
	$qs = implode('&', $qs);
	$url = "http://api.musixmatch.com/ws/1.1/$method?$qs";
	$rs = download_json($url)['message'];
	if ($rs['header']['status_code'] !== 200) {
		throw new Exception('MusiXMatch error: ' . $rs['header']['status_code']);
	}
	return $rs;
}

/**
 * Retrieves lyrics from MusiXMatch by track and artist name
 * @param string trackName The track name to use
 * @param string artistName The artist name to use
 * @return mixed An array of lyrics info on success, false if none were found
 */
function get_lyrics($trackName, $artistName) {
	// For some reason, last fm uses entirely different MBIDs to those used by MusiXMatch. SIGH.
	// This may trip up on obscure entries where the track name also happens to be an artist name, but hopefully that'll never happen.


	$searchResults = mm_request('track.search', array('f_has_lyrics' => '1', 'q' => "$trackName $artistName"));

	// Nothing found!
	if ($searchResults['header']['available'] <= 0) {
		return false;
	}

	// Sheesh!
	$firstEntryId = $searchResults['body']['track_list'][0]['track']['track_id'];

	$lyricsJson = mm_request('track.lyrics.get', array('track_id' => $firstEntryId));
	return $lyricsJson['body']['lyrics'];
}

/**
 * Retrieves the chorus of a set of lyrics
 * @param string lyricsStr A song's lyrics returned by get_lyrics
 * @return mixed The chorus of the lyrics, or false if it couldn't be found.
 */
function get_chorus($lyricsStr) {
	// As far as I know choruses are defined by two linebreaks.
	// You know what else is defined by two linebreaks? Restriction notices.
	$lines = explode("\n", $lyricsStr);

	$firstLinePos = 0;
	$lastLinePos = 0;
	for ($i = 0; $i < count($lines); $i++) { 
		$l = $lines[$i];
		// If the current line is blank and the line above isn't an ellipsis, the line after will be a lyric
		if ($l === '' && $lines[$i - 1] !== '...') {
			// If we haven't found the first line, this is it
			if ($firstLinePos === 0) {
				$firstLinePos = $i + 1;
				continue;
			}

			// If we're past the first line, we're at the last line
			if ($lastLinePos === 0) {
				$lastLinePos = $i;
				break;
			}
		}
	}

	if ($firstLinePos > 0 && $lastLinePos > 0) {
		return implode("\n", array_slice($lines, $firstLinePos, $lastLinePos - $firstLinePos));
	}
	return false;
}

function last_chorus() {

	$lt = get_latest_lfm_track();
	$artistName = $lt['artist']['#text'];
	$trackName = $lt['name'];

	$lyrics = get_lyrics($trackName, $artistName);
	if (!$lyrics) {
		return false;
	}

	$chorus = get_chorus($lyrics['lyrics_body']);

	return array(
		'last_fm_track' => $lt,
		'musixmatch_lyrics' => $lyrics,
		'chorus' => $chorus
		);
}