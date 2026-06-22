<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid Task");
}

$id = intval($_GET['id']);
$employee_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

ensure_employee_portal_schema($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_task'])) {
    mysqli_query($conn, "
        UPDATE employee_tasks
        SET status='In Progress', start_date=COALESCE(start_date, CURDATE())
        WHERE id='$id'
        AND employee_id='$employee_id'
        AND status='Pending'
    ");
    log_activity($conn, $employee_id, 'Started task #' . $id);
    $message = 'Task moved to In Progress.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $ownerCheck = mysqli_query($conn, "
        SELECT id
        FROM employee_tasks
        WHERE id = '$id'
        AND employee_id = '$employee_id'
        LIMIT 1
    ");

    if (!$ownerCheck || mysqli_num_rows($ownerCheck) === 0) {
        $error = 'Task not found.';
    } else {
        $uploadError = '';
        $uploadedFile = save_secure_upload($_FILES['proof_file'] ?? null, 'task_proofs', $uploadError);

        if (!$uploadedFile) {
            $error = $uploadError;
        } else {
            $proofName = mysqli_real_escape_string($conn, $uploadedFile['display_name']);
            $proofPath = mysqli_real_escape_string($conn, $uploadedFile['relative_path']);
            $digitalSignature = mysqli_real_escape_string($conn, trim($_POST['digital_signature'] ?? ''));
            $newStatus = 'Completed';
            $taskDateCheck = mysqli_query($conn, "
                SELECT t.end_date
                FROM employee_tasks et
                JOIN tasks t ON et.task_id = t.id
                WHERE et.id = '$id'
                AND et.employee_id = '$employee_id'
                LIMIT 1
            ");

            if ($taskDateCheck && $taskDate = mysqli_fetch_assoc($taskDateCheck)) {
                if (!empty($taskDate['end_date']) && $taskDate['end_date'] < date('Y-m-d')) {
                    $newStatus = 'Overdue';
                }
            }

            $safeStatus = mysqli_real_escape_string($conn, $newStatus);

            mysqli_query($conn, "
                UPDATE employee_tasks
                SET
                    status = '$safeStatus',
                    completed_date = CURDATE(),
                    proof_file_name = '$proofName',
                    proof_file_path = '$proofPath',
                    digital_signature = '$digitalSignature'
                WHERE id = '$id'
                AND employee_id = '$employee_id'
            ");

            mysqli_query($conn, "
                INSERT INTO compliance_documents (employee_id, task_id, file_name, file_path)
                VALUES ('$employee_id', '$id', '$proofName', '$proofPath')
            ");
            log_activity($conn, $employee_id, 'Submitted evidence for task #' . $id);

            $message = $newStatus === 'Overdue'
                ? 'Task proof uploaded successfully. This task is overdue because it was completed after the end date.'
                : 'Task marked as completed and proof uploaded successfully.';
        }
    }
}

$query = mysqli_query($conn, "
SELECT et.*, t.task_name, t.frequency, t.start_date, t.end_date, t.remark, t.additional_fields
FROM employee_tasks et
JOIN tasks t ON et.task_id = t.id
WHERE et.id = '$id'
AND et.employee_id = '$employee_id'
");

$task = mysqli_fetch_assoc($query);

if (!$task) {
    die("Task not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Task Details</title>
    <style>
        *{box-sizing:border-box;}
        body{font-family:Arial;background:#f4f6f9;padding:30px;color:#172033;}
        .box{
            max-width:780px;
            margin:auto;
            background:#fff;
            padding:28px;
            border-radius:8px;
            border:1px solid #e2e8f0;
            box-shadow:0 10px 24px rgba(15,23,42,.08);
        }
        h2{margin-bottom:20px;}
        p{margin:10px 0;line-height:1.5;}
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
            box-shadow:0 0 0 4px rgba(15,23,42,.06);
        }
        .pending-dot{background:#f59e0b;}
        .progress-dot{background:#0ea5e9;}
        .completed-dot{background:#16a34a;}
        .overdue-dot{background:#facc15;}
        .upload-panel{
            margin-top:24px;
            padding:18px;
            border:1px solid #dbe4ef;
            border-radius:8px;
            background:#f8fafc;
        }
        .upload-panel label{
            display:block;
            font-weight:700;
            margin-bottom:8px;
        }
        .upload-panel input[type=file]{
            width:100%;
            padding:12px;
            border:1px solid #cbd5e1;
            border-radius:6px;
            background:#fff;
            margin-bottom:14px;
        }
        .complete-btn{
            background:#15803d;
            color:#fff;
            border:none;
            padding:10px 18px;
            border-radius:6px;
            cursor:pointer;
            font-weight:700;
        }
        .notice{
            padding:12px 14px;
            border-radius:6px;
            margin-bottom:16px;
            font-weight:700;
        }
        .success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
        .error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
        .btn{
            display:inline-block;
            padding:10px 18px;
            background:#2563eb;
            color:#fff;
            text-decoration:none;
            border-radius:6px;
            margin-top:20px;
        }
        .extra-fields{
            margin-top:20px;
            padding:18px;
            border:1px solid #dbe4ef;
            border-radius:8px;
            background:#f8fafc;
        }
        .extra-fields h3{
            margin-bottom:12px;
            font-size:18px;
        }
        .extra-item{
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:8px;
            padding:12px;
            margin-top:10px;
        }
        .extra-item strong{
            display:block;
            color:#475569;
            margin-bottom:6px;
        }
    </style>
</head>
<body>

<div class="box">
    <?php if ($message !== '') { ?>
        <div class="notice success"><?php echo h($message); ?></div>
    <?php } ?>

    <?php if ($error !== '') { ?>
        <div class="notice error"><?php echo h($error); ?></div>
    <?php } ?>

    <h2><?php echo h($task['task_name']); ?></h2>

    <p><strong>Frequency:</strong> <?php echo h($task['frequency']); ?></p>
    <p><strong>Start Date:</strong> <?php echo h($task['start_date']); ?></p>
    <p><strong>End Date:</strong> <?php echo h($task['end_date']); ?></p>
    <p><strong>Remark:</strong> <?php echo h($task['remark']); ?></p>
    <p><strong>Status:</strong> <?php echo status_badge($task['status']); ?></p>
    <p><strong>Assigned Date:</strong> <?php echo h($task['assigned_date'] ?: 'Not set'); ?></p>
    <p><strong>Employee Start Date:</strong> <?php echo h($task['start_date'] ?: 'Not started'); ?></p>
    <p><strong>Completion Date:</strong> <?php echo h($task['completed_date'] ?: '-'); ?></p>

    <?php
    $extraFields = decode_task_extra_fields($task['additional_fields'] ?? '');
    if (!empty($extraFields)) {
    ?>
        <div class="extra-fields">
            <h3>Admin Added Fields</h3>
            <?php foreach ($extraFields as $field) { ?>
                <div class="extra-item">
                    <strong><?php echo h($field['label']); ?></strong>
                    <span><?php echo nl2br(h($field['value'])); ?></span>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!empty($task['proof_file_path'])) { ?>
        <p>
            <strong>Uploaded Proof:</strong>
            <a href="../<?php echo h($task['proof_file_path']); ?>" download>
                <?php echo h($task['proof_file_name']); ?>
            </a>
        </p>
    <?php } ?>

    <?php if (empty($task['proof_file_path'])) { ?>
        <?php if ($task['status'] === 'Pending') { ?>
            <form method="POST" class="upload-panel">
                <button type="submit" name="start_task" class="complete-btn" style="background:#0ea5e9;">
                    Start Task
                </button>
            </form>
        <?php } ?>
        <form method="POST" enctype="multipart/form-data" class="upload-panel">
            <label for="proof_file">Upload completion proof</label>
            <input
                type="file"
                id="proof_file"
                name="proof_file"
                accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx,.csv,.txt"
                required>
            <label for="digital_signature">Digital signature / employee confirmation</label>
            <input
                type="text"
                id="digital_signature"
                name="digital_signature"
                maxlength="255"
                placeholder="Type your name as confirmation"
                required>

            <button type="submit" name="complete_task" class="complete-btn">
                Mark Completed
            </button>
        </form>
    <?php } ?>

    <a href="mytasks.php" class="btn">Back</a>
</div>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
