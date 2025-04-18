<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define user role variables
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$is_project_manager = isset($_SESSION['role']) && $_SESSION['role'] === 'Project Manager';

$current_user = $_SESSION['first_name'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'User';
?>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">

            <div class="navbar-search-block">
                <form class="form-inline">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" placeholder="Search"
                            aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">15 Notifications</span>
                <div class="dropdown-divider"></div>
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
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <!-- Logout Button -->
        <li class="nav-item dropdown">
            <a class="nav-link" href="#" data-toggle="dropdown">
                <i class="fas fa-power-off text-danger"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right p-0" style="min-width: 200px;">
                <div class="card card-danger card-outline m-0">
                    <div class="card-header text-center">
                        <h5>Ready to Leave?</h5>
                    </div>
                    <div class="card-body text-center pb-0">
                        <img src="../dist/img/logout-icon.png" alt="Logout" class="img-circle mb-2" width="60"
                            onerror="this.src='../dist/img/user2-160x160.jpg'">
                        <p class="text-muted">Select "Logout" if you are ready to end your current session.</p>
                    </div>
                    <div class="card-footer">
                        <a href="../logout.php" class="btn btn-danger btn-block">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </li>
    </ul>
</nav>
<!-- /.navbar -->
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="home.php" class="brand-link">
        <img src="../dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
            style="opacity: .8">
        <span class="brand-text font-weight-light">RafaelBPO</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3">
            <div class="d-flex">
                <div class="image">
                    <a href="profile-settings.php">
                        <img src="../dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                    </a>
                </div>
                <div class="info">
                    <a href="profile-settings.php" class="d-block"><?php echo $current_role; ?></a>
                    <small class="text-muted"><?php echo $current_user; ?></small>
                </div>
                <div class="ml-auto mr-3">
                    <a href="profile-settings.php" class="text-light" title="Edit Profile">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
                <li class="nav-item">
                    <a href="home.php" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        <p>
                            Home

                        </p>
                    </a>

                </li>
                <li class="nav-item">
                    <a href="" class="nav-link">
                        <i class="nav-icon fas fa-building"></i>
                        <p>
                            Company
                            <i class="fas fa-angle-left right"></i>
                            <span class="badge badge-info right">6</span>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="add-company.php" class="nav-link text-sm">
                                <i class="fas fa-plus nav-icon"></i>
                                <p>Add Company</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="company-list.php" class="nav-link text-sm">
                                <i class="far fa-eye nav-icon"></i>
                                <p>View Company List</p>
                            </a>
                        </li>

                    </ul>
                </li>
                <li class="nav-item">
                    <a href="" class="nav-link">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>
                            Project
                            <i class="fas fa-angle-left right"></i>
                            <span class="badge badge-info right">6</span>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="add-project.php" class="nav-link text-sm">
                                <i class="fas fa-plus nav-icon"></i>
                                <p>Add Project</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="project-list.php" class="nav-link text-sm">
                                <i class="far fa-eye nav-icon"></i>
                                <p>View Project</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="delayed-project-list.php" class="nav-link text-sm">
                                <i class="fas fa-exclamation-circle nav-icon"></i>
                                <p>Delayed Project History</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <?php if ($is_admin): ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-user-tie nav-icon"></i>
                            <p>
                                User Management
                                <i class="fas fa-angle-left right"></i>
                                <span class="badge badge-info right">2</span>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add-user.php" class="nav-link text-sm">
                                    <i class="fas fa-plus nav-icon"></i>
                                    <p>Add User</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="user-list.php" class="nav-link text-sm">
                                    <i class="far fa-eye nav-icon"></i>
                                    <p>View Users</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>