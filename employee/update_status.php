<?php

include("../includes/session.php");
app_session_start();
include("../config/database.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$allowed_statuses = ['Pending', 'In Progress', 'Overdue'];

if ($task_id > 0 && in_array($status, $allowed_statuses, true)) {
    $employee_id = (int)$_SESSION['user_id'];
    $status = mysqli_real_escape_string($conn, $status);

    mysqli_query($conn, "
        UPDATE employee_tasks
        SET status='$status',
            start_date = CASE WHEN '$status'='In Progress' AND start_date IS NULL THEN CURDATE() ELSE start_date END
        WHERE id='$task_id'
        AND employee_id='$employee_id'
        AND status != 'Completed'
    ");
}

header("Location: mytasks.php");
exit();
?>
