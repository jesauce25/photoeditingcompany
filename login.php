<?php
// Start session
session_start();

// Include database connection and authentication functions
require_once("includes/db_connection.php");
require_once("includes/auth.php");

// Debug session status
error_log("Login.php - Session status: " . (isset($_SESSION['user_logged_in']) ? 'logged in' : 'not logged in'));
error_log("Login.php - Role in session: " . ($_SESSION['role'] ?? 'not set'));

// Check if the user is already logged in, redirect if so
if (isLoggedIn()) {
    // Use role from session to determine redirect
    $role = $_SESSION['role'] ?? '';
    error_log("User already logged in with role: $role - redirecting appropriately");

    if ($role === 'admin' || $role === 'Admin' || $role === 'Project Manager') {
        header("Location: admin/home.php");
        exit;
    } else if ($role === 'art' || $role === 'Graphic Artist') {
        header("Location: artist/home.php");
        exit;
    }
    // If role is not recognized, we'll let them stay on the login page
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

            // Log session and role for debugging
            error_log("User authenticated successfully - Role: " . $_SESSION['role']);

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
<?php include("includes/nav.php"); ?>

<style>
    /* Center the login container */
    body {
        position: relative;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-container {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
        width: 90%;
        max-width: 400px;
    }

    /* Make sure background elements cover the viewport */
    .background,
    .floating-shapes,
    .black-covers {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
</style>

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