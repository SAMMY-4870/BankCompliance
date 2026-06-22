<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

if(
!isset($_SESSION['user_id'])
||
$_SESSION['role'] != 'employee'
){
header("Location: ../auth/login.php");
exit();
}

/* FETCH FILES */

$files = mysqli_query(

$conn,

"SELECT *
FROM drive_files
ORDER BY uploaded_at DESC"

);

?>

<!DOCTYPE html>
<html>

<head>

<title>Employee Drive</title>

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

/* SIDEBAR */

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

/* MAIN */

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

box-shadow:
0 2px 10px rgba(0,0,0,0.1);

overflow:auto;

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

padding:15px;

text-align:center;

border-bottom:1px solid #ddd;

}

.download-btn{

background:#16a34a;

color:white;

padding:8px 15px;

border-radius:8px;

text-decoration:none;

}

.download-btn:hover{

opacity:0.9;

}

.no-file{

text-align:center;

padding:30px;

font-size:18px;

color:#666;

}

</style>

</head>

<body>

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

<a href="drive.php">
Drive
</a>

<a href="../auth/logout.php">
Logout
</a>

</div>

<div class="main">

<div class="heading">

Document Drive

</div>

<div class="table-box">

<table>

<tr>

<th>ID</th>
<th>File Name</th>
<th>Upload Date</th>
<th>Download</th>

</tr>

<?php

if(mysqli_num_rows($files) > 0){

while($row = mysqli_fetch_assoc($files)){

?>

<tr>

<td>
<?php echo $row['id']; ?>
</td>

<td>
<?php echo h($row['file_name']); ?>
</td>

<td>
<?php echo h($row['uploaded_at']); ?>
</td>

<td>

<a

 href="../<?php echo h($row['file_path']); ?>" 

download

class="download-btn">

Download

</a>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="4">

<div class="no-file">

No Documents Available

</div>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
