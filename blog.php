<?php
$page_title = 'Blog';
ob_start();
include 'header.php';

// Single post view
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($view_id > 0) {
    $stmt = $db->con->prepare("SELECT * FROM blog WHERE blog_id=? LIMIT 1");
    $stmt->bind_param("i", $view_id);
    $stmt->execute();
    $single = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $single = null;
}

// Fetch all blogs
$blogs = [];
$res = $db->con->query("SELECT * FROM blog ORDER BY published_at DESC");
if ($res) while ($row = $res->fetch_assoc()) $blogs[] = $row;

if (empty($blogs)) {
    $blogs = [
        ['blog_id'=>0,'title'=>'Upcoming Mobiles 2026',       'image'=>'assets/blog/blog1.jpg','published_at'=>'2026-04-20','content'=>'Explore the next generation of smartphones launching this season. From foldable screens to ultra-fast 5G connectivity, see which flagship devices are worth the wait.'],
        ['blog_id'=>0,'title'=>'How to Maintain Your Phone',  'image'=>'assets/blog/blog2.jpg','published_at'=>'2026-04-15','content'=>'Keep your smartphone running like new with our professional maintenance tips. Learn how to optimize battery health and manage background apps.'],
        ['blog_id'=>0,'title'=>'Top 5 Budget Phones in 2026', 'image'=>'assets/blog/blog3.jpg','published_at'=>'2026-04-10','content'=>'Looking for great value? We review the top 5 budget smartphones that deliver premium features without breaking the bank.'],
    ];
}

// Fill to minimum 3 by repeating existing posts
while (count($blogs) < 3) {
    foreach ($blogs as $b) {
        $blogs[] = $b;
        if (count($blogs) >= 3) break;
    }
}
?>

<style>
.blog-card {
    background:#fff; border-radius:14px;
    box-shadow:0 2px 12px rgba(0,0,0,0.07);
    border:1px solid #eee; overflow:hidden;
    transition:transform 0.2s, box-shadow 0.2s;
    height:100%;
}
.blog-card:hover { transform:translateY(-4px); box-shadow:0 8px 28px rgba(0,0,0,0.12); }
.blog-card img { width:100%; height:200px; object-fit:cover; display:block; }
.blog-card-body { padding:20px; }
.blog-date { font-size:12px; color:#888; margin-bottom:8px; }
.blog-title {
    font-family:'Rubik',sans-serif; font-size:17px; font-weight:700;
    color:#003859; margin-bottom:10px; line-height:1.4;
}
.blog-excerpt { font-size:14px; color:#555; line-height:1.75; margin-bottom:16px; }
.blog-read-more {
    display:inline-flex; align-items:center; gap:6px;
    font-size:13px; font-weight:600; color:#00A5C4;
    text-decoration:none; transition:gap 0.2s;
}
.blog-read-more:hover { gap:10px; color:#003859; }
</style>

<section class="py-5" style="background:#f8f9fa;min-height:80vh;">
<div class="container">

    <?php if ($single): ?>
    <!-- ===== SINGLE POST VIEW ===== -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb" style="font-size:13px;">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($single['title']); ?></li>
        </ol>
    </nav>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                <img src="<?php echo htmlspecialchars($single['image']); ?>"
                     alt="<?php echo htmlspecialchars($single['title']); ?>"
                     style="width:100%;height:320px;object-fit:cover;">
                <div class="p-4 p-md-5">
                    <p class="text-muted mb-2" style="font-size:13px;">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('M d, Y', strtotime($single['published_at'])); ?>
                    </p>
                    <h2 style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;margin-bottom:20px;">
                        <?php echo htmlspecialchars($single['title']); ?>
                    </h2>
                    <div style="font-size:15px;line-height:1.9;color:#444;">
                        <?php echo nl2br(htmlspecialchars($single['content'])); ?>
                    </div>
                    <hr class="my-4">
                    <a href="blog.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Blog
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== BLOG LIST ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="section-heading mb-0"><i class="fas fa-newspaper me-2"></i>Latest Blog Posts</h4>
        <span class="text-muted" style="font-size:13px;"><?php echo count($blogs); ?> articles</span>
    </div>

    <div class="row g-4">
        <?php foreach ($blogs as $blog): ?>
        <div class="col-md-4">
            <div class="blog-card">
                <div class="blog-card-img-wrap">
                    <img src="<?php echo htmlspecialchars($blog['image']); ?>"
                         alt="<?php echo htmlspecialchars($blog['title']); ?>">
                </div>
                <div class="blog-card-body">
                    <div class="blog-date">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('M d, Y', strtotime($blog['published_at'])); ?>
                    </div>
                    <div class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></div>
                    <div class="blog-excerpt">
                        <?php echo htmlspecialchars(mb_substr($blog['content'], 0, 140)) . '...'; ?>
                    </div>
                    <?php if ($blog['blog_id'] > 0): ?>
                    <a href="blog.php?id=<?php echo $blog['blog_id']; ?>" class="blog-read-more">
                        Read More <i class="fas fa-arrow-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</section>

<?php include 'footer.php'; ?>
