<?php
require_once 'session_helper.php';

// Remember me cookie logic
if (!isset($_SESSION['studentID']) && isset($_COOKIE['student_remember_email'])) {
    $rememberedEmail = filter_var($_COOKIE['student_remember_email'], FILTER_SANITIZE_EMAIL);
} else {
    $rememberedEmail = '';
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($email) || empty($password)) {
        $error_message = 'Email and Password cannot be empty.';
    } else {
        // Database connection
        $conn = new mysqli("localhost", "root", "", "medicalclinicnotify");

        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }

        // Get user data with a single query
        $sql = "SELECT studentID, email, password, FirstName, LastName FROM students WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Initialize session with user data
            $userData = [
                'FirstName' => $user['FirstName'],
                'LastName' => $user['LastName'],
                'email' => $user['email']
            ];
            initializeSession($user['studentID'], $userData);

            if ($remember) {
                // Set cookie with parameters suitable for local development
                setcookie(
                    'student_remember_email', 
                    $email, 
                    [
                        'expires' => time() + (86400 * 30), // 30 days
                        'path' => '/',
                        'secure' => false,     // Allow HTTP for local development
                        'httponly' => true,    // Not accessible via JavaScript
                        'samesite' => 'Lax'    // Less strict for local development
                    ]
                );
            } else {
                // Remove the cookie if "Remember Me" is not checked
                setcookie(
                    'student_remember_email', 
                    '', 
                    [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'secure' => false,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            }
            header("Location: otp.php");
            exit();
        } else {
            $error_message = 'Email or password is incorrect.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgb(141, 206, 243) 0%, #011f4b 100%);
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        .login-container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(10px);
        }
        .animation-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
            background: rgb(141, 206, 243);
        }
        .animation-container::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 1px;
            background: linear-gradient(to bottom, 
                rgba(1, 31, 75, 0) 0%,
                rgba(1, 31, 75, 0.2) 50%,
                rgba(1, 31, 75, 0) 100%);
        }
        .animation-container img {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .form-container {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .form-header h2 {
            color: #011f4b;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .form-header p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #011f4b;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-group input:focus {
            outline: none;
            border-color: #011f4b;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(1, 31, 75, 0.1);
        }
        .right-icon {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #666;
            font-size: 18px;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #011f4b;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #024351;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(1, 31, 75, 0.2);
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .register-link a {
            color: #011f4b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .register-link a:hover {
            color: #024351;
            text-decoration: underline;
        }
        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
            display: block;
        }
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            .animation-container {
                padding: 20px;
            }
            .form-container {
                padding: 30px;
            }
        }
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            .login-container {
                width: 100%;
                border-radius: 15px;
            }
            .form-container {
                padding: 25px;
            }
            .form-header h2 {
                font-size: 24px;
            }
            .form-group input {
                padding: 10px 12px;
                font-size: 13px;
            }
            .submit-btn {
                padding: 10px;
                font-size: 14px;
            }
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #011f4b;
            user-select: none;
            position: relative;
            padding-left: 28px;
        }
        .checkbox-container input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .checkmark {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 18px;
            width: 18px;
            background-color: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .checkbox-container:hover .checkmark {
            border-color: #011f4b;
        }
        .checkbox-container input:checked ~ .checkmark {
            background-color: #011f4b;
            border-color: #011f4b;
        }
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        .checkbox-container .checkmark:after {
            left: 5px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
    </style>
    <!-- Include Lottie script -->
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
</head>
<body>
    <!-- Login form -->
    <div class="login-container">
        <div class="animation-container">
            <dotlottie-player 
                src="https://lottie.host/fa8a5e18-1af9-434f-8d12-01ca3aa91e15/dDkcGIx87f.lottie" 
                background="transparent" 
                speed="1" 
                style="width: 100%; max-width: 400px; height: auto;" 
                loop 
                autoplay>
            </dotlottie-player>
        </div>
        <div class="form-container">
            <div class="form-header">
                <h2>Welcome Back!</h2>
                <p>Login to access your Medical Clinic Notify+ account</p>
            </div>
            <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                <div class="message success-message" style="background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 12px; border-radius: 8px; font-size: 14px; text-align: center; margin-bottom: 12px; display: block;">
                    <i class="bi bi-check-circle-fill" style="color:#2e7d32; margin-right:7px; font-size:1.2em; vertical-align:middle;"></i>
                    Registration successful! Please log in.
                </div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($rememberedEmail); ?>" required>
                    <i class="bi bi-envelope right-icon"></i>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <span class="right-icon" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleEye"></i>
                    </span>
                </div>
                <div class="form-group remember-me">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" id="remember" <?php echo !empty($rememberedEmail) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="forgot_password.php" style="color: #011f4b; text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.3s ease;">Forgot Password?</a>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Login</button>
                <div class="error-message" style="display:<?php echo !empty($error_message) ? 'block' : 'none'; ?>; background: #fbeaea; color: #c0392b; border: 1px solid #f5c6cb; padding: 12px; border-radius: 8px; font-size: 14px; text-align: center; margin-top: 12px;">
                    <?php if (!empty($error_message)): ?>
                        <i class="bi bi-exclamation-triangle-fill" style="color:#c0392b; margin-right:7px; font-size:1.2em; vertical-align:middle;"></i>
                        <?php echo $error_message; ?>
                    <?php endif; ?>
                </div>
            </form>
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleEye');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }
    </script>
</body>
</html>
