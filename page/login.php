<?php
session_start();
require '../config/db.php';

// (Toàn bộ code PHP của bạn giữ nguyên vì nó đã được sửa đúng)
// Nếu đã đăng nhập thì chuyển về trang chủ
if(isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Không cần mysqli_real_escape_string khi dùng prepared statements
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        
        // ===================================
        // SỬA LỖI SQL INJECTION BẮT ĐẦU TỪ ĐÂY
        // ===================================

        $query = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if(!$stmt) {
             $error = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result(); // Lấy kết quả
            
            if($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Xác thực mật khẩu
                if(password_verify($password, $user['password'])) {
                    // Đăng nhập thành công, gán session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // ===================================
                    // SỬA LỖI THIẾU SESSION 'name'
                    // ===================================
                    $_SESSION['name'] = $user['name']; // <-- Thêm dòng này

                    $success = 'Đăng nhập thành công! Đang chuyển hướng...';
                    
                    // Chuyển hướng sau 1.5 giây
                    header('refresh:1.5;url=' . BASE_URL . 'index.php');
                
                } else {
                    $error = 'Mật khẩu không chính xác!';
                }
            } else {
                $error = 'Tài khoản không tồn tại!';
            }
            $stmt->close();
        }
        // ===================================
        // KẾT THÚC SỬA LỖI
        // ===================================
    }
}

include '../templates/header.php';
?>

<style>
/* Thêm keyframes "float" giống register.php */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

/* Thêm keyframes "pulse" giống register.php */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Thêm keyframes "fadeInUp" giống register.php */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-container {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

/* Áp dụng hiệu ứng "float" cho vòng tròn */
.login-container:before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    top: -250px;
    right: -250px;
    animation: float 6s ease-in-out infinite; /* Thêm animation */
}

/* Áp dụng hiệu ứng "float" cho vòng tròn */
.login-container:after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    bottom: -200px;
    left: -200px;
    animation: float 8s ease-in-out infinite; /* Thêm animation */
}

.login-box {
    background: white;
    border-radius: 20px;
    padding: 50px;
    /* Nâng cấp shadow cho đẹp hơn */
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
    max-width: 450px;
    width: 100%;
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.6s ease;
}

.login-header {
    text-align: center;
    margin-bottom: 40px;
}

.login-header .icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    animation: pulse 2s ease infinite; /* Thêm animation "pulse" */
}

.login-header h2 {
    color: #333;
    font-size: 28px;
    margin-bottom: 10px;
}

.login-header p {
    color: #666;
    font-size: 14px;
}

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

/* Nâng cấp icon: Mặc định mờ đi, khi focus thì rõ nét */
.input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa; /* Màu mờ mặc định */
    font-size: 18px;
    transition: color 0.3s ease; /* Thêm transition */
}

.form-group input {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    /* Thêm transition mượt mà */
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    background: #f8f9fa;
}

/* Khi focus vào input, đổi màu icon */
.form-group input:focus + .input-wrapper i,
.input-wrapper:focus-within i { /* Hỗ trợ cả khi dùng focus-within */
    color: #667eea; /* Đổi màu icon khi focus */
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    font-size: 14px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
}

.remember-me input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.forgot-password {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease; /* Thêm transition */
}

.forgot-password:hover {
    text-decoration: underline;
    color: #4338CA; /* Đổi màu đậm hơn khi hover */
}

.btn-login {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    /* Thêm transition mượt mà */
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Nâng cấp hiệu ứng hover 3D Pop */
.btn-login:hover {
    transform: translateY(-3px) scale(1.03); /* Hiệu ứng "pop" */
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5); /* Shadow đậm hơn */
}

.btn-login:active {
    transform: translateY(0) scale(1); /* Trở về bình thường khi click */
}

.divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.divider:before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: #e0e0e0;
}

.divider span {
    background: white;
    padding: 0 15px;
    color: #999;
    font-size: 14px;
    position: relative;
}

.register-link {
    text-align: center;
    color: #666;
    font-size: 14px;
}

.register-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease; /* Thêm transition */
}

.register-link a:hover {
    text-decoration: underline;
    color: #4338CA; /* Đổi màu đậm hơn khi hover */
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    animation: slideDown 0.3s ease; /* Giữ nguyên animation slideDown */
}

/* Thêm keyframes cho slideDown (nếu chưa có) */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert i {
    font-size: 20px;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #3c3;
    border: 1px solid #cfc;
}

@media (max-width: 768px) {
    .login-box {
        padding: 30px 25px;
    }
    
    .login-header h2 {
        font-size: 24px;
    }
    
    .login-container:before,
    .login-container:after {
        display: none;
    }
}

/* (Giữ nguyên CSS cho phần loading) */
.btn-login.loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn-login.loading:after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    display: inline-block;
    margin-left: 10px;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <div class="icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2>Đăng nhập</h2>
            <p>Chào mừng bạn quay trở lại!</p>
        </div>

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

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Nhập tên đăng nhập"
                           required
                           autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                 <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Nhập mật khẩu"
                           required
                           autocomplete="current-password">
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
                <a href="forgot-password.php" class="forgot-password">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập
            </button>
        </form>

        <div class="divider">
            <span>hoặc</span>
        </div>

        <div class="register-link">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>
    </div>
</div>

<script>
// (Toàn bộ code JavaScript của bạn giữ nguyên)
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if(this.checkValidity()) {
        const btn = this.querySelector('.btn-login');
        btn.classList.add('loading');
        btn.innerHTML = '<i class="fas fa-spinner"></i> Đang đăng nhập...';
    }
});

document.getElementById('username').focus();
</script>

<?php include 'templates/footer.php'; ?>