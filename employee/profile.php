<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

ensure_employee_portal_schema($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile'] ?? ''));
    $branch = mysqli_real_escape_string($conn, trim($_POST['branch_location'] ?? ''));

    mysqli_query($conn, "
        UPDATE users
        SET mobile='$mobile', branch_location='$branch'
        WHERE id='$user_id'
    ");

    log_activity($conn, $user_id, 'Updated profile details');
    $message = 'Profile details updated successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $photoError = '';
    $photoPath = save_profile_photo_upload($_FILES['profile_photo'] ?? null, $photoError);

    if ($photoPath) {
        $safePhoto = mysqli_real_escape_string($conn, $photoPath);
        mysqli_query($conn, "UPDATE users SET profile_photo='$safePhoto' WHERE id='$user_id'");
        log_activity($conn, $user_id, 'Updated profile photo');
        $message = 'Profile photo updated successfully.';
    } else {
        $error = $photoError;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $newPassword = trim($_POST['new_password'] ?? '');

    if (strlen($newPassword) < 6) {
        $error = 'Password must contain at least 6 characters.';
    } else {
        $safePassword = mysqli_real_escape_string($conn, $newPassword);
        mysqli_query($conn, "UPDATE users SET password='$safePassword' WHERE id='$user_id'");
        log_activity($conn, $user_id, 'Changed account password');
        $message = 'Password changed successfully.';
    }
}

$userResult = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($userResult);

$totalTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id'"));
$pendingTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='Pending'"));
$completedTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='Completed'"));
$overdueTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='Overdue'"));
$progressTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='In Progress'"));
$completionPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Arial,sans-serif;}
        body{min-height:100vh;background:#edf2f7;color:#0f172a;}
        .shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh;}
        .sidebar{background:linear-gradient(180deg,#0f172a,#1e293b);padding:22px;color:white;}
        .sidebar h2{font-size:24px;margin-bottom:24px;}
        .nav a{display:block;color:white;text-decoration:none;background:rgba(255,255,255,.07);padding:14px;border-radius:12px;margin-bottom:12px;}
        .nav a:hover,.nav a.active{background:#2563eb;}
        .logout{background:#dc2626!important;margin-top:22px!important;text-align:center;}
        .main{padding:32px;}
        .hero{background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:24px;color:white;padding:30px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:24px;}
        .hero-left{display:flex;align-items:center;gap:18px;}
        .hero img{width:96px;height:96px;border-radius:50%;object-fit:cover;border:5px solid #dbeafe;}
        .hero h1{font-size:36px;line-height:1.1;margin-bottom:8px;}
        .hero p{color:#cbd5e1;}
        .account-status{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;font-weight:700;font-size:13px;background:#dcfce7;color:#166534;}
        .account-status span{width:9px;height:9px;border-radius:50%;background:#16a34a;display:inline-block;}
        .account-status.inactive{background:#fee2e2;color:#991b1b;}
        .account-status.inactive span{background:#dc2626;}
        .content-grid{display:grid;grid-template-columns:1.4fr .8fr;gap:22px;}
        .panel,.task-card{background:white;border-radius:18px;padding:24px;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        .panel h2{font-size:24px;margin-bottom:18px;}
        .detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
        .detail-grid div{padding:16px;border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;}
        .detail-grid span{display:block;color:#64748b;font-size:12px;text-transform:uppercase;margin-bottom:7px;}
        .detail-grid strong{display:block;word-break:break-word;}
        .tasks{display:grid;gap:14px;}
        .task-card{display:flex;align-items:center;justify-content:space-between;border-left:6px solid #2563eb;}
        .task-card.pending{border-left-color:#f59e0b;}
        .task-card.completed{border-left-color:#16a34a;}
        .task-card.progress{border-left-color:#0ea5e9;}
        .task-card.overdue{border-left-color:#facc15;}
        .task-card span{color:#64748b;}
        .task-card strong{font-size:32px;}
        .notice{padding:13px 15px;border-radius:12px;margin-bottom:18px;font-weight:800;}
        .success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
        .error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:18px;}
        .form-field label{display:block;font-weight:800;margin-bottom:7px;color:#334155;}
        .form-field input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:12px;background:#fff;}
        .btn{border:0;border-radius:12px;background:#2563eb;color:white;padding:12px 16px;font-weight:900;cursor:pointer;}
        .btn.secondary{background:#e0ecff;color:#1d4ed8;}
        @media(max-width:900px){
            .shell{grid-template-columns:1fr;}
            .hero,.hero-left{align-items:flex-start;flex-direction:column;}
            .content-grid,.detail-grid{grid-template-columns:1fr;}
            .form-grid{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <h2>Bank Compliance</h2>
        <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a class="active" href="profile.php">Profile</a>
            <a href="mytasks.php">My Tasks</a>
            <a href="notifications.php">Notifications</a>
            <a href="drive.php">Drive</a>
            <a class="logout" href="../auth/logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main">
        <?php if ($message !== '') { ?><div class="notice success"><?php echo h($message); ?></div><?php } ?>
        <?php if ($error !== '') { ?><div class="notice error"><?php echo h($error); ?></div><?php } ?>

        <section class="hero">
            <div class="hero-left">
                <img src="<?php echo h(user_profile_photo_src($user['profile_photo'] ?? 'default.png')); ?>" alt="">
                <div>
                    <h1><?php echo $user['first_name'].' '.$user['last_name']; ?></h1>
                    <p><?php echo h($user['employee_id'] ?: 'Employee'); ?></p>
                </div>
            </div>
            <?php echo account_status_badge($user['account_status'] ?? 'Active'); ?>
        </section>

        <div class="content-grid">
            <section class="panel">
                <h2>Profile Details</h2>
                <div class="detail-grid">
                    <div><span>Email</span><strong><?php echo h($user['email']); ?></strong></div>
                    <div><span>Mobile</span><strong><?php echo h($user['mobile'] ?: 'Not added'); ?></strong></div>
                    <div><span>Branch</span><strong><?php echo h($user['branch_location'] ?: 'Not assigned'); ?></strong></div>
                    <div><span>Role</span><strong><?php echo h(ucfirst($user['role'])); ?></strong></div>
                    <div><span>Last Login</span><strong><?php echo h(format_last_login($user['last_login'] ?? null)); ?></strong></div>
                    <div><span>Joined</span><strong><?php echo h(format_last_login($user['created_at'] ?? null)); ?></strong></div>
                </div>

                <form method="POST" class="form-grid">
                    <div class="form-field">
                        <label>Mobile Number</label>
                        <input type="text" name="mobile" value="<?php echo h($user['mobile']); ?>" maxlength="15">
                    </div>
                    <div class="form-field">
                        <label>Branch Location</label>
                        <input type="text" name="branch_location" value="<?php echo h($user['branch_location']); ?>" maxlength="100">
                    </div>
                    <div>
                        <button type="submit" name="update_profile" class="btn">Update Profile</button>
                    </div>
                </form>

                <form method="POST" enctype="multipart/form-data" class="form-grid">
                    <div class="form-field">
                        <label>Profile Photo</label>
                        <input type="file" name="profile_photo" accept=".png,.jpg,.jpeg,.webp" required>
                    </div>
                    <div>
                        <button type="submit" name="upload_photo" class="btn secondary">Upload Photo</button>
                    </div>
                </form>

                <form method="POST" class="form-grid">
                    <div class="form-field">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="6" required>
                    </div>
                    <div>
                        <button type="submit" name="change_password" class="btn secondary">Change Password</button>
                    </div>
                </form>
            </section>

            <section class="tasks">
                <div class="task-card"><span>Total Tasks</span><strong><?php echo $totalTasks; ?></strong></div>
                <div class="task-card pending"><span>Pending</span><strong><?php echo $pendingTasks; ?></strong></div>
                <div class="task-card progress"><span>In Progress</span><strong><?php echo $progressTasks; ?></strong></div>
                <div class="task-card completed"><span>Completed</span><strong><?php echo $completedTasks; ?></strong></div>
                <div class="task-card overdue"><span>Overdue</span><strong><?php echo $overdueTasks; ?></strong></div>
                <div class="task-card"><span>Compliance Score</span><strong><?php echo $completionPercent; ?>%</strong></div>
            </section>
        </div>
    </main>
</div>

<?php include("../includes/team_chat.php"); ?>
</body>
</html>
