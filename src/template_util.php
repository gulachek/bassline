<?php

// safely escape text into html
function text(string $text): string
{
	return htmlspecialchars($text);
}

// render a relative URI to current page
function uri(string $rel): string
{
	return text("{$_SERVER['PHP_SELF']}/{$rel}");
}
