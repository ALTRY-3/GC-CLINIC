<?php
include 'config.php';
session_start();

if (!isset($_SESSION['studentID'])) {
    header('location:login.php');
    exit;
}

$student_id = $_SESSION['studentID'];

// Fetch student data for welcome message
$student_data = ['FirstName' => 'Student']; // default fallback
$student_query = "SELECT FirstName FROM Students WHERE StudentID = ? LIMIT 1";
$student_stmt = $conn->prepare($student_query);
if ($student_stmt) {
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    if ($student_result && $student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
    }
    $student_stmt->close();
}

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
    <title>Services</title>
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
            background-color: #011f4b !important;
            color: white;
            padding-top: 15px;
            box-shadow: 4px 0 15px rgba(1, 31, 75, 0.15);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin: 12px 20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.85);
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
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding-left: 30px;
        }

        .sidebar a:hover i {
            transform: translateX(3px);
        }

        .sidebar a.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border-right: 4px solid #4a90e2;
        }

        /* Top Bar Part */
        .top-bar {
            width: calc(100% - 260px);
            height: 65px;
            background-color: #011f4b;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 30px;
            font-size: 1.4rem;
            font-weight: 600;
            margin-left: 260px;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 0 2px 15px rgba(1, 31, 75, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

        /* Services Container */
        .services-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .service-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e3f0fc;
        }

        .service-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .service-box img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .service-box h3 {
            color: #011f4b;
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .service-box p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .page-title {
            color: #011f4b;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #1976d2;
            border-radius: 2px;
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
            .services-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-double-right"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <img src="MedicalClinicLogo.png" alt="Logo">
        <div class="sidebar-divider"></div>
        <a href="studentHome.php"><i class="bi bi-house"></i> Home</a>
        <a href="doctors.php"><i class="bi bi-person-square"></i> Doctors</a>
        <a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a>
        <a href="schedule.php"><i class="bi bi-journal-arrow-down"></i> My Appointments</a>
        <a href="services.php" class="active"><i class="bi bi-journal-album"></i> Service</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="top-bar">
        <span>Medical Clinic Notify+</span>
        <div class="d-flex align-items-center">
            <div class="welcome-text">
                <i class="bi bi-person-circle"></i>
                Welcome, <?php echo htmlspecialchars($student_data['FirstName']); ?>
            </div>
            <div class="notification-bell" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications">
                <i class="bi bi-bell-fill"></i>
                <?php if ($notifications->num_rows > 0): ?>
                    <span class="notification-count"><?php echo $notifications->num_rows; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="main-content">
        <h1 class="page-title">Our Services</h1>
        <div class="services-container">
            <div class="services-grid">
                <div class="service-box">
                    <img src="MedicalExam.png" alt="Medical Examination">
                    <h3>Medical Examination</h3>
                    <p>A comprehensive evaluation of your health by a doctor to assess overall well-being. This exam includes checking vital signs, reviewing medical history, and conducting necessary physical tests to detect any potential health issues. It is essential for monitoring your health regularly and preventing possible future complications.</p>
                </div>
                <div class="service-box">
                    <img src="MedicalCert.png" alt="Medical Certificate">
                    <h3>Medical Certificate</h3>
                    <p>A document stating your medical condition issued by a licensed physician. This certificate is often required for work, school, or other legal purposes to verify your health status. It includes details of the examination conducted, your diagnosis, and recommendations for treatment if necessary.</p>
                </div>
                <div class="service-box">
                    <img src="MedicalClearance.png" alt="Medical Clearance">
                    <h3>Medical Clearance</h3>
                    <p>A certificate that ensures you are fit for work or school, required by institutions. The clearance includes a thorough health evaluation, confirming that you are physically and mentally capable of performing tasks or participating in activities. It may also be required before traveling, joining sports, or engaging in physically demanding jobs.</p>
                </div>
                <div class="service-box">
                    <img src="OralCheck.png" alt="Oral Care Checkup">
                    <h3>Oral Care Checkup</h3>
                    <p>A dental checkup to assess the health of your teeth and gums. During this exam, a dentist will look for signs of decay, gum disease, or any other oral health issues. Regular checkups are vital in maintaining good oral hygiene, preventing cavities, and ensuring that your mouth stays healthy for years to come. Professional cleaning may also be done to remove plaque buildup.</p>
                </div>
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
        });
    </script>
</body>
</html>