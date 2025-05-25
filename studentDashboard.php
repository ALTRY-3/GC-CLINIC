<?php
include 'config.php';
session_start();

if (!isset($_SESSION['studentID'])) {
    header('location:login.php');
    exit;
}

$student_id = $_SESSION['studentID'];
// Fetch student data
$student_query = "SELECT * FROM students WHERE studentID = ? LIMIT 1";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("s", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();
$student_stmt->close();

// Fetch next appointment
$appt_query = "SELECT * FROM appointments WHERE StudentID = ? AND AppointmentDate >= CURDATE() ORDER BY AppointmentDate, AppointmentID LIMIT 1";
$appt_stmt = $conn->prepare($appt_query);
$appt_stmt->bind_param("s", $student_id);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();
$next_appt = $appt_result->fetch_assoc();
$appt_stmt->close();

// Fetch unread notifications
$notif_query = "SELECT * FROM notifications WHERE studentID = ? AND is_read = 0 ORDER BY created_at DESC";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("s", $student_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$unread_count = $notif_result->num_rows;
$latest_notifs = [];
while (($row = $notif_result->fetch_assoc()) && count($latest_notifs) < 3) {
    $latest_notifs[] = $row;
}
$notif_stmt->close();

// Fetch appointment summary
$summary_query = "SELECT statusID, COUNT(*) as count FROM appointments WHERE StudentID = ? GROUP BY statusID";
$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->bind_param("s", $student_id);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$appt_summary = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
while ($row = $summary_result->fetch_assoc()) {
    $appt_summary[$row['statusID']] = $row['count'];
}
$summary_stmt->close();
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
        
        /* Dashboard Specific Styles */
        .welcome-banner { 
            background: #fff; 
            border-radius: 16px; 
            box-shadow: 0 8px 32px rgba(1,31,75,0.08); 
            padding: 2rem 2rem 1.5rem 2rem; 
            display: flex; 
            align-items: center; 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
        }
        
        .welcome-banner .avatar { 
            width: 70px; 
            height: 70px; 
            border-radius: 50%; 
            background: #e3f0fc; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 2.5rem; 
            color: var(--primary); 
        }
        
        .welcome-banner h2 { 
            margin: 0; 
            font-size: 2rem; 
            font-weight: 600; 
            color: var(--primary); 
        }
        
        .dashboard-cards { 
            display: flex; 
            gap: 1.5rem; 
            margin-bottom: 2rem; 
            flex-wrap: wrap; 
        }
        
        .dashboard-card { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.07); 
            padding: 1.5rem 1.2rem; 
            flex: 1 1 220px; 
            min-width: 220px; 
            display: flex; 
            flex-direction: column; 
            align-items: flex-start; 
        }
        
        .dashboard-card h4 { 
            font-size: 1.1rem; 
            font-weight: 600; 
            color: var(--primary); 
            margin-bottom: 0.5rem; 
        }
        
        .dashboard-card .stat { 
            font-size: 1.2rem; 
            font-weight: 500; 
            color: #011f4b; 
            margin-bottom: 0.5rem; 
        }
        
        .latest-notifications { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.07); 
            padding: 1.5rem 1.2rem; 
        }
        
        .latest-notifications h5 { 
            font-size: 1.1rem; 
            font-weight: 600; 
            color: var(--primary); 
            margin-bottom: 1rem; 
        }
        
        .latest-notifications ul { 
            list-style: none; 
            padding: 0; 
            margin: 0; 
        }
        
        .latest-notifications li { 
            padding: 0.5rem 0; 
            border-bottom: 1px solid #e3f0fc; 
            font-size: 0.98rem; 
            color: #222; 
        }
        
        .latest-notifications li:last-child { 
            border-bottom: none; 
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
            
            .dashboard-cards {
                flex-direction: column;
            }
            
            .dashboard-card {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
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
            
            .welcome-banner {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .welcome-banner h2 {
                font-size: 1.5rem;
            }
            
            .notification-dropdown {
                width: 100%;
                max-width: 320px;
                right: -15px;
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
                <li><a href="studentDashboard.php" class="active"><i class="bi bi-house"></i> Home</a></li>
                <li><a href="studentHome.php"><i class="bi bi-person"></i> Profile</a></li>
                <li><a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a></li>
                <li><a href="schedule.php"><i class="bi bi-journal-arrow-down"></i> My Appointments</a></li>
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
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-count"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <i class="bi bi-bell"></i> Notifications
                        </div>
                        <div class="notification-list">
                            <?php if (count($latest_notifs) > 0): ?>
                                <?php foreach ($latest_notifs as $notif): ?>
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
            <div class="welcome-banner">
                <div class="avatar">
                    <?php if (!empty($student_data['profilePhoto']) && file_exists($student_data['profilePhoto'])): ?>
                        <img src="<?php echo htmlspecialchars($student_data['profilePhoto']); ?>" alt="Profile Photo" class="profile-photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h2>Welcome, <?php echo htmlspecialchars($student_data['firstName'] ?? ''); ?>!</h2>
                    <p style="margin:0;color:#666;font-size:1.05rem;">Here's what's happening with your clinic account.</p>
                </div>
            </div>
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <h4>Next Appointment</h4>
                    <div class="stat">
                        <?php if ($next_appt): ?>
                            <?php echo date('M d, Y', strtotime($next_appt['AppointmentDate'])); ?><br>
                            <?php echo htmlspecialchars($next_appt['Reason']); ?>
                        <?php else: ?>
                            <span style="color:#888;">No upcoming</span>
                        <?php endif; ?>
                    </div>
                    <a href="schedule.php" class="btn btn-outline-success btn-sm">View Details</a>
                </div>
                <div class="dashboard-card">
                    <h4>Notifications</h4>
                    <div class="stat"><?php echo $unread_count; ?> unread</div>
                    <a href="#" class="btn btn-outline-primary btn-sm" id="viewAllNotifications">View All</a>
                </div>
                <div class="dashboard-card">
                    <h4>Book Appointment</h4>
                    <a href="appointment.php" class="btn btn-success btn-sm">Book Now</a>
                </div>
                <div class="dashboard-card">
                    <h4>Appointment Summary</h4>
                    <div class="stat" style="font-size:0.98rem;">
                        Pending: <?php echo $appt_summary[1]; ?> <br>
                        Approved: <?php echo $appt_summary[2]; ?> <br>
                        Completed: <?php echo $appt_summary[3]; ?> <br>
                        Cancelled: <?php echo $appt_summary[4]; ?>
                    </div>
                    <a href="schedule.php" class="btn btn-outline-secondary btn-sm">View All</a>
                </div>
            </div>
            <div class="latest-notifications" style="margin-bottom:2rem;">
                <h5 style="color:#2e7d32;">Clinic News & Announcements</h5>
                <ul style="list-style:none;padding:0;margin:0;">
                    <li style="padding:0.5rem 0;border-bottom:1px solid #e3f0fc;font-size:0.98rem;color:#222;">
                        <strong>May 2025:</strong> The clinic will be closed on June 1 for facility maintenance. Please plan your appointments accordingly.
                    </li>
                    <li style="padding:0.5rem 0;border-bottom:1px solid #e3f0fc;font-size:0.98rem;color:#222;">
                        <strong>Oral Health Month:</strong> Free dental check-ups for all students every Friday this month!
                    </li>
                    <li style="padding:0.5rem 0;font-size:0.98rem;color:#222;">
                        <strong>New Service:</strong> We now offer digital dental records. Ask your dentist for more info!
                    </li>
                </ul>
            </div>
            <div class="latest-notifications">
                <h5>Latest Notifications</h5>
                <ul>
                    <?php if (count($latest_notifs) > 0): ?>
                        <?php foreach ($latest_notifs as $notif): ?>
                            <li><?php echo htmlspecialchars($notif['message']); ?> <span style="color:#888;font-size:0.92em;float:right;"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></span></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="color:#888;">No recent notifications.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </main>
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
            const viewAllNotifications = document.getElementById('viewAllNotifications');
            
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

            // View All Notifications button
            if (viewAllNotifications) {
                viewAllNotifications.addEventListener('click', function(e) {
                    e.preventDefault();
                    notificationDropdown.style.display = 'block';
                });
            }
            
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
    </script>
</body>
</html>