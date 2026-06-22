
<?php
include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_employee_login();

$employee_id = (int)$_SESSION['user_id'];
$message = '';

if(isset($_POST['submit_leave'])){

    $leave_type = mysqli_real_escape_string($conn,$_POST['leave_type']);
    $start_date = mysqli_real_escape_string($conn,$_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn,$_POST['end_date']);
    $reason = mysqli_real_escape_string($conn,$_POST['reason']);

    mysqli_query($conn,"
    INSERT INTO leave_requests
    (
        user_id,
        leave_type,
        start_date,
        end_date,
        reason,
        status
    )
    VALUES
    (
        '$employee_id',
        '$leave_type',
        '$start_date',
        '$end_date',
        '$reason',
        'Pending'
    )
    ");

    $message = "Leave Request Submitted Successfully";
}

$requests = mysqli_query($conn,"
SELECT *
FROM leave_requests
WHERE user_id='$employee_id'
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Leave Requests</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial;
}

body{
background:#eef3f8;
padding:30px;
}

.top{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
}

.btn{
background:#2563eb;
color:white;
padding:12px 18px;
border-radius:10px;
text-decoration:none;
font-weight:bold;
}

.grid{
display:grid;
grid-template-columns:340px 1fr;
gap:20px;
}

.card{
background:white;
padding:22px;
border-radius:14px;
box-shadow:0 10px 25px rgba(0,0,0,.08);
}

label{
display:block;
font-weight:bold;
margin-top:12px;
margin-bottom:6px;
}

input,textarea{
width:100%;
padding:12px;
border:1px solid #d1d5db;
border-radius:8px;
}

textarea{
height:110px;
}

.submit-btn{
margin-top:15px;
background:#2563eb;
color:white;
border:none;
padding:12px 18px;
border-radius:8px;
cursor:pointer;
font-weight:bold;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#0f172a;
color:white;
padding:14px;
text-align:left;
}

td{
padding:14px;
border-bottom:1px solid #e5e7eb;
}

tr:hover{
background:#eff6ff;
}

.response-approved{
color:#16a34a;
font-weight:bold;
}

.response-rejected{
color:#dc2626;
font-weight:bold;
}

.response-pending{
color:#f59e0b;
font-weight:bold;
}

.alert{
background:#dcfce7;
padding:12px;
margin-bottom:15px;
border-radius:8px;
color:#166534;
font-weight:bold;
}

</style>
</head>

<body>

<div class="top">
<h1>Leave Request Management</h1>
<a href="dashboard.php" class="btn">Dashboard</a>
</div>

<?php if($message!=""){ ?>
<div class="alert">
<?php echo $message; ?>
</div>
<?php } ?>

<div class="grid">

<div class="card">

<h2>New Request</h2>

<form method="POST">

<label>Leave Type</label>
<input type="text" name="leave_type" required>

<label>Start Date</label>
<input type="date" name="start_date" required>

<label>End Date</label>
<input type="date" name="end_date" required>

<label>Reason</label>
<textarea name="reason"></textarea>

<button type="submit"
name="submit_leave"
class="submit-btn">
Submit
</button>

</form>

</div>

<div class="card">

<h2>My Requests</h2>

<table>

<tr>
<th>Leave Type</th>
<th>Start Date</th>
<th>End Date</th>
<th>Reason</th>
<th>Status</th>
<th>Admin Response</th>
</tr>

<?php while($row=mysqli_fetch_assoc($requests)){ ?>

<tr>

<td><?php echo h($row['leave_type']); ?></td>

<td><?php echo h($row['start_date']); ?></td>

<td><?php echo h($row['end_date']); ?></td>

<td><?php echo h($row['reason']); ?></td>

<td><?php echo h($row['status']); ?></td>

<td>

<?php

if($row['status']=="Approved"){
echo "<span class='response-approved'>✅ Approved by Admin</span>";
}
elseif($row['status']=="Rejected"){
echo "<span class='response-rejected'>❌ Rejected by Admin</span>";
}
else{
echo "<span class='response-pending'>⏳ Waiting for Approval</span>";
}

?>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>
```
