<?php
// Configuration settings for the Medical Clinic application

// Define the application environment
define('APP_ENV', 'development'); // Change to 'production' in live environment

// Define the base URL of the application
define('BASE_URL', 'http://localhost/medical-clinic/public/');

// Define the database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username'); // Replace with your database username
define('DB_PASS', 'your_password'); // Replace with your database password
define('DB_NAME', 'medical_clinic'); // Replace with your database name

// Define application constants
define('APP_NAME', 'Medical Clinic');
define('APP_VERSION', '1.0.0');

// Define error messages
define('ERROR_DB_CONNECTION', 'Database connection failed.');
define('ERROR_INVALID_CREDENTIALS', 'Invalid username or password.');
define('ERROR_USER_NOT_FOUND', 'User not found.');

// Other configuration settings can be added here as needed
?>