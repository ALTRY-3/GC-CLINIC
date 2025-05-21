<?php
session_start();
include 'config.php';

// TEMPORARY: Hardcoded DoctorID for testing (replace with a real one)
$doctorID = 'DOC-2025-0001'; // or just 5, 7, etc., based on your `doctors` table

$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

// Upcoming Appointments
$sql = "SELECT a.*, s.StartTime, s.EndTime, st.name as studentName
        FROM appointments a
        JOIN timeslots s ON a.SlotID = s.SlotID
        JOIN students st ON a.StudentID = st.studentID
        WHERE a.DoctorID = ?
        AND a.AppointmentDate BETWEEN ? AND ?
        AND a.statusID IN (1, 2)
        ORDER BY a.AppointmentDate, s.StartTime";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("sss", $doctorID, $today, $week_end);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

// Patients Handled Count (Total) - Kept for reference but not displayed anymore
$count_sql = "SELECT COUNT(*) AS count FROM appointments WHERE DoctorID = ? AND statusID = 3";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $doctorID);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$handled_count = $count_result->fetch_assoc()['count'];

// Today's Patients Count
$today_sql = "SELECT COUNT(*) AS count FROM appointments 
              WHERE DoctorID = ? 
              AND AppointmentDate = ? 
              AND statusID IN (2, 3)";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("ss", $doctorID, $today);
$today_stmt->execute();
$today_result = $today_stmt->get_result();
$today_count = $today_result->fetch_assoc()['count'];

// This Week's Patients Count
$week_sql = "SELECT COUNT(*) AS count FROM appointments 
             WHERE DoctorID = ? 
             AND AppointmentDate BETWEEN ? AND ? 
             AND statusID IN (2, 3)";
$week_stmt = $conn->prepare($week_sql);
$week_stmt->bind_param("sss", $doctorID, $week_start, $week_end);
$week_stmt->execute();
$week_result = $week_stmt->get_result();
$week_count = $week_result->fetch_assoc()['count'];

