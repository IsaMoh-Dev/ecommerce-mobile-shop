<?php
// =====================================================
// Homepage Carousel Banners — fully dynamic from DB
// Admin manages these at: admin/banners.php
// =====================================================

$carousel_banners = [];

// Load all banner slots from DB
for ($i = 1; $i <= 6; $i++) {
    $key = 'banner_' . $i;
    $res = $db->con->query("SELECT setting_value FROM settings WHERE setting_key='{$key}' LIMIT 1");

    if ($res && $res->num_rows > 0) {
        // Key exists in DB
        $val = $res->fetch_assoc()['setting_value'];
        if ($val !== '') {
            // Non-empty = active banner
            $carousel_banners[] = $val;
        }
        // Empty string = admin removed it, skip
    } else {
        // Key not in DB yet (fresh install) — use default
        $defaults = [
            1 => 'assets/Banner1.png',
            2 => 'assets/my_banner.png',
            3 => 'assets/my_banner2.png',
            4 => 'assets/my_banner3.png',
        ];
        if (isset($defaults[$i]) && file_exists($defaults[$i])) {
            $carousel_banners[] = $defaults[$i];
        }
    }
}
?>
<!-- Owl-carousel -->
<section id="banner-area">
    <?php if (!empty($carousel_banners)): ?>
    <div class="owl-carousel owl-theme">
        <?php foreach ($carousel_banners as $banner): ?>
        <div class="item">
            <img src="<?php echo htmlspecialchars($banner); ?>" alt="Banner">
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<!-- !Owl-carousel -->

<!-- Scroll Down Indicator -->
<div id="scroll-down-indicator">
    <a href="#top-sale" title="Scroll down">
        <i class="fas fa-chevron-down scroll-down-icon"></i>
    </a>
</div>

<style>
#scroll-down-indicator {
    display: flex;
    justify-content: center;
    padding: 10px 0 6px;
    background: #f0f7fa;
}
.scroll-down-icon {
    color: #00A5C4;
    font-size: 22px;
    animation: bounceDown 1.2s ease-in-out infinite;
    cursor: pointer;
}
@keyframes bounceDown {
    0%, 100% { transform: translateY(0); opacity: 1; }
    50%       { transform: translateY(8px); opacity: 0.5; }
}
</style>
