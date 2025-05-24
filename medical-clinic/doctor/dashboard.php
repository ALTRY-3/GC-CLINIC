<?php
include '../config/config.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['doctorID'])) {
    header('Location: ../auth/login.php');
    exit;
}

$doctor_id = $_SESSION['doctorID'];

// Fetch doctor information
$query = "SELECT * FROM doctors WHERE DoctorID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $doctor_data = $result->fetch_assoc();
} else {
    echo "No doctor data found.";
    exit;
}

// Fetch appointments for the doctor
$appointmentsQuery = "SELECT * FROM appointments WHERE doctorID = ? ORDER BY appointment_date DESC";
$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("s", $doctor_id);
$appointmentsStmt->execute();
$appointments = $appointmentsStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Welcome, Dr. <?php echo htmlspecialchars($doctor_data['name']); ?></h1>
        <h2>Your Appointments</h2>
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Appointment Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $appointments->fetch_assoc()): ?>
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