-- 東陽明珠 賞鯨預約系統 資料庫結構
-- 匯入方式：可用 phpMyAdmin 匯入，或執行 `mysql -u root < schema.sql`

CREATE DATABASE IF NOT EXISTS dongyang_mingzhu
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE dongyang_mingzhu;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    adult_count INT NOT NULL DEFAULT 1,
    child_count INT NOT NULL DEFAULT 0,
    contact_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NULL,
    notes TEXT NULL,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trip_date (trip_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 預設管理員帳號：admin / admin123
-- 請務必在正式使用前更改密碼！
INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$69B7bGMASuH/D17.juW6l.WvqaP2Em/hlr3S3EmBRr7tflHaT9AUO')
ON DUPLICATE KEY UPDATE username = username;
-- 上面的 hash 對應密碼 "admin123"（bcrypt）
