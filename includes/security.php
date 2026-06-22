<?php

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ensure_table_column($conn, $table, $column, $definition)
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table . $column)) {
        return;
    }

    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");

    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "ALTER TABLE `$table` ADD `$column` $definition");
    }
}

function ensure_employee_portal_schema($conn)
{
    ensure_table_column($conn, 'users', 'mobile', 'VARCHAR(15) NULL');
    ensure_table_column($conn, 'users', 'branch_location', 'VARCHAR(100) NULL');
    ensure_table_column($conn, 'users', 'profile_photo', "VARCHAR(255) NULL DEFAULT 'default.png'");
    ensure_table_column($conn, 'users', 'last_login', 'DATETIME NULL');
    ensure_table_column($conn, 'users', 'account_status', "ENUM('Active','Inactive') DEFAULT 'Active'");
    ensure_table_column($conn, 'employee_tasks', 'assigned_date', 'DATE NULL');
    ensure_table_column($conn, 'employee_tasks', 'start_date', 'DATE NULL');
    ensure_table_column($conn, 'employee_tasks', 'end_date', 'DATE NULL');
    ensure_table_column($conn, 'employee_tasks', 'completed_date', 'DATE NULL');
    ensure_table_column($conn, 'employee_tasks', 'proof_file_name', 'VARCHAR(255) NULL');
    ensure_table_column($conn, 'employee_tasks', 'proof_file_path', 'VARCHAR(500) NULL');
    ensure_table_column($conn, 'employee_tasks', 'digital_signature', 'VARCHAR(255) NULL');
    ensure_table_column($conn, 'tasks', 'additional_fields', 'TEXT NULL');
    mysqli_query($conn, "
        ALTER TABLE employee_tasks
        MODIFY status ENUM('Pending','In Progress','Completed','Overdue') DEFAULT 'Pending'
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS compliance_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            task_id INT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS leave_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            leave_type VARCHAR(80) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            reason TEXT NULL,
            status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function ensure_admin_portal_schema($conn)
{
    ensure_employee_portal_schema($conn);
    ensure_table_column($conn, 'tasks', 'task_template', 'VARCHAR(120) NULL');
    ensure_table_column($conn, 'tasks', 'recurrence_rule', 'VARCHAR(120) NULL');
    ensure_table_column($conn, 'employee_tasks', 'approval_status', "ENUM('Pending Review','Approved','Rejected') DEFAULT 'Pending Review'");
    ensure_table_column($conn, 'employee_tasks', 'verified_by', 'INT NULL');
    ensure_table_column($conn, 'employee_tasks', 'verified_at', 'DATETIME NULL');
    ensure_table_column($conn, 'employee_tasks', 'escalation_level', 'INT DEFAULT 0');

    mysqli_query($conn, "
        ALTER TABLE users
        MODIFY role ENUM('super_admin','admin','manager','auditor','employee') DEFAULT 'employee'
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_name VARCHAR(120) NOT NULL,
            branch_code VARCHAR(40) NULL,
            location VARCHAR(160) NULL,
            manager_name VARCHAR(120) NULL,
            status ENUM('Active','Inactive') DEFAULT 'Active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS document_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id INT NOT NULL,
            reviewed_by INT NULL,
            status ENUM('Pending','Approved','Rejected','Archived') DEFAULT 'Pending',
            remarks TEXT NULL,
            reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS task_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_task_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function require_admin_access()
{
    $allowedRoles = ['super_admin', 'admin', 'manager', 'auditor'];

    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
        header('Location: ../auth/login.php');
        exit();
    }
}

function admin_role_label($role)
{
    $role = (string)$role;
    return ucwords(str_replace('_', ' ', $role));
}

function escalate_overdue_tasks($conn)
{
    $tasks = mysqli_query($conn, "
        SELECT et.id, et.escalation_level, CONCAT(u.first_name,' ',u.last_name), t.task_name, COALESCE(et.end_date, t.end_date) AS due_date
        FROM employee_tasks et
        JOIN tasks t ON et.task_id=t.id
        JOIN users u ON et.employee_id=u.id
        WHERE et.status='Overdue'
        AND COALESCE(et.end_date, t.end_date) <= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    ");

    while ($tasks && $task = mysqli_fetch_assoc($tasks)) {
        $nextLevel = max(1, (int)$task['escalation_level'] + 1);
        mysqli_query($conn, "UPDATE employee_tasks SET escalation_level='$nextLevel' WHERE id='" . (int)$task['id'] . "'");

        $message = 'Escalation L' . $nextLevel . ': ' . $task['task_name'] . ' assigned to ' . $task['name'] . ' is overdue since ' . $task['due_date'];
        $safeMessage = mysqli_real_escape_string($conn, $message);
        $exists = mysqli_query($conn, "
            SELECT id FROM notifications
            WHERE message='$safeMessage'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            LIMIT 1
        ");

        if (!$exists || mysqli_num_rows($exists) === 0) {
            mysqli_query($conn, "INSERT INTO notifications(message) VALUES('$safeMessage')");
        }
    }
}

function require_employee_login()
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employee') {
        header('Location: ../auth/login.php');
        exit();
    }
}

function log_activity($conn, $userId, $action)
{
    $userId = (int)$userId;
    $action = mysqli_real_escape_string($conn, trim((string)$action));

    if ($action === '') {
        return;
    }

    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action) VALUES ('$userId', '$action')");
}

function generate_due_task_reminders($conn, $employeeId)
{
    $employeeId = (int)$employeeId;
    $tasks = mysqli_query($conn, "
        SELECT et.id, t.task_name, COALESCE(et.end_date, t.end_date) AS due_date
        FROM employee_tasks et
        JOIN tasks t ON et.task_id = t.id
        WHERE et.employee_id='$employeeId'
        AND et.status != 'Completed'
        AND COALESCE(et.end_date, t.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ");

    while ($tasks && $task = mysqli_fetch_assoc($tasks)) {
        $message = 'Reminder: ' . $task['task_name'] . ' is due on ' . $task['due_date'];
        $safeMessage = mysqli_real_escape_string($conn, $message);
        $exists = mysqli_query($conn, "
            SELECT id FROM notifications
            WHERE message='$safeMessage'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            LIMIT 1
        ");

        if (!$exists || mysqli_num_rows($exists) === 0) {
            mysqli_query($conn, "INSERT INTO notifications(message) VALUES('$safeMessage')");
        }
    }
}

function upload_base_dir()
{
    return realpath(__DIR__ . '/../assets/uploads');
}

function ensure_upload_dir($subdir)
{
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $subdir)) {
        return false;
    }

    $base = upload_base_dir();
    if ($base === false) {
        return false;
    }

    $targetDir = $base . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    return is_dir($targetDir) ? $targetDir : false;
}

function safe_upload_path($relativePath)
{
    $relativePath = str_replace('\\', '/', (string)$relativePath);

    if (!preg_match('#^assets/uploads/[a-zA-Z0-9_-]+/[a-zA-Z0-9._-]+$#', $relativePath)) {
        return false;
    }

    $base = upload_base_dir();
    if ($base === false) {
        return false;
    }

    $absolutePath = realpath(__DIR__ . '/../' . $relativePath);
    if ($absolutePath === false) {
        return false;
    }

    return strpos($absolutePath, $base . DIRECTORY_SEPARATOR) === 0 ? $absolutePath : false;
}

function save_secure_upload($file, $subdir, &$error)
{
    $error = '';

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid file.';
        return false;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        $error = 'Maximum allowed file size is 10 MB.';
        return false;
    }

    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'Only PDF, image, Office, CSV and text files are allowed.';
        return false;
    }

    $targetDir = ensure_upload_dir($subdir);
    if ($targetDir === false) {
        $error = 'Upload folder is not available.';
        return false;
    }

    $safeOriginalName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName);
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $absoluteTarget = $targetDir . DIRECTORY_SEPARATOR . $storedName;

    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $absoluteTarget)) {
        $error = 'File upload failed.';
        return false;
    }

    return [
        'display_name' => $safeOriginalName,
        'stored_name' => $storedName,
        'relative_path' => 'assets/uploads/' . $subdir . '/' . $storedName
    ];
}

