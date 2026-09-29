<?php
$page_title = 'My Profile';
ob_start();
include 'header.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $current_user_id;
$success = '';
$error   = '';

// Fetch full user from DB
$stmt = $db->con->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- Handle profile update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $email      = trim(strtolower($_POST['email'] ?? ''));

    if (empty($first_name) || empty($email)) {
        $error = 'First name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $chk = $db->con->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
        $chk->bind_param("si", $email, $user_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'This email is already used by another account.';
        } else {
            $stmt = $db->con->prepare(
                "UPDATE user SET first_name=?, last_name=?, email=?, phone=? WHERE user_id=?"
            );
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            if ($stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name']  = $last_name;
                $_SESSION['email']      = $email;
                $user['first_name'] = $first_name;
                $user['last_name']  = $last_name;
                $user['email']      = $email;
                $user['phone']      = $phone;
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Update failed. Please try again.';
            }
            $stmt->close();
        }
        $chk->close();
    }
}

// ---- Handle avatar upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $size    = $_FILES['avatar']['size'];
        if (!in_array($ext, $allowed)) {
            $error = 'Invalid file type. Use JPG, PNG, or WebP.';
        } elseif ($size > 2 * 1024 * 1024) {
            $error = 'Avatar must be under 2MB.';
        } else {
            $fname = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $dest  = 'assets/products/' . $fname;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                // Delete old avatar file if it exists
                if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                    @unlink($user['avatar']);
                }
                $avatar_path = 'assets/products/' . $fname;
                $stmt = $db->con->prepare("UPDATE user SET avatar=? WHERE user_id=?");
                $stmt->bind_param("si", $avatar_path, $user_id);
                $stmt->execute();
                $stmt->close();
                $user['avatar'] = $avatar_path;
                $success = 'Profile photo updated!';
            } else {
                $error = 'Upload failed. Please try again.';
            }
        }
    } else {
        $error = 'Please choose a photo to upload.';
    }
}

// ---- Handle remove avatar ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_avatar'])) {
    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
        @unlink($user['avatar']);
    }
    $db->con->query("UPDATE user SET avatar=NULL WHERE user_id=$user_id");
    $user['avatar'] = null;
    $success = 'Profile photo removed.';
}

// ---- Handle address update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $address = trim($_POST['default_address'] ?? '');
    $city    = trim($_POST['default_city']    ?? '');
    // Store in settings-like user meta — we'll use the user table with extra columns
    // Add columns if not exist (safe)
    $db->con->query("ALTER TABLE user ADD COLUMN IF NOT EXISTS default_address TEXT DEFAULT NULL");
    $db->con->query("ALTER TABLE user ADD COLUMN IF NOT EXISTS default_city VARCHAR(100) DEFAULT NULL");
    $stmt = $db->con->prepare("UPDATE user SET default_address=?, default_city=? WHERE user_id=?");
    $stmt->bind_param("ssi", $address, $city, $user_id);
    $stmt->execute();
    $stmt->close();
    $user['default_address'] = $address;
    $user['default_city']    = $city;
    $success = 'Default address saved!';
}

// ---- Handle password change ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'] ?? '';
    $new_pw     = $_POST['new_password']     ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
        $error = 'All password fields are required.';
    } elseif (!password_verify($current_pw, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_pw) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm_pw) {
        $error = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
        $stmt   = $db->con->prepare("UPDATE user SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Password change failed. Please try again.';
        }
        $stmt->close();
    }
}

// ---- Handle account settings ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Currency preference
    $currency = in_array($_POST['currency'] ?? '', ['USD','ETB']) ? $_POST['currency'] : 'USD';
    $_SESSION['currency'] = $currency;

    // Newsletter subscription
    $newsletter = isset($_POST['newsletter_subscribe']) ? 1 : 0;
    if ($newsletter) {
        $em = mysqli_real_escape_string($db->con, $user['email']);
        $db->con->query("INSERT IGNORE INTO newsletter (email) VALUES ('{$em}')");
    } else {
        $em = mysqli_real_escape_string($db->con, $user['email']);
        $db->con->query("DELETE FROM newsletter WHERE email='{$em}'");
    }
    $success = 'Settings saved!';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirm_pw = $_POST['delete_confirm_password'] ?? '';
    if (!password_verify($confirm_pw, $user['password'])) {
        $error = 'Incorrect password. Account not deleted.';
    } else {
        // Delete user data
        $db->con->query("DELETE FROM cart WHERE user_id=$user_id");
        $db->con->query("DELETE FROM wishlist WHERE user_id=$user_id");
        $db->con->query("DELETE FROM reviews WHERE user_id=$user_id");
        $db->con->query("DELETE FROM password_resets WHERE user_id=$user_id");
        if (!empty($user['avatar']) && file_exists($user['avatar'])) @unlink($user['avatar']);
        $db->con->query("DELETE FROM user WHERE user_id=$user_id");
        $auth->logout();
        header('Location: index.php?msg=account_deleted');
        exit;
    }
}

