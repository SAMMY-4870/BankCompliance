<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

$message = '';
$adminId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['branch_name'] ?? ''));
    $code = mysqli_real_escape_string($conn, trim($_POST['branch_code'] ?? ''));
    $location = mysqli_real_escape_string($conn, trim($_POST['location'] ?? ''));
    $manager = mysqli_real_escape_string($conn, trim($_POST['manager_name'] ?? ''));

    if ($name !== '') {
        mysqli_query($conn, "INSERT INTO branches (branch_name, branch_code, location, manager_name) VALUES ('$name', '$code', '$location', '$manager')");
        log_activity($conn, $adminId, 'Added branch ' . $name);
        $message = 'Branch added.';
    }
}

$branches = mysqli_query($conn, "
    SELECT b.*,
           COUNT(u.id) AS employee_count,
           COUNT(et.id) AS total_tasks,
           SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) AS completed_tasks,
           ROUND(CASE WHEN COUNT(et.id)=0 THEN 0 ELSE SUM(CASE WHEN et.status='Completed' THEN 1 ELSE 0 END) / COUNT(et.id) * 100 END) AS score
    FROM branches b
    LEFT JOIN users u ON u.branch_location=b.branch_name AND u.role='employee'
    LEFT JOIN employee_tasks et ON et.employee_id=u.id
    GROUP BY b.id
    ORDER BY b.branch_name ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Branch Management</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}body{background:#eef2f7;padding:30px;color:#0f172a}.top{display:flex;justify-content:space-between;margin-bottom:20px}.btn,button{background:#2563eb;color:white;text-decoration:none;border:0;border-radius:8px;padding:10px 14px;font-weight:800;cursor:pointer}.grid{display:grid;grid-template-columns:340px 1fr;gap:20px}.panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08)}label{display:block;font-weight:800;margin:12px 0 7px}input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px}.notice{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:14px;font-weight:800}table{width:100%;border-collapse:collapse}th{background:#0f172a;color:white;text-align:left;padding:12px}td{padding:12px;border-bottom:1px solid #e2e8f0}@media(max-width:900px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="top"><h1>Branch Management</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
    <?php if ($message !== '') { ?><div class="notice"><?php echo h($message); ?></div><?php } ?>
    <div class="grid">
        <section class="panel">
            <h2>Add Branch</h2>
            <form method="POST">
                <label>Branch Name</label><input name="branch_name" required>
                <label>Branch Code</label><input name="branch_code">
                <label>Location</label><input name="location">
                <label>Manager</label><input name="manager_name">
                <button type="submit" name="add_branch" style="margin-top:12px;">Save Branch</button>
            </form>
        </section>
        <section class="panel">
            <h2>Branch Performance</h2>
            <table>
                <tr><th>Branch</th><th>Code</th><th>Manager</th><th>Employees</th><th>Completed</th><th>Score</th></tr>
                <?php while ($branch = mysqli_fetch_assoc($branches)) { ?>
                    <tr>
                        <td><?php echo h($branch['branch_name']); ?></td>
                        <td><?php echo h($branch['branch_code']); ?></td>
                        <td><?php echo h($branch['manager_name']); ?></td>
                        <td><?php echo (int)$branch['employee_count']; ?></td>
                        <td><?php echo (int)$branch['completed_tasks']; ?>/<?php echo (int)$branch['total_tasks']; ?></td>
                        <td><strong><?php echo (int)$branch['score']; ?>%</strong></td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    </div>
</body>
</html>
