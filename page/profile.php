<?php
session_start(); // Bắt đầu session
require '../config/db.php'; // Kết nối CSDL

// BẮT BUỘC: Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Nếu chưa, đá về trang đăng nhập
    exit();
}

$user_id = $_SESSION['user_id']; // Lấy user ID từ session

// Khai báo các biến thông báo
$info_error = '';
$info_success = '';
$pass_error = '';
$pass_success = '';

// XỬ LÝ KHI NGƯỜI DÙNG SUBMIT FORM
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. XỬ LÝ CẬP NHẬT THÔNG TIN
    if (isset($_POST['action']) && $_POST['action'] == 'update_info') {
        $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $address = mysqli_real_escape_string($conn, trim($_POST['address']));

        $stmt = $conn->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        if (!$stmt) {
            $info_error = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
        } else {
            $stmt->bind_param("ssi", $phone, $address, $user_id);
            if ($stmt->execute()) {
                $info_success = "Cập nhật thông tin thành công!";
            } else {
                $info_error = "Lỗi khi cập nhật: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // 2. XỬ LÝ ĐỔI MẬT KHẨU
    if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $pass_error = 'Vui lòng điền đầy đủ các trường!';
        } elseif (strlen($new_password) < 6) {
            $pass_error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        } elseif ($new_password !== $confirm_password) {
            $pass_error = 'Mật khẩu xác nhận không khớp!';
        } else {
            // Kiểm tra mật khẩu cũ có đúng không
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_row = $result->fetch_assoc();
            $stmt->close();

            if ($user_row && password_verify($old_password, $user_row['password'])) {
                // Mật khẩu cũ chính xác -> Tiến hành hash và cập nhật mật khẩu mới
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                if(!$stmt_update) {
                    $pass_error = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
                } else {
                    $stmt_update->bind_param("si", $hashed_new_password, $user_id);
                    if ($stmt_update->execute()) {
                        $pass_success = "Đổi mật khẩu thành công!";
                    } else {
                        $pass_error = "Lỗi khi cập nhật mật khẩu: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                }
            } else {
                $pass_error = 'Mật khẩu cũ không chính xác!';
            }
        }
    }
}

// LẤY DỮ LIỆU NGƯỜI DÙNG ĐỂ HIỂN THỊ
// (Luôn chạy sau khối POST để lấy dữ liệu mới nhất nếu vừa cập nhật)
$stmt = $conn->prepare("SELECT name, email, username, phone, address FROM users WHERE id = ?");
if (!$stmt) {
    die("Lỗi nghiêm trọng: Không thể lấy thông tin người dùng. " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Nếu không tìm thấy user (ví dụ: bị admin xóa), hủy session và logout
    session_destroy();
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

// Include header (PHẢI nằm sau logic PHP)
include '../templates/header.php';
?>

<style>
/* Sử dụng lại style nền của login/register */
.profile-container {
    min-height: calc(100vh - 72px); /* 72px là chiều cao header */
    display: flex;
    flex-direction: column; /* Xếp chồng 2 box */
    align-items: center;
    justify-content: flex-start; /* Bắt đầu từ trên */
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    gap: 30px; /* Khoảng cách giữa 2 box */
}

/* Sử dụng lại style .login-box và đổi tên */
.profile-box {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 600px; /* Cho box rộng hơn một chút */
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.6s ease;
}

/* Keyframes cho animation */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.profile-header {
    text-align: left;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.profile-header i {
    font-size: 24px;
    color: var(--primary);
}

.profile-header h2 {
    color: #333;
    font-size: 24px;
    margin: 0;
}

/* Style cho form group (mượn từ register) */
.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
    font-size: 18px;
    transition: color 0.3s ease;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    font-family: 'Inter', sans-serif; /* Đảm bảo font nhất quán */
}

.form-group textarea {
    padding: 15px 20px; /* Textarea không cần icon */
    resize: vertical;
    min-height: 100px;
}

/* Style cho input bị vô hiệu hóa */
.form-group input:disabled {
    background: #f1f1f1;
    color: #777;
    cursor: not-allowed;
}
.form-group input:disabled + .input-wrapper i {
    color: #777;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.form-group input:focus + .input-wrapper i,
.input-wrapper:focus-within i {
    color: var(--primary);
}

/* Nút Submit (mượn style của btn-register) */
.btn-submit {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
}

.btn-submit:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
}

/* Nút đổi mật khẩu (màu khác) */
.btn-submit.btn-danger {
    background: linear-gradient(135deg, #F56565, #E53E3E);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}
.btn-submit.btn-danger:hover {
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
}


/* Thông báo lỗi/thành công (mượn từ login) */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    animation: slideDown 0.3s ease;
}
.alert i { font-size: 20px; }
.alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
.alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }

@media (max-width: 768px) {
    .profile-box {
        padding: 30px 25px;
    }
    .profile-header h2 {
        font-size: 20px;
    }
}
</style>
<div class="profile-container">

    <div class="profile-box">
        <div class="profile-header">
            <i class="fas fa-user-edit"></i>
            <h2>Thông tin cá nhân</h2>
        </div>

        <?php if($info_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $info_error; ?></span>
            </div>
        <?php endif; ?>
        <?php if($info_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $info_success; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="update_info">
            
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

            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <div class="input-wrapper">
                    <i class="fas fa-id-card"></i>
                    <input type="text" id="username" name="username" disabled 
                           value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="phone" name="phone" 
                           placeholder="Nhập số điện thoại của bạn"
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <textarea id="address" name="address" 
                          placeholder="Nhập địa chỉ của bạn"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Lưu thay đổi
            </button>
        </form>
    </div>

    <div class="profile-box">
        <div class="profile-header">
            <i class="fas fa-key"></i>
            <h2>Đổi mật khẩu</h2>
        </div>

        <?php if($pass_error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $pass_error; ?></span>
            </div>
        <?php endif; ?>
        <?php if($pass_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $pass_success; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="old_password">Mật khẩu cũ</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock-open"></i>
                    <input type="password" id="old_password" name="old_password" 
                           placeholder="Nhập mật khẩu cũ" required>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" 
                           placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" required minlength="6">
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Nhập lại mật khẩu mới" required>
                </div>
            </div>

            <button type="submit" class="btn-submit btn-danger">
                <i class="fas fa-sync-alt"></i> Đổi mật khẩu
            </button>
        </form>
    </div>

</div> <?php include '../templates/footer.php'; ?>