-- Run this SQL to add Google OAuth support to existing database
-- Execute in phpMyAdmin SQL tab

ALTER TABLE users ADD COLUMN google_id VARCHAR(100) DEFAULT NULL AFTER avatar;
ALTER TABLE users ADD INDEX idx_google_id (google_id);
