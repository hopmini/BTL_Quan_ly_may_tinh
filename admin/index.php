<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

$message = '';

// XỬ LÝ CẬP NHẬT TRẠNG THÁI (Giữ nguyên)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['new_status'];
    
    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $new_status, $booking_id);
        $stmt_update->execute();
        $stmt_update->close();
        $message = "Cập nhật trạng thái thành công!";
    }
}

// 3. THỐNG KÊ TỔNG QUAN (KPIs) - Giữ nguyên
$revenue_result = $conn->query("SELECT SUM(s.price) as total_revenue FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status = 'completed'");
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

$total_bookings_result = $conn->query("SELECT COUNT(id) as total_bookings FROM bookings");
$total_bookings = $total_bookings_result->fetch_assoc()['total_bookings'] ?? 0;

$new_bookings_result = $conn->query("SELECT COUNT(id) as new_bookings FROM bookings WHERE status = 'pending'");
$new_bookings_count = $new_bookings_result->fetch_assoc()['new_bookings'] ?? 0;

$total_users_result = $conn->query("SELECT COUNT(id) as total_users FROM users WHERE role = 'user'");
$total_users = $total_users_result->fetch_assoc()['total_users'] ?? 0;


// 4. NÂNG CẤP: LẤY DỮ LIỆU CHO CÁC KHU VỰC

// KHU VỰC 1: CÁC ĐƠN HÀNG MỚI (CHỜ XÁC NHẬN)
// Ưu tiên đơn cũ nhất (để xử lý trước)
$stmt_pending = $conn->prepare(
    "SELECT b.id, b.booking_date, b.notes, u.name as user_name, u.phone as user_phone, s.name as service_name, s.price as service_price
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN services s ON b.service_id = s.id
     WHERE b.status = 'pending'
     ORDER BY b.created_at ASC" // Đơn cũ nhất lên đầu
);
$stmt_pending->execute();
$pending_bookings_result = $stmt_pending->get_result();
$pending_bookings = $pending_bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt_pending->close();

// KHU VỰC 2: DỊCH VỤ ĐƯỢC ĐẶT NHIỀU NHẤT (TOP 5)
$top_services_result = $conn->query(
    "SELECT s.name, COUNT(b.service_id) as booking_count
     FROM bookings b
     JOIN services s ON b.service_id = s.id
     GROUP BY b.service_id
     ORDER BY booking_count DESC
     LIMIT 5"
);
$top_services = $top_services_result->fetch_all(MYSQLI_ASSOC);

// KHU VỰC 3: KHÁCH HÀNG ĐẶT NHIỀU NHẤT (TOP 5)
$top_users_result = $conn->query(
    "SELECT u.name, u.email, COUNT(b.user_id) as booking_count
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     GROUP BY b.user_id
     ORDER BY booking_count DESC
     LIMIT 5"
);
$top_users = $top_users_result->fetch_all(MYSQLI_ASSOC);

// KHU VỰC 4: LỊCH SỬ CÁC ĐƠN HÀNG ĐÃ XỬ LÝ (Không phải pending)
$stmt_history = $conn->prepare(
    "SELECT b.id, b.booking_date, b.status, u.name as user_name, s.name as service_name, s.price as service_price
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN services s ON b.service_id = s.id
     WHERE b.status != 'pending'
     ORDER BY b.created_at DESC" // Đơn mới nhất lên đầu
);
$stmt_history->execute();
$history_bookings_result = $stmt_history->get_result();
$history_bookings = $history_bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();


// (Hàm dịch trạng thái và mảng status giữ nguyên)
function translate_status($status) {
    switch ($status) {
        case 'pending': return ['text' => 'Chờ xác nhận', 'color' => '#f39c12'];
        case 'confirmed': return ['text' => 'Đã xác nhận', 'color' => '#2980b9'];
        case 'processing': return ['text' => 'Đang xử lý', 'color' => '#8e44ad'];
        case 'completed': return ['text' => 'Đã hoàn thành', 'color' => '#27ae60'];
        case 'cancelled': return ['text' => 'Đã hủy', 'color' => '#c0392b'];
        default: return ['text' => ucfirst($status), 'color' => '#7f8c8d'];
    }
}
$status_options = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];

