<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

/* FETCH REPORT DATA */
$search = $_GET['search'] ?? '';
$frequency = $_GET['frequency'] ?? '';
$status = $_GET['status'] ?? '';
$allowed_frequencies = ['Weekly', 'Monthly', 'Quarterly', 'Yearly'];
$allowed_statuses = ['Pending', 'In Progress', 'Completed', 'Overdue'];

$where = " WHERE 1=1 ";

if($search != ''){
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where .= " AND (
        CONCAT(u.first_name,' ',u.last_name) LIKE '%$safe_search%'
        OR
        u.employee_id LIKE '%$safe_search%'
    )";
}

if($frequency != '' && in_array($frequency, $allowed_frequencies, true)){
    $safe_frequency = mysqli_real_escape_string($conn, $frequency);
    $where .= " AND t.frequency='$safe_frequency'";
}

if($status != '' && in_array($status, $allowed_statuses, true)){
    $safe_status = mysqli_real_escape_string($conn, $status);
    $where .= " AND et.status='$safe_status'";
}
$reports = mysqli_query($conn, "

SELECT
CONCAT(u.first_name,' ',u.last_name) AS employee_name,
u.first_name,
u.last_name,
u.employee_id,

t.task_name,
t.frequency,

et.status,
et.priority,

COALESCE(et.start_date,t.start_date) as start_date,
COALESCE(et.end_date,t.end_date) as end_date,

et.completed_date,
et.proof_file_name,
et.proof_file_path,
u.branch_location

FROM employee_tasks et

LEFT JOIN tasks t
ON et.task_id = t.id

LEFT JOIN users u
ON et.employee_id = u.id

$where

ORDER BY et.id DESC

");

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    log_activity($conn, (int)$_SESSION['user_id'], 'Exported compliance report CSV');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bank_compliance_report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Bank Compliance Management System']);
    fputcsv($out, ['Generated', date('d M Y, h:i A')]);
    fputcsv($out, ['Executive Summary', 'Compliance Rate reports completed tasks against assigned tasks for selected filters.']);
    fputcsv($out, []);
    fputcsv($out, ['Employee', 'Employee Code', 'Branch', 'Task', 'Frequency', 'Priority', 'Start Date', 'End Date', 'Status', 'Completed Date', 'Proof']);
    mysqli_data_seek($reports, 0);
    while ($row = mysqli_fetch_assoc($reports)) {
        fputcsv($out, [
            $row['first_name'].' '.$row['last_name'] ?? '-',
            $row['employee_id'] ?? '-',
            $row['branch_location'] ?? '-',
            $row['task_name'] ?? '-',
            $row['frequency'] ?? '-',
            $row['priority'] ?? '-',
            $row['start_date'] ?? '-',
            $row['end_date'] ?? '-',
            $row['status'] ?? '-',
            $row['completed_date'] ?? '-',
            $row['proof_file_path'] ?? '-'
        ]);
    }
    fclose($out);
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Reports</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial;
}

body{
background:#f1f5f9;
display:flex;
}

.sidebar{
width:250px;
height:100vh;
background:#17233c;
padding:20px;
position:fixed;
}

.sidebar h2{
color:white;
margin-bottom:30px;
}

.sidebar a{
display:block;
padding:14px;
margin-bottom:12px;
background:#2d3b55;
color:white;
text-decoration:none;
border-radius:10px;
}

.sidebar a:hover{
background:#3d4d6d;
}

.main{
margin-left:270px;
padding:30px;
width:100%;
}

.heading{
font-size:32px;
font-weight:bold;
margin-bottom:20px;
}

.table-box{
background:white;
padding:20px;
border-radius:15px;
box-shadow:0 4px 15px rgba(0,0,0,.08);
overflow-x:auto;
overflow-y:hidden;
}

table{
width:100%;
min-width:1400px;
border-collapse:collapse;
}

table th{
background:#0f172a;
color:white;
padding:15px;
}

table td{
padding:12px;
text-align:center;
border-bottom:1px solid #ddd;
}

.pending{
background:#fef3c7;
color:#b45309;
padding:6px 12px;
border-radius:20px;
font-weight:bold;
}

.completed{
background:#dcfce7;
color:#15803d;
padding:6px 12px;
border-radius:20px;
font-weight:bold;
}

