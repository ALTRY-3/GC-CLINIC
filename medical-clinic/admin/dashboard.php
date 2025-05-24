<?php
include '../config/config.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/sidebar.php';

// Check if the user is logged in and is an admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch relevant statistics for the dashboard
// Example: Total number of doctors, patients, and appointments
$totalDoctors = 0; // Replace with actual query to count doctors
$totalPatients = 0; // Replace with actual query to count patients
$totalAppointments = 0; // Replace with actual query to count appointments

?>

<div class="main-content">
    <div class="container">
        <h1 class="mt-4">Admin Dashboard</h1>
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Total Doctors</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalDoctors; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Total Patients</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalPatients; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Total Appointments</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalAppointments; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>