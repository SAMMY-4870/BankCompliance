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

ensure_employee_portal_schema($conn);
generate_due_task_reminders($conn, $user_id);

$userResult = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($userResult);

$result = mysqli_query(
    $conn,
    "SELECT id FROM employee_tasks WHERE employee_id='$user_id'"
);

if(!$result){
    die("SQL Error: " . mysqli_error($conn));
}

$result = mysqli_query(
    $conn,
    "SELECT id FROM employee_tasks WHERE employee_id='$user_id'"
);

if(!$result){
    die("SQL Error: " . mysqli_error($conn));
}

$totalTasks = mysqli_num_rows($result);
$q1 = mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='Completed'");

if(!$q1){
    die("Completed Query Error: " . mysqli_error($conn));
}

$completedTasks = mysqli_num_rows($q1);
$pendingTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='Pending'"));
$progressTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='In Progress'"));
$overdueTasks = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_tasks WHERE employee_id='$user_id' AND status='Overdue'"));
$dueQuery = mysqli_query($conn, "
    SELECT et.id
    FROM employee_tasks et
    JOIN tasks t ON et.task_id = t.id
    WHERE et.employee_id='$user_id'
    AND et.status != 'Completed'
    AND COALESCE(et.end_date, t.end_date)
        BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");

if(!$dueQuery){
    die("Due Query Error: " . mysqli_error($conn));
}

$dueThisWeek = mysqli_num_rows($dueQuery);


$completionPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
$badge = 'Getting Started';

if ($completionPercent === 100 && $totalTasks > 0) {
    $badge = 'Compliance Champion';
} elseif ($completionPercent >= 75) {
    $badge = 'Audit Ready';
} elseif ($completionPercent >= 50) {
    $badge = 'Steady Performer';
}

$upcomingTasks = mysqli_query($conn, "
    SELECT et.id, et.status, t.task_name, COALESCE(et.start_date, t.start_date) AS start_date,
           COALESCE(et.end_date, t.end_date) AS due_date
    FROM employee_tasks et
    JOIN tasks t ON et.task_id = t.id
    WHERE et.employee_id='$user_id'
    AND et.status != 'Completed'
    ORDER BY due_date IS NULL, due_date ASC
    LIMIT 6
");

$historyTasks = mysqli_query($conn, "
    SELECT et.id, et.status, et.completed_date, t.task_name, COALESCE(et.end_date, t.end_date) AS due_date
    FROM employee_tasks et
    JOIN tasks t ON et.task_id = t.id
    WHERE et.employee_id='$user_id'
    ORDER BY et.id DESC
    LIMIT 6
");

$notifications = mysqli_query($conn, "SELECT * FROM notifications ORDER BY id DESC LIMIT 4");

$leaderboard = mysqli_query($conn, "
    SELECT
        u.id,
        u.first_name,
        u.last_name,
        u.branch_location,
        CONCAT(u.first_name,' ',u.last_name) AS employee_name,

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
            ELSE (
                SUM(
                    CASE
                    WHEN et.status='Completed'
                    THEN 1
                    ELSE 0
                    END
                ) / COUNT(et.id)
            ) * 100
            END
        ) AS score

    FROM users u

    LEFT JOIN employee_tasks et
    ON et.employee_id = u.id

    WHERE u.role='employee'

    GROUP BY u.id

    ORDER BY score DESC, completed_tasks DESC, total_tasks DESC

    LIMIT 5
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Compliance Portal</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#eef2f7;color:#0f172a;}
        .shell{display:grid;grid-template-columns:270px 1fr;min-height:100vh;}
        .sidebar{background:#0f172a;color:white;padding:24px;position:sticky;top:0;height:100vh;overflow:auto;}
        .brand{font-size:24px;font-weight:800;margin-bottom:22px;}
        .profile-mini{padding:18px;border-radius:18px;background:rgba(255,255,255,.06);margin-bottom:20px;text-align:center;}
        .profile-mini img{width:86px;height:86px;border-radius:50%;object-fit:cover;border:4px solid #dbeafe;margin-bottom:12px;}
        .profile-mini h2{font-size:20px;margin-bottom:4px;}
        .profile-mini p{color:#cbd5e1;font-size:13px;word-break:break-word;}
        .nav a{display:block;padding:13px 14px;margin-bottom:10px;border-radius:12px;color:white;text-decoration:none;background:rgba(255,255,255,.07);font-weight:700;}
        .nav a:hover,.nav a.active{background:#2563eb;}
        .logout{background:#dc2626!important;margin-top:18px!important;text-align:center;}
        .main{padding:32px;}
        .hero{background:linear-gradient(135deg,#0f172a,#1e293b);color:white;border-radius:24px;padding:30px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:24px;}
        .hero h1{font-size:36px;margin-bottom:8px;}
        .hero p{color:#cbd5e1;}
        .hero-meta{display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end;}
        .hero-meta div{min-width:150px;padding:12px 14px;border-radius:14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);}
        .hero-meta span,.label{display:block;color:#64748b;font-size:12px;text-transform:uppercase;margin-bottom:6px;}
        .hero-meta span{color:#94a3b8;}
        .hero-meta strong{display:block;color:white;}
        .stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px;margin-bottom:22px;}
        .stat{background:white;border-radius:16px;padding:18px;box-shadow:0 4px 18px rgba(15,23,42,.08);border-left:6px solid #2563eb;}
        .stat.completed{border-left-color:#16a34a;}
        .stat.pending{border-left-color:#f59e0b;}
        .stat.progress{border-left-color:#0ea5e9;}
        .stat.overdue{border-left-color:#facc15;}
        .stat.week{border-left-color:#7c3aed;}
        .stat strong{font-size:32px;display:block;}
        .grid{display:grid;grid-template-columns:1.2fr .8fr;gap:20px;margin-bottom:20px;}
        .panel{background:white;border-radius:18px;padding:22px;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        .panel h2{font-size:22px;margin-bottom:16px;}
        .score-wrap{display:flex;align-items:center;gap:22px;}
        .score-ring{width:150px;height:150px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#16a34a <?php echo $completionPercent; ?>%, #e2e8f0 0);}
        .score-ring div{width:112px;height:112px;border-radius:50%;background:white;display:grid;place-items:center;font-size:30px;font-weight:900;}
        .metric-list{display:grid;gap:10px;flex:1;}
        .metric-list div,.task-row,.leader-row,.note-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;}
        .metric-list strong,.task-row strong,.leader-row strong{word-break:break-word;}
        .status-pill{display:inline-flex;align-items:center;gap:8px;font-weight:800;white-space:nowrap;}
        .status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
        .pending-dot{background:#f59e0b;}
        .progress-dot{background:#0ea5e9;}
        .completed-dot{background:#16a34a;}
        .overdue-dot{background:#facc15;}
        .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 14px;border-radius:10px;background:#2563eb;color:white;text-decoration:none;font-weight:800;}
        .btn.secondary{background:#e0ecff;color:#1d4ed8;}
        .list{display:grid;gap:10px;}
        .task-row a{color:#2563eb;font-weight:800;text-decoration:none;}
        .leader-rank{width:34px;height:34px;border-radius:50%;background:#e0ecff;color:#1d4ed8;display:grid;place-items:center;font-weight:900;flex:0 0 34px;}
        .leader-name{flex:1;}
        .leader-name span,.task-row span,.note-row span{display:block;color:#64748b;font-size:13px;margin-top:3px;}
        .badge{display:inline-flex;padding:8px 12px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:900;}
        @media(max-width:1200px){.stats{grid-template-columns:repeat(3,1fr);}.grid{grid-template-columns:1fr;}}
        @media(max-width:800px){.shell{grid-template-columns:1fr;}.sidebar{position:relative;height:auto;}.stats{grid-template-columns:1fr;}.hero{align-items:flex-start;flex-direction:column;}.hero-meta{justify-content:flex-start;}.score-wrap{align-items:flex-start;flex-direction:column;}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">Bank Compliance</div>
        <div class="profile-mini">
            <img src="<?php echo h(user_profile_photo_src($user['profile_photo'] ?? 'default.png')); ?>" alt="">
            <h2><?php echo h($user['first_name'].' '.$user['last_name']); ?></h2>
            <p><?php echo h($user['employee_id'] ?: 'Employee'); ?></p>
            <p><?php echo h($user['branch_location'] ?: 'Branch not assigned'); ?></p>
        </div>
        <nav class="nav">
            <a class="active" href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="mytasks.php">My Tasks</a>
            <a href="task_history.php">Task History</a>
            <a href="calendar.php">Calendar</a>
            <a href="documents.php">Evidence Center</a>
            <a href="leaderboard.php">Leaderboard</a>
            <a href="leave_requests.php">Leave Requests</a>
            <a href="activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
            <a href="drive.php">Drive</a>
            <a class="logout" href="../auth/logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main">
        <section class="hero">
            <div>
                <h1>Employee Compliance Portal</h1>
                <p>Monitor assignments, deadlines, evidence and compliance performance from one workspace.</p>
            </div>
            <div class="hero-meta">
                <div><span>Last Login</span><strong><?php echo h(format_last_login($user['last_login'] ?? null)); ?></strong></div>
                <div><span>Achievement</span><strong><?php echo h($badge); ?></strong></div>
                <div><span>Branch</span><strong><?php echo h($user['branch_location'] ?: 'Not assigned'); ?></strong></div>
            </div>
        </section>

        <section class="stats">
            <div class="stat"><span class="label">Total Tasks</span><strong><?php echo $totalTasks; ?></strong></div>
            <div class="stat completed"><span class="label">Completed</span><strong><?php echo $completedTasks; ?></strong></div>
            <div class="stat pending"><span class="label">Pending</span><strong><?php echo $pendingTasks; ?></strong></div>
            <div class="stat progress"><span class="label">In Progress</span><strong><?php echo $progressTasks; ?></strong></div>
            <div class="stat overdue"><span class="label">Overdue</span><strong><?php echo $overdueTasks; ?></strong></div>
            <div class="stat week"><span class="label">Due This Week</span><strong><?php echo $dueThisWeek; ?></strong></div>
        </section>

        <section class="grid">
            <div class="panel">
                <h2>Compliance Score</h2>
                <div class="score-wrap">
                    <div class="score-ring"><div><?php echo $completionPercent; ?>%</div></div>
                    <div class="metric-list">
                        <div><span>Completed vs assigned</span><strong><?php echo $completedTasks; ?> / <?php echo $totalTasks; ?></strong></div>
                        <div><span>Current badge</span><strong class="badge"><?php echo h($badge); ?></strong></div>
                        <div><span>Certificate</span><strong><?php echo ($completionPercent === 100 && $totalTasks > 0) ? 'Eligible' : 'Complete all tasks to unlock'; ?></strong></div>
                    </div>
                </div>
                <div class="actions">
                    <a class="btn" href="mytasks.php">Open Tasks</a>
                    <a class="btn secondary" href="documents.php">Evidence Center</a>
                    <?php if ($completionPercent === 100 && $totalTasks > 0) { ?>
                        <a class="btn secondary" href="certificate.php">Generate Certificate</a>
                    <?php } ?>
                </div>
            </div>

            <div class="panel">
                <h2>Upcoming Deadlines</h2>
                <div class="list">
                    <?php while($task = mysqli_fetch_assoc($upcomingTasks)){ ?>
                        <div class="task-row">
                            <div>
                                <strong><?php echo h($task['task_name']); ?></strong>
                                <span>Start: <?php echo h($task['start_date'] ?: 'Not set'); ?> | Due: <?php echo h($task['due_date'] ?: 'Not set'); ?></span>
                            </div>
                            <a href="task_details.php?id=<?php echo (int)$task['id']; ?>">Open</a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="panel">
                <h2>Task History</h2>
                <div class="list">
                    <?php while($task = mysqli_fetch_assoc($historyTasks)){ ?>
                        <div class="task-row">
                            <div>
                                <strong><?php echo h($task['task_name']); ?></strong>
                                <span>Due: <?php echo h($task['due_date'] ?: 'Not set'); ?> | Completed: <?php echo h($task['completed_date'] ?: '-'); ?></span>
                            </div>
                            <?php echo status_badge($task['status']); ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="actions"><a class="btn secondary" href="task_history.php">View Full History</a></div>
            </div>

            <div class="panel">
                <h2>Performance Leaderboard</h2>
                <div class="list">
                    <?php $rank = 1; while($row = mysqli_fetch_assoc($leaderboard)){ ?>
                        <div class="leader-row">
                            <div class="leader-rank"><?php echo $rank; ?></div>
                            <div class="leader-name">
                                <strong><?php echo h($row['first_name'].' '.$row['last_name']); ?></strong>
                                <span><?php echo h($row['branch_location'] ?: 'No branch'); ?> | <?php echo (int)$row['completed_tasks']; ?> completed</span>
                            </div>
                            <strong><?php echo (int)$row['score']; ?>%</strong>
                        </div>
                    <?php $rank++; } ?>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Recent Notifications and Reminders</h2>
            <div class="list">
                <?php if ($dueThisWeek > 0) { ?>
                    <div class="note-row"><strong><?php echo $dueThisWeek; ?> task(s) are due within 7 days.</strong><span>Please review your calendar and upload evidence before the due date.</span></div>
                <?php } ?>
                <?php while($note = mysqli_fetch_assoc($notifications)){ ?>
                    <div class="note-row">
                        <strong><?php echo h($note['message']); ?></strong>
                        <span><?php echo h(format_last_login($note['created_at'])); ?></span>
                    </div>
                <?php } ?>
            </div>
        </section>
    </main>
</div>

<?php include("../includes/team_chat.php"); ?>
</body>
</html>
