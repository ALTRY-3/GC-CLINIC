<?php
// This file includes various utility functions used throughout the application

/**
 * Function to sanitize user input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Function to check if a user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Function to redirect to a specified URL
 * @param string $url
 */
function redirectTo($url) {
    header("Location: $url");
    exit();
}

/**
 * Function to display flash messages
 * @param string $message
 * @param string $type
 */
function flashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Function to get flash messages
 * @return array
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Function to validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Function to generate a random token for CSRF protection
 * @return string
 */
function generateCsrfToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Function to verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>