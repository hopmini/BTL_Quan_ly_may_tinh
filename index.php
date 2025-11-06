<?php
session_start();
require 'config/db.php';
include 'templates/header.php';


// Lấy danh sách dịch vụ
$services = $conn->query("SELECT * FROM services ORDER BY id DESC");
?>

<style>
:root {
    --primary: #4F46E5;
    --primary-dark: #4338CA;
    --secondary: #EC4899;
    --accent: #F59E0B;
    --text-dark: #1F2937;
    --text-light: #6B7280;
    --bg-light: #F9FAFB;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg-light);
    color: var(--text-dark);
}

/* Hero Section */
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
    border: 2px solid rgba(255,255,255,0.3);
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

/* Floating Animation */
.hero-image {
    position: absolute;
    right: -50px;
    top: 50%;
    transform: translateY(-50%);
    width: 600px;
    height: 600px;
    opacity: 0.15;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(-50%) translateX(0); }
    50% { transform: translateY(-50%) translateX(20px); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Main Container */
.main-container {
    max-width: 1400px;
    margin: 50px auto;
    padding: 0 20px;
    display: flex;
    gap: 32px;
}

/* Sidebar */
.sidebar {
    width: 280px;
    position: sticky;
    top: 20px;
    height: fit-content;
}

.sidebar-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
}

.sidebar-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--text-dark);
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu li {
    margin-bottom: 8px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    text-decoration: none;
    color: var(--text-light);
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.sidebar-menu a:hover {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    transform: translateX(4px);
}

.sidebar-menu a i {
    width: 20px;
    font-size: 16px;
}

/* Services Section */
.services-section {
    flex: 1;
}

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

/* Service Cards */
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

.service-card:hover::before {
    transform: scaleX(1);
}

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

.service-features {
    list-style: none;
    margin-bottom: 24px;
}

.service-features li {
    color: var(--text-light);
    font-size: 14px;
    padding: 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.service-features li::before {
    content: "✓";
    color: #10B981;
    font-weight: 700;
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
    position: relative;
    overflow: hidden;
}

.btn-order::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.btn-order:hover::before {
    left: 100%;
}

.btn-order:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
}

/* Empty State */
.empty-state {
    grid-column: 1/-1;
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 64px;
    color: #E5E7EB;
    margin-bottom: 20px;
}

.empty-text {
    color: var(--text-light);
    font-size: 18px;
}

/* Responsive */
@media (max-width: 1024px) {
    .main-container {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        position: relative;
    }
    
    .hero-title {
        font-size: 40px;
    }
    
    .hero-image {
        display: none;
    }
}

@media (max-width: 768px) {
    .hero-section {
        height: auto;
        padding: 80px 0 60px;
    }
    
    .hero-title {
        font-size: 32px;
    }
    
    .hero-subtitle {
        font-size: 16px;
    }
    
    .hero-cta {
        flex-direction: column;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .section-title {
        font-size: 28px;
    }
}

/* Scrollbar Custom */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}
</style>

<!-- Main Container -->
<div class="main-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-card">
            <h3 class="sidebar-title">Danh mục dịch vụ</h3>
            <ul class="sidebar-menu">
                <li><a href="#cleaning"><i class="fas fa-broom"></i> Vệ sinh máy tính</a></li>
                <li><a href="#repair"><i class="fas fa-tools"></i> Sửa chữa phần cứng</a></li>
                <li><a href="#security"><i class="fas fa-shield-alt"></i> Diệt virus</a></li>
                <li><a href="#upgrade"><i class="fas fa-microchip"></i> Nâng cấp linh kiện</a></li>
                <li><a href="#software"><i class="fas fa-download"></i> Cài đặt phần mềm</a></li>
                <li><a href="#maintenance"><i class="fas fa-cogs"></i> Bảo trì định kỳ</a></li>
                <li><a href="#backup"><i class="fas fa-database"></i> Sao lưu dữ liệu</a></li>
                <li><a href="#optimize"><i class="fas fa-tachometer-alt"></i> Tối ưu hóa</a></li>
                <li><a href="#network"><i class="fas fa-wifi"></i> Cài đặt mạng</a></li>
                <li><a href="#consultation"><i class="fas fa-headset"></i> Tư vấn kỹ thuật</a></li>
            </ul>
        </div>
    </aside>

    <!-- Services Section -->
    <section class="services-section">
        <div class="section-header">
            <div class="section-label">CÁC DỊCH VỤ CỦA CHÚNG TÔI</div>
            <h2 class="section-title">Giải pháp toàn diện cho máy tính</h2>
            <p class="section-description">
                Từ vệ sinh, sửa chữa đến nâng cấp - chúng tôi cung cấp đầy đủ dịch vụ để máy tính của bạn luôn hoạt động tốt nhất.
            </p>
        </div>

        <div class="services-grid">
            <?php if($services->num_rows > 0): ?>
                <?php while($service = $services->fetch_assoc()): ?>
                    <div class="service-card">
                        <div class="service-icon">⚙️</div>
                        <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
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
</div>

<script>
function orderService(serviceId) {
    <?php if(isset($_SESSION['user_id'])): ?>
        window.location.href = '<?php echo BASE_URL; ?>page/booking.php?service_id=' + serviceId;
    <?php else: ?>
        // Show modern alert
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center';
        overlay.innerHTML = `
            <div style="background:white;padding:40px;border-radius:20px;max-width:400px;text-align:center;animation:fadeInUp 0.3s ease">
                <div style="font-size:48px;margin-bottom:20px">🔐</div>
                <h3 style="font-size:24px;margin-bottom:12px;color:#1F2937">Vui lòng đăng nhập</h3>
                <p style="color:#6B7280;margin-bottom:24px">Bạn cần đăng nhập để sử dụng dịch vụ này</p>
                <button onclick="window.location.href='login.php'" style="background:linear-gradient(135deg, #4F46E5, #4338CA);color:white;border:none;padding:12px 32px;border-radius:12px;font-weight:600;cursor:pointer;width:100%">
                    Đăng nhập ngay
                </button>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.onclick = (e) => e.target === overlay && overlay.remove();
    <?php endif; ?>
}

// Smooth scroll animation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Add scroll animation to cards
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.service-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
});
</script>

<?php include 'templates/footer.php'; ?>