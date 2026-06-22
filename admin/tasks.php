<?php

/*session_start();

include("../config/database.php");
include("../includes/security.php");

if($_SESSION['role'] != 'admin'){

    header("Location: ../auth/login.php");
}*/



























// new changes //

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);


/* DELETE TASK */

if(isset($_GET['delete'])){

    $delete_id = (int)$_GET['delete'];

    mysqli_query(

        $conn,

        "DELETE FROM employee_tasks
        WHERE task_id='$delete_id'"
    );

    mysqli_query(

        $conn,

        "DELETE FROM tasks
        WHERE id='$delete_id'"
    );

    header("Location: tasks.php");
}

/* PRESET COMPLIANCE TASKS */

$compliance_tasks = [

"Q2 - Customer Awareness",
"Q3 - Customer Awareness",

"Q2 - End Point Protection Review",
"Q3 - End Point Protection Review",

"First DR Drill",
"Second DR Drill",

"Backup Restoration Q1 - Backup process",
"Backup Restoration Q2 - Backup process",
"Backup Restoration Q3 - Backup process",
"Backup Restoration Q4 - Backup process",

"Q2 - USER ID Review",
"Q3 - USER ID Review",

"Q2 - Firewall Rule Review",
"Q3 - Firewall Rule Review",

"Q2 - Hardening",
"Q3 - Hardening",

"Vendor SLA Review",

"JUNE - Patch Management End Computers",
"JULY - Patch Management End Computers",
"AUG - Patch Management End Computers",
"SEP - Patch Management End Computers",
"OCT - Patch Management End Computers",
"NOV - Patch Management End Computers",
"DEC - Patch Management End Computers",

"JUNE - Patch Management Critical system",
"JULY - Patch Management Critical system",
"AUG - Patch Management Critical system",
"SEP - Patch Management Critical system",
"OCT - Patch Management Critical system",
"NOV - Patch Management Critical system",
"DEC - Patch Management Critical system",

"JUNE - Change Management",
"JULY - Change Management",
"AUG - Change Management",
"SEP - Change Management",
"OCT - Change Management",
"NOV - Change Management",
"DEC - Change Management",

"JUNE - Anti-Phishing Anti-Rogue Report Review",
"JULY - Anti-Phishing Anti-Rogue Report Review",
"AUG - Anti-Phishing Anti-Rogue Report Review",
"SEP - Anti-Phishing Anti-Rogue Report Review",
"OCT - Anti-Phishing Anti-Rogue Report Review",
"NOV - Anti-Phishing Anti-Rogue Report Review",
"DEC - Anti-Phishing Anti-Rogue Report Review",

"Q1 - IT Security Committee Meeting",
"Q2 - IT Security Committee Meeting",
"Q3 - IT Security Committee Meeting",
"Q4 - IT Security Committee Meeting",

"Q2 - SOC Review",
"Q3 - SOC Review",

"VAPT COMPLIANCE",
"VAPT compliance completed",
"VAPT retest completed",
"VAPT Compliance certificate received",

"IT Risk Management",
"Risk Assessment Completed",
"Risk Mitigation Completed",

"Network Security",

"Architecture Review Completed",
"Architecture Restructuring",

"Policies-Information and Cyber Security",
"Policy Reviewed",
"Policy Updated",
"Policy Approved",
"Policy Released",

"Policy Training Given to Employees",

"Policy Acceptance obtained from Employees"

];

/* ADD TASK */

ensure_admin_portal_schema($conn);