// Fetch address columns safely
$addr_res = $db->con->query("SHOW COLUMNS FROM user LIKE 'default_address'");
if ($addr_res && $addr_res->num_rows > 0) {
    $addr_row = $db->con->query("SELECT default_address, default_city FROM user WHERE user_id=$user_id")->fetch_assoc();
    $user['default_address'] = $addr_row['default_address'] ?? '';
    $user['default_city']    = $addr_row['default_city']    ?? '';
} else {
    $user['default_address'] = '';
    $user['default_city']    = '';
}

// Order stats
$order_count = $db->con->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$user_id")->fetch_assoc()['c'];
$total_spent = $db->con->query("SELECT COALESCE(SUM(total_amount),0) as t FROM orders WHERE user_id=$user_id AND status != 'cancelled'")->fetch_assoc()['t'];
$wish_count  = $Cart->countWishlist($user_id);

// Check newsletter subscription
$is_subscribed = false;
$nl = $db->con->prepare("SELECT id FROM newsletter WHERE email=? LIMIT 1");
$nl->bind_param("s", $user['email']);
$nl->execute(); $nl->store_result();
$is_subscribed = $nl->num_rows > 0;
$nl->close();

// Current currency preference
$user_currency = $_SESSION['currency'] ?? 'USD';
$total_spent = $db->con->query("SELECT COALESCE(SUM(total_amount),0) as t FROM orders WHERE user_id=$user_id AND status != 'cancelled'")->fetch_assoc()['t'];
$wish_count  = $Cart->countWishlist($user_id);

// Avatar display helper
$avatar_src = (!empty($user['avatar']) && file_exists($user['avatar']))
    ? $user['avatar']
    : null;
$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'] ?: $user['first_name'],0,1));
?>

