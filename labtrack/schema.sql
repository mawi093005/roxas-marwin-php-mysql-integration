CREATE DATABASE IF NOT EXISTS labtrack_db;
USE labtrack_db;

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('Available', 'Borrowed', 'Maintenance') DEFAULT 'Available',
    borrower VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample initial data
INSERT INTO assets (asset_tag, name, category, status, borrower) VALUES
('AST-1001', 'Dell Latitude 5420', 'Laptops', 'Available', NULL),
('AST-1002', 'Cisco Catalyst 2960', 'Networking', 'Borrowed', 'John Doe'),
('AST-1003', 'Logitech G102 Mouse', 'Peripherals', 'Maintenance', NULL),
('AST-1004', 'ViewSonic 24" Monitor', 'Monitors', 'Available', NULL);