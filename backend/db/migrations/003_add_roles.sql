-- backend/db/migrations/003_add_roles.sql
-- Ensure users table supports both user and admin roles

ALTER TABLE users
DROP COLUMN is_admin;

ALTER TABLE users
ADD COLUMN role ENUM('user','admin') DEFAULT 'user'
AFTER password_hash;
