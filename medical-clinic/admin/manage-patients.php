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

$query = "SELECT * FROM patients";
$result = $conn->query($query);

?>

<div class="main-content">
    <div class="container">
        <h2 class="mt-4">Manage Patients</h2>
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
                <?php while ($patient = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($patient['id']); ?></td>
                        <td><?php echo htmlspecialchars($patient['name']); ?></td>
                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                        <td><?php echo htmlspecialchars($patient['contactNumber']); ?></td>
                        <td>
                            <a href="edit-patient.php?id=<?php echo htmlspecialchars($patient['id']); ?>" class="btn btn-warning">Edit</a>
                            <a href="delete-patient.php?id=<?php echo htmlspecialchars($patient['id']); ?>" class="btn btn-danger">Delete</a>
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