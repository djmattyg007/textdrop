CREATE TABLE friendships (
	`user1` SMALLINT UNSIGNED NOT NULL,
	`user2` SMALLINT UNSIGNED NOT NULL,
	`status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
	CONSTRAINT friendshipsPK PRIMARY KEY (`user1`, `user2`),
	CONSTRAINT statusCode CHECK `status` IN (0, 1),
	CONSTRAINT userOrder CHECK `user1` < `user2`,
	FOREIGN KEY(`user1`) REFERENCES users(`userID`) ON DELETE CASCADE,
	FOREIGN KEY(`user2`) REFERENCES users(`userID`) ON DELETE CASCADE
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `users`
ADD CONSTRAINT userActive CHECK `active` IN (0, 1);

ALTER TABLE `api_keys`
ADD CONSTRAINT keyActive CHECK `active` IN (0, 1);

ALTER TABLE `sessions`
ADD CONSTRAINT requestsCheck CHECK `totalRequests` >= 0;

ALTER TABLE `main_data`
ADD CONSTRAINT archived CHECK `archived` IN (0, 1);