include '../templates/header.php'; // 5. HIỂN THỊ GIAO DIỆN
?>

<style>
.admin-container {
    min-height: calc(100vh - 72px);
    padding: 30px 20px;
    background: var(--bg-light);
}
.admin-content {
    max-width: 1600px; /* Rộng hơn cho dashboard */
    margin: 0 auto;
}
.admin-box {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}
.admin-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}
.admin-header i { font-size: 22px; color: var(--primary); }
.admin-header h2 { font-size: 20px; font-weight: 700; color: #333; margin: 0; }

/* 1. KPI Grid (Giữ nguyên) */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}
.kpi-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: flex-start;
    gap: 20px;
}
.kpi-card .kpi-icon { font-size: 24px; color: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.kpi-card .kpi-info .kpi-value { font-size: 26px; font-weight: 700; color: var(--text-dark); }
.kpi-card .kpi-info .kpi-title { font-size: 14px; color: var(--text-light); }
.kpi-card.revenue .kpi-icon { background: linear-gradient(135deg, #27ae60, #2ecc71); }
.kpi-card.new-bookings .kpi-icon { background: linear-gradient(135deg, #f39c12, #f1c40f); }
.kpi-card.total-bookings .kpi-icon { background: linear-gradient(135deg, #2980b9, #3498db); }
.kpi-card.total-users .kpi-icon { background: linear-gradient(135deg, #8e44ad, #9b59b6); }

/* 2. KHU VỰC HÀNH ĐỘNG (Layout 2 cột) */
.admin-main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr; /* Cột trái 66%, Cột phải 33% */
    gap: 25px;
    margin-bottom: 25px;
}

/* 2a. Cột trái: Đơn hàng mới */
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; color: var(--text-dark); }

.user-info strong { font-size: 14px; color: var(--text-dark); }
.user-info .user-contact { font-size: 13px; color: var(--text-light); display: block; }
.service-info strong { font-size: 14px; color: var(--primary); }
.service-price { font-weight: 700; color: #27ae60; }
.booking-notes { font-size: 13px; color: #777; font-style: italic; max-width: 200px; white-space: normal; }

.status-form { display: flex; gap: 8px; min-width: 250px; }
.status-select { flex-grow: 1; padding: 8px 12px; font-size: 14px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.btn-update { padding: 8px 15px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.3s ease; }
.btn-update:hover { background: var(--primary-dark); }
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: white; min-width: 110px; text-align: center; }

/* 2b. Cột phải: Tóm tắt */
.summary-grid {
    display: flex;
    flex-direction: column;
    gap: 25px;
}
.summary-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.summary-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}
.summary-list li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.summary-list .item-info {
    font-size: 14px;
    color: var(--text-dark);
    font-weight: 500;
}
.summary-list .item-info span {
    font-size: 12px;
    color: var(--text-light);
    display: block;
}
.summary-list .item-count {
    font-size: 16px;
    font-weight: 700;
    color: var(--primary);
}

/* Thông báo */
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; background: #e0f2fe; color: #0c546b; border: 1px solid #bee5eb; font-size: 15px; }

/* Responsive */
@media (max-width: 1200px) {
    .admin-main-grid {
        grid-template-columns: 1fr; /* Xếp chồng 2 cột chính */
    }
}
@media (max-width: 768px) {
    .kpi-grid { grid-template-columns: 1fr; } /* Thẻ KPI xếp chồng */
    .admin-table { display: block; overflow-x: auto; white-space: nowrap; }
}
</style>
<div class="admin-container">
    <div class="admin-content">

        <?php if ($message): ?>
            <div class="message-box" style="background: #efe; color: #3c3; border: 1px solid #cfc;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo number_format($total_revenue, 0, ",", "."); ?> VNĐ</div>
                    <div class="kpi-title">Tổng doanh thu</div>
                </div>
            </div>
            <div class="kpi-card new-bookings">
                <div class="kpi-icon"><i class="fas fa-inbox"></i></div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo $new_bookings_count; ?></div>
                    <div class="kpi-title">Đơn hàng mới</div>
                </div>
            </div>
            <div class="kpi-card total-bookings">
                <div class="kpi-icon"><i class="fas fa-tasks"></i></div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo $total_bookings; ?></div>
                    <div class="kpi-title">Tổng số đơn</div>
                </div>
            </div>
            <div class="kpi-card total-users">
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
                <div class="kpi-info">
                    <div class="kpi-value"><?php echo $total_users; ?></div>
                    <div class="kpi-title">Tổng khách hàng</div>
                </div>
            </div>
        </div>

        <div class="admin-main-grid">
            
            <div class="admin-box">
                <div class="admin-header">
                    <i class="fas fa-clock" style="color: #f39c12;"></i>
                    <h2>Đơn hàng mới chờ xác nhận (<?php echo count($pending_bookings); ?>)</h2>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Dịch vụ & Ghi chú</th>
                                <th>Giá</th>
                                <th>Ngày hẹn</th>
                                <th>Xử lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_bookings)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 20px;">Không có đơn hàng mới nào.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($pending_bookings as $booking): ?>
                                <tr>
                                    <td class="user-info">
                                        <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong>
                                        <span class="user-contact"><?php echo htmlspecialchars($booking['user_phone']); ?></span>
                                    </td>
                                    <td class="service-info">
                                        <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                                        <div class="booking-notes"><?php echo !empty($booking['notes']) ? htmlspecialchars($booking['notes']) : '<i>(Không có)</i>'; ?></div>
                                    </td>
                                    <td><strong class="service-price"><?php echo number_format($booking['service_price'], 0, ",", "."); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <form method="POST" action="index.php" class="status-form">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <select name="new_status" class="status-select">
                                                <option value="pending" selected>Chờ xác nhận</option>
                                                <option value="confirmed">Đã xác nhận</option>
                                                <option value="cancelled">Hủy bỏ</option>
                                            </select>
                                            <button type="submit" class="btn-update">Lưu</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="summary-grid">
                <div class="admin-box">
                    <div class="admin-header">
                        <i class="fas fa-fire" style="color: #e74c3c;"></i>
                        <h2>Dịch vụ "Hot" (Top 5)</h2>
                    </div>
                    <ul class="summary-list">
                        <?php if (empty($top_services)): ?>
                            <li>Không có dữ liệu</li>
                        <?php endif; ?>
                        <?php foreach ($top_services as $service): ?>
                            <li>
                                <div class="item-info"><?php echo htmlspecialchars($service['name']); ?></div>
                                <span class="item-count"><?php echo $service['booking_count']; ?> lượt</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="admin-box">
                    <div class="admin-header">
                        <i class="fas fa-user-check" style="color: #27ae60;"></i>
                        <h2>Khách hàng Tiềm năng (Top 5)</h2>
                    </div>
                    <ul class="summary-list">
                        <?php if (empty($top_users)): ?>
                            <li>Không có dữ liệu</li>
                        <?php endif; ?>
                        <?php foreach ($top_users as $user): ?>
                            <li>
                                <div class="item-info">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <span class="item-count"><?php echo $user['booking_count']; ?> đơn</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="admin-box">
            <div class="admin-header">
                <i class="fas fa-history"></i>
                <h2>Lịch sử Đơn hàng (Đã xử lý)</h2>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Dịch vụ</th>
                            <th>Giá tiền</th>
                            <th>Ngày hẹn</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history_bookings)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px;">Chưa có đơn hàng nào được xử lý.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($history_bookings as $booking): ?>
                            <tr>
                                <td class="user-info"><strong><?php echo htmlspecialchars($booking['user_name']); ?></strong></td>
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

<?php include '../templates/footer.php'; ?>