<?php
include 'config.php';
require_once 'session_helper.php';

// Validate session
if (!validateSession()) {
    header('location:login.php');
    exit;
}

$student_id = getStudentID();
$user_data = getUserData();

// Log student ID for debugging
error_log("Retrieving notifications for StudentID: " . $student_id);

// Validate student ID format
if (!preg_match('/^PT-\d{8}-\d{4}$/', $student_id)) {
    error_log("Invalid StudentID format: " . $student_id);
    header('location:login.php');
    exit;
}

// Fetch notifications with additional validation
$notificationQuery = "SELECT * FROM notifications WHERE studentID = ? AND is_read = FALSE ORDER BY created_at DESC";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("s", $student_id);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();

// Log notification count
error_log("Found " . $notifications->num_rows . " notifications for StudentID: " . $student_id);

// Fetch appointments with doctor and time slot details
$query = "SELECT a.AppointmentID, a.AppointmentDate, a.Reason, 
                 d.FirstName, d.LastName,
                 ts.StartTime, ts.EndTime,
                 s.status_name
          FROM Appointments a 
          LEFT JOIN TimeSlots ts ON a.SlotID = ts.SlotID
          LEFT JOIN Doctors d ON a.DoctorID = d.DoctorID
          LEFT JOIN Status s ON a.statusID = s.statusID
          WHERE a.StudentID = ? 
          ORDER BY 
            (CASE 
                WHEN a.AppointmentDate = CURDATE() THEN 1
                WHEN a.AppointmentDate > CURDATE() THEN 2
                ELSE 3
            END),
            a.AppointmentDate DESC,
            ts.StartTime DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
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
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            padding-top: 70px;
            transition: margin-left 0.3s ease;
        }

        /* Table Styling */
        .table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #011f4b;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-primary {
            background: #1976d2;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
        }

        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
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

        .profile-container {
            max-width: 1100px;
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
        .profile-header h2 {
            color: #011f4b;
            font-size: 1.6rem;
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
        .badge {
            font-size: 1em;
            border-radius: 8px;
            padding: 0.5em 1em;
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.95em;
            border-radius: 6px;
        }
        @media (max-width: 992px) {
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
        <a href="studentHome.php"><i class="bi bi-house"></i> Home</a>
        <a href="doctors.php"><i class="bi bi-person-square"></i> Doctors</a>
        <a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a>
        <a href="schedule.php" class="active"><i class="bi bi-journal-arrow-down"></i> My Appointments</a>
        <a href="services.php"><i class="bi bi-journal-album"></i> Service</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="top-bar">
        <span>Medical Clinic Notify+</span>
        <div class="d-flex align-items-center">
            <div class="welcome-text">
                <i class="bi bi-person-circle"></i>
                Welcome, <?php echo htmlspecialchars($user_data['FirstName']); ?>
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
            <div class="profile-header" style="text-align:left;margin-bottom:1.5rem;">
                <h2 style="color:#011f4b;font-size:1.6rem;"><i class="bi bi-calendar2-check"></i> My Appointments</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Doctor</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th>Test Result</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('F j, Y', strtotime($row['AppointmentDate'])); ?></td>
                                <td><?php echo htmlspecialchars($row['Reason']); ?></td>
                                <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
                                <td><?php echo date('g:i A', strtotime($row['StartTime'])) . ' - ' . date('g:i A', strtotime($row['EndTime'])); ?></td>
                                <td>
                                    <?php
                                    $status = strtolower($row['status_name']);
                                    $badge = '';
                                    switch ($status) {
                                        case 'pending':
                                            $badge = '<span class="badge bg-warning text-dark px-3 py-2">Pending</span>';
                                            break;
                                        case 'approved':
                                            $badge = '<span class="badge bg-primary px-3 py-2">Approved</span>';
                                            break;
                                        case 'completed':
                                            $badge = '<span class="badge bg-success px-3 py-2">Completed</span>';
                                            break;
                                        case 'cancelled':
                                        case 'canceled':
                                            $badge = '<span class="badge bg-danger px-3 py-2">Cancelled</span>';
                                            break;
                                        default:
                                            $badge = '<span class="badge bg-secondary px-3 py-2">' . htmlspecialchars($row['status_name']) . '</span>';
                                    }
                                    echo $badge;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $testQuery = "SELECT FilePath, FileName FROM test_results WHERE appointmentID = ?";
                                    $testStmt = $conn->prepare($testQuery);
                                    $testStmt->bind_param("i", $row['AppointmentID']);
                                    $testStmt->execute();
                                    $testResult = $testStmt->get_result();
                                    if ($testResult->num_rows > 0) {
                                        $testData = $testResult->fetch_assoc();
                                        echo '<button type="button" class="btn btn-primary btn-sm" onclick="showTestResultModal(\'' . htmlspecialchars($testData['FilePath'], ENT_QUOTES) . '\', \'' . htmlspecialchars($testData['FileName'], ENT_QUOTES) . '\')">View</button>';
                                    } else {
                                        echo '<span style="color: gray;">No test result available</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($row['status_name'] == 'Pending' || $row['status_name'] == 'Approved'): ?>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="openCancellationModal(<?php echo $row['AppointmentID']; ?>)">
                                        <i class="bi bi-x-circle"></i> Request Cancellation
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Test Result Preview -->
    <div class="modal fade" id="testResultModal" tabindex="-1" aria-labelledby="testResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testResultModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="testResultModalBody">
                    <!-- File preview will be injected here -->
                </div>
                <div class="modal-footer">
                    <a id="downloadTestResultBtn" href="#" class="btn btn-success" download target="_blank">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Cancellation Reason -->
    <div class="modal fade" id="cancellationModal" tabindex="-1" aria-labelledby="cancellationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="cancellationForm" method="POST" action="request_cancellation.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancellationModalLabel">Request Appointment Cancellation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="modalAppointmentId">
                        <div class="mb-3">
                            <label for="cancellationReason" class="form-label">Reason for cancellation <span class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" id="cancellationReason" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            const topBar = document.querySelector('.top-bar');
            const bell = document.querySelector('.notification-bell');
            const dropdown = document.getElementById('notificationDropdown');
            const notifCount = document.querySelector('.notification-count');

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

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

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

        function showTestResultModal(filePath, fileName) {
            document.getElementById('testResultModalLabel').textContent = fileName;
            var ext = filePath.split('.').pop().toLowerCase();
            var body = document.getElementById('testResultModalBody');
            var downloadBtn = document.getElementById('downloadTestResultBtn');
            downloadBtn.href = filePath;
            downloadBtn.setAttribute('download', fileName);
            if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
                body.innerHTML = '<img src="' + filePath + '" alt="Test Result" class="img-fluid" style="max-width:100%;max-height:70vh;display:block;margin:auto;">';
            } else if (ext === "pdf") {
                body.innerHTML = '<iframe src="' + filePath + '" style="width:100%;height:70vh;border:none;"></iframe>';
            } else {
                body.innerHTML = '<div class="text-center">File type not supported for preview. <a href="' + filePath + '" download>Download</a></div>';
            }
            var modal = new bootstrap.Modal(document.getElementById('testResultModal'));
            modal.show();
        }

        function openCancellationModal(appointmentId) {
            document.getElementById('modalAppointmentId').value = appointmentId;
            document.getElementById('cancellationReason').value = '';
            var modal = new bootstrap.Modal(document.getElementById('cancellationModal'));
            modal.show();
        }

        // Add form validation
        document.getElementById('cancellationForm').addEventListener('submit', function(e) {
            var reason = document.getElementById('cancellationReason').value.trim();
            if (!reason) {
                e.preventDefault();
                alert('Please provide a reason for cancellation.');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>
