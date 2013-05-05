<?php

define("MODE", "API");

// Initialise response functions.
require("response.php");

// If no API key was supplied, no further processing should be done.
if (!isset($_SERVER["HTTP_X_API_KEY"])) {
	respond(400, false, "There was no API key supplied with the request.");
}

// Setup site configuration.
require("config.php");

// Setup database connection.
require("db" . DIRECTORY_SEPARATOR . "connection.php");

// Perform API authorisation check.
require("db" . DIRECTORY_SEPARATOR . "auth.php");

