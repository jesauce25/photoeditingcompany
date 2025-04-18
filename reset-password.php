<?php
// Check if token is provided
if (!isset($_GET["token"]) || empty($_GET["token"])) {
    die("Invalid request. Token is required.");
}

$token = $_GET["token"];
$token_hash = hash("sha256", $token);

// Include the database connection
require_once("includes/db_connection.php");

// Look up the token in the database
$sql = "SELECT user_id, token_expires FROM tbl_users WHERE reset_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired token. Please request a new password reset link.");
}

$user = $result->fetch_assoc();

// Check if the token has expired
if (strtotime($user["token_expires"]) <= time()) {
    // Clear expired token from database
    $update_sql = "UPDATE tbl_users SET reset_token = NULL, token_expires = NULL WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $user["user_id"]);
    $update_stmt->execute();

    die("Password reset token has expired. Please request a new password reset link.");
}

// Get error message if any
$error_message = "";
if (isset($_GET["error"]) && !empty($_GET["error"])) {
    $error_message = htmlspecialchars($_GET["error"]);
}

// Get success message if any
$success_message = "";
if (isset($_GET["success"]) && !empty($_GET["success"])) {
    $success_message = htmlspecialchars($_GET["success"]);
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Reset Password - Visayas Memorial Park</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 400px;
            max-width: 90%;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-weight: 600;
        }

        .input-group {
            position: relative;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        button {
            width: 100%;
            padding: 12px 0;
            background-color: #4a90e2;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #3a7bc8;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #4a90e2;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Reset Your Password</h1>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="post" action="process-reset-password.php" id="resetForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" required>
                    <i class="toggle-password fas fa-eye-slash" onclick="togglePasswordVisibility('password')"></i>
                </div>
                <div class="password-requirements">
                    Password must be at least 8 characters and include at least one number.
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-group">
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                    <i class="toggle-password fas fa-eye-slash"
                        onclick="togglePasswordVisibility('password_confirmation')"></i>
                </div>
            </div>

            <button type="submit">Reset Password</button>
        </form>

        <a href="login.php" class="back-link">Back to Login</a>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;

            // Toggle password visibility
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function (event) {
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            // Check password length
            if (password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }

            // Check if password contains at least one number
            if (!/\d/.test(password)) {
                event.preventDefault();
                alert('Password must contain at least one number.');
                return;
            }

            // Check if passwords match
            if (password !== passwordConfirmation) {
                event.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });
    </script>
</body>

</html>