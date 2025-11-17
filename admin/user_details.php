<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

// BẮT BUỘC: Lấy ID của user từ URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . BASE_URL . "admin/users.php"); // Nếu không có ID, quay về trang user
    exit();
}
$user_id = (int)$_GET['id'];
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all'; // (Dùng cho menu)

// === 1. LẤY THÔNG TIN CHI TIẾT CỦA USER NÀY ===
$stmt_user = $conn->prepare(
    "SELECT u.*, COUNT(b.id) as total_bookings
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id
     WHERE u.id = ?
     GROUP BY u.id"
);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();

// Nếu không tìm thấy User ID, quay về
if (!$user) {
    header("Location: " . BASE_URL . "admin/users.php?error=" . urlencode("Không tìm thấy người dùng!"));
    exit();
}

// === 2. LẤY TOÀN BỘ LỊCH SỬ ĐẶT HÀNG CỦA USER NÀY ===
$stmt_bookings = $conn->prepare(
    "SELECT b.id, b.booking_date, b.status, b.notes, s.name as service_name, s.price as service_price
     FROM bookings b
     JOIN services s ON b.service_id = s.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
$stmt_bookings->bind_param("i", $user_id);
$stmt_bookings->execute();
$bookings_result = $stmt_bookings->get_result();
$all_bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt_bookings->close();

// (Hàm dịch trạng thái - copy từ admin/index.php)
function translate_status($status) {
    switch ($status) {
        case 'pending': return ['text' => 'Chờ xác nhận', 'color' => '#f39c12'];
        case 'confirmed': return ['text' => 'Đã xác nhận', 'color' => '#2980b9'];
        case 'processing': return ['text' => 'Đang xử lý', 'color' => '#8e44ad'];
        case 'completed': return ['text' => 'Đã hoàn thành', 'color' => '#27ae60'];
        case 'cancelled': return ['text' => 'Đã hủy', 'color' => '#c0392b'];
        case 'pending_cancellation': return ['text' => 'CHỜ HỦY', 'color' => '#e67e22']; 
        default: return ['text' => ucfirst($status), 'color' => '#7f8c8d'];
    }
}

include '../templates/header.php';
?>

<style>
/* Menu Admin (Giữ nguyên) */
.admin-nav { background: #343a40; padding: 10px 0; position: sticky; top: 72px; z-index: 999; margin-bottom: 30px; }
.admin-nav-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: center; gap: 15px; }
.admin-nav a { color: #f8f9fa; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: background-color 0.3s ease; }
.admin-nav a:hover { background-color: #495057; }
.admin-nav a.active { background-color: var(--primary, #4F46E5); color: white; }

/* Layout chung */
.admin-container { min-height: calc(100vh - 72px); padding: 30px 20px; background: var(--bg-light); }
.admin-content { max-width: 1400px; margin: 0 auto; }
.admin-box { background: white; border-radius: 20px; padding: 30px 40px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); margin-bottom: 30px; }
.admin-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.admin-header i { font-size: 22px; color: var(--primary); }
.admin-header h2 { font-size: 20px; font-weight: 700; color: #333; margin: 0; }

/* NÂNG CẤP: Giao diện chi tiết (2 cột) */
.user-detail-grid {
    display: grid;
    grid-template-columns: 350px 1fr; /* Cột 1 cố định 350px, cột 2 co giãn */
    gap: 30px;
}

/* CỘT 1: Thẻ Profile */
.profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    padding: 30px;
    text-align: center;
    position: sticky; /* Dính khi cuộn */
    top: 120px; /* 72px (header) + 48px (admin-nav) */
}
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    font-size: 48px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.profile-name {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 5px;
}
.profile-username {
    font-size: 16px;
    color: var(--text-light);
    margin-bottom: 20px;
}
/* Huy hiệu (Badges) */
.profile-badges {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 25px;
}
.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}
.badge-admin { background: #e74c3c; }
.badge-nhanvien { background: #8e44ad; }
.badge-user { background: #3498db; }
.badge-vip { background: #27ae60; }
.badge-blacklist { background: #34495e; text-decoration: line-through; }

.profile-info {
    text-align: left;
    font-size: 15px;
    line-height: 1.7;
}
.profile-info-item {
    margin-bottom: 15px;
    color: var(--text-dark);
}
.profile-info-item strong {
    color: var(--text-light);
    width: 80px; /* Căn lề */
    display: inline-block;
}

/* CỘT 2: Bảng Lịch sử Đơn hàng */
.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; }
.service-info strong { font-size: 14px; color: var(--primary); }
.service-price { font-weight: 700; color: #27ae60; }
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: white; min-width: 110px; text-align: center; }

@media (max-width: 1024px) {
    .user-detail-grid { grid-template-columns: 1fr; }
    .profile-card { position: static; } /* Bỏ dính */
}
</style>
<div class="admin-nav">
            <div class="admin-nav-container">
                <a href="<?php echo BASE_URL; ?>admin/index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>admin/services.php">
                    <i class="fas fa-cogs"></i> Quản lý Dịch vụ
                </a>
                <a href="<?php echo BASE_URL; ?>admin/users.php" class="active">
                    <i class="fas fa-users-cog"></i> Quản lý User
                </a>
                <a href="<?php echo BASE_URL; ?>admin/contacts.php">
                    <i class="fas fa-envelope"></i> Quản lý Tin nhắn
                </a>
                <a href="<?php echo BASE_URL; ?>admin/posts.php">
                    <i class="fas fa-newspaper"></i> Quản lý Blog
                </a>
            </div>
        </div>
</div>
<div class="admin-container">
    <div class="admin-content">

        <div class="user-detail-grid">

            <aside class="profile-card">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>

                <div class="profile-badges">
                    <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                    <?php if ($user['is_vip']): ?>
                        <span class="badge badge-vip"><i class="fas fa-star"></i> VIP</span>
                    <?php endif; ?>
                    <?php if ($user['is_blacklisted']): ?>
                        <span class="badge badge-blacklist"><i class="fas fa-ban"></i> Blacklisted</span>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <div class="profile-info-item">
                        <strong><i class="fas fa-envelope"></i> Email:</strong>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="profile-info-item">
                        <strong><i class="fas fa-phone"></i> SĐT:</strong>
                        <span><?php echo htmlspecialchars($user['phone'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="profile-info-item">
                        <strong><i class="fas fa-map-marker-alt"></i> Địa chỉ:</strong>
                        <span><?php echo htmlspecialchars($user['address'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="profile-info-item">
                        <strong><i class="fas fa-calendar-alt"></i> Tham gia:</strong>
                        <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="profile-info-item">
                        <strong><i class="fas fa-tasks"></i> Tổng đơn:</strong>
                        <span><?php echo $user['total_bookings']; ?> đơn hàng</span>
                    </div>
                </div>
            </aside>

            <div class="admin-box">
                <div class="admin-header">
                    <i class="fas fa-history"></i>
                    <h2>Lịch sử Đặt hàng của <?php echo htmlspecialchars($user['name']); ?></h2>
                </div>
                
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID Đơn</th>
                                <th>Dịch vụ</th>
                                <th>Giá tiền</th>
                                <th>Ngày hẹn</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_bookings)): ?>
                                <tr><td colspan="5" style="text-align: center;">Người dùng này chưa đặt đơn hàng nào.</td></tr>
                            <?php endif; ?>

                            <?php foreach ($all_bookings as $booking): ?>
                                <tr>
                                    <td><strong>#<?php echo $booking['id']; ?></strong></td>
                                    <td class="service-info"><strong><?php echo htmlspecialchars($booking['service_name']); ?></strong></td>
                                    <td><strong class="service-price"><?php echo number_format($booking['service_price'], 0, ",", "."); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <?php $status_info = translate_status($booking['status']); ?>
                                        <span class="status-badge" style="background-color: <?php echo $status_info['color']; ?>">
                                            <?php echo $status_info['text']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../templates/footer.php'; ?>