.overdue{
background:#fee2e2;
color:#dc2626;
padding:6px 12px;
border-radius:20px;
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

.pending-dot{background:#f59e0b;}
.completed-dot{background:#16a34a;}
.overdue-dot{background:#facc15;}

.export-btn{
background:#16a34a;
color:white;
padding:12px 20px;
border:none;
border-radius:8px;
cursor:pointer;
margin-bottom:20px;
}
@media print{

.sidebar,
form,
.export-btn{
display:none !important;
.report-header{
box-shadow:none;
border:1px solid #ddd;
.team-chat,
.chat-widget,
.chat-container,
#team-chat,
#chat-box{
display:none !important;
}
}
}

.main{
margin:0 !important;
padding:0 !important;
width:100% !important;
}

}
.cards{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin-bottom:25px;
}

.card{
padding:25px;
border-radius:15px;
color:white;
text-align:center;
font-weight:bold;
}

.blue{background:#2563eb;}
.green{background:#16a34a;}
.orange{background:#f59e0b;}
.red{background:#dc2626;}

.card h3{
font-size:32px;
margin-bottom:10px;
}
.report-header{
    background:#fff;
    padding:20px;
    border-radius:15px;
    margin-bottom:25px;
    display:flex;
    align-items:center;
    gap:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.bank-logo{
    width:120px;
    height:auto;
}

.report-header h1{
    color:#17233c;
    margin:0;
    font-size:30px;
}

.report-header h3{
    color:#2563eb;
    margin-top:5px;
}

.report-header p{
    color:#64748b;
    margin-top:8px;
}

</style>

</head>

<body>

<div class="sidebar">

<h2>Bank Compliance</h2>

<a href="dashboard.php">Dashboard</a>

<a href="employees.php">Employees</a>

<a href="tasks.php">Tasks</a>

<a href="analytics.php">Analytics</a>

<a href="reports.php">Reports</a>

<a href="../auth/logout.php">Logout</a>

</div>

<div class="main">

<div class="report-header">

    <img src="../assets/images/image.png" class="bank-logo">

    <div>
        <h1>Bhingar Urban Co-Operative Bank Ltd.</h1>
        <h3>Bank Compliance Management System</h3>
        <p>Employee Compliance Report</p>
    </div>

</div>
<?php

$total = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM employee_tasks
"));

$completed = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM employee_tasks
WHERE status='Completed'
"));

$pending = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM employee_tasks
WHERE status='Pending'
"));

$overdue = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM employee_tasks
WHERE status='Overdue'
"));

?>

<div class="cards">

<div class="card blue">
<h3><?php echo $total; ?></h3>
<p>Total Tasks</p>
</div>

<div class="card green">
<h3><?php echo $completed; ?></h3>
<p>Completed</p>
</div>

<div class="card orange">
<h3><?php echo $pending; ?></h3>
<p>Pending</p>
</div>

<div class="card red">
<h3><?php echo $overdue; ?></h3>
<p>Overdue</p>
</div>

</div>
<form method="GET" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">

<input
type="text"
name="search"
placeholder="Employee Name / Code"
value="<?php echo h($search); ?>">

<select name="frequency">

<option value="">All Frequency</option>
<option value="Weekly" <?php echo $frequency === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
<option value="Monthly" <?php echo $frequency === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
<option value="Quarterly" <?php echo $frequency === 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
<option value="Yearly" <?php echo $frequency === 'Yearly' ? 'selected' : ''; ?>>Yearly</option>

</select>

<select name="status">

<option value="">All Status</option>
<option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
<option value="In Progress" <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
<option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
<option value="Overdue" <?php echo $status === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>

</select>

<button type="submit" class="export-btn">
Generate Report
</button>
<a class="export-btn" style="text-decoration:none;display:inline-block;" href="?<?php echo h(http_build_query(array_merge($_GET, ['export' => 'csv']))); ?>">Export CSV</a>

</form>
<button
class="export-btn"
onclick="window.print()">

Print Report

</button>

<div class="table-box">

<table>

<tr>

<th>Employee</th>
<th>Employee Code</th>
<th>Task</th>
<th>Frequency</th>
<th>Priority</th>
<th>Start Date</th>
<th>End Date</th>
<th>Status</th>
<th>Completed On</th>
<th>Report</th>
<th>Proof</th>

</tr>

<?php

while($row = mysqli_fetch_assoc($reports)){

?>

<tr>

<td>
    <?php echo h($row['first_name'].' '.$row['last_name'] ?? '-'); ?>
</td>

<td>
<?php echo h($row['employee_id'] ?? '-'); ?>
</td>

<td>
<?php echo h($row['task_name'] ?? '-'); ?>
</td>

<td>
<?php echo h($row['frequency'] ?? '-'); ?>
</td>

<td>
<?php echo h($row['priority']); ?>
</td>

<td>
<?php echo h($row['start_date']); ?>
</td>

<td>
<?php echo h($row['end_date']); ?>
</td>

<td>

<?php echo status_badge($row['status']); ?>

</td>
<td>

<?php

echo $row['completed_date']
? date(
"d-m-Y",
strtotime($row['completed_date'])
)
: "-";

?>

</td>

<td>

<?php

echo !empty($row['proof_file_name'])
? h(substr($row['proof_file_name'],0,30))." ..."
: "-";

?>

</td>

<td>

<?php

if(!empty($row['proof_file_path'])){

?>

<a
href="../<?php echo h($row['proof_file_path']); ?>"
target="_blank">

View

</a>

<?php

}else{

echo "-";

}

?>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

<?php if(!isset($_GET['print'])){ ?>
<?php include("../includes/team_chat.php"); ?>
<?php } ?>

</body>

</html>
