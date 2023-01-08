<?php

// safely escape text into html
function text(string $text): string
{
	return htmlspecialchars($text);
}