<section class="py-5" style="background:#f8f9fa; min-height:80vh;">
<div class="container">

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" style="border-radius:10px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Left: Avatar + Stats -->
        <div class="col-lg-3">

            <!-- Avatar card -->
            <div class="bg-white rounded-3 shadow-sm p-4 text-center mb-4">
                <!-- Avatar display -->
                <div class="position-relative d-inline-block mb-3">
                    <?php if ($avatar_src): ?>
                    <img src="<?php echo htmlspecialchars($avatar_src); ?>" id="avatarPreview"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--secondary);"
                         alt="Avatar">
                    <?php else: ?>
                    <div class="profile-avatar mx-auto" id="avatarInitials"><?php echo $initials; ?></div>
                    <?php endif; ?>
                    <!-- Camera icon overlay -->
                    <label for="avatarInput" style="position:absolute;bottom:0;right:0;background:var(--secondary);color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;" title="Change photo">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>

                <!-- Avatar upload form -->
                <form method="post" enctype="multipart/form-data" id="avatarForm">
                    <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none"
                           onchange="document.getElementById('avatarForm').submit();">
                    <?php if ($avatar_src): ?>
                    <button type="submit" name="remove_avatar" class="btn btn-sm btn-outline-danger mb-2" style="font-size:11px;">
                        <i class="fas fa-trash me-1"></i>Remove Photo
                    </button>
                    <?php endif; ?>
                </form>

                <h5 style="font-family:'Rubik',sans-serif; font-weight:700; margin-bottom:2px;">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                </h5>
                <p class="text-muted mb-2" style="font-size:13px;"><?php echo htmlspecialchars($user['email']); ?></p>
                <?php
                $role_colors = ['superadmin'=>'danger','admin'=>'danger','seller'=>'success','customer'=>'primary'];
                $rc = $role_colors[$user['role']] ?? 'primary';
                ?>
                <span class="badge bg-<?php echo $rc; ?> px-3 py-1" style="font-size:11px; border-radius:20px;">
                    <?php echo $user['role']==='superadmin' ? 'Super Admin' : ucfirst($user['role']); ?>
                </span>
                <hr>
                <p class="text-muted mb-0" style="font-size:11px;">
                    <i class="fas fa-calendar me-1"></i>
                    Member since <?php echo date('M Y', strtotime($user['register_date'])); ?>
                </p>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-3 shadow-sm p-3">
                <h6 style="font-family:'Rubik',sans-serif; font-weight:600; color:var(--primary); margin-bottom:14px;">
                    <i class="fas fa-chart-bar me-2"></i>My Stats
                </h6>
                <div class="profile-stat-row">
                    <span><i class="fas fa-shopping-bag text-primary me-2"></i>Total Orders</span>
                    <strong><?php echo $order_count; ?></strong>
                </div>
                <div class="profile-stat-row">
                    <span><i class="fas fa-dollar-sign text-success me-2"></i>Total Spent</span>
                    <strong class="text-danger"><?php echo formatPrice($total_spent); ?></strong>
                </div>
                <div class="profile-stat-row">
                    <span><i class="fas fa-heart text-danger me-2"></i>Wishlist</span>
                    <strong><?php echo $wish_count; ?> items</strong>
                </div>
                <hr class="my-2">
                <a href="orders.php" class="btn btn-outline-primary w-100 btn-sm mt-1">
                    <i class="fas fa-box me-1"></i>View My Orders
                </a>
                <a href="cart.php" class="btn btn-outline-danger w-100 btn-sm mt-2">
                    <i class="fas fa-heart me-1"></i>View Wishlist
                </a>
            </div>

        </div>

        <!-- Right: Edit forms -->
        <div class="col-lg-9">

            <!-- Tabs — scrollable on mobile -->
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <ul class="nav nav-tabs profile-tabs mb-4 flex-nowrap" id="profileTabs" style="min-width:max-content;">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-info">
                        <i class="fas fa-user me-2"></i>Personal Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-address">
                        <i class="fas fa-map-marker-alt me-2"></i>Address
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-password">
                        <i class="fas fa-lock me-2"></i>Password
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-settings">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" data-bs-toggle="tab" href="#tab-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Account
                    </a>
                </li>
            </ul>
            </div><!-- end scrollable tabs wrapper -->

            <div class="tab-content">

                <!-- Tab 1: Personal Info -->
                <div class="tab-pane fade show active" id="tab-info">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h5 style="font-family:'Rubik',sans-serif; font-weight:600; color:var(--primary); margin-bottom:20px;">
                            <i class="fas fa-user-edit me-2"></i>Edit Personal Information
                        </h5>
                        <form method="post">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label profile-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control profile-input"
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label profile-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control profile-input"
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label profile-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control profile-input"
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label profile-label">Phone Number</label>
                                    <input type="text" name="phone" class="form-control profile-input"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           placeholder="+251 9XX XXX XXX">
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary-custom px-5">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab 2: Default Address -->
                <div class="tab-pane fade" id="tab-address">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h5 style="font-family:'Rubik',sans-serif; font-weight:600; color:var(--primary); margin-bottom:8px;">
                            <i class="fas fa-map-marker-alt me-2"></i>Default Delivery Address
                        </h5>
                        <p class="text-muted mb-4" style="font-size:13px;">
                            This address will be pre-filled automatically when you checkout.
                        </p>
                        <form method="post">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label profile-label">Street Address / Area</label>
                                    <input type="text" name="default_address" class="form-control profile-input"
                                           value="<?php echo htmlspecialchars($user['default_address'] ?? ''); ?>"
                                           placeholder="e.g. Bole, near Edna Mall, House No. 123">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label profile-label">City</label>
                                    <input type="text" name="default_city" class="form-control profile-input"
                                           value="<?php echo htmlspecialchars($user['default_city'] ?? ''); ?>"
                                           placeholder="e.g. Addis Ababa">
                                </div>
                                <div class="col-12 mt-2">
                                    <button type="submit" name="update_address" class="btn btn-primary-custom px-5">
                                        <i class="fas fa-save me-2"></i>Save Address
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab 3: Change Password -->
                <div class="tab-pane fade" id="tab-password">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h5 style="font-family:'Rubik',sans-serif; font-weight:600; color:var(--primary); margin-bottom:20px;">
                            <i class="fas fa-key me-2"></i>Change Password
                        </h5>
                        <form method="post" style="max-width:480px;">
                            <div class="mb-3">
                                <label class="form-label profile-label">Current Password *</label>
                                <div class="position-relative">
                                    <input type="password" name="current_password" id="cur_pw"
                                           class="form-control profile-input" placeholder="Enter current password">
                                    <button type="button" class="pw-eye-btn" onclick="togglePw('cur_pw', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label profile-label">New Password *</label>
                                <div class="position-relative">
                                    <input type="password" name="new_password" id="new_pw"
                                           class="form-control profile-input" placeholder="At least 6 characters">
                                    <button type="button" class="pw-eye-btn" onclick="togglePw('new_pw', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <!-- Password strength -->
                                <div class="mt-1">
                                    <div style="height:3px;background:#eee;border-radius:3px;overflow:hidden;">
                                        <div id="profilePwBar" style="height:100%;width:0;border-radius:3px;transition:width 0.3s,background 0.3s;"></div>
                                    </div>
                                    <small id="profilePwText" style="font-size:11px;color:#888;"></small>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label profile-label">Confirm New Password *</label>
                                <div class="position-relative">
                                    <input type="password" name="confirm_password" id="con_pw"
                                           class="form-control profile-input" placeholder="Repeat new password">
                                    <button type="button" class="pw-eye-btn" onclick="togglePw('con_pw', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-secondary-custom px-5">
                                <i class="fas fa-lock me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tab 4: Settings -->
                <div class="tab-pane fade" id="tab-settings">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h5 style="font-family:'Rubik',sans-serif;font-weight:600;color:var(--primary);margin-bottom:20px;">
                            <i class="fas fa-cog me-2"></i>Account Settings
                        </h5>
                        <form method="post">

                            <!-- Currency -->
                            <div class="mb-4 pb-4 border-bottom">
                                <h6 class="fw-bold mb-1" style="font-size:14px;color:#333;">
                                    <i class="fas fa-exchange-alt me-2 text-primary"></i>Currency Preference
                                </h6>
                                <p class="text-muted mb-3" style="font-size:13px;">Choose which currency prices are shown in across the shop.</p>
                                <div class="d-flex gap-3">
                                    <label class="d-flex align-items-center gap-2 p-3 rounded-2 border cursor-pointer"
                                           style="cursor:pointer;min-width:130px;<?php echo $user_currency==='USD'?'border-color:var(--secondary)!important;background:#f0fbff;':''; ?>">
                                        <input type="radio" name="currency" value="USD"
                                               <?php echo $user_currency==='USD'?'checked':''; ?>
                                               style="accent-color:var(--secondary);">
                                        <div>
                                            <div class="fw-bold" style="font-size:14px;">USD</div>
                                            <div class="text-muted" style="font-size:11px;">US Dollar</div>
                                        </div>
                                    </label>
                                    <label class="d-flex align-items-center gap-2 p-3 rounded-2 border cursor-pointer"
                                           style="cursor:pointer;min-width:130px;<?php echo $user_currency==='ETB'?'border-color:var(--secondary)!important;background:#f0fbff;':''; ?>">
                                        <input type="radio" name="currency" value="ETB"
                                               <?php echo $user_currency==='ETB'?'checked':''; ?>
                                               style="accent-color:var(--secondary);">
                                        <div>
                                            <div class="fw-bold" style="font-size:14px;">ETB</div>
                                            <div class="text-muted" style="font-size:11px;">Ethiopian Birr</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Newsletter -->
                            <div class="mb-4 pb-4 border-bottom">
                                <h6 class="fw-bold mb-1" style="font-size:14px;color:#333;">
                                    <i class="fas fa-envelope-open-text me-2 text-primary"></i>Newsletter & Promotions
                                </h6>
                                <p class="text-muted mb-3" style="font-size:13px;">Receive price alerts, new arrivals, and exclusive deals by email.</p>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="newsletter_subscribe"
                                           id="newsletterToggle" role="switch"
                                           <?php echo $is_subscribed ? 'checked' : ''; ?>
                                           style="width:44px;height:22px;cursor:pointer;accent-color:var(--secondary);">
                                    <label class="form-check-label ms-2" for="newsletterToggle" style="font-size:13px;font-weight:600;">
                                        <?php echo $is_subscribed
                                            ? '<span style="color:#28a745;">✓ Subscribed — you\'ll receive our latest deals</span>'
                                            : '<span style="color:#888;">Not subscribed</span>'; ?>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" name="update_settings" class="btn btn-primary-custom px-5">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tab 5: Danger Zone -->
                <div class="tab-pane fade" id="tab-danger">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <h5 style="font-family:'Rubik',sans-serif; font-weight:600; color:#dc3545; margin-bottom:8px;">
                            <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                        </h5>
                        <p class="text-muted mb-4" style="font-size:13px;">
                            These actions are permanent and cannot be undone.
                        </p>

                        <!-- Delete account -->
                        <div class="border border-danger rounded-3 p-4" style="background:#fff5f5;">
                            <h6 class="fw-bold text-danger mb-1"><i class="fas fa-user-times me-2"></i>Delete My Account</h6>
                            <p class="text-muted mb-3" style="font-size:13px;">
                                This will permanently delete your account, cart, wishlist, and reviews.
                                Your order history will be kept for our records. This cannot be undone.
                            </p>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#deleteAccountForm">
                                <i class="fas fa-trash me-1"></i>I want to delete my account
                            </button>
                            <div class="collapse mt-3" id="deleteAccountForm">
                                <form method="post" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">
                                    <label class="form-label profile-label">Enter your password to confirm</label>
                                    <div class="position-relative mb-3" style="max-width:320px;">
                                        <input type="password" name="delete_confirm_password" id="del_pw"
                                               class="form-control profile-input" placeholder="Your current password" required>
                                        <button type="button" class="pw-eye-btn" onclick="togglePw('del_pw', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <button type="submit" name="delete_account" class="btn btn-danger px-4">
                                        <i class="fas fa-trash me-2"></i>Permanently Delete Account
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</section>

