<?php
// Start the session
session_start();

// Database connection
require_once 'db_connection.php'; // Include your database connection file

// If doctor is not logged in, use a fallback or demo doctor ID
$doctorId = isset($_SESSION['doctor_id']) ? $_SESSION['doctor_id'] : 'DOC-2025-0004'; // fallback

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch doctor information
$doctorQuery = "SELECT * FROM doctors WHERE DoctorID = ?";
$doctorStmt = $conn->prepare($doctorQuery);
$doctorStmt->bind_param("s", $doctorId); // use "s" if DoctorID is a string like "DOC-2025-0004"
$doctorStmt->execute();
$doctorResult = $doctorStmt->get_result();
$doctorInfo = $doctorResult->fetch_assoc();

// Get total appointments count
$totalAppointmentsQuery = "SELECT COUNT(*) as total FROM appointments WHERE DoctorID = ?";
$totalStmt = $conn->prepare($totalAppointmentsQuery);
$totalStmt->bind_param("s", $doctorId);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalAppointments = $totalResult->fetch_assoc()['total'];

// Get completed appointments count (status 3)
$completedAppointmentsQuery = "SELECT COUNT(*) as completed FROM appointments WHERE DoctorID = ? AND statusID = 3";
$completedStmt = $conn->prepare($completedAppointmentsQuery);
$completedStmt->bind_param("s", $doctorId);
$completedStmt->execute();
$completedResult = $completedStmt->get_result();
$completedAppointments = $completedResult->fetch_assoc()['completed'];

// Get cancelled appointments count (status 4)
$cancelledAppointmentsQuery = "SELECT COUNT(*) as cancelled FROM appointments WHERE DoctorID = ? AND statusID = 4";
$cancelledStmt = $conn->prepare($cancelledAppointmentsQuery);
$cancelledStmt->bind_param("s", $doctorId);
$cancelledStmt->execute();
$cancelledResult = $cancelledStmt->get_result();
$cancelledAppointments = $cancelledResult->fetch_assoc()['cancelled'];

// Get blocked dates count
$blockedDatesQuery = "SELECT COUNT(*) as blocked FROM blocked_dates WHERE DoctorID = ?";
$blockedStmt = $conn->prepare($blockedDatesQuery);
$blockedStmt->bind_param("s", $doctorId);
$blockedStmt->execute();
$blockedResult = $blockedStmt->get_result();
$blockedDatesCount = $blockedResult->fetch_assoc()['blocked'];

// Get recent appointments
$recentAppointmentsQuery = "SELECT a.*, s.status_name, st.FirstName, st.LastName 
                           FROM appointments a
                           JOIN status s ON a.statusID = s.statusID
                           JOIN students st ON a.StudentID = st.StudentID
                           WHERE a.DoctorID = ?
                           ORDER BY a.AppointmentDate DESC
                           LIMIT 5";
$recentStmt = $conn->prepare($recentAppointmentsQuery);
$recentStmt->bind_param("s", $doctorId);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();

// Get recent blocked dates
$recentBlockedDatesQuery = "SELECT * FROM blocked_dates 
                           WHERE DoctorID = ? 
                           ORDER BY BlockedDate DESC 
                           LIMIT 5";
$recentBlockedStmt = $conn->prepare($recentBlockedDatesQuery);
$recentBlockedStmt->bind_param("s", $doctorId);
$recentBlockedStmt->execute();
$recentBlockedResult = $recentBlockedStmt->get_result();

// Get most common cancellation reasons
$commonCancellationsQuery = "SELECT Reason, COUNT(*) as count 
                            FROM appointments 
                            WHERE DoctorID = ? AND statusID = 4 AND Reason IS NOT NULL 
                            GROUP BY Reason 
                            ORDER BY count DESC 
                            LIMIT 5";
$commonCancellationsStmt = $conn->prepare($commonCancellationsQuery);
$commonCancellationsStmt->bind_param("s", $doctorId);
$commonCancellationsStmt->execute();
$commonCancellationsResult = $commonCancellationsStmt->get_result();

