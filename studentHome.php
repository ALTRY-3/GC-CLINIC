<?php
include 'config.php';
session_start();

if (!isset($_SESSION['studentID'])) {
    header('location:login.php');
    exit;
}

$student_id = trim($_SESSION['studentID']);
// Debug: Print the session ID
echo "<!-- Debug: Session Student ID: " . htmlspecialchars($student_id) . " -->";

// Fix the query to use prepared statement and proper column name
$query = "SELECT * FROM students WHERE StudentID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Print the query result
echo "<!-- Debug: Query executed. Number of rows: " . $result->num_rows . " -->";

// Fetch notifications
$notificationQuery = "SELECT * FROM notifications WHERE studentID = ? AND is_read = FALSE ORDER BY created_at DESC";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("s", $student_id);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();

if ($result) {
    $student_data = $result->fetch_assoc();
    if (!$student_data) {
        echo "<!-- Debug: No student data found for ID: " . htmlspecialchars($student_id) . " -->";
        echo "No student data found for this ID.";
    } else {
        echo "<!-- Debug: Student data found: " . print_r($student_data, true) . " -->";
    }
} else {
    echo "<!-- Debug: Query error: " . $conn->error . " -->";
    echo "Error fetching student data: " . $conn->error;
    exit;
}

// Debug: Print the final student data
echo "<!-- Debug: Final student_data array: " . print_r($student_data, true) . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
            width: 260px;
            height: 100vh;
            position: fixed;
            background-color: #2e7d32 !important;
            color: white;
            padding-top: 15px;
            box-shadow: 4px 0 15px rgba(46, 125, 50, 0.15);
            transition: transform 0.3s ease;
            z-index: 2000;
            overflow-y: hidden;
            left: 0;
            top: 0;
            display: block;
        }

        .sidebar img {
            width: 65%;
            height: auto;
            margin: 0 auto 15px;
            display: block;
            filter: none;
            transition: transform 0.3s ease;
        }

        .sidebar img:hover {
            transform: scale(1.05);
        }

        .sidebar-divider {
            border-bottom: 1.5px solid #60ad5e;
            margin: 12px 20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            padding: 14px 25px;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .sidebar a:hover {
            background: #60ad5e;
            color: #fff;
            padding-left: 30px;
        }

        .sidebar a:hover i {
            transform: translateX(3px);
        }

        .sidebar a.active {
            background: #60ad5e;
            color: #fff;
            border-right: 6px solid #388e3c;
        }

        /* Top Bar Part */
        .top-bar.custom-navbar {
            width: calc(100% - 260px);
            height: 65px;
            background-color: #2e7d32;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 30px;
            font-size: 1.15rem;
            font-weight: 400;
            margin-left: 260px;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: none;
            border-bottom: none;
            letter-spacing: 0.5px;
        }

        .navbar-title {
            font-size: 1.1em;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .navbar-user {
            position: relative;
            cursor: pointer;
        }

        .navbar-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 110%;
            min-width: 210px;
            background: #fff;
            color: #222;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(1,31,75,0.18);
            border: 1.5px solid #e3f0fc;
            z-index: 9999;
            font-size: 1rem;
            padding: 0.5rem 0;
        }

        .navbar-dropdown.show {
            display: block;
        }

        .navbar-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            color: #222;
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s;
        }

        .navbar-dropdown .dropdown-item:hover {
            background: #f4f8fd;
        }

        .navbar-dropdown .dropdown-divider {
            height: 1px;
            background: #e3f0fc;
            margin: 4px 0;
            border: none;
        }

        .navbar-dropdown .notification-bell {
            position: relative;
            background: none;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }

        .navbar-dropdown .notification-count {
            position: absolute;
            top: -6px;
            right: -10px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(255, 68, 68, 0.3);
        }

        .navbar-username {
            text-transform: uppercase;
            font-size: 1em;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            margin-left: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-bell:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .notification-bell i {
            font-size: 1.3rem;
            color: #fff;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(255, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 2px 5px rgba(255, 68, 68, 0.3);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 4px 8px rgba(255, 68, 68, 0.4);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 2px 5px rgba(255, 68, 68, 0.3);
            }
        }

        /* Toggle Button */
        .toggle-btn {
            position: fixed;
            left: 260px;
            top: 20px;
            background: #fff;
            color: #1976d2;
            border: none;
            width: 35px;
            height: 35px;
            padding: 0;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(1, 31, 75, 0.15);
            cursor: pointer;
            z-index: 1100;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-btn:hover {
            background: #e3f0fc;
        }

        .toggle-btn i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        /* Welcome Text */
        .welcome-text {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-right: 20px;
            display: flex;
            align-items: center;
        }

        .welcome-text i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            padding-top: 70px;
            transition: margin-left 0.3s ease;
        }

        .main-content {
            margin-left: 260px;
            padding: 0;
            min-height: 100vh;
            background: #f6faff;
        }

        /* Profile Header Bar */
        .profile-header-bar {
            background: #fff;
            display: flex;
            align-items: center;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .profile-photo-container {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            background: #e3f0fc;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #2e7d32;
            flex-shrink: 0;
        }

        .profile-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo-container i {
            font-size: 3rem;
            color: #2e7d32;
        }

        .profile-header-info {
            margin-left: 15px;
            flex-grow: 1;
        }

        .profile-header-info h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2e7d32;
        }

        .profile-id-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-top: 3px;
        }

        .edit-profile-btn {
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .edit-profile-btn:hover {
            background: #1b5e20;
            transform: translateY(-2px);
        }

        .profile-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 15px;
        }

        .info-section {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .info-header {
            background: #2e7d32;
            color: white;
            padding: 10px 15px;
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            padding: 15px;
        }

        .info-item {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 5px;
        }

        .info-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #333;
            word-break: break-word;
        }

        /* Make Medical Information section full width */
        .info-section:last-child {
            grid-column: 1 / -1;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .profile-header-bar {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }
            
            .profile-header-info {
                margin: 10px 0;
            }
            
            .edit-profile-btn {
                margin-top: 10px;
            }
        }

        @media (max-width: 576px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Media queries for responsiveness */
        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: repeat(8, 1fr);
            }
            
            .profile-photo-card {
                grid-column: span 8;
                grid-row: span 1;
            }
            
            .profile-card:nth-child(2),
            .profile-card:nth-child(3),
            .profile-card:nth-child(4),
            .profile-card:nth-child(5) {
                grid-column: span 8;
            }
        }

        @media (max-width: 576px) {
            .profile-header {
                text-align: center;
            }
            
            .card-header {
                padding: 12px 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .profile-title {
                font-size: 1.5rem;
            }
            
            .profile-action-card {
                padding: 15px;
            }
        }

        .notification-dropdown {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(1,31,75,0.18);
            max-height: 400px;
            min-width: 200px;
            width: 90vw;
            max-width: 270px;
            overflow-y: auto;
            padding: 0;
            border: 1.5px solid #e3f0fc;
            animation: fadeIn 0.2s;
            right: 0;
            left: auto;
            font-size: 1rem;
        }

        @media (max-width: 400px) {
            .notification-dropdown {
                min-width: 0;
                width: 98vw;
                max-width: 98vw;
                font-size: 0.95rem;
                padding: 0;
            }
            .notification-dropdown .dropdown-header {
                font-size: 1rem;
                padding: 10px 10px;
            }
            .notification-dropdown .dropdown-item {
                padding: 10px 10px;
                font-size: 0.93rem;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .notification-dropdown .dropdown-header {
            background: #1976d2;
            color: #fff;
            font-weight: 600;
            padding: 14px 18px;
            border-radius: 12px 12px 0 0;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        .notification-dropdown .dropdown-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 18px;
            border-bottom: 1px solid #f0f4fa;
            font-size: 0.98rem;
            background: #fff;
            transition: background 0.2s;
        }
        .notification-dropdown .dropdown-item:last-child {
            border-bottom: none;
        }
        .notification-dropdown .dropdown-item:hover {
            background: #f4f8fd;
        }
        .notification-dropdown .notif-icon {
            color: #1976d2;
            font-size: 1.3rem;
            margin-top: 2px;
        }
        .notification-dropdown .notif-message {
            flex: 1;
            color: #222;
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.4;
            word-break: break-word;
        }
        .notification-dropdown .notif-date {
            color: #888;
            font-size: 0.82rem;
            margin-top: 2px;
            font-weight: 400;
        }
        .notification-dropdown .no-notif {
            text-align: center;
            color: #aaa;
            padding: 30px 0;
            font-size: 1rem;
        }
        .change-password-modal-content {
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(1,31,75,0.18);
            padding: 0 0 0 0;
        }
        #changePasswordModal .modal-header {
            border-bottom: none;
        }
        #changePasswordModal .modal-title {
            font-size: 1.3rem;
        }
        #changePasswordModal .form-label {
            font-weight: 500;
        }
        #changePasswordModal .form-control {
            border-radius: 8px;
            font-size: 1rem;
        }
        #changePasswordModal .input-group .btn {
            border-radius: 0 8px 8px 0;
        }
        #changePasswordModal .btn-primary {
            border-radius: 8px;
            font-weight: 500;
            font-size: 1.08rem;
            background: #2563eb;
            border: none;
        }
        #changePasswordModal .btn-primary:disabled {
            background: #e0e0e0;
            color: #aaa;
            border: none;
        }
    </style>