// This Month's Patients Count
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
  <title>Clinic Appointment System - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      overflow-x: hidden;
    }
    .sidebar {
      width: 240px;
      height: 100vh;
      position: fixed;
      background-color: #2e7d32 !important;
      color: white;
      padding-top: 20px;
      box-shadow: 2px 0 12px rgba(46, 125, 50, 0.3);
      transition: transform 0.3s ease;
      z-index: 2000;
      overflow-y: auto;
      left: 0;
      top: 0;
      display: block;
    }
    .sidebar-divider {
      border-bottom: 1.5px solid #60ad5e;
      margin: 18px 0 12px 0;
    }
    .sidebar.collapsed {
      transform: translateX(-240px);
      background-color: #2e7d32 !important;
    }
    .toggle-btn {
      position: fixed;
      left: 240px;
      top: 24px;
      background-color: #fff;
      color: #2e7d32;
      border: none;
      width: 40px;
      height: 40px;
      padding: 0;
      border-radius: 50%;
      box-shadow: 0 2px 8px rgba(46, 125, 50, 0.3);
      cursor: pointer;
      z-index: 2100;
      transition: left 0.3s, background 0.2s, color 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .toggle-btn:hover {
      background: #dcedc8;
      color: #2e7d32;
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
      color: #2e7d32;
    }
    .toggle-btn.expanded i {
      transform: rotate(0deg) scale(1.1);
      color: #2e7d32;
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
    .sidebar a:hover,
    .sidebar a.active {
      background-color: #60ad5e;
      color: #fff;
      border-right: 6px solid #388e3c;
    }
    .sidebar-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0,0,0,0.5);
      z-index: 1500;
      display: none;
      transition: opacity 0.3s ease;
    }
    .sidebar-overlay.active {
      display: block;
    }
    .top-bar {
      width: calc(100% - 240px);
      height: 60px;
      background-color: #2e7d32;
      color: #fff;
      display: flex;
      align-items: center;
      padding: 0 28px;
      font-size: 22px;
      font-weight: 600;
      margin-left: 240px;
      justify-content: space-between;
      transition: all 0.3s ease;
      box-shadow: 0 2px 10px rgba(46, 125, 50, 0.1);
      border-bottom: 2px solid #60ad5e;
      letter-spacing: 0.5px;
    }
    .main-content {
      margin-left: 240px;
      padding: 20px;
      padding-top: 70px;
      transition: all 0.3s ease;
    }
    h1,
    h2 {
      color: #2e7d32;
      margin-bottom: 1.5rem;
    }
    h1 {
      font-size: 2rem;
      font-weight: 600;
    }
    .report-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(46, 125, 50, 0.1);
    }
    /* Stats boxes styling */
    .stats-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-box {
      flex: 1;
      min-width: 200px;
      background-color: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    }
    .stat-box.today {
      border-top: 4px solid #2196F3;
    }
    .stat-box.week {
      border-top: 4px solid #4CAF50;
    }
    .stat-box.month {
      border-top: 4px solid #FF9800;
    }
    .stat-box.total {
      border-top: 4px solid #9C27B0;
    }
    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      margin: 10px 0;
      color: #333;
    }
    .stat-label {
      font-size: 1rem;
      font-weight: 500;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .stat-icon {
      font-size: 1.8rem;
      margin-bottom: 10px;
    }
    .today .stat-icon {
      color: #2196F3;
    }
    .week .stat-icon {
      color: #4CAF50;
    }
    .month .stat-icon {
      color: #FF9800;
    }
    .total .stat-icon {
      color: #9C27B0;
    }
    /* Card styling */
    .card {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      padding: 20px;
      margin-bottom: 30px;
    }
    .card h4 {
      color: #2e7d32;
      margin-bottom: 20px;
      font-weight: 600;
    }
    .header {
      color: #2e7d32;
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 25px;
    }
    @media (max-width: 992px) {
      .sidebar {
        background-color: 0 0 20px rgba(46, 125, 50, 0.1) !important;
        left: 0;
        top: 0;
        display: block;
        z-index: 2000;
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
      .report-container {
        margin: 15px auto;
        padding: 15px;
      }
      .stats-container {
        flex-direction: column;
      }
      .stat-box {
        width: 100%;
      }
    }
    @media (max-width: 768px) {
      .top-bar {
        font-size: 16px;
        height: 50px;
      }
      h1 {
        font-size: 1.8rem;
      }
      .stat-value {
        font-size: 2rem;
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
      h1 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
      }
      .stat-value {
        font-size: 1.8rem;
      }
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    table th, table td {
      padding: 12px 15px;
      text-align: left;
    }
    table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #2e7d32;
    }
    table tbody tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    table tbody tr:hover {
      background-color: #f1f8e9;
    }
    /* Responsive table styles */
    @media screen and (max-width: 767px) {
      .table-responsive table {
        display: block;
        width: 100%;
      }
      .table-responsive thead, 
      .table-responsive tbody, 
      .table-responsive th, 
      .table-responsive td, 
      .table-responsive tr {
        display: block;
      }
      .table-responsive thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }
      .table-responsive tr {
        border: 1px solid #ddd;
        margin-bottom: 15px;
        border-radius: 8px;
        overflow: hidden;
      }
      .table-responsive td {
        border: none;
        border-bottom: 1px solid #eee;
        position: relative;
        padding-left: 50%;
        white-space: normal;
        text-align: left;
      }
      .table-responsive td:before {
        position: absolute;
        top: 12px;
        left: 15px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: 600;
        color: #2e7d32;
      }
      .table-responsive td:nth-of-type(1):before { content: "ID"; }
      .table-responsive td:nth-of-type(2):before { content: "Student Name"; }
      .table-responsive td:nth-of-type(3):before { content: "Date"; }
      .table-responsive td:nth-of-type(4):before { content: "Time Slot"; }
      .table-responsive td:nth-of-type(5):before { content: "Reason"; }
      .table-responsive td:nth-of-type(6):before { content: "Status"; }
    }
    
    /* Print specific styles */
    @media print {
      body {
        background-color: #fff;
      }
      .sidebar, 
      .toggle-btn, 
      .sidebar-overlay, 
      .top-bar,
      .print-btn,
      .no-print {
        display: none !important;
      }
      .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        padding-top: 10px !important;
      }
      .card {
        box-shadow: none;
        border: 1px solid #ddd;
      }
      .stats-container {
        margin-bottom: 15px;
      }
      .stat-box {
        box-shadow: none;
        border: 1px solid #ddd;
        transform: none !important;
      }
      .stat-box:hover {
        transform: none !important;
        box-shadow: none;
      }
      .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: bold;
        color: #2e7d32;
      }
      .print-date {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
        font-size: 14px;
        color: #666;
      }
      /* Ensure table is properly formatted for print */
      table {
        width: 100%;
        page-break-inside: auto;
      }
      tr {
        page-break-inside: avoid;
        page-break-after: auto;
      }
      thead {
        display: table-header-group;
      }
      tfoot {
        display: table-footer-group;
      }
    }
    
    /* Print header (only visible when printing) */
    .print-header, .print-date {
      display: none;
    }
    
    /* Print button styles */
    .print-btn {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background-color: #2e7d32;
      color: white;
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      cursor: pointer;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s, transform 0.2s;
    }
    
    .print-btn:hover {
      background-color: #388e3c;
      transform: scale(1.05);
    }
    
    .print-btn i {
      font-size: 24px;
    }
  </style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <img src="MedicalClinicLogo.png" alt="Logo" />
  <a href="doctor_dashboard.php" class="<?= $current_page === 'doctor_dashboard.php' ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> Dashboard Overview
  </a>
  <a href="doctor_student.php" class="<?= $current_page === 'doctor_student.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar-check"></i> Appointment Management
  </a>
    <a href="student_viewer.php" class="<?= basename($_SERVER['PHP_SELF']) === 'student_viewer.php' ? 'active' : '' ?>">
    <i class="bi bi-person-lines-fill"></i> Patient Records Viewer
  </a>
    <a href="doctor_notes.php" class="<?= $current_page === 'doctor_notes.php' ? 'active' : '' ?>">
    <i class="bi bi-journal-text"></i> Patient Notes
  </a>
  <a href="doctor_profile.php" class="<?= $current_page === 'doctor_profile.php' ? 'active' : '' ?>">
    <i class="bi bi-person-circle"></i> Doctor Profile
  </a>
  <a href="doctor_schedule.php" class="<?= $current_page === 'doctor_schedule.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar3"></i> Schedule Configuration
  </a>
  <a href="doctor_report.php" class="<?= $current_page === 'doctor_report.php' ? 'active' : '' ?>">
    <i class="bi bi-graph-up"></i> Reports & Analytics
  </a>
