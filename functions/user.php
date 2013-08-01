<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$userf = null;

// Draw an entire data into memory and cache it. This reduces the number of
// requests to the database.
function userf_draw($userID)
{
	if (is_numeric($_POST["user"])) {
		if (intval($_POST["user"]) <= 0) {
			return false;
		}
		$where = "userID";
		$whereType = PDO::PARAM_INT;
	} else {
		$where = "username";
		$whereType = PDO::PARAM_STR;
	}
	global $db;

	try {
		$statement = $db->prepare("SELECT `userID`, `username`, `displayname` FROM `users` WHERE `{$where}` = ?");
		$statement->bindParam(1, $_POST["user"], $whereType);
		$statement->execute();
		$user = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}
	
	if ($user) {
		if ($GLOBALS["userf"] === null) {
			$GLOBALS["userf"] = array();
		}
		$GLOBALS["userf"][$user["userID"]] = &$user;
		$GLOBALS["userf"][$user["username"]] = &$user;
		return true;
	} else {
		return false;
	}
}

function userf_exists($userID, $fullUser = false)
{
	if (userf_draw($userID)) {
		if ($fullUser) {
			return $GLOBALS["userf"][$userID];
		} else {
			return $GLOBALS["userf"][$userID]["userID"];
		}
	} else {
		return null;
	}
}

function userf_isfriend($userA, $userB)
{
	if (!is_numeric($userA) || !is_numeric($userB)) {
		respondFatal();
	}
	$uA = intval($userA);
	$uB = intval($userB);
	if ($userA == $userB) {
		return false;
	} elseif ($uA > $uB) {
		$temp = $uA;
		$uA = $uB;
		$uB = $temp;
		unset($temp);
	}
	global $db;

	try {
		$statement = $db->prepare("SELECT `status` FROM `friendships` WHERE `user1` = ? AND `user2` = ?");
		$statement->bindParam(1, $uA, PDO::PARAM_INT);
		$statement->bindParam(2, $uB, PDO::PARAM_INT);
		$statement->execute();
		$status = $statement->fetchColumn();
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	if ($status) {
		return true;
	} else {
		return false;
	}
}

