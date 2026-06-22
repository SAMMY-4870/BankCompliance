<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_employee_login();
ensure_employee_portal_schema($conn);

$employee_id = (int)$_SESSION['user_id'];
$status = trim($_GET['status'] ?? '');
$allowed = ['Pending', 'In Progress', 'Completed', 'Overdue'];
$where = "WHERE et.employee_id='$employee_id'";

if ($status !== '' && in_array($status, $allowed, true)) {
    $safeStatus = mysqli_real_escape_string($conn, $status);
    $where .= " AND et.status='$safeStatus'";
}

$tasks = mysqli_query($conn, "
    SELECT et.*, t.task_name, t.frequency, COALESCE(et.end_date, t.end_date) AS due_date
    FROM employee_tasks et
    JOIN tasks t ON et.task_id=t.id
    $where
    ORDER BY et.completed_date IS NULL, et.completed_date DESC, et.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task History</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#f1f5f9;color:#0f172a;}
        .wrap{display:grid;grid-template-columns:250px 1fr;min-height:100vh;}
        .side{background:#0f172a;padding:22px;color:white;}
        .side h2{margin-bottom:24px;}
        .side a{display:block;color:white;text-decoration:none;background:#1e293b;padding:13px;border-radius:10px;margin-bottom:10px;font-weight:700;}
        .side a.active,.side a:hover{background:#2563eb;}
        .main{padding:30px;}
        .head{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:20px;}
        select,button{padding:10px 12px;border-radius:8px;border:1px solid #cbd5e1;}
        button{background:#2563eb;color:white;border:0;font-weight:800;}
        .panel{background:white;border-radius:16px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08);overflow:auto;}
        table{width:100%;border-collapse:collapse;}
        th{background:#0f172a;color:white;text-align:left;padding:13px;}
        td{padding:13px;border-bottom:1px solid #e2e8f0;}
        .status-pill{display:inline-flex;align-items:center;gap:8px;font-weight:800;white-space:nowrap;}
        .status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
        .pending-dot{background:#f59e0b;}.progress-dot{background:#0ea5e9;}.completed-dot{background:#16a34a;}.overdue-dot{background:#facc15;}
    </style>
</head>
<body>
<div class="wrap">
    <aside class="side">
        <h2>Bank Compliance</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="mytasks.php">My Tasks</a>
        <a class="active" href="task_history.php">Task History</a>
        <a href="calendar.php">Calendar</a>
        <a href="documents.php">Evidence Center</a>
        <a href="../auth/logout.php">Logout</a>
    </aside>
    <main class="main">
        <div class="head">
            <h1>Task History</h1>
            <form method="GET">
                <select name="status">
                    <option value="">All Status</option>
                    <?php foreach ($allowed as $item) { ?>
                        <option value="<?php echo h($item); ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo h($item); ?></option>
                    <?php } ?>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>
        <section class="panel">
            <table>
                <tr><th>Task</th><th>Frequency</th><th>Assigned</th><th>Started</th><th>Due</th><th>Completed</th><th>Status</th></tr>
                <?php while ($task = mysqli_fetch_assoc($tasks)) { ?>
                    <tr>
                        <td><a href="task_details.php?id=<?php echo (int)$task['id']; ?>"><?php echo h($task['task_name']); ?></a></td>
                        <td><?php echo h($task['frequency']); ?></td>
                        <td><?php echo h($task['assigned_date'] ?: '-'); ?></td>
                        <td><?php echo h($task['start_date'] ?: '-'); ?></td>
                        <td><?php echo h($task['due_date'] ?: '-'); ?></td>
                        <td><?php echo h($task['completed_date'] ?: '-'); ?></td>
                        <td><?php echo status_badge($task['status']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    </main>
</div>
<?php include("../includes/team_chat.php"); ?>
</body>
</html>
