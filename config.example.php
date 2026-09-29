<?php
/**
 * config.example.php — Configuration template
 * ─────────────────────────────────────────────────────────────────────────────
 * Copy this file to config.php and fill in your actual values.
 * config.php is ignored by git — config.example.php is safe to commit.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_USER',     'root');         // your DB username
define('DB_PASSWORD', '');             // your DB password
define('DB_NAME',     'shopee');       // your database name

// ── SMTP / Email (optional — set USE_SMTP to true to enable) ──────────────────
define('USE_SMTP',    false);
define('SMTP_HOST',   'smtp.gmail.com');
define('SMTP_PORT',   587);
define('SMTP_USER',   'your@gmail.com');       // your Gmail address
define('SMTP_PASS',   'your_app_password');    // Gmail App Password
