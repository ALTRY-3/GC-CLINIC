<?php 
session_start();
include 'db_connection.php'; 

// Check if the admin is logged in
if (!isset($_SESSION['adminID'])) {
    header('Location: admin_login.php');
    exit();
}

// Fetch the logged-in admin's details
$adminID = $_SESSION['adminID'];
$query = "SELECT * FROM admins WHERE adminID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adminID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
} else {
    echo "No admin data found.";
    exit();
}

$stmt->close();
$conn->close();

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
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
            border-radius: 0 !important;
        }
        .sidebar a i {
            margin-right: 14px;
            font-size: 1.25rem;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #e3f0fc;
            color: #1976d2;
            border-right: 6px solid #1976d2;
            border-radius: 0 !important;
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
        .top-bar .date-time-responsive {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            white-space: nowrap;
        }
        .top-bar .date-time-responsive i.bi-calendar-event {
            font-size: 1.2rem;
            transition: font-size 0.2s;
        }
        .top-bar .date-time-responsive .date-part {
            display: inline;
        }
        .top-bar .date-time-responsive .time-part {
            display: inline;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
            padding-top: 70px;
            transition: all 0.3s ease;
        }

        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 32px 0 rgba(25, 118, 210, 0.22), 0 2px 12px 0 rgba(1, 31, 75, 0.13);
            border: 1.5px solid #e3f0fc;
            border-left: 6px solid #1976d2;
            background: #f6faff;
            padding: 2.5rem 2rem 2rem 2rem !important;
        }

        .profile-container h1 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td, .table th {
            padding: 12px;
            vertical-align: middle;
        }

        .btn-primary {
            background-color: #011f4b;
            border-color: #011f4b;
            padding: 8px 20px;
            font-size: 0.95rem;
        }

        .btn-primary:hover {
            background-color: #023a7a;
            border-color: #023a7a;
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
            .profile-container {
                margin: 15px auto;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                font-size: 16px;
                height: 50px;
                padding: 0 10px;
            }
            .top-bar .date-time-responsive {
                font-size: 16px;
            }
            .top-bar .date-time-responsive .time-part {
                display: none !important;
            }
            .top-bar .date-time-responsive i.bi-calendar-event {
                font-size: 1rem;
            }
            .profile-container h1 {
                font-size: 1.5rem;
            }
            .table td, .table th {
                padding: 8px;
                font-size: 0.9rem;
            }
            .btn-primary {
                padding: 6px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .top-bar {
                font-size: 14px;
                padding: 0 10px;
            }
            .profile-container {
                padding: 10px;
                margin: 10px auto;
            }
            .profile-container h1 {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }
            .table td, .table th {
                padding: 6px;
                font-size: 0.85rem;
            }
            .btn-primary {
                width: 100%;
                margin-top: 10px;
            }
            .sidebar a {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            .sidebar img {
                width: 70%;
            }
        }

        /* Table responsive styles */
        @media (max-width: 480px) {
            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .table td, .table th {
                min-width: 120px;
            }
            .profile-container {
                box-shadow: none;
                border-radius: 0;
            }
            .top-bar {
                font-size: 14px;
                padding: 0 6px;
            }
            .top-bar .date-time-responsive {
                font-size: 14px;
            }
            .top-bar .date-time-responsive .time-part {
                display: none !important;
            }
            .top-bar .date-time-responsive i.bi-calendar-event {
                font-size: 0.9rem;
            }
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.25);
            z-index: 1500;
        }
        .sidebar-overlay.active {
            display: block;
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
        <div id="liveDateTime" class="date-time-responsive fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-calendar-event me-1"></i>
            <span class="date-part" id="datePart"></span>
            <span class="time-part" id="timePart"></span>
        </div>
    </div>

    <div class="main-content">
    <div class="profile-container shadow-sm border-0 p-4" style="max-width: 500px; margin: 32px auto; border-radius: 12px; background: #fff;">
        <div class="d-flex flex-column align-items-center mb-4">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-person-circle text-white" style="font-size: 2.8rem;"></i>
            </div>
            <h2 class="fw-bold mb-1" style="font-size: 1.5rem;">Admin Profile</h2>
        </div>
        <table class="table table-borderless mb-4">
            <tbody>
                <tr>
                    <th class="text-end" style="width: 40%;">ID</th>
                    <td class="ps-3"><?php echo htmlspecialchars($admin_data['adminID']); ?></td>
                </tr>
                <tr>
                    <th class="text-end">Name</th>
                    <td class="ps-3"><?php echo htmlspecialchars($admin_data['adminName']) . ' ' . htmlspecialchars($admin_data['adminLastName']); ?></td>
                </tr>
                <tr>
                    <th class="text-end">Email</th>
                    <td class="ps-3"><?php echo htmlspecialchars($admin_data['adminEmail']); ?></td>
                </tr>
                <tr>
                    <th class="text-end">Position</th>
                    <td class="ps-3">Admin</td>
                </tr>
                <tr>
                    <th class="text-end">Contact Number</th>
                    <td class="ps-3"><?php echo htmlspecialchars($admin_data['contactNumber']); ?></td>
                </tr>
            </tbody>
        </table>
        <div class="d-flex justify-content-center">
            <button type="button" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#updateProfileModal" style="font-size: 1.08rem;">
                <i class="bi bi-pencil-square me-2"></i>Update Profile
            </button>
        </div>
        <div id="profileUpdateAlert" class="mt-3"></div>
    </div>
</div>

<!-- Update Profile Modal -->
<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 border-0">
            <div class="modal-header bg-primary text-white rounded-top-4 d-flex align-items-center">
                <i class="bi bi-person-gear me-2" style="font-size:1.5rem;"></i>
                <h5 class="modal-title flex-grow-1" id="updateProfileModalLabel">Update Admin Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="updateProfileForm" method="POST" action="admin_update_profile.php">
                    <input type="hidden" name="adminID" value="<?php echo htmlspecialchars($admin_data['adminID']); ?>">
                    <input type="hidden" name="position" value="Admin">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="adminName" value="<?php echo htmlspecialchars($admin_data['adminName']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="adminLastName" value="<?php echo htmlspecialchars($admin_data['adminLastName']); ?>" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Middle Initial</label>
                            <input type="text" name="adminMiddleInitial" value="<?php echo htmlspecialchars($admin_data['adminMiddleInitial']); ?>" class="form-control" maxlength="1">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Email</label>
                            <input type="email" name="adminEmail" value="<?php echo htmlspecialchars($admin_data['adminEmail']); ?>" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-12">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contactNumber" value="<?php echo htmlspecialchars($admin_data['contactNumber']); ?>" class="form-control">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4 fw-semibold">Save Changes</button>
                    </div>
                </form>
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
            const updateProfileForm = document.getElementById('updateProfileForm');
            const updateProfileModal = document.getElementById('updateProfileModal');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            // Handle form submission
            updateProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('admin_update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message below the Update Profile button
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.getElementById('profileUpdateAlert').innerHTML = '';
                        document.getElementById('profileUpdateAlert').appendChild(alertDiv);
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(updateProfileModal);
                        modal.hide();
                        // Reload page after 1.5 seconds to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message in modal
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.modal-body').insertBefore(alertDiv, updateProfileForm);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        An error occurred while updating the profile.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('.modal-body').insertBefore(alertDiv, updateProfileForm);
                });
            });

            // Clear alerts when modal is closed
            updateProfileModal.addEventListener('hidden.bs.modal', function () {
                const alerts = this.querySelectorAll('.alert');
                alerts.forEach(alert => alert.remove());
            });

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

        // Live date and clock for navbar
        function updateDateTime() {
            const now = new Date();
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            const dateString = now.toLocaleDateString(undefined, options);
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            document.getElementById('datePart').textContent = dateString;
            document.getElementById('timePart').textContent = ` | ${timeString}`;
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();
    </script>
</body>
</html>
