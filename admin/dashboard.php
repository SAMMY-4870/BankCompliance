<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);
escalate_overdue_tasks($conn);

$adminId = (int)$_SESSION['user_id'];
$adminUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$adminId'"));

$totalEmployees = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='employee'"));
$activeEmployees = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE role='employee' AND COALESCE(account_status,'Active')='Active'"));
$totalTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks"));
$completedTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE status='Completed'"));
$pendingTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE status='Pending'"));
$progressTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE status='In Progress'"));
$overdueTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE status='Overdue'"));
$complianceRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$branchRows = mysqli_query($conn, "
    SELECT COALESCE(NULLIF(u.branch_location,''),'Unassigned') AS branch,
           COUNT(et.id) AS total_tasks,
           SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) AS completed_tasks,
           SUM(CASE WHEN et.status='Overdue' THEN 1 ELSE 0 END) AS overdue_tasks,
           ROUND(CASE WHEN COUNT(et.id)=0 THEN 0 ELSE SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) / COUNT(et.id) * 100 END) AS score
    FROM users u
    LEFT JOIN employee_tasks et ON et.employee_id=u.id
    WHERE u.role='employee'
    GROUP BY branch
    ORDER BY score DESC, total_tasks DESC
");

$monthlyRows = mysqli_query($conn, "
    SELECT DATE_FORMAT(COALESCE(completed_date, CURDATE()), '%b %Y') AS month_label, COUNT(id) AS completed_count
    FROM employee_tasks
    WHERE status='Completed'
    GROUP BY YEAR(COALESCE(completed_date, CURDATE())), MONTH(COALESCE(completed_date, CURDATE()))
    ORDER BY YEAR(COALESCE(completed_date, CURDATE())) DESC, MONTH(COALESCE(completed_date, CURDATE())) DESC
    LIMIT 6
");
$productivityRows = mysqli_query($conn, "
    SELECT
    u.first_name,
    u.last_name,
    u.employee_id,

    COUNT(et.id) AS total_tasks,

    SUM(
        CASE
        WHEN et.status='Completed'
        THEN 1
        ELSE 0
        END
    ) AS completed_tasks,

    ROUND(
        CASE
        WHEN COUNT(et.id)=0
        THEN 0
        ELSE SUM(
            CASE
            WHEN et.status='Completed'
            THEN 1
            ELSE 0
            END
        ) / COUNT(et.id) * 100
        END
    ) AS score

    FROM users u

    LEFT JOIN employee_tasks et
    ON et.employee_id=u.id

    WHERE u.role='employee'

    GROUP BY u.id

    ORDER BY score DESC, completed_tasks DESC

    LIMIT 8
");

// $productivityRows = mysqli_query($conn, "
//     SELECT CONCAT(u.first_name,' ',u.last_name), u.employee_id,
//            COUNT(et.id) AS total_tasks,
//            SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) AS completed_tasks,
//            ROUND(CASE WHEN COUNT(et.id)=0 THEN 0 ELSE SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) / COUNT(et.id) * 100 END) AS score
//     FROM users u
//     LEFT JOIN employee_tasks et ON et.employee_id=u.id
//     WHERE u.role='employee'
//     GROUP BY u.id, CONCAT(u.first_name,' ',u.last_name), u.employee_id
//     ORDER BY score DESC, completed_tasks DESC
//     LIMIT 8
// ");

$highRiskRows = mysqli_query($conn, "
    SELECT et.id, et.status, et.priority, et.escalation_level, CONCAT(u.first_name,' ',u.last_name), t.task_name, COALESCE(et.end_date, t.end_date) AS due_date
    FROM employee_tasks et
    JOIN tasks t ON et.task_id=t.id
    JOIN users u ON et.employee_id=u.id
    WHERE et.status='Overdue' OR et.priority='High' OR et.escalation_level > 0
    ORDER BY et.escalation_level DESC, due_date ASC
    LIMIT 8
");

$notifications = mysqli_query($conn, "SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Compliance Command Center</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#eef2f7;color:#0f172a;}
        .shell{display:grid;grid-template-columns:270px 1fr;min-height:100vh;}
        .sidebar{background:#0f172a;color:white;padding:24px;position:sticky;top:0;height:100vh;overflow:auto;}
        .sidebar h2{font-size:23px;margin-bottom:18px;}
        .admin-card{padding:16px;border-radius:8px;background:rgba(255,255,255,.07);margin-bottom:18px;}
        .admin-card span{display:block;color:#cbd5e1;font-size:13px;margin-top:4px;}
        .nav a{display:block;color:white;text-decoration:none;background:rgba(255,255,255,.07);padding:12px 13px;border-radius:8px;margin-bottom:9px;font-weight:700;}
        .nav a.active,.nav a:hover{background:#2563eb;}
        .nav a.logout{background:#dc2626;margin-top:14px;text-align:center;}
        .main{padding:30px;}
        .hero{display:flex;align-items:center;justify-content:space-between;gap:20px;background:#0f172a;color:white;border-radius:8px;padding:26px;margin-bottom:22px;}
        .hero h1{font-size:34px;margin-bottom:6px;}.hero p{color:#cbd5e1;}
        .hero-meta{display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end;}
        .hero-meta div{background:rgba(255,255,255,.08);padding:12px;border-radius:8px;min-width:140px;}
        .hero-meta span,.label{display:block;font-size:12px;text-transform:uppercase;color:#64748b;margin-bottom:6px;}
        .hero-meta span{color:#94a3b8;}.hero-meta strong{color:white;}
        .kpis{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:14px;margin-bottom:22px;}
        .kpi{background:white;border-radius:8px;padding:16px;border-left:5px solid #2563eb;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        .kpi strong{display:block;font-size:28px;}.kpi.completed{border-color:#16a34a;}.kpi.pending{border-color:#f59e0b;}.kpi.overdue{border-color:#dc2626;}.kpi.rate{border-color:#0ea5e9;}
        .grid{display:grid;grid-template-columns:1.15fr .85fr;gap:20px;margin-bottom:20px;}
        .panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        .panel h2{font-size:21px;margin-bottom:15px;}
        .bar-row{display:grid;grid-template-columns:170px 1fr 56px;gap:12px;align-items:center;margin-bottom:12px;}
        .track{height:12px;border-radius:999px;background:#e2e8f0;overflow:hidden;}.fill{height:100%;border-radius:999px;background:#2563eb;}
        .fill.good{background:#16a34a;}.fill.warn{background:#f59e0b;}.fill.bad{background:#dc2626;}
        .list{display:grid;gap:10px;}.item{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;}
        .item small{display:block;color:#64748b;margin-top:3px;}
        .status-pill{display:inline-flex;align-items:center;gap:8px;font-weight:800;white-space:nowrap;}.status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
        .pending-dot{background:#f59e0b;}.progress-dot{background:#0ea5e9;}.completed-dot{background:#16a34a;}.overdue-dot{background:#facc15;}
        @media(max-width:1250px){.kpis{grid-template-columns:repeat(4,1fr);}.grid{grid-template-columns:1fr;}}
        @media(max-width:800px){.shell{grid-template-columns:1fr;}.sidebar{position:relative;height:auto;}.kpis{grid-template-columns:1fr;}.hero{align-items:flex-start;flex-direction:column;}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <h2>Bank Compliance</h2>
        <div class="admin-card">
            <strong><?php echo h($_SESSION['name']); ?></strong>
            <span><?php echo h(admin_role_label($_SESSION['role'])); ?></span>
        </div>
        <nav class="nav">
            <a class="active" href="dashboard.php">Command Center</a>
            <a href="employees.php">Employees</a>
            <a href="tasks.php">Tasks</a>
            <a href="monitoring.php">Monitoring Center</a>
            <a href="documents.php">Documents</a>
            <a href="branches.php">Branches</a>
            <a href="audit_trail.php">Audit Trail</a>
            <a href="leave_management.php" class="menu-card">
                <i class="fas fa-calendar-check"></i>
                <span>Leave Requests</span>
            </a>
            <a href="notifications.php">Notifications</a>
            <a href="reports.php">Reports</a>
            <a href="drive.php">Drive</a>

            <a class="logout" href="../auth/logout.php">Logout</a>
        </nav>
    </aside>
    <main class="main">
        <section class="hero">
            <div>
                <h1>Banking Compliance Command Center</h1>
                <p>Track workforce compliance, branch risk, task progress, audits, documents and alerts from one control surface.</p>
            </div>
            <div class="hero-meta">
                <div><span>Admin Status</span><strong><?php echo h($adminUser['account_status'] ?? 'Active'); ?></strong></div>
                <div><span>Last Login</span><strong><?php echo h(format_last_login($adminUser['last_login'] ?? null)); ?></strong></div>
                <div><span>Compliance Rate</span><strong><?php echo $complianceRate; ?>%</strong></div>
            </div>
        </section>
        <section class="kpis">
            <div class="kpi"><span class="label">Total Employees</span><strong><?php echo $totalEmployees; ?></strong></div>
            <div class="kpi"><span class="label">Active Employees</span><strong><?php echo $activeEmployees; ?></strong></div>
            <div class="kpi"><span class="label">Total Tasks</span><strong><?php echo $totalTasks; ?></strong></div>
            <div class="kpi completed"><span class="label">Completed</span><strong><?php echo $completedTasks; ?></strong></div>
            <div class="kpi pending"><span class="label">Pending</span><strong><?php echo $pendingTasks; ?></strong></div>
            <div class="kpi pending"><span class="label">In Progress</span><strong><?php echo $progressTasks; ?></strong></div>
            <div class="kpi overdue"><span class="label">Overdue</span><strong><?php echo $overdueTasks; ?></strong></div>
            <div class="kpi rate"><span class="label">Compliance Rate</span><strong><?php echo $complianceRate; ?>%</strong></div>
        </section>
        <section class="grid">
            <div class="panel">
                <h2>Branch-wise Performance</h2>
                <?php while ($branch = mysqli_fetch_assoc($branchRows)) {
                    $score = (int)$branch['score'];
                    $tone = $score >= 80 ? 'good' : ($score >= 50 ? 'warn' : 'bad');
                ?>
                    <div class="bar-row">
                        <strong><?php echo h($branch['branch']); ?></strong>
                        <div class="track"><div class="fill <?php echo $tone; ?>" style="width:<?php echo $score; ?>%"></div></div>
                        <strong><?php echo $score; ?>%</strong>
                    </div>
                <?php } ?>
            </div>
            <div class="panel">
                <h2>Task Completion Rates</h2>
                <div class="bar-row"><strong>Completed</strong><div class="track"><div class="fill good" style="width:<?php echo $totalTasks ? round($completedTasks / $totalTasks * 100) : 0; ?>%"></div></div><strong><?php echo $completedTasks; ?></strong></div>
                <div class="bar-row"><strong>Pending</strong><div class="track"><div class="fill warn" style="width:<?php echo $totalTasks ? round($pendingTasks / $totalTasks * 100) : 0; ?>%"></div></div><strong><?php echo $pendingTasks; ?></strong></div>
                <div class="bar-row"><strong>Overdue</strong><div class="track"><div class="fill bad" style="width:<?php echo $totalTasks ? round($overdueTasks / $totalTasks * 100) : 0; ?>%"></div></div><strong><?php echo $overdueTasks; ?></strong></div>
            </div>
        </section>
        <section class="grid">
            <div class="panel">
                <h2>Employee Productivity Analysis</h2>
                <div class="list">
                    <?php while ($row = mysqli_fetch_assoc($productivityRows)) { ?>
                        <div class="item"><div><strong><?php echo h($row['first_name'].' '.$row['last_name']); ?></strong><small><?php echo h($row['employee_id']); ?> | <?php echo (int)$row['completed_tasks']; ?> of <?php echo (int)$row['total_tasks']; ?> completed</small></div><strong><?php echo (int)$row['score']; ?>%</strong></div>
                    <?php } ?>
                </div>
            </div>
            <div class="panel">
                <h2>Monthly Compliance Trends</h2>
                <?php while ($month = mysqli_fetch_assoc($monthlyRows)) {
                    $width = min(100, ((int)$month['completed_count']) * 10);
                ?>
                    <div class="bar-row"><strong><?php echo h($month['month_label']); ?></strong><div class="track"><div class="fill good" style="width:<?php echo $width; ?>%"></div></div><strong><?php echo (int)$month['completed_count']; ?></strong></div>
                <?php } ?>
            </div>
        </section>
        <section class="grid">
            <div class="panel">
                <h2>High Risk and Escalated Activities</h2>
                <div class="list">
                    <?php while ($risk = mysqli_fetch_assoc($highRiskRows)) { ?>
                        <div class="item"><div><strong><?php echo h($risk['task_name']); ?></strong><small><?php echo h($risk['name']); ?> | Due: <?php echo h($risk['due_date'] ?: '-'); ?> | Escalation L<?php echo (int)$risk['escalation_level']; ?></small></div><?php echo status_badge($risk['status']); ?></div>
                    <?php } ?>
                </div>
            </div>
            <div class="panel">
                <h2>Recent Notifications</h2>
                <div class="list">
                    <?php while ($note = mysqli_fetch_assoc($notifications)) { ?>
                        <div class="item"><div><strong><?php echo h($note['message']); ?></strong><small><?php echo h(format_last_login($note['created_at'])); ?></small></div></div>
                    <?php } ?>
                </div>
            </div>
        </section>
    </main>
</div>
<?php include("../includes/team_chat.php"); ?>
</body>
</html>
