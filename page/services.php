<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG

// === 1. LẤY CÁC LOẠI DỊCH VỤ (CATEGORIES) ĐỂ TẠO BỘ LỌC ===
$categories_result = $conn->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// === 2. LẤY FILTER HIỆN TẠI TỪ URL ===
$current_category = $_GET['category'] ?? 'all'; // Mặc định là 'all'

// === 3. CÀI ĐẶT PHÂN TRANG (PAGINATION) ===
$limit = 6; // Số dịch vụ trên mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// === 4. TẠO CÂU LỆNH SQL ĐỘNG (BẢO MẬT) ===
$sql_where = ""; // Mệnh đề WHERE
$params = []; // Mảng chứa các biến
$types = ""; // Chuỗi chứa kiểu dữ liệu (ví dụ: 'sii')

// Nếu có lọc category
if ($current_category != 'all') {
    $sql_where = "WHERE category = ?";
    $params[] = $current_category;
    $types .= "s";
}

// === 5. TRUY VẤN 1: ĐẾM TỔNG SỐ DỊCH VỤ (ĐỂ PHÂN TRANG) ===
$count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM services $sql_where");
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_services = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_services / $limit);
$count_stmt->close();

// === 6. TRUY VẤN 2: LẤY DỊCH VỤ CHO TRANG HIỆN TẠI ===
// Thêm LIMIT và OFFSET vào câu lệnh
$sql_limit = "ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare("SELECT id, name, description, image_url, price FROM services $sql_where $sql_limit");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$services = $stmt->get_result(); // Lấy kết quả để lặp (while)
$stmt->close();

$i = 0; // Khởi tạo $i cho animation

include '../templates/header.php'; // 7. GỌI HEADER
?>

<style>
:root {
    --primary: #4F46E5;
    --primary-dark: #4338CA;
    --secondary: #EC4899;
    --text-dark: #1F2937;
    --text-light: #6B7280;
    --bg-light: #F9FAFB;
}

.page-container { max-width: 1400px; margin: 40px auto 60px; padding: 0 20px; }
.section-header { margin-bottom: 40px; }
.section-label { color: var(--primary); font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.section-title { font-size: 36px; font-weight: 800; color: var(--text-dark); margin-bottom: 12px; }
.section-description { color: var(--text-light); font-size: 18px; max-width: 600px; }

/* === 1. CSS CHO BỘ LỌC (MỚI) === */
.filter-bar {
    display: flex;
    flex-wrap: wrap; /* Cho phép xuống dòng trên mobile */
    justify-content: center;
    gap: 12px;
    margin-bottom: 40px;
}
.filter-bar a {
    display: inline-block;
    padding: 10px 20px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    color: var(--text-light);
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 30px; /* Bo tròn */
    transition: all 0.3s ease;
}
.filter-bar a:hover {
    background: #f0f0f0;
    color: var(--text-dark);
    border-color: #ccc;
}
/* Nút active (đang được chọn) */
.filter-bar a.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
/* === KẾT THÚC CSS BỘ LỌC === */


/* Lưới dịch vụ (Giữ nguyên) */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 30px;
    min-height: 300px; /* Thêm chiều cao tối thiểu */
}

