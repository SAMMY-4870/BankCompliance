<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

$staffId = (int)($_GET['id'] ?? 0);
$staffResult = mysqli_query($conn, "SELECT * FROM users WHERE id='$staffId'");
$staff = mysqli_fetch_assoc($staffResult);

if(!$staff){
    header("Location: employees.php");
    exit();
}

$taskSummary = [
    'total' => 0,
    'pending' => 0,
    'completed' => 0,
    'overdue' => 0
];

if($staff['role'] === 'employee'){
    $taskSummary['total'] = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$staffId'"));
    $taskSummary['pending'] = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$staffId' AND status='Pending'"));
    $taskSummary['completed'] = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$staffId' AND status='Completed'"));
    $taskSummary['overdue'] = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$staffId' AND status='Overdue'"));
}

$activityLogs = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id='$staffId' ORDER BY created_at DESC LIMIT 12");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Profile</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=2">
</head>
<body>

<div class="sidebar">
    <h2>Bank Compliance</h2>
    <div class="admin-profile-sidebar">
        <h3><?php
echo h(
($_SESSION['first_name'] ?? 'Admin')
.' '.
($_SESSION['last_name'] ?? '')
);
?>
</h3>
        <p><?php echo h(admin_role_label($_SESSION['role'])); ?></p>
    </div>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="employees.php">Employees</a></li>
        <li><a href="tasks.php">Tasks</a></li>
        <li><a href="analytics.php">Analytics</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="drive.php">Drive</a></li>
        <li><a href="../auth/logout.php">Logout</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <p class="eyebrow">Staff Profile</p>
            <h1><?php echo h($staff['first_name'].' '.$staff['last_name']); ?></h1>
        </div>
        <a class="primary-action" href="employees.php">Back to Directory</a>
    </div>

    <div class="profile-layout">
        <section class="profile-panel">
            <img class="profile-photo-large" src="<?php echo h(user_profile_photo_src($staff['profile_photo'] ?? 'default.png')); ?>" alt="">
            <h2><?php echo h($staff['first_name'].' '.$staff['last_name']); ?></h2>
            <p><?php echo h(admin_role_label($staff['role'])); ?></p>
            <?php echo account_status_badge($staff['account_status'] ?? 'Active'); ?>
        </section>

        <section class="detail-panel">
            <h2>Contact and Branch</h2>
            <div class="detail-grid">
                <div>
                    <span>Email</span>
                    <strong><?php echo h($staff['email']); ?></strong>
                </div>
                <div>
                    <span>Mobile</span>
                    <strong><?php echo h($staff['mobile'] ?: 'Not added'); ?></strong>
                </div>
                <div>
                    <span>Branch</span>
                    <strong><?php echo h($staff['branch_location'] ?: 'Not assigned'); ?></strong>
                </div>
                <div>
                    <span>Last Login</span>
                    <strong><?php echo h(format_last_login($staff['last_login'] ?? null)); ?></strong>
                </div>
                <div>
                    <span>Employee Code</span>
                    <strong><?php echo h($staff['employee_id'] ?: 'Admin account'); ?></strong>
                </div>
                <div>
                    <span>Joined</span>
                    <strong><?php echo h(format_last_login($staff['created_at'] ?? null)); ?></strong>
                </div>
            </div>
        </section>
    </div>

    <?php if($staff['role'] === 'employee'){ ?>
    <div class="cards compact-cards">
        <div class="card employee"><h3>Total Tasks</h3><p><?php echo $taskSummary['total']; ?></p></div>
        <div class="card pending"><h3>Pending</h3><p><?php echo $taskSummary['pending']; ?></p></div>
        <div class="card task"><h3>Completed</h3><p><?php echo $taskSummary['completed']; ?></p></div>
        <div class="card overdue"><h3>Overdue</h3><p><?php echo $taskSummary['overdue']; ?></p></div>
    </div>
    <?php } ?>

    <div class="table-card">
        <h2>Employee Activity History</h2>
        <table class="staff-table">
            <thead><tr><th>Activity</th><th>Date</th></tr></thead>
            <tbody>
            <?php while($log = mysqli_fetch_assoc($activityLogs)){ ?>
                <tr>
                    <td><?php echo h($log['action']); ?></td>
                    <td><?php echo h(format_last_login($log['created_at'])); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