if(isset($_POST['add_task'])){

    $sr_no = (int)$_POST['sr_no'];

    $task_name = mysqli_real_escape_string($conn, $_POST['task_name']);

    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);

    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);

    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority'] ?? 'Medium');
    $assign_to = $_POST['assign_to'] === 'all' ? 'all' : (int)$_POST['assign_to'];
    $created_by = (int)$_SESSION['user_id'];
    $extra_fields = [];
    $extra_labels = $_POST['extra_label'] ?? [];
    $extra_values = $_POST['extra_value'] ?? [];

    if (is_array($extra_labels) && is_array($extra_values)) {
        foreach ($extra_labels as $index => $label) {
            $label = trim((string)$label);
            $value = trim((string)($extra_values[$index] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            $extra_fields[] = [
                'label' => $label !== '' ? $label : 'Additional Field',
                'value' => $value
            ];
        }
    }

    $additional_fields = mysqli_real_escape_string($conn, json_encode($extra_fields));

    $query = "

    INSERT INTO tasks

    (

    sr_no,
    task_name,
    description,
    frequency,
    start_date,
    end_date,
    remark,
    additional_fields,
    created_by

    )

    VALUES

    (

    '$sr_no',
    '$task_name',
    '$description',
    '$frequency',
    '$start_date',
    '$end_date',
    '$remark',
    '$additional_fields',
    '$created_by'

    )

    ";

    if(!mysqli_query($conn, $query)){
    die("Task Insert Error: " . mysqli_error($conn));
}
    $task_id = mysqli_insert_id($conn);
  

if($assign_to == "all"){

$all_emp = mysqli_query(
$conn,
"SELECT * FROM users WHERE role='employee'"
);

while($emp = mysqli_fetch_assoc($all_emp)){

mysqli_query(

$conn,

"INSERT INTO employee_tasks
(
task_id,
employee_id,
status,
priority,
assigned_by
)

VALUES
(
'$task_id',
'{$emp['id']}',
'Pending',
'$priority',
'$created_by'
)"

);

}

}else{

mysqli_query(

$conn,

"INSERT INTO employee_tasks
(
task_id,
employee_id,
status,
priority,
assigned_by
)

VALUES
(
'$task_id',
'$assign_to',
'Pending',
'$priority',
'$created_by'
)"

);

}

    /* NOTIFICATION */

    $message = mysqli_real_escape_string($conn, "New Compliance Task Added : " . $_POST['task_name']);

    mysqli_query(

        $conn,

        "INSERT INTO notifications(message)

        VALUES('$message')"
    );

 echo "

    <script>

     alert('Task Added Successfully');

     window.location='tasks.php';

     </script>
    ";
header("Location: tasks.php?success=1");
exit();
}

/*header("Location: tasks.php?success=1");
exit();*/

/* FETCH TASKS */
/* FETCH TASKS */

$filters = [
    'filter_id' => trim($_GET['filter_id'] ?? ''),
    'subject' => trim($_GET['subject'] ?? ''),
    'project' => trim($_GET['project'] ?? ''),
    'allocated_to' => trim($_GET['allocated_to'] ?? ''),
    'status_filter' => trim($_GET['status_filter'] ?? ''),
    'priority_filter' => trim($_GET['priority_filter'] ?? ''),
    'pending_from' => trim($_GET['pending_from'] ?? ''),
    'completed_on' => trim($_GET['completed_on'] ?? ''),
    'task_area' => trim($_GET['task_area'] ?? ''),
    'expected_start_date' => trim($_GET['expected_start_date'] ?? ''),
    'expected_end_date' => trim($_GET['expected_end_date'] ?? '')
];

ensure_admin_portal_schema($conn);

$whereParts = ["1=1"];

if ($filters['filter_id'] !== '') {
    $safeId = mysqli_real_escape_string($conn, $filters['filter_id']);
    $whereParts[] = "(t.sr_no LIKE '%$safeId%' OR t.id LIKE '%$safeId%')";
}

if ($filters['subject'] !== '') {
    $safeSubject = mysqli_real_escape_string($conn, $filters['subject']);
    $whereParts[] = "t.task_name LIKE '%$safeSubject%'";
}

if ($filters['project'] !== '') {
    $safeProject = mysqli_real_escape_string($conn, $filters['project']);
    $whereParts[] = "t.frequency LIKE '%$safeProject%'";
}

if ($filters['allocated_to'] !== '') {
    $safeAllocated = mysqli_real_escape_string($conn, $filters['allocated_to']);
    $whereParts[] = "(CONCAT(u.first_name,' ',u.last_name) LIKE '%$safeAllocated%' OR u.employee_id LIKE '%$safeAllocated%')";
}

if ($filters['status_filter'] !== '') {
    $safeStatus = mysqli_real_escape_string($conn, $filters['status_filter']);
    $whereParts[] = "et.status = '$safeStatus'";
}

if ($filters['priority_filter'] !== '') {
    $safePriority = mysqli_real_escape_string($conn, $filters['priority_filter']);
    $whereParts[] = "et.priority = '$safePriority'";
}

if ($filters['pending_from'] !== '') {
    $safePendingFrom = mysqli_real_escape_string($conn, $filters['pending_from']);
    $whereParts[] = "t.start_date >= '$safePendingFrom'";
}

if ($filters['completed_on'] !== '') {
    $safeCompletedOn = mysqli_real_escape_string($conn, $filters['completed_on']);
    $whereParts[] = "et.completed_date = '$safeCompletedOn'";
}

if ($filters['task_area'] !== '') {
    $safeTaskArea = mysqli_real_escape_string($conn, $filters['task_area']);
    $whereParts[] = "(t.description LIKE '%$safeTaskArea%' OR t.remark LIKE '%$safeTaskArea%')";
}

if ($filters['expected_start_date'] !== '') {
    $safeStartDate = mysqli_real_escape_string($conn, $filters['expected_start_date']);
    $whereParts[] = "t.start_date = '$safeStartDate'";
}

if ($filters['expected_end_date'] !== '') {
    $safeEndDate = mysqli_real_escape_string($conn, $filters['expected_end_date']);
    $whereParts[] = "t.end_date = '$safeEndDate'";
}

$whereSql = implode(' AND ', $whereParts);

$tasks = mysqli_query($conn, "

SELECT
t.*,
t.id AS task_id,

CONCAT(u.first_name,' ',u.last_name) AS name,

u.employee_id,

et.status,
et.priority,
et.completed_date,
et.proof_file_name,
et.proof_file_path

FROM employee_tasks et

JOIN tasks t
ON et.task_id = t.id

JOIN users u
ON et.employee_id = u.id

WHERE $whereSql

ORDER BY t.sr_no ASC

");

if(!$tasks){
    die("Tasks Query Error: ".mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Compliance Tasks</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}

body{
    background:#eef3f8;
    color:#0f172a;
}

.admin-shell{
    min-height:100vh;
    display:flex;
}

.sidebar{
    width:280px;
    min-height:100vh;
    background:#0f172a;
    color:white;
    padding:28px 22px;
    position:fixed;
    left:0;
    top:0;
}

.sidebar h2{
    font-size:30px;
    line-height:1.15;
    margin-bottom:28px;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    min-height:56px;
    padding:15px 18px;
    color:white;
    text-decoration:none;
    border-radius:10px;
    margin-bottom:12px;
    background:#334155;
    font-size:17px;
    font-weight:700;
}

.sidebar a:hover,
.sidebar a.active{
    background:#2563eb;
}

.sidebar a.logout{
    margin-top:22px;
    border:1px solid rgba(255,255,255,.75);
}

.nav-icon{
    width:22px;
    height:22px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}

/* MAIN */

.main{
    width:calc(100% - 280px);
    margin-left:280px;
    padding:32px;
}

.page-head{
    display:flex;
    justify-content:space-between;
    gap:20px;
    align-items:flex-start;
    margin-bottom:22px;
}

.page-head h1{
    margin-bottom:8px;
}

.page-head p{
    color:#64748b;
    max-width:680px;
    line-height:1.5;
}

.admin-stat{
    background:white;
    border:1px solid #e2e8f0;
    border-radius:8px;
    padding:16px 18px;
    min-width:180px;
    box-shadow:0 8px 20px rgba(15,23,42,.06);
}

.admin-stat span{
    display:block;
    color:#64748b;
    font-size:13px;
    margin-bottom:6px;
}

.admin-stat strong{
    font-size:30px;
}

/* FORM */

.task-form{
    max-width:980px;
    background:white;
    padding:26px;
    border-radius:8px;
    border:1px solid #e2e8f0;
    box-shadow:0 12px 30px rgba(15,23,42,.08);
    margin-bottom:32px;
}

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group.full{
    grid-column:1 / -1;
}

.task-form label,
.extra-header label{
    font-weight:700;
    margin-bottom:8px;
    color:#1e293b;
}

.task-form input,
.task-form textarea,
.task-form select{
    width:100%;
    padding:14px;
    border:1px solid #d9e2ec;
    border-radius:8px;
    outline:none;
    font-size:15px;
    background:#fff;
}

.task-form textarea{
    min-height:94px;
    resize:vertical;
}

.task-form input:focus,
.task-form textarea:focus,
.task-form select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.12);
}

.extra-field-panel{
    grid-column:1 / -1;
    border:1px dashed #cbd5e1;
    border-radius:8px;
    padding:16px;
    background:#f8fafc;
}

.extra-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
}

