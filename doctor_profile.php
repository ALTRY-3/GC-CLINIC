<?php
session_start();
include 'config.php';

// TEMPORARY: Hardcoded DoctorID for testing (replace with a real one)
$doctorID = 'DOC-2025-0001'; // or just 5, 7, etc., based on your `doctors` table

// Get doctor details
$sql = "SELECT * FROM doctors WHERE DoctorID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctorID);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// Handle form submission for profile update
$updateMessage = '';
$updateStatus = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $specialization = $_POST['specialization'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // Optional: Add validation here
    
    $update_sql = "UPDATE doctors SET 
                    FirstName = ?, 
                    LastName = ?, 
                    Specialization = ?, 
                    Email = ?, 
                    Phone = ?
                  WHERE DoctorID = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssss", $firstName, $lastName, $specialization, $email, $phone, $doctorID);
    
    if ($update_stmt->execute()) {
        $updateMessage = "Profile updated successfully!";
        $updateStatus = "success";
        
        // Refresh doctor data after update
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
    } else {
        $updateMessage = "Error updating profile: " . $conn->error;
        $updateStatus = "danger";
    }
}

// Get current page for navbar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doctor Profile - Clinic Appointment System</title>
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
    .profile-container {
      max-width: 900px;
      margin: 20px auto;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(46, 125, 50, 0.1);
      overflow: hidden;
    }
    .profile-header {
      background-color: #f1f8e9;
      padding: 30px;
      border-bottom: 1px solid #e0e0e0;
    }
    .profile-content {
      padding: 30px;
    }
    .profile-picture {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #fff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .profile-name {
      font-size: 1.8rem;
      font-weight: 600;
      color: #2e7d32;
      margin-bottom: 5px;
    }
    .profile-specialization {
      font-size: 1.2rem;
      color: #666;
      margin-bottom: 20px;
    }
    .profile-form .form-label {
      font-weight: 500;
      color: #555;
    }
    .profile-form .form-control {
      border-radius: 8px;
      padding: 10px 15px;
      border: 1px solid #ddd;
      transition: all 0.3s;
    }
    .profile-form .form-control:focus {
      border-color: #4CAF50;
      box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    }
    .btn-primary {
      background-color: #2e7d32;
      border-color: #2e7d32;
      padding: 10px 25px;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.3s;
    }
    .btn-primary:hover {
      background-color: #388e3c;
      border-color: #388e3c;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-outline-secondary {
      color: #666;
      border-color: #ddd;
      padding: 10px 25px;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.3s;
    }
    .btn-outline-secondary:hover {
      background-color: #f5f5f5;
      color: #333;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .alert {
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 25px;
    }
    .header {
      color: #2e7d32;
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 25px;
    }
    .info-group {
      margin-bottom: 20px;
    }
    .info-label {
      font-weight: 600;
      color: #555;
      margin-bottom: 5px;
    }
    .info-value {
      font-size: 1.1rem;
      color: #333;
    }
    .divider {
      height: 1px;
      background-color: #eee;
      margin: 30px 0;
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
      .profile-container {
        margin: 15px auto;
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
      .profile-header,
      .profile-content {
        padding: 20px;
      }
      .profile-picture {
        width: 100px;
        height: 100px;
      }
      .profile-name {
        font-size: 1.5rem;
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
      .profile-header,
      .profile-content {
        padding: 15px;
      }
      .btn-primary,
      .btn-outline-secondary {
        width: 100%;
        margin-bottom: 10px;
      }
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
  Medical Clinic Notify+ - Doctor Profile
</div>

<div class="main-content">
  <div class="header">Doctor Profile</div>
  
  <?php if (!empty($updateMessage)): ?>
    <div class="alert alert-<?= $updateStatus ?> alert-dismissible fade show" role="alert">
      <?= $updateMessage ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  
  <div class="profile-container">
    <div class="profile-header">
      <div class="row align-items-center">
        <div class="col-md-3 text-center">
          <img src="doctor_avatar.png" alt="Doctor Avatar" class="profile-picture">
        </div>
        <div class="col-md-9">
          <h1 class="profile-name"><?= htmlspecialchars($doctor['FirstName'] . ' ' . $doctor['LastName']) ?></h1>
          <p class="profile-specialization"><?= htmlspecialchars($doctor['Specialization']) ?></p>
          <p><i class="bi bi-person-badge me-2"></i> ID: <?= htmlspecialchars($doctor['DoctorID']) ?></p>
        </div>
      </div>
    </div>
    
    <div class="profile-content">
      <!-- View Mode -->
      <div id="viewMode">
        <div class="row">
          <div class="col-md-6">
            <div class="info-group">
              <div class="info-label">First Name</div>
              <div class="info-value"><?= htmlspecialchars($doctor['FirstName']) ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="info-group">
              <div class="info-label">Last Name</div>
              <div class="info-value"><?= htmlspecialchars($doctor['LastName']) ?></div>
            </div>
          </div>
        </div>
        
        <div class="info-group">
          <div class="info-label">Specialization</div>
          <div class="info-value"><?= htmlspecialchars($doctor['Specialization']) ?></div>
        </div>
        
        <div class="info-group">
          <div class="info-label">Email Address</div>
          <div class="info-value"><?= htmlspecialchars($doctor['Email']) ?></div>
        </div>
        
        <div class="info-group">
          <div class="info-label">Contact Number</div>
          <div class="info-value"><?= htmlspecialchars($doctor['Phone']) ?></div>
        </div>
        
        <div class="divider"></div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
          <button class="btn btn-primary" id="editProfileBtn">
            <i class="bi bi-pencil-square me-2"></i>Edit Profile
          </button>
        </div>
      </div>
      
      <!-- Edit Mode -->
      <div id="editMode" style="display: none;">
        <form class="profile-form" method="POST" action="">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="firstName" class="form-label">First Name</label>
              <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($doctor['FirstName']) ?>" required>
            </div>
            <div class="col-md-6">
              <label for="lastName" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($doctor['LastName']) ?>" required>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="specialization" class="form-label">Specialization</label>
            <input type="text" class="form-control" id="specialization" name="specialization" value="<?= htmlspecialchars($doctor['Specialization']) ?>" required>
          </div>
          
          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($doctor['Email']) ?>" required>
          </div>
          
          <div class="mb-3">
            <label for="phone" class="form-label">Contact Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($doctor['Phone']) ?>">
          </div>
          
          <div class="divider"></div>
          
          <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <button type="button" class="btn btn-outline-secondary" id="cancelEditBtn">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" name="update_profile">
              <i class="bi bi-check-lg me-2"></i>Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle functionality
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
  
  // Profile view/edit mode toggle
  const viewMode = document.getElementById('viewMode');
  const editMode = document.getElementById('editMode');
  const editProfileBtn = document.getElementById('editProfileBtn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');
  
  editProfileBtn.addEventListener('click', () => {
    viewMode.style.display = 'none';
    editMode.style.display = 'block';
  });
  
  cancelEditBtn.addEventListener('click', () => {
    editMode.style.display = 'none';
    viewMode.style.display = 'block';
  });
  
  // Auto-dismiss alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bootstrapAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bootstrapAlert.close();
    }, 5000);
  });
</script>

</body>
</html>