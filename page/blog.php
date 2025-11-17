<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG
include '../templates/header.php'; // 2. GỌI HEADER

// === LẤY TẤT CẢ BÀI VIẾT (MỚI NHẤT LÊN ĐẦU) ===
$stmt_posts = $conn->prepare(
    "SELECT p.id, p.title, p.content, p.image_url, p.created_at, u.name as author_name
     FROM posts p
     LEFT JOIN users u ON p.author_id = u.id
     ORDER BY p.created_at DESC"
);
$stmt_posts->execute();
$posts_result = $stmt_posts->get_result();
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);
$stmt_posts->close();

// Hàm rút gọn nội dung (tóm tắt)
function get_excerpt($content, $length = 150) {
    $content = strip_tags($content); // Xóa tag HTML
    if (strlen($content) > $length) {
        $excerpt = substr($content, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        $excerpt = substr($excerpt, 0, $last_space);
        return $excerpt . '...';
    }
    return $content;
}
?>

<style>
/* Nền chung */
.blog-container {
    min-height: calc(100vh - 72px);
    padding: 40px 20px 60px;
    background: var(--bg-light);
}
.blog-content {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header của trang */
.blog-header {
    text-align: center;
    margin-bottom: 50px;
}
.blog-header .section-label {
    color: var(--primary);
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}
.blog-header .section-title {
    font-size: 36px;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 12px;
}
.blog-header .section-description {
    color: var(--text-light);
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto;
}

/* Lưới các bài blog */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

/* Thẻ bài viết (Card) */
.post-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    overflow: hidden; /* Để bo góc ảnh */
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    display: flex; /* Giúp đẩy footer xuống */
    flex-direction: column;
}
.post-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
}

.post-image-wrapper {
    height: 220px;
    background: #eee;
    overflow: hidden;
}
.post-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.post-card:hover .post-image-wrapper img {
    transform: scale(1.05); /* Zoom nhẹ khi hover */
}

.post-content {
    padding: 25px 30px 30px;
    display: flex;
    flex-direction: column;
    flex-grow: 1; /* Quan trọng: Đẩy footer xuống */
}

.post-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 15px;
    line-height: 1.4;
    /* Giới hạn 2 dòng */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
    min-height: 56px; /* Giữ 2 dòng */
}
.post-title a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}
.post-title a:hover {
    color: var(--primary);
}

.post-excerpt {
    font-size: 15px;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 20px;
    /* Giới hạn 3 dòng */
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}

.post-meta {
    margin-top: auto; /* Đẩy xuống dưới cùng */
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: var(--text-light);
    border-top: 1px solid #f0f0f0;
    padding-top: 15px;
}
.post-meta .author {
    font-weight: 500;
    color: var(--text-dark);
}
.post-meta .date {
    font-style: italic;
}

/* Khi không có bài viết */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    border: 2px dashed #e0e0e0;
    border-radius: 15px;
    grid-column: 1 / -1; /* Chiếm toàn bộ lưới */
}
.empty-state i { font-size: 48px; color: #ccc; margin-bottom: 15px; }
.empty-state p { font-size: 16px; color: var(--text-light); }

@media (max-width: 768px) {
    .blog-grid { grid-template-columns: 1fr; }
    .section-title { font-size: 28px; }
}
</style>
<main class="blog-container">
    <div class="blog-content">
        
        <div class="blog-header">
            <div class="section-label">TIN TỨC & MẸO VẶT</div>
            <h2 class="section-title">Blog Công nghệ</h2>
            <p class="section-description">
                Cập nhật các tin tức mới nhất, hướng dẫn và mẹo vặt hữu ích để sử dụng máy tính hiệu quả hơn.
            </p>
        </div>

        <div class="blog-grid">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-newspaper"></i>
                    <p>Chưa có bài viết nào. Vui lòng quay lại sau!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-image-wrapper">
                            <a href="<?php echo BASE_URL; ?>page/post_detail.php?id=<?php echo $post['id']; ?>">
                                <?php 
                                    $image = !empty($post['image_url']) 
                                             ? htmlspecialchars($post['image_url']) 
                                             : 'https://via.placeholder.com/400x220/667eea/ffffff?text=ComputerCare';
                                ?>
                                <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </a>
                        </div>
                        <div class="post-content">
                            <h3 class="post-title">
                                <a href="<?php echo BASE_URL; ?>page/post_detail.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            <p class="post-excerpt">
                                <?php echo htmlspecialchars(get_excerpt($post['content'])); ?>
                            </p>
                            <div class="post-meta">
                                <span class="author">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                                </span>
                                <span class="date">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../templates/footer.php'; ?>