function save_profile_photo_upload($file, &$error)
{
    $error = '';

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid profile photo.';
        return false;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Profile photo must be 2 MB or smaller.';
        return false;
    }

    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];

    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'Only PNG, JPG, JPEG and WEBP profile photos are allowed.';
        return false;
    }

    $targetDir = ensure_upload_dir('profile_photos');

    if ($targetDir === false) {
        $error = 'Profile photo folder is not available.';
        return false;
    }

    $storedName = 'profile_' . bin2hex(random_bytes(12)) . '.' . $extension;
    $absoluteTarget = $targetDir . DIRECTORY_SEPARATOR . $storedName;

    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $absoluteTarget)) {
        $error = 'Profile photo upload failed.';
        return false;
    }

    return 'assets/uploads/profile_photos/' . $storedName;
}

function status_badge($status)
{
    $status = (string)$status;
    $class = 'status-dot pending-dot';

    if ($status === 'Completed') {
        $class = 'status-dot completed-dot';
    } elseif ($status === 'In Progress') {
        $class = 'status-dot progress-dot';
    } elseif ($status === 'Overdue') {
        $class = 'status-dot overdue-dot';
    }

    return "<span class=\"status-pill\"><span class=\"$class\"></span>" . h($status) . '</span>';
}

function user_profile_photo_src($profilePhoto)
{
    $profilePhoto = trim((string)$profilePhoto);

    if ($profilePhoto === '') {
        $profilePhoto = 'default.png';
    }

    if (preg_match('#^assets/uploads/[a-zA-Z0-9_/-]+\.(png|jpg|jpeg|webp)$#i', $profilePhoto)) {
        return '../' . $profilePhoto;
    }

    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $profilePhoto)) {
        $profilePhoto = 'default.png';
    }

    return '../assets/images/profile/' . $profilePhoto;
}

function account_status_badge($status)
{
    $status = trim((string)$status);
    $status = $status !== '' ? $status : 'Active';
    $class = strtolower($status) === 'inactive' ? 'inactive' : 'active';

    return '<span class="account-status ' . $class . '"><span></span>' . h($status) . '</span>';
}

function format_last_login($lastLogin)
{
    if (empty($lastLogin)) {
        return 'Never logged in';
    }

    $timestamp = strtotime((string)$lastLogin);

    if ($timestamp === false) {
        return 'Never logged in';
    }

    return date('d M Y, h:i A', $timestamp);
}

function decode_task_extra_fields($json)
{
    $decoded = json_decode((string)$json, true);

    if (!is_array($decoded)) {
        return [];
    }

    $fields = [];

    foreach ($decoded as $field) {
        if (!is_array($field)) {
            continue;
        }

        $label = trim((string)($field['label'] ?? ''));
        $value = trim((string)($field['value'] ?? ''));

        if ($label === '' && $value === '') {
            continue;
        }

        $fields[] = [
            'label' => $label !== '' ? $label : 'Additional Field',
            'value' => $value
        ];
    }

    return $fields;
}
