<?php
session_start();
$loginError = '';
$forgotError = '';
$forgotSuccess = '';

require 'configure.php'; 

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $userCaptcha = $_POST['captcha'];
    $generatedCaptcha = $_POST['generated_captcha'];

    if ($userCaptcha !== $generatedCaptcha) {
        $loginError = "CAPTCHA is incorrect.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE name = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($pass, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['name'];

                header("Location: dashboard.php");
                exit();
            } else {
                $loginError = "Invalid password.";
            }
        } else {
            $loginError = "User not found.";
        }
    }
}

// Handle Forgot Password form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Generate a secure token
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Store token and expiry in a separate table or in users table (add columns reset_token, reset_expires)
        // For simplicity, assuming users table has these columns:
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expires, $user['id']);
        $stmt->execute();

        // Send reset link by email
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=$token";

        $subject = "Password Reset Request";
        $message = "Hi " . htmlspecialchars($user['name']) . ",\n\n";
        $message .= "We received a request to reset your password. Click the link below to reset it:\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you didn't request this, please ignore this email.";

        $headers = "From: no-reply@yourdomain.com\r\n";

        if (mail($email, $subject, $message, $headers)) {
            $forgotSuccess = "A password reset link has been sent to your email.";
        } else {
            $forgotError = "Failed to send reset email. Please try again later.";
        }
    } else {
        $forgotError = "Email address not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login Page</title>
    <style>
        /* Your existing styles here, fix the background gradient typo */
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, rgb(3, 101, 206), rgb(11, 84, 163));
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .login {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(10px);
            padding: 40px;
            width: 400px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(3, 2, 2, 0.1);
            position: relative;
        }

        .login h2 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
            color: #111;
        }

        .slogan {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
            color: black;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #222;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .captcha-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .captcha-box {
            background: #fff;
            padding: 10px 15px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
            flex-grow: 1;
            text-align: center;
            border: 1px solid #ccc;
        }

        .refresh-btn {
            background: rgb(3, 101, 206);
            border: none;
            color: white;
            padding: 8px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        #captcha-input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-bottom: 15px;
        }

        .button {
            background-color: #1591EA;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 40px;
            font-size: 18px;
            color: #111;
            cursor: pointer;
            transition: 0.3s;
        }

        .button:hover {
            transform: scale(1.05);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .login-footer a {
            color: rgb(3, 101, 206);
            font-weight: bold;
            text-decoration: none;
        }

        .error, .success {
            text-align: center;
            margin-bottom: 10px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }

        /* Forgot Password form toggle */
        #forgot-password-form {
            display: none;
        }

        .link-button {
            background: none;
            border: none;
            color: rgb(3, 101, 206);
            font-weight: bold;
            cursor: pointer;
            padding: 0;
            margin: 10px 0;
            text-align: center;
            text-decoration: underline;
            font-size: 14px;
        }
    </style>

    <script>
        function generateCaptcha() {
            let captcha = '';
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            for (let i = 0; i < 6; i++) {
                captcha += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('cap').innerText = captcha;
            document.getElementById('generated_captcha').value = captcha;
        }

        window.onload = generateCaptcha;

        function showForgotPassword() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('forgot-password-form').style.display = 'block';
            clearMessages();
        }

        function showLogin() {
            document.getElementById('login-form').style.display = 'block';
            document.getElementById('forgot-password-form').style.display = 'none';
            clearMessages();
        }

        function clearMessages() {
            const errors = document.querySelectorAll('.error');
            errors.forEach(e => e.innerText = '');
            const successes = document.querySelectorAll('.success');
            successes.forEach(s => s.innerText = '');
        }
    </script>
</head>
<body>
    <img src="https://i.pinimg.com/originals/fe/4b/c3/fe4bc367c796f800a0897599c2ba2022.gif" class="video-background" alt="Background GIF">

    <div class="login">

        <!-- Login Form -->
        <div id="login-form">
            <h2>Log In</h2>
            <p class="slogan">Welcome back! Secure access to your world.</p>

            <?php if ($loginError): ?>
                <div class="error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="login" value="1" />
                <div class="input-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" required>
                </div>

                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" required>
                </div>

                <div class="captcha-container">
                    <div class="captcha-box" id="cap"></div>
                    <button type="button" class="refresh-btn" onclick="generateCaptcha()">🔄</button>
                </div>

                <input type="hidden" id="generated_captcha" name="generated_captcha">
                <input type="text" id="captcha-input" name="captcha" placeholder="Enter CAPTCHA" required>

                <button class="button" type="submit">Log In</button>
            </form>

            <button class="link-button" onclick="showForgotPassword()">Forgot Password?</button>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Sign up</a></p>
            </div>
        </div>

        <!-- Forgot Password Form -->
        <div id="forgot-password-form">
            <h2>Forgot Password</h2>
            <p class="slogan">Enter your registered email to reset your password.</p>

            <?php if ($forgotError): ?>
                <div class="error"><?= htmlspecialchars($forgotError) ?></div>
            <?php endif; ?>
            <?php if ($forgotSuccess): ?>
                <div class="success"><?= htmlspecialchars($forgotSuccess) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="forgot_password" value="1" />
                <div class="input-group">
                    <label for="email">Email Address:</label>
                    <input type="email" name="email" required>
                </div>

                <button class="button" type="submit">Send Reset Link</button>
            </form>

            <button class="link-button" onclick="showLogin()">Back to Login</button>
        </div>
    </div>
</body>
</html>
