<?php
session_start();
include 'config.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}

// Get the logged-in doctor's unique ID from session
$doctorID = $_SESSION['doctor_id'];

// Verify doctor exists and get their information
$doctor_verify_sql = "SELECT * FROM doctors WHERE DoctorID = ? AND Status = 'Active'";
$doctor_verify_stmt = $conn->prepare($doctor_verify_sql);
$doctor_verify_stmt->bind_param("s", $doctorID);
$doctor_verify_stmt->execute();
$doctor_verify_result = $doctor_verify_stmt->get_result();

if ($doctor_verify_result->num_rows === 0) {
    session_destroy();
    header("Location: doctor_login.php?error=invalid_session");
    exit();
}

$doctorInfo = $doctor_verify_result->fetch_assoc();

$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// Upcoming Appointments - ONLY for this logged-in doctor
$sql = "SELECT a.*, s.StartTime, s.EndTime, st.FirstName, st.LastName 
        FROM appointments a
        JOIN timeslots s ON a.SlotID = s.SlotID
        JOIN students st ON a.StudentID = st.StudentID
        WHERE a.DoctorID = ?
        AND a.AppointmentDate BETWEEN ? AND ?
        AND a.statusID IN (1, 2)
        ORDER BY a.AppointmentDate, s.StartTime";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $doctorID, $today, $week_end);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

// Patients Handled Count (Total) - ONLY for this doctor
$count_sql = "SELECT COUNT(*) AS count FROM appointments WHERE DoctorID = ? AND statusID = 3";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $doctorID);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$handled_count = $count_result->fetch_assoc()['count'];

// Today's Patients Count - ONLY for this doctor
$today_sql = "SELECT COUNT(*) AS count FROM appointments 
              WHERE DoctorID = ? 
              AND AppointmentDate = ? 
              AND statusID IN (2, 3)";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("ss", $doctorID, $today);
$today_stmt->execute();
$today_result = $today_stmt->get_result();
$today_count = $today_result->fetch_assoc()['count'];

// This Week's Patients Count - ONLY for this doctor
$week_sql = "SELECT COUNT(*) AS count FROM appointments 
             WHERE DoctorID = ? 
             AND AppointmentDate BETWEEN ? AND ? 
             AND statusID IN (2, 3)";
$week_stmt = $conn->prepare($week_sql);
$week_stmt->bind_param("sss", $doctorID, $week_start, $week_end);
$week_stmt->execute();
$week_result = $week_stmt->get_result();
$week_count = $week_result->fetch_assoc()['count'];

// This Month's Patients Count - ONLY for this doctor
$month_sql = "SELECT COUNT(*) AS count FROM appointments 
              WHERE DoctorID = ? 
              AND AppointmentDate BETWEEN ? AND ? 
              AND statusID IN (2, 3)";
