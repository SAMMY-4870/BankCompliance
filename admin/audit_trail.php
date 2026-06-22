<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

$logs = mysqli_query($conn, "
    SELECT al.*, CONCAT(u.first_name,' ',u.last_name), u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id=u.id
    ORDER BY al.created_at DESC
    LIMIT 300
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Trail</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}body{background:#eef2f7;padding:30px;color:#0f172a}.top{display:flex;justify-content:space-between;margin-bottom:20px}.btn{background:#2563eb;color:white;text-decoration:none;border-radius:8px;padding:10px 14px;font-weight:800}.panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08)}.log{display:grid;grid-template-columns:220px 1fr 220px;gap:16px;padding:13px;border-bottom:1px solid #e2e8f0}.log:last-child{border-bottom:0}small{color:#64748b}
    </style>
</head>
<body>
    <div class="top"><h1>Audit Trail System</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
<section class="panel">
    <?php while ($log = mysqli_fetch_assoc($logs)) { ?>
        <div class="log">
            <div>
                <strong>
                    <?php echo h(($log['first_name'] ?? '').' '.($log['last_name'] ?? '')); ?>
                </strong>
                <br>
                <small>
                    <?php echo h(admin_role_label($log['role'] ?: 'system')); ?>
                </small>
            </div>

            <strong>
                <?php echo h($log['action']); ?>
            </strong>

            <small>
                <?php echo h(format_last_login($log['created_at'])); ?>
            </small>
        </div>
    <?php } ?>
</section>
</body>
</html>
