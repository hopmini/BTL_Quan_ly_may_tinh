<?php
session_start();
require 'config/db.php'; // 1. GỌI CONFIG
include 'templates/header.php'; // 2. GỌI HEADER

// === LẤY 3 BÀI BLOG MỚI NHẤT ===
$stmt_posts = $conn->prepare(
    "SELECT p.id, p.title, p.content, p.image_url, p.created_at, u.name as author_name
     FROM posts p
     LEFT JOIN users u ON p.author_id = u.id
     ORDER BY p.created_at DESC
     LIMIT 3" // Chỉ lấy 3 bài
);
$stmt_posts->execute();
$latest_posts = $stmt_posts->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_posts->close();

// Hàm rút gọn nội dung (tóm tắt 100 ký tự)
function get_excerpt($content, $length = 100) {
    $content = strip_tags($content); 
    if (strlen($content) > $length) {
        $excerpt = substr($content, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        // Đảm bảo không cắt giữa chừng
        $excerpt = ($last_space) ? substr($excerpt, 0, $last_space) : $excerpt; 
        return $excerpt . '...';
    }
    return $content;
}

// (Hàm này dùng cho "Dịch vụ Nổi bật" - nếu bạn muốn nâng cấp sau)
// function getIconForCategory($category) { ... }
?>

<style>
/* 1. CSS CHO BLOG MỚI NHẤT */
.latest-blog-section {
    max-width: 1200px;
    margin: 80px auto;
    padding: 0 20px;
}
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}
.post-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
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
    transform: scale(1.05);
}
.post-content {
    padding: 25px 30px 30px;
    display: flex;
    flex-direction: column;
    flex-grow: 1; 
}
.post-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 15px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
    min-height: 56px;
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
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
.post-meta {
    margin-top: auto;
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
.view-all-link {
    text-align: center;
    margin-top: 40px;
}
.view-all-link .btn-primary {
    background: var(--primary);
    color: white;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
}
.view-all-link .btn-primary:hover {
    background: var(--primary-dark);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
}

/* 2. CSS CHO HERO SECTION */
.hero-section {
    position: relative;
    height: 600px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
    margin-bottom: 60px;
}
.hero-content {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: 0 auto;
    padding: 120px 20px 60px;
    color: white;
}
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 14px;
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,0.2);
}
.hero-title {
    font-size: 56px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 24px;
    animation: fadeInUp 0.8s ease;
}
.hero-subtitle {
    font-size: 20px;
    opacity: 0.95;
    max-width: 600px;
    line-height: 1.6;
    margin-bottom: 32px;
    animation: fadeInUp 0.8s ease 0.2s both;
}
.hero-cta {
    display: flex;
    gap: 16px;
    animation: fadeInUp 0.8s ease 0.4s both;
}
.btn-primary {
    background: white;
    color: var(--primary);
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    text-decoration: none; 
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.btn-secondary {
    background: rgba(255,255,255,0.15);
    color: white;
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    border: 2px solid rgba(255,255,0.3);
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    text-decoration: none; 
}
.btn-secondary:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.hero-image { display: none; }

/* 3. CSS CHO CHAT DEMO */
.chat-bubble {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    cursor: pointer;
    z-index: 1000;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    animation: fadeInUp 0.5s ease;
    display: none; /* Ẩn ban đầu */
}
.chat-bubble:hover {
    transform: scale(1.1);
}
.chat-window {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    max-width: 90%;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: fadeInUp 0.5s ease;
    display: none; /* Ẩn ban đầu, JS sẽ cho hiện */
}
.chat-header {
    background: #1F2937;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.chat-header-text strong {
    display: block;
    font-size: 16px;
}
.chat-header-text span {
    font-size: 13px;
    opacity: 0.8;
}
.chat-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    font-weight: 300;
    cursor: pointer;
    padding: 0 5px;
}
.chat-body {
    flex-grow: 1;
    padding: 20px;
    background: #f9f9f9;
    overflow-y: auto;
}
.message {
    background: white;
    border-radius: 10px 10px 10px 0;
    padding: 12px 15px;
    max-width: 80%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    border: 1px solid #eee;
}
.message.fado {
    background: #f1f0f0;
    border-radius: 10px 10px 0 10px;
    float: left;
}
.message span {
    font-size: 15px;
    color: var(--text-dark);
    display: block;
    line-height: 1.5;
}
.message small {
    font-size: 11px;
    color: var(--text-light);
    margin-top: 5px;
    display: block;
}
.chat-footer {
    border-top: 1px solid #e0e0e0;
    padding: 10px 15px;
    background: white;
    display: flex;
    flex-shrink: 0;
}
.chat-footer input {
    flex-grow: 1;
    border: none;
    padding: 10px;
    font-size: 14px;
    background: #f1f1f1;
    border-radius: 20px;
    padding-left: 20px;
}
.chat-footer input:focus {
    outline: none;
}
.chat-footer button {
    background: none;
    border: none;
    font-size: 20px;
    color: var(--primary);
    cursor: pointer;
    padding: 0 15px;
}

