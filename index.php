<?php

define("MODE", "WEB");

if (isset($_SERVER["HTTP_X_API_KEY"])) {
	echo $_SERVER["HTTP_X_API_KEY"];
} else {
	echo "No authentication";
}

// Initialise response functions.
require("response.php");

// Setup site configuration.
require("config.php");

// Setup database connection.
require("db" . DIRECTORY_SEPARATOR . "connection.php");