.extra-header p{
    color:#64748b;
    font-size:14px;
    margin-top:4px;
}

.add-field-btn,
.remove-field-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:none;
    cursor:pointer;
    font-weight:800;
}

.add-field-btn{
    width:42px;
    height:42px;
    border-radius:8px;
    background:#0f172a;
    color:white;
    font-size:28px;
    line-height:1;
}

.extra-field-row{
    display:grid;
    grid-template-columns:minmax(180px,.7fr) minmax(260px,1.3fr) 42px;
    gap:12px;
    align-items:start;
    margin-top:12px;
}

.remove-field-btn{
    width:42px;
    height:42px;
    border-radius:8px;
    background:#fee2e2;
    color:#991b1b;
    font-size:22px;
}

.empty-extra{
    color:#64748b;
    padding:14px;
    border-radius:8px;
    background:white;
    border:1px solid #e2e8f0;
}

.task-form button{
    background:#2563eb;
    color:white;
    border:none;
    padding:14px 25px;
    border-radius:8px;
    cursor:pointer;
    font-size:15px;
    transition:0.3s;
    font-weight:700;
}

.task-form button:hover{

    background:#1d4ed8;
}

/* TABLE */

.table-box{
    background:white;
    padding:20px;
    padding:22px;
    border-radius:8px;
    border:1px solid #e2e8f0;
    box-shadow:0 12px 30px rgba(15,23,42,.08);
    overflow:auto;
}

