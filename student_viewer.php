<?php
include 'config.php';

$searchTerm = $_GET['search'] ?? '';
$studentID = $_GET['studentID'] ?? '';

// Get student list for search dropdown
$studentSql = "SELECT StudentID, CONCAT(FirstName, ' ', middleInitial, '. ', LastName) AS student_name 
               FROM students 
               ORDER BY LastName, FirstName";
$studentResult = $conn->query($studentSql);

$students = [];
while ($row = $studentResult->fetch_assoc()) {
    $students[] = $row;
}

// Get specific student's appointment history
$appointmentHistory = [];
if (!empty($studentID)) {
    $sql = "SELECT a.AppointmentID, a.AppointmentDate, a.Reason, a.Notes,
                   t.StartTime, t.EndTime,
                   s.Status_name AS status_name,
                   CONCAT(d.FirstName, ' ', d.LastName) as doctor_name
            FROM appointments a
            JOIN timeslots t ON a.SlotID = t.SlotID
            JOIN status s ON a.StatusID = s.StatusID
            LEFT JOIN doctors d ON t.DoctorID = d.DoctorID
            WHERE a.StudentID = ?
            ORDER BY a.AppointmentDate DESC, t.StartTime DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Format time and date
        $row['formatted_date'] = date("F d, Y", strtotime($row['AppointmentDate']));
        $row['time'] = date("h:i A", strtotime($row['StartTime'])) . ' - ' . date("h:i A", strtotime($row['EndTime']));
        $appointmentHistory[] = $row;
    }
    $stmt->close();
    
    // Get student info
    $studentInfoSql = "SELECT CONCAT(FirstName, ' ', middleInitial, '. ', LastName) AS full_name, 
                              StudentID, Email, ContactNumber, dob, course, year
                       FROM students 
                       WHERE StudentID = ?";
    $studentStmt = $conn->prepare($studentInfoSql);
    $studentStmt->bind_param("i", $studentID);
    $studentStmt->execute();
    $studentInfo = $studentStmt->get_result()->fetch_assoc();
    $studentStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Clinic Appointment System - Patient Records</title>
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
    .record-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(46, 125, 50, 0.1);
    }
    .card {
      border: none;
      box-shadow: 0 0 15px rgba(46, 125, 50, 0.1);
      border-radius: 10px;
      margin-bottom: 20px;
    }
    .card-header {
      background-color: #e8f5e9;
      border-bottom: 2px solid #a5d6a7;
      font-weight: 600;
      color: #2e7d32;
    }
    .btn-primary {
      background-color: #2e7d32;
      border-color: #2e7d32;
    }
    .btn-primary:hover {
      background-color: #388e3c;
      border-color: #388e3c;
    }
    .profile-info {
      background-color: #f1f8e9;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    .profile-label {
      font-weight: 600;
      color: #2e7d32;
    }
    .badge-pending {
      background-color: #ffecb3;
      color: #ff8f00;
    }
    .badge-approved {
      background-color: #c8e6c9;
      color: #2e7d32;
    }
    .badge-completed {
      background-color: #bbdefb;
      color: #1976d2;
    }
    .badge-cancelled {
      background-color: #ffcdd2;
      color: #d32f2f;
    }
    .table {
      margin-top: 10px;
    }
    .table th {
      background-color: #e8f5e9;
      color: #2e7d32;
      font-weight: 600;
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
      .record-container {
        margin: 15px auto;
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
  <a href="doctor_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_dashboard.php' ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> Dashboard Overview
  </a>
  <a href="doctor_student.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_student.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar-check"></i> Appointment Management
  </a>
  <a href="student_viewer.php" class="<?= basename($_SERVER['PHP_SELF']) === 'student_viewer.php' ? 'active' : '' ?>">
    <i class="bi bi-person-lines-fill"></i> Patient Records Viewer
  </a>
    <a href="doctor_notes.php" class="<?= $current_page === 'doctor_notes.php' ? 'active' : '' ?>">
    <i class="bi bi-journal-text"></i> Patient Notes
  </a>
  <a href="doctor_profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_profile.php' ? 'active' : '' ?>">
    <i class="bi bi-person-circle"></i> Doctor Profile
  </a>
  <a href="doctor_schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_schedule.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar3"></i> Schedule Configuration
  </a>
  <a href="doctor_report.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_report.php' ? 'active' : '' ?>">
    <i class="bi bi-graph-up"></i> Reports & Analytics
  </a>
</div>

  <!-- Sidebar toggle button -->
  <button id="sidebarToggle" class="toggle-btn" aria-label="Toggle sidebar">
    <i class="bi bi-chevron-double-left"></i>
  </button>

  <!-- Sidebar overlay -->
  <div id="sidebarOverlay" class="sidebar-overlay"></div>

  <!-- Top bar -->
  <div class="top-bar">
    Clinic Appointment System - Patient Records
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="record-container">
      <h1><i class="bi bi-person-lines-fill me-2"></i>Patient Records Viewer</h1>
      <p class="lead mb-4">View history of a specific student's appointment records</p>
      
      <!-- Search Form -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-search me-2"></i>Search Student
        </div>
        <div class="card-body">
          <form action="" method="GET" class="row g-3">
            <div class="col-md-8">
              <label for="studentID" class="form-label">Select Student</label>
              <select name="studentID" id="studentID" class="form-select">
                <option value="">-- Select a student --</option>
                <?php foreach ($students as $student): ?>
                  <option value="<?= $student['StudentID'] ?>" <?= ($studentID == $student['StudentID']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($student['student_name']) ?> (ID: <?= $student['StudentID'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-2"></i>View Records
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <?php if (!empty($studentID) && isset($studentInfo)): ?>
      <!-- Student Information -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="bi bi-person-badge me-2"></i>Student Information
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <p><span class="profile-label">Name:</span> <?= htmlspecialchars($studentInfo['full_name']) ?></p>
              <p><span class="profile-label">Student ID:</span> <?= htmlspecialchars($studentInfo['StudentID']) ?></p>
              <p><span class="profile-label">Email:</span> <?= htmlspecialchars($studentInfo['Email']) ?></p>
            </div>
            <div class="col-md-6">
              <p><span class="profile-label">Phone Number:</span> <?= htmlspecialchars($studentInfo['ContactNumber']) ?></p>
              <p><span class="profile-label">Date of Birth:</span> <?= date("F d, Y", strtotime($studentInfo['dob'])) ?></p>
              <p><span class="profile-label">Course & Year:</span> <?= htmlspecialchars($studentInfo['course']) ?> (Year <?= htmlspecialchars($studentInfo['year']) ?>)</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Appointment History -->
      <div class="card">
        <div class="card-header">
          <i class="bi bi-clock-history me-2"></i>Appointment History
        </div>
        <div class="card-body">
          <?php if (count($appointmentHistory) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Doctor</th>
                    <th>Status</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($appointmentHistory as $appointment): ?>
                    <tr>
                      <td><?= htmlspecialchars($appointment['formatted_date']) ?></td>
                      <td><?= htmlspecialchars($appointment['time']) ?></td>
                      <td><?= htmlspecialchars($appointment['Reason']) ?></td>
                      <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
                      <td>
                        <?php
                          $statusClass = '';
                          switch(strtolower($appointment['status_name'])) {
                              case 'pending':
                                  $statusClass = 'badge-pending';
                                  break;
                              case 'approved':
                                  $statusClass = 'badge-approved';
                                  break;
                              case 'completed':
                                  $statusClass = 'badge-completed';
                                  break;
                              case 'cancelled':
                                  $statusClass = 'badge-cancelled';
                                  break;
                          }
                        ?>
                        <span class="badge rounded-pill <?= $statusClass ?>"><?= htmlspecialchars($appointment['status_name']) ?></span>
                      </td>
                      <td>
                        <?php if (!empty($appointment['Notes'])): ?>
                          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#notesModal<?= $appointment['AppointmentID'] ?>">
                            <i class="bi bi-file-text"></i> View
                          </button>
                          
                          <!-- Notes Modal -->
                          <div class="modal fade" id="notesModal<?= $appointment['AppointmentID'] ?>" tabindex="-1" aria-labelledby="notesModalLabel<?= $appointment['AppointmentID'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                              <div class="modal-content">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="notesModalLabel<?= $appointment['AppointmentID'] ?>">Appointment Notes</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                  <?= nl2br(htmlspecialchars($appointment['Notes'])) ?>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                              </div>
                            </div>
                          </div>
                        <?php else: ?>
                          <span class="text-muted">No notes</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>No appointment history found for this student.
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php elseif (!empty($studentID)): ?>
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle me-2"></i>Student information not found. Please select a valid student.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
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
  </script>
</body>
</html>