-- Add remember_token column to users table for storing remember me tokens
ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL AFTER password;
