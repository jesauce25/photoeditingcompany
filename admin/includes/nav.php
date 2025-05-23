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
$current_user_id = $_SESSION['user_id'] ?? 0;

// Connect to database
require_once __DIR__ . '/../../includes/db_connection.php';

// Create notifications table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS tbl_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    entity_id INT,
    entity_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbl_users(user_id) ON DELETE CASCADE
)";
$conn->query($createTableSQL);

// Fetch user profile image
$user_profile_image = '../dist/img/user2-160x160.jpg'; // Default image
if ($current_user_id > 0) {
    // Query to get profile image
    $stmt = $conn->prepare("SELECT profile_img FROM tbl_users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['profile_img'])) {
            // Check if the path is already in the uploads directory or still in the old location
            $profile_img = $row['profile_img'];
            if (strpos($profile_img, 'assets/img/profile') !== false) {
                // Old path - we'll continue to use it for now
                $user_profile_image = '../' . $profile_img;
            } else {
                // Should be in the uploads directory
                $user_profile_image = '../uploads/profile_pictures/' . basename($profile_img);
            }
        }
    }
}

// Get notifications - fetch latest 10 unread notifications
$notifications = [];
$notificationCount = 0;

if ($current_user_id > 0) {
    // Count total unread notifications
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM tbl_notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
    $countStmt->bind_param("i", $current_user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    if ($countResult && $row = $countResult->fetch_assoc()) {
        $notificationCount = $row['count'];
    }

    // Get the latest notifications
    $notifStmt = $conn->prepare("SELECT * FROM tbl_notifications 
                                WHERE (user_id = ? OR user_id IS NULL) 
                                ORDER BY created_at DESC LIMIT 10");
    $notifStmt->bind_param("i", $current_user_id);
    $notifStmt->execute();
    $notifResult = $notifStmt->get_result();

    while ($notifResult && $row = $notifResult->fetch_assoc()) {
        $notifications[] = $row;
    }
}

// Check for overdue assignments and due tomorrow projects
$today = new DateTime('today');
$tomorrow = new DateTime('tomorrow');

// Find overdue assignments
$overdueAssignments = 0;
$overdueStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM tbl_project_assignments pa 
    JOIN tbl_projects p ON pa.project_id = p.project_id
    WHERE pa.deadline < CURDATE() 
    AND pa.status_assignee != 'completed' 
    AND pa.delay_acceptable = 0");
$overdueStmt->execute();
$overdueResult = $overdueStmt->get_result();
if ($overdueResult && $row = $overdueResult->fetch_assoc()) {
    $overdueAssignments = $row['count'];
}

// Find projects due tomorrow
$tomorrowProjects = 0;
$tomorrowStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM tbl_projects 
    WHERE DATE(deadline) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    AND status_project != 'completed'");
$tomorrowStmt->execute();
$tomorrowResult = $tomorrowStmt->get_result();
if ($tomorrowResult && $row = $tomorrowResult->fetch_assoc()) {
    $tomorrowProjects = $row['count'];
}

// Find tasks in approval stage
$approvalTasks = 0;
$approvalStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM tbl_project_assignments 
    WHERE status_assignee = 'qa'");
$approvalStmt->execute();
$approvalResult = $approvalStmt->get_result();
if ($approvalResult && $row = $approvalResult->fetch_assoc()) {
    $approvalTasks = $row['count'];
}

// Find tasks that just started (status changed to in_progress in last 24 hours)
$startedTasks = 0;
$startedStmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM tbl_project_assignments 
    WHERE status_assignee = 'in_progress'
    AND last_updated >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$startedStmt->execute();
$startedResult = $startedStmt->get_result();
if ($startedResult && $row = $startedResult->fetch_assoc()) {
    $startedTasks = $row['count'];
}

// Add system notifications based on these counts
// We'll add these at the beginning of the notifications array
$systemNotifications = [];

if ($overdueAssignments > 0) {
    $systemNotifications[] = [
        'message' => "You have $overdueAssignments overdue assignment(s)",
        'type' => 'danger',
        'icon' => 'fas fa-exclamation-triangle',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

if ($tomorrowProjects > 0) {
    $systemNotifications[] = [
        'message' => "$tomorrowProjects project(s) due tomorrow",
        'type' => 'warning',
        'icon' => 'fas fa-calendar-day',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

if ($approvalTasks > 0) {
    $systemNotifications[] = [
        'message' => "$approvalTasks task(s) awaiting approval",
        'type' => 'info',
        'icon' => 'fas fa-clipboard-check',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

if ($startedTasks > 0) {
    $systemNotifications[] = [
        'message' => "$startedTasks task(s) recently started",
        'type' => 'success',
        'icon' => 'fas fa-play-circle',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Update notification count to include system notifications
$notificationCount += count($systemNotifications);



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
        <!-- <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" id="notification-bell">
                <i class="far fa-bell"></i>
                <?php if ($notificationCount > 0): ?>
                    <span class="badge badge-warning navbar-badge"><?php echo $notificationCount; ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?php echo $notificationCount; ?> Notifications</span>
                <div class="dropdown-divider"></div>

                <?php foreach ($systemNotifications as $notification): ?>
                    <a href="#" class="dropdown-item">
                        <i class="<?php echo $notification['icon']; ?> mr-2 text-<?php echo $notification['type']; ?>"></i>
                        <?php echo $notification['message']; ?>
                        <span class="float-right text-muted text-sm">Just now</span>
                    </a>
                    <div class="dropdown-divider"></div>
                <?php endforeach; ?>

                <?php foreach ($notifications as $notification): ?>
                    <?php
                    // Determine the link based on entity_type and entity_id
                    $link = "#";
                    if (!empty($notification['entity_type']) && !empty($notification['entity_id'])) {
                        switch ($notification['entity_type']) {
                            case 'project':
                                $link = "view-project.php?id=" . $notification['entity_id'];
                                break;
                            case 'company':
                                $link = "view-company.php?id=" . $notification['entity_id'];
                                break;
                            case 'user':
                                $link = "view-user.php?id=" . $notification['entity_id'];
                                break;
                            case 'task':
                            case 'assignment':
                                $link = "task.php?id=" . $notification['entity_id'];
                                break;
                            default:
                                $link = "#";
                        }
                    }
                    ?>
                    <a href="<?php echo $link; ?>"
                        class="dropdown-item <?php echo $notification['is_read'] ? 'text-muted' : 'font-weight-bold'; ?>"
                        data-notification-id="<?php echo $notification['notification_id']; ?>">
                        <?php
                        // Determine icon based on notification type
                        $icon = 'fas fa-info-circle';
                        $iconClass = 'info';

                        switch ($notification['type']) {
                            case 'assignment':
                                $icon = 'fas fa-tasks';
                                $iconClass = 'primary';
                                break;
                            case 'project':
                                $icon = 'fas fa-project-diagram';
                                $iconClass = 'success';
                                break;
                            case 'deadline':
                                $icon = 'fas fa-calendar-alt';
                                $iconClass = 'warning';
                                break;
                            case 'user':
                                $icon = 'fas fa-user';
                                $iconClass = 'secondary';
                                break;
                            case 'warning':
                                $icon = 'fas fa-exclamation-triangle';
                                $iconClass = 'danger';
                                break;
                        }
                        ?>
                        <i class="<?php echo $icon; ?> mr-2 text-<?php echo $iconClass; ?>"></i>
                        <?php echo htmlspecialchars($notification['message']); ?>
                        <span class="float-right text-muted text-sm">
                            <?php
                            $created = new DateTime($notification['created_at']);
                            $now = new DateTime();
                            $diff = $created->diff($now);

                            if ($diff->days > 0) {
                                echo $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
                            } elseif ($diff->h > 0) {
                                echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
                            } elseif ($diff->i > 0) {
                                echo $diff->i . ' min' . ($diff->i > 1 ? 's' : '');
                            } else {
                                echo 'Just now';
                            }
                            ?>
                        </span>
                    </a>
                    <div class="dropdown-divider"></div>
                <?php endforeach; ?>
                <a href="#" class="dropdown-item dropdown-footer" id="clear-notifications">Clear All Notifications</a>
            </div>
        </li> -->
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
                        <img src="<?php echo $user_profile_image; ?>" alt="Logout" class="img-circle mb-2" width="60">
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
                        <img src="<?php echo $user_profile_image; ?>" class="img-circle elevation-2" alt="User Image">
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
                            <!-- <a href="delayed-project-list.php" class="nav-link text-sm">
                                <i class="fas fa-exclamation-circle nav-icon"></i>
                                <p>Delayed Project History</p>
                            </a> -->
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