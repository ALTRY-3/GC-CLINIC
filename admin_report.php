<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicalclinicnotify";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch daily appointments count
$queryDaily = "SELECT COUNT(*) AS total FROM appointments WHERE DATE(AppointmentDate) = CURDATE()";
$resultDaily = $conn->query($queryDaily);
$dailyTotal = $resultDaily->fetch_assoc()['total'] ?? 0;

// Fetch weekly appointments count
$queryWeekly = "SELECT COUNT(*) AS total FROM appointments WHERE YEARWEEK(AppointmentDate, 1) = YEARWEEK(CURDATE(), 1)";
$resultWeekly = $conn->query($queryWeekly);
$weeklyTotal = $resultWeekly->fetch_assoc()['total'] ?? 0;

// Fetch monthly appointments count
$queryMonthly = "SELECT COUNT(*) AS total FROM appointments WHERE MONTH(AppointmentDate) = MONTH(CURDATE()) AND YEAR(AppointmentDate) = YEAR(CURDATE())";
$resultMonthly = $conn->query($queryMonthly);
$monthlyTotal = $resultMonthly->fetch_assoc()['total'] ?? 0;

// Fetch cancellation count
$queryCancellations = "SELECT COUNT(*) AS total FROM appointments WHERE statusID = 4 AND MONTH(AppointmentDate) = MONTH(CURDATE()) AND YEAR(AppointmentDate) = YEAR(CURDATE())";
$resultCancellations = $conn->query($queryCancellations);
$cancellationsTotal = $resultCancellations->fetch_assoc()['total'] ?? 0;

// Fetch the most common reasons for appointments
$queryReasons = "SELECT Reason, COUNT(*) AS count FROM appointments GROUP BY Reason ORDER BY count DESC LIMIT 3";
$resultReasons = $conn->query($queryReasons);
$reasonsData = [];
while ($row = $resultReasons->fetch_assoc()) {
    $reasonsData[] = $row;
}

// Fetch the most common reasons for cancellation
$queryCancellationReasons = "SELECT n.cancellation_reason, COUNT(*) AS count 
                            FROM notifications n 
                            INNER JOIN appointments a ON n.appointmentID = a.AppointmentID 
                            WHERE n.cancellation_reason IS NOT NULL 
                            AND a.StatusID = 4 
                            GROUP BY n.cancellation_reason 
                            ORDER BY count DESC 
                            LIMIT 3";
