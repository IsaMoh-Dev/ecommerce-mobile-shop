<?php
class Auth
{
    public $db = null;

    public function __construct(DBController $db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Register
    public function register($first_name, $last_name, $email, $password, $phone = '') {
        $first_name = trim($first_name);
        $last_name  = trim($last_name);
        $email      = trim(strtolower($email));
        $password   = trim($password);
        $phone      = trim($phone);

        if (empty($first_name) || empty($email) || empty($password))
            return ['success' => false, 'message' => 'Please fill in all required fields.'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'message' => 'Invalid email address.'];

        if (strlen($password) < 6)
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];

        $stmt = $this->db->con->prepare("SELECT user_id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }
        $stmt->close();

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt   = $this->db->con->prepare(
            "INSERT INTO user (first_name, last_name, email, password, phone, register_date) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed, $phone);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            $this->createSession($new_id, $first_name, $last_name, $email, 'customer');
            return ['success' => true, 'message' => 'Welcome, ' . htmlspecialchars($first_name) . '! Your account has been created.'];
        }
        $stmt->close();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }

    // Login
    public function login($email, $password) {
        $email    = trim(strtolower($email));
        $password = trim($password);

        if (empty($email) || empty($password))
            return ['success' => false, 'message' => 'Please enter your email and password.'];

        $stmt = $this->db->con->prepare(
            "SELECT user_id, first_name, last_name, email, password, role FROM user WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'No account found with this email.'];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($password, $user['password']))
            return ['success' => false, 'message' => 'Incorrect password. Please try again.'];

        $this->createSession($user['user_id'], $user['first_name'], $user['last_name'], $user['email'], $user['role']);
        return ['success' => true, 'message' => 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!', 'role' => $user['role']];
    }

    // Logout
    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }
        session_destroy();
    }

    // Create session
    private function createSession($user_id, $first_name, $last_name, $email, $role) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user_id;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name']  = $last_name;
        $_SESSION['email']      = $email;
        $_SESSION['role']       = $role;
        $_SESSION['logged_in']  = true;
    }

    public function isLoggedIn()    { return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true; }
    public function isSuperAdmin()  { return $this->isLoggedIn() && $_SESSION['role'] === 'superadmin'; }
    public function isAdmin()       { return $this->isLoggedIn() && in_array($_SESSION['role'], ['admin', 'superadmin']); }
    public function isSeller()      { return $this->isLoggedIn() && $_SESSION['role'] === 'seller'; }
    public function canAccessAdmin(){ return $this->isLoggedIn() && in_array($_SESSION['role'], ['seller', 'admin', 'superadmin']); }
    public function getRole()       { return $_SESSION['role'] ?? 'customer'; }

    public function getUser() {
        if ($this->isLoggedIn()) {
            return [
                'user_id'    => $_SESSION['user_id'],
                'first_name' => $_SESSION['first_name'],
                'last_name'  => $_SESSION['last_name'],
                'email'      => $_SESSION['email'],
                'role'       => $_SESSION['role'],
            ];
        }
        return null;
    }

    // Get current user_id (returns 1 as guest fallback)
    public function getUserId() {
        return $this->isLoggedIn() ? (int)$_SESSION['user_id'] : 0;
    }
}