/* Thẻ dịch vụ (Giữ nguyên) */
.service-card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid #f0f0f0; position: relative; overflow: hidden; display: flex; flex-direction: column; }
.service-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(79, 70, 229, 0.15); }
.service-card-image { height: 220px; overflow: hidden; }
.service-card-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.service-card:hover .service-card-image img { transform: scale(1.05); }
.service-card-content { padding: 25px 30px 30px; display: flex; flex-direction: column; flex-grow: 1; }
.service-title { font-size: 20px; font-weight: 700; color: var(--text-dark); margin-bottom: 12px; }
.service-description { color: var(--text-light); font-size: 15px; line-height: 1.6; margin-bottom: 20px; }
.service-card-footer { margin-top: auto; border-top: 1px solid #f0f0f0; padding-top: 20px; }
.service-price { display: flex; align-items: baseline; gap: 8px; margin-bottom: 20px; }
.price-amount { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.price-currency { color: var(--text-light); font-size: 16px; }
.btn-order { width: 100%; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border: none; padding: 14px 24px; border-radius: 12px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease; }
.btn-order:hover { transform: scale(1.02); box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4); }

/* (Giữ nguyên .empty-state, keyframes, responsive) */
.empty-state { grid-column: 1/-1; text-align: center; padding: 80px 20px; }
.empty-icon { font-size: 64px; color: #E5E7EB; margin-bottom: 20px; }
.empty-text { color: var(--text-light); font-size: 18px; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* === 2. CSS CHO PHÂN TRANG (MỚI) === */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 50px; /* Đặt dưới lưới dịch vụ */
}
.pagination a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%; /* Bo tròn */
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-light);
    background: #fff;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}
.pagination a:hover {
    background: #f0f0f0;
    color: var(--text-dark);
    border-color: #ccc;
}
/* Nút trang active (đang được chọn) */
.pagination a.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
/* === KẾT THÚC CSS PHÂN TRANG === */

@media (max-width: 768px) { 
    .services-grid { grid-template-columns: 1fr; } 
    .section-title { font-size: 28px; } 
}
</style>
<main class="page-container">

    <section class="services-section">
        <div class="section-header">
            <div class="section-label">CÁC DỊCH VỤ CỦA CHÚNG TÔI</div>
            <h2 class="section-title">Giải pháp toàn diện cho máy tính</h2>
            <p class="section-description">
                Từ vệ sinh, sửa chữa đến nâng cấp - chúng tôi cung cấp đầy đủ dịch vụ để máy tính của bạn luôn hoạt động tốt nhất.
            </p>
        </div>

        <div class="filter-bar">
            <a href="<?php echo BASE_URL; ?>page/services.php?category=all" 
               class="<?php echo ($current_category == 'all') ? 'active' : ''; ?>">
                Tất cả Dịch vụ
            </a>
            
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo BASE_URL; ?>page/services.php?category=<?php echo urlencode($cat['category']); ?>" 
                   class="<?php echo ($current_category == $cat['category']) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="services-grid">
            <?php if ($services->num_rows > 0): ?>
                <?php while ($service = $services->fetch_assoc()): ?>
                
                    <div class="service-card" style="animation: fadeInUp 0.5s ease backwards; animation-delay: <?php echo $i * 0.1; ?>s;">
                        <div class="service-card-image">
                            <?php 
                                $image = !empty($service['image_url']) 
                                         ? htmlspecialchars($service['image_url']) 
                                         : 'https://via.placeholder.com/400x220/667eea/ffffff?text=ComputerCare';
                            ?>
                            <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                        </div>
                        <div class="service-card-content">
                            <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="service-card-footer">
                                <div class="service-price">
                                    <span class="price-amount"><?php echo number_format($service['price'], 0, ",", "."); ?></span>
                                    <span class="price-currency">VNĐ</span>
                                </div>
                                <button class="btn-order" onclick="orderService(<?php echo $service['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Đặt dịch vụ ngay
                                </button>
                            </div>
                        </div>
                    </div>

                <?php 
                    $i++;
                ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p class="empty-text">Không tìm thấy dịch vụ nào phù hợp với bộ lọc này.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <nav class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="<?php echo BASE_URL; ?>page/services.php?category=<?php echo urlencode($current_category); ?>&page=<?php echo $p; ?>" 
                   class="<?php echo ($p == $page) ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
        </nav>
        </section>

</main>

<script>
// (Code JavaScript cho orderService(serviceId) giữ nguyên, không cần sửa)
function orderService(serviceId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = '<?php echo BASE_URL; ?>page/booking.php?service_id=' + serviceId;
    <?php else: ?>
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center';
        overlay.innerHTML = `
            <div style="background:white;padding:40px;border-radius:20px;max-width:400px;text-align:center;animation:slideDown 0.3s ease">
                <div style="font-size:48px;margin-bottom:20px">🔐</div>
                <h3 style="font-size:24px;margin-bottom:12px;color:#1F2937">Vui lòng đăng nhập</h3>
                <p style="color:#6B7280;margin-bottom:24px">Bạn cần đăng nhập để sử dụng dịch vụ này</p>
                <button onclick="window.location.href='<?php echo BASE_URL; ?>page/login.php'" style="background:linear-gradient(135deg, #4F46E5, #4338CA);color:white;border:none;padding:12px 32px;border-radius:12px;font-weight:600;cursor:pointer;width:100%">
                    Đăng nhập ngay
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.onclick = (e) => e.target === overlay && overlay.remove();
    <?php endif; ?>
}
</script>

<?php include '../templates/footer.php'; ?>