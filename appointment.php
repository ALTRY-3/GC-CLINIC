<?php

include 'config.php';

session_start();

if (!isset($_SESSION['studentID'])) {
    header('location:login.php');
    exit;
}

$student_id = trim($_SESSION['studentID']);

// Fetch student data
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            background-color: #f6faff;
        }

        /* Sidebar styles */
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

        /* Top Bar styles */
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
            padding-top: 85px;
            transition: margin-left 0.3s ease;
        }

        /* Appointment Form Container */
        .appointment-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 2.5rem 2rem;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(25, 118, 210, 0.22);
            border: 1.5px solid #e3f0fc;
            border-left: 6px solid #1976d2;
        }

        .appointment-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .appointment-header h2 {
            color: #011f4b;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .appointment-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #1976d2, #64b5f6);
            border-radius: 2px;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 1px solid #e3f0fc;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15);
        }

        .form-floating label {
            color: #666;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1976d2, #2196f3);
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1565c0, #1976d2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
        }

        /* Doctors List Styling */
        .doctors-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e3f0fc;
        }

        .doctors-section h3 {
            color: #011f4b;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .doctor-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e3f0fc;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(25, 118, 210, 0.15);
            border-color: #1976d2;
        }

        .doctor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #1976d2, #64b5f6);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .doctor-card:hover::before {
            opacity: 1;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .doctor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e3f0fc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .doctor-avatar i {
            font-size: 1.8rem;
            color: #1976d2;
        }

        .doctor-details h4 {
            margin: 0;
            color: #011f4b;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .doctor-details p {
            margin: 0.2rem 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .doctor-schedule {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e3f0fc;
        }

        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .schedule-time {
            color: #1976d2;
            font-weight: 500;
        }

        .schedule-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-available {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-busy {
            background: #ffebee;
            color: #c62828;
        }

        .no-doctors-message {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            color: #666;
        }

        .no-doctors-message i {
            font-size: 2rem;
            color: #1976d2;
            margin-bottom: 1rem;
            display: block;
        }

        /* Loading Animation */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner i {
            font-size: 2rem;
            color: #1976d2;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .appointment-container {
                margin: 15px;
                padding: 1.5rem;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
            }

            .appointment-header h2 {
                font-size: 1.5rem;
            }
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
        <a href="appointment.php" class="active"><i class="bi bi-journal-plus"></i> Schedule Appointment</a>
        <a href="schedule.php"><i class="bi bi-journal-arrow-down"></i> My Appointments</a>
        <a href="services.php"><i class="bi bi-journal-album"></i> Service</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="top-bar">
        <span>Medical Clinic Notify+</span>
        <div class="d-flex align-items-center">
            <div class="welcome-text">
                <i class="bi bi-person-circle"></i>
                Welcome, <?php echo htmlspecialchars($student_data['FirstName']); ?>
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
        <div class="appointment-container">
            <div class="appointment-header">
                <h2>Schedule an Appointment</h2>
            </div>
            
            <form id="dateForm" class="needs-validation" novalidate>
                <div class="form-floating mb-4">
                    <input type="date" class="form-control" id="getDayWeek" required>
                    <label for="getDayWeek">Select Date</label>
                    <div class="invalid-feedback">Please select a date</div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-2"></i>Find Available Doctors
                    </button>
                </div>
            </form>

            <div class="loading-spinner">
                <i class="bi bi-arrow-repeat"></i>
                <p class="mt-2">Loading available doctors...</p>
            </div>

            <div class="doctors-section">
                <h3>Available Doctors</h3>
                <div id="filteredDoctors">
                    <div class="no-doctors-message">
                        <i class="bi bi-calendar-x"></i>
                        <p>Please select a date to view available doctors.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content"></div>
        </div>
    </div>

    <!-- Booking Result Modal -->
    <div class="modal fade" id="bookingResultModal" tabindex="-1" aria-labelledby="bookingResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingResultModalLabel">Appointment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingResultMessage">
                    <!-- Message will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Reason Modal -->
    <div class="modal fade" id="appointmentReasonModal" tabindex="-1" aria-labelledby="appointmentReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="appointmentReasonForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appointmentReasonModalLabel">Book Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <input type="text" class="form-control" id="modalDoctorName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="text" class="form-control" id="modalAppointmentDate" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time</label>
                            <input type="text" class="form-control" id="modalAppointmentTime" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="modalAppointmentReason" class="form-label">Reason for Appointment</label>
                            <textarea class="form-control" id="modalAppointmentReason" rows="2" required></textarea>
                            <div class="invalid-feedback">Please enter a reason.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'appointmentModal.html'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            const topBar = document.querySelector('.top-bar');
            const dateForm = document.getElementById('dateForm');
            const loadingSpinner = document.querySelector('.loading-spinner');
            const filteredDoctors = document.getElementById('filteredDoctors');
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

            // Form submission handling
            dateForm.addEventListener('submit', function(event) {
                event.preventDefault();
                if (!dateForm.checkValidity()) {
                    event.stopPropagation();
                    dateForm.classList.add('was-validated');
                    return;
                }

                const selectedDate = document.getElementById('getDayWeek').value;
                
                // Show loading spinner
                loadingSpinner.style.display = 'block';
                filteredDoctors.innerHTML = '';

                // Make AJAX request to fetch available doctors
                fetch('get_available_doctors.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'date=' + selectedDate
                })
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.style.display = 'none';
                    
                    if (!data.success) {
                        filteredDoctors.innerHTML = `
                            <div class="no-doctors-message">
                                <i class="bi bi-exclamation-circle"></i>
                                <p>${data.error}</p>
                                ${data.debug ? `<small class="text-muted">Debug info: ${JSON.stringify(data.debug)}</small>` : ''}
                            </div>
                        `;
                        return;
                    }

                    const doctors = data.doctors;
                    
                    if (doctors.length === 0) {
                        filteredDoctors.innerHTML = `
                            <div class="no-doctors-message">
                                <i class="bi bi-calendar-x"></i>
                                <p>No doctors available for the selected date.</p>
                            </div>
                        `;
                        return;
                    }

                    // Create doctors grid
                    const doctorsGrid = document.createElement('div');
                    doctorsGrid.className = 'doctors-grid';

                    // Add each doctor to the grid
                    doctors.forEach(doctor => {
                        const doctorCard = document.createElement('div');
                        doctorCard.className = 'doctor-card';
                        doctorCard.innerHTML = `
                            <div class="doctor-info">
                                <div class="doctor-avatar">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="doctor-details">
                                    <h4>Dr. ${doctor.FirstName} ${doctor.LastName}</h4>
                                    <p>${doctor.Specialization}</p>
                                </div>
                            </div>
                            <div class="doctor-schedule">
                                <div class="schedule-item">
                                    <span class="schedule-time">${to12HourRange(doctor.ScheduleTime)}</span>
                                    <span class="schedule-status status-available">Available</span>
                                </div>
                            </div>
                            <button class="btn btn-primary w-100 mt-3"
                                onclick="bookAppointment('${doctor.DoctorID}', '${doctor.SlotID}', '${selectedDate}', '${doctor.ScheduleTime}')">
                                <i class="bi bi-calendar-plus me-2"></i>Book Appointment
                            </button>
                        `;
                        doctorsGrid.appendChild(doctorCard);
                    });

                    filteredDoctors.innerHTML = '';
                    filteredDoctors.appendChild(doctorsGrid);
                })
                .catch(error => {
                    loadingSpinner.style.display = 'none';
                    filteredDoctors.innerHTML = `
                        <div class="no-doctors-message">
                            <i class="bi bi-exclamation-circle"></i>
                            <p>Error loading doctors. Please try again.</p>
                            <small class="text-muted">Error details: ${error.message}</small>
                        </div>
                    `;
                    console.error('Error:', error);
                });
            });

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

        // Store booking info globally for modal use
        let bookingInfo = {};

        function bookAppointment(doctorId, slotId, date, time) {
            // Find doctor name from the card (or pass as argument if you prefer)
            const doctorCard = event.target.closest('.doctor-card');
            const doctorName = doctorCard.querySelector('.doctor-details h4').textContent;

            bookingInfo = { doctorId, slotId, date, time, doctorName };

            // Fill modal fields
            document.getElementById('modalDoctorName').value = doctorName;
            document.getElementById('modalAppointmentDate').value = date;
            document.getElementById('modalAppointmentTime').value = time;
            document.getElementById('modalAppointmentReason').value = '';

            // Show modal
            var reasonModal = new bootstrap.Modal(document.getElementById('appointmentReasonModal'));
            reasonModal.show();
        }

        // Handle modal form submission
        document.addEventListener('DOMContentLoaded', function() {
            const reasonForm = document.getElementById('appointmentReasonForm');
            if (reasonForm) {
                reasonForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const reasonInput = document.getElementById('modalAppointmentReason');
                    if (!reasonInput.value.trim()) {
                        reasonInput.classList.add('is-invalid');
                        return;
                    } else {
                        reasonInput.classList.remove('is-invalid');
                    }

                    // Hide modal
                    var reasonModalEl = document.getElementById('appointmentReasonModal');
                    var reasonModal = bootstrap.Modal.getInstance(reasonModalEl);
                    reasonModal.hide();

                    // Send AJAX request
                    fetch('submit_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            doctorID: bookingInfo.doctorId,
                            appointmentDate: bookingInfo.date,
                            appointmentTime: bookingInfo.time,
                            reason: reasonInput.value.trim()
                        }),
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        let message = '';
                        if (data.success) {
                            message = '<div class="alert alert-success mb-0">' + data.message + '</div>';
                            // Refresh the available doctors after successful booking
                            document.getElementById('dateForm').dispatchEvent(new Event('submit'));
                        } else {
                            message = '<div class="alert alert-danger mb-0">' + data.message + '</div>';
                        }
                        document.getElementById('bookingResultMessage').innerHTML = message;
                        var bookingModal = new bootstrap.Modal(document.getElementById('bookingResultModal'));
                        bookingModal.show();
                    })
                    .catch(error => {
                        document.getElementById('bookingResultMessage').innerHTML =
                            '<div class="alert alert-danger mb-0">Error booking appointment. Please try again.</div>';
                        var bookingModal = new bootstrap.Modal(document.getElementById('bookingResultModal'));
                        bookingModal.show();
                        console.error('Error:', error);
                    });
                });
            }
        });

        // Add this function before the doctors.forEach loop
        function to12HourRange(timeRange) {
            // Expects timeRange like "13:00-14:30"
            if (!timeRange) return '';
            const [start, end] = timeRange.split('-');
            return `${to12Hour(start)} - ${to12Hour(end)}`;
        }
        function to12Hour(time) {
            // Expects time like "13:00"
            const [hour, minute] = time.split(':');
            let h = parseInt(hour, 10);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            if (h === 0) h = 12;
            return `${h}:${minute} ${ampm}`;
        }
    </script>
</body>
</html>