<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

$success = '';
$error   = '';

// ── Edit user details ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $uid   = (int)$_POST['user_id'];
    $fn    = trim($_POST['first_name'] ?? '');
    $ln    = trim($_POST['last_name']  ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $addr  = trim($_POST['default_address'] ?? '');
    $city  = trim($_POST['default_city']    ?? '');

    if (empty($fn) || empty($email)) {
        $error = 'First name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Check email not taken by another user
        $chk = $db->con->prepare("SELECT user_id FROM user WHERE email=? AND user_id!=?");
        $chk->bind_param("si", $email, $uid);
        $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'This email is already used by another account.';
        } else {
            $db->con->query("ALTER TABLE user ADD COLUMN IF NOT EXISTS default_address TEXT DEFAULT NULL");
            $db->con->query("ALTER TABLE user ADD COLUMN IF NOT EXISTS default_city VARCHAR(100) DEFAULT NULL");
            $stmt = $db->con->prepare(
                "UPDATE user SET first_name=?,last_name=?,email=?,phone=?,default_address=?,default_city=? WHERE user_id=?"
            );
            $stmt->bind_param("ssssssi", $fn, $ln, $email, $phone, $addr, $city, $uid);
            $stmt->execute(); $stmt->close();
            $success = 'User details updated.';
        }
        $chk->close();
    }
}

// ── Reset user password ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_user_password'])) {
    $uid    = (int)$_POST['user_id'];
    $newpw  = trim($_POST['new_user_password'] ?? '');
    if (strlen($newpw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hashed = password_hash($newpw, PASSWORD_BCRYPT);
        $stmt = $db->con->prepare("UPDATE user SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $hashed, $uid);
        $stmt->execute(); $stmt->close();
        $success = 'User password has been reset.';
    }
}

// ── Toggle role ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_role'])) {
    $uid      = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    if (!in_array($new_role, ['customer','seller','admin'])) $new_role = 'customer';
    if ($new_role === 'admin' && !$auth->isSuperAdmin()) {
        $error = 'Only the Super Admin can assign Admin role.';
    } elseif ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot change your own role.';
    } else {
        $target = $db->con->query("SELECT role FROM user WHERE user_id=$uid LIMIT 1")->fetch_assoc();
        if ($target['role'] === 'superadmin') {
            $error = 'The Super Admin role cannot be changed.';
        } else {
            $db->con->query("UPDATE user SET role='{$new_role}' WHERE user_id={$uid}");
            $success = 'User role updated to ' . ucfirst($new_role) . '.';
        }
    }
}

// ── Delete user ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $target = $db->con->query("SELECT role,avatar FROM user WHERE user_id=$uid LIMIT 1")->fetch_assoc();
        if ($target['role'] === 'superadmin') {
            $error = 'The Super Admin account cannot be deleted.';
        } else {
            if (!empty($target['avatar']) && file_exists('../' . $target['avatar'])) @unlink('../' . $target['avatar']);
            $db->con->query("DELETE FROM cart WHERE user_id=$uid");
            $db->con->query("DELETE FROM wishlist WHERE user_id=$uid");
            $db->con->query("DELETE FROM reviews WHERE user_id=$uid");
            $db->con->query("DELETE FROM password_resets WHERE user_id=$uid");
            $db->con->query("DELETE FROM user WHERE user_id=$uid");
            $success = 'User deleted.';
        }
    }
}

// ── Fetch users ──────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$users  = [];

$where = "WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '') {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}
if ($filter === 'customer') { $where .= " AND role='customer'"; }
elseif ($filter === 'seller')   { $where .= " AND role='seller'"; }
elseif ($filter === 'admin')    { $where .= " AND role IN ('admin','superadmin')"; }

$sql = "SELECT * FROM user $where ORDER BY register_date DESC";
if ($params) {
    $stmt = $db->con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $users[] = $row;
    $stmt->close();
} else {
    $result = $db->con->query($sql);
    while ($row = $result->fetch_assoc()) $users[] = $row;
}