$month_stmt = $conn->prepare($month_sql);
$month_stmt->bind_param("sss", $doctorID, $month_start, $month_end);
$month_stmt->execute();
$month_result = $month_stmt->get_result();
$month_count = $month_result->fetch_assoc()['count'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Dr. <?= htmlspecialchars($doctorInfo['FirstName']) ?> - Medical Clinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <!-- Keep all your existing CSS styles exactly as they are -->
  <style>
    /* Your existing CSS styles remain exactly the same */
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
    
    /* All your existing CSS remains the same - I won't repeat it all here for brevity */
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background-color: var(--surface-light);
        color: var(--text-dark);
        overflow-x: hidden;
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
        width: 250px; /* Slightly wider sidebar */
        background: var(--primary);
        transition: all 0.3s ease;
        position: fixed;
        height: 100vh;
        z-index: 100;
        box-shadow: var(--shadow-md);
    }
    
    .sidebar-collapsed {
        transform: translateX(-250px); /* Match the exact sidebar width */
    }
    
    .sidebar-header {
        padding: 20px;
        text-align: center;
    }
    
    .sidebar-logo {
        width: 70%; /* Increased from 60% */
        max-width: 140px; /* Increased max width */
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
        padding: 14px 18px; /* Increased padding */
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
        font-weight: 500;
        font-size: 1rem; /* Increased font size */
        line-height: 1.3; /* Better line height for wrapped text */
    }
    
    .sidebar-menu a:hover {
        background: var(--primary-light);
        padding-left: 22px; /* Adjusted for the new base padding */
    }
    
    .sidebar-menu a.active {
        background: var(--primary-light);
        border-right: 4px solid white;
    }
    
    .sidebar-menu i {
        margin-right: 12px; /* Slightly increased margin */
        font-size: 1.25rem; /* Larger icons */
        min-width: 24px; /* Wider fixed width for icons */
        text-align: center;
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
        margin-left: 0; /* Start with no margin */
    }
    
    .header-expanded {
        margin-left: 250px; /* Match the sidebar width */
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
    
    /* Enhanced toggle button styling */
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
        position: relative;
        z-index: 91; /* Ensure it's above other elements */
    }
    
    .toggle-sidebar:hover {
        background-color: var(--surface-light);
    }
    
    .toggle-sidebar:active {
        transform: scale(0.95);
    }
    
    .toggle-sidebar i {
        font-size: 1.5rem; /* Make the icon slightly larger */
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
    
    /* Notifications */
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
    
    /* Main Content */
    .main-content {
        margin-left: 0; /* Start with no margin */
        padding: 20px;
        transition: all 0.3s ease;
        background-color: var(--surface-light);
    }
    
    .main-expanded {
        margin-left: 250px; /* Match the sidebar width */
    }
    
    /* Sidebar Overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.5);
        z-index: 99;
        display: none;
    }
    
    /* Add these styles for the welcome banner */
    .welcome-banner {
        background: linear-gradient(145deg, #ffffff 0%, #f9fbff 100%);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(46, 125, 50, 0.08);
        padding: 32px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 30px;
        border: 1px solid rgba(46, 125, 50, 0.1);
        position: relative;
    }

    .welcome-banner .avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        color: white;
        box-shadow: 0 8px 25px rgba(46, 125, 50, 0.25);
        border: 4px solid white;
    }

    .welcome-banner h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        color: var(--primary);
    }

    .welcome-banner p {
        margin: 5px 0 0;
        color: var(--text-medium);
        font-size: 1rem;
    }
    
    /* Fix the existing stats container styling */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin-bottom: 30px;
    }

    .stat-box {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-box:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        color: var(--primary);
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--text-dark);
    }

    .stat-label {
        font-size: 1rem;
        color: var(--text-medium);
    }

    /* Card styling */
    .card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 30px;
    }

    .card h4 {
        margin-top: 0;
        margin-bottom: 20px;
        color: var(--primary);
        font-weight: 600;
        font-size: 1.3rem;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--surface-medium);
    }

    /* Improved table responsiveness */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
        border-radius: 8px;
    }

    .table {
        min-width: 800px; /* Ensures table has minimum width for scrolling */
    }

    /* Better card handling on different screens */
    .card {
        padding: clamp(15px, 4vw, 25px);
    }

    /* Responsive Stats Container */
    .stats-container {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* More flexible grid */
        gap: clamp(10px, 3vw, 24px); /* Responsive spacing */
    }

    .stat-box {
        padding: clamp(15px, 3vw, 24px);
    }

    .stat-value {
        font-size: clamp(1.5rem, 4vw, 2.2rem);
    }

    .stat-label {
        font-size: clamp(0.85rem, 2vw, 1rem);
        text-align: center;
    }

    /* Responsive welcome banner */
    .welcome-banner {
        padding: clamp(20px, 5vw, 32px);
        gap: clamp(12px, 3vw, 24px);
    }

    .welcome-banner .avatar {
        width: clamp(60px, 10vw, 80px);
        height: clamp(60px, 10vw, 80px);
        font-size: clamp(1.6rem, 3vw, 2.2rem);
    }

    .welcome-banner h2 {
        font-size: clamp(1.4rem, 4vw, 2rem);
        word-break: break-word;
    }

    /* Improved responsive font sizes */
    h4 {
        font-size: clamp(1.1rem, 3vw, 1.3rem);
    }

    /* Additional breakpoints for finer control */
    @media (max-width: 1200px) {
        .main-content {
            padding: 18px;
        }
        
        .stats-container {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 992px) {
        .main-content {
            padding: 15px;
        }
        
        .stats-container {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .stat-icon {
            font-size: 2.2rem;
            margin-bottom: 12px;
        }
        
        .print-btn {
            width: 50px;
            height: 50px;
            right: 20px;
            bottom: 20px;
        }
    }

    @media (max-width: 768px) {
        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .welcome-banner {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }
        
        .header-title {
            font-size: 1.3rem;
        }
        
        .card h4 {
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        
        /* Better status display on mobile */
        td:nth-child(6) {
            min-width: 80px;
        }
        
        .print-btn {
            width: 45px;
            height: 45px;
            right: 15px;
            bottom: 15px;
        }
        
        .print-btn i {
            font-size: 20px;
        }
    }

    @media (max-width: 576px) {
        .stats-container {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .stat-box {
            padding: 15px;
        }
        
        .welcome-banner {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .welcome-banner .avatar {
            width: 60px;
            height: 60px;
        }
        
        .welcome-banner p {
            font-size: 0.9rem;
        }
        
        .card {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        /* Better table presentation on tiny screens */
        .table-responsive {
            margin-left: -15px;
            margin-right: -15px;
            width: calc(100% + 30px);
            border-radius: 0;
        }
        
        .table th, .table td {
            padding: 8px 12px;
            font-size: 0.85rem;
        }
    }

    /* Better touch interactions for mobile */
    @media (hover: none) {
        .sidebar-menu a:hover {
            background: var(--primary);
            padding-left: 18px;
        }
        
        .stat-box:hover {
            transform: none;
            box-shadow: var(--shadow-sm);
        }
        
        .sidebar-menu a:active {
            background: var(--primary-light);
        }
        
        .stat-box:active {
            transform: translateY(-2px);
        }
    }

    /* Very small device optimizations */
    @media (max-width: 360px) {
        .header {
            padding: 10px;
        }
        
        .header-title {
            font-size: 1.1rem;
        }
        
        .welcome-banner h2 {
            font-size: 1.3rem;
        }
        
        .welcome-banner p {
            font-size: 0.8rem;
        }
        
        .table th, .table td {
            padding: 6px 8px;
            font-size: 0.8rem;
        }
    }

    /* Mobile-friendly table styles */
    @media (max-width: 576px) {
        .table-responsive table {
            border: 0;
        }
        
        .table-responsive table thead {
            display: none; /* Hide the table header on mobile */
        }
        
        .table-responsive table tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid var(--surface-medium);
            border-radius: 8px;
        }
        
        .table-responsive table td {
            display: block;
            text-align: right;
            border-bottom: 1px solid var(--surface-medium);
            position: relative;
            padding-left: 50%;
        }
        
        .table-responsive table td:last-child {
            border-bottom: 0;
        }
        
        .table-responsive table td::before {
            content: attr(data-label);
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            text-align: left;
            color: var(--text-dark);
        }
    }
  </style>
</head>
<body>

<!-- Your existing sidebar with updated menu items -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="img/GCLINIC.png" alt="Medical Clinic Logo" class="sidebar-logo">
    </div>
    <div class="sidebar-divider"></div>
    <!-- Updated sidebar menu to match doctor_student.php -->
    <ul class="sidebar-menu">
        <li><a href="doctor_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a></li>
        <li><a href="doctor_student.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_student.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span>My Appointments</span>
        </a></li>
        <li><a href="student_viewer.php" class="<?= basename($_SERVER['PHP_SELF']) === 'student_viewer.php' ? 'active' : '' ?>">
            <i class="bi bi-person-lines-fill"></i> <span>My Patients</span>
        </a></li>
        <li><a href="doctor_notes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_notes.php' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> <span>Patient Notes</span>
        </a></li>
        <li><a href="doctor_profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> <span>My Profile</span>
        </a></li>
        <li><a href="doctor_schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_schedule.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar3"></i> <span>My Schedule</span>
        </a></li>
        <li><a href="doctor_report.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_report.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up"></i> <span>My Reports</span>
        </a></li>
    </ul>
</aside>

<!-- Updated header with doctor-specific information -->
<header class="header header-expanded" id="header">
    <div class="d-flex align-items-center">
        <button class="toggle-sidebar me-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="header-title">Dashboard - Dr. <?= htmlspecialchars($doctorInfo['FirstName'] . ' ' . $doctorInfo['LastName']) ?></h1>
    </div>
    
    <div class="header-actions">
        <span class="text-muted me-3">
            <?= htmlspecialchars($doctorInfo['Specialization']) ?>
        </span>
        <a href="doctor_profile.php" class="btn btn-sm btn-outline-primary me-2">
            <i class="bi bi-person-circle"></i> Profile
        </a>
        <a href="doctor_logout.php" class="btn btn-sm btn-outline-danger me-2">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <button onclick="printDashboard()" class="btn btn-sm btn-light no-print">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</header>

<!-- Sidebar overlay -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- Updated main content with personalized welcome -->
<main class="main-content main-expanded" id="mainContent">
    <!-- Personalized welcome header -->
    <div class="welcome-banner">
        <div class="avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="welcome-text">
            <h2>Welcome, Dr. <?= htmlspecialchars($doctorInfo['FirstName']) ?></h2>
            <p><?= htmlspecialchars($doctorInfo['Specialization']) ?> - Your personalized dashboard</p>
            <small class="text-muted">
                Last login: <?= isset($_SESSION['login_time']) ? date('M d, Y h:i A', strtotime($_SESSION['login_time'])) : 'Unknown' ?>
            </small>
        </div>
    </div>

    <!-- Patient Stats Boxes - Now showing ONLY this doctor's data -->
    <div class="stats-container">
        <div class="stat-box today">
            <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
            <div class="stat-value"><?= $today_count ?></div>
            <div class="stat-label">My Patients Today</div>
        </div>
        
        <div class="stat-box week">
            <div class="stat-icon"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-value"><?= $week_count ?></div>
            <div class="stat-label">My Patients This Week</div>
        </div>
        
        <div class="stat-box month">
            <div class="stat-icon"><i class="bi bi-calendar-month"></i></div>
            <div class="stat-value"><?= $month_count ?></div>
            <div class="stat-label">My Patients This Month</div>
        </div>
        
        <div class="stat-box total">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?= $handled_count ?></div>
            <div class="stat-label">Total Completed</div>
        </div>
    </div>

    <div class="card">
        <h4><i class="bi bi-calendar-check me-2"></i>My Upcoming Appointments</h4>
        <p class="text-muted mb-3">Appointments scheduled for you from today through this week</p>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($appointments) > 0): ?>
                    <?php foreach ($appointments as $row): ?>
                        <tr>
                            <td data-label="Appointment ID"><?= $row['AppointmentID'] ?></td>
                            <td data-label="Patient Name"><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                            <td data-label="Date"><?= date('M d, Y', strtotime($row['AppointmentDate'])) ?></td>
                            <td data-label="Time Slot"><?= date('h:i A', strtotime($row['StartTime'])) . ' - ' . date('h:i A', strtotime($row['EndTime'])) ?></td>
                            <td data-label="Reason"><?= htmlspecialchars($row['Reason']) ?></td>
                            <td data-label="Status">
                                <?php
                                switch ($row['statusID']) {
                                    case 1: echo '<span class="badge bg-warning">Pending</span>'; break;
                                    case 2: echo '<span class="badge bg-success">Approved</span>'; break;
                                    case 3: echo '<span class="badge bg-primary">Completed</span>'; break;
                                    case 4: echo '<span class="badge bg-danger">Cancelled</span>'; break;
                                    case 5: echo '<span class="badge bg-warning">Cancel Requested</span>'; break;
                                    default: echo '<span class="badge bg-secondary">Unknown</span>';
                                }
                                ?>
                            </td>
                            <td data-label="Actions">
                                <a href="doctor_student.php?appointment_id=<?= $row['AppointmentID'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 40px;">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">No upcoming appointments scheduled for you</p>
                            <p class="text-muted">When patients book appointments with you, they'll appear here.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($appointments) > 0): ?>
            <div class="text-center mt-3">
                <a href="doctor_student.php" class="btn btn-primary">
                    <i class="bi bi-calendar-check me-2"></i>View All My Appointments
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <h4><i class="bi bi-lightning me-2"></i>Quick Actions</h4>
        <div class="row">
            <div class="col-md-3 mb-3">
                <a href="doctor_student.php" class="btn btn-outline-primary w-100 p-3">
                    <i class="bi bi-calendar-check d-block mb-2" style="font-size: 2rem;"></i>
                    Manage My Appointments
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="student_viewer.php" class="btn btn-outline-success w-100 p-3">
                    <i class="bi bi-people d-block mb-2" style="font-size: 2rem;"></i>
                    View My Patients
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="doctor_schedule.php" class="btn btn-outline-info w-100 p-3">
                    <i class="bi bi-calendar3 d-block mb-2" style="font-size: 2rem;"></i>
                    Update My Schedule
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="doctor_profile.php" class="btn btn-outline-warning w-100 p-3">
                    <i class="bi bi-person-gear d-block mb-2" style="font-size: 2rem;"></i>
                    Edit My Profile
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Keep all your existing JavaScript exactly as it is -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM Elements
        const sidebar = document.getElementById('sidebar');
        const header = document.getElementById('header');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        // Toggle Sidebar
        function toggleSidebar() {
            if (sidebar.classList.contains('sidebar-collapsed')) {
                // Expand sidebar
                sidebar.classList.remove('sidebar-collapsed');
                header.classList.add('header-expanded');
                mainContent.classList.add('main-expanded');
                sidebarOverlay.style.display = 'none';
            } else {
                // Collapse sidebar
                sidebar.classList.add('sidebar-collapsed');
                header.classList.remove('header-expanded');
                mainContent.classList.remove('main-expanded');
                
                if (window.innerWidth <= 992) {
                    sidebarOverlay.style.display = 'block';
                }
            }
            
            // Debug line - remove after testing
            console.log("Sidebar toggled, collapsed:", sidebar.classList.contains('sidebar-collapsed'));
        }
        
        // Set initial state based on screen size
        function setInitialState() {
            if (window.innerWidth <= 992) {
                sidebar.classList.add('sidebar-collapsed');
                header.classList.remove('header-expanded');
                mainContent.classList.remove('main-expanded');
            } else {
                // Ensure expanded classes are applied on larger screens
                sidebar.classList.remove('sidebar-collapsed');
                header.classList.add('header-expanded');
                mainContent.classList.add('main-expanded');
            }
        }
        
        // Toggle sidebar event
        sidebarToggle.addEventListener('click', toggleSidebar);
        
        // Handle overlay click
        sidebarOverlay.addEventListener('click', function() {
            if (!sidebar.classList.contains('sidebar-collapsed')) {
                toggleSidebar();
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.add('sidebar-collapsed');
                header.classList.remove('header-expanded');
                mainContent.classList.remove('main-expanded');
            }
        });
        
        // Print function
        window.printDashboard = function() {
            window.print();
        }
        
        // Set initial state
        setInitialState();
    });
    
    // Add responsive table behaviors for mobile
    document.addEventListener('DOMContentLoaded', function() {
        function setupResponsiveTables() {
            const tables = document.querySelectorAll('.table-responsive table');
            
            // Only apply to small screens
            if (window.innerWidth <= 576) {
                tables.forEach(table => {
                    // Get all headers
                    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
                    
                    // Get all data rows
                    const rows = table.querySelectorAll('tbody tr');
                    
                    // Add data attributes to each cell for mobile display
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        cells.forEach((cell, index) => {
                            if (headers[index]) {
                                cell.setAttribute('data-label', headers[index]);
                            }
                        });
                    });
                });
            }
        }
        
        // Initial setup
        setupResponsiveTables();
        
        // Re-setup on window resize
        window.addEventListener('resize', function() {
            setupResponsiveTables();
        });
    });
</script>
</body>
</html>
