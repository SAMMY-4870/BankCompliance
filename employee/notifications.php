<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");

if($_SESSION['role'] != 'employee'){

    header("Location: ../auth/login.php");
}

/* FETCH NOTIFICATIONS */

$notifications = mysqli_query(

    $conn,

    "SELECT * FROM notifications
    ORDER BY id DESC"

);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Notifications</title>

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

            background:#0f172a;

            padding:20px;

            position:fixed;
        }

        .sidebar h2{

            color:white;

            margin-bottom:30px;
        }

        .sidebar a{

            display:block;

            color:white;

            text-decoration:none;

            padding:15px;

            margin-bottom:10px;

            border-radius:10px;

            background:#1e293b;

            transition:0.3s;
        }

        .sidebar a:hover{

            background:#2563eb;
        }

        /* MAIN */

        .main{

            margin-left:270px;

            width:100%;

            padding:30px;
        }

        .main h1{

            margin-bottom:30px;

            color:#0f172a;
        }

        .notification-box{

            background:white;

            padding:20px;

            border-radius:15px;

            margin-bottom:20px;

            box-shadow:
            0 2px 10px rgba(0,0,0,0.1);

            border-left:5px solid #2563eb;
        }

        .notification-message{

            font-size:17px;

            color:#111827;

            margin-bottom:10px;
        }

        .notification-time{

            color:gray;

            font-size:14px;
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

    <a href="../auth/logout.php">
        Logout
    </a>

</div>

<!-- MAIN -->

<div class="main">

    <h1>Notifications</h1>

    <?php

    while($row =
    mysqli_fetch_assoc($notifications)){

    ?>

    <div class="notification-box">

        <div class="notification-message">

            <?php
            echo $row['message'];
            ?>

        </div>

        <div class="notification-time">

            <?php
            echo $row['created_at'];
            ?>

        </div>

    </div>

    <?php } ?>

</div>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
