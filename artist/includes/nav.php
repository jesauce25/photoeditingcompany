<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user's information
$current_user = $_SESSION['first_name'] ?? 'Artist';
$current_role = $_SESSION['role'] ?? 'Artist';
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
                        <span class="nav-link" data-toggle="dropdown" style="cursor: pointer; color: white;">
                            <i class="far fa-bell"></i>
                            <span class="badge badge-warning navbar-badge">15</span>
                        </span>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right ">
                            <span class="dropdown-item dropdown-header">15 Notifications</span>
                            <div class=" dropdown-divider">
                            </div>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-envelope mr-2"></i> 4 new messages
                                <span class="float-right text-muted text-sm">3 mins</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-users mr-2"></i> 8 friend requests
                                <span class="float-right text-muted text-sm">12 hours</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-file mr-2"></i> 3 new reports
                                <span class="float-right text-muted text-sm">2 days</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
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