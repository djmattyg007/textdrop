CREATE TABLE `tags` (
	`tagID` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`label` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	CONSTRAINT label_ux UNIQUE (`label`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `main_data`
ADD COLUMN `tag` SMALLINT UNSIGNED DEFAULT NULL,
ADD FOREIGN KEY(`tag`) REFERENCES `tags`(`tagID`) ON DELETE SET NULL;

