<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

$message = '';
$adminId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $body = mysqli_real_escape_string($conn, trim($_POST['message'] ?? ''));
    if ($body !== '') {
        mysqli_query($conn, "INSERT INTO notifications(message) VALUES('$body')");
        log_activity($conn, $adminId, 'Sent system notification');
        $message = 'Notification published.';
    }
}

$notifications = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notification Center</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}body{background:#eef2f7;padding:30px;color:#0f172a}.top{display:flex;justify-content:space-between;margin-bottom:20px}.btn,button{background:#2563eb;color:white;text-decoration:none;border:0;border-radius:8px;padding:10px 14px;font-weight:800;cursor:pointer}.grid{display:grid;grid-template-columns:380px 1fr;gap:20px}.panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08)}textarea{width:100%;min-height:120px;padding:12px;border:1px solid #cbd5e1;border-radius:8px;margin:12px 0}.notice{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:14px;font-weight:800}.item{padding:13px;border-bottom:1px solid #e2e8f0}.item:last-child{border-bottom:0}small{color:#64748b}@media(max-width:900px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="top"><h1>Notification Center</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
    <?php if ($message !== '') { ?><div class="notice"><?php echo h($message); ?></div><?php } ?>
    <div class="grid">
        <section class="panel">
            <h2>System Announcement</h2>
            <form method="POST">
                <textarea name="message" placeholder="Compliance reminder, overdue alert, approval request, or employee communication" required></textarea>
                <button type="submit" name="send_notification">Publish</button>
            </form>
            <p style="margin-top:14px;color:#64748b;">Email integration can use the existing PHPMailer setup; SMS can be attached later through a gateway API.</p>
        </section>
        <section class="panel">
            <h2>Recent Notifications</h2>
            <?php while ($note = mysqli_fetch_assoc($notifications)) { ?>
                <div class="item"><strong><?php echo h($note['message']); ?></strong><br><small><?php echo h(format_last_login($note['created_at'])); ?></small></div>
            <?php } ?>
        </section>
    </div>
</body>
</html>
