<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_rental_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_URL', 'http://localhost/car-rental-system');
define('SITE_NAME', 'Car Rental System');
define('ADMIN_EMAIL', 'admin@carrental.com');

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours

// Upload Configuration
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();

// Timezone
date_default_timezone_set('UTC');
?>
