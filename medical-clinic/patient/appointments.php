<?php
include '../config/config.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['patientID'])) {
    header('Location: ../auth/login.php');
    exit;
}

$patient_id = $_SESSION['patientID'];

// Create a new database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch appointments for the patient
$query = "SELECT a.*, d.name AS doctor_name FROM appointments a 
          JOIN doctors d ON a.doctor_id = d.id 
          WHERE a.patient_id = ? 
          ORDER BY a.date_time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="main-content">
    <h2>Your Appointments</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Doctor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="3">No appointments found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['date_time']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>