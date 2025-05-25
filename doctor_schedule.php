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

// Process form submissions
$message = '';
$alertType = '';

// Add available time slots
if (isset($_POST['add_timeslot'])) {
    $day = $_POST['day'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';

    if (!empty($day) && !empty($startTime) && !empty($endTime)) {
        // Check if the start time is before end time
        if (strtotime($startTime) < strtotime($endTime)) {
            // Check for duplicate time slot
            $check_sql = "SELECT SlotID FROM timeslots WHERE DoctorID = ? AND AvailableDay = ? AND StartTime = ? AND EndTime = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ssss", $doctorID, $day, $startTime, $endTime);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = "This time slot already exists for the selected day.";
                $alertType = "warning";
            } else {
                $sql = "INSERT INTO timeslots (DoctorID, AvailableDay, StartTime, EndTime, IsAvailable) 
                        VALUES (?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $doctorID, $day, $startTime, $endTime); // Fixed: all strings
                
                if ($stmt->execute()) {
                    $message = "Time slot added successfully!";
                    $alertType = "success";
                } else {
                    $message = "Error adding time slot: " . $conn->error;
                    $alertType = "danger";
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $message = "Start time must be before end time.";
            $alertType = "warning";
        }
    } else {
        $message = "Please fill all fields.";
        $alertType = "warning";
    }
}

// Block off dates - ONLY for this doctor
if (isset($_POST['block_date'])) {
    $blockDate = $_POST['block_date'] ?? '';
    $reason = $_POST['block_reason'] ?? '';

    if (!empty($blockDate)) {
        // Check if date is not in the past
        if (strtotime($blockDate) >= strtotime(date('Y-m-d'))) {
            // Check if date is already blocked
            $check_sql = "SELECT BlockID FROM blocked_dates WHERE DoctorID = ? AND BlockedDate = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $doctorID, $blockDate);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $message = "This date is already blocked.";
                $alertType = "warning";
            } else {
                $sql = "INSERT INTO blocked_dates (DoctorID, BlockedDate, Reason) 
                        VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $doctorID, $blockDate, $reason);
                
                if ($stmt->execute()) {
                    $message = "Date blocked successfully!";
                    $alertType = "success";
                } else {
                    $message = "Error blocking date: " . $conn->error;
                    $alertType = "danger";
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $message = "Cannot block dates in the past.";
            $alertType = "warning";
        }
    } else {
        $message = "Please select a date to block.";
        $alertType = "warning";
    }
}

// Remove time slot - ONLY for this doctor
if (isset($_GET['remove_slot']) && is_numeric($_GET['remove_slot'])) {
    $slotID = $_GET['remove_slot'];

    // Verify the slot belongs to this doctor
    $verify_sql = "SELECT SlotID FROM timeslots WHERE SlotID = ? AND DoctorID = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("is", $slotID, $doctorID);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $message = "You can only remove your own time slots.";
        $alertType = "danger";
    } else {
        $sql = "DELETE FROM timeslots WHERE SlotID = ? AND DoctorID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $slotID, $doctorID); // Fixed: slotID is int, doctorID is string
        
        if ($stmt->execute()) {
            $message = "Time slot removed successfully!";
            $alertType = "success";
        } else {
            $message = "Error removing time slot: " . $conn->error;
            $alertType = "danger";
        }
        $stmt->close();
    }
    $verify_stmt->close();
}

// Remove blocked date - ONLY for this doctor
if (isset($_GET['remove_block']) && is_numeric($_GET['remove_block'])) {
    $blockID = $_GET['remove_block'];

    // Verify the blocked date belongs to this doctor
    $verify_sql = "SELECT BlockID FROM blocked_dates WHERE BlockID = ? AND DoctorID = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("is", $blockID, $doctorID);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $message = "You can only remove your own blocked dates.";
        $alertType = "danger";
    } else {
        $sql = "DELETE FROM blocked_dates WHERE BlockID = ? AND DoctorID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $blockID, $doctorID); // Fixed: blockID is int, doctorID is string
        
        if ($stmt->execute()) {
            $message = "Blocked date removed successfully!";
            $alertType = "success";
        } else {
            $message = "Error removing blocked date: " . $conn->error;
            $alertType = "danger";
        }
        $stmt->close();
    }
    $verify_stmt->close();
}

