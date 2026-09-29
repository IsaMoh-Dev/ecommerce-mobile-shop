<?php
$page_title = 'Support';
ob_start();
include 'header.php';

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? 'General');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // 1. Save to database
        $n = mysqli_real_escape_string($db->con, $name);
        $e = mysqli_real_escape_string($db->con, $email);
        $s = mysqli_real_escape_string($db->con, $subject);
        $m = mysqli_real_escape_string($db->con, $message);
        $db->con->query(
            "CREATE TABLE IF NOT EXISTS contact_messages (
                msg_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(150) NOT NULL DEFAULT 'General',
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT NOW()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $db->con->query(
            "INSERT INTO contact_messages (name, email, subject, message)
             VALUES ('{$n}', '{$e}', '{$s}', '{$m}')"
        );

        // 2. Send email to shop
        $shop_email = getSetting('shop_email') ?: 'info@isadagi.com';
        $mail_subject = 'New Contact Message: ' . $subject;
        $mail_body =
            "New message from the website contact form:\n\n" .
            "Name: " . $name . "\n" .
            "Email: " . $email . "\n" .
            "Subject: " . $subject . "\n\n" .
            "Message:\n" . $message . "\n\n" .
            "-- Isa & Dagi Mobile Shop Website";
        $headers = "From: " . $email . "\r\nReply-To: " . $email . "\r\nX-Mailer: PHP/" . phpversion();
        @mail($shop_email, $mail_subject, $mail_body, $headers);

        // 3. Send confirmation to customer
        $confirm_body =
            "Hi " . $name . ",\n\n" .
            "Thank you for contacting Isa & Dagi Mobile Shop!\n\n" .
            "We have received your message and will get back to you within 24 hours.\n\n" .
            "Your message:\n" . $message . "\n\n" .
            "-- Isa & Dagi Mobile Shop\n" .
            "Phone: +251 929 346 248\n" .
            "Telegram: @isadagishop";
        $confirm_headers = "From: support@isadagi.com\r\nReply-To: support@isadagi.com\r\nX-Mailer: PHP/" . phpversion();
        @mail($email, 'We received your message — Isa & Dagi', $confirm_body, $confirm_headers);

        $sent = true;
    }
}
?>

<!-- ===================== SUPPORT HERO ===================== -->
<section style="background:linear-gradient(135deg, var(--primary) 0%, #005580 100%); padding:50px 0 40px;">
    <div class="container text-center">
        <div style="width:64px;height:64px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-headset" style="font-size:28px;color:#fff;"></i>
        </div>
        <h2 style="font-family:'Rubik',sans-serif;font-weight:700;color:#fff;margin-bottom:10px;">How Can We Help?</h2>
        <p style="color:rgba(255,255,255,0.75);font-size:15px;max-width:500px;margin:0 auto;">
            Our support team is available 7 days a week. Browse the FAQ or send us a message and we'll get back to you fast.
        </p>
    </div>
</section>

