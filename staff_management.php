<?php
// Start session
session_start();

// Include database connection
include('db_connection.php');

// Add this function at the top of the file after the database connection
function generateDoctorID($conn) {
    $year = date('Y');
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(DoctorID, '-', -1) AS UNSIGNED)) as max_num 
              FROM doctors 
              WHERE DoctorID LIKE 'DOC-$year-%'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $next_num = ($row['max_num'] ?? 0) + 1;
    return sprintf("DOC-%s-%04d", $year, $next_num);
}

// Fetch staff (doctors) details from the doctors table
$staffQuery = "SELECT d.DoctorID, d.FirstName, d.LastName, d.Specialization, d.Email, d.ContactNumber as Phone, d.Status, d.ImageFile,
               a.AppointmentID, a.AppointmentDate, a.Reason, a.statusID, a.TestResultFile, s.StudentID, s.FirstName AS StudentFirstName, s.LastName AS StudentLastName
               FROM doctors d
               LEFT JOIN appointments a ON d.DoctorID = a.DoctorID
               LEFT JOIN students s ON a.StudentID = s.StudentID
               GROUP BY d.DoctorID
               ORDER BY d.DoctorID DESC";
$staffResult = $conn->query($staffQuery);

// Check if the query was successful
if ($staffResult === false) {
    echo "Error fetching staff details: " . $conn->error;
    exit();
}

// Fetch the total number of appointments for each doctor
$appointmentQuery = "SELECT DoctorID, COUNT(*) as TotalAppointments FROM appointments GROUP BY DoctorID";
$appointmentResult = $conn->query($appointmentQuery);

// Check if the appointment query was successful
if ($appointmentResult === false) {
    echo "Error fetching appointments: " . $conn->error;
    exit();
}

// Store the total appointments in an associative array (DoctorID => TotalAppointments)
$appointmentsByDoctor = [];
while ($appointment = $appointmentResult->fetch_assoc()) {
    $appointmentsByDoctor[$appointment['DoctorID']] = $appointment['TotalAppointments'];
}

// Add these queries after the existing queries
$specializationQuery = "SELECT DISTINCT Specialization FROM doctors WHERE Specialization IS NOT NULL";
$specializationResult = $conn->query($specializationQuery);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_doctor':
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $specialization = $_POST['specialization'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $status = $_POST['status'];
                
                // Generate new doctor ID
                $doctorID = generateDoctorID($conn);

                $insertQuery = "INSERT INTO doctors (DoctorID, FirstName, LastName, Specialization, Email, ContactNumber, Status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("sssssss", $doctorID, $firstName, $lastName, $specialization, $email, $phone, $status);
                
                if ($stmt->execute()) {
                    $message = "Doctor added successfully!";
                    $messageType = "success";
                    // Refresh the page to show new data
                    header("Location: " . $_SERVER['PHP_SELF'] . "?message=added");
                    exit();
                } else {
                    $message = "Error adding doctor: " . $conn->error;
                    $messageType = "error";
                }
                break;

            case 'edit_doctor':
                $doctorID = $_POST['doctorID'];
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $specialization = $_POST['specialization'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $status = $_POST['status'];

                $updateQuery = "UPDATE doctors SET 
                              FirstName = ?, 
                              LastName = ?, 
                              Specialization = ?, 
                              Email = ?, 
                              ContactNumber = ?, 
                              Status = ? 
                              WHERE DoctorID = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sssssss", $firstName, $lastName, $specialization, $email, $phone, $status, $doctorID);
                
                if ($stmt->execute()) {
                    $message = "Doctor updated successfully!";
                    $messageType = "success";
                    // Refresh the page to show updated data
                    header("Location: " . $_SERVER['PHP_SELF'] . "?message=updated");
                    exit();
                } else {
                    $message = "Error updating doctor: " . $conn->error;
                    $messageType = "error";
                }
                break;

            case 'delete_doctor':
                $doctorID = $_POST['doctorID'];
                
                // First check if doctor has any appointments
                $checkQuery = "SELECT COUNT(*) as appointment_count FROM appointments WHERE DoctorID = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param("s", $doctorID);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['appointment_count'] > 0) {
                    $message = "Cannot delete doctor with existing appointments.";
                    $messageType = "error";
                } else {
                    // Start transaction
                    $conn->begin_transaction();
                    
                    try {
                        // First delete associated timeslots
                        $deleteTimeslotsQuery = "DELETE FROM timeslots WHERE DoctorID = ?";
                        $stmt = $conn->prepare($deleteTimeslotsQuery);
                        $stmt->bind_param("s", $doctorID);
                        $stmt->execute();
                        
                        // Then delete the doctor
                        $deleteDoctorQuery = "DELETE FROM doctors WHERE DoctorID = ?";
                        $stmt = $conn->prepare($deleteDoctorQuery);
                        $stmt->bind_param("s", $doctorID);
                        $stmt->execute();
                        
                        // If both operations successful, commit transaction
                        $conn->commit();
                        
                        $message = "Doctor deleted successfully!";
                        $messageType = "success";
                        // Refresh the page to show updated data
                        header("Location: " . $_SERVER['PHP_SELF'] . "?message=deleted");
                        exit();
                    } catch (Exception $e) {
                        // If any operation fails, rollback changes
                        $conn->rollback();
                        $message = "Error deleting doctor: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
                break;
        }
    }
}

