<?php

function gidTokenFromUrl(string $url) :array
{
	$x = preg_match('/.+e.hentai.org\/g\/(\d+)\/(\w+)\//', $url, $out);

	if (!$x) {
		return [];
	}

	return [ $out[1], $out[2] ];
}