/* 4. CSS CHO HEADER SECTION (Tiêu đề chung) */
.section-header {
    text-align: center;
    margin-bottom: 50px;
    padding: 0 20px;
}
.section-label {
    color: var(--primary);
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}
.section-title {
    font-size: 36px;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 12px;
}
.section-description {
    color: var(--text-light);
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto;
}

/* 5. CSS CHO KHU VỰC "QUY TRÌNH 3 BƯỚC" */
.how-it-works-section {
    max-width: 1200px;
    margin: 60px auto;
    padding: 0 20px;
}
.how-it-works-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    text-align: center;
}
.step-card {
    padding: 30px;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    background: #fff;
    transition: all 0.3s ease;
}
.step-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.07);
}
.step-icon {
    font-size: 48px;
    color: var(--primary);
    margin-bottom: 20px;
}
.step-card h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 10px;
}
.step-card p {
    font-size: 15px;
    color: var(--text-light);
    line-height: 1.6;
}

/* 6. CSS CHO KHU VỰC "DỊCH VỤ NỔI BẬT" (DEMO) */
.feature-section {
    max-width: 1200px;
    margin: 80px auto;
    padding: 0 20px;
}
.feature-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}
.feature-card {
    background: white;
    border-radius: 20px;
    padding: 35px 30px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    transition: all 0.4s ease;
    border: 1px solid transparent;
    display: flex; 
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 12px 40px rgba(79, 70, 229, 0.15);
    border-color: var(--primary);
}
.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: white;
    margin-bottom: 25px;
    transition: all 0.3s ease;
}
.feature-card:hover .feature-icon {
    transform: scale(1.1) rotate(10deg);
}
.feature-card h3 {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
}
.feature-card p {
    font-size: 15px;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 25px;
}
.feature-card .btn-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    margin-top: auto; 
}
.feature-card .btn-link i {
    margin-left: 5px;
    transition: transform 0.3s ease;
}
.feature-card:hover .btn-link i {
    transform: translateX(5px);
}

/* 7. CSS RESPONSIVE */
@media (max-width: 992px) {
    .how-it-works-grid, .feature-grid {
        grid-template-columns: 1fr; /* Xếp chồng trên tablet */
    }
}
</style>

<div class="hero-section">
    <div class="hero-content">
        <div class="hero-badge">
            <span>🚀</span>
            <span>Dịch vụ chuyên nghiệp hàng đầu</span>
        </div>
        <h1 class="hero-title">
            Chăm sóc máy tính<br>
            của bạn tận tâm
        </h1>
        <p class="hero-subtitle">
            Dịch vụ sửa chữa, bảo trì và nâng cấp máy tính chuyên nghiệp với đội ngũ kỹ thuật viên giàu kinh nghiệm. Chúng tôi cam kết mang đến giải pháp tối ưu nhất cho bạn.
        </p>
        <div class="hero-cta">
            <a href="<?php echo BASE_URL; ?>page/services.php" class="btn-primary">
                Khám phá dịch vụ
            </a>
            <a href="<?php echo BASE_URL; ?>page/contact.php" class="btn-secondary">
                Tư vấn miễn phí
            </a>
        </div>
    </div>
</div>

<section class="how-it-works-section">
    <div class="section-header">
        <div class="section-label">LÀM VIỆC ĐƠN GIẢN</div>
        <h2 class="section-title">Quy trình Dịch vụ 3 Bước</h2>
        <p class="section-description">
            Chúng tôi tối ưu hóa quy trình để mang lại trải nghiệm nhanh chóng và thuận tiện nhất cho bạn.
        </p>
    </div>
    <div class="how-it-works-grid">
        <div class="step-card">
            <div class="step-icon"><i class="fas fa-calendar-check"></i></div>
            <h3>1. Đặt lịch Online</h3>
            <p>Chọn dịch vụ bạn cần, điền thông tin và chọn ngày giờ hẹn. Hệ thống sẽ xác nhận ngay lập tức.</p>
        </div>
        <div class="step-card">
            <div class="step-icon"><i class="fas fa-cogs"></i></div>
            <h3>2. Xử lý & Sửa chữa</h3>
            <p>Mang máy đến cửa hàng (hoặc chúng tôi đến tận nơi). Kỹ thuật viên sẽ chẩn đoán và tiến hành sửa chữa.</p>
        </div>
        <div class="step-card">
            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
            <h3>3. Nhận máy & Bảo hành</h3>
            <p>Bạn nhận lại thiết bị đã hoạt động hoàn hảo, kèm theo cam kết bảo hành cho dịch vụ của chúng tôi.</p>
        </div>
    </div>
