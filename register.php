<?php
require 'configure.php'; // Ensure this connects to your DB and $conn is available

$errors = [];
$registrationSuccess = false;
$registrationMessage = '';

// --- Password Change Prompt Logic (DEMO for this page) ---
// On actual logged-in pages, you'd get this from the session after checking DB.
// For example:
// session_start();
// $showPasswordChangePrompt = false;
// if (isset($_SESSION['user_id']) && isset($_SESSION['password_last_changed_at'])) {
//     $lastChanged = new DateTime($_SESSION['password_last_changed_at']);
//     $now = new DateTime();
//     $interval = $lastChanged->diff($now);
//     if ($interval->days >= 1) {
//         $showPasswordChangePrompt = true;
//     }
// }
// For this registration page, let's assume it's false unless explicitly set for testing.
$showPasswordChangePrompt = false; // Set to true to see the prompt for demo

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Name Validation
    if (empty($_POST['name'])) {
        $errors['name'] = 'Full name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $_POST['name'])) {
        $errors['name'] = 'Invalid name. Please use only letters and spaces.';
    }

    // Email Validation
    if (empty($_POST['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check_email->bind_param("s", $_POST['email']);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors['email'] = 'This email address is already registered.';
        }
        $stmt_check_email->close();
    }


    // Contact Validation
    if (empty($_POST['contact'])) {
        $errors['contact'] = 'Contact number is required';
    } elseif (!preg_match('/^[0-9]{10}$/', $_POST['contact'])) {
        $errors['contact'] = 'Invalid contact number. Please enter a 10-digit number';
    }

    // Password Validation
    if (empty($_POST['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $_POST['password'])) {
        // \W matches any non-word character. _ is a word character, so include it if desired.
        // Or specify characters: (?=.*[!@#$%^&*(),.?":{}|<>])
        $errors['password'] = 'Password must be at least 12 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }

    // Confirm Password Validation
    if (empty($_POST['confirmPassword'])) {
        $errors['confirmPassword'] = 'Confirm password is required';
    } elseif ($_POST['password'] !== $_POST['confirmPassword']) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }

    if (empty($errors)) {
        // No 'require configure.php' needed here again if already at the top
        $name = $_POST['name'];
        $email = $_POST['email'];
        $contact = $_POST['contact'];
        $password_plain = $_POST['password']; // Keep plain for history check if needed on change
        $password_hashed = password_hash($password_plain, PASSWORD_BCRYPT);
        $current_timestamp = date('Y-m-d H:i:s'); // For password_last_changed_at

        // Insert into users table
        // Ensure your users table has 'password_last_changed_at' column
        $stmt = $conn->prepare("INSERT INTO users (name, email, contact, password, password_last_changed_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $contact, $password_hashed, $current_timestamp);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id; // Get the ID of the newly inserted user

            // Store the initial password in password_history
            // Ensure you have the password_history table created
            $stmt_history = $conn->prepare("INSERT INTO password_history (user_id, hashed_password) VALUES (?, ?)");
            $stmt_history->bind_param("is", $user_id, $password_hashed);
            $stmt_history->execute();
            $stmt_history->close();

            $registrationSuccess = true;
            $registrationMessage = "<h2>Registration Successful!</h2><p>You can now <a href='login.php'>login</a>.</p>";
        } else {
            // Check for duplicate email specifically if not caught above (e.g., race condition or DB constraint)
            if ($conn->errno == 1062) { // MySQL error code for duplicate entry
                 $errors['email'] = "This email address is already registered.";
                 $registrationMessage = "<h2>Error</h2><p>This email address is already registered. Please use a different email.</p>";
            } else {
                $registrationMessage = "<h2>Error</h2><p>Registration failed: " . htmlspecialchars($conn->error) . "</p>";
            }
        }
        $stmt->close();
    } else {
        // Construct a general error message if specific DB error didn't occur
        if (empty($registrationMessage) && !empty($errors)) {
            $registrationMessage = "<h2>Error</h2><p>Please correct the errors below and try again.</p>";
        }
    }
}
$conn->close(); // Close connection at the end of the script
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <style>
        /* ... (your existing CSS is good, keep it) ... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1e90ff, #00bfff);
            padding: 10px;
        }

        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 120%;
            object-fit: cover;
            z-index: -1;
        }

        .container {
            position: relative;
            padding: 50px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            width: 600px;
            max-width: 70%;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }

        .message-container {
            padding: 30px;
            width: 500px;
            max-width: 90%;
            text-align: center;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            font-size: 18px;
            color: #000;
        }

        .message-container h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .message-container a {
            color: #1591EA;
            font-weight: bold;
            text-decoration: none;
        }

        .message-container a:hover {
            text-decoration: underline;
        }

        h2 {
            font-size: 36px;
            color: #000000;
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-group label {
            font-size: 16px;
            color: #000000;
        }

        .input-group input {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            color: #333;
            width: 100%;
        }

        .input-group input:focus {
            outline: none;
            border-color: #1591EA;
            box-shadow: 0 0 5px rgba(89, 105, 230, 0.5);
        }

        button {
            background-color: #1591EA;
            color: rgb(19, 2, 2);
            height: 45px;
            width: 100%;
            border-radius: 40px;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 10px;
        }

        button:hover {
            transform: scale(1.05);
        }

        .error {
            color: red;
            font-size: 14px;
        }

        .login-footer {
            text-align: center;
            font-size: 16px;
            color:rgb(31, 29, 29);
            margin-top: 15px;
        }

        .login-footer a {
            text-decoration: none;
            color: #1591EA;
            font-weight: bold;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Style for the password change notification */
        .password-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #ffc107; /* Warning yellow */
            color: #333;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 14px;
            display: none; /* Hidden by default, JS will show it */
        }
        .password-notification a {
            color: #007bff;
            text-decoration: underline;
            font-weight: bold;
        }
        .password-notification .close-btn {
            position: absolute;
            top: 5px;
            right: 10px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #333;
        }


        @media (max-width: 768px) {
            .container, .message-container {
                padding: 30px;
            }
             .password-notification {
                width: calc(100% - 40px);
                bottom: 10px;
                right: 10px;
                left: 10px;
             }
        }

        @media (max-width: 480px) {
            .container, .message-container {
                padding: 20px;
            }
            h2 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>
    <?php if ($registrationSuccess): ?>
        <div class="message-container">
            <?= $registrationMessage ?>
        </div>
    <?php else: ?>
        <img src="https://i.pinimg.com/originals/fe/4b/c3/fe4bc367c796f800a0897599c2ba2022.gif" class="video-background" alt="Background GIF">
        <div class="container">
            <h2>Registration</h2>

            <?php if (!$registrationSuccess && !empty($registrationMessage) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <div class="message-container" style="background: rgba(255,0,0,0.1); border-color: rgba(255,0,0,0.3); margin-bottom:15px;">
                     <?= $registrationMessage ?>
                </div>
            <?php endif; ?>
            
            <form id="registrationForm" method="POST" action="register.php">
                <div class="input-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : '') ?>" placeholder="Enter your full name" required>
                    <span class="error"><?= isset($errors['name']) ? $errors['name'] : '' ?></span>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : '') ?>" placeholder="Enter your email" required>
                    <span class="error"><?= isset($errors['email']) ? $errors['email'] : '' ?></span>
                </div>

                <div class="input-group">
                    <label for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact" value="<?= htmlspecialchars(isset($_POST['contact']) ? $_POST['contact'] : '') ?>" placeholder="Enter 10-digit number" required pattern="[0-9]{10}">
                    <span class="error"><?= isset($errors['contact']) ? $errors['contact'] : '' ?></span>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Min 12 chars, Aa, 1, @" required>
                    <span class="error"><?= isset($errors['password']) ? $errors['password'] : '' ?></span>
                </div>

                <div class="input-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                    <span class="error"><?= isset($errors['confirmPassword']) ? $errors['confirmPassword'] : '' ?></span>
                </div>

                <button type="submit">Register</button>
            </form>

            <div class="login-footer">
                <p>Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Password Change Notification Area -->
    <div id="passwordChangeNotification" class="password-notification">
        <button class="close-btn" onclick="this.parentElement.style.display='none'">×</button>
        It's recommended to update your password periodically.
        <a href="change_password.php">Change Your Password</a>
    </div>

    <script>
        // This script would typically run on pages where the user is logged in.
        // The PHP part would determine if $showPasswordChangePrompt is true.
        const showPrompt = <?php echo json_encode($showPasswordChangePrompt); ?>;
        
        if (showPrompt) {
            const notification = document.getElementById('passwordChangeNotification');
            if (notification) {
                notification.style.display = 'block';
            }
        }

        // Optional: Add client-side validation hints for password complexity if you want immediate feedback
        // before form submission, though server-side validation is the authoritative one.
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const value = this.value;
                const errorSpan = this.parentElement.querySelector('.error');
                let errors = [];
                if (value.length < 12) errors.push("at least 12 characters");
                if (!/[a-z]/.test(value)) errors.push("a lowercase letter");
                if (!/[A-Z]/.test(value)) errors.push("an uppercase letter");
                if (!/\d/.test(value)) errors.push("a number");
                if (!/[\W_]/.test(value)) errors.push("a special character"); // \W is non-word, _ is word

                if (errors.length > 0 && errorSpan) {
                     // errorSpan.textContent = "Requires: " + errors.join(', ') + ".";
                     // Keep server-side error for consistency, or merge this carefully
                } else if (errorSpan) {
                    // errorSpan.textContent = ""; // Clear if valid by these rules
                }
            });
        }
    </script>
</body>
</html>