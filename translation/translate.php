<?php

if (empty($CONFIG["LANG"])) {
	respondFatal();
}

$translationFile = __DIR__ . DIRECTORY_SEPARATOR . $CONFIG["LANG"] . ".json";
if (!file_exists($translationFile)) {
	respondFatal();
}

$translation = file_get_contents($translationFile);
$translation = json_decode($translation);

if (!$translation) {
	respondFatal();
}

function translate($str)
{
	if (isset($translation->{"$str"})) {
		return $translation->{"$str"};
	} else {
		return $str;
	}
}

