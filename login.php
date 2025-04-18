<?php
// Start session
session_start();

// Include database connection and authentication functions
require_once("includes/db_connection.php");
require_once("includes/auth.php");

// Check if the user is already logged in, redirect if so
if (isLoggedIn()) {
    redirectBasedOnRole();
}

// Process form submission
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        // Authenticate user
        $user = authenticate($username, $password);

        if ($user) {
            // Create user session
            createUserSession($user);

            // Redirect based on role
            redirectBasedOnRole();
        } else {
            // For debugging, show SQL
            if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                global $conn;
                $sql = "SELECT * FROM tbl_accounts WHERE username = '$username'";
                $result = $conn->query($sql);
                echo "<div style='background: #eee; padding: 10px; margin: 10px;'>";
                echo "<h3>Debug Info (remove in production):</h3>";
                echo "Username: $username<br>";
                echo "Password: " . str_repeat("*", strlen($password)) . "<br>";
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo "Found user in database with these details:<br>";
                    echo "Username: " . $row['username'] . "<br>";
                    echo "Password in DB: " . $row['password'] . "<br>";
                    echo "Role: " . $row['role'] . "<br>";
                    echo "Status: " . $row['status'] . "<br>";
                } else {
                    echo "No user found with username: $username<br>";
                }
                echo "</div>";
            }

            $error_message = "Invalid username or password.";
        }
    }
}

include("includes/header.php");
?>

<body>
    <div class="background"></div>
    <div class="floating-shapes"></div>
    <div class="black-covers"></div>

    <div class="login-container">
        <h2>LOGIN</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="input-group">
                <input type="text" placeholder="Enter your username" id="username" name="username" required />
            </div>
            <div class="input-group">
                <input type="password" placeholder="Enter your password" id="password" name="password" required />
            </div>

            <button type="submit">
                <div class="text">
                    <span class="letter">L</span>
                    <span class="letter">O</span>
                    <span class="letter">G</span>
                    <span class="letter">I</span>
                    <span class="letter">N</span>
                </div>
            </button>

            <div class="forgot-password-container">
                <a href="forgot_password.php" id="forgotPassword">Forgot Password?</a>
            </div>
        </form>
    </div>

    <?php
    include("includes/footer.php");
    ?>