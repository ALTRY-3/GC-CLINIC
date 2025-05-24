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

$query = "SELECT * FROM doctors";
$result = $conn->query($query);

?>

<div class="main-content">
    <div class="container">
        <h2 class="mt-4">Manage Doctors</h2>
        <a href="add-doctor.php" class="btn btn-primary mb-3">Add New Doctor</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($doctor = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doctor['id']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['name']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['contact']); ?></td>
                        <td>
                            <a href="edit-doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-warning">Edit</a>
                            <a href="delete-doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this doctor?');">Delete</a>
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