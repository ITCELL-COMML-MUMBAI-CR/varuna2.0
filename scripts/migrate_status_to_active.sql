-- Migration script to change status from 'Regular' to 'Active'
-- Run this script to update the database schema and existing data

-- First, update all existing 'Regular' status values to 'Active' in contracts table
UPDATE contracts SET status = 'Active' WHERE status = 'Regular';

-- Then, alter the enum to replace 'Regular' with 'Active'
ALTER TABLE contracts MODIFY COLUMN status ENUM('Under extension','Expired','Active','Terminated') NOT NULL;

-- Note: For licensees, the status is already using 'active' (lowercase) which is correct 