$resultCancellationReasons = $conn->query($queryCancellationReasons);
$cancellationReasonsData = [];
while ($row = $resultCancellationReasons->fetch_assoc()) {
    $cancellationReasonsData[] = $row;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Appointment System - Report</title>
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
            width: 240px;
            height: 100vh;
            position: fixed;
            background-color: #011f4b !important;
            color: white;
            padding-top: 20px;
            box-shadow: 2px 0 12px rgba(1, 31, 75, 0.10);
            transition: transform 0.3s ease;
            z-index: 2000;
            overflow-y: auto;
            left: 0;
            top: 0;
            display: block;
        }
        .sidebar-divider {
            border-bottom: 1.5px solid #23406a;
            margin: 18px 0 12px 0;
        }
        .sidebar.collapsed {
            transform: translateX(-240px);
            background-color: #011f4b !important;
        }
        .toggle-btn {
            position: fixed;
            left: 240px;
            top: 24px;
            background-color: #fff;
            color: #1976d2;
            border: none;
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(1,31,75,0.10);
            cursor: pointer;
            z-index: 1100;
            transition: left 0.3s, background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-btn:hover {
            background: #e3f0fc;
            color: #011f4b;
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
            color: #011f4b;
        }
        .toggle-btn.expanded i {
            transform: rotate(0deg) scale(1.1);
            color: #1976d2;
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
        .sidebar a:hover, .sidebar a.active {
            background-color: #e3f0fc;
            color: #1976d2;
            border-right: 6px solid #1976d2;
        }

        /* Top Bar Part */
        .top-bar {
            width: calc(100% - 240px);
            height: 60px;
            background-color: #011f4b;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 28px;
            font-size: 22px;
            font-weight: 600;
            margin-left: 240px;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(1, 31, 75, 0.08);
            border-bottom: 2px solid #23406a;
            letter-spacing: 0.5px;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
            padding-top: 70px;
            transition: all 0.3s ease;
        }

        h1, h2 {
            color: #011f4b;
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
            box-shadow: 0 0 20px rgba(1, 31, 75, 0.1);
        }

        .summary-boxes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-box {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
        }

        .summary-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(1, 31, 75, 0.15);
        }

        .summary-box h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #011f4b;
        }

        .summary-box p {
            font-size: 2rem;
            font-weight: 600;
            color: #011f4b;
            margin: 0;
        }

        .reasons {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
        }

        .reasons h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #011f4b;
        }

        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        ul li {
            background-color: #f8f9fa;
            margin-bottom: 12px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: transform 0.2s ease;
        }

        ul li:hover {
            transform: translateX(5px);
            background-color: #f1f3f5;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                background-color: #011f4b !important;
                left: 0;
                top: 0;
                display: block;
                z-index: 2000;
            }
            .sidebar.expanded {
                transform: translateX(0);
            }
            .toggle-btn {
                left: 0;
            }
            .toggle-btn.expanded {
                left: 240px;
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
            .summary-boxes {
                gap: 15px;
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
            .summary-boxes {
                grid-template-columns: 1fr;
            }
            .summary-box h3 {
                font-size: 1.1rem;
            }
            .summary-box p {
                font-size: 1.8rem;
            }
            .reasons h3 {
                font-size: 1.3rem;
            }
            ul li {
                padding: 12px;
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
            .summary-boxes {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .summary-box {
                padding: 15px;
            }
            .summary-box h3 {
                font-size: 1rem;
                margin-bottom: 10px;
            }
            .summary-box p {
                font-size: 1.5rem;
            }
            .reasons {
                padding: 15px;
            }
            .reasons h3 {
                font-size: 1.2rem;
                margin-bottom: 15px;
            }
            ul li {
                padding: 10px;
                margin-bottom: 8px;
            }
            .sidebar a {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            .sidebar img {
                width: 70%;
            }
        }
    </style>
</head>
<body>
    <button class="toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="bi bi-chevron-double-right"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <img src="MedicalClinicLogo.png" alt="Logo">
        <div class="sidebar-divider"></div>
        <a href="admin_profile.php" class="<?php echo $currentPage == 'admin_profile.php' ? 'active' : ''; ?>"><i class="bi bi-person-vcard"></i> Profile</a>
        <a href="staff_management.php" class="<?php echo $currentPage == 'staff_management.php' ? 'active' : ''; ?>"><i class="bi bi-person-lines-fill"></i> Staff Management</a>
        <a href="student_management.php" class="<?php echo $currentPage == 'student_management.php' ? 'active' : ''; ?>"><i class="bi bi-journal-text"></i> Users Management</a>
        <a href="admin_report.php" class="<?php echo $currentPage == 'admin_report.php' ? 'active' : ''; ?>"><i class="bi bi-bar-chart"></i> Reports</a>
        <a href="adminLogout.php" class="mt-auto d-flex align-items-center"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="top-bar">
        <div>Appointment Management System</div>
    </div>

    <div class="main-content">
        <div class="report-container">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
                <h1 class="mb-0">Appointment Report</h1>
                <div class="d-flex gap-2 flex-wrap">
                    <form class="d-flex align-items-center gap-2" id="dateRangeForm" method="GET" action="#">
                        <label class="form-label mb-0 me-1" for="dateFrom">From</label>
                        <input type="date" class="form-control form-control-sm" id="dateFrom" name="dateFrom" style="min-width:130px;">
                        <label class="form-label mb-0 ms-2 me-1" for="dateTo">To</label>
                        <input type="date" class="form-control form-control-sm" id="dateTo" name="dateTo" style="min-width:130px;">
                        <button type="submit" class="btn btn-primary btn-sm ms-2"><i class="bi bi-funnel"></i> Filter</button>
                    </form>
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()" data-bs-toggle="tooltip" title="Print or export this report"><i class="bi bi-printer"></i> Export/Print</button>
                </div>
            </div>
            <hr class="mb-4">
            <div class="summary-boxes">
                <div class="summary-box position-relative border-0 shadow-sm" style="border-left: 6px solid #1976d2;">
                    <div class="position-absolute top-0 end-0 p-2"><i class="bi bi-calendar-day text-primary" style="font-size:1.5rem;" data-bs-toggle="tooltip" title="Total appointments today"></i></div>
                    <h3 class="mb-2">Daily Appointments</h3>
                    <p class="display-6 fw-bold text-primary mb-0"><?php echo $dailyTotal; ?></p>
                </div>
                <div class="summary-box position-relative border-0 shadow-sm" style="border-left: 6px solid #43a047;">
                    <div class="position-absolute top-0 end-0 p-2"><i class="bi bi-calendar-week text-success" style="font-size:1.5rem;" data-bs-toggle="tooltip" title="Total appointments this week"></i></div>
                    <h3 class="mb-2">Weekly Appointments</h3>
                    <p class="display-6 fw-bold text-success mb-0"><?php echo $weeklyTotal; ?></p>
                </div>
                <div class="summary-box position-relative border-0 shadow-sm" style="border-left: 6px solid #f9a825;">
                    <div class="position-absolute top-0 end-0 p-2"><i class="bi bi-calendar-month text-warning" style="font-size:1.5rem;" data-bs-toggle="tooltip" title="Total appointments this month"></i></div>
                    <h3 class="mb-2">Monthly Appointments</h3>
                    <p class="display-6 fw-bold text-warning mb-0"><?php echo $monthlyTotal; ?></p>
                </div>
                <div class="summary-box position-relative border-0 shadow-sm" style="border-left: 6px solid #dc3545;">
                    <div class="position-absolute top-0 end-0 p-2"><i class="bi bi-x-circle text-danger" style="font-size:1.5rem;" data-bs-toggle="tooltip" title="Total cancelled appointments this month"></i></div>
                    <h3 class="mb-2">Monthly Cancellations</h3>
                    <p class="display-6 fw-bold text-danger mb-0"><?php echo $cancellationsTotal; ?></p>
                </div>
            </div>
            <hr class="my-4">
            <div class="reasons">
                <div class="d-flex align-items-center mb-3 gap-2">
                    <h3 class="mb-0 flex-grow-1">Most Common Reasons for Appointments</h3>
                    <span class="text-muted small"><i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Top 3 reasons for appointments in the selected period"></i></span>
                </div>
                <ul class="mb-0">
                    <?php foreach ($reasonsData as $reason): ?>
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary me-2" style="font-size:1rem;"><i class="bi bi-clipboard2-pulse me-1"></i><?php echo htmlspecialchars($reason['Reason']); ?></span>
                            <span class="fw-semibold ms-auto">(<?php echo $reason['count']; ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <hr class="my-4">

            <div class="reasons">
                <div class="d-flex align-items-center mb-3 gap-2">
                    <h3 class="mb-0 flex-grow-1">Most Common Reasons for Cancellation</h3>
                    <span class="text-muted small"><i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Top 3 reasons for appointment cancellations"></i></span>
                </div>
                <ul class="mb-0">
                    <?php if (empty($cancellationReasonsData)): ?>
                        <li class="d-flex align-items-center gap-2">
                            <span class="text-muted">No cancellation data available</span>
                        </li>
                    <?php else: ?>
                        <?php foreach ($cancellationReasonsData as $reason): ?>
                            <li class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger me-2" style="font-size:1rem;"><i class="bi bi-x-circle me-1"></i><?php echo htmlspecialchars($reason['cancellation_reason']); ?></span>
                                <span class="fw-semibold ms-auto">(<?php echo $reason['count']; ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Chart.js placeholder for future chart integration -->
            <!-- <canvas id="reasonsChart" height="120"></canvas> -->
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mainContent = document.querySelector('.main-content');
            const topBar = document.querySelector('.top-bar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            function setSidebarState() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.add('collapsed');
                    toggleBtn.classList.remove('expanded');
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                    sidebarOverlay.classList.remove('active');
                } else {
                    sidebar.classList.remove('collapsed');
                    toggleBtn.classList.add('expanded');
                    mainContent.style.marginLeft = '240px';
                    topBar.style.marginLeft = '240px';
                    topBar.style.width = 'calc(100% - 240px)';
                    sidebarOverlay.classList.remove('active');
                }
            }

            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                toggleBtn.classList.toggle('collapsed');
                toggleBtn.classList.toggle('expanded');
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                    sidebarOverlay.classList.remove('active');
                } else {
                    if (window.innerWidth <= 992) {
                        sidebarOverlay.classList.add('active');
                    }
                    mainContent.style.marginLeft = window.innerWidth > 992 ? '240px' : '0';
                    topBar.style.marginLeft = window.innerWidth > 992 ? '240px' : '0';
                    topBar.style.width = window.innerWidth > 992 ? 'calc(100% - 240px)' : '100%';
                }
            });

            sidebarOverlay.addEventListener('click', function() {
                if (!sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    toggleBtn.classList.remove('expanded');
                    toggleBtn.classList.add('collapsed');
                    sidebarOverlay.classList.remove('active');
                    mainContent.style.marginLeft = '0';
                    topBar.style.marginLeft = '0';
                    topBar.style.width = '100%';
                }
            });

            window.addEventListener('resize', setSidebarState);
            setSidebarState();
        });

        // Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
