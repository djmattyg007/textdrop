<?php
if (isset($_SERVER["HTTP_X_API_KEY"])) {
	echo $_SERVER["HTTP_X_API_KEY"];
} else {
	echo "No authentication";
}

// Setup site configuration
require("config.php");

// Setup database connection
require("connection.php");

