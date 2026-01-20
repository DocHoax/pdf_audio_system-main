-- EchoDoc Analytics Table Update
-- Run this SQL to add support for additional event types

USE echodoc_db;

-- Update the event_type column to include new event types
-- First, alter the enum to add new values
ALTER TABLE user_analytics 
MODIFY COLUMN event_type VARCHAR(50) NOT NULL;

-- Add index on event_data for JSON queries if not exists
-- Note: MySQL 5.7+ supports JSON indexing via generated columns
-- For now, we rely on the existing indexes

-- Create a new table for voice statistics (optional - for faster queries)
CREATE TABLE IF NOT EXISTS voice_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voice_name VARCHAR(50) NOT NULL,
    stat_date DATE NOT NULL,
    use_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_voice_date (voice_name, stat_date),
    INDEX idx_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a new table for language statistics (optional - for faster queries)
CREATE TABLE IF NOT EXISTS language_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL,
    stat_date DATE NOT NULL,
    translation_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_lang_date (language_code, stat_date),
    INDEX idx_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update analytics_daily to include TTS and translation stats
ALTER TABLE analytics_daily 
ADD COLUMN IF NOT EXISTS total_tts_generations INT DEFAULT 0 AFTER total_downloads,
ADD COLUMN IF NOT EXISTS total_translations INT DEFAULT 0 AFTER total_tts_generations,
ADD COLUMN IF NOT EXISTS total_tts_characters BIGINT DEFAULT 0 AFTER total_translations;