</section>

<section class="feature-section">
    <div class="section-header">
        <div class="section-label">CHÚNG TÔI CÓ THỂ LÀM GÌ?</div>
        <h2 class="section-title">Dịch vụ Nổi bật</h2>
        <p class="section-description">
            Giải quyết mọi vấn đề từ cơ bản đến phức tạp nhất cho thiết bị của bạn.
        </p>
    </div>
    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-tools"></i></div>
            <h3>Sửa chữa Phần cứng</h3>
            <p>Chẩn đoán và sửa chữa các lỗi phần cứng phức tạp như mainboard, VGA, màn hình, bàn phím.</p>
            <a href="<?php echo BASE_URL; ?>page/services.php" class="btn-link">
                Xem chi tiết <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-microchip"></i></div>
            <h3>Nâng cấp & Bảo trì</h3>
            <p>Tăng tốc máy tính của bạn với nâng cấp SSD, RAM. Vệ sinh, tra keo tản nhiệt định kỳ.</p>
            <a href="<?php echo BASE_URL; ?>page/services.php" class="btn-link">
                Xem chi tiết <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Cài đặt & Bảo mật</h3>
            <p>Cài đặt Windows, MacOS, phần mềm văn phòng. Diệt virus tận gốc và thiết lập bảo mật hệ thống.</p>
            <a href="<?php echo BASE_URL; ?>page/services.php" class="btn-link">
                Xem chi tiết <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="latest-blog-section">
    <div class="section-header">
        <div class="section-label">TIN TỨC & MẸO VẶT</div>
        <h2 class="section-title">Blog Công nghệ Mới nhất</h2>
    </div>
    
    <div class="blog-grid">
        <?php if (empty($latest_posts)): ?>
            <p style="text-align: center; color: var(--text-light); grid-column: 1 / -1;">Chưa có bài viết nào.</p>
        <?php else: ?>
            <?php foreach ($latest_posts as $post): ?>
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
    
    <div class="view-all-link">
        <a href="<?php echo BASE_URL; ?>page/blog.php" class="btn-primary">
            Xem tất cả bài viết <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

<div id="chat-bubble" class="chat-bubble" onclick="openChatWindow()">
    <i class="fas fa-comment-dots"></i>
</div>

<div id="chat-window" class="chat-window">
    <div class="chat-header">
        <div class="chat-header-text">
            <strong>Đội hỗ trợ Computer Care</strong>
            <span>Sẵn lòng giải đáp!</span>
        </div>
        <button class="chat-close-btn" onclick="closeChatWindow()">&times;</button>
    </div>
    <div class="chat-body">
        <div class="message fado">
            <span>Anh/chị cần hỗ trợ thông tin gì ạ?</span>
            <small>CSKH Computer Care • Vài giây trước</small>
        </div>
    </div>
    <div class="chat-footer">
        <input type="text" placeholder="Nhập nội dung...">
        <button><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
    // === CODE CHO CHAT DEMO ===
    const chatBubble = document.getElementById('chat-bubble');
    const chatWindow = document.getElementById('chat-window');

    function closeChatWindow() {
        if (chatWindow) chatWindow.style.display = 'none'; 
        if (chatBubble) chatBubble.style.display = 'flex';
    }

    function openChatWindow() {
        if (chatBubble) chatBubble.style.display = 'none';
        if (chatWindow) chatWindow.style.display = 'flex';
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            if (chatWindow && chatWindow.style.display !== 'none') {
                chatWindow.style.display = 'flex';
                if (chatBubble) chatBubble.style.display = 'none';
            }
        }, 200); // Mở sau 2 giây
    });
    
    // === CODE SMOOTH SCROLL ===
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>

<?php include 'templates/footer.php'; // CHỈ 1 DÒNG INCLUDE FOOTER Ở CUỐI CÙNG ?>