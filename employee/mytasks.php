<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_employee_login();

/* FETCH ALL TASKS */
$employee_id = (int)$_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowed_statuses = ['Pending', 'In Progress', 'Completed', 'Overdue'];

ensure_employee_portal_schema($conn);

$where = "WHERE et.employee_id = '$employee_id'";

if($search !== ''){
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (t.task_name LIKE '%$safe_search%' OR t.frequency LIKE '%$safe_search%')";
}

if($status !== '' && in_array($status, $allowed_statuses, true)){
    $safe_status = mysqli_real_escape_string($conn, $status);
    $where .= " AND et.status = '$safe_status'";
}

$tasks = mysqli_query($conn, "
SELECT
    et.*,
    t.sr_no,
    t.task_name,
    t.frequency,
    t.start_date,
    t.end_date,
    t.remark,
    t.additional_fields,
    COALESCE(et.start_date, t.start_date) AS timeline_start_date,
    COALESCE(et.end_date, t.end_date) AS timeline_end_date
FROM employee_tasks et
JOIN tasks t ON et.task_id = t.id
$where
ORDER BY et.id DESC
");

?>

<!DOCTYPE html>
<html>

<head>

<title>My Tasks</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body{
    background:#f1f5f9;
}

/* SIDEBAR */

.sidebar{

    width:220px;
    height:100vh;

    background:#17233c;

    position:fixed;

    padding:20px;
}

.sidebar h2{

    color:white;

    margin-bottom:30px;
}

.sidebar a{

    display:block;

    background:#2d3b55;

    color:white;

    text-decoration:none;

    padding:15px;

    border-radius:8px;

    margin-bottom:15px;

    transition:0.3s;
}

.sidebar a:hover{

    background:#3d4d6d;
}

/* MAIN */

.main{

    margin-left:240px;

    padding:30px;
}

.main h1{

    margin-bottom:20px;
}

/* TABLE */

.table-box{

    background:white;

    padding:20px;

    border-radius:20px;

    box-shadow:
    0 2px 10px rgba(0,0,0,0.1);
}

table{

    width:100%;

    border-collapse:collapse;
}

table th{

    background:#0f172a;

    color:white;

    padding:15px;
}

table td{

    text-align:center;

    padding:15px;

    border-bottom:1px solid #ddd;
}

.pending{

    color:orange;

    font-weight:bold;
}

.completed{

    color:green;

    font-weight:bold;
}

.overdue{

    color:#ca8a04;

    font-weight:bold;
}

.status-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-weight:700;
}

.status-dot{
    width:12px;
    height:12px;
    border-radius:50%;
    display:inline-block;
    box-shadow:0 0 0 4px rgba(15,23,42,0.06);
}

.pending-dot{
    background:#f59e0b;
}

.completed-dot{
    background:#16a34a;
}

.overdue-dot{
    background:#facc15;
}

.complete-btn{

    background:green;

    color:white;

    border:none;

    padding:10px 15px;

    border-radius:8px;

    cursor:pointer;
}

.extra-list{
    display:grid;
    gap:8px;
    min-width:220px;
    text-align:left;
}

.extra-list div{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:8px;
    padding:8px 10px;
}

.extra-list strong{
    display:block;
    font-size:12px;
    color:#475569;
    margin-bottom:3px;
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>Employee Panel</h2>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="mytasks.php">
        My Tasks
    </a>

    <a href="notifications.php">
        Notifications
    </a>

    <!-- <a href="#"> -->
        <!-- Reports -->
    <!-- </a> -->

    <a href="../auth/logout.php">
        Logout
    </a>

</div>

<!-- MAIN -->

<div class="main">

<h1>My Compliance Tasks</h1>
<form method="GET" style="margin-bottom:20px;display:flex;gap:10px;">

    <input
        type="text"
        name="search"
        placeholder="Search Task..."
        value="<?php echo h($search); ?>"
        style="padding:10px;width:250px;border-radius:8px;border:1px solid #ccc;">

    <select
        name="status"
        style="padding:10px;border-radius:8px;">

        <option value="">All Status</option>
        <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="In Progress" <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
        <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
        <option value="Overdue" <?php echo $status === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>

    </select>

    <button
        type="submit"
        style="padding:10px 20px;background:#2563eb;color:white;border:none;border-radius:8px;">
        Search
    </button>

</form>

<div class="table-box">

<table>

<tr>

<th>SR NO</th>
<th>Task</th>
<th>Frequency</th>
<th>Start Date</th>
<th>End Date</th>
<th>Remark</th>
<th>Admin Fields</th>
<th>Status</th>
<th>Action</th>

</tr>

<?php

while($task =
mysqli_fetch_assoc($tasks)){

?>
<tr>

    <td><?php echo h($task['sr_no']); ?></td>

    <td><?php echo h($task['task_name']); ?></td>

    <td><?php echo h($task['frequency']); ?></td>

    <td><?php echo h($task['start_date']); ?></td>

    <td><?php echo h($task['end_date']); ?></td>

    <td><?php echo h($task['remark']); ?></td>

    <td>
        <?php
        $extraFields = decode_task_extra_fields($task['additional_fields'] ?? '');
        if (!empty($extraFields)) {
        ?>
            <div class="extra-list">
            <?php foreach ($extraFields as $field) { ?>
                <div>
                    <strong><?php echo h($field['label']); ?></strong>
                    <span><?php echo nl2br(h($field['value'])); ?></span>
                </div>
            <?php } ?>
            </div>
        <?php } else { ?>
            -
        <?php } ?>
    </td>

    <td>
        <?php echo status_badge($task['status']); ?>
    </td>

    <td>
        <a href="task_details.php?id=<?php echo (int)$task['id']; ?>"
           style="background:#2563eb;color:#fff;padding:6px 12px;border-radius:5px;text-decoration:none;">
            Open
        </a>
    </td>

</tr>

<?php } ?>

</table>

</div>

</div>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
