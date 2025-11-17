<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG

// 2. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = BASE_URL . 'page/notifications.php';
    header("Location: " . BASE_URL . "page/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// === 3. TỰ ĐỘNG ĐÁNH DẤU LÀ "ĐÃ ĐỌC" ===
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id AND is_read = 0");


// === 4. LẤY TẤT CẢ LỊCH SỬ THÔNG BÁO ===
$stmt = $conn->prepare(
    "SELECT * FROM notifications 
     WHERE user_id = ? 
     ORDER BY created_at DESC" // Thông báo mới nhất lên đầu
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// =============================================
// ===== 5. NÂNG CẤP: HÀM CHỌN ICON & MÀU =====
// =============================================
function getNotificationDetails($message) {
    $message_lower = strtolower($message); // Chuyển sang chữ thường để so sánh
    
    // Kiểm tra từ khóa
    if (str_contains($message_lower, 'mới')) {
        return ['icon' => 'fas fa-inbox', 'color' => '#f39c12']; // Cam - Đơn mới
    }
    if (str_contains($message_lower, 'hoàn thành')) {
        return ['icon' => 'fas fa-star', 'color' => '#27ae60']; // Xanh lá - Hoàn thành
    }
    if (str_contains($message_lower, 'xác nhận')) {
        return ['icon' => 'fas fa-check-circle', 'color' => '#2980b9']; // Xanh dương - Xác nhận
    }
    if (str_contains($message_lower, 'hủy')) {
        return ['icon' => 'fas fa-times-circle', 'color' => '#c0392b']; // Đỏ - Hủy
    }
    
    // Mặc định (nếu không khớp)
    return ['icon' => 'fas fa-info-circle', 'color' => '#7f8c8d']; // Xám
}
// =============================================


// 7. GỌI HEADER (PHẢI SAU KHI XỬ LÝ LOGIC)
include '../templates/header.php';
?>

<style>
/* (Giữ nguyên .notifications-container, .content, .header) */
.notifications-container {
    min-height: calc(100vh - 72px);
    padding: 40px 20px;
    background: var(--bg-light); 
}
.notifications-content {
    max-width: 800px; 
    margin: 0 auto;
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}
.notifications-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}
.notifications-header i { font-size: 24px; color: var(--primary); }
.notifications-header h2 { font-size: 24px; color: #333; margin: 0; }

/* (Giữ nguyên .notification-list, .notification-item) */
.notification-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.notification-item {
    display: flex;
    gap: 20px;
    padding: 20px 0;
    border-bottom: 1px solid #f0f0f0;
}
.notification-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

/* === SỬA LẠI CSS ICON === */
.notification-icon {
    font-size: 20px;
    /* (Xóa bỏ màu 'color' cố định) */
    margin-top: 5px;
}
/* === KẾT THÚC SỬA CSS ICON === */

.notification-details {
    flex-grow: 1;
}
.notification-message {
    font-size: 16px;
    color: var(--text-dark);
    line-height: 1.6;
    margin-bottom: 5px;
}
.notification-time {
    font-size: 13px;
    color: var(--text-light);
    font-style: italic;
}
.notification-link {
    text-decoration: none;
    color: inherit;
}

/* NÂNG CẤP: Tin CHƯA ĐỌC sẽ có font đậm */
/* (Xóa class .is-read cũ và thay bằng .unread) */
.notification-item.unread .notification-message {
    font-weight: 600; /* In đậm chữ */
    color: var(--text-dark);
}
/* Tin đã đọc sẽ mờ đi */
.notification-item.read {
    opacity: 0.7;
}
.notification-item.read .notification-icon {
    color: var(--text-light) !important; /* Ghi đè màu, cho mờ đi */
}


/* (Giữ nguyên .empty-state) */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}
.empty-state p {
    font-size: 16px;
    color: var(--text-light);
}
</style>
<div class="notifications-container">
    <div class="notifications-content">
        <div class="notifications-header">
            <i class="fas fa-bell"></i>
            <h2>Thông báo của bạn</h2>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>Bạn chưa có thông báo nào.</p>
            </div>
        <?php else: ?>
            <ul class="notification-list">
                <?php foreach ($notifications as $noti): ?>
                    
                    <?php 
                        // === NÂNG CẤP: GỌI HÀM ===
                        // Lấy thông tin icon và màu sắc
                        $details = getNotificationDetails($noti['message']); 
                    ?>

                    <li class="notification-item <?php echo $noti['is_read'] ? 'read' : 'unread'; ?>">
                        
                        <div class="notification-icon" style="color: <?php echo $details['color']; ?>">
                            <i class="<?php echo $details['icon']; ?>"></i>
                        </div>

                        <div class="notification-details">
                            <a href="<?php echo BASE_URL . htmlspecialchars($noti['link']); ?>" class="notification-link">
                                <p class="notification-message">
                                    <?php echo htmlspecialchars($noti['message']); ?>
                                </p>
                                <span class="notification-time">
                                    <?php echo date('d/m/Y \l\ú\c H:i', strtotime($noti['created_at'])); ?>
                                </span>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div>
</div>

<?php include '../templates/footer.php'; ?>