// Handle URL messages
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'added':
            $message = "Doctor added successfully!";
            $messageType = "success";
            break;
        case 'updated':
            $message = "Doctor updated successfully!";
            $messageType = "success";
            break;
        case 'deleted':
            $message = "Doctor deleted successfully!";
            $messageType = "success";
            break;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General styles */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }
        /* Sidebar DESIGN */
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            background-color: #011f4b !important;
            color: white;
            padding-top: 20px;
            box-shadow: 2px 0 12px rgba(1, 31, 75, 0.10);
            transition: transform 0.3s ease;
            z-index: 2000;
            overflow-y: auto;
            left: 0;
            top: 0;
            display: block;
        }
        .sidebar-divider {
            border-bottom: 1.5px solid #23406a;
            margin: 18px 0 12px 0;
        }
        .sidebar.collapsed {
            transform: translateX(-240px);
            background-color: #011f4b !important;
        }
        .toggle-btn {
            position: fixed;
            left: 240px;
            top: 24px;
            background-color: #fff;
            color: #1976d2;
            border: none;
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(1,31,75,0.10);
            cursor: pointer;
            z-index: 1100;
            transition: left 0.3s, background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-btn:hover {
            background: #e3f0fc;
            color: #011f4b;
        }
        .toggle-btn.collapsed {
            left: 16px;
        }
        .toggle-btn i {
            font-size: 20px;
            font-weight: bold;
            transition: transform 0.3s, color 0.2s;
        }
        .toggle-btn.collapsed i {
            transform: rotate(-90deg) scale(1.1);
            color: #011f4b;
        }
        .toggle-btn.expanded i {
            transform: rotate(0deg) scale(1.1);
            color: #1976d2;
        }
        .sidebar img {
            width: 80%;
            height: auto;
            margin: 0 auto 10px;
            display: block;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            padding: 16px 24px;
            width: 100%;
            transition: background-color 0.2s, color 0.2s;
            font-size: 1.08rem;
            font-weight: 500;
        }
        .sidebar a i {
            margin-right: 14px;
            font-size: 1.25rem;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #e3f0fc;
            color: #1976d2;
            border-right: 6px solid #1976d2;
        }
        /* Top Bar Part */
        .top-bar {
            width: calc(100% - 240px);
            height: 60px;
            background-color: #011f4b;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 28px;
            font-size: 22px;
            font-weight: 600;
            margin-left: 240px;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(1, 31, 75, 0.08);
            border-bottom: 2px solid #23406a;
            letter-spacing: 0.5px;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
            padding-top: 70px;
            transition: all 0.3s ease;
        }

        h1, h2 {
            color: #011f4b;
            margin-bottom: 1.5rem;
        }

        h2 {
            font-weight: 600;
            font-size: 1.8rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(1, 31, 75, 0.1);
            margin-bottom: 2rem;
        }

        .table th {
            background-color: #011f4b;
            color: #fff;
            padding: 15px;
            font-weight: 500;
            text-align: left;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(37, 129, 196, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                background-color: #011f4b !important;
                left: 0;
                top: 0;
                display: block;
                z-index: 2000;
            }
            .sidebar.expanded {
                transform: translateX(0);
            }
            .toggle-btn {
                left: 0;
            }
            .toggle-btn.expanded {
                left: 240px;
            }
            .top-bar {
                margin-left: 0;
                width: 100%;
                font-size: 18px;
                padding: 0 15px;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                font-size: 16px;
                height: 50px;
            }
            h2 {
                font-size: 1.5rem;
            }
            .table th, .table td {
                padding: 10px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .top-bar {
                font-size: 14px;
                padding: 0 10px;
            }
            .main-content {
                padding: 10px;
            }
            h2 {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }
            .table th, .table td {
                padding: 8px;
                font-size: 0.85rem;
            }
            .sidebar a {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            .sidebar img {
                width: 70%;
            }
        }

        /* Table responsive styles */
        @media (max-width: 480px) {
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                box-shadow: none;
            }
            .table th, .table td {
                min-width: 120px;
            }
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.25);
            z-index: 1500;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* New styles for doctor management */
        .doctor-management {
            margin-top: 2rem;
        }
        
        .add-doctor-btn {
            background: #1976d2;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .add-doctor-btn:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #011f4b 0%, #024351 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating > .form-control {
            height: 45px;
            padding: 1rem 0.75rem;
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .form-select {
            height: 45px;
            padding: 0.75rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #357abd 0%, #2c6aa0 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: none;
        }
        
        .edit-btn {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .delete-btn {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .action-btn:hover {
            transform: none;
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            background: #011f4b;
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Form Field Styles */
        .form-floating > .form-control,
        .form-floating > .form-select {
            height: 48px;
            padding: 1rem 0.75rem;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            color: #000;
        }

        .form-floating > .form-control::placeholder {
            color: #666;
        }

        .form-floating > .form-control:focus,
        .form-floating > .form-select:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
            color: #000;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
            color: #666;
            font-size: 0.95rem;
        }

        /* Status Select Styles */
        .form-select {
            height: 48px;
            padding: 0.75rem;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #000;
            background-color: #fff;
            cursor: pointer;
        }

        .form-select:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
        }

        .form-select option {
            padding: 8px;
            color: #000;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        /* Form Validation Styles */
        .was-validated .form-control:valid,
        .was-validated .form-select:valid {
            border-color: #2e7d32;
            background-image: none;
            color: #000;
        }

        .was-validated .form-control:invalid,
        .was-validated .form-select:invalid {
            border-color: #d32f2f;
            background-image: none;
            color: #000;
        }

        .invalid-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            color: #d32f2f;
        }

        /* Remove form field transitions */
        .form-control,
        .form-select {
            transition: none !important;
        }

        .form-control:focus,
        .form-select:focus {
            transition: none !important;
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 1rem;
            height: 1rem;
            top: 50%;
            left: 50%;
            margin: -0.5rem 0 0 -0.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        /* Responsive Adjustments */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.75rem;
            }
            
            .modal-body {
                padding: 1.25rem;
            }
            
            .btn {
                padding: 0.625rem 1.25rem;
            }
        }

        /* Remove modal animations */
        .modal.fade {
            transition: none;
        }

        .modal.fade .modal-dialog {
            transform: none;
            transition: none;
        }

        .modal.show .modal-dialog {
            transform: none;
        }

        /* Update modal styles */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            background: #011f4b;
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Remove animation from buttons */
        .btn {
            transition: none;
        }

        .btn:hover {
            transform: none;
        }

        .doctor-avatar-initials {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            max-width: 40px !important;
            max-height: 40px !important;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
            line-height: 40px !important;
            border-radius: 50% !important;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
            padding: 0 !important;
        }
    </style>
</head>
<body>
    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-double-right"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <img src="MedicalClinicLogo.png" alt="Logo">
        <div class="sidebar-divider"></div>
        <a href="admin_profile.php" class="<?php echo $currentPage == 'admin_profile.php' ? 'active' : ''; ?>"><i class="bi bi-person-vcard"></i> Profile</a>
        <a href="staff_management.php" class="<?php echo $currentPage == 'staff_management.php' ? 'active' : ''; ?>"><i class="bi bi-person-lines-fill"></i> Staff Management</a>
        <a href="student_management.php" class="<?php echo $currentPage == 'student_management.php' ? 'active' : ''; ?>"><i class="bi bi-journal-text"></i> Users Management</a>
        <a href="admin_report.php" class="<?php echo $currentPage == 'admin_report.php' ? 'active' : ''; ?>"><i class="bi bi-bar-chart"></i> Reports</a>
        <a href="adminLogout.php" class="mt-auto d-flex align-items-center"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <span>Appointment Management System</span>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 text-center py-3 h-100">
                    <div class="mb-2"><i class="bi bi-people-fill" style="font-size:1.7rem;color:#1976d2;"></i></div>
                    <div class="fw-bold" style="font-size:1.05rem;">Total Doctors</div>
                    <div class="fs-5 text-primary"><?php echo $staffResult->num_rows; ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 text-center py-3 h-100">
                    <div class="mb-2"><i class="bi bi-calendar-check" style="font-size:1.7rem;color:#357abd;"></i></div>
                    <div class="fw-bold" style="font-size:1.05rem;">Total Appointments</div>
                    <div class="fs-5 text-info"><?php echo array_sum($appointmentsByDoctor); ?></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 text-center py-3 h-100">
                    <div class="mb-2"><i class="bi bi-exclamation-circle-fill" style="font-size:1.7rem;color:#f9a825;"></i></div>
                    <div class="fw-bold" style="font-size:1.05rem;">Doctors with 0 Appointments</div>
                    <div class="fs-5 text-warning"><?php echo $staffResult->num_rows - count(array_filter($appointmentsByDoctor)); ?></div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-0 p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                <h2 class="mb-0">Doctors</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="add-doctor-btn" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                        <i class="bi bi-plus-lg"></i> Add New Doctor
                    </button>
                    <div style="min-width:240px;max-width:400px;flex:1 1 240px;">
                        <div class="card shadow-sm border-0 p-2 mb-0" style="background:#f6faff;">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" id="doctorSearch" class="form-control border-start-0" placeholder="Search doctors...">
                                <button id="clearSearch" class="btn btn-outline-secondary d-none" type="button"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($staffResult->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="doctorsTable">
                        <thead class="sticky-top" style="background:#011f4b;color:#fff;z-index:1;">
                            <tr>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Total Appointments</th>
                                <th>Manage Timeslots</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $staffResult->data_seek(0);
                            while ($staff = $staffResult->fetch_assoc()):
                                $doctorID = $staff['DoctorID'];
                                $totalAppt = isset($appointmentsByDoctor[$doctorID]) ? $appointmentsByDoctor[$doctorID] : 0;
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center doctor-avatar-initials">
                                                <?php echo strtoupper(substr($staff['FirstName'], 0, 1) . substr($staff['LastName'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?></div>
                                                <small class="text-muted">ID: <?php echo htmlspecialchars($doctorID); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($staff['Specialization'] ?? 'Not specified'); ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($staff['Email'] ?? 'N/A'); ?></small>
                                            <small><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($staff['Phone'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo ($staff['Status'] ?? '') == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $staff['Status'] ?? 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $totalAppt; ?>
                                        <?php if ($totalAppt == 0): ?>
                                            <span class="badge bg-warning text-dark ms-2"><i class="bi bi-exclamation-circle"></i> None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-primary btn-sm manage-timeslot-btn" 
                                                data-doctor-id="<?php echo $doctorID; ?>"
                                                data-doctor-name="<?php echo htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?>">
                                            <i class="bi bi-clock"></i> Manage
                                        </button>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn edit-btn" title="Edit" data-doctor="<?php echo htmlspecialchars(json_encode($staff)); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="action-btn delete-btn" title="Delete" data-doctor-id="<?php echo $doctorID; ?>" data-doctor-name="<?php echo htmlspecialchars($staff['FirstName'] . ' ' . $staff['LastName']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-3 text-muted">No doctors found in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDoctorModalLabel">Add New Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="addDoctorForm" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="add_doctor">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                    <label for="firstName">First Name</label>
                                    <div class="invalid-feedback">Please enter first name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                    <label for="lastName">Last Name</label>
                                    <div class="invalid-feedback">Please enter last name</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="specialization" name="specialization" required>
                            <label for="specialization">Specialization</label>
                            <div class="invalid-feedback">Please enter specialization</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" required>
                            <label for="email">Email Address</label>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="phone" name="phone" required pattern="[0-9]{11}">
                            <label for="phone">Phone Number (11 digits)</label>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="form-floating mb-3">
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <label for="status">Status</label>
                            <div class="invalid-feedback">Please select a status</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Doctor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1" aria-labelledby="editDoctorModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDoctorModalLabel">Edit Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="editDoctorForm" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="edit_doctor">
                        <input type="hidden" name="doctorID" id="edit_doctorID">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_firstName" name="firstName" required>
                                    <label for="edit_firstName">First Name</label>
                                    <div class="invalid-feedback">Please enter first name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="edit_lastName" name="lastName" required>
                                    <label for="edit_lastName">Last Name</label>
                                    <div class="invalid-feedback">Please enter last name</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="edit_specialization" name="specialization" required>
                            <label for="edit_specialization">Specialization</label>
                            <div class="invalid-feedback">Please enter specialization</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                            <label for="edit_email">Email Address</label>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required pattern="[0-9]{11}">
                            <label for="edit_phone">Phone Number (11 digits)</label>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="form-floating mb-3">
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <label for="edit_status">Status</label>
                            <div class="invalid-feedback">Please select a status</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Doctor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDoctorModal" tabindex="-1" aria-labelledby="deleteDoctorModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDoctorModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span id="deleteDoctorName" class="fw-bold"></span>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
                    <form method="POST" action="" id="deleteDoctorForm">
                        <input type="hidden" name="action" value="delete_doctor">
                        <input type="hidden" name="doctorID" id="delete_doctorID">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger flex-grow-1">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeslot Management Modal -->
    <div class="modal fade" id="timeslotModal" tabindex="-1" aria-labelledby="timeslotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="timeslotModalLabel">Manage Timeslots for <span id="modalDoctorName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTimeslotForm" class="row g-3 mb-3">
                        <input type="hidden" name="doctorID" id="modalDoctorID">
                        <div class="col-md-4">
                            <select class="form-select" name="AvailableDay" required>
                                <option value="">Day</option>
                                <option>Monday</option>
                                <option>Tuesday</option>
                                <option>Wednesday</option>
                                <option>Thursday</option>
                                <option>Friday</option>
                                <option>Saturday</option>
                                <option>Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="time" class="form-control" name="StartTime" required>
                        </div>
                        <div class="col-md-3">
                            <input type="time" class="form-control" name="EndTime" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Add</button>
                        </div>
                    </form>
                    <div id="timeslotList"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Doctor search/filter
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('doctorSearch');
        const table = document.getElementById('doctorsTable');
        const clearBtn = document.getElementById('clearSearch');
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const nameCell = row.querySelector('td:first-child');
                if (nameCell.textContent.toLowerCase().includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            clearBtn.classList.toggle('d-none', searchInput.value === '');
        });
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('keyup'));
            searchInput.focus();
        });
    });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            const topBar = document.querySelector('.top-bar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            function setSidebarState() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.add('collapsed');
                    toggleBtn.classList.remove('expanded');
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                    sidebarOverlay.classList.remove('active');
                } else {
                    sidebar.classList.remove('collapsed');
                    toggleBtn.classList.add('expanded');
                    mainContent.style.marginLeft = '240px';
                    topBar.style.marginLeft = '240px';
                    topBar.style.width = 'calc(100% - 240px)';
                    sidebarOverlay.classList.remove('active');
                }
            }

            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                toggleBtn.classList.toggle('collapsed');
                toggleBtn.classList.toggle('expanded');
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                    sidebarOverlay.classList.remove('active');
                } else {
                    if (window.innerWidth <= 992) {
                        sidebarOverlay.classList.add('active');
                    }
                    mainContent.style.marginLeft = window.innerWidth > 992 ? '240px' : '0';
                    topBar.style.marginLeft = window.innerWidth > 992 ? '240px' : '0';
                    topBar.style.width = window.innerWidth > 992 ? 'calc(100% - 240px)' : '100%';
                }
            });

            sidebarOverlay.addEventListener('click', function() {
                if (!sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    toggleBtn.classList.remove('expanded');
                    toggleBtn.classList.add('collapsed');
                    sidebarOverlay.classList.remove('active');
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                }
            });

            window.addEventListener('resize', setSidebarState);
            setSidebarState();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap modals without animation
        const addDoctorModal = new bootstrap.Modal(document.getElementById('addDoctorModal'), {
            backdrop: 'static',
            keyboard: false
        });
        
        const editDoctorModal = new bootstrap.Modal(document.getElementById('editDoctorModal'), {
            backdrop: 'static',
            keyboard: false
        });
        
        const deleteDoctorModal = new bootstrap.Modal(document.getElementById('deleteDoctorModal'), {
            backdrop: 'static',
            keyboard: false
        });

        // Form validation for both add and edit forms
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, { passive: true });
        });

        // Clear form when add modal is closed
        document.getElementById('addDoctorModal').addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('addDoctorForm');
            form.reset();
            form.classList.remove('was-validated');
        });

        // Clear form when edit modal is closed
        document.getElementById('editDoctorModal').addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('editDoctorForm');
            form.classList.remove('was-validated');
        });

        // Phone number validation for both add and edit forms
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }
                e.target.value = value;
            });
        });

        // Edit Doctor Function
        window.editDoctor = function(doctor) {
            const editForm = document.getElementById('editDoctorForm');
            editForm.querySelector('#edit_doctorID').value = doctor.DoctorID;
            editForm.querySelector('#edit_firstName').value = doctor.FirstName;
            editForm.querySelector('#edit_lastName').value = doctor.LastName;
            editForm.querySelector('#edit_specialization').value = doctor.Specialization || '';
            editForm.querySelector('#edit_email').value = doctor.Email || '';
            editForm.querySelector('#edit_phone').value = doctor.Phone || '';
            editForm.querySelector('#edit_status').value = doctor.Status || 'Active';
            
            editDoctorModal.show();
        };

        // Delete Doctor Function
        window.deleteDoctor = function(doctorID, doctorName) {
            document.getElementById('delete_doctorID').value = doctorID;
            document.getElementById('deleteDoctorName').textContent = doctorName;
            deleteDoctorModal.show();
        };

        // Add click event listeners to the buttons
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const doctorData = JSON.parse(this.getAttribute('data-doctor'));
                editDoctor(doctorData);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const doctorID = this.getAttribute('data-doctor-id');
                const doctorName = this.getAttribute('data-doctor-name');
                deleteDoctor(doctorID, doctorName);
            });
        });
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Open modal and load timeslots
        document.querySelectorAll('.manage-timeslot-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const doctorID = this.getAttribute('data-doctor-id');
                const doctorName = this.getAttribute('data-doctor-name');
                document.getElementById('modalDoctorID').value = doctorID;
                document.getElementById('modalDoctorName').textContent = doctorName;
                loadTimeslots(doctorID);
                new bootstrap.Modal(document.getElementById('timeslotModal')).show();
            });
        });

        // Add timeslot
        document.getElementById('addTimeslotForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('manage_timeslot.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadTimeslots(formData.get('doctorID'));
                    this.reset();
                } else {
                    alert(data.error || 'Failed to add timeslot.');
                }
            });
        });
    });

    // Load timeslots for a doctor
    function loadTimeslots(doctorID) {
        fetch('manage_timeslot.php?doctorID=' + encodeURIComponent(doctorID))
        .then(r => r.json())
        .then(data => {
            let html = '<table class=\"table table-sm\"><thead><tr><th>Day</th><th>Start</th><th>End</th><th>Action</th></tr></thead><tbody>';
            if (data.timeslots && data.timeslots.length) {
                data.timeslots.forEach(slot => {
                    html += `<tr>
                        <td>${slot.AvailableDay}</td>
                        <td>${slot.StartTime}</td>
                        <td>${slot.EndTime}</td>
                        <td>
                            <button class=\"btn btn-danger btn-sm\" onclick=\"deleteTimeslot(${slot.SlotID}, '${doctorID}')\">Delete</button>
                        </td>
                    </tr>`;
                });
            } else {
                html += '<tr><td colspan=\"4\" class=\"text-center\">No timeslots found.</td></tr>';
            }
            html += '</tbody></table>';
            document.getElementById('timeslotList').innerHTML = html;
        });
    }

    // Delete timeslot
    function deleteTimeslot(slotID, doctorID) {
        if (confirm('Delete this timeslot?')) {
            fetch('manage_timeslot.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'delete=1&SlotID=' + encodeURIComponent(slotID)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadTimeslots(doctorID);
                } else {
                    alert(data.error || 'Failed to delete timeslot.');
                }
            });
        }
    }
    </script>
</body>
</html>
