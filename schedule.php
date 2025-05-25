<?php
include 'config.php';
session_start();

if (!isset($_SESSION['studentID'])) {
    header('location:login.php');
    exit;
}

$student_id = trim($_SESSION['studentID']);

// Fetch student data directly instead of using helper functions
$query = "SELECT * FROM students WHERE StudentID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

// Fetch notifications
$notificationQuery = "SELECT * FROM notifications WHERE studentID = ? AND is_read = FALSE ORDER BY created_at DESC";
$notificationStmt = $conn->prepare($notificationQuery);
$notificationStmt->bind_param("s", $student_id);
$notificationStmt->execute();
$notifications = $notificationStmt->get_result();

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
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #1b5e20;
            --secondary: #1565c0;
            --secondary-light: #5e92f3;
            --secondary-dark: #003c8f;
            --text-dark: #263238;
            --text-medium: #546e7a;
            --text-light: #78909c;
            --surface-light: #f5f7fa;
            --surface-medium: #e1e5eb;
            --surface-dark: #cfd8dc;
            --danger: #d32f2f;
            --success: #388e3c;
            --warning: #f57c00;
            --shadow-sm: 0 2px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 20px;
        }
        
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-light);
            color: var(--text-dark);
        }
        
        /* Layout */
        .app-container {
            display: grid;
            min-height: 100vh;
            grid-template-columns: auto 1fr;
            grid-template-rows: auto 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
        }
        
        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            width: 260px;
            background: var(--primary);
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }
        
        .sidebar-collapsed {
            transform: translateX(-260px);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
        }
        
        .sidebar-logo {
            width: 70%;
            transition: transform 0.3s;
        }
        
        .sidebar-logo:hover {
            transform: scale(1.05);
        }
        
        .sidebar-divider {
            border-bottom: 1px solid var(--primary-light);
            margin: 8px 20px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover {
            background: var(--primary-light);
            padding-left: 30px;
        }
        
        .sidebar-menu a.active {
            background: var(--primary-light);
            border-right: 4px solid white;
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            font-size: 1.2rem;
            transition: transform 0.2s;
        }
        
        .sidebar-menu a:hover i {
            transform: translateX(3px);
        }
        
        /* Header */
        .header {
            grid-area: header;
            background: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 90;
            transition: all 0.3s ease;
        }
        
        .header-expanded {
            margin-left: 260px;
        }
        
        .header-title {
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--primary);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .toggle-sidebar:hover {
            background: var(--surface-light);
        }
        
        .welcome-message {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: var(--text-medium);
        }
        
        .welcome-message i {
            color: var(--primary);
        }
        
        .notifications {
            position: relative;
        }
        
        .notification-btn {
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--primary);
            transition: all 0.2s;
            position: relative;
        }
        
        .notification-btn:hover {
            background: var(--surface-light);
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Main Content */
        .main-content {
            grid-area: main;
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .main-expanded {
            margin-left: 260px;
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            position: absolute;
            top: 45px;
            right: 0;
            width: 320px;
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            overflow: hidden;
            display: none;
            animation: fadeInDown 0.3s;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-header {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--surface-light);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: var(--surface-light);
        }
        
        .notification-icon {
            color: var(--primary);
            background: var(--surface-light);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-message {
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: var(--text-dark);
            line-height: 1.4;
        }
        
        .notification-date {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .no-notifications {
            padding: 30px 20px;
            text-align: center;
            color: var(--text-light);
        }
        
        /* Appointment Specific Styles */
        .profile-container {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .profile-header {
            margin-bottom: 20px;
        }
        
        .profile-header h2 {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--surface-light);
            font-weight: 600;
            color: var(--text-dark);
            padding: 12px 15px;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 15px;
        }
        
        .badge {
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
        }
        
        .btn-sm {
            font-weight: 500;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
        }
        
        /* Modals */
        .modal-content {
            border-radius: var(--radius-md);
            border: none;
            box-shadow: var(--shadow-lg);
        }
        
        .modal-header {
            background: var(--primary);
            color: white;
            border-bottom: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-260px);
            }
            
            .header, .main-content {
                margin-left: 0 !important;
            }
            
            .app-container {
                grid-template-columns: 1fr;
            }
            
            .toggle-sidebar {
                display: flex;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .header-title {
                font-size: 1.2rem;
            }
            
            .welcome-message span {
                display: none;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .notification-dropdown {
                width: 100%;
                max-width: 320px;
                right: -15px;
            }
            
            .table-responsive {
                border-radius: var(--radius-sm);
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="img/GCLINIC.png" alt="Medical Clinic Logo" class="sidebar-logo">
            </div>
            <div class="sidebar-divider"></div>
            <ul class="sidebar-menu">
                <li><a href="studentDashboard.php"><i class="bi bi-house"></i> Home</a></li>
                <li><a href="studentHome.php"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a></li>
                <li><a href="schedule.php" class="active"><i class="bi bi-journal-arrow-down"></i> My Appointments</a></li>
                <li><a href="services.php"><i class="bi bi-journal-album"></i> Service</a></li>
                <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Header -->
        <header class="header header-expanded" id="header">
            <div class="d-flex align-items-center">
                <button class="toggle-sidebar me-3" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">Medical Clinic Notify+</h1>
            </div>
            
            <div class="header-actions">
                <div class="welcome-message">
                    <i class="bi bi-person-circle"></i>
                    <span>Welcome, <?php echo htmlspecialchars($student_data['firstName'] ?? 'Student'); ?></span>
                </div>
                
                <div class="notifications">
                    <button class="notification-btn" id="notificationBtn">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($notifications->num_rows > 0): ?>
                            <span class="notification-count"><?php echo $notifications->num_rows; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <i class="bi bi-bell"></i> Notifications
                        </div>
                        <div class="notification-list">
                            <?php if ($notifications->num_rows > 0): ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="notification-item" data-id="<?php echo $notif['notificationID']; ?>">
                                        <div class="notification-icon">
                                            <i class="bi bi-info-circle"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                            <div class="notification-date"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'] ?? '')); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-notifications">
                                    <i class="bi bi-bell-slash mb-2"></i>
                                    <p>No new notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="main-content main-expanded" id="mainContent">
            <div class="profile-container">
                <div class="profile-header">
                    <h2><i class="bi bi-calendar2-check"></i> My Appointments</h2>
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
                                                $badge = '<span class="badge bg-warning text-dark">Pending</span>';
                                                break;
                                            case 'approved':
                                                $badge = '<span class="badge bg-primary">Approved</span>';
                                                break;
                                            case 'completed':
                                                $badge = '<span class="badge bg-success">Completed</span>';
                                                break;
                                            case 'cancelled':
                                            case 'canceled':
                                                $badge = '<span class="badge bg-danger">Cancelled</span>';
                                                break;
                                            default:
                                                $badge = '<span class="badge bg-secondary">' . htmlspecialchars($row['status_name']) . '</span>';
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
                                            echo '<span class="text-muted">No test result available</span>';
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
        </main>
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
            // DOM Elements
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            // Toggle Sidebar
            function toggleSidebar() {
                const isSidebarCollapsed = sidebar.classList.contains('sidebar-collapsed');
                
                if (isSidebarCollapsed) {
                    sidebar.classList.remove('sidebar-collapsed');
                    header.classList.add('header-expanded');
                    mainContent.classList.add('main-expanded');
                } else {
                    sidebar.classList.add('sidebar-collapsed');
                    header.classList.remove('header-expanded');
                    mainContent.classList.remove('main-expanded');
                }
            }
            
            // Set initial state based on screen size
            function setInitialState() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.add('sidebar-collapsed');
                    header.classList.remove('header-expanded');
                    mainContent.classList.remove('main-expanded');
                }
            }
            
            // Toggle sidebar event
            sidebarToggle.addEventListener('click', toggleSidebar);
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.add('sidebar-collapsed');
                    header.classList.remove('header-expanded');
                    mainContent.classList.remove('main-expanded');
                }
            });
            
            // Notification dropdown toggle
            notificationBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                notificationDropdown.style.display = 'none';
            });
            
            // Mark notification as read
            document.querySelectorAll('.notification-item').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const notifId = this.getAttribute('data-id');
                    
                    fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'notification_id=' + encodeURIComponent(notifId)
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Remove notification from list
                        this.remove();
                        
                        // Update count
                        const countElement = document.querySelector('.notification-count');
                        if (countElement) {
                            let count = parseInt(countElement.textContent, 10);
                            if (count > 1) {
                                countElement.textContent = count - 1;
                            } else {
                                countElement.remove();
                                const noNotif = document.createElement('div');
                                noNotif.className = 'no-notifications';
                                noNotif.innerHTML = '<i class="bi bi-bell-slash mb-2"></i><p>No new notifications</p>';
                                document.querySelector('.notification-list').innerHTML = '';
                                document.querySelector('.notification-list').appendChild(noNotif);
                            }
                        }
                    });
                });
            });
            
            // Set initial state
            setInitialState();
        });
        
        // Test Result Modal Functions
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
        
        // Cancellation Modal Functions
        function openCancellationModal(appointmentId) {
            document.getElementById('modalAppointmentId').value = appointmentId;
            document.getElementById('cancellationReason').value = '';
            var modal = new bootstrap.Modal(document.getElementById('cancellationModal'));
            modal.show();
        }
        
        // Cancellation Form Validation
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
