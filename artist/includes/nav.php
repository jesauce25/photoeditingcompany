<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include notifications controller
require_once __DIR__ . '/../controllers/notifications_controller.php';

// Get current user's information
$current_user = $_SESSION['first_name'] ?? 'Artist';
$current_role = $_SESSION['role'] ?? 'Artist';
$user_id = $_SESSION['user_id'] ?? 0;

// Get notification count if user is logged in
$unread_count = 0;
if ($user_id > 0) {
    $unread_count = getUnreadNotificationsCount($user_id);
}
?>
<div class="background"></div>
<div class="floating-shapes"></div>
<div class="black-covers"></div>
<!-- Navbar -->
<nav class="navbar">
    <div class="nav-container">
        <!-- Hamburger Icon -->
        <!-- <button class="nav-toggle" id="navToggle">☰</button> -->

        <!-- Navigation Links -->
        <ul class="nav-links" id="navLinks">
            <div>
                <a href="home.php" class="nav-logo">RAFAELBPO</a>


            </div>

            <li><a href="home.php">Home</a></li>
            <li><a href="task.php">Task</a></li>
            <li><a href="history.php">History</a></li>
            <li class="dropdown">
                <div class="d-flex align-items-center ml-auto mr-3">
                    <!-- Notifications Dropdown Menu -->
                    <div class="nav-item dropdown mr-2">
                        <span class="nav-link" data-toggle="dropdown" style="cursor: pointer; color: white;" id="notificationDropdown">
                            <i class="far fa-bell"></i>
                            <span class="badge badge-warning navbar-badge" id="notificationCount"><?php echo $unread_count > 0 ? $unread_count : ''; ?></span>
                        </span>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" id="notificationMenu">
                            <span class="dropdown-item dropdown-header" id="notificationHeader"><?php echo $unread_count; ?> Notifications</span>
                            <div class="dropdown-divider"></div>
                            
                            <div id="notificationItems">
                                <!-- Notifications will be loaded dynamically here -->
                                <div class="dropdown-item text-center" id="notificationLoading">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading notifications...
                                </div>
                                <div class="dropdown-item text-center d-none" id="noNotifications">
                                    <i class="fas fa-check-circle mr-2"></i> No new notifications
                                </div>
                            </div>
                            
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item dropdown-footer" id="markAllRead">Mark All As Read</a>
                        </div>
                    </div>

                    <!-- Settings Dropdown Menu -->
                    <div class="nav-item dropdown">
                        <span class="text-light dropdown-toggle d-flex align-items-center" id="profileDropdown"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
                            <img src="https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&w=600"
                                alt="Profile" class="nav-profile-img me-2">
                            <span class="d-none d-md-inline"><?php echo $current_user; ?></span>
                        </span>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileDropdown">
                            <a class="dropdown-item" href="profile-settings.php">Profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item logout" href="../logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </li>
            <!-- dont remove this div or else mawa ang white background for the task.php -->
    </div>
    </li>

    </ul>
    </div>
</nav>