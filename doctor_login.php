<?php
session_start();
include 'config.php';

// PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoloader

$message = '';
$messageType = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } else {
        // Check if doctor exists
        $sql = "SELECT * FROM doctors WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $doctor = $result->fetch_assoc();
            
            // Direct password comparison (plain text)
            if ($password === $doctor['Password']) {
                $_SESSION['doctor_id'] = $doctor['DoctorID'];
                $_SESSION['doctor_name'] = $doctor['FirstName'] . ' ' . $doctor['LastName'];
                $_SESSION['doctor_email'] = $doctor['Email'];
                
                header("Location: doctor_dashboard.php");
                exit();
            } else {
                $message = "Invalid email or password.";
                $messageType = "danger";
            }
        } else {
            $message = "Invalid email or password.";
            $messageType = "danger";
        }
    }
}

// Handle Forgot Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['forgot_email']);
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $messageType = "danger";
    } else {
        // Check if email exists
        $sql = "SELECT * FROM doctors WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $doctor = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            // Fixed: Use PHP's date function with +2 hours for longer expiry
            $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
            
            // Store reset token in database
            $update_sql = "UPDATE doctors SET reset_token = ?, reset_expires = ? WHERE Email = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sss", $token, $expires, $email);
            
            if ($update_stmt->execute()) {
                // Debug: Log the token generation
                error_log("Token generated for $email: $token, expires: $expires");
                
                // Send reset email
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'clinicauthentication@gmail.com'; // Your email
                    $mail->Password   = 'ierxkcmkmxftggkw';    // Your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    // Recipients
                    $mail->setFrom('clinicauthentication@gmail.com', 'Medical Clinic System');
                    $mail->addAddress($email, $doctor['FirstName'] . ' ' . $doctor['LastName']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - Medical Clinic';
                    
                    $resetLink = "http://localhost/MedicalClinic/doctor_resetpass.php?token=" . $token;
                    
                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                        <div style='background-color: #2e7d32; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                            <h1 style='margin: 0; font-size: 28px;'>Medical Clinic System</h1>
                            <p style='margin: 10px 0 0 0; font-size: 16px;'>Password Reset Request</p>
                        </div>
                        <div style='background-color: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                                Dear Dr. " . htmlspecialchars($doctor['FirstName'] . ' ' . $doctor['LastName']) . ",
                            </p>
                            <p style='font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 25px;'>
                                We received a request to reset your password for your Medical Clinic account. 
                                Click the button below to create a new password:
                            </p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='" . $resetLink . "' 
                                   style='display: inline-block; background-color: #2e7d32; color: white; padding: 15px 30px; 
                                          text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;'>
                                    Reset Password
                                </a>
                            </div>
                            <p style='font-size: 14px; color: #666; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;'>
                                This link will expire in 2 hours. If you didn't request this password reset, please ignore this email.
                            </p>
                            <p style='font-size: 12px; color: #999; margin-top: 15px;'>
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href='" . $resetLink . "' style='color: #2e7d32; word-break: break-all;'>" . $resetLink . "</a>
                            </p>
                        </div>
                    </div>";
                    
                    $mail->send();
                    $message = "Password reset link has been sent to your email address.";
                    $messageType = "success";
                    
                } catch (Exception $e) {
                    $message = "Failed to send reset email. Please try again later. Error: " . $mail->ErrorInfo;
                    $messageType = "danger";
                }
            } else {
                $message = "Error processing request. Please try again.";
                $messageType = "danger";
            }
        } else {
            // Don't reveal if email exists or not for security
            $message = "If your email address exists in our system, you will receive a password reset link.";
            $messageType = "info";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - Medical Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            background: linear-gradient(rgba(46, 125, 50, 0.8), rgba(46, 125, 50, 0.8)), url('loginbg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(46, 125, 50, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(45deg, #2e7d32, #60ad5e, #388e3c);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 8px rgba(46, 125, 50, 0.3));
        }
        
        .login-title {
            color: #2e7d32;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 45px 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fafafa;
        }
        
        .form-control:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
            background-color: #fff;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #2e7d32;
            font-size: 1.1rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #2e7d32;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #388e3c;
        }
        
        .btn-login {
            background: linear-gradient(45deg, #2e7d32, #388e3c);
            border: none;
            border-radius: 12px;
            padding: 14px;
            width: 100%;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.4);
        }
        
        .forgot-password-link {
            color: #2e7d32;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password-link:hover {
            color: #388e3c;
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-weight: 500;
            animation: fadeInDown 0.5s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .forgot-password-form {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .back-to-login {
            color: #2e7d32;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }
        
        .back-to-login:hover {
            color: #388e3c;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            z-index: 1000;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #2e7d32;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
                max-width: none;
            }
            
            .login-title {
                font-size: 1.75rem;
            }
            
            body {
                padding: 20px;
                align-items: flex-start;
                padding-top: 50px;
            }
        }
        
        .form-floating {
            position: relative;
        }
        
        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }
        
        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
            color: #6c757d;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
        </div>
        
        <div class="logo-container">
            <img src="MedicalClinicLogo.png" alt="Medical Clinic Logo">
            <h1 class="login-title">Doctor Portal</h1>
            <p class="login-subtitle">Secure Access to Medical Clinic System</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>" role="alert">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="position-relative">
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="Enter your email address" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="position-relative">
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Enter your password">
                    <i class="bi bi-eye password-toggle" id="passwordToggle"></i>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-login" name="login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </div>
            
            <div class="text-center">
                <a href="#" class="forgot-password-link" id="showForgotPassword">
                    <i class="bi bi-key me-1"></i>Forgot your password?
                </a>
            </div>
        </form>
        
        <!-- Forgot Password Form -->
        <form id="forgotPasswordForm" class="forgot-password-form" method="POST" action="">
            <div class="text-center mb-3">
                <h3 style="color: #2e7d32; font-size: 1.5rem; margin-bottom: 10px;">Reset Password</h3>
                <p style="color: #666; font-size: 0.9rem;">Enter your email address and we'll send you a reset link</p>
            </div>
            
            <div class="form-group">
                <label for="forgot_email" class="form-label">Email Address</label>
                <div class="position-relative">
                    <input type="email" class="form-control" id="forgot_email" name="forgot_email" required 
                           placeholder="Enter your email address">
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-login" name="forgot_password">
                    <i class="bi bi-send me-2"></i>Send Reset Link
                </button>
            </div>
            
            <div class="text-center">
                <a href="#" class="back-to-login" id="backToLogin">
                    <i class="bi bi-arrow-left"></i>Back to login
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
        
        // Form switching functionality
        const loginForm = document.getElementById('loginForm');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const showForgotPassword = document.getElementById('showForgotPassword');
        const backToLogin = document.getElementById('backToLogin');
        
        showForgotPassword.addEventListener('click', function(e) {
            e.preventDefault();
            loginForm.style.display = 'none';
            forgotPasswordForm.style.display = 'block';
        });
        
        backToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            forgotPasswordForm.style.display = 'none';
            loginForm.style.display = 'block';
        });
        
        // Loading overlay functionality
        const forms = document.querySelectorAll('form');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                loadingOverlay.style.display = 'flex';
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });
        
        // Enhanced form validation
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#2e7d32';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(220, 53, 69)') {
                    this.style.borderColor = '#e0e0e0';
                }
            });
        });
        
        // Prevent multiple form submissions
        let isSubmitting = false;
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }
                isSubmitting = true;
                
                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    isSubmitting = false;
                    loadingOverlay.style.display = 'none';
                }, 3000);
            });
        });
    </script>
</body>
</html>