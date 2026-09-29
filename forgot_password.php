<?php
$page_title = 'Forgot Password';
ob_start();
include 'header.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset'])) {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $stmt = $db->con->prepare("SELECT user_id, first_name FROM user WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Don't reveal if email exists — show same success message for security
            $success = 'If that email is registered, a reset link has been sent.';
        } else {
            $user = $result->fetch_assoc();
            $stmt->close();

            // Generate secure token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in DB (create table if needed)
            $db->con->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT NOW(),
                UNIQUE KEY unique_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Delete old tokens for this user
            $del = $db->con->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user['user_id']);
            $del->execute();
            $del->close();

            // Insert new token
            $ins = $db->con->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user['user_id'], $token, $expires);
            $ins->execute();
            $ins->close();

            // Build reset link
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base_url  = $protocol . '://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $reset_url = rtrim($base_url, '/') . '/reset_password.php?token=' . $token;

            // Send email
            $to      = $email;
            $subject = 'Password Reset — Isa & Dagi Mobile Shop';
            $message = "Hi {$user['first_name']},\n\n"
                     . "You requested a password reset for your Isa & Dagi Mobile Shop account.\n\n"
                     . "Click the link below to reset your password (valid for 1 hour):\n\n"
                     . $reset_url . "\n\n"
                     . "If you did not request this, please ignore this email.\n\n"
                     . "— Isa & Dagi Mobile Shop\n"
                     . "Merkato, Samson Building, Addis Ababa";
            require_once 'mailer.php';
            sendMail($to, $subject, $message);
            $success = 'If that email is registered, a reset link has been sent. Check your inbox.';
        }
    }
}
?>

<section class="py-5" style="background:#f8f9fa; min-height:80vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="bg-white rounded-3 shadow-sm p-5">

                <!-- Icon -->
                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="fas fa-key" style="font-size:26px;color:#fff;"></i>
                    </div>
                    <h4 style="font-family:'Rubik',sans-serif;font-weight:700;color:var(--primary);">Forgot Password?</h4>
                    <p class="text-muted" style="font-size:14px;">Enter your email and we'll send you a reset link.</p>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius:10px;">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary-custom px-4">Back to Home</a>
                </div>
                <?php else: ?>

                <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:10px; font-size:13px;">
                    <i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-4">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Email Address</label>
                        <input type="email" name="email" class="form-control profile-input"
                               placeholder="your@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required autofocus>
                    </div>
                    <button type="submit" name="send_reset" class="btn btn-primary-custom w-100 py-2">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="#" id="openAuthModal" style="font-size:13px;color:var(--secondary);">
                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                    </a>
                </div>
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
</style>

<?php include 'footer.php'; ?>