.table-head{
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:center;
    margin-bottom:18px;
}

.filter-panel{
    border:1px solid #e5e7eb;
    border-radius:8px;
    padding:16px;
    margin-bottom:22px;
    background:#fff;
    box-shadow:0 8px 20px rgba(15,23,42,.04);
}

.filter-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(160px,1fr)) auto;
    gap:12px 14px;
    align-items:start;
}

.filter-grid input,
.filter-grid select{
    width:100%;
    min-height:44px;
    border:none;
    border-radius:12px;
    background:#f1f1f2;
    color:#334155;
    padding:10px 12px;
    font-size:16px;
    outline:none;
}

.filter-grid input::placeholder{
    color:#8a8f98;
}

.filter-grid input:focus,
.filter-grid select:focus{
    box-shadow:0 0 0 3px rgba(37,99,235,.14);
}

.filter-actions{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:0;
    grid-column:5;
    grid-row:1;
}

.filter-actions button,
.filter-actions a{
    min-height:44px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:0 15px;
    border:none;
    background:#f1f1f2;
    color:#0f172a;
    text-decoration:none;
    cursor:pointer;
    font-size:16px;
}

.filter-actions button{
    border-radius:12px 0 0 12px;
    border-right:1px solid #d8dce2;
}

.filter-actions a{
    border-radius:0 12px 12px 0;
    font-size:24px;
    line-height:1;
}

.filter-icon{
    width:18px;
    height:18px;
    display:inline-block;
    position:relative;
}

.filter-icon:before,
.filter-icon:after{
    content:"";
    position:absolute;
    left:0;
    height:2px;
    background:#0f172a;
    border-radius:999px;
}

.filter-icon:before{
    top:4px;
    width:18px;
}

.filter-icon:after{
    top:12px;
    width:11px;
}

table{

    width:100%;

    border-collapse:collapse;
}

table th{
    background:#0f172a;
    color:white;
    color:white;
    padding:14px;
    text-align:center;
    font-size:13px;
    white-space:nowrap;
}

table td{
    padding:14px;
    text-align:center;
    border-bottom:1px solid #ddd;
    vertical-align:top;
    font-size:14px;
}

.task-name-cell{
    text-align:left;
    min-width:230px;
}

.status-pending{

    color:orange;

    font-weight:bold;
}

.status-completed{

    color:green;

    font-weight:bold;
}

