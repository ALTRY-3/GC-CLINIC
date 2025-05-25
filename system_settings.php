<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header('location:admin_login.php');
    exit;
}

$admin_id = $_SESSION['adminID'];

// Fetch admin data
$admin_query = "SELECT * FROM admins WHERE adminID = ? LIMIT 1";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("s", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_data = $admin_result->fetch_assoc();
$admin_stmt->close();

// Initialize variables
$updateMessage = '';
$updateStatus = '';

// Check if system_settings table exists, if not create it
$check_settings_table = "SHOW TABLES LIKE 'system_settings'";
$table_exists = $conn->query($check_settings_table)->num_rows > 0;

if (!$table_exists) {
    // Create system_settings table
    $create_table_sql = "
    CREATE TABLE `system_settings` (
        `setting_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `setting_key` varchar(100) NOT NULL UNIQUE,
        `setting_value` text NOT NULL,
        `setting_type` varchar(50) DEFAULT 'text',
        `category` varchar(50) DEFAULT 'general',
        `description` text,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `updated_by` varchar(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->query($create_table_sql);
    
    // Insert default settings
    $default_settings = [
        ['clinic_name', 'GSIS General Clinic', 'text', 'general', 'Name of the clinic'],
        ['clinic_address', '123 Medical Center Drive, Healthcare City', 'text', 'general', 'Clinic address'],
        ['clinic_phone', '+1 (555) 123-4567', 'text', 'general', 'Clinic contact number'],
        ['clinic_email', 'admin@gsisclinic.com', 'email', 'general', 'Clinic email address'],
        ['appointment_duration', '30', 'number', 'appointments', 'Default appointment duration in minutes'],
        ['max_appointments_per_day', '50', 'number', 'appointments', 'Maximum appointments per day'],
        ['advance_booking_days', '30', 'number', 'appointments', 'How many days in advance can appointments be booked'],
        ['clinic_open_time', '08:00', 'time', 'schedule', 'Clinic opening time'],
        ['clinic_close_time', '17:00', 'time', 'schedule', 'Clinic closing time'],
        ['lunch_start_time', '12:00', 'time', 'schedule', 'Lunch break start time'],
        ['lunch_end_time', '13:00', 'time', 'schedule', 'Lunch break end time'],
        ['email_notifications', '1', 'checkbox', 'notifications', 'Enable email notifications'],
        ['sms_notifications', '0', 'checkbox', 'notifications', 'Enable SMS notifications'],
        ['auto_confirm_appointments', '0', 'checkbox', 'appointments', 'Automatically confirm appointments'],
        ['maintenance_mode', '0', 'checkbox', 'system', 'Enable maintenance mode'],
        ['session_timeout', '30', 'number', 'security', 'Session timeout in minutes'],
        ['max_login_attempts', '5', 'number', 'security', 'Maximum login attempts before lockout'],
        ['password_min_length', '6', 'number', 'security', 'Minimum password length'],
        ['backup_frequency', 'weekly', 'select', 'backup', 'Database backup frequency'],
        ['timezone', 'Asia/Manila', 'select', 'general', 'System timezone']
    ];
    
    $insert_stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($default_settings as $setting) {
        $insert_stmt->bind_param("sssss", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4]);
        $insert_stmt->execute();
    }
    $insert_stmt->close();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update system settings
        $settings_updated = 0;
        
        foreach ($_POST as $key => $value) {
            if ($key !== 'update_settings') {
                $update_sql = "UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sss", $value, $admin_id, $key);
                if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                    $settings_updated++;
                }
                $update_stmt->close();
            }
        }
        
        if ($settings_updated > 0) {
            $updateMessage = "System settings updated successfully! ($settings_updated settings modified)";
            $updateStatus = "success";
            
            // Log the activity
            $log_activity = "INSERT INTO system_logs (action, user_id, user_type, details) VALUES (?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_activity);
            $action = "System settings updated";
            $user_type = "admin";
            $details = "Updated $settings_updated system settings";
            $log_stmt->bind_param("ssss", $action, $admin_id, $user_type, $details);
            $log_stmt->execute();
            $log_stmt->close();
        } else {
            $updateMessage = "No changes were made to the settings.";
            $updateStatus = "info";
        }
    }
    
    if (isset($_POST['backup_database'])) {
        // Database backup functionality
        $backup_filename = 'clinic_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = 'backups/' . $backup_filename;
        
        // Create backups directory if it doesn't exist
        if (!is_dir('backups')) {
            mkdir('backups', 0777, true);
        }
        
        // Simple backup command (you might need to adjust this based on your server configuration)
        $command = "mysqldump --user=root --password= --host=localhost medicalclinic > $backup_path";
        $result = shell_exec($command);
        
        if (file_exists($backup_path)) {
            $updateMessage = "Database backup created successfully: $backup_filename";
            $updateStatus = "success";
            
            // Log the activity
            $log_activity = "INSERT INTO system_logs (action, user_id, user_type, details) VALUES (?, ?, ?, ?)";
            $log_stmt = $conn->prepare($log_activity);
            $action = "Database backup created";
            $user_type = "admin";
            $details = "Backup file: $backup_filename";
            $log_stmt->bind_param("ssss", $action, $admin_id, $user_type, $details);
            $log_stmt->execute();
            $log_stmt->close();
        } else {
            $updateMessage = "Failed to create database backup. Please check server permissions.";
            $updateStatus = "danger";
        }
    }
    
    if (isset($_POST['clear_logs'])) {
        // Clear system logs
        $clear_sql = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $clear_result = $conn->query($clear_sql);
        
        if ($clear_result) {
            $affected_rows = $conn->affected_rows;
            $updateMessage = "Old system logs cleared successfully. ($affected_rows records removed)";
            $updateStatus = "success";
        } else {
            $updateMessage = "Failed to clear system logs.";
            $updateStatus = "danger";
        }
    }
}

// Fetch current settings
$settings_query = "SELECT * FROM system_settings ORDER BY category, setting_key";
$settings_result = $conn->query($settings_query);
$settings = [];

if ($settings_result && $settings_result->num_rows > 0) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['category']][] = $row;
    }
}

// Get recent backups
$backup_files = [];
if (is_dir('backups')) {
    $backup_files = array_diff(scandir('backups'), array('.', '..'));
    rsort($backup_files); // Most recent first
    $backup_files = array_slice($backup_files, 0, 5); // Only show last 5
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Medical Clinic Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #1b5e20;
            --text-dark: #263238;
            --text-medium: #546e7a;
            --text-light: #78909c;
            --surface-light: #f5f7fa;
            --surface-medium: #e1e5eb;
            --shadow-sm: 0 2px 6px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --radius-sm: 6px;
            --radius-md: 12px;
        }
        
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-light);
            color: var(--text-dark);
        }
        
        /* Layout - Same as admin dashboard */
        .app-container {
            display: grid;
            min-height: 100vh;
            grid-template-columns: auto 1fr;
            grid-template-rows: auto 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
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
        }
        
        .header-expanded {
            margin-left: 260px;
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
        }
        
        .toggle-sidebar:hover {
            background: var(--surface-light);
        }
        
        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            width: 260px;
            background: var(--primary);
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            z-index: 100;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-collapsed {
            transform: translateX(-260px);
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
        }
        
        .sidebar-logo {
            width: 70%;
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
            padding: 14px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .sidebar-menu a:hover {
            background: var(--primary-light);
            padding-left: 30px;
        }
        
        .sidebar-menu a.active {
            background: var(--primary-light);
            border-right: 4px solid white;
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            font-size: 1.2rem;
            transition: transform 0.2s;
        }
        
        .sidebar-menu a:hover i {
            transform: translateX(3px);
        }
        
        .sidebar-menu:last-child {
            margin-top: auto;
            padding-bottom: 20px;
        }
        
        .logout-link {
            color: #ffcdd2 !important;
            transition: all 0.3s ease !important;
        }

        .logout-link:hover {
            background: #d32f2f !important;
            color: white !important;
            padding-left: 22px !important;
        }

        .logout-link i {
            color: #ffcdd2 !important;
        }

        .logout-link:hover i {
            color: white !important;
        }
        
        /* Main Content */
        .main-content {
            grid-area: main;
            padding: 30px;
            transition: all 0.3s ease;
            background-color: var(--surface-light);
        }
        
        .main-expanded {
            margin-left: 260px;
        }
        
        /* Page Header */
        .page-header {
            background: white;
            padding: 25px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary);
        }

        .page-header h1 {
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .page-header p {
            color: var(--text-medium);
            margin: 0;
            font-size: 1rem;
        }
        
        /* Settings Sections */
        .settings-section {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid var(--surface-medium);
            display: flex;
            align-items: center;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .section-header i {
            margin-right: 12px;
            color: var(--primary);
            font-size: 1.3rem;
        }
        
        .section-content {
            padding: 25px;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid var(--surface-medium);
            border-radius: var(--radius-sm);
            padding: 12px 15px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.15);
            outline: none;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .setting-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .setting-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .setting-description {
            font-size: 0.9rem;
            color: var(--text-medium);
            margin-top: 5px;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: var(--radius-sm);
            font-weight: 500;
            padding: 12px 24px;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            background: transparent;
            border-radius: var(--radius-sm);
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-warning {
            background-color: #f57c00;
            border-color: #f57c00;
            color: white;
            border-radius: var(--radius-sm);
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s;
        }
        
        .btn-warning:hover {
            background-color: #ef6c00;
            border-color: #ef6c00;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background-color: #d32f2f;
            border-color: #d32f2f;
            color: white;
            border-radius: var(--radius-sm);
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s;
        }
        
        .btn-danger:hover {
            background-color: #c62828;
            border-color: #c62828;
            transform: translateY(-1px);
        }
        
        /* Alert */
        .alert {
            border-radius: var(--radius-sm);
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
        }
        
        /* Backup Files List */
        .backup-file {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
        }
        
        .backup-file:last-child {
            margin-bottom: 0;
        }
        
        .file-info {
            display: flex;
            align-items: center;
        }
        
        .file-info i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .file-name {
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .file-size {
            font-size: 0.9rem;
            color: var(--text-medium);
            margin-left: 10px;
        }
        
        /* Grid Layout for Settings */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-260px);
            }
            
            .header, .main-content {
                margin-left: 0 !important;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .page-header,
            .section-content {
                padding: 20px;
            }
            
            .section-header {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="img/GCLINIC.png" alt="Medical Clinic Logo" class="sidebar-logo">
            </div>
            <div class="sidebar-divider"></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a></li>
                <li><a href="admin_profile.php">
                    <i class="bi bi-person-circle"></i> My Profile
                </a></li>
                <li><a href="staff_management.php">
                    <i class="bi bi-people-fill"></i> Staff Management
                </a></li>
                <li><a href="student_management.php">
                    <i class="bi bi-person-vcard"></i> Student Management
                </a></li>
                <li><a href="appointment_management.php">
                    <i class="bi bi-calendar-check"></i> Appointments
                </a></li>
                <li><a href="admin_report.php">
                    <i class="bi bi-graph-up"></i> Reports
                </a></li>
                <li><a href="system_settings.php" class="active">
                    <i class="bi bi-gear"></i> System Settings
                </a></li>
            </ul>
            
            <div class="sidebar-divider" style="margin-top: auto;"></div>
            <ul class="sidebar-menu">
                <li><a href="admin_login.php" class="logout-link" onclick="return confirmLogout()">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a></li>
            </ul>
        </aside>
        
        <!-- Header -->
        <header class="header header-expanded" id="header">
            <div class="d-flex align-items-center">
                <button class="toggle-sidebar me-3" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="header-title">System Settings</h1>
            </div>
            
            <div class="header-actions">
                <a href="admin_dashboard.php" class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                
                <a href="admin_login.php" onclick="return confirmLogout()" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="main-content main-expanded" id="mainContent">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="bi bi-gear me-2"></i>System Settings</h1>
                <p>Configure system-wide settings and preferences for the Medical Clinic Management System</p>
            </div>
            
            <?php if (!empty($updateMessage)): ?>
            <div class="alert alert-<?= $updateStatus ?> alert-dismissible fade show" role="alert">
                <?= $updateMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="settings-grid">
                    <!-- Left Column -->
                    <div>
                        <!-- General Settings -->
                        <?php if (isset($settings['general'])): ?>
                        <div class="settings-section">
                            <div class="section-header">
                                <h3><i class="bi bi-building"></i>General Settings</h3>
                            </div>
                            <div class="section-content">
                                <?php foreach ($settings['general'] as $setting): ?>
                                <div class="setting-item">
                                    <label for="<?= htmlspecialchars($setting['setting_key']) ?>" class="form-label">
                                        <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                    </label>
                                    
                                    <?php if ($setting['setting_type'] === 'textarea'): ?>
                                        <textarea class="form-control" id="<?= htmlspecialchars($setting['setting_key']) ?>" name="<?= htmlspecialchars($setting['setting_key']) ?>" rows="3"><?= htmlspecialchars($setting['setting_value']) ?></textarea>
                                    <?php elseif ($setting['setting_type'] === 'select' && $setting['setting_key'] === 'timezone'): ?>
                                        <select class="form-control" id="<?= htmlspecialchars($setting['setting_key']) ?>" name="<?= htmlspecialchars($setting['setting_key']) ?>">
                                            <option value="Asia/Manila" <?= $setting['setting_value'] === 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila</option>
                                            <option value="America/New_York" <?= $setting['setting_value'] === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                            <option value="Europe/London" <?= $setting['setting_value'] === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                            <option value="Asia/Tokyo" <?= $setting['setting_value'] === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?= htmlspecialchars($setting['setting_type']) ?>" 
                                               class="form-control" 
                                               id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description"><?= htmlspecialchars($setting['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Appointment Settings -->
                        <?php if (isset($settings['appointments'])): ?>
                        <div class="settings-section">
                            <div class="section-header">
                                <h3><i class="bi bi-calendar-check"></i>Appointment Settings</h3>
                            </div>
                            <div class="section-content">
                                <?php foreach ($settings['appointments'] as $setting): ?>
                                <div class="setting-item">
                                    <label for="<?= htmlspecialchars($setting['setting_key']) ?>" class="form-label">
                                        <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                    </label>
                                    
                                    <?php if ($setting['setting_type'] === 'checkbox'): ?>
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                                   name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                                   value="1" 
                                                   <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                            <input type="hidden" name="<?= htmlspecialchars($setting['setting_key']) ?>" value="0">
                                        </div>
                                    <?php else: ?>
                                        <input type="<?= htmlspecialchars($setting['setting_type']) ?>" 
                                               class="form-control" 
                                               id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description"><?= htmlspecialchars($setting['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Schedule Settings -->
                        <?php if (isset($settings['schedule'])): ?>
                        <div class="settings-section">
                            <div class="section-header">
                                <h3><i class="bi bi-clock"></i>Schedule Settings</h3>
                            </div>
                            <div class="section-content">
                                <?php foreach ($settings['schedule'] as $setting): ?>
                                <div class="setting-item">
                                    <label for="<?= htmlspecialchars($setting['setting_key']) ?>" class="form-label">
                                        <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                    </label>
                                    <input type="<?= htmlspecialchars($setting['setting_type']) ?>" 
                                           class="form-control" 
                                           id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                           name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                           value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description"><?= htmlspecialchars($setting['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <!-- Notification Settings -->
                        <?php if (isset($settings['notifications'])): ?>
                        <div class="settings-section">
                            <div class="section-header">
                                <h3><i class="bi bi-bell"></i>Notification Settings</h3>
                            </div>
                            <div class="section-content">
                                <?php foreach ($settings['notifications'] as $setting): ?>
                                <div class="setting-item">
                                    <label for="<?= htmlspecialchars($setting['setting_key']) ?>" class="form-label">
                                        <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                    </label>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               value="1" 
                                               <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                        <input type="hidden" name="<?= htmlspecialchars($setting['setting_key']) ?>" value="0">
                                    </div>
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description"><?= htmlspecialchars($setting['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Security Settings -->
                        <?php if (isset($settings['security'])): ?>
                        <div class="settings-section">
                            <div class="section-header">
                                <h3><i class="bi bi-shield-check"></i>Security Settings</h3>
                            </div>
                            <div class="section-content">
                                <?php foreach ($settings['security'] as $setting): ?>
                                <div class="setting-item">
                                    <label for="<?= htmlspecialchars($setting['setting_key']) ?>" class="form-label">
                                        <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                    </label>
                                    <input type="<?= htmlspecialchars($setting['setting_type']) ?>" 
                                           class="form-control" 
                                           id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                           name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                           value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description"><?= htmlspecialchars($setting['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- System Settings -->
                        <?php if (isset($settings['system'])): ?>
                        <div class="settings-section">
                            <div class="section-header">
                                <h3><i class="bi bi-cpu"></i>System Settings</h3>
                            </div>
                            <div class="section-content">
                                <?php foreach ($settings['system'] as $setting): ?>
                                <div class="setting-item">
                                    <label for="<?= htmlspecialchars($setting['setting_key']) ?>" class="form-label">
                                        <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                    </label>
                                    
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               name="<?= htmlspecialchars($setting['setting_key']) ?>" 
                                               value="1" 
                                               <?= $setting['setting_value'] == '1' ? 'checked' : '' ?>>
                                        <input type="hidden" name="<?= htmlspecialchars($setting['setting_key']) ?>" value="0">
                                    </div>
                                    
                                    <?php if (!empty($setting['description'])): ?>
                                        <div class="setting-description"><?= htmlspecialchars($setting['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Save Settings Button -->
                <div class="settings-section">
                    <div class="section-content text-center">
                        <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Save All Settings
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- System Maintenance -->
            <div class="settings-section">
                <div class="section-header">
                    <h3><i class="bi bi-tools"></i>System Maintenance</h3>
                </div>
                <div class="section-content">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Database Backup</h5>
                            <p class="text-muted">Create a backup of your database for safety.</p>
                            <form method="POST" action="" class="d-inline">
                                <button type="submit" name="backup_database" class="btn btn-warning" onclick="return confirm('Are you sure you want to create a database backup?')">
                                    <i class="bi bi-database me-2"></i>Create Backup
                                </button>
                            </form>
                            
                            <?php if (!empty($backup_files)): ?>
                            <div class="mt-3">
                                <h6>Recent Backups:</h6>
                                <?php foreach ($backup_files as $file): ?>
                                <div class="backup-file">
                                    <div class="file-info">
                                        <i class="bi bi-file-earmark-zip"></i>
                                        <span class="file-name"><?= htmlspecialchars($file) ?></span>
                                        <span class="file-size"><?= number_format(filesize('backups/' . $file) / 1024, 2) ?> KB</span>
                                    </div>
                                    <a href="backups/<?= htmlspecialchars($file) ?>" class="btn btn-sm btn-outline-primary" download>
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>System Logs</h5>
                            <p class="text-muted">Clear old system logs to free up space.</p>
                            <form method="POST" action="" class="d-inline">
                                <button type="submit" name="clear_logs" class="btn btn-danger" onclick="return confirm('This will delete logs older than 30 days. Are you sure?')">
                                    <i class="bi bi-trash me-2"></i>Clear Old Logs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar functionality
            const sidebar = document.getElementById('sidebar');
            const header = document.getElementById('header');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            function toggleSidebar() {
                const isSidebarCollapsed = sidebar.classList.contains('sidebar-collapsed');
                
                if (isSidebarCollapsed) {
                    sidebar.classList.remove('sidebar-collapsed');
                    header.classList.add('header-expanded');
                    mainContent.classList.add('main-expanded');
                } else {
                    sidebar.classList.add('sidebar-collapsed');
                    header.classList.remove('header-expanded');
                    mainContent.classList.remove('main-expanded');
                }
            }
            
            function setInitialState() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.add('sidebar-collapsed');
                    header.classList.remove('header-expanded');
                    mainContent.classList.remove('main-expanded');
                }
            }
            
            sidebarToggle.addEventListener('click', toggleSidebar);
            
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.add('sidebar-collapsed');
                    header.classList.remove('header-expanded');
                    mainContent.classList.remove('main-expanded');
                }
            });
            
            window.confirmLogout = function() {
                return confirm('Are you sure you want to logout?');
            };
            
            // Handle checkbox inputs properly
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const hiddenInput = this.nextElementSibling;
                    if (hiddenInput && hiddenInput.type === 'hidden') {
                        hiddenInput.disabled = this.checked;
                    }
                });
            });
            
            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bootstrapAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bootstrapAlert.close();
                }, 5000);
            });
            
            setInitialState();
        });
    </script>
</body>
</html>