</head>
<body>
    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-double-right"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <img src="img/GCLINIC.png" alt="Logo">
        <div class="sidebar-divider"></div>
        <a href="studentDashboard.php"><i class="bi bi-house"></i> Home</a>
        <a href="studentHome.php" class="active"><i class="bi bi-person"></i> Profile</a>
        <a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a>
        <a href="schedule.php"><i class="bi bi-journal-arrow-down"></i> My Appointments</a>
        <a href="services.php"><i class="bi bi-journal-album"></i> Service</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="top-bar custom-navbar">
        <div class="navbar-title">Student Information System</div>
        <div class="navbar-user" id="navbarUser">
            <i class="bi bi-person-circle"></i>
            <span class="navbar-username"><?php echo strtoupper(htmlspecialchars($student_data['name'] ?? '')); ?></span>
            <i class="bi bi-caret-down-fill" style="font-size: 0.9em; margin-left: 4px;"></i>
            <div class="navbar-dropdown" id="navbarDropdown">
                <button class="dropdown-item notification-bell" type="button" id="notificationDropdownBtn">
                    <i class="bi bi-bell-fill"></i>
                    Notifications
                    <?php if ($notifications->num_rows > 0): ?>
                        <span class="notification-count"><?php echo $notifications->num_rows; ?></span>
                    <?php endif; ?>
                </button>
                <hr class="dropdown-divider">
                <button class="dropdown-item" id="openChangePasswordModal" type="button">
                    <i class="bi bi-key"></i> Change Password
                </button>
            </div>
        </div>
    </div>

    <div class="main-content" style="margin-left:260px; padding:0; min-height:100vh; background:#f6faff;">
        <!-- Profile Header Bar -->
        <div class="profile-header-bar">
            <div class="profile-photo-container">
                <?php if (!empty($student_data['profilePhoto']) && file_exists($student_data['profilePhoto'])): ?>
                    <img src="<?php echo htmlspecialchars($student_data['profilePhoto']); ?>" alt="Profile Photo" class="profile-photo">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
            </div>
            <div class="profile-header-info">
                <h2><?php echo htmlspecialchars(trim(($student_data['firstName'] ?? '') . ' ' . ($student_data['lastName'] ?? ''))); ?></h2>
                <div class="profile-id-badge"><?php echo htmlspecialchars($student_data['studentID'] ?? ''); ?></div>
            </div>
            <button type="button" class="edit-profile-btn" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                <i class="bi bi-pencil-square"></i> Edit Profile
            </button>
        </div>

        <!-- Profile Content Grid -->
        <div class="profile-content">
            <!-- Personal Information -->
            <div class="info-section">
                <div class="info-header">
                    <i class="bi bi-person-badge"></i> Personal Information
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">College/Program</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['course'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['gender'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['address'] ?? ''); ?></div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="info-section">
                <div class="info-header">
                    <i class="bi bi-envelope"></i> Contact Information
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['email'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Alternate Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['altEmail'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['contactNumber'] ?? ''); ?></div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="info-section">
                <div class="info-header">
                    <i class="bi bi-shield-plus"></i> Emergency Information
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Parent/Guardian</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['parentGuardian'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Parent Contact</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['parentContact'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Emergency Contact</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['emergencyContactName'] ?? ''); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Relationship</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['emergencyContactRelationship'] ?? ''); ?></div>
                    </div>
                </div>
            </div>

            <!-- Medical Information -->
            <div class="info-section">
                <div class="info-header">
                    <i class="bi bi-heart-pulse"></i> Medical Information
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Blood Type</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['bloodType'] ?? 'Not specified'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Allergies</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['allergies'] ?? 'None'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Medical Conditions</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['medicalConditions'] ?? 'None'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Medications</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['medications'] ?? 'None'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">Update Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <form method="POST" action="update.php" id="updateProfileForm" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($student_data['firstName'] ?? ''); ?>" required>
                                    <label for="firstName">First Name</label>
                                    <div class="invalid-feedback">Please enter first name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($student_data['lastName'] ?? ''); ?>" required>
                                    <label for="lastName">Last Name</label>
                                    <div class="invalid-feedback">Please enter last name</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>" required>
                            <label for="email">Email Address</label>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($student_data['address'] ?? ''); ?>" required>
                            <label for="address">Address</label>
                            <div class="invalid-feedback">Please enter address</div>
                        </div>

                        <div class="form-floating mb-3">
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($student_data['gender']) && $student_data['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($student_data['gender']) && $student_data['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($student_data['gender']) && $student_data['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <label for="gender">Gender</label>
                            <div class="invalid-feedback">Please select gender</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" value="<?php echo htmlspecialchars($student_data['contactNumber'] ?? ''); ?>" required pattern="[0-9]{11}">
                            <label for="contactNumber">Contact Number (11 digits)</label>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="parentGuardian" name="parentGuardian" value="<?php echo htmlspecialchars($student_data['parentGuardian'] ?? ''); ?>" required>
                            <label for="parentGuardian">Parent/Guardian</label>
                            <div class="invalid-feedback">Please enter parent/guardian name</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="parentContact" name="parentContact" value="<?php echo htmlspecialchars($student_data['parentContact'] ?? ''); ?>" pattern="[0-9]{11}">
                            <label for="parentContact">Parent/Guardian Contact (11 digits)</label>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="emergencyContactName" name="emergencyContactName" value="<?php echo htmlspecialchars($student_data['emergencyContactName'] ?? ''); ?>">
                            <label for="emergencyContactName">Emergency Contact Name</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="emergencyContactRelationship" name="emergencyContactRelationship" value="<?php echo htmlspecialchars($student_data['emergencyContactRelationship'] ?? ''); ?>">
                            <label for="emergencyContactRelationship">Emergency Contact Relationship</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="emergencyContactNumber" name="emergencyContactNumber" value="<?php echo htmlspecialchars($student_data['emergencyContactNumber'] ?? ''); ?>">
                            <label for="emergencyContactNumber">Emergency Contact Number</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="bloodType" name="bloodType" value="<?php echo htmlspecialchars($student_data['bloodType'] ?? ''); ?>">
                            <label for="bloodType">Blood Type</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="allergies" name="allergies" value="<?php echo htmlspecialchars($student_data['allergies'] ?? ''); ?>">
                            <label for="allergies">Allergies</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="medicalConditions" name="medicalConditions" value="<?php echo htmlspecialchars($student_data['medicalConditions'] ?? ''); ?>">
                            <label for="medicalConditions">Medical Conditions</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="medications" name="medications" value="<?php echo htmlspecialchars($student_data['medications'] ?? ''); ?>">
                            <label for="medications">Medications</label>
                        </div>

                        <div class="mb-3 text-center">
                            <label for="profilePhoto" class="form-label" style="font-weight:500; color:#1976d2;">Profile Photo</label>
                            <input type="file" class="form-control" id="profilePhoto" name="profilePhoto" accept="image/*">
                            <div class="form-text">Max size: 2MB. JPG, PNG only.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content change-password-modal-content">
                <div class="modal-header" style="border-bottom: none;">
                    <h5 class="modal-title" id="changePasswordModalLabel" style="color:#2563eb;font-weight:600;font-size:1.3rem;">
                        <span style="font-size:1.5rem;font-weight:700;letter-spacing:1px;">***</span>Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size:1.3rem;"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2" style="color:#d32f2f;font-size:0.98rem;font-weight:500;">
                        All fields are required. Password must be at least eight (8) characters or more
                    </div>
                    <form id="changePasswordForm" autocomplete="off">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label" style="color:#d32f2f;font-weight:500;">Current password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" placeholder="Password" required minlength="8">
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" placeholder="Password" required minlength="8">
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" placeholder="Password" required minlength="8">
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary" id="submitChangePassword" disabled>Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            const topBar = document.querySelector('.top-bar');

            // Function to update sidebar state
            function updateSidebarState(isCollapsed) {
                if (isCollapsed) {
                    sidebar.style.transform = 'translateX(-260px)';
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                    toggleBtn.style.left = '20px';
                    toggleBtn.innerHTML = '<i class="bi bi-chevron-double-right"></i>';
                } else {
                    sidebar.style.transform = 'translateX(0)';
                    mainContent.style.marginLeft = '260px';
                    topBar.style.marginLeft = '260px';
                    topBar.style.width = 'calc(100% - 260px)';
                    toggleBtn.style.left = '260px';
                    toggleBtn.innerHTML = '<i class="bi bi-chevron-double-left"></i>';
                }
            }

            // Initial state based on screen size
            function setInitialState() {
                if (window.innerWidth <= 992) {
                    updateSidebarState(true);
                } else {
                    updateSidebarState(false);
                }
            }

            // Toggle button click handler
            toggleBtn.addEventListener('click', function() {
                const isCurrentlyCollapsed = sidebar.style.transform === 'translateX(-260px)';
                updateSidebarState(!isCurrentlyCollapsed);
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 992) {
                    updateSidebarState(true);
                } else {
                    updateSidebarState(false);
                }
            });

            // Set initial state
            setInitialState();

            // Form validation
            const updateProfileForm = document.getElementById('updateProfileForm');
            updateProfileForm.addEventListener('submit', function(event) {
                if (!updateProfileForm.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                updateProfileForm.classList.add('was-validated');
            }, { passive: true });

            // Phone number validation
            const phoneInputs = document.querySelectorAll('#updateProfileForm input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 11) {
                        value = value.slice(0, 11);
                    }
                    e.target.value = value;
                });
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            const bell = document.querySelector('.notification-bell');
            const dropdown = document.getElementById('notificationDropdown');
            const notifCount = document.querySelector('.notification-count');

            bell.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function() {
                dropdown.style.display = 'none';
            });

            // Mark notification as read on click
            document.querySelectorAll('.notification-item').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const notifId = this.getAttribute('data-id');
                    fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'notification_id=' + encodeURIComponent(notifId)
                    }).then(response => response.text()).then(data => {
                        // Remove the notification from the dropdown
                        this.remove();
                        // Update the count
                        let count = parseInt(notifCount.textContent, 10);
                        if (count > 1) {
                            notifCount.textContent = count - 1;
                        } else {
                            notifCount.remove();
                            dropdown.querySelector('.dropdown-header').insertAdjacentHTML('afterend', '<div class="no-notif">No new notifications.</div>');
                        }
                    });
                });
            });

            // Dropdown toggle for navbar user
            const navbarUser = document.getElementById('navbarUser');
            const navbarDropdown = document.getElementById('navbarDropdown');
            let dropdownOpen = false;
            navbarUser.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownOpen = !dropdownOpen;
                navbarDropdown.classList.toggle('show', dropdownOpen);
            });
            document.addEventListener('click', function() {
                if (dropdownOpen) {
                    navbarDropdown.classList.remove('show');
                    dropdownOpen = false;
                }
            });
            // Optional: handle notification dropdown click
            document.getElementById('notificationDropdownBtn').addEventListener('click', function(e) {
                e.stopPropagation();
                // You can show a modal or redirect to a notifications page here
                // For now, just alert
                alert('Show notifications here!');
            });

            // Change Password Modal logic
            document.addEventListener('DOMContentLoaded', function() {
                // Open modal on dropdown click
                document.getElementById('openChangePasswordModal').addEventListener('click', function(e) {
                    e.stopPropagation();
                    var modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                    modal.show();
                    // Close dropdown
                    document.getElementById('navbarDropdown').classList.remove('show');
                });

                // Toggle password visibility
                document.querySelectorAll('#changePasswordModal .toggle-password').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var input = this.parentElement.querySelector('input');
                        var icon = this.querySelector('i');
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.classList.remove('bi-eye-slash');
                            icon.classList.add('bi-eye');
                        } else {
                            input.type = 'password';
                            icon.classList.remove('bi-eye');
                            icon.classList.add('bi-eye-slash');
                        }
                    });
                });

                // Enable submit only if all fields are valid and passwords match
                var form = document.getElementById('changePasswordForm');
                var submitBtn = document.getElementById('submitChangePassword');
                var current = document.getElementById('currentPassword');
                var newP = document.getElementById('newPassword');
                var confirm = document.getElementById('confirmPassword');
                function validateChangePassword() {
                    var valid =
                        current.value.length >= 8 &&
                        newP.value.length >= 8 &&
                        confirm.value.length >= 8 &&
                        newP.value === confirm.value;
                    submitBtn.disabled = !valid;
                }
                [current, newP, confirm].forEach(function(input) {
                    input.addEventListener('input', validateChangePassword);
                });
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // TODO: Implement AJAX password change here
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                    setTimeout(function() {
                        submitBtn.textContent = 'Submit';
                        submitBtn.disabled = false;
                        var modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                        modal.hide();
                        alert('Password changed (demo only).');
                    }, 1200);
                });
            });
        });
    </script>
</body>
</html>
