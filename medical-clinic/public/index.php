<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start the session
session_start();

// Check if the user is logged in and redirect accordingly
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'patient':
            header('Location: ../patient/dashboard.php');
            break;
        case 'doctor':
            header('Location: ../doctor/dashboard.php');
            break;
        default:
            header('Location: ../auth/login.php');
            break;
    }
    exit;
}

// Include the header
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Clinic</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome to the Medical Clinic</h1>
        <p>Please <a href="auth/login.php">login</a> to continue.</p>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>