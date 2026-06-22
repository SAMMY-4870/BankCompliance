<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);
escalate_overdue_tasks($conn);

$status = trim($_GET['status'] ?? '');
$allowed = ['Pending', 'In Progress', 'Completed', 'Overdue'];
$where = "WHERE 1=1";

if ($status !== '' && in_array($status, $allowed, true)) {
    $safeStatus = mysqli_real_escape_string($conn, $status);
    $where .= " AND et.status='$safeStatus'";
}

$rows = mysqli_query($conn, "
    SELECT
    et.*,
    CONCAT(u.first_name,' ',u.last_name) AS employee_name,

    u.first_name,
    u.last_name,
    u.employee_id,
    u.branch_location,

    t.task_name,
    t.frequency,

    COALESCE(et.end_date, t.end_date) AS due_date

    FROM employee_tasks et

    JOIN users u
    ON et.employee_id = u.id

    JOIN tasks t
    ON et.task_id = t.id

    $where

    ORDER BY
    FIELD(et.status,'Overdue','Pending','In Progress','Completed'),
    due_date ASC

");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compliance Monitoring Center</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}body{background:#eef2f7;color:#0f172a;padding:30px;}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:14px}.btn,button{background:#2563eb;color:white;text-decoration:none;border:0;border-radius:8px;padding:10px 14px;font-weight:800;cursor:pointer}
        .panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08);overflow:auto}form{display:flex;gap:10px;margin-bottom:16px}select{padding:10px;border:1px solid #cbd5e1;border-radius:8px}
        table{width:100%;border-collapse:collapse;min-width:1050px}th{background:#0f172a;color:white;text-align:left;padding:12px}td{padding:12px;border-bottom:1px solid #e2e8f0}.risk{font-weight:900}.high{color:#dc2626}.medium{color:#b45309}.low{color:#166534}
        .status-pill{display:inline-flex;align-items:center;gap:8px;font-weight:800}.status-dot{width:10px;height:10px;border-radius:50%;display:inline-block}.pending-dot{background:#f59e0b}.progress-dot{background:#0ea5e9}.completed-dot{background:#16a34a}.overdue-dot{background:#facc15}
    </style>
</head>
<body>
    <div class="top"><div><h1>Compliance Monitoring Center</h1><p>Real-time task status, risk and branch compliance tracking.</p></div><a class="btn" href="dashboard.php">Dashboard</a></div>
    <section class="panel">
        <form method="GET">
            <select name="status">
                <option value="">All Status</option>
                <?php foreach ($allowed as $item) { ?><option value="<?php echo h($item); ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo h($item); ?></option><?php } ?>
            </select>
            <button type="submit">Filter</button>
        </form>
        <table>
            <tr><th>Task</th><th>Employee</th><th>Branch</th><th>Frequency</th><th>Priority</th><th>Due Date</th><th>Status</th><th>Risk</th><th>Escalation</th></tr>
            <?php while ($row = mysqli_fetch_assoc($rows)) {
                $risk = 'Low';
                if ($row['status'] === 'Overdue' || (int)$row['escalation_level'] > 0) {
                    $risk = 'High';
                } elseif ($row['priority'] === 'High' || $row['status'] === 'Pending') {
                    $risk = 'Medium';
                }
            ?>
                <tr>
                    <td><?php echo h($row['task_name']); ?></td>
                    <td><?php echo h($row['first_name'].' '.$row['last_name']); ?> <small><?php echo h($row['employee_id']); ?></small></td>
                    <td><?php echo h($row['branch_location'] ?: 'Unassigned'); ?></td>
                    <td><?php echo h($row['frequency']); ?></td>
                    <td><?php echo h($row['priority'] ?: 'Medium'); ?></td>
                    <td><?php echo h($row['due_date'] ?: '-'); ?></td>
                    <td><?php echo status_badge($row['status']); ?></td>
                    <td class="risk <?php echo strtolower($risk); ?>"><?php echo h($risk); ?></td>
                    <td>L<?php echo (int)$row['escalation_level']; ?></td>
                </tr>
            <?php } ?>
        </table>
    </section>
</body>
</html>
