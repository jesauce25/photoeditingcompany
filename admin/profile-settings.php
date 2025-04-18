<?php
include("includes/header.php");
?>
<div class="wrapper">
    <?php include("includes/nav.php"); ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-user-cog mr-2"></i>Profile Settings</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Profile Settings</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-4">
                        <!-- Profile Card -->
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center position-relative">
                                    <img class="profile-user-img img-fluid img-circle"
                                        src="../dist/img/user2-160x160.jpg" alt="User profile picture">
                                    <button type="button" class="btn btn-sm btn-primary position-absolute"
                                        style="bottom: 0; right: 35%; border-radius: 50%; width: 32px; height: 32px; padding: 0;">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                                <h3 class="profile-username text-center">Joepet</h3>
                                <p class="text-muted text-center">CEO</p>


                            </div>
                        </div>
                    </div>

                    <!-- Settings Tabs -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header p-2">
                                <ul class="nav nav-pills">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#settings" data-toggle="tab">
                                            <i class="fas fa-cog mr-1"></i> Settings
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#security" data-toggle="tab">
                                            <i class="fas fa-shield-alt mr-1"></i> Security
                                        </a>
                                    </li>

                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Settings Tab -->
                                    <div class="active tab-pane" id="settings">
                                        <form class="form-horizontal">
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Full Name</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control" placeholder="John Doe"
                                                        value="John Doe">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Email</label>
                                                <div class="col-sm-10">
                                                    <input type="email" class="form-control"
                                                        placeholder="john@example.com" value="john@example.com">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Phone</label>
                                                <div class="col-sm-10">
                                                    <input type="tel" class="form-control" placeholder="+1234567890"
                                                        value="+1234567890">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Department</label>
                                                <div class="col-sm-10">
                                                    <input type="text" class="form-control"
                                                        placeholder="Project Management" value="Project Management">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-sm-2 col-form-label">Bio</label>
                                                <div class="col-sm-10">
                                                    <textarea class="form-control" rows="3"
                                                        placeholder="Tell something about yourself">Experienced project manager with a passion for delivering successful projects and leading high-performing teams.</textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="offset-sm-2 col-sm-10">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save mr-1"></i> Save Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Security Tab -->
                                    <div class="tab-pane" id="security">
                                        <form>
                                            <div class="form-group">
                                                <label>Current Password</label>
                                                <input type="password" class="form-control"
                                                    placeholder="Enter current password">
                                            </div>
                                            <div class="form-group">
                                                <label>New Password</label>
                                                <input type="password" class="form-control"
                                                    placeholder="Enter new password">
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm New Password</label>
                                                <input type="password" class="form-control"
                                                    placeholder="Confirm new password">
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-key mr-1"></i> Change Password
                                                </button>
                                            </div>
                                            <hr>

                                        </form>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<!-- ... existing code ... -->
<style>

</style>

<script>
</script>

<!-- ... existing code ... -->
<?php include("includes/footer.php"); ?>