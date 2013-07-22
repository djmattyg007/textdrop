<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}
//TODO: draw an entire user into memory and cache it

function userf_exists($userID, $fullUser = false)
{
	if (is_numeric($_POST["user"])) {
		if (intval($_POST["user"]) <= 0) {
			return null;
		}
		$where = "userID";
		$whereType = PDO::PARAM_INT;
	} else {
		$where = "username";
		$whereType = PDO::PARAM_STR;
	}
	global $db;

	if ($fullUser) {
		$select = "`userID`, `username`, `displayname`";
	} else {
		$select = "`userID`";
	}

	try {
		$statement = $db->prepare("SELECT $select FROM `users` WHERE `{$where}` = ?");
		$statement->bindParam(1, $_POST["user"], $whereType);
		$statement->execute();
		$user = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	if ($user) {
		return $user;
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

