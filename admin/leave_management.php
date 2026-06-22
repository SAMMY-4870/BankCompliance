
<?php
include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}

/* APPROVE */

if(isset($_GET['approve'])){

    $id = (int)$_GET['approve'];

    mysqli_query($conn,"
    UPDATE leave_requests
    SET
    status='Approved',
    admin_response='Approved by Admin'
    WHERE id='$id'
    ");

    header("Location: leave_management.php");
    exit;
}

/* REJECT */

if(isset($_GET['reject'])){

    $id = (int)$_GET['reject'];

    mysqli_query($conn,"
    UPDATE leave_requests
    SET
    status='Rejected',
    admin_response='Rejected by Admin'
    WHERE id='$id'
    ");

    header("Location: leave_management.php");
    exit;
}

$leaves = mysqli_query($conn,"
SELECT
lr.*,
CONCAT(u.first_name,' ',u.last_name) AS employee_name,
u.employee_id
FROM leave_requests lr
LEFT JOIN users u
ON lr.user_id = u.id
ORDER BY lr.id DESC
");

$total_requests = mysqli_num_rows($leaves);
?>

<!DOCTYPE html>
<html>
<head>

<title>Leave Management</title>

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

.badge{
background:#2563eb;
color:white;
padding:12px 18px;
border-radius:30px;
font-weight:bold;
}

.card{
background:white;
padding:25px;
border-radius:16px;
box-shadow:0 10px 25px rgba(0,0,0,.08);
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:linear-gradient(135deg,#0f172a,#2563eb);
color:white;
padding:15px;
text-align:left;
}

td{
padding:15px;
border-bottom:1px solid #e5e7eb;
}

tr:hover{
background:#eff6ff;
}

.pending{
color:#f59e0b;
font-weight:bold;
}

.approved{
color:#16a34a;
font-weight:bold;
}

.rejected{
color:#dc2626;
font-weight:bold;
}

.approve-btn{
background:#16a34a;
color:white;
padding:8px 14px;
border-radius:8px;
text-decoration:none;
font-weight:bold;
}

.reject-btn{
background:#dc2626;
color:white;
padding:8px 14px;
border-radius:8px;
text-decoration:none;
font-weight:bold;
margin-left:5px;
}

.done{
color:#64748b;
font-weight:bold;
}

</style>

</head>

<body>

<div class="top">

<h1>Leave Request Management</h1>

<div class="badge">
Total Requests: <?php echo $total_requests; ?>
</div>

</div>

<div class="card">

<table>

<tr>
<th>ID</th>
<th>Employee</th>
<th>Employee Code</th>
<th>Leave Type</th>
<th>Start Date</th>
<th>End Date</th>
<th>Reason</th>
<th>Leave Response</th>
<th>Submitted</th>
<th>Action</th>
</tr>

<?php while($leave=mysqli_fetch_assoc($leaves)){ ?>

<tr>

<td><?php echo $leave['id']; ?></td>

<td><?php echo h($leave['employee_name']); ?></td>

<td><?php echo h($leave['employee_id']); ?></td>

<td><?php echo h($leave['leave_type']); ?></td>

<td><?php echo h($leave['start_date']); ?></td>

<td><?php echo h($leave['end_date']); ?></td>

<td><?php echo h($leave['reason']); ?></td>

<td>

<?php

if($leave['status']=="Approved"){
echo "<span class='approved'>✅ Approved by Admin</span>";
}
elseif($leave['status']=="Rejected"){
echo "<span class='rejected'>❌ Rejected by Admin</span>";
}
else{
echo "<span class='pending'>⏳ Waiting for Admin Approval</span>";
}

?>

</td>

<td><?php echo h($leave['created_at']); ?></td>

<td>

<?php if($leave['status']=="Pending"){ ?>

<a class="approve-btn"
href="?approve=<?php echo $leave['id']; ?>">
✓ Approve
</a>

<a class="reject-btn"
href="?reject=<?php echo $leave['id']; ?>">
✕ Reject
</a>

<?php } else { ?>

<span class="done">
Completed
</span>

<?php } ?>

</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>
```
