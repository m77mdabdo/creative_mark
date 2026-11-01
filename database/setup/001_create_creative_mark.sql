-- Creative Mark: dedicated database and least-privilege users (ADR-0008).
-- Run once with an account that can GRANT (XAMPP root). Touches nothing outside `creative_mark`.
-- Replace the two placeholder passwords before running; the real values live only in the git-ignored .env.

CREATE DATABASE IF NOT EXISTS `creative_mark`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Migration user: schema changes on creative_mark only.
CREATE USER IF NOT EXISTS 'cm_migrate'@'localhost' IDENTIFIED BY '__CM_MIGRATE_PASSWORD__';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES
  ON `creative_mark`.* TO 'cm_migrate'@'localhost';

-- Runtime user: data access on creative_mark only, no DDL.
CREATE USER IF NOT EXISTS 'cm_app'@'localhost' IDENTIFIED BY '__CM_APP_PASSWORD__';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE TEMPORARY TABLES, LOCK TABLES
  ON `creative_mark`.* TO 'cm_app'@'localhost';

FLUSH PRIVILEGES;

SHOW GRANTS FOR 'cm_migrate'@'localhost';
SHOW GRANTS FOR 'cm_app'@'localhost';
