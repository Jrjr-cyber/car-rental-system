-- Car Rental System Database Schema
-- Created for University PHP & JS Project

CREATE DATABASE IF NOT EXISTS car_rental_db;
USE car_rental_db;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cars table
CREATE TABLE cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid') NOT NULL,
    transmission ENUM('Manual', 'Automatic') NOT NULL,
    seats INT NOT NULL,
    description TEXT,
    image1 VARCHAR(255),
    image2 VARCHAR(255),
    image3 VARCHAR(255),
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Reservations table
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Insert sample admin
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@carrental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample users
INSERT INTO users (first_name, last_name, email, phone, password, address) VALUES 
('John', 'Doe', 'john.doe@email.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Main St, New York, NY'),
('Jane', 'Smith', 'jane.smith@email.com', '+0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Ave, Los Angeles, CA'),
('Mike', 'Johnson', 'mike.j@email.com', '+1122334455', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Pine Rd, Chicago, IL');

-- Insert sample cars
INSERT INTO cars (brand, model, year, price_per_day, fuel_type, transmission, seats, description, image1, image2, image3, available) VALUES 
('Toyota', 'Camry', 2023, 45.00, 'Hybrid', 'Automatic', 5, 'Comfortable and fuel-efficient sedan perfect for city driving.', 'camry1.jpg', 'camry2.jpg', 'camry3.jpg', TRUE),
('Honda', 'CR-V', 2023, 55.00, 'Petrol', 'Automatic', 5, 'Spacious SUV with excellent safety features and reliability.', 'crv1.jpg', 'crv2.jpg', 'crv3.jpg', TRUE),
('BMW', '3 Series', 2023, 85.00, 'Petrol', 'Automatic', 5, 'Luxury sports sedan with premium features and performance.', 'bmw1.jpg', 'bmw2.jpg', 'bmw3.jpg', TRUE),
('Mercedes', 'C-Class', 2023, 90.00, 'Petrol', 'Automatic', 5, 'Elegant luxury sedan with advanced technology and comfort.', 'merc1.jpg', 'merc2.jpg', 'merc3.jpg', TRUE),
('Tesla', 'Model 3', 2023, 120.00, 'Electric', 'Automatic', 5, 'Revolutionary electric vehicle with autopilot and premium features.', 'tesla1.jpg', 'tesla2.jpg', 'tesla3.jpg', TRUE),
('Ford', 'Mustang', 2023, 100.00, 'Petrol', 'Manual', 4, 'Iconic American muscle car with powerful performance.', 'mustang1.jpg', 'mustang2.jpg', 'mustang3.jpg', TRUE),
('Audi', 'A4', 2023, 95.00, 'Petrol', 'Automatic', 5, 'Sophisticated luxury sedan with Quattro all-wheel drive.', 'audi1.jpg', 'audi2.jpg', 'audi3.jpg', TRUE),
('Nissan', 'Leaf', 2023, 65.00, 'Electric', 'Automatic', 5, 'Popular electric hatchback with impressive range and features.', 'leaf1.jpg', 'leaf2.jpg', 'leaf3.jpg', TRUE);

-- Insert sample reservations
INSERT INTO reservations (user_id, car_id, pickup_date, return_date, total_amount, status) VALUES 
(1, 1, '2024-01-15', '2024-01-18', 135.00, 'Confirmed'),
(2, 3, '2024-01-20', '2024-01-22', 170.00, 'Confirmed'),
(3, 5, '2024-01-25', '2024-01-28', 360.00, 'Pending'),
(1, 2, '2024-02-01', '2024-02-03', 110.00, 'Completed'),
(2, 4, '2024-02-05', '2024-02-07', 180.00, 'Cancelled');
