<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

/* COUNTS */

$totalEmployees = mysqli_num_rows(
mysqli_query(
$conn,
"SELECT * FROM users WHERE role='employee'"
)
);

$totalTasks = mysqli_num_rows(
mysqli_query(
$conn,
"SELECT * FROM tasks"
)
);

$pendingTasks = mysqli_num_rows(
mysqli_query(
$conn,
"SELECT * FROM employee_tasks
WHERE status='Pending'"
)
);

$completedTasks = mysqli_num_rows(
mysqli_query(
$conn,
"SELECT * FROM employee_tasks
WHERE status='Completed'"
)
);

$overdueTasks = mysqli_num_rows(
mysqli_query(
$conn,
"SELECT * FROM employee_tasks
WHERE status='Overdue'"
)
);

?>

<!DOCTYPE html>
<html>
<head>

<title>Analytics Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial,sans-serif;
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
margin-bottom:25px;
}

.cards{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin-bottom:30px;
}

.card{
background:white;
padding:25px;
border-radius:15px;
box-shadow:0 3px 10px rgba(0,0,0,0.1);
}

.card h3{
margin-bottom:10px;
}

.card p{
font-size:32px;
font-weight:bold;
}

.chart-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
}

.chart-box{
background:white;
padding:20px;
border-radius:15px;
box-shadow:0 3px 10px rgba(0,0,0,0.1);
height:450px;
}

.chart-box canvas{
width:100% !important;
height:350px !important;
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

<div class="heading">
Analytics Dashboard
</div>

<div class="cards">

<div class="card">
<h3>Employees</h3>
<p><?php echo $totalEmployees; ?></p>
</div>

<div class="card">
<h3>Tasks</h3>
<p><?php echo $totalTasks; ?></p>
</div>

<div class="card">
<h3>Pending</h3>
<p><?php echo $pendingTasks; ?></p>
</div>

<div class="card">
<h3>Completed</h3>
<p><?php echo $completedTasks; ?></p>
</div>

</div>

<div class="chart-grid">

<div class="chart-box">

<h2>Task Status</h2>

<canvas id="pieChart"></canvas>

</div>

<div class="chart-box">

<h2>System Overview</h2>

<canvas id="barChart"></canvas>

</div>

</div>

</div>

<script>

new Chart(
document.getElementById('pieChart'),
{
type:'pie',
data:{
labels:[
'Pending',
'Completed',
'Overdue'
],
datasets:[{
data:[
<?php echo $pendingTasks; ?>,
<?php echo $completedTasks; ?>,
<?php echo $overdueTasks; ?>
]
}]
}
}
);

new Chart(
document.getElementById('barChart'),
{
type:'bar',
data:{
labels:[
'Employees',
'Tasks',
'Pending',
'Completed'
],
datasets:[{
label:'System Data',
data:[
<?php echo $totalEmployees; ?>,
<?php echo $totalTasks; ?>,
<?php echo $pendingTasks; ?>,
<?php echo $completedTasks; ?>
]
}]
}
}
);

</script>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
