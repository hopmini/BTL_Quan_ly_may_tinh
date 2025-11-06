<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

$message = '';
$error = '';
$current_admin_id = $_SESSION['user_id']; // Lấy ID của admin đang đăng nhập

// XỬ LÝ KHI ADMIN THAY ĐỔI VAI TRÒ (ROLE) CỦA USER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_role') {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];

    // Chỉ cho phép 2 vai trò: 'user' hoặc 'admin'
    if ($new_role !== 'user' && $new_role !== 'admin') {
        $error = 'Vai trò không hợp lệ!';
    } 
    // KIỂM TRA QUAN TRỌNG: Ngăn admin tự hạ vai trò của chính mình
    elseif ($user_id_to_update === $current_admin_id) {
        $error = 'Bạn không thể thay đổi vai trò của chính mình!';
    } else {
        // Tiến hành cập nhật
        $stmt_update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_role, $user_id_to_update);
            if ($stmt_update->execute()) {
                $message = "Cập nhật vai trò cho user ID $user_id_to_update thành công!";
            } else {
                $error = "Lỗi khi cập nhật: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}

// LẤY TẤT CẢ USER TỪ CSDL
// Sắp xếp để admin lên đầu, user mới đăng ký lên đầu
$stmt_users = $conn->prepare("SELECT id, name, username, email, role, created_at FROM users ORDER BY role DESC, created_at DESC");
$stmt_users->execute();
$users_result = $stmt_users->get_result();
$all_users = $users_result->fetch_all(MYSQLI_ASSOC);
$stmt_users->close();

// Mảng vai trò để admin chọn
$role_options = ['user', 'admin'];

include '../templates/header.php'; // 3. HIỂN THỊ GIAO DIỆN
?>

<style>
/* Menu Admin */
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

/* Layout chung */
.admin-container {
    min-height: calc(100vh - 72px);
    padding: 30px 20px;
    background: var(--bg-light);
}
.admin-content {
    max-width: 1400px;
    margin: 0 auto;
}
.admin-box {
    background: white;
    border-radius: 20px;
    padding: 30px 40px;
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

/* Thông báo */
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; font-size: 15px; }
.message-box.success { background: #efe; color: #3c3; border: 1px solid #cfc; }
.message-box.error { background: #fee; color: #c33; border: 1px solid #fcc; }

/* Bảng User */
.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; }

.user-name { font-weight: 600; color: var(--text-dark); }
.user-email { font-size: 13px; color: var(--text-light); }
.user-role.admin { color: #e74c3c; font-weight: 700; }
.user-role.user { color: #3498db; }

/* Form cập nhật vai trò */
.role-form { display: flex; gap: 8px; align-items: center; }
.role-select {
    padding: 8px 12px;
    font-size: 14px;
    border: 2px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}
.btn-update {
    padding: 8px 15px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}
.btn-update:hover { background: var(--primary-dark); }
/* Vô hiệu hóa nút nếu là chính admin */
.btn-update:disabled, .role-select:disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .admin-table { display: block; overflow-x: auto; white-space: nowrap; }
}
</style>
        <div class="admin-nav">
            <div class="admin-nav-container">
                <a href="<?php echo BASE_URL; ?>admin/index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>admin/services.php">
                    <i class="fas fa-cogs"></i> Quản lý Dịch vụ
                </a>
                <a href="<?php echo BASE_URL; ?>admin/users.php" class="active">
                    <i class="fas fa-users-cog"></i> Quản lý User
                </a>
            </div>
        </div>
<div class="admin-container">
    <div class="admin-content">



        <?php if ($message): ?>
            <div class="message-box success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message-box error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-box">
            <div class="admin-header">
                <i class="fas fa-users-cog"></i>
                <h2>Quản lý Tài khoản (<?php echo count($all_users); ?>)</h2>
            </div>
            
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email / Username</th>
                            <th>Ngày tham gia</th>
                            <th>Vai trò (Cập nhật)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_users)): ?>
                            <tr><td colspan="5" style="text-align: center;">Không có tài khoản nào.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong class="user-name"><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td>
                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    <div><?php echo htmlspecialchars($user['username']); ?></div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="<?php echo BASE_URL; ?>admin/users.php" class="role-form">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        
                                        <select name="new_role" class="role-select" 
                                                <?php echo ($user['id'] === $current_admin_id) ? 'disabled' : ''; ?>>
                                            
                                            <?php foreach ($role_options as $role): ?>
                                                <option value="<?php echo $role; ?>" <?php echo ($role == $user['role']) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($role); // Viết hoa chữ cái đầu ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <button type="submit" class="btn-update"
                                                <?php echo ($user['id'] === $current_admin_id) ? 'disabled' : ''; ?>>
                                            Lưu
                                        </button>
                                    </form>
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