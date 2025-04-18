<?php
// Include session check to ensure only authorized users can access
require_once("includes/session_check.php");

include("includes/header.php");
?>


<?php include("includes/nav.php"); ?>

<!-- Hero Section with Grid Layout -->
<div class="container-fluid">
    <div class="row mx-3 mt-5 pt-4">
        <div class="col-md-2">
            <!-- Left Panel -->
            <div class="left-panel">
                <div class="profile-card glass">
                    <div class="profile-image-wrapper ">
                        <img src="https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg?auto=compress&cs=tinysrgb&w=600"
                            alt="Profile" class="profile-img floating" />
                        <div class="profile-status mr-2">
                            <span class="status-dot"></span>
                            <span class="status-text text-white">Available</span>
                        </div>
                    </div>
                    <h3 class="artist-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h3>
                    <p class="artist-title">Graphic Artist</p>
                    <div class="skill-pills">
                        <span class="skill-pill">UI/UX</span>
                        <span class="skill-pill">Branding</span>
                        <span class="skill-pill">3D</span>
                    </div>
                    <div class="performance-metrics">
                        <div class="metric-item achievement">
                            <div class="metric-icon"><i class="fas fa-tasks"></i></div>
                            <div class="metric-info">
                                <span class="metric-value">156</span>
                                <span class="metric-label">Total Tasks</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Top Panel -->
            <div class="top-panel row pt-2">
                <div class="stat-panel">
                    <div class="stat-card task-card overdue">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <span class="stat-value">3</span>
                            <span class="stat-label">Overdue Tasks</span>
                            <div class="stat-trend negative">
                                <i class="fas fa-arrow-up"></i> 2 new this week
                            </div>
                        </div>
                    </div>
                    <div class="stat-card task-card in-progress">
                        <div class="stat-icon"><i class="fas fa-spinner fa-spin"></i></div>
                        <div class="stat-info">
                            <span class="stat-value">8</span>
                            <span class="stat-label ">In Progress</span>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-up"></i> 3 active now
                            </div>
                        </div>
                    </div>
                    <div class="stat-card task-card pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-value">5</span>
                            <span class="stat-label">Pending QA</span>
                            <div class="stat-trend neutral">
                                <i class="fas fa-minus"></i> Waiting for approval
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Center Panel (Intro Text) -->
            <div class="col-md-12" id="introText">
                <h1>Maayung Buntag,</h1>
                <h1>Graphic Artist!</h1>
            </div>
        </div>

        <div class="col-md-2">
            <!-- Right Panel -->
            <div class=" right-panel">
                <div class="timeline-card glass">
                    <h3>Performance Insights</h3>
                    <div class="analytics-grid">
                        <div class="analytics-item achievement">
                            <div class="analytics-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="analytics-content">
                                <h4>Early Completions</h4>
                                <div class="progress-bar">
                                    <div class="progress"
                                        style="width: 85%; background: linear-gradient(90deg, #4CAF50, #45a049);"></div>
                                </div>
                                <p>45 Tasks Early</p>
                                <div class="insight-trend positive">
                                    <i class="fas fa-arrow-up"></i> 5 this month
                                </div>
                            </div>
                        </div>
                        <div class="analytics-item milestone">
                            <div class="analytics-icon"><i class="fas fa-ban"></i></div>
                            <div class="analytics-content">
                                <h4>Account Holds</h4>
                                <div class="progress-bar">
                                    <div class="progress"
                                        style="width: 25%; background: linear-gradient(90deg, #f44336, #d32f2f);"></div>
                                </div>
                                <p>7 Account Holds</p>
                                <div class="insight-trend negative">
                                    <i class="fas fa-arrow-up"></i> 2 new this month
                                </div>
                            </div>
                        </div>
                        <div class="analytics-item improvement">
                            <div class="analytics-icon"><i class="fas fa-clock"></i></div>
                            <div class="analytics-content">
                                <h4>On-Time Delivery</h4>
                                <div class="progress-bar">
                                    <div class="progress"
                                        style="width: 92%; background: linear-gradient(90deg, #2196F3, #1976D2);"></div>
                                </div>
                                <p>92% Success Rate</p>
                                <div class="insight-trend positive">
                                    <i class="fas fa-arrow-up"></i> 3% improvement
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row m-3 p-4">
        <!-- Bottom Panel -->
        <div class=" bottom-panel col-md-12">
            <div class="analytics-cards">
                <div class="mini-card glass task-card completed">
                    <div class="mini-card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="mini-card-info">
                        <h4>Completed Tasks</h4>
                        <span class="counter">156</span>
                        <div class="trend positive">
                            <i class="fas fa-arrow-up"></i> 12 this month
                        </div>
                    </div>
                    <div class="mini-card-chart">
                        <canvas id="miniChart1" height="40"></canvas>
                    </div>
                </div>
                <div class="mini-card glass task-card clip-pathing">
                    <div class="mini-card-icon"><i class="fas fa-cut"></i></div>
                    <div class="mini-card-info">
                        <h4>Clip Pathing</h4>
                        <span class="counter">45</span>
                        <div class="trend positive">
                            <i class="fas fa-arrow-up"></i> 5 this month
                        </div>
                    </div>
                    <div class="mini-card-chart">
                        <canvas id="miniChart2" height="40"></canvas>
                    </div>
                </div>
                <div class="mini-card glass task-card retouching">
                    <div class="mini-card-icon"><i class="fas fa-magic"></i></div>
                    <div class="mini-card-info">
                        <h4>Retouching</h4>
                        <span class="counter">101</span>
                        <div class="trend positive">
                            <i class="fas fa-arrow-up"></i> 12 this month
                        </div>
                    </div>
                    <div class="mini-card-chart">
                        <canvas id="miniChart3" height="40"></canvas>
                    </div>
                </div>
                <div class="mini-card glass task-card final">
                    <div class="mini-card-icon"><i class="fas fa-check-double"></i></div>
                    <div class="mini-card-info">
                        <h4>Final Tasks</h4>
                        <span class="counter">10</span>
                        <div class="trend positive">
                            <i class="fas fa-arrow-up"></i> 8 this month
                        </div>
                    </div>
                    <div class="mini-card-chart">
                        <canvas id="miniChart4" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<!-- Add Chart.js library if not already included -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->