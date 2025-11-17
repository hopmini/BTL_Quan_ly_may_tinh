<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = BASE_URL . 'page/my_bookings.php';
    header("Location: " . BASE_URL . "page/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ''; // Biến để chứa thông báo

// === 3. NÂNG CẤP LOGIC HỦY ĐƠN HÀNG ===
if (isset($_GET['action']) && isset($_GET['booking_id'])) {
    $booking_id = (int)$_GET['booking_id'];
    
    // --- HÀNH ĐỘNG 1: HỦY NGAY LẬP TỨC ---
    if ($_GET['action'] == 'cancel') {
        // Chỉ hủy ngay nếu trạng thái là 'pending'
        $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt_check->bind_param("ii", $booking_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Hợp lệ -> Cập nhật trạng thái thành 'cancelled'
            $stmt_cancel = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt_cancel->bind_param("i", $booking_id);
            if ($stmt_cancel->execute()) {
                $message = "Đã hủy lịch hẹn (ID: $booking_id) thành công!";
            } else {
                $message = "Lỗi khi hủy lịch hẹn: " . $stmt_cancel->error;
            }
            $stmt_cancel->close();
        } else {
            $message = "Không thể hủy lịch hẹn này (có thể đã được xác nhận).";
        }
        $stmt_check->close();
    }
    
    // --- HÀNH ĐỘNG 2: GỬI YÊU CẦU HỦY ---
    elseif ($_GET['action'] == 'request_cancel') {
        // Chỉ cho phép yêu cầu hủy nếu trạng thái là 'confirmed'
        $stmt_check = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? AND status = 'confirmed'");
        $stmt_check->bind_param("ii", $booking_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Hợp lệ -> Cập nhật trạng thái thành 'pending_cancellation' (Chờ Admin duyệt)
            $stmt_req = $conn->prepare("UPDATE bookings SET status = 'pending_cancellation' WHERE id = ?");
            $stmt_req->bind_param("i", $booking_id);
            if ($stmt_req->execute()) {
                $message = "Đã gửi yêu cầu hủy (ID: $booking_id). Chờ Admin xác nhận.";
            } else {
                $message = "Lỗi khi gửi yêu cầu: " . $stmt_req->error;
            }
            $stmt_req->close();
        } else {
            $message = "Không thể gửi yêu cầu hủy cho đơn hàng này.";
        }
        $stmt_check->close();
    }

    // Chuyển hướng về chính trang này để xóa tham số "action"
    header("Location: " . BASE_URL . "page/my_bookings.php?message=" . urlencode($message));
    exit();
}
// === KẾT THÚC LOGIC HỦY ===

// 4. LẤY THÔNG BÁO (NẾU CÓ)
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// 5. LẤY DANH SÁCH BOOKING CỦA USER HIỆN TẠI
// (Giữ nguyên)
$stmt_bookings = $conn->prepare(
    "SELECT b.id, b.booking_date, b.status, b.notes, b.created_at, s.name as service_name 
     FROM bookings b
     JOIN services s ON b.service_id = s.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
$stmt_bookings->bind_param("i", $user_id);
$stmt_bookings->execute();
$bookings_result = $stmt_bookings->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$stmt_bookings->close();

// 6. HÀM HỖ TRỢ: DỊCH TRẠNG THÁI (Nâng cấp)
function translate_status($status) {
    switch ($status) {
        case 'pending': return ['text' => 'Chờ xác nhận', 'color' => '#f39c12'];
        case 'confirmed': return ['text' => 'Đã xác nhận', 'color' => '#2980b9'];
        case 'processing': return ['text' => 'Đang xử lý', 'color' => '#8e44ad'];
        case 'completed': return ['text' => 'Đã hoàn thành', 'color' => '#27ae60'];
        case 'cancelled': return ['text' => 'Đã hủy', 'color' => '#c0392b'];
        // NÂNG CẤP: Thêm trạng thái mới
        case 'pending_cancellation': return ['text' => 'Chờ hủy', 'color' => '#e67e22']; // Màu cam
        default: return ['text' => ucfirst($status), 'color' => '#7f8c8d'];
    }
}

// 7. GỌI HEADER
include '../templates/header.php';
?>

<style>
/* (Giữ nguyên toàn bộ CSS cũ của bạn: .bookings-container, .bookings-content, .bookings-header, .message-box, .bookings-table, .status-badge) */
.bookings-container { min-height: calc(100vh - 72px); padding: 40px 20px; background: var(--bg-light); }
.bookings-content { max-width: 1200px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
.bookings-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.bookings-header i { font-size: 24px; color: var(--primary); }
.bookings-header h2 { font-size: 24px; color: #333; margin: 0; }
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; background: #e0f2fe; color: #0c546b; border: 1px solid #bee5eb; font-size: 15px; }
.bookings-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.bookings-table th, .bookings-table td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
.bookings-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 13px; font-weight: 600; text-transform: uppercase; }
.bookings-table td { font-size: 14px; color: var(--text-dark); }
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: white; min-width: 110px; text-align: center; }

/* CSS cho Nút Hủy (btn-cancel) */
.btn-cancel {
    display: inline-flex; align-items: center; gap: 5px;
    background-color: #fee; color: #c0392b; border: 1px solid #fcc;
    padding: 8px 15px; border-radius: 8px; font-size: 13px;
    font-weight: 500; text-decoration: none; cursor: pointer;
    transition: all 0.3s ease;
}
.btn-cancel:hover { background-color: #c0392b; color: white; border-color: #c0392b; }
.btn-cancel i { font-size: 14px; }

/* NÂNG CẤP: CSS cho Nút Yêu cầu Hủy (btn-request-cancel) */
.btn-request-cancel {
    display: inline-flex; align-items: center; gap: 5px;
    background-color: #fff5e6; color: #e67e22; border: 1px solid #fbeebc;
    padding: 8px 15px; border-radius: 8px; font-size: 13px;
    font-weight: 500; text-decoration: none; cursor: pointer;
    transition: all 0.3s ease;
}
.btn-request-cancel:hover { background-color: #e67e22; color: white; border-color: #e67e22; }
.btn-request-cancel i { font-size: 14px; }

/* NÂNG CẤP: CSS cho Nút Đã gửi Yêu cầu (Disabled) */
.btn-disabled {
    display: inline-flex; align-items: center; gap: 5px;
    background-color: #ecf0f1; color: #95a5a6; border: 1px solid #e0e0e0;
    padding: 8px 15px; border-radius: 8px; font-size: 13px;
    font-weight: 500; text-decoration: none; cursor: not-allowed;
}

/* (Giữ nguyên .empty-state và responsive) */
.empty-state { text-align: center; padding: 60px 20px; border: 2px dashed #e0e0e0; border-radius: 15px; margin-top: 30px; }
.empty-state i { font-size: 48px; color: #ccc; margin-bottom: 15px; }
.empty-state p { font-size: 16px; color: var(--text-light); }
.empty-state-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
.empty-state-link:hover { background: var(--primary-dark); transform: translateY(-2px); }
@media (max-width: 768px) {
    .table-wrapper { display: block; width: 100%; overflow-x: auto; }
    .bookings-table { min-width: 600px; }
    .bookings-content { padding: 30px 20px; }
}
</style>
<div class="bookings-container">
    <div class="bookings-content">
        <div class="bookings-header">
            <i class="fas fa-history"></i>
            <h2>Lịch sử Đặt lịch của bạn</h2>
        </div>

        <?php if ($message): ?>
            <div class="message-box">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($bookings)): ?>
            <div class="table-wrapper"> 
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>Dịch vụ</th>
                            <th>Ngày hẹn</th>
                            <th>Ngày đặt</th>
                            <th>Ghi chú</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                                <td><?php echo !empty($booking['notes']) ? nl2br(htmlspecialchars($booking['notes'])) : '<i>(Không có)</i>'; ?></td>
                                <td>
                                    <?php 
                                        $status_info = translate_status($booking['status']);
                                    ?>
                                    <span class="status-badge" style="background-color: <?php echo $status_info['color']; ?>">
                                        <?php echo $status_info['text']; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <a href="<?php echo BASE_URL; ?>page/my_bookings.php?action=cancel&booking_id=<?php echo $booking['id']; ?>" 
                                           class="btn-cancel" 
                                           onclick="return confirm('Bạn có chắc chắn muốn HỦY NGAY lịch hẹn này?');">
                                            <i class="fas fa-times"></i> Hủy lịch
                                        </a>
                                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                                        <a href="<?php echo BASE_URL; ?>page/my_bookings.php?action=request_cancel&booking_id=<?php echo $booking['id']; ?>" 
                                           class="btn-request-cancel" 
                                           onclick="return confirm('Lịch đã được xác nhận. Bạn có muốn GỬI YÊU CẦU HỦY đến Admin không?');">
                                            <i class="fas fa-exclamation-triangle"></i> Yêu cầu hủy
                                        </a>
                                    <?php elseif ($booking['status'] == 'pending_cancellation'): ?>
                                        <span class="btn-disabled">
                                            <i class="fas fa-clock"></i> Đã gửi Y/C
                                        </span>
                                    <?php else: ?>
                                        <span>-</span> 
                                    <?php endif; ?>
                                </td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Bạn chưa đặt dịch vụ nào cả.</p>
                <a href="<?php echo BASE_URL; ?>index.php" class="empty-state-link">Xem các dịch vụ</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include '../templates/footer.php'; ?>