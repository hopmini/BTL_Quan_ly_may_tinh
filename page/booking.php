<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG

// === 1. XỬ LÝ LOGIC (PHP) ===

// BẮT BUỘC: 1. Phải đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_URL . "page/login.php");
    exit();
}

// BẮT BUỘC: 2. Phải có ID dịch vụ
if (!isset($_GET['service_id']) || !is_numeric($_GET['service_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$service_id = (int)$_GET['service_id'];

$error = '';
$success = '';

// Lấy thông tin DỊCH VỤ
$stmt_service = $conn->prepare("SELECT name, price FROM services WHERE id = ?");
$stmt_service->bind_param("i", $service_id);
$stmt_service->execute();
$service_result = $stmt_service->get_result();
$service = $service_result->fetch_assoc();
$stmt_service->close();

if (!$service) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Lấy ĐẦY ĐỦ thông tin NGƯỜI DÙNG (để điền sẵn form)
$stmt_user = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();


// XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT ĐẶT LỊCH
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $booking_date = $_POST['booking_date'];
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
    $service_method = mysqli_real_escape_string($conn, $_POST['service_method']);
    $device_type = mysqli_real_escape_string($conn, $_POST['device_type']);
    $device_brand = mysqli_real_escape_string($conn, trim($_POST['device_brand']));

    // Validation
    if (empty($phone) || empty($address) || empty($booking_date) || empty($device_type)) {
        $error = 'Vui lòng điền đầy đủ các trường có dấu * !';
    } else {
        // Chèn vào bảng 'bookings'
        $stmt_insert = $conn->prepare(
            "INSERT INTO bookings (user_id, service_id, device_type, device_brand, service_method, phone, address, booking_date, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt_insert) {
            $error = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
        } else {
            $stmt_insert->bind_param("iisssssss", $user_id, $service_id, $device_type, $device_brand, $service_method, $phone, $address, $booking_date, $notes);
            
            // ...
if ($stmt_insert->execute()) {
    $success = "Đặt lịch thành công! ...";
    header('refresh:3;url=' . BASE_URL . 'page/my_bookings.php');

    // =======================================================
    // ===== BẮT ĐẦU: GỬI THÔNG BÁO CHO TẤT CẢ ADMIN =====
    // =======================================================

    // 1. Lấy ID của đơn hàng vừa tạo
    $new_booking_id = $conn->insert_id; 

    // 2. Chuẩn bị tin nhắn
    $notify_message = "Đơn hàng mới #$new_booking_id từ khách: " . htmlspecialchars($user['name']);
    $notify_link = "admin/index.php?tab=pending";

    // 3. Lấy ID của tất cả Admin
    $admin_result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
    $admins = $admin_result->fetch_all(MYSQLI_ASSOC);

    // 4. Chuẩn bị 1 lần
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");

    // 5. Gửi thông báo cho từng Admin
    foreach ($admins as $admin) {
        $stmt_notify->bind_param("iss", $admin['id'], $notify_message, $notify_link);
        $stmt_notify->execute(); 
    }
    $stmt_notify->close();

    // =======================================================
    // ===== KẾT THÚC: GỬI THÔNG BÁO CHO ADMIN =====
    // =======================================================

} else {
// ...
                $error = "Lỗi khi đặt lịch: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
}
// === KẾT THÚC LOGIC (PHP) ===


// GỌI HEADER (Sau khi logic đã chạy xong)
include '../templates/header.php'; 
?>

<style>
/* Layout 2 cột mới */
.booking-page-container {
    max-width: 1200px; /* Chiếm chiều rộng lớn hơn */
    margin: 40px auto;
    padding: 0 20px;
}
.booking-page-grid {
    display: grid;
    grid-template-columns: 2fr 1fr; /* Cột form (trái) rộng hơn cột tóm tắt (phải) */
    gap: 40px;
}

/* CỘT TRÁI: FORM CHÍNH */
.booking-main {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

/* CỘT PHẢI: SIDEBAR TÓM TẮT */
.booking-sidebar {
    /* (Cột này sẽ chứa nhiều box) */
}

/* Style cho box Tóm tắt */
.summary-box {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    border: 1px solid #e0e0e0;
    position: sticky;
    top: 90px; /* 72px (header) + 18px (khoảng cách) */
}
.summary-box h3 {
    font-size: 22px; color: var(--text-dark); margin-bottom: 20px;
    border-bottom: 1px solid #ddd; padding-bottom: 15px;
}
.summary-line { display: flex; justify-content: space-between; font-size: 16px; margin-bottom: 15px; }
.summary-line span:first-child { color: var(--text-light); }
.summary-line span:last-child { font-weight: 600; color: var(--text-dark); }
.summary-line.service-name { font-weight: 600; color: var(--primary); font-size: 18px; display: block; }
#summary-onsite-fee { display: none; } /* Ẩn phí tại nhà */
.summary-total { border-top: 2px solid #ddd; padding-top: 15px; margin-top: 20px; }
.summary-total span { font-size: 24px; font-weight: 700; }

/* Box "Chi tiết" (Tại sao chọn chúng tôi) */
.trust-box {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    margin-top: 30px;
}
.trust-box h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 20px;
}
.trust-box ul { list-style: none; padding: 0; margin: 0; }
.trust-box li {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: var(--text-light);
}
.trust-box li i { font-size: 18px; color: var(--primary); }


/* CSS cho Form (Giữ nguyên) */
.booking-main h2 {
    font-size: 24px; color: #333; margin-bottom: 30px;
    border-bottom: 1px solid #eee; padding-bottom: 20px;
}
.form-section { font-size: 16px; font-weight: 600; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 20px; margin-top: 30px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
.form-group label span { color: #e74c3c; }
.input-wrapper { position: relative; }
.input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 18px; transition: color 0.3s ease; }
.form-group input, .form-group textarea, .form-group select {
    width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #e0e0e0;
    border-radius: 10px; font-size: 15px; transition: all 0.3s ease;
    background: #f8f9fa; font-family: 'Inter', sans-serif;
}
.form-group textarea { padding: 15px 20px; resize: vertical; min-height: 100px; }
.form-group input[type="datetime-local"], .form-group select { padding: 15px 20px; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus {
    outline: none; border-color: var(--primary); background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}
.form-group input:focus + .input-wrapper i, .input-wrapper:focus-within i { color: var(--primary); }
.form-group input:disabled { background: #f1f1f1; color: #777; cursor: not-allowed; }
.form-group input:disabled + .input-wrapper i { color: #777; }
.service-method-options { display: flex; gap: 15px; }
.service-method-options label { display: flex; align-items: center; gap: 10px; padding: 15px 20px; border: 2px solid #e0e0e0; border-radius: 10px; width: 100%; cursor: pointer; transition: all 0.3s ease; }
.service-method-options input[type="radio"] { width: 20px; height: 20px; }
.service-method-options label:hover { border-color: #ccc; }
.service-method-options input[type="radio"]:checked + label {
    border-color: var(--primary); background: rgba(79, 70, 229, 0.05);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}
.btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
.btn-submit:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }

/* Thông báo */
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; animation: slideDown 0.3s ease; }
.alert i { font-size: 20px; }
.alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
.alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 992px) {
    .booking-page-grid { grid-template-columns: 1fr; }
    .booking-sidebar { order: -1; position: static; }
}
</style>
<main class="booking-page-container">
    <div class="booking-page-grid">
        
        <div class="booking-main">
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <?php if(!$success): ?>
                <form method="POST" action="<?php echo BASE_URL; ?>page/booking.php?service_id=<?php echo $service_id; ?>" class="booking-form">
                    
                    <h2 class="form-section">1. Thông tin người đặt</h2>
                    
                    <div class="form-group">
                        <label for="name">Họ và tên</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" disabled
                                value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" disabled
                                value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>

                    <h2 class="form-section">2. Thông tin dịch vụ</h2>
                    <div class="form-group">
                        <label>Hình thức dịch vụ <span>*</span></label>
                        <div class="service-method-options">
                            <input type="radio" id="method_dropoff" name="service_method" value="drop-off" checked onchange="updateTotal()">
                            <label for="method_dropoff">
                                <i class="fas fa-store"></i>
                                <span>Mang đến cửa hàng</span>
                            </label>
                            
                            <input type="radio" id="method_onsite" name="service_method" value="on-site" onchange="updateTotal()">
                            <label for="method_onsite">
                                <i class="fas fa-home"></i>
                                <span>Sửa chữa tại nhà</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="device_type">Loại thiết bị <span>*</span></label>
                        <select id="device_type" name="device_type" required>
                            <option value="" disabled selected>-- Vui lòng chọn --</option>
                            <option value="Laptop">Laptop</option>
                            <option value="PC (Máy tính để bàn)">PC (Máy tính để bàn)</option>
                            <option value="AIO (All-in-One)">AIO (All-in-One)</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="device_brand">Hãng sản xuất / Model</label>
                        <div class="input-wrapper">
                            <i class="fas fa-laptop"></i>
                            <input type="text" id="device_brand" name="device_brand"
                                placeholder="Ví dụ: Dell XPS 13, Macbook Pro M1...">
                        </div>
                    </div>

                    <h2 class="form-section">3. Thông tin liên hệ & Đặt lịch</h2>
                    <div class="form-group">
                        <label for="phone">Số điện thoại <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="text" id="phone" name="phone" required
                                placeholder="SĐT để chúng tôi liên hệ"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Địa chỉ <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" id="address" name="address" required
                                placeholder="Địa chỉ của bạn (bắt buộc nếu sửa tại nhà)"
                                value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="booking_date">Ngày giờ hẹn <span>*</span></label>
                        <input type="datetime-local" id="booking_date" name="booking_date" required>
                    </div>
                    <div class="form-group">
                        <label for="notes">Mô tả sự cố / Ghi chú</label>
                        <textarea id="notes" name="notes" 
                            placeholder="Ví dụ: Máy tính của tôi chạy rất chậm và hay bị treo..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-calendar-check"></i> Xác nhận đặt lịch
                    </button>
                </form>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>page/my_bookings.php" class="btn-submit" style="text-align: center; text-decoration: none;">
                    <i class="fas fa-history"></i> Xem lịch sử đặt lịch
                </a>
            <?php endif; ?>
        </div>
        
        <aside class="booking-sidebar">
            
            <div class="summary-box">
                <h3>Tóm tắt đơn hàng</h3>
                <div class="summary-line service-name">
                    <?php echo htmlspecialchars($service['name']); ?>
                </div>
                <div class="summary-line">
                    <span>Chi phí dịch vụ:</span>
                    <span id="summary-service-price" data-price="<?php echo $service['price']; ?>">
                        <?php echo number_format($service['price'], 0, ",", "."); ?> VNĐ
                    </span>
                </div>
                <div class="summary-line" id="summary-onsite-fee">
                    <span>Phí sửa tại nhà:</span>
                    <span id="summary-onsite-price">36.000 VNĐ</span> </div>
                <div class="summary-line summary-total">
                    <strong>Tổng cộng:</strong>
                    <strong id="summary-total-price">
                        <?php echo number_format($service['price'], 0, ",", "."); ?> VNĐ
                    </strong>
                </div>
            </div>

            <div class="trust-box">
                <h4>Tại sao chọn Computer Care?</h4>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Kỹ thuật viên giàu kinh nghiệm.</li>
                    <li><i class="fas fa-shield-alt"></i> Linh kiện chính hãng, bảo hành uy tín.</li>
                    <li><i class="fas fa-dollar-sign"></i> Giá cả minh bạch, không phát sinh chi phí.</li>
                    <li><i class="fas fa-history"></i> Theo dõi trạng thái đơn hàng 24/7.</li>
                </ul>
            </div>

        </aside>

    </div>
</main>

<script>
// Sửa giá trị phí tại nhà
const ON_SITE_FEE = 36000; 
const BASE_PRICE = <?php echo $service['price']; ?>;
const onSiteFeeLine = document.getElementById('summary-onsite-fee');
const onSitePriceEl = document.getElementById('summary-onsite-price');
const totalPriceEl = document.getElementById('summary-total-price');

function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
}

function updateTotal() {
    const isOnSite = document.getElementById('method_onsite').checked;
    if (isOnSite) {
        onSiteFeeLine.style.display = 'flex';
        onSitePriceEl.innerText = formatCurrency(ON_SITE_FEE);
        const newTotal = BASE_PRICE + ON_SITE_FEE;
        totalPriceEl.innerText = formatCurrency(newTotal);
    } else {
        onSiteFeeLine.style.display = 'none';
        totalPriceEl.innerText = formatCurrency(BASE_PRICE);
    }
}
document.addEventListener('DOMContentLoaded', updateTotal);
</script>

<?php include '../templates/footer.php'; ?>