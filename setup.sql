-- Computer Laboratory Activity No. 10
-- Student Information Management System - database setup
--
-- How to run:
--   1. Start Apache and MySQL in XAMPP.
--   2. Open http://localhost/phpmyadmin/ -> SQL tab.
--   3. Paste this whole file and click Go.
--
-- WARNING: The DROP TABLE statements erase the two activity tables.
-- Run this once on a fresh database (or when you want to start over).

CREATE DATABASE IF NOT EXISTS wst_week13
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wst_week13;

DROP TABLE IF EXISTS student_comments;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_comments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  comment    TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_user (user_id),
  CONSTRAINT fk_comments_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- After you register a test account, promote it to administrator with:
-- UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
