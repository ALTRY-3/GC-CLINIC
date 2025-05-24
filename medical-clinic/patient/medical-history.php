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

// Fetch medical history for the patient
$query = "SELECT * FROM medical_history WHERE patientID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$medical_history = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $medical_history[] = $row;
    }
} else {
    $medical_history = null;
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="mt-4">Medical History</h2>
    <?php if ($medical_history): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Condition</th>
                    <th>Treatment</th>
                    <th>Doctor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medical_history as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['date']); ?></td>
                        <td><?php echo htmlspecialchars($record['condition']); ?></td>
                        <td><?php echo htmlspecialchars($record['treatment']); ?></td>
                        <td><?php echo htmlspecialchars($record['doctor']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No medical history found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>