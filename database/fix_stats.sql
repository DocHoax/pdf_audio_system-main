-- EchoDoc Stats Fix Migration
-- This adds event tracking columns to the existing user_analytics table
-- Run this in phpMyAdmin for echodoc_db

USE echodoc_db;

-- Add event_type column if it doesn't exist
ALTER TABLE user_analytics 
ADD COLUMN event_type VARCHAR(50) NULL AFTER user_id;

-- Add event_data column if it doesn't exist  
ALTER TABLE user_analytics
ADD COLUMN event_data JSON NULL AFTER event_type;

-- Add session_id column if it doesn't exist
ALTER TABLE user_analytics
ADD COLUMN session_id VARCHAR(64) NULL AFTER event_type;

-- Add page_url column if it doesn't exist
ALTER TABLE user_analytics
ADD COLUMN page_url VARCHAR(500) NULL AFTER event_data;

-- Add ip_address column if it doesn't exist
ALTER TABLE user_analytics
ADD COLUMN ip_address VARCHAR(45) NULL AFTER page_url;

-- Add user_agent column if it doesn't exist
ALTER TABLE user_analytics
ADD COLUMN user_agent VARCHAR(500) NULL AFTER ip_address;

-- Add index on event_type for faster queries
ALTER TABLE user_analytics
ADD INDEX idx_event_type (event_type);

-- Done! Refresh the stats page to verify.