.status-overdue{

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

.proof-link{
    color:#2563eb;
    font-weight:bold;
    text-decoration:none;
}

.proof-link:hover{
    text-decoration:underline;
}

.delete-btn{
    background:#dc2626;
    color:white;
    padding:8px 15px;
    border-radius:8px;
    text-decoration:none;
    text-decoration:none;
    display:inline-block;
}

.delete-btn:hover{

    opacity:0.8;
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

@media(max-width:900px){
    .sidebar{
        position:relative;
        width:100%;
        min-height:auto;
    }

    .admin-shell{
        display:block;
    }

    .main{
        width:100%;
        margin-left:0;
        padding:22px;
    }

    .form-grid,
    .extra-field-row{
        grid-template-columns:1fr;
    }

    .page-head,
    .table-head{
        flex-direction:column;
        align-items:stretch;
    }

    .filter-grid{
        grid-template-columns:1fr;
    }

    .filter-actions{
        grid-column:auto;
        grid-row:auto;
        justify-content:flex-start;
    }
}

</style>

</head>

<body>

<div class="admin-shell">

<aside class="sidebar">

    <h2>Bank Compliance</h2>

    <a href="dashboard.php"><span class="nav-icon">D</span>Dashboard</a>
    <a href="employees.php"><span class="nav-icon">E</span>Employees</a>
    <a href="tasks.php" class="active"><span class="nav-icon">T</span>Tasks</a>
    <a href="analytics.php"><span class="nav-icon">A</span>Analytics</a>
    <a href="reports.php"><span class="nav-icon">R</span>Reports</a>
    <a href="drive.php"><span class="nav-icon">F</span>Drive</a>
    <a href="../auth/logout.php" class="logout"><span class="nav-icon">L</span>Logout</a>

</aside>

<div class="main">

<div class="page-head">
    <div>
        <h1>Add Compliance Task</h1>
        <p>Create assigned compliance work with dates, owner, remarks, proof tracking, and custom admin text fields that employees can read on their task screen.</p>
    </div>
    <div class="admin-stat">
        <span>Visible Task Rows</span>
        <strong><?php echo mysqli_num_rows($tasks); ?></strong>
    </div>
</div>

<form method="POST" class="task-form">

<div class="form-grid">

<div class="form-group">
    <label>Serial Number</label>
    <input
    type="number"

    name="sr_no"

    placeholder="Serial Number"

    required>
</div>

<div class="form-group">
    <label>Compliance Task</label>
    <select
    name="task_name"

    required>

        <option value="">
            Select Compliance Task
        </option>

        <?php

        foreach($compliance_tasks as $task){

        ?>

        <option value="<?php echo h($task); ?>">

            <?php echo h($task); ?>

        </option>

        <?php } ?>

    </select>
</div>

<div class="form-group full">
    <label>Task Description</label>
    <textarea

    name="description"

    placeholder="Task Description"

    required></textarea>
</div>

<div class="form-group">
    <label>Frequency</label>
    <select
    name="frequency"

    required>

        <option value="">
            Select Frequency
        </option>

        <option value="Weekly">
            Weekly
        </option>

        <option value="Monthly">
            Monthly
        </option>

        <option value="Quarterly">
            Quarterly
        </option>

        <option value="Yearly">
            Yearly
        </option>

    </select>
</div>

<div class="form-group">
    <label>Start Date</label>

    <input
    type="date"

    name="start_date"

    required>
</div>

<div class="form-group">
    <label>Priority</label>
    <select name="priority" required>
        <option value="Low">Low</option>
        <option value="Medium" selected>Medium</option>
        <option value="High">High</option>
    </select>
</div>

<div class="form-group">
    <label>End Date</label>

    <input
    type="date"

    name="end_date"

    required>
</div>

<div class="form-group">
    <label>Assign To</label>

<select name="assign_to" required>

<option value="all">
All Employees
</option>

<?php

$employees = mysqli_query(
$conn,
"SELECT * FROM users WHERE role='employee'"
);

while($emp = mysqli_fetch_assoc($employees)){

?>

<option value="<?php echo (int)$emp['id']; ?>">

<?php
echo h($emp['name'] . " (" .
$emp['employee_id'] . ")");
?>

</option>

<?php } ?>

</select>
</div>

<div class="form-group full">
    <label>Admin Remark</label>
    <textarea

    name="remark"

    placeholder="Admin Remark"></textarea>
</div>

<div class="extra-field-panel">
    <div class="extra-header">
        <div>
            <label>Additional Employee Text Fields</label>
            <p>Use the + icon for any extra instructions, checklist notes, document names, risk comments, or audit references.</p>
        </div>
        <button type="button" class="add-field-btn" id="addExtraField" aria-label="Add more text field">+</button>
    </div>

    <div id="extraFields">
        <div class="empty-extra" id="emptyExtra">No extra fields added yet.</div>
    </div>
</div>

<div class="form-group full">
    <button
    type="submit"

    name="add_task">

        Add Compliance Task

    </button>
</div>

</div>

</form>

<!-- TABLE -->

<div class="table-box">

<div class="table-head">
<h1>All Compliance Tasks</h1>
</div>

<form method="GET" class="filter-panel">
    <div class="filter-grid">
        <input type="text" name="filter_id" placeholder="ID" value="<?php echo h($filters['filter_id']); ?>">
        <input type="text" name="subject" placeholder="Subject" value="<?php echo h($filters['subject']); ?>">
        <input type="text" name="project" placeholder="Project" value="<?php echo h($filters['project']); ?>">
        <input type="text" name="allocated_to" placeholder="Allocated To" value="<?php echo h($filters['allocated_to']); ?>">

        <select name="status_filter">
            <option value="">Status</option>
            <option value="Pending" <?php echo $filters['status_filter'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Completed" <?php echo $filters['status_filter'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="Overdue" <?php echo $filters['status_filter'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
        </select>

        <select name="priority_filter">
            <option value="">Priority</option>
            <option value="Low" <?php echo $filters['priority_filter'] === 'Low' ? 'selected' : ''; ?>>Low</option>
            <option value="Medium" <?php echo $filters['priority_filter'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
            <option value="High" <?php echo $filters['priority_filter'] === 'High' ? 'selected' : ''; ?>>High</option>
        </select>

        <input type="date" name="pending_from" title="Pending from" value="<?php echo h($filters['pending_from']); ?>">
        <input type="date" name="completed_on" title="Completed on" value="<?php echo h($filters['completed_on']); ?>">
        <input type="text" name="task_area" placeholder="Task Area" value="<?php echo h($filters['task_area']); ?>">
        <input type="date" name="expected_start_date" title="Expected Start Date" value="<?php echo h($filters['expected_start_date']); ?>">
        <input type="date" name="expected_end_date" title="Expected End Date" value="<?php echo h($filters['expected_end_date']); ?>">

        <div class="filter-actions">
            <button type="submit"><span class="filter-icon"></span>Filter</button>
            <a href="tasks.php" aria-label="Clear filters">&times;</a>
        </div>
    </div>
</form>

<table>

<tr>

<th>SR NO</th>
<th>Employee</th>
<th>Employee Code</th>
<th>Task</th>
<th>Frequency</th>
<th>Priority</th>
<th>Start</th>
<th>End</th>
<th>Completed</th>
<th>Remark</th>
<th>Extra Fields</th>
<th>Status</th>
<th>Proof</th>
<th>Action</th>

</tr>

<?php

while($task =
mysqli_fetch_assoc($tasks)){

?>

<tr>

<td>
    <?php echo h($task['sr_no']); ?>
</td>
<td>
<?php echo h($task['name']); ?>
</td>

<td>
<?php echo h($task['employee_id']); ?>
</td>

<td>
    <?php echo h($task['task_name']); ?>
</td>

<td>
    <?php echo h($task['frequency']); ?>
</td>
<td>
<?php echo h($task['priority']); ?>
</td>

<td>
    <?php echo h($task['start_date']); ?>
</td>

<td>
    <?php echo h($task['end_date']); ?>
</td>

<td>
    <?php echo h($task['completed_date'] ?: '-'); ?>
</td>

<td>
    <?php echo h($task['remark']); ?>
</td>

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

<?php if(!empty($task['proof_file_path'])){ ?>
    <a class="proof-link" href="../<?php echo h($task['proof_file_path']); ?>" download>
        View Proof
    </a>
<?php } else { ?>
    -
<?php } ?>

</td>

<td>

<a

href="?delete=<?php echo (int)$task['task_id']; ?>"

class="delete-btn"

onclick="return confirm('Delete Task?')">

Delete

</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</div>

<script>
const addExtraButton = document.getElementById('addExtraField');
const extraFields = document.getElementById('extraFields');
const emptyExtra = document.getElementById('emptyExtra');

function refreshEmptyState(){
    const hasRows = extraFields.querySelectorAll('.extra-field-row').length > 0;
    emptyExtra.style.display = hasRows ? 'none' : 'block';
}

function addExtraField(){
    const row = document.createElement('div');
    row.className = 'extra-field-row';
    row.innerHTML = `
        <input type="text" name="extra_label[]" placeholder="Field title">
        <textarea name="extra_value[]" placeholder="Field text for employee"></textarea>
        <button type="button" class="remove-field-btn" aria-label="Remove text field">&times;</button>
    `;

    row.querySelector('.remove-field-btn').addEventListener('click', function(){
        row.remove();
        refreshEmptyState();
    });

    extraFields.appendChild(row);
    refreshEmptyState();
    row.querySelector('input').focus();
}

addExtraButton.addEventListener('click', addExtraField);
</script>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
