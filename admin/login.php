<?php
require_once '../functions.php';

// Already logged in as admin/seller — go to dashboard
if ($auth->canAccessAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle login form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->login(
        $_POST['email']    ?? '',
        $_POST['password'] ?? ''
    );
    if ($result['success']) {
        if ($auth->canAccessAdmin()) {
            header('Location: index.php');
            exit;
        } else {
            $auth->logout();
            $error = 'This account does not have staff access.';
        }
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Isa & Dagi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Rubik:wght@500;700;800&display=swap');

        body {
            background:
                radial-gradient(ellipse at 0% 0%,   rgba(0,165,196,0.22) 0%, transparent 55%),
                radial-gradient(ellipse at 100% 100%,rgba(155,89,247,0.18) 0%, transparent 55%),
                radial-gradient(ellipse at 100% 0%,  rgba(79,142,247,0.15) 0%, transparent 55%),
                linear-gradient(160deg, #0d1b2a 0%, #112233 50%, #0a1628 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px;
            padding: 42px 38px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo .icon {
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #003859, #00A5C4);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            box-shadow: 0 8px 28px rgba(0,165,196,0.45);
        }
        .login-logo .icon i { color: #fff; font-size: 28px; }
        .login-logo h4 { font-weight: 800; color: #fff; margin: 0; font-family: 'Rubik', sans-serif; font-size: 22px; }
        .login-logo p  { color: rgba(255,255,255,0.55); font-size: 13px; margin: 5px 0 0; }
        .form-label { color: rgba(255,255,255,0.75); font-size: 12.5px; font-weight: 600; }
        .form-control {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1.5px solid rgba(255,255,255,0.15);
            font-size: 14px;
            background: rgba(255,255,255,0.07);
            color: #fff;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .form-control:focus {
            border-color: #00A5C4;
            box-shadow: 0 0 0 3px rgba(0,165,196,0.2);
            background: rgba(255,255,255,0.10);
            color: #fff;
            outline: none;
        }
        .btn-login {
            background: linear-gradient(135deg, #003859 0%, #0077a8 50%, #00A5C4 100%);
            color: #fff;
            border: none;
            padding: 13px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.22s;
            box-shadow: 0 6px 22px rgba(0,165,196,0.4);
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,165,196,0.55);
            color: #fff;
        }
        .back-link { text-align: center; margin-top: 18px; font-size: 13px; color: rgba(255,255,255,0.5); }
        .back-link a { color: #00A5C4; text-decoration: none; font-weight: 600; }
        .back-link a:hover { color: #FFD289; }
        .pw-wrap { position: relative; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: rgba(255,255,255,0.4); cursor: pointer;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: #00A5C4; }
        .alert-danger {
            background: rgba(247,86,124,0.15);
            border: none;
            border-left: 4px solid #f7567c;
            color: #fecdd3;
            border-radius: 10px;
            font-size: 13px;
        }
        /* divider line */
        .login-divider {
            border-color: rgba(255,255,255,0.10);
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <div class="icon"><i class="fas fa-shield-alt"></i></div>
        <h4>Admin Panel</h4>
        <p>Isa &amp; Dagi Mobile Shop</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 px-3" style="font-size:13px;">
        <i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label fw-bold" style="font-size:13px;">Admin Email</label>
            <input type="email" name="email" class="form-control"
                   placeholder="admin@mobileshop.com"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold" style="font-size:13px;">Password</label>
            <div class="pw-wrap">
                <input type="password" name="password" id="adminPwd"
                       class="form-control" placeholder="Enter password" required>
                <button type="button" class="pw-toggle" onclick="
                    var i=document.getElementById('adminPwd');
                    i.type=i.type==='password'?'text':'password';
                    this.innerHTML=i.type==='password'?'<i class=\'fas fa-eye\'></i>':'<i class=\'fas fa-eye-slash\'></i>';
                "><i class="fas fa-eye"></i></button>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>Login to Admin Panel
        </button>
    </form>

    <div class="back-link">
        <a href="../index.php"><i class="fas fa-arrow-left me-1"></i>Back to Shop</a>
        &nbsp;·&nbsp;
        <a href="../forgot_password.php">Forgot password?</a>
    </div>
</div>
</body>
</html>
