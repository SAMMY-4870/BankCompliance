<?php

include("../includes/session.php");
app_session_start();

include("../config/database.php");
include("../includes/security.php");

require_admin_access();
ensure_admin_portal_schema($conn);

$adminId = (int)$_SESSION['user_id'];
$message = '';
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$branch = trim($_GET['branch'] ?? '');
$allowedRoles = ['super_admin', 'admin', 'manager', 'auditor', 'employee'];
$allowedStatuses = ['Active', 'Inactive'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['employee_ids'] ?? [];
    $bulkStatus = $_POST['bulk_status'] ?? '';

    if (is_array($ids) && in_array($bulkStatus, $allowedStatuses, true)) {
        $safeIds = array_map('intval', $ids);
        $safeIds = array_filter($safeIds);

        if (!empty($safeIds)) {
            $idList = implode(',', $safeIds);
            $safeStatus = mysqli_real_escape_string($conn, $bulkStatus);
            mysqli_query($conn, "UPDATE users SET account_status='$safeStatus' WHERE id IN ($idList)");
            log_activity($conn, $adminId, 'Updated employee account status in bulk');
            $message = 'Bulk action applied.';
        }
    }
}

$where = "WHERE 1=1";

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $where .= " AND (name LIKE '%$safeSearch%' OR email LIKE '%$safeSearch%' OR employee_id LIKE '%$safeSearch%')";
}

if ($role !== '' && in_array($role, $allowedRoles, true)) {
    $safeRole = mysqli_real_escape_string($conn, $role);
    $where .= " AND role='$safeRole'";
}

if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $safeStatus = mysqli_real_escape_string($conn, $status);
    $where .= " AND account_status='$safeStatus'";
}

if ($branch !== '') {
    $safeBranch = mysqli_real_escape_string($conn, $branch);
    $where .= " AND branch_location LIKE '%$safeBranch%'";
}

$query = "SELECT * FROM users $where ORDER BY role ASC, first_name ASC, last_name ASC";

$result = mysqli_query($conn, $query);
if(!$result){
    die("Employees Query Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Employees</title>

    <link rel="stylesheet"
    href="../assets/css/admin.css?v=2">

</head>

<body>

<div class="sidebar">

    <h2>Bank Compliance</h2>

    <ul>

        <li>
            <a href="dashboard.php">
                Dashboard
            </a>
        </li>

        <li>
            <a href="employees.php">
                Employees
            </a>
        </li>

        <li>
            <a href="tasks.php">
            Tasks
            </a>
        </li>

        <li>
            <a href="reports.php">
            Reports
            </a>
        </li>
        <li><a href="audit_trail.php">Audit Trail</a></li>

        <li>
            <a href="../auth/logout.php">
                Logout
            </a>
        </li>

    </ul>

</div>

<div class="main-content">

    <div class="page-header">
        <div>
            <p class="eyebrow">Staff Directory</p>
            <h1>Admin and Employee Profiles</h1>
        </div>
        <a class="primary-action" href="dashboard.php">Dashboard</a>
    </div>
    <?php if ($message !== '') { ?><div class="notice success"><?php echo h($message); ?></div><?php } ?>

    <form method="GET" class="employee-filter" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
        <input type="text" name="search" placeholder="Search name, email, code" value="<?php echo h($search); ?>">
        <input type="text" name="branch" placeholder="Branch" value="<?php echo h($branch); ?>">
        <select name="role">
            <option value="">All Roles</option>
            <?php foreach ($allowedRoles as $item) { ?><option value="<?php echo h($item); ?>" <?php echo $role === $item ? 'selected' : ''; ?>><?php echo h(admin_role_label($item)); ?></option><?php } ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo $status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
        <button type="submit" class="primary-action" style="border:0;">Filter</button>
    </form>

    <div class="table-card">
    <form method="POST">
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;">
        <select name="bulk_status">
            <option value="Active">Mark Active</option>
            <option value="Inactive">Mark Inactive</option>
        </select>
        <button type="submit" name="bulk_action" class="primary-action" style="border:0;">Apply Bulk Action</button>
    </div>

    <table class="staff-table">

        <thead>
        <tr>
            <th>Select</th>
            <th>Profile</th>
            <th>Contact</th>
            <th>Role</th>
            <th>Branch</th>
            <th>Status</th>
            <th>Last Login</th>
        </tr>
        </thead>

        <tbody>

        <?php

        while($row = mysqli_fetch_assoc($result)){
            $profileUrl = 'employee_profile.php?id=' . (int)$row['id'];
            $nameParts = preg_split('/\s+/', trim((string)$row['first_name'].' '.$row['last_name']));
            $initials = '';

            foreach ($nameParts as $part) {
                if ($part !== '') {
                    $initials .= strtoupper(substr($part, 0, 1));
                }

                if (strlen($initials) === 2) {
                    break;
                }
            }

            $initials = $initials !== '' ? $initials : 'U';

        ?>

        <tr class="clickable-row" onclick="window.location='<?php echo $profileUrl; ?>'">
            <td onclick="event.stopPropagation();"><input type="checkbox" name="employee_ids[]" value="<?php echo (int)$row['id']; ?>"></td>

            <td>
                <div class="person-cell">
                    <img class="staff-avatar" src="<?php echo h(user_profile_photo_src($row['profile_photo'] ?? 'default.png')); ?>" alt="">
                    <div>
                        <strong><?php echo h($row['first_name'].' '.$row['last_name']); ?></strong>
                        <span><?php echo h($row['employee_id'] ?: 'Admin'); ?></span>
                    </div>
                </div>
            </td>

            <td>
                <div class="stacked-text">
                    <strong><?php echo h($row['email']); ?></strong>
                    <span><?php echo h($row['mobile'] ?: 'Mobile not added'); ?></span>
                </div>
            </td>

            <td>
                <span class="role-pill"><?php echo h(admin_role_label($row['role'])); ?></span>
            </td>

            <td><?php echo h($row['branch_location'] ?: 'Not assigned'); ?></td>

            <td><?php echo account_status_badge($row['account_status'] ?? 'Active'); ?></td>

            <td><?php echo h(format_last_login($row['last_login'] ?? null)); ?></td>

        </tr>

        <?php } ?>

        </tbody>
    </table>
    </form>
    </div>

</div>

<?php include("../includes/team_chat.php"); ?>

</body>
</html>
