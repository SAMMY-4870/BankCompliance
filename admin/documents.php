<?php
include("../includes/session.php");
app_session_start();
include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

$adminId = (int)$_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_document'])) {
    $documentId = (int)$_POST['document_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Pending');
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    mysqli_query($conn, "INSERT INTO document_reviews (document_id, reviewed_by, status, remarks) VALUES ('$documentId', '$adminId', '$status', '$remarks')");
    log_activity($conn, $adminId, 'Reviewed compliance document #' . $documentId . ' as ' . $status);
    $message = 'Document review saved.';
}

$documents = mysqli_query($conn, "
    SELECT cd.*, CONCAT(u.first_name,' ',u.last_name), u.employee_id, u.branch_location, t.task_name,
           (SELECT dr.status FROM document_reviews dr WHERE dr.document_id=cd.id ORDER BY dr.reviewed_at DESC LIMIT 1) AS review_status
    FROM compliance_documents cd
    JOIN users u ON cd.employee_id=u.id
    LEFT JOIN employee_tasks et ON cd.task_id=et.id
    LEFT JOIN tasks t ON et.task_id=t.id
    ORDER BY cd.uploaded_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Document Management</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;}body{background:#eef2f7;padding:30px;color:#0f172a}.top{display:flex;justify-content:space-between;margin-bottom:20px}.btn,button{background:#2563eb;color:white;text-decoration:none;border:0;border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer}.panel{background:white;border-radius:8px;padding:20px;box-shadow:0 4px 18px rgba(15,23,42,.08);overflow:auto}.notice{background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:14px;font-weight:800}table{width:100%;border-collapse:collapse;min-width:1100px}th{background:#0f172a;color:white;text-align:left;padding:12px}td{padding:12px;border-bottom:1px solid #e2e8f0}select,input{padding:9px;border:1px solid #cbd5e1;border-radius:8px}
    </style>
</head>
<body>
    <div class="top"><h1>Document Management System</h1><a class="btn" href="dashboard.php">Dashboard</a></div>
    <?php if ($message !== '') { ?><div class="notice"><?php echo h($message); ?></div><?php } ?>
    <section class="panel">
        <table>
            <tr><th>Document</th><th>Employee</th><th>Branch</th><th>Task</th><th>Uploaded</th><th>Status</th><th>Action</th></tr>
            <?php while ($doc = mysqli_fetch_assoc($documents)) { ?>
                <tr>
                    <td><a href="../<?php echo h($doc['file_path']); ?>" download><?php echo h($doc['file_name']); ?></a></td>
                    <td><?php echo h($doc['name']); ?> <small><?php echo h($doc['employee_id']); ?></small></td>
                    <td><?php echo h($doc['branch_location'] ?: 'Unassigned'); ?></td>
                    <td><?php echo h($doc['task_name'] ?: 'General'); ?></td>
                    <td><?php echo h(format_last_login($doc['uploaded_at'])); ?></td>
                    <td><?php echo h($doc['review_status'] ?: 'Pending'); ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <input type="hidden" name="document_id" value="<?php echo (int)$doc['id']; ?>">
                            <select name="status"><option>Approved</option><option>Rejected</option><option>Archived</option><option>Pending</option></select>
                            <input name="remarks" placeholder="Remarks">
                            <button type="submit" name="review_document">Save</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </section>
</body>
</html>