// Close statements
$doctorStmt->close();
$totalStmt->close();
$completedStmt->close();
$cancelledStmt->close();
$blockedStmt->close();
$recentStmt->close();
$recentBlockedStmt->close();
$commonCancellationsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Clinic Appointment System - Doctor Report</title>
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
    
    /* Dashboard specific styles */
    .card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
      transition: transform 0.3s;
    }
    .card:hover {
      transform: translateY(-5px);
    }
    .stats-card {
      min-height: 140px;
    }
    .icon-container {
      font-size: 2.5rem;
      padding: 10px;
      border-radius: 50%;
      width: 70px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
    }
    .bg-total {
      background-color: #e3f2fd;
      color: #0d6efd;
    }
    .bg-completed {
      background-color: #e8f5e9;
      color: #2e7d32;
    }
    .bg-cancelled {
      background-color: #ffebee;
      color: #c62828;
    }
    .bg-upcoming {
      background-color: #fff8e1;
      color: #ff8f00;
    }
    .bg-blocked {
      background-color: #f3e5f5;
      color: #7b1fa2;
    }
    .dash-count {
      font-size: 2rem;
      font-weight: bold;
    }
    .status-badge {
      padding: 5px 10px;
      border-radius: 12px;
      font-weight: bold;
      font-size: 0.8rem;
    }
    .status-pending {
      background-color: #fff8e1;
      color: #ff8f00;
    }
    .status-approved {
      background-color: #e8f5e9;
      color: #2e7d32;
    }
    .status-completed {
      background-color: #e3f2fd;
      color: #0d6efd;
    }
    .status-cancelled {
      background-color: #ffebee;
      color: #c62828;
    }
    .status-requested {
      background-color: #f3e5f5;
      color: #7b1fa2;
    }
    
    /* Progress bar for cancellation reasons */
    .reason-progress {
      height: 10px;
      border-radius: 5px;
    }
    
    /* Print button styles */
    .print-btn {
      position: fixed;
      right: 25px;
      top: 15px;
      z-index: 2100;
      background-color: #fff;
      color: #2e7d32;
      border: none;
      border-radius: 5px;
      padding: 8px 16px;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(46, 125, 50, 0.2);
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .print-btn:hover {
      background-color: #dcedc8;
    }
    .print-btn i {
      font-size: 1.1rem;
    }
    
    /* Print styles */
    @media print {
      .sidebar, 
      .toggle-btn, 
      .sidebar-overlay, 
      .top-bar,
      .print-btn,
      .btn-outline-primary,
      .btn-success {
        display: none !important;
      }
      
      .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
      
      .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        break-inside: avoid !important;
      }
      
      .card:hover {
        transform: none !important;
      }
      
      body {
        font-size: 12pt;
        background-color: white !important;
      }
      
      .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
      }
      
      .page-break {
        page-break-before: always;
      }
    }
    
    .print-header {
      display: none;
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
    }
    @media (max-width: 768px) {
      .top-bar {
        font-size: 16px;
        height: 50px;
      }
      h1 {
        font-size: 1.8rem;
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
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
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

  <!-- Sidebar toggle button -->
  <button id="sidebarToggle" class="toggle-btn" aria-label="Toggle sidebar">
    <i class="bi bi-chevron-double-left"></i>
  </button>

  <!-- Print button -->
  <button id="printButton" class="print-btn">
    <i class="bi bi-printer"></i> Print Report
  </button>

  <!-- Sidebar overlay -->
  <div id="sidebarOverlay" class="sidebar-overlay"></div>

  <!-- Top bar -->
  <div class="top-bar">
    Clinic Appointment System - Doctor Report
  </div>

  <!-- Main content -->
  <div class="main-content">
    <div class="container-fluid">
      <!-- Print header (only visible when printing) -->
      <div class="print-header">
        <h2>Clinic Appointment System</h2>
        <h3>Doctor Report - <?php echo date('F d, Y'); ?></h3>
        <p>Dr. <?php echo htmlspecialchars($doctorInfo['FirstName'] . ' ' . $doctorInfo['LastName']); ?></p>
        <hr>
      </div>
      
      <div class="row mb-4">
        <div class="col-12">
          <h1 class="mb-3">Welcome, Dr. <?php echo htmlspecialchars($doctorInfo['LastName']); ?></h1>
          <p class="text-muted">Your appointment reports</p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-md-4 col-lg">
          <div class="card stats-card h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              <div class="icon-container bg-total">
                <i class="bi bi-calendar-check"></i>
              </div>
              <h5 class="card-title">Total Appointments</h5>
              <p class="dash-count"><?php echo $totalAppointments; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg">
          <div class="card stats-card h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              <div class="icon-container bg-completed">
                <i class="bi bi-check-circle"></i>
              </div>
              <h5 class="card-title">🟡 Completed</h5>
              <p class="dash-count"><?php echo $completedAppointments; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg">
          <div class="card stats-card h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              <div class="icon-container bg-cancelled">
                <i class="bi bi-x-circle"></i>
              </div>
              <h5 class="card-title">🔴 Cancelled</h5>
              <p class="dash-count"><?php echo $cancelledAppointments; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg">
          <div class="card stats-card h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              <div class="icon-container bg-blocked">
                <i class="bi bi-calendar-x"></i>
              </div>
              <h5 class="card-title">📅 Blocked Dates</h5>
              <p class="dash-count"><?php echo $blockedDatesCount; ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Recent Appointments -->
        <div class="col-lg-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-white">
              <h5 class="mb-0">Recent Appointments</h5>
            </div>
            <div class="card-body">
              <?php if ($recentResult->num_rows > 0): ?>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th class="d-print-none">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $recentResult->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo date('M d, Y', strtotime($row['AppointmentDate'])); ?></td>
                          <td><?php echo htmlspecialchars($row['FirstName']) . ' ' . htmlspecialchars($row['LastName']); ?></td>
                          <td>
                            <?php
                            $statusClass = '';
                            switch ($row['statusID']) {
                              case 1:
                                $statusClass = 'status-pending';
                                break;
                              case 2:
                                $statusClass = 'status-approved';
                                break;
                              case 3:
                                $statusClass = 'status-completed';
                                break;
                              case 4:
                                $statusClass = 'status-cancelled';
                                break;
                              case 5:
                                $statusClass = 'status-requested';
                                break;
                            }
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                              <?php echo htmlspecialchars($row['status_name']); ?>
                            </span>
                          </td>
                          <td class="d-print-none">
                            <a href="appointment_details.php?id=<?php echo $row['AppointmentID']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-center text-muted">No recent appointments found.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Recently Added Blocked Dates -->
        <div class="col-lg-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-white">
              <h5 class="mb-0">Recently Added Blocked Dates</h5>
            </div>
            <div class="card-body">
              <?php if ($recentBlockedResult->num_rows > 0): ?>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Date Added</th>
                        <th class="d-print-none">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $recentBlockedResult->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo date('M d, Y', strtotime($row['BlockedDate'])); ?></td>
                          <td><?php echo htmlspecialchars($row['Reason']); ?></td>
                          <td><?php echo date('M d, Y', strtotime($row['BlockedDate'])); ?></td>
                          <td class="d-print-none">
                            <a href="edit_blocked_date.php?id=<?php echo $row['BlockedDateID']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-center text-muted">No blocked dates found.</p>
              <?php endif; ?>
              <div class="text-center mt-3 d-print-none">
                <a href="doctor_schedule.php" class="btn btn-success">
                  <i class="bi bi-plus-circle"></i> Add Blocked Date
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Most Common Cancellation Reasons -->
      <div class="row">
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header bg-white">
              <h5 class="mb-0">Most Common Cancellation Reasons</h5>
            </div>
            <div class="card-body">
              <?php if ($commonCancellationsResult->num_rows > 0): ?>
                <?php 
                // Get the total count of cancellations for percentage calculation
                $totalCancellationsForPercent = $cancelledAppointments > 0 ? $cancelledAppointments : 1; // Avoid division by zero
                ?>
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Reason</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Distribution</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $commonCancellationsResult->fetch_assoc()): ?>
                        <?php 
                        $percentage = ($row['count'] / $totalCancellationsForPercent) * 100;
                        ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['Reason']); ?></td>
                          <td><?php echo $row['count']; ?></td>
                          <td><?php echo number_format($percentage, 1); ?>%</td>
                          <td class="w-50">
                            <div class="progress reason-progress">
                              <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-center text-muted">No cancellation data available.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Print timestamp footer - only visible when printing -->
      <div class="d-none d-print-block mt-5">
        <hr>
        <div class="row">
          <div class="col-6">
            <p class="small text-muted">Report generated: <?php echo date('Y-m-d H:i:s'); ?></p>
          </div>
          <div class="col-6 text-end">
            <p class="small text-muted">Doctor ID: <?php echo htmlspecialchars($doctorId); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Sidebar toggle and print script -->
  <script>
    // Sidebar functionality
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
        topBar.style.marginLeft = '0';
        topBar.style.width = '100%';
      } else {
        toggleBtn.setAttribute('aria-label', 'Collapse sidebar');
        mainContent.style.marginLeft = '240px';
        topBar.style.marginLeft = '240px';
        topBar.style.width = 'calc(100% - 240px)';
      }
    }

    toggleBtn.addEventListener('click', toggleSidebar);

    overlay.addEventListener('click', () => {
      sidebar.classList.add('collapsed');
      toggleBtn.classList.add('collapsed');
      overlay.classList.remove('active');
      mainContent.style.marginLeft = '0';
      topBar.style.marginLeft = '0';
      topBar.style.width = '100%';
      toggleBtn.setAttribute('aria-label', 'Expand sidebar');
    });
    