</div>

<!-- Sidebar toggle button and overlay -->
<button id="sidebarToggle" class="toggle-btn expanded" aria-label="Collapse sidebar">
  <i class="bi bi-chevron-left"></i>
</button>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<div class="top-bar">
  <div>Medical Clinic Notify+ - Student Management</div>
  <button onclick="printDashboard()" class="btn btn-sm btn-light no-print">
    <i class="bi bi-printer"></i> Print
  </button>
</div>

<div class="main-content">
    <!-- Print-only headers -->
    <div class="print-header">Medical Clinic Notify+ Dashboard Report</div>
    <div class="print-date">Generated on: <?= date('F d, Y') ?></div>
    
    <div class="header">Welcome, Doctor</div>

    <!-- Patient Stats Boxes -->
    <div class="stats-container">
        <div class="stat-box today">
            <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
            <div class="stat-value"><?= $today_count ?></div>
            <div class="stat-label">Patients Today</div>
        </div>
        
        <div class="stat-box week">
            <div class="stat-icon"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-value"><?= $week_count ?></div>
            <div class="stat-label">Patients This Week</div>
        </div>
        
        <div class="stat-box month">
            <div class="stat-icon"><i class="bi bi-calendar-month"></i></div>
            <div class="stat-value"><?= $month_count ?></div>
            <div class="stat-label">Patients This Month</div>
        </div>
    </div>

    <div class="card">
        <h4>Upcoming Appointments (Today to This Week)</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Student Name</th>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($appointments) > 0): ?>
                    <?php foreach ($appointments as $row): ?>
                        <tr>
                            <td><?= $row['AppointmentID'] ?></td>
                            <td><?= $row['studentName'] ?></td>
                            <td><?= date('M d, Y', strtotime($row['AppointmentDate'])) ?></td>
                            <td><?= date('h:i A', strtotime($row['StartTime'])) . ' - ' . date('h:i A', strtotime($row['EndTime'])) ?></td>
                            <td><?= htmlspecialchars($row['Reason']) ?></td>
                            <td>
                                <?php
                                switch ($row['statusID']) {
                                    case 1: echo "Pending"; break;
                                    case 2: echo "Approved"; break;
                                    case 3: echo "Completed"; break;
                                    case 4: echo "Cancelled"; break;
                                    case 5: echo "Cancel Requested"; break;
                                    default: echo "Unknown";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No upcoming appointments</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Print button (fixed position) -->
    <button class="print-btn" onclick="printDashboard()" aria-label="Print Dashboard">
      <i class="bi bi-printer"></i>
    </button>
</div>

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');
  const mainContent = document.querySelector('.main-content');
  const topBar = document.querySelector('.top-bar');

  function toggleSidebar() {
    sidebar.classList.toggle('collapsed');
    toggleBtn.classList.toggle('collapsed');
    overlay.classList.toggle('active');

    if(sidebar.classList.contains('collapsed')){
      toggleBtn.setAttribute('aria-label', 'Expand sidebar');
      mainContent.style.marginLeft = '0';
      if(topBar) {
        topBar.style.marginLeft = '0';
        topBar.style.width = '100%';
      }
    } else {
      toggleBtn.setAttribute('aria-label', 'Collapse sidebar');
      mainContent.style.marginLeft = '240px';
      if(topBar) {
        topBar.style.marginLeft = '240px';
        topBar.style.width = 'calc(100% - 240px)';
      }
    }
  }

  toggleBtn.addEventListener('click', toggleSidebar);

  overlay.addEventListener('click', () => {
    sidebar.classList.add('collapsed');
    toggleBtn.classList.add('collapsed');
    overlay.classList.remove('active');
    mainContent.style.marginLeft = '0';
    if(topBar) {
      topBar.style.marginLeft = '0';
      topBar.style.width = '100%';
    }
    toggleBtn.setAttribute('aria-label', 'Expand sidebar');
  });
  
  // Print function
  function printDashboard() {
    window.print();
  }
</script>

</body>
</html>