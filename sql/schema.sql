-- Sophie's Stardust — MySQL Schema
--
-- STEP 1 — Create the database in the 123reg control panel:
--   Hosting → MySQL Databases → Create database
--   Note down the database name, username, and password it gives you.
--   (You cannot CREATE DATABASE from a script on shared hosting — no privilege.)
--
-- STEP 2 — Run this file in phpMyAdmin:
--   Select the database you just created from the left panel, then
--   click the SQL tab, paste this file, and click Go.

CREATE TABLE IF NOT EXISTS `attempts` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(24)     NOT NULL,
  `quiz_id`     VARCHAR(100)    NOT NULL,
  `category_id` VARCHAR(100)    NOT NULL,
  `category`    VARCHAR(100)    NOT NULL,
  `quiz`        VARCHAR(200)    NOT NULL,
  `score`       TINYINT UNSIGNED NOT NULL,
  `total`       TINYINT UNSIGNED NOT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_name_quiz` (`name`, `quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores the full quizzes JSON (one row, id=1 always).
-- The admin page writes here; the site reads from here.
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `json_data`   LONGTEXT        NOT NULL,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
