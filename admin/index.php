<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

$message = '';
$error = '';

// === 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI (POST) ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // A. Xử lý Cập nhật TRẠNG THÁI ĐƠN HÀNG
    if ($_POST['action'] == 'update_status' && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
        $booking_id = (int)$_POST['booking_id'];
        $new_status = $_POST['new_status'];
        
        $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_status, $booking_id);
            if($stmt_update->execute()) {
                $message = "Cập nhật trạng thái thành công!";
            } else {
                $error = "Lỗi khi cập nhật trạng thái.";
            }
            $stmt_update->close();
        }
    }
}

// === 2. LẤY DỮ LIỆU THỐNG KÊ (KPIs) ===
$revenue_result = $conn->query("SELECT SUM(s.price) as total_revenue FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status = 'completed'");
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;
$total_bookings_result = $conn->query("SELECT COUNT(id) as total_bookings FROM bookings");
$total_bookings = $total_bookings_result->fetch_assoc()['total_bookings'] ?? 0;
$new_bookings_result = $conn->query("SELECT COUNT(id) as new_bookings FROM bookings WHERE status = 'pending'");
$new_bookings_count = $new_bookings_result->fetch_assoc()['new_bookings'] ?? 0;
$total_users_result = $conn->query("SELECT COUNT(id) as total_users FROM users WHERE role = 'user'");
$total_users = $total_users_result->fetch_assoc()['total_users'] ?? 0;


// === 3. LẤY DỮ LIỆU CHO CÁC BOX TÓM TẮT ===

// KHU VỰC 1: DỊCH VỤ ĐƯỢC ĐẶT NHIỀU NHẤT (TOP 5)
$top_services_result = $conn->query(
    "SELECT s.id, s.name, COUNT(b.service_id) as booking_count
     FROM bookings b
     JOIN services s ON b.service_id = s.id
     GROUP BY b.service_id
     ORDER BY booking_count DESC
     LIMIT 5"
);
$top_services = $top_services_result->fetch_all(MYSQLI_ASSOC);

// KHU VỰC 2: KHÁCH HÀNG ĐẶT NHIỀU NHẤT (TOP 5)
$top_users_result = $conn->query(
    "SELECT u.name, u.email, COUNT(b.user_id) as booking_count
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     GROUP BY b.user_id
     ORDER BY booking_count DESC
     LIMIT 5"
);
$top_users = $top_users_result->fetch_all(MYSQLI_ASSOC);


// === 4. LẤY DANH SÁCH ĐƠN HÀNG (THEO TAB) ===
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all'; // Tab mặc định là 'all'
$where_clause = ''; // Mệnh đề WHERE cho SQL

switch ($current_tab) {
    case 'pending':
        $where_clause = "WHERE b.status = 'pending'";
        break;
    case 'processing':
        $where_clause = "WHERE b.status IN ('confirmed', 'processing')";
        break;
    case 'completed':
        $where_clause = "WHERE b.status = 'completed'";
        break;
    case 'cancelled':
        $where_clause = "WHERE b.status = 'cancelled'";
        break;
    case 'all':
    default:
        $where_clause = ""; // Không có WHERE, lấy tất cả
        break;
}

$stmt_all_bookings = $conn->prepare(
    "SELECT 
        b.id, b.booking_date, b.status, b.notes, 
        b.device_type, b.device_brand, b.service_method,
        u.name as user_name, u.email as user_email, u.phone as user_phone,
        s.name as service_name, s.price as service_price
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN services s ON b.service_id = s.id
     $where_clause
     ORDER BY b.created_at DESC"
);

$stmt_all_bookings->execute();
$all_bookings_result = $stmt_all_bookings->get_result();
$all_bookings = $all_bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt_all_bookings->close();


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

<div class="admin-nav">
    <div class="admin-nav-container">
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="active">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>admin/services.php">
            <i class="fas fa-cogs"></i> Quản lý Dịch vụ
        </a>
        <a href="<?php echo BASE_URL; ?>admin/users.php">
            <i class="fas fa-users-cog"></i> Quản lý User
        </a>
    </div>
