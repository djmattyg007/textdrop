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

function translate($str, $val = null)
{
	if (isset($translation->{"$str"})) {
		$string = $translation->{"$str"};
		if ($val) {
			$string = str_replace("{s}", $val, $string);
		}
		return $string;
	} else {
		return $str;
	}
}

