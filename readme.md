Last chorus
===========

A PHP utility that gets the chorus of the most recent last.fm track you scrobbled. You'll need a last.fm developer account and musixmatch account as well.

## Usage

Include it, then call `last_chorus()`, preferably as a cron job every half hour or so (to limit resource use). The demo files should get you started and give you some inspiration.

`last_chorus()` is the only function you need to know about, and returns an array of:

* The last fm track object (`last_fm_track`). See http://www.last.fm/api/show/user.getRecentTracks for a rundown of what it has.

* The musixmatch lyrics page object (`musixmatch_lyrics`). See https://developer.musixmatch.com/documentation/api-reference/track-lyrics-get.

* The chorus (`chorus`) separated by linebreaks.

## Things you need to know

* Some songs may contain profanity. You can either use a profanity filter or check if the `restricted` property of the lyrics is true.

* I haven't tested this out on every song so you may get an error or not the complete chorus, as the lyrics cut off at 30% on a non-commericial musixmatch API account.

* **if you call last chorus frequently, musixmatch, last fm, your users and your webhost won't be happy.**

## TODO

* Put this into a class