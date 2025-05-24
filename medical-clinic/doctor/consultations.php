<?php
include '../config/config.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['doctorID'])) {
    header('Location: ../auth/login.php');
    exit;
}

$doctor_id = $_SESSION['doctorID'];

// Create a new database connection
$db = new Database();
$conn = $db->getConnection();

// Fetch consultations for the logged-in doctor
$query = "SELECT c.*, p.name AS patient_name FROM consultations c 
          JOIN patients p ON c.patientID = p.PatientID 
          WHERE c.doctorID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all consultations
$consultations = [];
while ($row = $result->fetch_assoc()) {
    $consultations[] = $row;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <h2 class="mt-4">Consultations</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Consultation ID</th>
                    <th>Patient Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($consultations)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No consultations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($consultations as $consultation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($consultation['ConsultationID']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['date']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['time']); ?></td>
                            <td><?php echo htmlspecialchars($consultation['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>