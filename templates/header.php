<?php
// Luôn bắt đầu session ở header để mọi trang đều có thể kiểm tra
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Care - Dịch vụ chăm sóc máy tính chuyên nghiệp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === CÀI ĐẶT GỐC (GIỮ NGUYÊN) === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #EC4899;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --bg-light: #F9FAFB;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
        }

        /* === NÂNG CẤP: HEADER MƯỢT MÀ HƠN === */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            /* Thêm transition cho hiệu ứng đổ bóng khi cuộn */
            transition: box-shadow 0.3s ease, background-color 0.3s ease;
        }

        header.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 72px;
        }

        /* === NÂNG CẤP: HIỆU ỨNG CHUNG === */
        /* Áp dụng 1 kiểu transition mượt mà cho tất cả các phần tử tương tác */
        .logo, .icon-btn, nav a, .notification-btn, .user-avatar-btn, .logout-btn, .mobile-menu-toggle {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        /* Logo Section */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo:hover {
            /* Thêm hiệu ứng "pop" (nảy) nhẹ */
            transform: translateY(-2px) scale(1.02);
        }
        
        /* (Giữ nguyên các style logo-icon, logo-text...) */
        .logo-icon{width:44px;height:44px;background:linear-gradient(135deg, var(--primary), var(--primary-dark));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;box-shadow:0 4px 12px rgba(79, 70, 229, 0.25);}
        .logo-text{display:flex;flex-direction:column;}
        .logo-title{font-size:20px;font-weight:700;color:var(--text-dark);letter-spacing:-0.5px;}
        .logo-subtitle{font-size:11px;color:var(--text-light);font-weight:500;margin-top:-2px;}

        
        /* === NÂNG CẤP: HIỆU ỨNG "POP" 3D CHO NÚT === */
        .auth-buttons {
            display: flex;
            gap: 8px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            position: relative;
            text-decoration: none;
        }

        .icon-btn i { font-size: 16px; }

        .icon-btn:hover {
            /* Hiệu ứng "pop" 3D */
            transform: translateY(-2px) scale(1.05);
        }
        
        /* (Giữ nguyên style các nút login, register, tooltip) */
        .btn-login{background:linear-gradient(135deg, #10B981, #059669);color:white;box-shadow:0 2px 8px rgba(16, 185, 129, 0.25);}
        .btn-login:hover{box-shadow:0 4px 16px rgba(16, 185, 129, 0.4);}
        .btn-register{background:linear-gradient(135deg, var(--primary), var(--primary-dark));color:white;box-shadow:0 2px 8px rgba(79, 70, 229, 0.25);}
        .btn-register:hover{box-shadow:0 4px 16px rgba(79, 70, 229, 0.4);}
        .icon-btn::after{content:attr(data-tooltip);position:absolute;bottom:-36px;left:50%;transform:translateX(-50%) scale(0.8);background:var(--text-dark);color:white;padding:6px 12px;border-radius:6px;font-size:12px;white-space:nowrap;opacity:0;pointer-events:none;transition:all 0.3s ease;}
        .icon-btn:hover::after{opacity:1;transform:translateX(-50%) scale(1);}


        /* === NÂNG CẤP: GẠCH CHÂN ĐỘNG CHO MENU === */
        nav {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        nav a {
            color: var(--text-light);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative; /* Cần cho gạch chân */
            overflow: hidden; /* Ẩn gạch chân khi chưa active */
        }
        
        nav a i { font-size: 16px; }

        /* Tạo gạch chân giả bằng ::after */
        nav a::after {
            content: '';
            position: absolute;
            bottom: 6px; /* Khoảng cách gạch chân với chữ */
            left: 50%;
            width: 0; /* Ban đầu ẩn */
            height: 2px;
            background: var(--primary);
            border-radius: 2px;
            transform: translateX(-50%);
            transition: width 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        nav a:hover {
            color: var(--primary);
            background: rgba(79, 70, 229, 0.08); /* Giữ lại màu nền mờ khi hover */
        }
        
        /* Khi hover hoặc active, gạch chân chạy ra */
        nav a:hover::after {
            width: 50%; /* Độ rộng của gạch chân */
        }

        nav a.active {
            color: var(--primary); /* Chữ đậm màu hơn khi active */
            font-weight: 600;
            background: transparent; /* Bỏ màu nền gradient cũ */
            box-shadow: none;
        }
        
        nav a.active::after {
            width: 50%; /* Hiển thị gạch chân khi active */
        }


        /* === NÂNG CẤP: HIỆU ỨNG "POP" CHO USER SECTION === */
        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .notification-btn, .user-avatar-btn, .logout-btn {
             position: relative;
             display: flex;
             align-items: center;
             justify-content: center;
             border: none;
             cursor: pointer;
             border-radius: 10px;
             height: 40px;
        }

        .notification-btn {
             width: 40px;
             background: rgba(79, 70, 229, 0.08);
             color: var(--primary);
        }
        
        .user-avatar-btn {
             gap: 10px;
             padding: 6px 12px 6px 6px;
             background: rgba(79, 70, 229, 0.08);
        }
        
        .logout-btn {
             width: 40px;
             background: rgba(239, 68, 68, 0.08);
             color: #EF4444;
             text-decoration: none; /* Thêm cho thẻ <a> */
        }

        .notification-btn:hover, .user-avatar-btn:hover {
            transform: translateY(-2px) scale(1.05); /* Hiệu ứng pop */
            background: rgba(79, 70, 229, 0.12);
        }
        
        .logout-btn:hover {
            transform: translateY(-2px) scale(1.05); /* Hiệu ứng pop */
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* (Giữ nguyên các style badge, avatar, user-name) */
        .notification-badge{position:absolute;top:-4px;right:-4px;background:linear-gradient(135deg, #EF4444, #DC2626);color:white;border-radius:10px;padding:2px 6px;font-size:10px;font-weight:700;box-shadow:0 2px 8px rgba(239, 68, 68, 0.4);animation:pulse 2s infinite;}
        @keyframes pulse{0%, 100%{transform:scale(1);} 50%{transform:scale(1.1);}}
        .user-avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg, var(--primary), var(--primary-dark));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:14px;}
        .user-name{color:var(--text-dark);font-weight:600;font-size:14px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}


        /* === NÂNG CẤP: ICON MOBILE XOAY === */
        .mobile-menu-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(79, 70, 229, 0.08);
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 18px;
        }
        
        /* Thêm transition cho icon bên trong nút */
        .mobile-menu-toggle i {
            transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        /* Khi icon là 'fa-times' (nút X), nó sẽ xoay 90 độ */
        .mobile-menu-toggle i.fa-times {
            transform: rotate(90deg);
        }


        /* (Giữ nguyên toàn bộ media queries cho responsive) */
        @media (max-width: 1024px) {
            .header-container {
                height: 64px;
                padding: 0 16px;
            }
            .logo-section {
                gap: 16px;
            }
            .logo-subtitle {
                display: none;
            }
            nav {
                position: fixed;
                top: 64px;
                left: -100%;
                width: 280px;
                height: calc(100vh - 64px);
                background: white;
                flex-direction: column;
                padding: 20px;
                gap: 8px;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
                transition: left 0.3s ease;
                align-items: stretch;
            }
            nav.active {
                left: 0;
            }
            nav a {
                width: 100%;
                padding: 14px 16px;
                justify-content: flex-start;
            }
            /* Gạch chân trên mobile */
            nav a::after {
                bottom: 10px;
                left: 16px;
                transform: translateX(0); /* Căn trái gạch chân */
            }
            nav a:hover::after, nav a.active::after {
                width: 30%; /* Gạch chân ngắn hơn */
            }
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .auth-buttons {
                /* (Sửa lại logic này nếu cần, vì nó nằm trong nav) */
                 position: absolute;
                 bottom: 20px;
                 left: 20px;
                 right: 20px;
            }
            .user-name {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }
            .notification-btn {
                width: 36px;
                height: 36px;
            }
            .user-avatar-btn {
                padding: 4px;
            }
            .user-avatar {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <header id="mainHeader">
        <div class="header-container">
            
            <div class="logo-section">
                <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div class="logo-text">
                        <div class="logo-title">Computer Care</div>
                        <div class="logo-subtitle">Chăm sóc máy tính chuyên nghiệp</div>
                    </div>
                </a>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="auth-buttons" id="authButtons">
                        <a href="<?php echo BASE_URL; ?>page/login.php" class="icon-btn btn-login" data-tooltip="Đăng nhập">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>page/register.php" class="icon-btn btn-register" data-tooltip="Đăng ký">
                            <i class="fas fa-user-plus"></i>
                        </a>
                    </div>
                <?php endif; ?>
                
            </div>

            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>

            <nav id="mainNav">
                <a href="<?php echo BASE_URL; ?>index.php">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="<?php echo BASE_URL; ?>page/services.php">
                    <i class="fas fa-cogs"></i>
                    <span>Dịch vụ</span>
                </a>
                <a href="<?php echo BASE_URL; ?>page/my_bookings.php">
                    <i class="fas fa-history"></i>
                    <span>Lịch sử đặt lịch</span>
                </a>
                <a href="<?php echo BASE_URL; ?>page/contact.php">
                    <i class="fas fa-phone"></i>
                    <span>Liên hệ</span>
                </a>
            </nav>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-section" id="userSection">
                    
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span> 
                    </button>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/index.php" class="icon-btn btn-register" data-tooltip="Trang Quản lý Admin">
                            <i class="fas fa-tools"></i>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>page/profile.php" class="user-avatar-btn">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                        <span class="user-name">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </span>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>page/logout.php" class="logout-btn" data-tooltip="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <script>
        // Toggle Mobile Menu (Giữ nguyên)
        function toggleMobileMenu() {
            const nav = document.getElementById('mainNav');
            const toggle = document.querySelector('.mobile-menu-toggle i');
            nav.classList.toggle('active');
            
            if (nav.classList.contains('active')) {
                toggle.classList.remove('fa-bars');
                toggle.classList.add('fa-times');
            } else {
                toggle.classList.remove('fa-times');
                toggle.classList.add('fa-bars');
            }
        }

        // Close menu when clicking outside (Giữ nguyên)
        document.addEventListener('click', (e) => {
            const nav = document.getElementById('mainNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (toggle && !nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('active');
                const icon = document.querySelector('.mobile-menu-toggle i');
                if (icon) {
                     icon.classList.remove('fa-times');
                     icon.classList.add('fa-bars');
                }
            }
        });

        // Header scroll effect (Giữ nguyên)
        window.addEventListener('scroll', () => {
            const header = document.getElementById('mainHeader');
            if (window.pageYOffset > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Active link highlighting (Giữ nguyên)
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('nav a').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    </script>