// Print functionality
document.getElementById('printButton').addEventListener('click', function() {
  // Prepare page for printing
  const originalTitle = document.title;
  document.title = "Doctor Report - " + new Date().toLocaleDateString();
  
  // Add any print-specific classes
  document.body.classList.add('printing-active');
  
  // Expand collapsed elements for printing
  const collapsibleElements = document.querySelectorAll('.collapse');
  collapsibleElements.forEach(el => {
    el.classList.add('show');
    el.setAttribute('data-print-expanded', 'true');
  });
  
  // Enhance print view
  const charts = document.querySelectorAll('.progress');
  charts.forEach(chart => {
    chart.style.height = '20px'; // Make progress bars larger for print
  });
  
  // Create any runtime print-only elements
  const printTimestamp = document.createElement('div');
  printTimestamp.className = 'print-timestamp d-none d-print-block';
  printTimestamp.innerHTML = `<p class="text-center text-muted mt-4">Report printed: ${new Date().toLocaleString()}</p>`;
  document.querySelector('.container-fluid').appendChild(printTimestamp);
  
  // Add doctor info for print
  const doctorName = "<?php echo htmlspecialchars($doctorInfo['FirstName'] . ' ' . $doctorInfo['LastName']); ?>";
  const doctorID = "<?php echo htmlspecialchars($doctorId); ?>";
  const printDoctorInfo = document.createElement('div');
  printDoctorInfo.className = 'print-doctor-info d-none d-print-block';
  printDoctorInfo.innerHTML = `
    <div class="text-center mb-4">
      <h4>Dr. ${doctorName}</h4>
      <p class="text-muted">ID: ${doctorID}</p>
    </div>
  `;
  document.querySelector('.print-header').appendChild(printDoctorInfo);
  
  // Initiate print dialog after brief delay to ensure rendering
  setTimeout(function() {
    window.print();
    
    // Clean up after print dialog closes
    setTimeout(function() {
      // Restore title
      document.title = originalTitle;
      
      // Remove print classes
      document.body.classList.remove('printing-active');
      
      // Restore collapsed elements
      document.querySelectorAll('[data-print-expanded="true"]').forEach(el => {
        el.classList.remove('show');
        el.removeAttribute('data-print-expanded');
      });
      
      // Restore chart styling
      charts.forEach(chart => {
        chart.style.height = '';
      });
      
      // Remove temporary print elements
      document.querySelectorAll('.print-timestamp').forEach(el => el.remove());
    }, 1000);
  }, 300);
});

// Handle browser-initiated print
window.addEventListener('beforeprint', function() {
  // Apply same enhancements as button click
  document.body.classList.add('printing-active');
  
  // Expand any collapsed elements
  document.querySelectorAll('.collapse').forEach(el => {
    el.classList.add('show');
    el.setAttribute('data-print-expanded', 'true');
  });
});

// Reset after browser-initiated print
window.addEventListener('afterprint', function() {
  // Remove print classes
  document.body.classList.remove('printing-active');
  
  // Restore collapsed state if not expanded by print button
  document.querySelectorAll('[data-print-expanded="true"]').forEach(el => {
    el.classList.remove('show');
    el.removeAttribute('data-print-expanded');
  });
});
</script>
</body>
</html