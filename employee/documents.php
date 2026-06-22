<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_employee_login();
ensure_employee_portal_schema($conn);

$employee_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $uploadError = '';
    $uploaded = save_secure_upload($_FILES['evidence_file'] ?? null, 'task_proofs', $uploadError);

    if ($uploaded) {
        $fileName = mysqli_real_escape_string($conn, $uploaded['display_name']);
        $filePath = mysqli_real_escape_string($conn, $uploaded['relative_path']);
        $taskValue = $task_id > 0 ? "'$task_id'" : 'NULL';
        mysqli_query($conn, "
            INSERT INTO compliance_documents (employee_id, task_id, file_name, file_path)
            VALUES ('$employee_id', $taskValue, '$fileName', '$filePath')
        ");
        log_activity($conn, $employee_id, 'Uploaded compliance document');
        $message = 'Document uploaded successfully.';
    } else {
        $error = $uploadError;
    }
}

$taskOptions = mysqli_query($conn, "
    SELECT et.id, t.task_name
    FROM employee_tasks et
    JOIN tasks t ON et.task_id=t.id
    WHERE et.employee_id='$employee_id'
    ORDER BY et.id DESC
");

$documents = mysqli_query($conn, "
    SELECT cd.*, t.task_name
    FROM compliance_documents cd
    LEFT JOIN employee_tasks et ON cd.task_id=et.id
    LEFT JOIN tasks t ON et.task_id=t.id
    WHERE cd.employee_id='$employee_id'
    ORDER BY cd.uploaded_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Document Evidence Center</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}
        body{background:#eef2f7;color:#0f172a;padding:30px;}
        .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;}
        .btn,a.btn{background:#2563eb;color:white;text-decoration:none;border:0;border-radius:10px;padding:11px 14px;font-weight:800;cursor:pointer;}
        .grid{display:grid;grid-template-columns:360px 1fr;gap:20px;}
        .panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08);}
        label{display:block;font-weight:800;margin:14px 0 7px;}
        input,select{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:8px;}
        table{width:100%;border-collapse:collapse;margin-top:8px;}
        th{background:#0f172a;color:white;text-align:left;padding:12px;}td{padding:12px;border-bottom:1px solid #e2e8f0;}
        .notice{padding:12px;border-radius:8px;margin-bottom:14px;font-weight:800;}.success{background:#dcfce7;color:#166534;}.error{background:#fee2e2;color:#991b1b;}
        @media(max-width:900px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
    <div class="top"><h1>Document & Evidence Center</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
    <?php if ($message !== '') { ?><div class="notice success"><?php echo h($message); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="notice error"><?php echo h($error); ?></div><?php } ?>
    <div class="grid">
        <section class="panel">
            <h2>Upload Evidence</h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Related Task</label>
                <select name="task_id">
                    <option value="0">General compliance document</option>
                    <?php while ($task = mysqli_fetch_assoc($taskOptions)) { ?>
                        <option value="<?php echo (int)$task['id']; ?>"><?php echo h($task['task_name']); ?></option>
                    <?php } ?>
                </select>
                <label>File</label>
                <input type="file" name="evidence_file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx,.csv,.txt" required>
                <button class="btn" type="submit" name="upload_document" style="margin-top:14px;">Upload</button>
            </form>
        </section>
        <section class="panel">
            <h2>Uploaded Documents</h2>
            <table>
                <tr><th>File</th><th>Task</th><th>Uploaded</th><th>Download</th></tr>
                <?php while ($doc = mysqli_fetch_assoc($documents)) { ?>
                    <tr>
                        <td><?php echo h($doc['file_name']); ?></td>
                        <td><?php echo h($doc['task_name'] ?: 'General'); ?></td>
                        <td><?php echo h(format_last_login($doc['uploaded_at'])); ?></td>
                        <td><a href="../<?php echo h($doc['file_path']); ?>" download>Download</a></td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    </div>
    <?php include("../includes/team_chat.php"); ?>
</body>
</html>
