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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f6faff; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background-color: #2e7d32 !important; color: white; padding-top: 15px; box-shadow: 4px 0 15px rgba(46, 125, 50, 0.15); z-index: 2000; overflow-y: hidden; left: 0; top: 0; display: block; }
        .sidebar img { width: 65%; height: auto; margin: 0 auto 15px; display: block; }
        .sidebar-divider { border-bottom: 1.5px solid #60ad5e; margin: 12px 20px; }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            padding: 14px 25px;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .sidebar a i {
            margin-right: 12px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .sidebar a:hover {
            background: #60ad5e;
            color: #fff;
            padding-left: 32px;
        }
        .sidebar a.active {
            background: #60ad5e;
            color: #fff;
            font-weight: 700;
            /* No border-right */
        }
        .top-bar { width: calc(100% - 260px); height: 65px; background-color: #2e7d32; color: #fff; display: flex; align-items: center; padding: 0 30px; font-size: 1.15rem; font-weight: 400; margin-left: 260px; justify-content: space-between; transition: all 0.3s ease; box-shadow: none; border-bottom: none; letter-spacing: 0.5px; }
        .dashboard-main { margin-left: 260px; padding: 30px 20px 20px 20px; min-height: 100vh; }
        .welcome-banner { background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(1,31,75,0.08); padding: 2rem 2rem 1.5rem 2rem; display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; }
        .welcome-banner .avatar { width: 70px; height: 70px; border-radius: 50%; background: #e3f0fc; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #2e7d32; }
        .welcome-banner h2 { margin: 0; font-size: 2rem; font-weight: 600; color: #2e7d32; }
        .dashboard-cards { display: flex; gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .dashboard-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); padding: 1.5rem 1.2rem; flex: 1 1 220px; min-width: 220px; display: flex; flex-direction: column; align-items: flex-start; }
        .dashboard-card h4 { font-size: 1.1rem; font-weight: 600; color: #2e7d32; margin-bottom: 0.5rem; }
        .dashboard-card .stat { font-size: 1.2rem; font-weight: 500; color: #011f4b; margin-bottom: 0.5rem; }
        .dashboard-card .btn { margin-top: auto; }
        .latest-notifications { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); padding: 1.5rem 1.2rem; }
        .latest-notifications h5 { font-size: 1.1rem; font-weight: 600; color: #2e7d32; margin-bottom: 1rem; }
        .latest-notifications ul { list-style: none; padding: 0; margin: 0; }
        .latest-notifications li { padding: 0.5rem 0; border-bottom: 1px solid #e3f0fc; font-size: 0.98rem; color: #222; }
        .latest-notifications li:last-child { border-bottom: none; }
        @media (max-width: 992px) { .sidebar { transform: translateX(-260px); } .sidebar.expanded { transform: translateX(0); } .top-bar { margin-left: 0; width: 100%; } .dashboard-main { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <img src="img/GCLINIC.png" alt="Logo">
        <div class="sidebar-divider"></div>
        <a href="studentDashboard.php" class="active"><i class="bi bi-house"></i> Home</a>
        <a href="studentHome.php"><i class="bi bi-person"></i> Profile</a>
        <a href="appointment.php"><i class="bi bi-journal-plus"></i> Schedule Appointment</a>
        <a href="schedule.php"><i class="bi bi-journal-arrow-down"></i> My Appointments</a>
        <a href="services.php"><i class="bi bi-journal-album"></i> Service</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    <div class="top-bar">
        <span>Student Information System</span>
        <span><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($student_data['name'] ?? ''); ?></span>
    </div>
    <div class="dashboard-main">
        <div class="welcome-banner">
            <div class="avatar"><i class="bi bi-person-circle"></i></div>
            <div>
                <h2>Welcome, <?php echo htmlspecialchars($student_data['name'] ?? ''); ?>!</h2>
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
                <a href="#" class="btn btn-outline-primary btn-sm" onclick="alert('Show notifications modal!')">View All</a>
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>