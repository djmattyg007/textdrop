<?php

if (!empty($CONFIG["LANG"])) {
	//respond with error
}

$translationFile = $CONFIG["LANG"] . ".json";
if (!file_exists($translationFile) {
	//respond with error
}

$translation = file_get_contents($translationFile);
$translation = json_decode($translation);

if (!$translation) {
	//respond with error
}

function translate($str)
{
	if (isset($translation->{"$code"})) {
		return $translation->{"$code"};
	} else {
		return $str;
	}
}

