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

// Fetch patients for the logged-in doctor
$query = "SELECT * FROM patients WHERE doctorID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all patients
$patients = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Include header and sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <h2 class="mt-4">Patients List</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No patients found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['id']); ?></td>
                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td><?php echo htmlspecialchars($patient['contactNumber']); ?></td>
                            <td>
                                <a href="view_patient.php?id=<?php echo htmlspecialchars($patient['id']); ?>" class="btn btn-info">View</a>
                                <a href="edit_patient.php?id=<?php echo htmlspecialchars($patient['id']); ?>" class="btn btn-warning">Edit</a>
                                <a href="delete_patient.php?id=<?php echo htmlspecialchars($patient['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this patient?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>