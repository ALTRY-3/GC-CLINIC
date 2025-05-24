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
        .top-bar {
            width: calc(100% - 260px);
            height: 65px;
            background-color: #2e7d32;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 30px;
            font-size: 1.4rem;
            font-weight: 600;
            margin-left: 260px;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 0 2px 15px rgba(46, 125, 50, 0.1);
            border-bottom: 2px solid #60ad5e;
            letter-spacing: 0.5px;
        }

        .top-bar span {
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        /* Profile Container Styles */
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px 0 rgba(25, 118, 210, 0.22), 0 2px 12px 0 rgba(1, 31, 75, 0.13);
            border: 1.5px solid #e3f0fc;
            border-left: 6px solid #1976d2;
            background: #f6faff;
            padding: 2.5rem 2rem 2rem 2rem !important;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #1976d2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .profile-header .profile-image i {
            font-size: 3rem;
            color: white;
        }

        .profile-header h2 {
            color: #011f4b;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .table {
            width: 100%;
            margin-bottom: 1.5rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            padding: 1rem;
            color: #011f4b;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .btn-primary {
            background: #1976d2;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-260px);
            }
            .sidebar.expanded {
                transform: translateX(0);
            }
            .toggle-btn {
                left: 20px;
            }
            .toggle-btn.expanded {
                left: 260px;
            }
            .top-bar {
                margin-left: 0;
                width: 100%;
            }
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 15px;
                padding: 1.5rem !important;
            }
            .table th, .table td {
                padding: 0.75rem;
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
    </style>
</head>
<body>
    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-double-right"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <img src="img/GCLINIC.png" alt="Logo">
        <div class="sidebar-divider"></div>
        <a href="studentHome.php" class="active"><i class="bi bi-house"></i> Home</a>
        <a href="doctors.php"><i class="bi bi-person-square"></i> Doctors</a>
        <a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a>
        <a href="schedule.php"><i class="bi bi-journal-arrow-down"></i> My Appointments</a>
        <a href="services.php"><i class="bi bi-journal-album"></i> Service</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="top-bar">
        <span>Medical Clinic Notify+</span>
        <div class="d-flex align-items-center">
            <div class="welcome-text">
                <i class="bi bi-person-circle"></i>
                Welcome, <?php echo htmlspecialchars(($student_data['FirstName'] ?? '') . (isset($student_data['LastName']) ? ' ' . $student_data['LastName'] : '')); ?>
            </div>
            <div class="notification-bell" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications" style="position: relative;">
                <i class="bi bi-bell-fill"></i>
                <?php if ($notifications->num_rows > 0): ?>
                    <span class="notification-count"><?php echo $notifications->num_rows; ?></span>
                <?php endif; ?>
                <div class="dropdown-menu notification-dropdown" id="notificationDropdown" style="display: none; position: absolute; right: 0; top: 40px; z-index: 3000;">
                    <div class="dropdown-header">Notifications</div>
                    <?php if ($notifications->num_rows > 0): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="dropdown-item notification-item" data-id="<?php echo $notif['notificationID']; ?>">
                                <span class="notif-icon"><i class="bi bi-info-circle-fill"></i></span>
                                <div class="notif-message">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                    <div class="notif-date"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'] ?? '')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notif">No new notifications.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h2>Student Profile</h2>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width: 40%;">Student ID</th>
                            <td><?php echo htmlspecialchars($student_data['StudentID'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td><?php echo htmlspecialchars((($student_data['FirstName'] ?? '') . ' ' . ($student_data['LastName'] ?? '')) ?: 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?php echo htmlspecialchars($student_data['dob'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?php echo htmlspecialchars($student_data['GENDER'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Number</th>
                            <td><?php echo htmlspecialchars($student_data['ContactNumber'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($student_data['email'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo htmlspecialchars($student_data['address'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Parent/Guardian</th>
                            <td><?php echo htmlspecialchars($student_data['parentGuardian'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Parent Contact</th>
                            <td><?php echo htmlspecialchars($student_data['parentContact'] ?? 'N/A'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                    <i class="bi bi-pencil-square me-2"></i>Update Profile
                </button>
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
                <form method="POST" action="update.php" id="updateProfileForm" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($student_data['FirstName'] ?? ''); ?>" required>
                                    <label for="firstName">First Name</label>
                                    <div class="invalid-feedback">Please enter first name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($student_data['LastName'] ?? ''); ?>" required>
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
                                <option value="Male" <?php echo ($student_data['GENDER'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($student_data['GENDER'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($student_data['GENDER'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <label for="gender">Gender</label>
                            <div class="invalid-feedback">Please select gender</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="contactNumber" name="contactNumber" value="<?php echo htmlspecialchars($student_data['ContactNumber'] ?? ''); ?>" required pattern="[0-9]{11}">
                            <label for="contactNumber">Contact Number (11 digits)</label>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="parentGuardian" name="parentGuardian" value="<?php echo htmlspecialchars($student_data['parentGuardian'] ?? ''); ?>" required>
                            <label for="parentGuardian">Parent/Guardian</label>
                            <div class="invalid-feedback">Please enter parent/guardian name</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="parentContact" name="parentContact" value="<?php echo htmlspecialchars($student_data['parentContact'] ?? ''); ?>" required pattern="[0-9]{11}">
                            <label for="parentContact">Parent/Guardian Contact (11 digits)</label>
                            <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password">
                            <label for="password">New Password (leave blank to keep current)</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
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
        });
    </script>
</body>
</html>
