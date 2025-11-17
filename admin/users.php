<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

$message = '';
$error = '';
$current_admin_id = $_SESSION['user_id']; // Lấy ID của admin đang đăng nhập

// === 1. XỬ LÝ CÁC HÀNH ĐỘNG (POST & GET) ===

// A. XỬ LÝ POST (Cập nhật Vai trò, VIP, Blacklist)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_user_settings') {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];
    $is_vip = isset($_POST['is_vip']) ? 1 : 0;
    $is_blacklisted = isset($_POST['is_blacklisted']) ? 1 : 0;

    // SỬA LẠI: Chấp nhận 'nhanvien' (nếu bạn có)
    if (!in_array($new_role, ['user', 'admin', 'nhanvien'])) { // Dùng in_array cho dễ
        $error = 'Vai trò không hợp lệ!';
    } 
    elseif ($user_id_to_update === $current_admin_id) {
        $error = 'Bạn không thể thay đổi cài đặt của chính mình!';
    } else {
        $stmt_update = $conn->prepare("UPDATE users SET role = ?, is_vip = ?, is_blacklisted = ? WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("siii", $new_role, $is_vip, $is_blacklisted, $user_id_to_update);
            if ($stmt_update->execute()) {
                $message = "Cập nhật cài đặt cho user ID $user_id_to_update thành công!";
            } else {
                $error = "Lỗi khi cập nhật: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}

// B. XỬ LÝ GET (Xóa tài khoản)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    // (Logic Xóa tài khoản giữ nguyên)
    $user_id_to_delete = (int)$_GET['id'];
    if ($user_id_to_delete === $current_admin_id) {
        $error = 'Bạn không thể tự xóa tài khoản đang đăng nhập!';
    } else {
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_delete->bind_param("i", $user_id_to_delete);
        if ($stmt_delete->execute()) { $message = "Xóa tài khoản thành công!"; } 
        else { $error = "Lỗi khi xóa: " . $stmt_delete->error; }
        $stmt_delete->close();
    }
    $redirect_url = $message ? '?message=' . urlencode($message) : '?error=' . urlencode($error);
    header('Location: ' . BASE_URL . 'admin/users.php' . $redirect_url);
    exit();
}

if (isset($_GET['message'])) { $message = htmlspecialchars($_GET['message']); }
if (isset($_GET['error'])) { $error = htmlspecialchars($_GET['error']); }


// === 2. LẤY DỮ LIỆU USER (NÂNG CẤP VỚI TÌM KIẾM/LỌC) ===

// Lấy các tham số lọc từ URL (dùng GET)
$search_term = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? 'all';
$filter_status = $_GET['filter_status'] ?? 'all';

// Mảng điều kiện WHERE và tham số
$where_clauses = [];
$params = [];
$types = "";

// 1. Xử lý TÌM KIẾM
if (!empty($search_term)) {
    $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR u.phone LIKE ?)";
    $search_like = "%" . $search_term . "%";
    // Thêm 4 tham số cho 4 dấu ?
    $params = array_merge($params, [$search_like, $search_like, $search_like, $search_like]);
    $types .= "ssss";
}

// 2. Xử lý LỌC VAI TRÒ
if ($filter_role != 'all') {
    $where_clauses[] = "u.role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

// 3. Xử lý LỌC TRẠNG THÁI
if ($filter_status == 'vip') {
    $where_clauses[] = "u.is_vip = 1";
} elseif ($filter_status == 'blacklisted') {
    $where_clauses[] = "u.is_blacklisted = 1";
}

// Xây dựng câu SQL
$sql = "SELECT u.id, u.name, u.username, u.email, u.role, u.created_at, u.phone, u.address,
            u.is_vip, u.is_blacklisted,
            COUNT(b.id) as total_bookings
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id";

// Nối các điều kiện WHERE
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY u.id ORDER BY u.role DESC, u.created_at DESC";

// Chuẩn bị và thực thi
$stmt_users = $conn->prepare($sql);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$users_result = $stmt_users->get_result();
$all_users = $users_result->fetch_all(MYSQLI_ASSOC);
$stmt_users->close();

$role_options = ['user', 'admin', 'nhanvien']; // Thêm 'nhanvien' (nếu bạn muốn)

include '../templates/header.php';
?>

<style>
/* Menu Admin (Giữ nguyên) */
.admin-nav { background: #343a40; padding: 10px 0; position: sticky; top: 72px; z-index: 999; margin-bottom: 30px; }
.admin-nav-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: center; gap: 15px; }
.admin-nav a { color: #f8f9fa; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: background-color 0.3s ease; }
.admin-nav a:hover { background-color: #495057; }
.admin-nav a.active { background-color: var(--primary, #4F46E5); color: white; }

/* Layout chung (Giữ nguyên) */
.admin-container { min-height: calc(100vh - 72px); padding: 30px 20px; background: var(--bg-light); }
.admin-content { max-width: 1400px; margin: 0 auto; }
.admin-box { background: white; border-radius: 20px; padding: 30px 40px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); margin-bottom: 30px; }
.admin-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.admin-header i { font-size: 22px; color: var(--primary); }
.admin-header h2 { font-size: 20px; font-weight: 700; color: #333; margin: 0; }
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; font-size: 15px; }
.message-box.success { background: #efe; color: #3c3; border: 1px solid #cfc; }
.message-box.error { background: #fee; color: #c33; border: 1px solid #fcc; }

/* === NÂNG CẤP: CSS CHO FORM TÌM KIẾM/LỌC === */
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}
.filter-form .form-group {
    flex-grow: 1;
    margin-bottom: 0;
}
.filter-form .form-group.search-bar {
    flex-basis: 300px; /* Cho ô tìm kiếm rộng hơn */
}
.filter-form .form-group input,
.filter-form .form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    background: #f8f9fa;
}
.filter-form .filter-actions {
    display: flex;
    gap: 10px;
    align-items: flex-end; /* Căn nút thẳng hàng với input */
}
.btn-filter {
    padding: 12px 20px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-filter:hover { background: var(--primary-dark); }
.btn-clear-filter {
    padding: 12px 20px;
    background: #f1f1f1;
    color: var(--text-light);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}
.btn-clear-filter:hover { background: #e0e0e0; color: var(--text-dark); }
/* === HẾT CSS FORM === */


/* Bảng User (Giữ nguyên) */
.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
/* ... (Tất cả CSS của .admin-table, .user-name, .is-vip, .role-form, .btn-delete... giữ nguyên) ... */
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    white-space: nowrap;
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; }
.user-name { font-weight: 600; color: var(--text-dark); }
.user-contact-info { font-size: 13px; color: var(--text-light); line-height: 1.5; white-space: normal; min-width: 150px; }
.user-stats { font-weight: 700; color: #27ae60; text-align: center; min-width: 100px; }
.user-role-cell { min-width: 250px; }
.admin-table tr.is-vip { background-color: rgba(39, 174, 96, 0.05); }
.admin-table tr.is-blacklisted { background-color: rgba(231, 76, 60, 0.05); opacity: 0.7; }
.admin-table tr.is-blacklisted .user-name { text-decoration: line-through; }
.role-form { display: flex; flex-direction: column; gap: 10px; align-items: flex-start; }
.role-form-main { display: flex; gap: 8px; align-items: center; }
.role-select { padding: 8px 12px; font-size: 14px; border: 2px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.btn-update { padding: 8px 15px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.3s ease; }
.btn-update:hover { background: var(--primary-dark); }
.btn-update:disabled, .role-select:disabled { background: #ccc; cursor: not-allowed; opacity: 0.7; }
.status-toggles { display: flex; gap: 15px; font-size: 13px; font-weight: 500; }
.status-toggles label { display: flex; align-items: center; gap: 5px; cursor: pointer; }
.status-toggles .vip { color: #27ae60; }
.status-toggles .blacklist { color: #c0392b; }
.status-toggles input[type="checkbox"] { width: 16px; height: 16px; }
.action-buttons { display: flex; gap: 8px; }
.btn-delete { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; text-decoration: none; font-size: 13px; transition: all 0.3s ease; background: #fee; color: #c0392b; }
.btn-view { background: #e0f2fe; color: #2980b9; }
.btn-view:hover { background: #2980b9; color: white; }
.btn-delete:hover { background: #c0392b; color: white; }
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
                <a href="<?php echo BASE_URL; ?>admin/contacts.php">
                    <i class="fas fa-envelope"></i> Quản lý Tin nhắn
                </a>
                <a href="<?php echo BASE_URL; ?>admin/posts.php">
                    <i class="fas fa-newspaper"></i> Quản lý Blog
                </a>
            </div>
        </div>
<div class="admin-container">
    <div class="admin-content">

        <?php if ($message): ?><div class="message-box success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message-box error"><?php echo $error; ?></div><?php endif; ?>

        <div class="admin-box">
            <div class="admin-header">
                <i class="fas fa-users-cog"></i>
                <h2>Quản lý Tài khoản (<?php echo count($all_users); ?>)</h2>
            </div>
            
            <form method="GET" action="<?php echo BASE_URL; ?>admin/users.php" class="filter-form">
                <div class="form-group search-bar">
                    <input type="text" name="search" placeholder="Tìm theo Tên, Email, Username, SĐT..." 
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="form-group">
                    <select name="filter_role">
                        <option value="all">Tất cả Vai trò</option>
                        <option value="admin" <?php echo ($filter_role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo ($filter_role == 'user') ? 'selected' : ''; ?>>User</option>
                        </select>
                </div>
                <div class="form-group">
                    <select name="filter_status">
                        <option value="all">Tất cả Trạng thái</option>
                        <option value="vip" <?php echo ($filter_status == 'vip') ? 'selected' : ''; ?>>Chỉ xem VIP</option>
                        <option value="blacklisted" <?php echo ($filter_status == 'blacklisted') ? 'selected' : ''; ?>>Chỉ xem Blacklist</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Lọc</button>
                    <a href="<?php echo BASE_URL; ?>admin/users.php" class="btn-clear-filter">Xóa lọc</a>
                </div>
            </form>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Thông tin liên hệ</th>
                            <th>Ngày tham gia</th>
                            <th>Tổng đơn</th>
                            <th class="user-role-cell">Cài đặt (Vai trò & Trạng thái)</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_users)): ?>
                            <tr><td colspan="7" style="text-align: center;">Không tìm thấy tài khoản nào.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($all_users as $user): ?>
                            <tr class="<?php echo $user['is_vip'] ? 'is-vip' : ''; ?> <?php echo $user['is_blacklisted'] ? 'is-blacklisted' : ''; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td><strong class="user-name"><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td>
                                    <div class="user-contact-info">
                                        Email: <?php echo htmlspecialchars($user['email']); ?>
                                        <br>SĐT: <?php echo htmlspecialchars($user['phone'] ?? '---'); ?>
                                        <br>Địa chỉ: <?php echo htmlspecialchars($user['address'] ?? '---'); ?>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td class="user-stats"><?php echo $user['total_bookings']; ?></td>
                                <td>
                                    <form method="POST" action="<?php echo BASE_URL; ?>admin/users.php" class="role-form">
                                        <input type="hidden" name="action" value="update_user_settings">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php $is_self = ($user['id'] === $current_admin_id); ?>
                                        <div class="role-form-main">
                                            <select name="new_role" class="role-select" <?php echo $is_self ? 'disabled' : ''; ?>>
                                                <?php foreach ($role_options as $role): ?>
                                                    <option value="<?php echo $role; ?>" <?php echo ($role == $user['role']) ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($role); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-update" <?php echo $is_self ? 'disabled' : ''; ?>>
                                                Lưu
                                            </button>
                                        </div>
                                        <div class="status-toggles">
                                            <label class="vip">
                                                <input type="checkbox" name="is_vip" value="1" 
                                                       <?php echo $user['is_vip'] ? 'checked' : ''; ?> 
                                                       <?php echo $is_self ? 'disabled' : ''; ?>>
                                                VIP
                                            </label>
                                            <label class="blacklist">
                                                <input type="checkbox" name="is_blacklisted" value="1"
                                                       <?php echo $user['is_blacklisted'] ? 'checked' : ''; ?> 
                                                       <?php echo $is_self ? 'disabled' : ''; ?>>
                                                Blacklist
                                            </label>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>admin/user_details.php?id=<?php echo $user['id']; ?>" 
                                        class="btn-action btn-view" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if ($is_self): ?>
                                            <span style="color: #c0392b; font-weight: 600;">(Bạn)</span>
                                        <?php else: ?>
                                            <a href="<?php echo BASE_URL; ?>admin/users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                            class="btn-delete" title="Xóa tài khoản"
                                            onclick="return confirm('CẢNH BÁO: ...');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
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