</div>
<style>
/* === CSS CHO MENU ADMIN (MỚI) === */
.admin-nav {
    background: #343a40;
    padding: 10px 0;

    /* 1. Biến nó thành thanh "dính" */
    position: sticky; 

    /* 2. Dính vào vị trí 72px (chiều cao của header) */
    top: 72px; 

    /* 3. Đặt z-index thấp hơn header (header là 1000) */
    z-index: 999; 

    margin-bottom: 30px; /* Giữ lại margin-bottom */
}
.admin-nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: center; /* Căn giữa menu */
    gap: 15px;
}
.admin-nav a {
    color: #f8f9fa; /* Màu chữ sáng */
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    transition: background-color 0.3s ease;
}
.admin-nav a:hover {
    background-color: #495057; /* Màu nền khi hover */
}
.admin-nav a.active {
    background-color: var(--primary, #4F46E5); /* Dùng màu chủ đạo */
    color: white;
}
/* === HẾT CSS MENU ADMIN === */


/* Kế thừa từ code cũ của bạn */
.admin-container { min-height: calc(100vh - 72px); padding: 30px 20px; background: var(--bg-light); }
.admin-content { max-width: 1600px; margin: 0 auto; }
.admin-box { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); margin-bottom: 30px; }
.admin-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.admin-header i { font-size: 22px; color: var(--primary); }
.admin-header h2 { font-size: 20px; font-weight: 700; color: #333; margin: 0; }
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; background: #e0f2fe; color: #0c546b; border: 1px solid #bee5eb; font-size: 15px; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; margin-bottom: 25px; }
.kpi-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); display: flex; align-items: flex-start; gap: 20px; }
.kpi-card .kpi-icon { font-size: 24px; color: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.kpi-card .kpi-info .kpi-value { font-size: 26px; font-weight: 700; color: var(--text-dark); }
.kpi-card .kpi-info .kpi-title { font-size: 14px; color: var(--text-light); }
.kpi-card.revenue .kpi-icon { background: linear-gradient(135deg, #27ae60, #2ecc71); }
.kpi-card.new-bookings .kpi-icon { background: linear-gradient(135deg, #f39c12, #f1c40f); }
.kpi-card.total-bookings .kpi-icon { background: linear-gradient(135deg, #2980b9, #3498db); }
.kpi-card.total-users .kpi-icon { background: linear-gradient(135deg, #8e44ad, #9b59b6); }

/* NÂNG CẤP: Layout 2 cột cho các box phụ */
.admin-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

/* NÂNG CẤP: CSS cho các nút Sửa/Xóa Dịch vụ */
.summary-list { list-style: none; padding: 0; margin: 0; }
.summary-list li { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
.summary-list li:last-child { border-bottom: none; padding-bottom: 0; }
.summary-list .item-info { font-size: 14px; color: var(--text-dark); font-weight: 500; }
.summary-list .item-info span { font-size: 12px; color: var(--text-light); display: block; }
.summary-list .item-count { font-size: 16px; font-weight: 700; color: var(--primary); }
.service-quick-actions { display: flex; gap: 8px; align-items: center; }
.service-quick-actions .btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.3s ease;
}
.service-quick-actions .btn-edit { background: #e0f2fe; color: #2980b9; }
.service-quick-actions .btn-edit:hover { background: #2980b9; color: white; }
.service-quick-actions .btn-delete { background: #fee; color: #c0392b; }
.service-quick-actions .btn-delete:hover { background: #c0392b; color: white; }

/* NÂNG CẤP: CSS cho Tab điều hướng bảng */
.table-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: -1px; /* Để nó đè lên border của box */
    position: relative;
    z-index: 2;
}
.table-tabs a {
    padding: 12px 20px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    color: var(--text-light);
    border: 1px solid #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
    border-radius: 10px 10px 0 0;
    background: #f8f9fa;
    transition: all 0.3s ease;
}
.table-tabs a.active {
    color: var(--primary);
    background: white;
    border-bottom-color: white; /* Che đường viền */
}
.table-tabs a:hover:not(.active) {
    background: #eee;
    color: var(--text-dark);
}
.table-wrapper {
    overflow-x: auto;
    border: 1px solid #e0e0e0;
    border-radius: 0 10px 10px 10px;
    position: relative;
    z-index: 1;
}

/* NÂNG CẤP: Bảng chính */
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: top; /* Căn trên cho dễ đọc */
    white-space: nowrap; /* Ngăn vỡ dòng */
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; color: var(--text-dark); }
.admin-table .notes {
    white-space: normal; /* Cho phép ghi chú xuống dòng */
    min-width: 200px;
    font-size: 13px;
    color: #777;
    font-style: italic;
}
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: white; min-width: 110px; text-align: center; }
.status-form { display: flex; gap: 8px; min-width: 250px; }
.status-select { flex-grow: 1; padding: 8px 12px; font-size: 14px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.btn-update { padding: 8px 15px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.3s ease; }
.btn-update:hover { background: var(--primary-dark); }

/* Responsive */
@media (max-width: 1200px) {
    .admin-summary-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .kpi-grid { grid-template-columns: 1fr; }
}
</style>
<div class="admin-container">
    <div class="admin-content">

        <?php if ($message): ?>
            <div class="message-box" style="background: #efe; color: #3c3; border: 1px solid #cfc;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message-box" style="background: #fee; color: #c33; border: 1px solid #fcc;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="kpi-info"><div class="kpi-value"><?php echo number_format($total_revenue, 0, ",", "."); ?> VNĐ</div><div class="kpi-title">Tổng doanh thu</div></div>
            </div>
            <div class="kpi-card new-bookings">
                <div class="kpi-icon"><i class="fas fa-inbox"></i></div>
                <div class="kpi-info"><div class="kpi-value"><?php echo $new_bookings_count; ?></div><div class="kpi-title">Đơn hàng mới</div></div>
            </div>
            <div class="kpi-card total-bookings">
                <div class="kpi-icon"><i class="fas fa-tasks"></i></div>
                <div class="kpi-info"><div class="kpi-value"><?php echo $total_bookings; ?></div><div class="kpi-title">Tổng số đơn</div></div>
            </div>
            <div class="kpi-card total-users">
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
                <div class="kpi-info"><div class="kpi-value"><?php echo $total_users; ?></div><div class="kpi-title">Tổng khách hàng</div></div>
            </div>
        </div>
        <div class="admin-box">
            <div class="admin-header">
                <i class="fas fa-calendar-check"></i>
                <h2>Quản lý Toàn bộ Đơn hàng</h2>
            </div>
            
            <div class="table-tabs">
                <a href="<?php echo BASE_URL; ?>admin/index.php?tab=all" class="<?php echo ($current_tab == 'all') ? 'active' : ''; ?>">Tất cả</a>
                <a href="<?php echo BASE_URL; ?>admin/index.php?tab=pending" class="<?php echo ($current_tab == 'pending') ? 'active' : ''; ?>">Chờ xử lý (<?php echo $new_bookings_count; ?>)</a>
                <a href="<?php echo BASE_URL; ?>admin/index.php?tab=processing" class="<?php echo ($current_tab == 'processing') ? 'active' : ''; ?>">Đang thực hiện</a>
                <a href="<?php echo BASE_URL; ?>admin/index.php?tab=completed" class="<?php echo ($current_tab == 'completed') ? 'active' : ''; ?>">Đã hoàn thành</a>
                <a href="<?php echo BASE_URL; ?>admin/index.php?tab=cancelled" class="<?php echo ($current_tab == 'cancelled') ? 'active' : ''; ?>">Đã hủy</a>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Liên hệ</th>
                            <th>Dịch vụ</th>
                            <th>Giá tiền</th>
                            <th>Thiết bị</th>
                            <th>Hình thức</th>
                            <th>Ngày hẹn</th>
                            <th>Ghi chú</th>
                            <th>Cập nhật Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_bookings)): ?>
                            <tr><td colspan="9" style="text-align: center; padding: 20px;">Không có đơn hàng nào trong mục này.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($all_bookings as $booking): ?>
                            <tr>
                                <td class="user-info"><strong><?php echo htmlspecialchars($booking['user_name']); ?></strong></td>
                                <td class="service-info"><strong><?php echo htmlspecialchars($booking['service_name']); ?></strong></td>
                                <td><strong class="service-price"><?php echo number_format($booking['service_price'], 0, ",", "."); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['device_type']); ?>
                                    (<?php echo htmlspecialchars($booking['device_brand']); ?>)
                                </td>
                                <td><?php echo ($booking['service_method'] == 'on-site') ? 'Tại nhà' : 'Mang đến'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                <td class="notes"><?php echo !empty($booking['notes']) ? nl2br(htmlspecialchars($booking['notes'])) : '<i>-</i>'; ?></td>
                                
                                <td>
                                    <form method="POST" action="<?php echo BASE_URL; ?>admin/index.php?tab=<?php echo $current_tab; ?>" class="status-form">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        
                                        <select name="new_status" class="status-select">
                                            <?php foreach ($status_options as $option): ?>
                                                <option value="<?php echo $option; ?>" <?php echo ($option == $booking['status']) ? 'selected' : ''; ?>>
                                                    <?php echo translate_status($option)['text']; ?>
                                                </option>
                                            <?php endforeach; ?>
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
        <div class="admin-summary-grid">
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
                            <div class="service-quick-actions">
                                <span class="item-count"><?php echo $service['booking_count']; ?> lượt</span>
                            </div>
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
</div>

<?php include '../templates/footer.php'; ?>