// Get current time slots - ONLY for this doctor
$currentTimeSlots = [];
$sql = "SELECT SlotID, AvailableDay, StartTime, EndTime 
        FROM timeslots 
        WHERE DoctorID = ? 
        ORDER BY FIELD(AvailableDay, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), StartTime";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctorID); // Fixed: string binding
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Format time for display
    $row['formatted_start'] = date("h:i A", strtotime($row['StartTime']));
    $row['formatted_end'] = date("h:i A", strtotime($row['EndTime']));
    $currentTimeSlots[] = $row;
}
$stmt->close();

// Get blocked dates - ONLY for this doctor
$blockedDates = [];
$sql = "SELECT BlockID, BlockedDate, Reason 
        FROM blocked_dates 
        WHERE DoctorID = ? AND BlockedDate >= CURDATE()
        ORDER BY BlockedDate";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctorID); // Fixed: string binding
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Format date for display
    $row['formatted_date'] = date("F d, Y", strtotime($row['BlockedDate']));
    $blockedDates[] = $row;
}
$stmt->close();

// Days of week array
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Schedule - Dr. <?= htmlspecialchars($doctorInfo['FirstName']) ?> - Medical Clinic</title>
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
    .schedule-container {
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
    .btn-danger {
      background-color: #d32f2f;
      border-color: #d32f2f;
    }
    .btn-danger:hover {
      background-color: #c62828;
      border-color: #c62828;
    }
    .badge-blocked {
      background-color: #ffcdd2;
      color: #d32f2f;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 0.8rem;
    }
    .day-badge {
      background-color: #c8e6c9;
      color: #2e7d32;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      min-width: 100px;
      display: inline-block;
      text-align: center;
    }
    .time-badge {
      background-color: #bbdefb;
      color: #1976d2;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 0.8rem;
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
      .schedule-container {
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
      .day-badge, .time-badge {
        display: block;
        margin-bottom: 5px;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <img src="img/GCLINIC.png" alt="Medical Clinic Logo" />
  <div class="sidebar-divider"></div>
  <a href="doctor_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_dashboard.php' ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>
  <a href="doctor_student.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_student.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar-check"></i> My Appointments
  </a>
  <a href="student_viewer.php" class="<?= basename($_SERVER['PHP_SELF']) === 'student_viewer.php' ? 'active' : '' ?>">
    <i class="bi bi-person-lines-fill"></i> My Patients
  </a>
  <a href="doctor_notes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_notes.php' ? 'active' : '' ?>">
    <i class="bi bi-journal-text"></i> Patient Notes
  </a>
  <a href="doctor_profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_profile.php' ? 'active' : '' ?>">
    <i class="bi bi-person-circle"></i> My Profile
  </a>
  <a href="doctor_schedule.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_schedule.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar3"></i> My Schedule
  </a>
  <a href="doctor_report.php" class="<?= basename($_SERVER['PHP_SELF']) === 'doctor_report.php' ? 'active' : '' ?>">
    <i class="bi bi-graph-up"></i> My Reports
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
    My Schedule Configuration - Dr. <?= htmlspecialchars($doctorInfo['FirstName'] . ' ' . $doctorInfo['LastName']) ?>
    <div style="font-size: 14px; opacity: 0.9;">
        <?= htmlspecialchars($doctorInfo['Specialization']) ?>
    </div>
</div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="schedule-container">
      <h1><i class="bi bi-calendar3 me-2"></i>My Schedule Configuration</h1>
      <p class="lead mb-4">Dr. <?= htmlspecialchars($doctorInfo['FirstName'] . ' ' . $doctorInfo['LastName']) ?> - Configure your available days and time slots for appointments</p>
      
      <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
          <?= $message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <!-- Add Available Time Slots -->
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header">
              <i class="bi bi-clock me-2"></i>Add Available Time Slots
            </div>
            <div class="card-body">
              <form action="" method="POST">
                <div class="mb-3">
                  <label for="day" class="form-label">Day of Week</label>
                  <select name="day" id="day" class="form-select" required>
                    <option value="">-- Select Day --</option>
                    <?php foreach ($daysOfWeek as $day): ?>
                      <option value="<?= $day ?>"><?= $day ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                  </div>
                </div>
                <button type="submit" name="add_timeslot" class="btn btn-primary">
                  <i class="bi bi-plus-circle me-2"></i>Add Time Slot
                </button>
              </form>
            </div>
          </div>
          
          <!-- Current Time Slots -->
          <div class="card">
            <div class="card-header">
              <i class="bi bi-calendar-week me-2"></i>Current Available Time Slots
            </div>
            <div class="card-body">
              <?php if (count($currentTimeSlots) > 0): ?>
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($currentTimeSlots as $slot): ?>
                        <tr>
                          <td>
                            <span class="day-badge"><?= htmlspecialchars($slot['AvailableDay']) ?></span>
                          </td>
                          <td>
                            <span class="time-badge">
                              <?= htmlspecialchars($slot['formatted_start']) ?> - <?= htmlspecialchars($slot['formatted_end']) ?>
                            </span>
                          </td>
                          <td>
                            <a href="?remove_slot=<?= $slot['SlotID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this time slot?')">
                              <i class="bi bi-trash"></i> Remove
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="bi bi-info-circle me-2"></i>No time slots have been added yet.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Block Off Dates -->
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header">
              <i class="bi bi-calendar-x me-2"></i>Block Off Unavailable Dates
            </div>
            <div class="card-body">
              <form action="" method="POST">
                <div class="mb-3">
                  <label for="block_date" class="form-label">Select Date to Block</label>
                  <input type="date" class="form-control" id="block_date" name="block_date" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                  <label for="block_reason" class="form-label">Reason (Optional)</label>
                  <input type="text" class="form-control" id="block_reason" name="block_reason" placeholder="e.g., Vacation, Meeting, etc.">
                </div>
                <button type="submit" name="block_date" class="btn btn-danger">
                  <i class="bi bi-x-circle me-2"></i>Block This Date
                </button>
              </form>
            </div>
          </div>
          
          <!-- Current Blocked Dates -->
          <div class="card">
            <div class="card-header">
              <i class="bi bi-calendar-minus me-2"></i>Currently Blocked Dates
            </div>
            <div class="card-body">
              <?php if (count($blockedDates) > 0): ?>
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($blockedDates as $date): ?>
                        <tr>
                          <td>
                            <span class="badge-blocked">
                              <?= htmlspecialchars($date['formatted_date']) ?>
                            </span>
                          </td>
                          <td>
                            <?= !empty($date['Reason']) ? htmlspecialchars($date['Reason']) : '<em class="text-muted">No reason provided</em>' ?>
                          </td>
                          <td>
                            <a href="?remove_block=<?= $date['BlockID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to unblock this date?')">
                              <i class="bi bi-trash"></i> Remove
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="bi bi-info-circle me-2"></i>No dates are currently blocked.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Schedule Guidelines -->
      <div class="card mt-4">
        <div class="card-header">
          <i class="bi bi-info-circle me-2"></i>Schedule Guidelines
        </div>
        <div class="card-body">
          <ul>
            <li>Add your regular weekly availability by creating time slots for each day you're available.</li>
            <li>Use the "Block Off Unavailable Dates" section to mark specific dates when you're not available (vacations, meetings, etc.).</li>
            <li>Students will only be able to book appointments during your available time slots and on days that aren't blocked.</li>
            <li>You cannot select dates in the past to block off.</li>
            <li>Make sure to keep your schedule updated to prevent scheduling conflicts.</li>
          </ul>
        </div>
      </div>
      
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Get today's date in YYYY-MM-DD format for the date picker minimum
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('block_date').setAttribute('min', today);
    
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