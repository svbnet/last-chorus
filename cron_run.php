<?php

/* 
* cron_run.php
* Since it takes a good while for stuff to be retrieved this should only be run every half an hour.
* In this example, the script gets various pieces of data and writes it to an include file which is displayed
* on index.php.
*/

require 'last_chorus.php';

$ci = last_chorus();
$un = LFM_USERNAME;
$c = nl2br($ci['chorus']);

$html = <<<HTML
	<div class="np">
		<blockquote class="lyrics">
			$c
		</blockquote>
		<p class="ai"><a href="{$ci['last_fm_track']['url']}">{$ci['last_fm_track']['name']} - {$ci['last_fm_track']['artist']['#text']}</a></p>
		<p class="ui"><a href="http://www.last.fm/user/$un">$un on Last.fm</a></p>
	</div> 
HTML;

file_put_contents('lc_include.php', $html);