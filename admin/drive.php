<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);
/* UPLOAD FILE */

if(isset($_POST['upload'])){

$uploadError = '';
$uploadedFile = save_secure_upload($_FILES['file'], 'drive', $uploadError);

if($uploadedFile){

$file_name = mysqli_real_escape_string($conn, $uploadedFile['display_name']);
$db_path = mysqli_real_escape_string($conn, $uploadedFile['relative_path']);
$uploaded_by = (int)$_SESSION['user_id'];

mysqli_query(
$conn,
"INSERT INTO drive_files
(
file_name,
file_path,
uploaded_by
)
VALUES
(
'$file_name',
'$db_path',
'$uploaded_by'
)"

);

echo "<script>alert('File Uploaded Successfully');</script>";

}else{

echo "<script>alert(" . json_encode($uploadError) . ");</script>";

}

}

/* DELETE FILE */

if(isset($_GET['delete'])){

$id = (int)$_GET['delete'];

$get = mysqli_query(
$conn,
"SELECT * FROM drive_files WHERE id='$id'"
);

$row = mysqli_fetch_assoc($get);

if($row){
    $absoluteFilePath = safe_upload_path($row['file_path']);
    if($absoluteFilePath && file_exists($absoluteFilePath)){
        unlink($absoluteFilePath);
    }
}

mysqli_query(
$conn,
"DELETE FROM drive_files WHERE id='$id'"
);

header("Location: drive.php");
exit();

}

$query = "
SELECT *
FROM drive_files
ORDER BY uploaded_at DESC
";

$files = mysqli_query($conn, $query);

if(!$files){
    die("Drive Query Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>

<head>

<title>Admin Drive</title>

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

.container{
width:95%;
margin:30px auto;
}

.heading{
font-size:32px;
font-weight:bold;
margin-bottom:20px;
}

.upload-box{

background:white;
padding:25px;
border-radius:15px;
margin-bottom:25px;

box-shadow:
0 2px 10px rgba(0,0,0,0.1);

}

input[type=file]{

width:100%;
padding:12px;
margin-bottom:15px;

}

.upload-btn{

background:#2563eb;
color:white;
border:none;

padding:12px 20px;

border-radius:8px;
cursor:pointer;

}

.table-box{

background:white;
padding:20px;

border-radius:15px;

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

padding:15px;
text-align:center;

border-bottom:1px solid #ddd;

}

.download-btn{

background:green;
color:white;

padding:8px 15px;

border-radius:6px;
text-decoration:none;

}

.delete-btn{

background:red;
color:white;

padding:8px 15px;

border-radius:6px;
text-decoration:none;

margin-left:5px;

}

</style>

</head>

<body>

<div class="container">

<div class="heading">

Document Drive

</div>

<div class="upload-box">

<form
method="POST"
enctype="multipart/form-data">

<input
type="file"
name="file"
required>

<button
type="submit"
name="upload"
class="upload-btn">

Upload File

</button>

</form>

</div>

<div class="table-box">

<table>

<tr>

<th>ID</th>
<th>File Name</th>
<th>Upload Date</th>
<th>Action</th>

</tr>

<?php

while($row =
mysqli_fetch_assoc($files)){

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
class="download-btn"
download>

Download

</a>

<a
href="?delete=<?php echo (int)$row['id']; ?>"
class="delete-btn"
onclick="return confirm('Delete File?')">

Delete

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
