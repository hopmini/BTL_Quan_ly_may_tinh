<?php
session_start();
require '../config/db.php'; // 1. GỌI CONFIG

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

// NÂNG CẤP: Lấy ĐẦY ĐỦ thông tin NGƯỜI DÙNG (thêm name, email)
$stmt_user = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();


// XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT ĐẶT LỊCH
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu cũ
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $booking_date = $_POST['booking_date'];
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));

    // NÂNG CẤP: Lấy dữ liệu mới
    $service_method = mysqli_real_escape_string($conn, $_POST['service_method']);
    $device_type = mysqli_real_escape_string($conn, $_POST['device_type']);
    $device_brand = mysqli_real_escape_string($conn, trim($_POST['device_brand']));

    // Validation
    if (empty($phone) || empty($address) || empty($booking_date) || empty($device_type)) {
        $error = 'Vui lòng điền đầy đủ các trường có dấu * !';
    } else {
        // NÂNG CẤP: Chèn vào bảng 'bookings' (thêm các cột mới)
        $stmt_insert = $conn->prepare(
            "INSERT INTO bookings (user_id, service_id, device_type, device_brand, service_method, phone, address, booking_date, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt_insert) {
            $error = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
        } else {
            // NÂNG CẤP: bind_param (thêm 3 chuỗi 'sss')
            $stmt_insert->bind_param("iisssssss", $user_id, $service_id, $device_type, $device_brand, $service_method, $phone, $address, $booking_date, $notes);
            
            if ($stmt_insert->execute()) {
                $success = "Đặt lịch thành công! Chúng tôi sẽ liên hệ với bạn sớm. 
                            Đang chuyển hướng về trang lịch sử...";
                // Chuyển hướng về trang lịch sử sau 3 giây
                header('refresh:3;url=' . BASE_URL . 'page/my_bookings.php');
            } else {
                $error = "Lỗi khi đặt lịch: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
}

include '../templates/header.php'; // GỌI HEADER
?>

<style>
/* Kế thừa style từ profile.php */
.booking-container {
    min-height: calc(100vh - 72px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.booking-box {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 900px; /* Rộng hơn */
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.6s ease;
    display: grid;
    grid-template-columns: 1fr 1.5fr; /* Chia 2 cột */
    gap: 40px;
}
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* CỘT BÊN TRÁI: TÓM TẮT DỊCH VỤ (Nâng cấp) */
.service-summary {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 30px;
    border: 1px solid #e0e0e0;
}
.service-summary h3 {
    font-size: 22px;
    color: var(--text-dark);
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 15px;
}
.summary-line {
    display: flex;
    justify-content: space-between;
    font-size: 16px;
    margin-bottom: 15px;
}
.summary-line span:first-child {
    color: var(--text-light);
}
.summary-line span:last-child {
    font-weight: 600;
    color: var(--text-dark);
}
.summary-line.service-name {
    font-weight: 600;
    color: var(--primary);
    font-size: 18px;
    display: block;
}
/* Ẩn phí tại nhà ban đầu */
#summary-onsite-fee {
    display: none;
}
.summary-total {
    border-top: 2px solid #ddd;
    padding-top: 15px;
    margin-top: 20px;
}
.summary-total span {
    font-size: 24px;
    font-weight: 700;
}

/* CỘT BÊN PHẢI: FORM ĐIỀN */
.booking-form h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}
.form-section {
    font-size: 16px;
    font-weight: 600;
    color: var(--primary);
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
    margin-bottom: 20px;
    margin-top: 30px;
}
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
.form-group label span { color: #e74c3c; } /* Dấu * đỏ */

.input-wrapper { position: relative; }
.input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 18px; transition: color 0.3s ease; }
.form-group input, .form-group textarea, .form-group select {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    font-family: 'Inter', sans-serif;
}
.form-group textarea { padding: 15px 20px; resize: vertical; min-height: 100px; }
.form-group input[type="datetime-local"], .form-group select {
    padding: 15px 20px;
}
.form-group input:focus, .form-group textarea:focus, .form-group select:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}
.form-group input:focus + .input-wrapper i, .input-wrapper:focus-within i { color: var(--primary); }

/* Style cho input bị vô hiệu hóa */
.form-group input:disabled {
    background: #f1f1f1;
    color: #777;
    cursor: not-allowed;
}
.form-group input:disabled + .input-wrapper i {
    color: #777;
}

/* NÂNG CẤP: Style cho Radio Button (Hình thức dịch vụ) */
.service-method-options {
    display: flex;
    gap: 15px;
}
.service-method-options label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
}
.service-method-options input[type="radio"] {
    width: 20px;
    height: 20px;
}
.service-method-options label:hover {
    border-color: #ccc;
}
/* Khi radio được chọn */
.service-method-options input[type="radio"]:checked + label {
    border-color: var(--primary);
    background: rgba(79, 70, 229, 0.05);
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

/* Nút Submit */
.btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #10B981, #059669); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
.btn-submit:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }

/* Thông báo */
.alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; animation: slideDown 0.3s ease; grid-column: 1 / -1; }
.alert i { font-size: 20px; }
.alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
.alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }

/* Responsive */
@media (max-width: 768px) {
    .booking-box { grid-template-columns: 1fr; padding: 30px 25px; }
    .service-summary { order: -1; }
}
</style>
<div class="booking-container">
    <div class="booking-box">

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
            <style>.booking-form, .service-summary { display: none; }</style>
        <?php endif; ?>


        <div class="service-summary">
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
                <span id="summary-onsite-price">50.000 VNĐ</span>
            </div>

            <div class="summary-line summary-total">
                <strong>Tổng cộng:</strong>
                <strong id="summary-total-price">
                    <?php echo number_format($service['price'], 0, ",", "."); ?> VNĐ
                </strong>
            </div>
        </div>

        <div class="booking-form">
            <form method="POST" action="<?php echo BASE_URL; ?>page/booking.php?service_id=<?php echo $service_id; ?>">
                
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
                    <label for="address">Địa chỉ <span>*</span></DĐịa></label>
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
        </div>

    </div>
</div>

<script>

const ON_SITE_FEE = 36000;        
const BASE_PRICE = <?php echo $service['price']; ?>;

// Lấy các element trong bảng tóm tắt
const onSiteFeeLine = document.getElementById('summary-onsite-fee');
const onSitePriceEl = document.getElementById('summary-onsite-price');
const totalPriceEl = document.getElementById('summary-total-price');

// Hàm format tiền tệ
function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
}

// Hàm cập nhật tổng tiền
function updateTotal() {
    const isOnSite = document.getElementById('method_onsite').checked;
    
    if (isOnSite) {
        // 1. Hiển thị dòng phí tại nhà
        onSiteFeeLine.style.display = 'flex';
        onSitePriceEl.innerText = formatCurrency(ON_SITE_FEE);
        
        // 2. Cập nhật tổng tiền
        const newTotal = BASE_PRICE + ON_SITE_FEE;
        totalPriceEl.innerText = formatCurrency(newTotal);
    } else {
        // 1. Ẩn dòng phí tại nhà
        onSiteFeeLine.style.display = 'none';
        
        // 2. Cập nhật tổng tiền (chỉ giá gốc)
        totalPriceEl.innerText = formatCurrency(BASE_PRICE);
    }
}

// Chạy hàm này 1 lần khi tải trang để đảm bảo tổng tiền đúng
document.addEventListener('DOMContentLoaded', updateTotal);
</script>

<?php include '../templates/footer.php'; ?>