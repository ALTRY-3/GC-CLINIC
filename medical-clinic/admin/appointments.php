<?php
include '../config/config.php';
include '../config/database.php';
include '../includes/header.php';
include '../includes/sidebar.php';

session_start();
if (!isset($_SESSION['adminID'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$query = "SELECT * FROM appointments ORDER BY appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$appointments = $stmt->get_result();

?>

<div class="main-content">
    <div class="container">
        <h2 class="mt-4">Manage Appointments</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient Name</th>
                    <th>Doctor Name</th>
                    <th>Appointment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                    <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                    <td>
                        <a href="edit-appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-warning">Edit</a>
                        <a href="delete-appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>