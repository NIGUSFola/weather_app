-- Ethiopia Weather Aggregator Database Schema
-- Run this in phpMyAdmin or MySQL CLI after creating the database `ethiopia_weather`

CREATE DATABASE IF NOT EXISTS ethiopia_weather
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ethiopia_weather;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,   -- ✅ match PHP code
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cities table
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    region VARCHAR(100) DEFAULT 'Ethiopia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Favorites table (user saved cities)
CREATE TABLE favorites (
    user_id INT NOT NULL,
    city_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, city_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);

-- weather cache table --
CREATE TABLE weather_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    type ENUM('forecast','alerts','current','radar') NOT NULL,
    payload JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_city_type (city_id, type)
);

-- API requests table (rate limiting + monitoring)
CREATE TABLE api_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Logs table (admin monitoring)
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('INFO','WARN','ERROR','DEBUG') NOT NULL,
    message TEXT NOT NULL,
    user_id INT NULL,
    role ENUM('user','admin') NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Distributed locks table (for cron jobs)
CREATE TABLE distributed_locks (
    name VARCHAR(100) PRIMARY KEY,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ✅ Seed Ethiopian cities
INSERT INTO cities (name, region) VALUES
('Addis Ababa', 'Central'),
('Shashamane', 'Oromia'),
('Hawassa', 'South'),
('Bahir Dar', 'Amhara')
ON DUPLICATE KEY UPDATE name=name;

-- ✅ Seed admin account (bcrypt hash placeholder)
INSERT INTO users (email, password, role)
VALUES ('admin@gmail.com', '$2y$10$changemehashedpassword', 'admin')
ON DUPLICATE KEY UPDATE email=email;
