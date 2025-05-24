<?php
include '../config/config.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/sidebar.php';
session_start();

if (!isset($_SESSION['patientID'])) {
    header('Location: ../auth/login.php');
    exit;
}

$patient_id = $_SESSION['patientID'];

// Fetch patient data
$query = "SELECT * FROM patients WHERE PatientID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patient_data = $result->fetch_assoc();
} else {
    echo "No patient data found.";
    exit;
}

// Fetch appointments
$appointmentQuery = "SELECT * FROM appointments WHERE patientID = ? ORDER BY appointment_date DESC";
$appointmentStmt = $conn->prepare($appointmentQuery);
$appointmentStmt->bind_param("s", $patient_id);
$appointmentStmt->execute();
$appointments = $appointmentStmt->get_result();

?>

<div class="main-content">
    <h1>Welcome, <?php echo htmlspecialchars($patient_data['name']); ?></h1>
    <h2>Your Appointments</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($appointment = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
include '../includes/footer.php';
?>