<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_employee_login();
ensure_employee_portal_schema($conn);

$employee_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$employee_id'"));
$total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$employee_id'"));
$completed = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$employee_id' AND status='Completed'"));
$eligible = $total > 0 && $total === $completed;

if ($eligible) {
    log_activity($conn, $employee_id, 'Generated compliance certificate');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compliance Certificate</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:Georgia,'Times New Roman',serif;}
        body{background:#eef2f7;padding:30px;color:#111827;}
        .actions{max-width:980px;margin:0 auto 16px;display:flex;justify-content:space-between;}
        .btn{background:#2563eb;color:white;text-decoration:none;border:0;border-radius:8px;padding:10px 14px;font:800 14px 'Segoe UI',Arial;cursor:pointer;}
        .cert{max-width:980px;margin:auto;background:white;border:10px solid #0f172a;padding:54px;text-align:center;min-height:620px;}
        .cert h1{font-size:44px;margin:28px 0;color:#0f172a;}
        .cert h2{font-size:34px;margin:18px 0;color:#166534;}
        .cert p{font-size:20px;line-height:1.7;margin:12px 0;}
        .seal{margin:34px auto 20px;width:120px;height:120px;border-radius:50%;border:6px solid #2563eb;display:grid;place-items:center;color:#2563eb;font-weight:900;font-family:'Segoe UI',Arial;}
        .blocked{max-width:760px;margin:80px auto;background:white;border-radius:8px;padding:28px;text-align:center;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        @media print{.actions{display:none;}body{background:white;padding:0;}.cert{border-width:8px;}}
    </style>
</head>
<body>
    <div class="actions"><a class="btn" href="dashboard.php">Dashboard</a><?php if ($eligible) { ?><button class="btn" onclick="window.print()">Print Certificate</button><?php } ?></div>
    <?php if ($eligible) { ?>
        <section class="cert">
            <p>Bank Compliance Management System</p>
            <h1>Certificate of Compliance</h1>
            <p>This certifies that</p>
            <h2><?php echo h($user['name']); ?></h2>
            <p>Employee Code: <?php echo h($user['employee_id']); ?></p>
            <p>has achieved 100% completion of assigned compliance tasks.</p>
            <div class="seal">100%</div>
            <p>Issued on <?php echo date('d M Y'); ?></p>
        </section>
    <?php } else { ?>
        <section class="blocked">
            <h1>Certificate Locked</h1>
            <p>Complete all assigned tasks to generate your compliance certificate.</p>
            <p><?php echo $completed; ?> of <?php echo $total; ?> tasks completed.</p>
        </section>
    <?php } ?>
</body>
</html>