$total_users   = $db->con->query("SELECT COUNT(*) as c FROM user")->fetch_assoc()['c'];
$total_admins  = $db->con->query("SELECT COUNT(*) as c FROM user WHERE role IN ('admin','superadmin')")->fetch_assoc()['c'];
$total_sellers = $db->con->query("SELECT COUNT(*) as c FROM user WHERE role='seller'")->fetch_assoc()['c'];
$total_customers = $db->con->query("SELECT COUNT(*) as c FROM user WHERE role='customer'")->fetch_assoc()['c'];
$this_month    = $db->con->query("SELECT COUNT(*) as c FROM user WHERE register_date >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<?php $active_page = 'users'; include '_sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-users me-2"></i>Manage Users
        </h5>
        <span class="text-muted" style="font-size:13px;">
            <?php echo $total_users; ?> users &nbsp;·&nbsp;
            <span class="text-danger"><?php echo $total_admins; ?> admin(s)</span>
        </span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i><?php echo $success; ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px;">
        <i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo $db->con->query("SELECT COUNT(*) as c FROM user")->fetch_assoc()['c']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#dc3545;">
                <div class="stat-label">Admins</div>
                <div class="stat-value"><?php echo $total_admins; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#28a745;">
                <div class="stat-label">Customers</div>
                <div class="stat-value"><?php echo $total_users - $total_admins; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#ffc107;">
                <div class="stat-label">This Month</div>
                <div class="stat-value"><?php
                    $m = date('Y-m');
                    echo $db->con->query("SELECT COUNT(*) as c FROM user WHERE register_date LIKE '{$m}%'")->fetch_assoc()['c'];
                ?></div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-3 shadow-sm p-3 mb-3">
        <form method="get" class="d-flex gap-2 align-items-center">
            <div class="position-relative flex-grow-1" style="max-width:360px;">
                <i class="fas fa-search position-absolute" style="left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:12px;"></i>
                <input type="text" name="q" class="form-control" style="padding-left:32px;font-size:13px;"
                       placeholder="Search by name or email..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-3">Search</button>
            <?php if ($search): ?><a href="users.php" class="btn btn-outline-secondary btn-sm">Clear</a><?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="admin-table">
        <div style="overflow-x:auto;">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $order_count = $db->con->query("SELECT COUNT(*) as c FROM orders WHERE user_id={$u['user_id']}")->fetch_assoc()['c'];
                    $is_me = $u['user_id'] == $_SESSION['user_id'];
                ?>
                <tr>
                    <td style="font-size:12px;color:#888;"><?php echo $u['user_id']; ?></td>
                    <td>
                        <div style="font-weight:600;font-size:13px;">
                            <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                            <?php if ($is_me): ?><span class="badge bg-info ms-1" style="font-size:9px;">You</span><?php endif; ?>
                        </div>
                    </td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td style="font-size:13px;color:#666;">
                        <?php echo $u['phone'] ? htmlspecialchars($u['phone']) : '<span class="text-muted">—</span>'; ?>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge bg-danger px-2 py-1">Admin</span>
                        <?php elseif ($u['role'] === 'superadmin'): ?>
                        <span class="badge bg-danger px-2 py-1"><i class="fas fa-crown me-1"></i>Super Admin</span>
                        <?php elseif ($u['role'] === 'seller'): ?>
                        <span class="badge bg-success px-2 py-1">Seller</span>
                        <?php else: ?>
                        <span class="badge bg-secondary px-2 py-1">Customer</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($u['register_date'])); ?></td>
                    <td>
                        <?php if ($order_count > 0): ?>
                        <span class="badge bg-primary"><?php echo $order_count; ?></span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Change role -->
                        <?php if (!$is_me && $u['role'] !== 'superadmin'): ?>
                        <form method="post" class="d-inline d-flex gap-1 align-items-center">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <select name="new_role" class="form-select form-select-sm" style="width:auto;font-size:12px;">
                                <option value="customer" <?php echo $u['role']==='customer'?'selected':''; ?>>Customer</option>
                                <option value="seller"   <?php echo $u['role']==='seller'  ?'selected':''; ?>>Seller</option>
                                <?php if ($auth->isSuperAdmin()): ?>
                                <option value="admin"    <?php echo $u['role']==='admin'   ?'selected':''; ?>>Admin</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" name="toggle_role" class="btn btn-sm btn-outline-primary" title="Update role">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <!-- Delete -->
                        <?php if (!$is_me && $u['role'] !== 'superadmin'): ?>
                        <form method="post" class="d-inline ms-1" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php elseif ($is_me): ?>
                        <span class="text-muted" style="font-size:12px;">You</span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

