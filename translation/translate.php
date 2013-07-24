<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

if (empty($CONFIG["APP"]["LANG"])) {
	respondFatal();
}

$translationFile = __DIR__ . DIRECTORY_SEPARATOR . $CONFIG["APP"]["LANG"] . ".json";
if (!file_exists($translationFile)) {
	respondFatal();
}

$translation = file_get_contents($translationFile);
$translation = json_decode($translation);

if (!$translation) {
	respondFatal();
}

function translate($str, $val = null)
{
	global $translation;
	if (isset($translation->{"$str"})) {
		$string = $translation->{"$str"};
	} else {
		$string = $str;
	}
	if ($val) {
		$string = str_replace("{s}", $val, $string);
	}
	return $string;
}