<style>
.profile-avatar {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Rubik', sans-serif;
    font-size: 28px; font-weight: 700;
}
.profile-stat-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px;
}
.profile-stat-row:last-of-type { border-bottom: none; }
.profile-tabs .nav-link {
    font-family: 'Rubik', sans-serif; font-size: 14px; font-weight: 500;
    color: #666; border: none; border-bottom: 2px solid transparent;
    padding: 10px 20px; border-radius: 0;
}
.profile-tabs .nav-link.active {
    color: var(--primary); border-bottom-color: var(--secondary);
    background: none; font-weight: 600;
}
.profile-label { font-size: 13px; font-weight: 600; color: #444; }
.profile-input {
    border: 1.5px solid #ddd; border-radius: 8px;
    padding: 10px 14px; font-size: 14px;
    transition: border-color 0.2s;
}
.profile-input:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(0,165,196,0.1); }
.pw-eye-btn {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #888; cursor: pointer; font-size: 14px;
}
.pw-eye-btn:hover { color: var(--primary); }
</style>

<script>
function togglePw(id, btn) {
    var input = document.getElementById(id);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Password strength meter
document.getElementById('new_pw').addEventListener('input', function() {
    var pw  = this.value;
    var str = 0;
    if (pw.length >= 6)           str++;
    if (pw.length >= 10)          str++;
    if (/[A-Z]/.test(pw))         str++;
    if (/[0-9]/.test(pw))         str++;
    if (/[^A-Za-z0-9]/.test(pw))  str++;
    var colors = ['','#dc3545','#fd7e14','#ffc107','#28a745','#20c997'];
    var labels = ['','Weak','Fair','Good','Strong','Very Strong'];
    document.getElementById('profilePwBar').style.width    = (str > 0 ? (str/5*100) : 0) + '%';
    document.getElementById('profilePwBar').style.background = colors[str] || '#eee';
    document.getElementById('profilePwText').textContent   = str > 0 ? labels[str] : '';
    document.getElementById('profilePwText').style.color   = colors[str] || '#888';
});

// Auto-open correct tab based on which form was submitted
<?php if ($error && isset($_POST['change_password'])): ?>
document.querySelector('[href="#tab-password"]').click();
<?php elseif ($error && isset($_POST['update_address'])): ?>
document.querySelector('[href="#tab-address"]').click();
<?php elseif ($error && isset($_POST['delete_account'])): ?>
document.querySelector('[href="#tab-danger"]').click();
<?php elseif ($success && isset($_POST['update_settings'])): ?>
document.querySelector('[href="#tab-settings"]').click();
<?php elseif ($success && isset($_POST['update_address'])): ?>
document.querySelector('[href="#tab-address"]').click();
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
