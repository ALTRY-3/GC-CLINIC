<?php
include '../config/config.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['doctorID'])) {
    header('location: ../auth/login.php');
    exit;
}

$doctor_id = $_SESSION['doctorID'];

// Create a new database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch the doctor's schedule
$query = "SELECT * FROM appointments WHERE doctorID = ? ORDER BY appointment_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch notifications for the doctor
$notificationQuery = "SELECT * FROM notifications WHERE doctorID = ? AND is_read = FALSE ORDER BY created_at DESC";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("s", $doctor_id);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedule</title>
    <link href="../public/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Doctor Schedule</h1>
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Appointment Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>