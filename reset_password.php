<?php
$page_title = 'Reset Password';
ob_start();
include 'header.php';

$token   = trim($_GET['token'] ?? '');
$success = '';
$error   = '';
$valid   = false;
$user    = null;

// Validate token
if (!empty($token)) {
    $stmt = $db->con->prepare(
        "SELECT pr.*, u.first_name, u.email FROM password_resets pr
         JOIN user u ON pr.user_id = u.user_id
         WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $valid = true;
        $user  = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset']) && $valid) {
    $new_pw     = $_POST['new_password']     ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if (strlen($new_pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm_pw) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
        $uid    = (int)$user['user_id'];

        $upd = $db->con->prepare("UPDATE user SET password=? WHERE user_id=?");
        $upd->bind_param("si", $hashed, $uid);
        $upd->execute();
        $upd->close();

        // Mark token as used
        $mrk = $db->con->prepare("UPDATE password_resets SET used=1 WHERE token=?");
        $mrk->bind_param("s", $token);
        $mrk->execute();
        $mrk->close();

        $success = 'Your password has been reset successfully! You can now log in.';
        $valid   = false;
    }
}
?>

<section class="py-5" style="background:#f8f9fa; min-height:80vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="bg-white rounded-3 shadow-sm p-5">

                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-lock-open" style="font-size:26px;color:#fff;"></i>
                    </div>
                    <h4 style="font-family:'Rubik',sans-serif;font-weight:700;color:var(--primary);">Reset Password</h4>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius:10px;">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
                <div class="text-center mt-3 d-flex gap-3 justify-content-center flex-wrap">
                    <button class="btn btn-primary-custom px-4" id="openAuthModal">
                        <i class="fas fa-user me-2"></i>Customer Login
                    </button>
                    <a href="admin/login.php" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-shield-alt me-2"></i>Admin Login
                    </a>
                </div>

                <?php elseif (!$valid): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2" style="border-radius:10px;">
                    <i class="fas fa-times-circle fa-lg"></i>
                    <div>This reset link is <strong>invalid or has expired</strong>. Please request a new one.</div>
                </div>
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="btn btn-primary-custom px-4">Request New Link</a>
                </div>

                <?php else: ?>
                <p class="text-muted text-center mb-4" style="font-size:14px;">
                    Hi <strong><?php echo htmlspecialchars($user['first_name']); ?></strong>, enter your new password below.
                </p>

                <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:10px; font-size:13px;">
                    <i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">New Password</label>
                        <div class="position-relative">
                            <input type="password" name="new_password" id="new_pw"
                                   class="form-control profile-input" placeholder="At least 6 characters" required>
                            <button type="button" class="pw-eye-btn" onclick="togglePw('new_pw',this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Confirm New Password</label>
                        <div class="position-relative">
                            <input type="password" name="confirm_password" id="con_pw"
                                   class="form-control profile-input" placeholder="Repeat new password" required>
                            <button type="button" class="pw-eye-btn" onclick="togglePw('con_pw',this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" name="do_reset" class="btn btn-primary-custom w-100 py-2">
                        <i class="fas fa-check me-2"></i>Reset Password
                    </button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
</section>

<style>
.profile-input {
    border: 1.5px solid #ddd; border-radius: 8px;
    padding: 11px 14px; font-size: 14px;
    transition: border-color 0.2s;
}
.profile-input:focus { border-color: var(--secondary); box-shadow: 0 0 0 3px rgba(0,165,196,0.1); outline: none; }
.pw-eye-btn {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #888; cursor: pointer; font-size: 14px;
}
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
</script>

<?php include 'footer.php'; ?>
