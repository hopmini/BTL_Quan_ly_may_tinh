<?php
session_start();
require '../config/db.php';
include '../templates/header.php';


// Lấy danh sách dịch vụ
$services = $conn->query("SELECT * FROM services ORDER BY id DESC");
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

/* Container chính cho trang */
.page-container {
    max-width: 1400px;
    margin: 40px auto 60px; /* Thêm khoảng cách trên dưới */
    padding: 0 20px;
}

/* Header của Section */
.section-header {
    margin-bottom: 40px;
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
}

/* Lưới dịch vụ */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

.service-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}
.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}
.service-card:hover::before { transform: scaleX(1); }
.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(79, 70, 229, 0.15);
    border-color: var(--primary);
}
.service-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}
.service-card:hover .service-icon {
    transform: scale(1.1) rotate(5deg);
}
.service-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
}
.service-description {
    color: var(--text-light);
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 20px;
}
.service-price {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 20px;
}
.price-amount {
    font-size: 28px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.price-currency {
    color: var(--text-light);
    font-size: 16px;
}
.btn-order {
    width: 100%;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    padding: 14px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-order:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
}
.empty-state {
    grid-column: 1/-1;
    text-align: center;
    padding: 80px 20px;
}
.empty-icon { font-size: 64px; color: #E5E7EB; margin-bottom: 20px; }
.empty-text { color: var(--text-light); font-size: 18px; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Keyframes cho popup */
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
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

        <div class="services-grid">
            <?php if ($services->num_rows > 0): ?>
                <?php while ($service = $services->fetch_assoc()): ?>
                    <div class="service-card" style="animation: fadeInUp 0.5s ease backwards; animation-delay: <?php echo $i * 0.1; ?>s;">
                        <div class="service-icon">⚙️</div> <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                        <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                        <div class="service-price">
                            <span class="price-amount"><?php echo number_format($service['price'], 0, ",", "."); ?></span>
                            <span class="price-currency">VNĐ</span>
                        </div>
                        <button class="btn-order" onclick="orderService(<?php echo $service['id']; ?>)">
                            <i class="fas fa-shopping-cart"></i> Đặt dịch vụ ngay
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p class="empty-text">Hiện chưa có dịch vụ nào. Vui lòng quay lại sau!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<script>
function orderService(serviceId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        // 1. Đã đăng nhập -> Chuyển đến trang booking
        window.location.href = '<?php echo BASE_URL; ?>page/booking.php?service_id=' + serviceId;
    <?php else: ?>
        // 2. Chưa đăng nhập -> Hiển thị popup yêu cầu đăng nhập
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center';
        overlay.innerHTML = `
            <div style="background:white;padding:40px;border-radius:20px;max-width:400px;text-align:center;animation:slideDown 0.3s ease">
                <div style="font-size:48px;margin-bottom:20px">🔐</div>
                <h3 style="font-size:24px;margin-bottom:12px;color:#1F2937">Vui lòng đăng nhập</h3>
                <p style="color:#6B7280;margin-bottom:24px">Bạn cần đăng nhập để sử dụng dịch vụ này</p>
                <button onclick="window.location.href='login.php'" style="background:linear-gradient(135deg, #4F46E5, #4338CA);color:white;border:none;padding:12px 32px;border-radius:12px;font-weight:600;cursor:pointer;width:100%">
                    Đăng nhập ngay
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
        // Cho phép bấm ra ngoài để đóng popup
        overlay.onclick = (e) => e.target === overlay && overlay.remove();
    <?php endif; ?>
}
</script>

<?php include 'templates/footer.php'; ?>