<section class="py-5" style="background:#f8f9fa;">
<div class="container">

    <!-- Quick Help Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="support-card text-center p-4">
                <div class="support-card-icon mb-3" style="background:rgba(0,56,89,0.08);color:var(--primary);">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;">Call Us</h6>
                <p class="text-muted mb-2" style="font-size:13px;">Speak directly with our team</p>
                <a href="tel:+251929346248" class="fw-bold" style="color:var(--primary);font-size:15px;">+251 929 346 248</a>
                <p class="text-muted mt-1 mb-0" style="font-size:12px;">Mon–Sat: 8 AM – 8 PM</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="support-card text-center p-4">
                <div class="support-card-icon mb-3" style="background:rgba(0,165,196,0.1);color:var(--secondary);">
                    <i class="fab fa-telegram-plane"></i>
                </div>
                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;">Telegram</h6>
                <p class="text-muted mb-2" style="font-size:13px;">Chat with us on Telegram</p>
                <a href="https://t.me/isadagishop" target="_blank" class="fw-bold" style="color:var(--secondary);font-size:15px;">@isadagishop</a>
                <p class="text-muted mt-1 mb-0" style="font-size:12px;">Usually replies within minutes</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="support-card text-center p-4">
                <div class="support-card-icon mb-3" style="background:rgba(40,167,69,0.1);color:#28a745;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;">Visit Us</h6>
                <p class="text-muted mb-2" style="font-size:13px;">Come to our physical store</p>
                <span class="fw-bold" style="color:#28a745;font-size:14px;">Merkato, Samson Building</span>
                <p class="text-muted mt-1 mb-0" style="font-size:12px;">Addis Ababa, Ethiopia</p>
            </div>
        </div>
    </div>

    <div class="row g-5">

        <!-- FAQ -->
        <div class="col-lg-6" id="faq">
            <h4 class="section-heading">Frequently Asked Questions</h4>

            <div class="accordion" id="faqAccordion">

                <div class="accordion-item support-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Do you sell original / genuine products?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="font-size:14px;color:#555;">
                            Yes. All products sold at Isa &amp; Dagi are 100% original and come with official manufacturer warranty. We do not sell refurbished or replica devices.
                        </div>
                    </div>
                </div>

                <div class="accordion-item support-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            How long does delivery take?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="font-size:14px;color:#555;">
                            Orders placed before 2:00 PM are delivered same-day within Addis Ababa. Orders placed after 2:00 PM are delivered the next business day. We'll contact you to confirm your delivery time.
                        </div>
                    </div>
                </div>

                <div class="accordion-item support-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            What is your return policy?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="font-size:14px;color:#555;">
                            We offer a 10-day return policy. If your product has a manufacturing defect or was damaged during delivery, contact us within 10 days and we'll arrange a replacement or full refund. The product must be in its original packaging.
                        </div>
                    </div>
                </div>

                <div class="accordion-item support-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="font-size:14px;color:#555;">
                            We accept Cash on Delivery (COD), Telebirr, CBE Birr, and direct Bank Transfer. You can choose your preferred method at checkout.
                        </div>
                    </div>
                </div>

                <div class="accordion-item support-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Do you offer warranty on products?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="font-size:14px;color:#555;">
                            Yes. All smartphones come with a minimum 1-year manufacturer warranty. Accessories carry a 6-month warranty. Bring your receipt to our store for any warranty claims.
                        </div>
                    </div>
                </div>

                <div class="accordion-item support-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                            Can I track my order?
                        </button>
                    </h2>
                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="font-size:14px;color:#555;">
                            Yes. After placing your order, you can view its status (Pending, Processing, Shipped, Delivered) from the <a href="orders.php" style="color:var(--secondary);">My Orders</a> page. You'll also receive updates via phone call.
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-6" id="contact">
            <h4 class="section-heading">Send Us a Message</h4>

            <?php if ($sent): ?>
            <div class="alert alert-success d-flex align-items-center gap-2" style="border-radius:10px;">
                <i class="fas fa-check-circle fa-lg"></i>
                <div>
                    <strong>Message sent!</strong> We'll get back to you within 24 hours. Check your email for a confirmation.
                </div>
            </div>
            <?php else: ?>

            <?php if ($error): ?>
            <div class="alert alert-danger" style="border-radius:10px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" class="support-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Your Name *</label>
                        <input type="text" name="name" class="form-control support-input"
                               value="<?php echo htmlspecialchars(!$sent && isset($_POST['name']) ? $_POST['name'] : ($auth->isLoggedIn() ? $_SESSION['first_name'].' '.$_SESSION['last_name'] : '')); ?>"
                               placeholder="Full name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Email Address *</label>
                        <input type="email" name="email" class="form-control support-input"
                               value="<?php echo htmlspecialchars(!$sent && isset($_POST['email']) ? $_POST['email'] : ($auth->isLoggedIn() ? $_SESSION['email'] : '')); ?>"
                               placeholder="your@email.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">Subject</label>
                        <select name="subject" class="form-select support-input">
                            <?php foreach (['Order Issue','Product Inquiry','Return / Refund','Warranty Claim','Delivery Problem','Other'] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo (!$sent && isset($_POST['subject']) && $_POST['subject']===$opt)?'selected':''; ?>>
                                <?php echo $opt; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">Message *</label>
                        <textarea name="message" class="form-control support-input" rows="5"
                                  placeholder="Describe your issue or question in detail..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="send_message" class="btn btn-primary-custom px-5 py-2">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>

    </div>
</div>
</section>

<?php include 'footer.php'; ?>
