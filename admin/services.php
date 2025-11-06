<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

$message = '';
$error = '';

// Khai báo biến cho form (để dùng cho cả Thêm và Sửa)
$form_action = 'add';
$form_id = '';
$form_name = '';
$form_description = '';
$form_price = '';
$form_button_text = 'Thêm dịch vụ';

// 3. LOGIC XỬ LÝ (POST, GET)

// XỬ LÝ POST (THÊM MỚI hoặc CẬP NHẬT)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (int)$_POST['price'];

    // THÊM MỚI
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        if (empty($name) || $price <= 0) {
            $error = 'Tên dịch vụ và Giá (phải > 0) là bắt buộc!';
        } else {
            // Nâng cấp: Thêm image_url (nếu có)
            $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
            $stmt = $conn->prepare("INSERT INTO services (name, description, image_url, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $description, $image_url, $price);
            if ($stmt->execute()) {
                $message = 'Thêm dịch vụ mới thành công!';
            } else {
                $error = 'Lỗi khi thêm: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    // CẬP NHẬT
    if (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if (empty($name) || $price <= 0 || $id <= 0) {
            $error = 'Tên, Giá, và ID dịch vụ là bắt buộc!';
        } else {
            // Nâng cấp: Thêm image_url (nếu có)
            $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
            $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, image_url = ?, price = ? WHERE id = ?");
            $stmt->bind_param("sssii", $name, $description, $image_url, $price, $id);
            if ($stmt->execute()) {
                $message = 'Cập nhật dịch vụ thành công!';
            } else {
                $error = 'Lỗi khi cập nhật: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// XỬ LÝ GET (XÓA hoặc LẤY DỮ LIỆU ĐỂ SỬA)
if (isset($_GET['action'])) {
    
    // XÓA DỊCH VỤ
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Xóa dịch vụ thành công!';
        } else {
            if ($conn->errno == 1451) { 
                $error = 'Lỗi: Không thể xóa dịch vụ này vì đã có khách hàng đặt.';
            } else {
                $error = 'Lỗi khi xóa: ' . $stmt->error;
            }
        }
        $stmt->close();
        $redirect_url = $message ? '?message=' . urlencode($message) : '?error=' . urlencode($error);
        header('Location: ' . BASE_URL . 'admin/services.php' . $redirect_url);
        exit();
    }

    // LẤY DỮ LIỆU ĐỂ SỬA
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?"); // Lấy tất cả cột
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $service_data = $result->fetch_assoc();
            // Đổ dữ liệu vào biến form
            $form_action = 'update';
            $form_id = $service_data['id'];
            $form_name = $service_data['name'];
            $form_description = $service_data['description'];
            $form_image_url = $service_data['image_url']; // Thêm image_url
            $form_price = $service_data['price'];
            $form_button_text = 'Cập nhật dịch vụ';
        }
        $stmt->close();
    }
}

if (isset($_GET['message'])) { $message = htmlspecialchars($_GET['message']); }
if (isset($_GET['error'])) { $error = htmlspecialchars($_GET['error']); }

// 4. LẤY TẤT CẢ DỊCH VỤ (ĐỂ HIỂN THỊ RA BẢNG)
$services_result = $conn->query("SELECT * FROM services ORDER BY id DESC");
$services = $services_result->fetch_all(MYSQLI_ASSOC);

include '../templates/header.php'; // 5. HIỂN THỊ GIAO DIỆN
?>

<div class="admin-nav">
    <div class="admin-nav-container">
        <a href="<?php echo BASE_URL; ?>admin/index.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>admin/services.php" class="active">
            <i class="fas fa-cogs"></i> Quản lý Dịch vụ
        </a>
        <a href="<?php echo BASE_URL; ?>admin/users.php">
            <i class="fas fa-users-cog"></i> Quản lý User
        </a>
    </div>
</div>
<style>
/* === CSS CHO MENU ADMIN (ĐÃ SỬA LỖI) === */
.admin-nav {
    background: #343a40;
    padding: 10px 0;
    position: sticky; 
    top: 72px; /* Dính vào dưới header */
    z-index: 999; 
    margin-bottom: 30px;
}
.admin-nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}
.admin-nav a {
    color: #f8f9fa;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    transition: background-color 0.3s ease;
}
.admin-nav a:hover {
    background-color: #495057;
}
.admin-nav a.active {
    background-color: var(--primary, #4F46E5);
    color: white;
}
/* === HẾT CSS MENU ADMIN === */


.admin-container {
    min-height: calc(100vh - 72px);
    padding: 30px 20px; /* Giảm padding trên cùng vì menu đã dính */
    background: var(--bg-light);
}
.admin-grid {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 30px;
}
.admin-box {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
}
.admin-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}
.admin-header i { font-size: 24px; color: var(--primary); }
.admin-header h2 { font-size: 22px; color: #333; margin: 0; }

/* Thông báo */
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; font-size: 15px; }
.message-box.success { background: #efe; color: #3c3; border: 1px solid #cfc; }
.message-box.error { background: #fee; color: #c33; border: 1px solid #fcc; }

/* Style cho Form (Cột 1) */
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
.form-group input, .form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f8f9fa;
    font-family: 'Inter', sans-serif;
}
.form-group textarea { resize: vertical; min-height: 100px; }
.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}
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
    transition: all 0.3s ease;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3); }

