-- EchoDoc Analytics Table Migration
-- This script adds missing columns to user_analytics table for full stats tracking
-- Run this SQL in phpMyAdmin or MySQL command line
-- 
-- IMPORTANT: This script is SAFE to run multiple times - it checks if columns exist first

USE echodoc_db;

-- Step 1: Check if user_analytics table exists and create it if not
CREATE TABLE IF NOT EXISTS user_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON DEFAULT NULL,
    page_url VARCHAR(500) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Add missing columns if they don't exist
-- Note: MySQL doesn't have "ADD COLUMN IF NOT EXISTS" before version 8.0.28
-- So we use a procedure to safely add columns

DELIMITER //

CREATE PROCEDURE AddColumnIfNotExists()
BEGIN
    -- Check and add event_type column
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'echodoc_db' 
        AND TABLE_NAME = 'user_analytics' 
        AND COLUMN_NAME = 'event_type'
    ) THEN
        ALTER TABLE user_analytics ADD COLUMN event_type VARCHAR(50) NOT NULL DEFAULT 'unknown';
    END IF;
    
    -- Check and add event_data column
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'echodoc_db' 
        AND TABLE_NAME = 'user_analytics' 
        AND COLUMN_NAME = 'event_data'
    ) THEN
        ALTER TABLE user_analytics ADD COLUMN event_data JSON DEFAULT NULL;
    END IF;
    
    -- Check and add session_id column
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'echodoc_db' 
        AND TABLE_NAME = 'user_analytics' 
        AND COLUMN_NAME = 'session_id'
    ) THEN
        ALTER TABLE user_analytics ADD COLUMN session_id VARCHAR(128) DEFAULT NULL;
    END IF;
    
    -- Check and add page_url column
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'echodoc_db' 
        AND TABLE_NAME = 'user_analytics' 
        AND COLUMN_NAME = 'page_url'
    ) THEN
        ALTER TABLE user_analytics ADD COLUMN page_url VARCHAR(500) DEFAULT NULL;
    END IF;
    
    -- Check and add ip_address column
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'echodoc_db' 
        AND TABLE_NAME = 'user_analytics' 
        AND COLUMN_NAME = 'ip_address'
    ) THEN
        ALTER TABLE user_analytics ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL;
    END IF;
    
    -- Check and add user_agent column
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'echodoc_db' 
        AND TABLE_NAME = 'user_analytics' 
        AND COLUMN_NAME = 'user_agent'
    ) THEN
        ALTER TABLE user_analytics ADD COLUMN user_agent VARCHAR(500) DEFAULT NULL;
    END IF;
END //

DELIMITER ;

-- Execute the procedure
CALL AddColumnIfNotExists();

-- Clean up: Drop the procedure after use
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Step 3: Add indexes if they don't exist (safely fails if they already exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'echodoc_db' AND TABLE_NAME = 'user_analytics' AND INDEX_NAME = 'idx_event_type') = 0,
    'ALTER TABLE user_analytics ADD INDEX idx_event_type (event_type)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Insert sample data for testing (optional - uncomment if needed)
-- INSERT INTO user_analytics (event_type, event_data) VALUES 
-- ('tts', '{"voice": "Eniola", "text_length": 500}'),
-- ('tts', '{"voice": "Idera", "text_length": 300}'),
-- ('translate', '{"target_lang": "yo"}'),
-- ('translate', '{"target_lang": "ha"}'),
-- ('download_mp3', '{"document": "test.pdf"}');

-- Step 5: Verify the table structure
DESCRIBE user_analytics;

-- All done! Your user_analytics table is now ready for full stats tracking.
