<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_employee_login();
ensure_employee_portal_schema($conn);
generate_due_task_reminders($conn, (int)$_SESSION['user_id']);

$employee_id = (int)$_SESSION['user_id'];
$tasks = mysqli_query($conn, "
    SELECT et.id, et.status, t.task_name, COALESCE(et.end_date, t.end_date) AS due_date
    FROM employee_tasks et
    JOIN tasks t ON et.task_id=t.id
    WHERE et.employee_id='$employee_id'
    AND COALESCE(et.end_date, t.end_date) IS NOT NULL
    ORDER BY due_date ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compliance Calendar</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#eef2f7;color:#0f172a;padding:28px;}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;}
        a.btn{background:#2563eb;color:white;text-decoration:none;border-radius:10px;padding:11px 14px;font-weight:800;}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
        .day{background:white;border-radius:8px;padding:18px;border-left:6px solid #2563eb;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        .day.overdue{border-left-color:#facc15;}.day.completed{border-left-color:#16a34a;}
        .day small{display:block;color:#64748b;margin-bottom:8px;font-weight:800;}
        .day h3{font-size:18px;margin-bottom:10px;}
        .status-pill{display:inline-flex;align-items:center;gap:8px;font-weight:800;}.status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
        .pending-dot{background:#f59e0b;}.progress-dot{background:#0ea5e9;}.completed-dot{background:#16a34a;}.overdue-dot{background:#facc15;}
    </style>
</head>
<body>
    <div class="top">
        <div><h1>Calendar View</h1><p>Upcoming and historical task deadlines.</p></div>
        <a class="btn" href="dashboard.php">Dashboard</a>
    </div>
    <section class="grid">
        <?php while ($task = mysqli_fetch_assoc($tasks)) {
            $class = strtolower(str_replace(' ', '-', $task['status']));
        ?>
            <article class="day <?php echo h($class); ?>">
                <small><?php echo h(date('d M Y', strtotime($task['due_date']))); ?></small>
                <h3><?php echo h($task['task_name']); ?></h3>
                <?php echo status_badge($task['status']); ?>
                <p style="margin-top:12px;"><a href="task_details.php?id=<?php echo (int)$task['id']; ?>">Open task</a></p>
            </article>
        <?php } ?>
    </section>
    <?php include("../includes/team_chat.php"); ?>
</body>
</html>
