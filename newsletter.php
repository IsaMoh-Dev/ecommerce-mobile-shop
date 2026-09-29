<?php
header('Content-Type: application/json');
require_once 'functions.php';

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$e = mysqli_real_escape_string($db->con, $email);

// Check if already subscribed
$check = $db->con->query("SELECT id FROM newsletter WHERE email='{$e}' LIMIT 1");
if ($check && $check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You are already subscribed!']);
    exit;
}

$db->con->query("INSERT INTO newsletter (email) VALUES ('{$e}')");
echo json_encode(['success' => true, 'message' => 'Subscribed! You will receive our latest deals.']);
