<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_employee_login();
ensure_employee_portal_schema($conn);

$employee_id = (int)$_SESSION['user_id'];
$logs = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id='$employee_id' ORDER BY created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#eef2f7;padding:30px;color:#0f172a;}
        .top{display:flex;justify-content:space-between;margin-bottom:22px;}.btn{background:#2563eb;color:white;text-decoration:none;border-radius:10px;padding:11px 14px;font-weight:800;}
        .panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08);max-width:900px;}
        .log{display:flex;justify-content:space-between;gap:18px;padding:14px;border-bottom:1px solid #e2e8f0;}
        .log:last-child{border-bottom:0;}small{color:#64748b;}
    </style>
</head>
<body>
    <div class="top"><h1>Activity Logs</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
    <section class="panel">
        <?php while ($log = mysqli_fetch_assoc($logs)) { ?>
            <div class="log"><strong><?php echo h($log['action']); ?></strong><small><?php echo h(format_last_login($log['created_at'])); ?></small></div>
        <?php } ?>
    </section>
</body>
</html>
