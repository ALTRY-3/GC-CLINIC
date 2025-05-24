<?php
include '../config/config.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['patientID'])) {
    header('Location: ../auth/login.php');
    exit;
}

$patient_id = $_SESSION['patientID'];
$db = new Database();
$conn = $db->getConnection();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h2>Profile Information</h2>
        <div class="profile-container">
            <h3><?php echo htmlspecialchars($patient_data['name']); ?></h3>
            <p>Email: <?php echo htmlspecialchars($patient_data['email']); ?></p>
            <p>Address: <?php echo htmlspecialchars($patient_data['address']); ?></p>
            <p>Contact Number: <?php echo htmlspecialchars($patient_data['contactNumber']); ?></p>
            <p>Gender: <?php echo htmlspecialchars($patient_data['gender']); ?></p>
            <p>Date of Birth: <?php echo htmlspecialchars($patient_data['dob']); ?></p>
            <a href="edit-profile.php" class="btn btn-primary">Edit Profile</a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>