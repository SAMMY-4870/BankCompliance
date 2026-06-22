<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_employee_login();
ensure_employee_portal_schema($conn);

$rows = mysqli_query($conn, "
    SELECT CONCAT(u.first_name,' ',u.last_name), u.employee_id, u.branch_location,
           COUNT(et.id) AS total_tasks,
           SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) AS completed_tasks,
           ROUND(CASE WHEN COUNT(et.id)=0 THEN 0 ELSE SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) / COUNT(et.id) * 100 END) AS score
    FROM users u
    LEFT JOIN employee_tasks et ON et.employee_id=u.id
    WHERE u.role='employee'
    GROUP BY u.id, CONCAT(u.first_name,' ',u.last_name), u.employee_id, u.branch_location
    ORDER BY score DESC, completed_tasks DESC, total_tasks DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Performance Leaderboard</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#f1f5f9;padding:30px;color:#0f172a;}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;}
        .btn{background:#2563eb;color:white;text-decoration:none;border-radius:10px;padding:11px 14px;font-weight:800;}
        .board{display:grid;gap:12px;max-width:980px;}
        .row{display:grid;grid-template-columns:54px 1fr 120px 120px;gap:14px;align-items:center;background:white;border-radius:8px;padding:16px;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        .rank{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:#e0ecff;color:#1d4ed8;font-weight:900;}
        small{color:#64748b;}
        strong.score{font-size:24px;color:#166534;}
    </style>
</head>
<body>
    <div class="top"><h1>Performance Leaderboard</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
    <section class="board">
        <?php $rank = 1; while ($row = mysqli_fetch_assoc($rows)) { ?>
            <div class="row">
                <div class="rank"><?php echo $rank; ?></div>
                <div><strong><?php echo h($row['first_name'].' '.$row['last_name']); ?></strong><br><small><?php echo h($row['employee_id']); ?> | <?php echo h($row['branch_location'] ?: 'No branch'); ?></small></div>
                <div><small>Completed</small><br><strong><?php echo (int)$row['completed_tasks']; ?>/<?php echo (int)$row['total_tasks']; ?></strong></div>
                <strong class="score"><?php echo (int)$row['score']; ?>%</strong>
            </div>
        <?php $rank++; } ?>
    </section>
</body>
</html>
