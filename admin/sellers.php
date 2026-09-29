<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

$success = '';
$error   = '';
$is_super = $auth->isSuperAdmin();

// ── Add new seller ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_seller'])) {
    $fn    = trim($_POST['first_name'] ?? '');
    $ln    = trim($_POST['last_name']  ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $pw    = trim($_POST['password'] ?? '');
    $role  = $_POST['role'] ?? 'seller';

    // Only superadmin can create admin accounts
    if ($role === 'admin' && !$is_super) {
        $error = 'Only the Super Admin can create Admin accounts.';
    } elseif ($role === 'superadmin') {
        $error = 'Cannot create another Super Admin account.';
    } elseif (empty($fn) || empty($email) || empty($pw)) {
        $error = 'First name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check duplicate email
        $chk = $db->con->prepare("SELECT user_id FROM user WHERE email = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($pw, PASSWORD_BCRYPT);
            $ins = $db->con->prepare(
                "INSERT INTO user (first_name, last_name, email, password, phone, role, register_date)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $ins->bind_param("ssssss", $fn, $ln, $email, $hashed, $phone, $role);
            if ($ins->execute()) {
                $success = ucfirst($role) . ' account created for ' . htmlspecialchars($fn) . '.';
            } else {
                $error = 'Failed to create account. Please try again.';
            }
            $ins->close();
        }
        $chk->close();
    }
}

// ── Edit seller ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_seller'])) {
    $uid   = (int)$_POST['user_id'];
    $fn    = trim($_POST['first_name'] ?? '');
    $ln    = trim($_POST['last_name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role  = $_POST['role'] ?? 'seller';
    $pw    = trim($_POST['password'] ?? '');

    // Protect superadmin from being demoted by non-superadmin
    $target = $db->con->query("SELECT role FROM user WHERE user_id=$uid LIMIT 1")->fetch_assoc();
    if ($target['role'] === 'superadmin' && !$is_super) {
        $error = 'You cannot edit the Super Admin account.';
    } elseif ($role === 'superadmin') {
        $error = 'Cannot assign Super Admin role.';
    } elseif ($role === 'admin' && !$is_super) {
        $error = 'Only the Super Admin can assign Admin role.';
    } else {
        if (!empty($pw) && strlen($pw) >= 6) {
            $hashed = password_hash($pw, PASSWORD_BCRYPT);
            $upd = $db->con->prepare("UPDATE user SET first_name=?, last_name=?, phone=?, role=?, password=? WHERE user_id=?");
            $upd->bind_param("sssssi", $fn, $ln, $phone, $role, $hashed, $uid);
        } else {
            $upd = $db->con->prepare("UPDATE user SET first_name=?, last_name=?, phone=?, role=? WHERE user_id=?");
            $upd->bind_param("ssssi", $fn, $ln, $phone, $role, $uid);
        }
        $upd->execute();
        $upd->close();
        $success = 'Account updated successfully.';
    }
}

// ── Delete seller/admin ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_seller'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $target = $db->con->query("SELECT role FROM user WHERE user_id=$uid LIMIT 1")->fetch_assoc();
        if ($target['role'] === 'superadmin') {
            $error = 'The Super Admin account cannot be deleted.';
        } elseif ($target['role'] === 'admin' && !$is_super) {
            $error = 'Only the Super Admin can delete Admin accounts.';
        } else {
            $db->con->query("DELETE FROM user WHERE user_id=$uid");
            $success = 'Account removed.';
        }
    }
}

// ── Fetch staff (sellers + admins + superadmin) ──────────────────────────────
$staff = [];
$res = $db->con->query(
    "SELECT * FROM user WHERE role IN ('seller','admin','superadmin') ORDER BY
     FIELD(role,'superadmin','admin','seller'), first_name ASC"
);
while ($row = $res->fetch_assoc()) $staff[] = $row;

