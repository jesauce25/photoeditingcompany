<?php
// Simple authentication test script
session_start();

// Include necessary files
require_once 'includes/db_connection.php';
require_once 'includes/auth.php';

// Clear any previous session data
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    session_start();
}

// Process login if form submitted
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Attempt authentication
    $user = authenticate($username, $password);

    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

        $success_message = "Login successful! Role: {$user['role']}";

        // Demonstrate redirection logic (but don't actually redirect in this test)
        if ($user['role'] === 'Admin' || $user['role'] === 'Project Manager') {
            $redirect_url = 'admin/home.php';
        } elseif ($user['role'] === 'Graphic Artist') {
            $redirect_url = 'artist/home.php';
        } else {
            $redirect_url = 'index.php';
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .success {
            color: green;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
        }

        .error {
            color: red;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .test-accounts {
            margin-top: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }

        .session-info {
            margin-top: 20px;
            background-color: #e2f0fb;
            padding: 15px;
            border-radius: 4px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Authentication Test</h1>

        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
            <p>Would redirect to: <?php echo $redirect_url ?? 'N/A'; ?></p>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Login</button>
            <a href="?logout=1" style="margin-left: 10px;">Logout</a>
        </form>

        <div class="test-accounts">
            <h2>Test Accounts</h2>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Role</th>
                </tr>
                <tr>
                    <td>admin</td>
                    <td>admin123</td>
                    <td>Admin</td>
                </tr>
                <tr>
                    <td>manager</td>
                    <td>manager</td>
                    <td>Project Manager</td>
                </tr>
                <tr>
                    <td>art</td>
                    <td>art</td>
                    <td>Graphic Artist</td>
                </tr>
            </table>
        </div>

        <?php if (isset($_SESSION['username'])): ?>
            <div class="session-info">
                <h2>Current Session</h2>
                <p><strong>Username:</strong> <?php echo $_SESSION['username']; ?></p>
                <p><strong>Role:</strong> <?php echo $_SESSION['role']; ?></p>
                <p><strong>Full Name:</strong> <?php echo $_SESSION['full_name'] ?? 'Not available'; ?></p>
                <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>