/* Style cho Bảng (Cột 2) */
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; }
.service-name { font-weight: 600; color: var(--text-dark); }
.service-desc {
    font-size: 13px;
    color: var(--text-light);
    max-width: 350px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.service-price { font-weight: 700; color: var(--primary); }
.action-buttons { display: flex; gap: 8px; }
.btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}
.btn-edit { background: #e0f2fe; color: #2980b9; }
.btn-edit:hover { background: #2980b9; color: white; }
.btn-delete { background: #fee; color: #c0392b; }
.btn-delete:hover { background: #c0392b; color: white; }

/* Responsive */
@media (max-width: 1024px) {
    .admin-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .admin-table { display: block; overflow-x: auto; white-space: nowrap; }
}
</style>
<div class="admin-container">

    <?php if ($message): ?>
        <div class="message-box success">
            <i class="fas fa-check-circle"></i> <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message-box error">
            <i class="fas fa-exclamation-circle"></i> <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="admin-grid">
        
        <div class="admin-box form-box">
            <div class="admin-header">
                <i class="fas <?php echo ($form_action == 'update') ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <h2><?php echo ($form_action == 'update') ? 'Sửa Dịch vụ' : 'Thêm Dịch vụ mới'; ?></h2>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>admin/services.php">
                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                <input type="hidden" name="id" value="<?php echo $form_id; ?>">

                <div class="form-group">
                    <label for="name">Tên dịch vụ <span>*</span></label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($form_name ?? ''); ?>"
                           placeholder="Ví dụ: Vệ sinh & Bảo trì Laptop">
                </div>

                <div class="form-group">
                    <label for="price">Giá (VNĐ) <span>*</span></label>
                    <input type="number" id="price" name="price" required 
                           value="<?php echo htmlspecialchars($form_price ?? ''); ?>"
                           placeholder="Ví dụ: 150000">
                </div>

                <!-- <div class="form-group">
                    <label for="image_url">Link Hình ảnh (Tùy chọn)</label>
                    <input type="text" id="image_url" name="image_url" 
                           value="<?php echo htmlspecialchars($form_image_url ?? ''); ?>"
                           placeholder="Dán URL hình ảnh minh họa...">
                </div> -->

                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" 
                              placeholder="Mô tả chi tiết về dịch vụ..."><?php echo htmlspecialchars($form_description ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> <?php echo $form_button_text; ?>
                </button>
                
                <?php if ($form_action == 'update'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/services.php" style="display: block; text-align: center; margin-top: 15px; color: #777; text-decoration: none;">
                        Hủy cập nhật
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-box table-box">
            <div class="admin-header">
                <i class="fas fa-cogs"></i>
                <h2>Danh sách Dịch vụ (<?php echo count($services); ?>)</h2>
            </div>
            
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Dịch vụ</th>
                            <th>Mô tả</th>
                            <th>Giá</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">Chưa có dịch vụ nào.</td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td><strong class="service-name"><?php echo htmlspecialchars($service['name']); ?></strong></td>
                                <td><div class="service-desc"><?php echo htmlspecialchars($service['description']); ?></div></td>
                                <td><strong class="service-price"><?php echo number_format($service['price'], 0, ",", "."); ?> VNĐ</strong></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>admin/services.php?action=edit&id=<?php echo $service['id']; ?>" 
                                           class="btn-action btn-edit" title="Sửa">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>admin/services.php?action=delete&id=<?php echo $service['id']; ?>" 
                                           class="btn-action btn-delete" 
                                           title="Xóa"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
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