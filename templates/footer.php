</main> <style>
/* CSS cho Footer mới */
.site-footer {
    background-color: #1F2937; /* Màu nền tối (lấy từ --text-dark) */
    color: var(--text-light, #9CA3AF); /* Màu chữ sáng mờ */
    padding: 60px 20px 20px;
    font-size: 15px;
    line-height: 1.6;
    margin-top: 40px; /* Tạo khoảng cách với nội dung bên trên */
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    /* Chia 4 cột, cột đầu (giới thiệu) rộng hơn */
    grid-template-columns: 1.5fr 1fr 1fr 1.5fr; 
    gap: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid #374151; /* Đường kẻ phân cách */
}

.footer-column h4 {
    font-size: 18px;
    font-weight: 600;
    color: #FFFFFF; /* Màu trắng */
    margin-bottom: 20px;
    position: relative;
}
/* Gạch chân nhỏ dưới tiêu đề cột */
.footer-column h4::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -8px;
    width: 40px;
    height: 2px;
    background: var(--primary, #4F46E5); /* Dùng màu tím chủ đạo */
}

/* Cột 1: Giới thiệu */
.footer-about .logo-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    margin-bottom: 15px;
}
.footer-about p {
    margin-bottom: 20px;
}
.footer-socials {
    display: flex;
    gap: 12px;
}
.footer-socials a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #374151; /* Màu xám đậm */
    color: #FFFFFF;
    border-radius: 50%;
    text-decoration: none;
    font-size: 16px;
    transition: all 0.3s ease;
}
.footer-socials a:hover {
    background: var(--primary, #4F46E5);
    transform: translateY(-3px);
}

/* Cột 2 & 3: Menu Links */
.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}
.footer-links li {
    margin-bottom: 12px;
}
.footer-links a {
    color: var(--text-light, #9CA3AF);
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}
.footer-links a::before {
    content: '›'; /* Icon mũi tên */
    font-weight: 700;
    color: var(--primary, #4F46E5);
    transition: all 0.3s ease;
}
.footer-links a:hover {
    color: #FFFFFF;
    padding-left: 5px; /* Dịch sang phải khi hover */
}
.footer-links a:hover::before {
    transform: rotate(90deg);
}

/* Cột 4: Thông tin liên hệ */
.contact-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.contact-info-list li {
    display: flex;
    align-items: flex-start; /* Căn trên cho icon và text */
    gap: 15px;
    margin-bottom: 20px;
}
.contact-info-list i {
    font-size: 18px;
    color: var(--primary, #4F46E5);
    margin-top: 5px; /* Căn chỉnh icon */
}

/* Copyright (dòng cuối cùng) */
.footer-bottom {
    text-align: center;
    padding-top: 30px;
    font-size: 14px;
}
.footer-bottom p {
    margin: 0;
}
.footer-bottom a {
    color: var(--primary, #4F46E5);
    text-decoration: none;
    font-weight: 500;
}
.footer-bottom a:hover {
    text-decoration: underline;
}

/* Responsive cho Footer */
@media (max-width: 992px) {
    .footer-container {
        /* 2 cột */
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .footer-container {
        /* 1 cột */
        grid-template-columns: 1fr;
    }
    .footer-column {
        margin-bottom: 20px; /* Thêm khoảng cách khi xếp chồng */
    }
}
</style>

<footer class="site-footer">
    <div class="footer-container">
        
        <div class="footer-column footer-about">
            <div class="logo-icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <h4>Computer Care</h4>
            <p>Đối tác tin cậy cho mọi nhu cầu sửa chữa, bảo trì và nâng cấp máy tính của bạn. Dịch vụ chuyên nghiệp, giá cả minh bạch.</p>
            <div class="footer-socials">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Zalo"><i class="fas fa-comment"></i></a>
                <a href="#" title="Youtube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

        <div class="footer-column">
            <h4>Links Nhanh</h4>
            <ul class="footer-links">
                <li><a href="<?php echo BASE_URL; ?>index.php">Trang chủ</a></li>
                <li><a href="<?php echo BASE_URL; ?>page/about.php">Về chúng tôi</a></li>
                <li><a href="<?php echo BASE_URL; ?>page/blog.php">Blog/Tin tức</a></li>
                <li><a href="<?php echo BASE_URL; ?>page/contact.php">Liên hệ</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Dịch vụ chính</h4>
            <ul class="footer-links">
                <li><a href="<?php echo BASE_URL; ?>page/services.php">Vệ sinh & Bảo trì</a></li>
                <li><a href="<?php echo BASE_URL; ?>page/services.php">Cài đặt Phần mềm</a></li>
                <li><a href="<?php echo BASE_URL; ?>page/services.php">Nâng cấp SSD & RAM</a></li>
                <li><a href="<?php echo BASE_URL; ?>page/services.php">Sửa chữa Phần cứng</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4>Thông tin liên hệ</h4>
            <ul class="contact-info-list">
                <li>
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 Đường ABC, Quận XYZ, TP. Hồ Chí Minh</span>
                </li>
                <li>
                    <i class="fas fa-phone"></i>
                    <span>(028) 3812 3456</span>
                </li>
                <li>
                    <i class="fas fa-envelope"></i>
                    <span>hotro@computercare.com</span>
                </li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Computer Care. Đã đăng ký bản quyền. | Thiết kế bởi <a href="#">Tên của bạn</a></p>
    </div>
</footer>
</body>
</html>