$total_sellers = count(array_filter($staff, fn($u) => $u['role'] === 'seller'));
$total_admins  = count(array_filter($staff, fn($u) => $u['role'] === 'admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sellers & Admins — Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<?php $active_page = 'sellers'; include '_sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-user-tag me-2"></i>Sellers &amp; Admins
        </h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="fas fa-plus me-1"></i>Add Staff
        </button>
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
            <div class="stat-card" style="border-left-color:#dc3545;">
                <div class="stat-label">Super Admin</div>
                <div class="stat-value">1</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#fd7e14;">
                <div class="stat-label">Admins</div>
                <div class="stat-value"><?php echo $total_admins; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#28a745;">
                <div class="stat-label">Sellers</div>
                <div class="stat-value"><?php echo $total_sellers; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#00A5C4;">
                <div class="stat-label">Total Staff</div>
                <div class="stat-value"><?php echo count($staff); ?></div>
            </div>
        </div>
    </div>

    <!-- Role permissions info -->
    <div class="bg-white rounded-3 shadow-sm p-3 mb-4">
        <h6 class="fw-bold mb-3" style="font-size:13px;color:#555;"><i class="fas fa-info-circle me-1 text-primary"></i>Role Permissions</h6>
        <div class="row g-2" style="font-size:12px;">
            <div class="col-md-4">
                <div class="p-2 rounded-2" style="background:#fff5f5;border:1px solid #ffd0d0;">
                    <strong style="color:#dc3545;"><i class="fas fa-crown me-1"></i>Super Admin</strong>
                    <div class="text-muted mt-1">Full access · Manage all admins &amp; sellers · All settings</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 rounded-2" style="background:#fff8f0;border:1px solid #ffd9a0;">
                    <strong style="color:#fd7e14;"><i class="fas fa-user-shield me-1"></i>Admin</strong>
                    <div class="text-muted mt-1">Products · Orders · Blog · Banners · Reviews · Users · Sellers</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 rounded-2" style="background:#f0fff4;border:1px solid #a0ddb0;">
                    <strong style="color:#28a745;"><i class="fas fa-user-tag me-1"></i>Seller</strong>
                    <div class="text-muted mt-1">Products · Accessories · Orders only</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="admin-table">
        <div class="p-3 border-bottom">
            <h6 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">Staff Accounts</h6>
        </div>
        <div style="overflow-x:auto;">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Since</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $u):
                    $is_me = $u['user_id'] == $_SESSION['user_id'];
                    $role_colors = ['superadmin'=>'danger','admin'=>'warning','seller'=>'success'];
                    $rc = $role_colors[$u['role']] ?? 'secondary';
                    $role_labels2 = ['superadmin'=>'Super Admin','admin'=>'Admin','seller'=>'Seller'];
                    $rl = $role_labels2[$u['role']] ?? ucfirst($u['role']);
                ?>
                <tr>
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
                        <span class="badge bg-<?php echo $rc; ?> px-2 py-1">
                            <?php if ($u['role']==='superadmin'): ?><i class="fas fa-crown me-1"></i><?php endif; ?>
                            <?php echo $rl; ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($u['register_date'])); ?></td>
                    <td>
                        <?php
                        $can_edit = !$is_me && (
                            $is_super ||
                            ($auth->isAdmin() && $u['role'] === 'seller')
                        );
                        ?>
                        <?php if ($can_edit): ?>
                        <!-- Edit -->
                        <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal" data-bs-target="#editStaffModal"
                                data-id="<?php echo $u['user_id']; ?>"
                                data-fn="<?php echo htmlspecialchars($u['first_name']); ?>"
                                data-ln="<?php echo htmlspecialchars($u['last_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($u['phone'] ?? ''); ?>"
                                data-role="<?php echo $u['role']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <!-- Delete -->
                        <form method="post" class="d-inline" onsubmit="return confirm('Remove this staff account?')">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <button type="submit" name="delete_seller" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php elseif ($is_me): ?>
                        <span class="text-muted" style="font-size:12px;">Your account</span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($staff)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No staff accounts yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ── Add Staff Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family:'Rubik',sans-serif;">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Add Staff Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Last Name</label>
                            <input type="text" name="last_name" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Phone</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+251 9XX XXX XXX">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Role *</label>
                            <select name="role" class="form-select">
                                <option value="seller">Seller — Products &amp; Orders only</option>
                                <?php if ($is_super): ?>
                                <option value="admin">Admin — Full access (except Super Admin)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Password *</label>
                            <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_seller" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Edit Staff Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-family:'Rubik',sans-serif;">
                    <i class="fas fa-user-edit me-2 text-warning"></i>Edit Staff Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">First Name *</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Last Name</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Phone</label>
                            <input type="tel" name="phone" id="editPhone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Role</label>
                            <select name="role" id="editRole" class="form-select">
                                <option value="seller">Seller</option>
                                <?php if ($is_super): ?>
                                <option value="admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">New Password <small class="text-muted fw-normal">(leave blank to keep current)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="Min 6 characters">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_seller" class="btn btn-warning text-white">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate edit modal
document.getElementById('editStaffModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('editUserId').value    = btn.dataset.id;
    document.getElementById('editFirstName').value = btn.dataset.fn;
    document.getElementById('editLastName').value  = btn.dataset.ln;
    document.getElementById('editPhone').value     = btn.dataset.phone;
    document.getElementById('editRole').value      = btn.dataset.role;
});
</script>
</body>
</html>
