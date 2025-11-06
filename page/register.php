<?php
session_start();
require '../config/db.php';

// Nếu đã đăng nhập thì chuyển về trang chủ
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$debug_info = ''; // Để debug

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if(empty($name) || empty($email) || empty($username) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif(strlen($username) < 4) {
        $error = 'Tên đăng nhập phải có ít nhất 4 ký tự!';
    } elseif(strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } else {
        // Kiểm tra username đã tồn tại chưa
        $check_user = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt_check = $conn->prepare($check_user);
        
        if(!$stmt_check) {
            $error = "Lỗi chuẩn bị câu lệnh: " . $conn->error;
        } else {
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if($row['username'] == $username) {
                    $error = 'Tên đăng nhập đã được sử dụng!';
                } else {
                    $error = 'Email đã được sử dụng!';
                }
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert vào database với prepared statement
                $sql = "INSERT INTO users (name, email, username, password, role, created_at) VALUES (?, ?, ?, ?, 'user', NOW())";
                $stmt = $conn->prepare($sql);
                
                if(!$stmt) {
                    $error = "Lỗi chuẩn bị câu lệnh INSERT: " . $conn->error;
                    $debug_info = "SQL Error: " . $conn->error;
                } else {
                    $stmt->bind_param("ssss", $name, $email, $username, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = "Đăng ký thành công! Đang chuyển đến trang đăng nhập...";
                        // Chuyển hướng sau 2 giây
                        header('refresh:2;url=' . BASE_URL . 'page/login.php');
                    } else {
                        $error = "Lỗi khi thêm dữ liệu: " . $stmt->error;
                        $debug_info = "Execute Error: " . $stmt->error . " | Errno: " . $stmt->errno;
                    }
                    $stmt->close();
                }
            }
            $stmt_check->close();
        }
    }
}

include '../templates/header.php';
?>

<style>
.register-container {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.register-container:before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    top: -300px;
    left: -300px;
    animation: float 6s ease-in-out infinite;
}

.register-container:after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    bottom: -200px;
    right: -200px;
    animation: float 8s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.register-box {
    background: white;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 100%;
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.6s ease;
}

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

.register-header {
    text-align: center;
    margin-bottom: 40px;
}

.register-header .icon {
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
    animation: pulse 2s ease infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.register-header h2 {
    color: #333;
    font-size: 28px;
    margin-bottom: 10px;
}

.register-header p {
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

.form-group label span {
    color: #e74c3c;
}

.input-wrapper {
    position: relative;
}

.input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #667eea;
    font-size: 18px;
}

.form-group input {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-group input.error {
    border-color: #e74c3c;
}

.password-strength {
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
}

.password-strength-bar.weak {
    width: 33%;
    background: #e74c3c;
}

.password-strength-bar.medium {
    width: 66%;
    background: #f39c12;
}

.password-strength-bar.strong {
    width: 100%;
    background: #27ae60;
}

.password-hint {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 25px;
    font-size: 14px;
}

.terms-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    cursor: pointer;
}

.terms-checkbox label {
    color: #666;
    line-height: 1.5;
}

.terms-checkbox a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.terms-checkbox a:hover {
    text-decoration: underline;
}

.btn-register {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

.btn-register:active {
    transform: translateY(0);
}

.btn-register:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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

.login-link {
    text-align: center;
    color: #666;
    font-size: 14px;
}

.login-link a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
}

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

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.debug-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
    margin-top: 15px;
    font-size: 12px;
    color: #6c757d;
    font-family: monospace;
    word-break: break-all;
}

@media (max-width: 768px) {
    .register-box {
        padding: 30px 25px;
    }
    
    .register-header h2 {
        font-size: 24px;
    }
    
    .register-container:before,
    .register-container:after {
        display: none;
    }
}

.btn-register.loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn-register.loading:after {
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

<div class="register-container">
    <div class="register-box">
        <div class="register-header">
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Đăng ký tài khoản</h2>
            <p>Tạo tài khoản mới để sử dụng dịch vụ</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php if($debug_info): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    <?php echo $debug_info; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="name">Họ và tên <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           placeholder="Nhập họ và tên"
                           required
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="username">Tên đăng nhập <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-id-card"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Nhập tên đăng nhập"
                           required
                           minlength="4"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="password-hint">Tối thiểu 4 ký tự</div>
            </div>

            <div class="form-group">
                <label for="email">Email <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="example@email.com"
                           required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Nhập mật khẩu"
                           required
                           minlength="6">
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-hint">Tối thiểu 6 ký tự</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Nhập lại mật khẩu"
                           required>
                </div>
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="terms" required>
                <label for="terms">
                    Tôi đồng ý với <a href="terms.php">Điều khoản dịch vụ</a> và 
                    <a href="privacy.php">Chính sách bảo mật</a>
                </label>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Đăng ký
            </button>
        </form>

        <div class="divider">
            <span>hoặc</span>
        </div>

        <div class="login-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </div>
    </div>
</div>

<script>
// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    
    let strength = 0;
    if(password.length >= 6) strength++;
    if(password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if(password.match(/\d/)) strength++;
    if(password.match(/[^a-zA-Z\d]/)) strength++;
    
    strengthBar.className = 'password-strength-bar';
    if(strength <= 2) {
        strengthBar.classList.add('weak');
    } else if(strength === 3) {
        strengthBar.classList.add('medium');
    } else {
        strengthBar.classList.add('strong');
    }
});

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if(confirmPassword && password !== confirmPassword) {
        this.classList.add('error');
    } else {
        this.classList.remove('error');
    }
});

// Form submit loading
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if(password !== confirmPassword) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp!');
        return false;
    }
    
    const btn = this.querySelector('.btn-register');
    btn.classList.add('loading');
    btn.innerHTML = '<i class="fas fa-spinner"></i> Đang xử lý...';
});

// Auto focus
document.getElementById('name').focus();
</script>

<?php include 'templates/footer.php'; ?>