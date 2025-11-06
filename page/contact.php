<?php
session_start();
require '../config/db.php'; // SỬA LẠI: Dùng ../ để lùi ra

$error = '';
$success = '';

// XỬ LÝ FORM KHI GỬI
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $message_content = mysqli_real_escape_string($conn, trim($_POST['message']));

    // Validation
    if (empty($name) || empty($email) || empty($message_content)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } else {
        // Chèn vào CSDL (bảng 'contacts' mà chúng ta đã thiết kế)
        $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
        if (!$stmt) {
            $error = "Lỗi chuẩn bị: " . $conn->error;
        } else {
            $stmt->bind_param("sss", $name, $email, $message_content);
            if ($stmt->execute()) {
                $success = "Gửi tin nhắn thành công! Chúng tôi sẽ phản hồi bạn sớm.";
            } else {
                $error = "Lỗi khi gửi: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

include '../templates/header.php'; // SỬA LẠI: Dùng ../ để lùi ra
?>

<style>
/* Kế thừa style của profile/booking */
.contact-container {
    min-height: calc(100vh - 72px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.contact-box {
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 900px; /* Rộng */
    display: grid;
    grid-template-columns: 1fr 1.2fr; /* Chia 2 cột */
    overflow: hidden; /* Bo góc */
    animation: fadeInUp 0.6s ease;
}

/* Cột thông tin bên trái */
.contact-info {
    background: #f8f9fa;
    padding: 50px 40px;
}
.contact-info h2 {
    font-size: 28px;
    color: var(--text-dark);
    margin-bottom: 15px;
}
.contact-info p {
    font-size: 16px;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 30px;
}
.info-item {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}
.info-item i {
    font-size: 20px;
    color: var(--primary);
    width: 25px;
    text-align: center;
}
.info-item span {
    font-size: 15px;
    color: var(--text-dark);
}

/* Cột form bên phải */
.contact-form {
    padding: 50px 40px;
}
.contact-form h2 {
    font-size: 28px;
    color: var(--text-dark);
    margin-bottom: 30px;
}

/* Style form (mượn từ profile) */
.form-group { margin-bottom: 25px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
.input-wrapper { position: relative; }
.input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 18px; transition: color 0.3s ease; }
.form-group input, .form-group textarea {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    font-family: 'Inter', sans-serif;
}
.form-group textarea { padding: 15px 20px; resize: vertical; min-height: 120px; }
.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}
.form-group input:focus + .input-wrapper i,
.input-wrapper:focus-within i { color: var(--primary); }
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
}
.btn-submit:hover { transform: translateY(-3px) scale(1.03); }

/* Thông báo */
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; animation: slideDown 0.3s ease; }
.alert i { font-size: 20px; }
.alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
.alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

/* Responsive */
@media (max-width: 768px) {
    .contact-box { grid-template-columns: 1fr; }
}
</style>
<div class="contact-container">
    <div class="contact-box">
        
        <div class="contact-info">
            <h2>Liên hệ</h2>
            <p>Chúng tôi luôn sẵn sàng lắng nghe! Gửi thắc mắc cho chúng tôi và đội ngũ sẽ phản hồi trong thời gian sớm nhất.</p>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>123 Đường ABC, Quận XYZ, TP. Hồ Chí Minh</span>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span>(028) 3812 3456</span>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span>hotro@computercare.com</span>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span>Thứ 2 - Thứ 7: 8:00 Sáng - 6:00 Tối</span>
            </div>
        </div>

        <div class="contact-form">
            <h2>Gửi tin nhắn</h2>

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
                <form method="POST" action="<?php echo BASE_URL; ?>page/contact.php">
                    <div class="form-group">
                        <label for="name">Họ và tên <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span>*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message">Nội dung tin nhắn <span>*</span></label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Gửi ngay
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; // SỬA LẠI: Dùng ../ để lùi ra ?>