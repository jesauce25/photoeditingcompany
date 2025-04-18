<?php
// Include session check to ensure only authorized users can access
require_once("includes/session_check.php");

include("includes/header.php");
?>
<div class="wrapper">
    <?php include("includes/nav.php"); ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if (isset($access_denied) && $access_denied): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Access Denied!</h5>
                        <?php echo $access_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Dashboard content -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Welcome!</h3>
                            </div>
                            <div class="card-body">
                                <p>Welcome to the Project Management System.</p>
                                <p>You are logged in as: <strong><?php echo $_SESSION['role']; ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include("includes/footer.php"); ?>