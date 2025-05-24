<?php
// Define application constants

// Database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'medical_clinic');

// Application status codes
define('STATUS_SUCCESS', 200);
define('STATUS_CREATED', 201);
define('STATUS_NO_CONTENT', 204);
define('STATUS_BAD_REQUEST', 400);
define('STATUS_UNAUTHORIZED', 401);
define('STATUS_FORBIDDEN', 403);
define('STATUS_NOT_FOUND', 404);
define('STATUS_INTERNAL_SERVER_ERROR', 500);

// Error messages
define('ERROR_INVALID_CREDENTIALS', 'Invalid username or password.');
define('ERROR_USER_NOT_FOUND', 'User not found.');
define('ERROR_ACCESS_DENIED', 'Access denied.');
define('ERROR_UNEXPECTED', 'An unexpected error occurred. Please try again later.');

// Other constants
define('APP_NAME', 'Medical Clinic Management System');
define('APP_VERSION', '1.0.0');
?>