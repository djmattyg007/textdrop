ALTER TABLE `main_data`
ADD COLUMN `visibility` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `main_data`
ADD CONSTRAINT visibilityCheck CHECK `visibility` IN (0, 1, 2);

