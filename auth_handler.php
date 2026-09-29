<?php
ob_start();
header('Content-Type: application/json');
require_once 'functions.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'check_email':
        $email = trim(strtolower($_POST['email'] ?? ''));
        $stmt  = $db->con->prepare("SELECT user_id FROM user WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        echo json_encode(['exists' => $exists]);
        break;

    case 'register':
        echo json_encode($auth->register(
            $_POST['first_name'] ?? '',
            $_POST['last_name']  ?? '',
            $_POST['email']      ?? '',
            $_POST['password']   ?? '',
            $_POST['phone']      ?? ''
        ));
        break;
    case 'login':
        echo json_encode($auth->login(
            $_POST['email']    ?? '',
            $_POST['password'] ?? ''
        ));
        break;
    case 'logout':
        $auth->logout();
        echo json_encode(['success' => true]);
        break;

    case 'forgot_password_inline':
        $email = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            break;
        }
        // Always return success to avoid email enumeration
        $stmt = $db->con->prepare("SELECT user_id, first_name FROM user WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt->close();

            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->con->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT NOW(),
                UNIQUE KEY unique_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $del = $db->con->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user['user_id']);
            $del->execute();
            $del->close();

            $ins = $db->con->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user['user_id'], $token, $expires);
            $ins->execute();
            $ins->close();

            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base_url  = $protocol . '://' . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            $reset_url = rtrim($base_url, '/') . '/reset_password.php?token=' . $token;

            $subject = 'Password Reset — Isa & Dagi Mobile Shop';
            $message = "Hi {$user['first_name']},\n\n"
                     . "You requested a password reset for your Isa & Dagi Mobile Shop account.\n\n"
                     . "Click the link below to reset your password (valid for 1 hour):\n\n"
                     . $reset_url . "\n\n"
                     . "If you did not request this, please ignore this email.\n\n"
                     . "— Isa & Dagi Mobile Shop\n"
                     . "Merkato, Samson Building, Addis Ababa";
            require_once 'mailer.php';
            sendMail($email, $subject, $message);
        } else {
            $stmt->close();
        }
        echo json_encode(['success' => true, 'message' => 'If that email is registered, a reset link has been sent. Check your inbox.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
ob_end_flush();
