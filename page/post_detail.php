<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG

// BẮT BUỘC: 2. Phải có ID bài viết
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . BASE_URL . "page/blog.php"); // Không có ID, đá về trang blog
    exit();
}

$post_id = (int)$_GET['id'];

// === LẤY CHI TIẾT BÀI VIẾT ===
$stmt = $conn->prepare(
    "SELECT p.id, p.title, p.content, p.image_url, p.created_at, u.name as author_name
     FROM posts p
     LEFT JOIN users u ON p.author_id = u.id
     WHERE p.id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

// Nếu không tìm thấy bài viết, quay về trang blog
if (!$post) {
    header("Location: " . BASE_URL . "page/blog.php");
    exit();
}

include '../templates/header.php'; // 3. GỌI HEADER
?>

<style>
.post-detail-container {
    max-width: 900px; /* Thu hẹp lại để dễ đọc */
    margin: 40px auto 60px;
    padding: 0 20px;
}
.post-detail-box {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.07);
    overflow: hidden; /* Bo góc ảnh */
}

/* Header (Tiêu đề, Tác giả) */
.post-detail-header {
    padding: 40px 40px 25px;
}
.post-detail-title {
    font-size: 36px;
    font-weight: 800;
    color: var(--text-dark);
    line-height: 1.3;
    margin-bottom: 20px;
}
.post-detail-meta {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: var(--text-light);
    border-top: 1px solid #f0f0f0;
    padding-top: 15px;
}
.post-detail-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
}
.post-detail-meta span i {
    color: var(--primary);
}

/* Ảnh bìa */
.post-detail-image {
    width: 100%;
    max-height: 450px; /* Giới hạn chiều cao ảnh */
    object-fit: cover;
}

/* Nội dung bài viết */
.post-detail-content {
    padding: 30px 40px 50px;
    font-size: 17px;
    color: #333;
    line-height: 1.8;
}
/* Style cho nội dung (nếu có HTML) */
.post-detail-content p {
    margin-bottom: 1.5em;
}
.post-detail-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-top: 2em;
    margin-bottom: 1em;
}
.post-detail-content ul, .post-detail-content ol {
    margin-bottom: 1.5em;
    padding-left: 1.5em;
}

/* Nút quay lại */
.back-link {
    display: inline-block;
    margin-bottom: 25px;
    font-size: 15px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    transition: all 0.3s ease;
}
.back-link i {
    margin-right: 5px;
    transition: transform 0.3s ease;
}
.back-link:hover {
    color: var(--primary-dark);
}
.back-link:hover i {
    transform: translateX(-5px);
}
</style>
<main class="post-detail-container">

    <a href="<?php echo BASE_URL; ?>page/blog.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Quay lại Danh sách Blog
    </a>

    <div class="post-detail-box">
        <div class="post-detail-header">
            <h1 class="post-detail-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-detail-meta">
                <span>
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?>
                </span>
                <span>
                    <i class="fas fa-calendar-alt"></i> 
                    <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($post['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                 class="post-detail-image">
        <?php endif; ?>

        <div class="post-detail-content">
            <?php 
                // Dùng nl2br để giữ lại các dấu xuống dòng
                echo nl2br(htmlspecialchars($post['content'])); 
            ?>
        </div>
    </div>
</main>

<?php include '../templates/footer.php'; ?>