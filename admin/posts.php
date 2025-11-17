<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL

$message = '';
$error = '';
$author_id = $_SESSION['user_id']; // Lấy ID của Admin đang đăng nhập

// Khai báo biến cho form
$form_action = 'add';
$form_id = '';
$form_title = '';
$form_content = '';
$form_image_url = '';
$form_button_text = 'Đăng bài viết';

// 3. LOGIC XỬ LÝ (POST, GET)

// XỬ LÝ POST (THÊM MỚI hoặc CẬP NHẬT)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // XÓA BỎ "mysqli_real_escape_string" VÌ ĐÃ CÓ BIND_PARAM
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image_url = $_POST['image_url'];

    // THÊM MỚI
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        if (empty($title) || empty($content)) {
            $error = 'Tiêu đề và Nội dung là bắt buộc!';
        } else {
            $stmt = $conn->prepare("INSERT INTO posts (title, content, image_url, author_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $content, $image_url, $author_id);
            if ($stmt->execute()) {
                $message = 'Đăng bài viết mới thành công!';
            } else {
                $error = 'Lỗi khi đăng bài: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    // CẬP NHẬT
    if (isset($_POST['action']) && $_POST['action'] == 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if (empty($title) || empty($content) || $id <= 0) {
            $error = 'Tiêu đề, Nội dung và ID bài viết là bắt buộc!';
        } else {
            $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, image_url = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $content, $image_url, $id);
            if ($stmt->execute()) {
                $message = 'Cập nhật bài viết thành công!';
            } else {
                $error = 'Lỗi khi cập nhật: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// XỬ LÝ GET (XÓA hoặc LẤY DỮ LIỆU ĐỂ SỬA)
if (isset($_GET['action'])) {
    
    // XÓA BÀI VIẾT
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Xóa bài viết thành công!';
        } else {
            $error = 'Lỗi khi xóa: ' . $stmt->error;
        }
        $stmt->close();
        $redirect_url = $message ? '?message=' . urlencode($message) : '?error=' . urlencode($error);
        header('Location: ' . BASE_URL . 'admin/posts.php' . $redirect_url);
        exit();
    }

    // LẤY DỮ LIỆU ĐỂ SỬA
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $post_data = $result->fetch_assoc();
            // Đổ dữ liệu vào biến form
            $form_action = 'update';
            $form_id = $post_data['id'];
            $form_title = $post_data['title'];
            $form_content = $post_data['content'];
            $form_image_url = $post_data['image_url'];
            $form_button_text = 'Cập nhật bài viết';
        }
        $stmt->close();
    }
}

if (isset($_GET['message'])) { $message = htmlspecialchars($_GET['message']); }
if (isset($_GET['error'])) { $error = htmlspecialchars($_GET['error']); }

// 4. LẤY TẤT CẢ BÀI VIẾT (ĐỂ HIỂN THỊ RA BẢNG)
$posts_result = $conn->query("SELECT p.id, p.title, p.created_at, u.name as author_name 
                              FROM posts p 
                              LEFT JOIN users u ON p.author_id = u.id 
                              ORDER BY p.created_at DESC");
$posts = $posts_result->fetch_all(MYSQLI_ASSOC);

include '../templates/header.php'; // 5. HIỂN THỊ GIAO DIỆN
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
.admin-grid { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.admin-box { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); }
.admin-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
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
.form-group textarea { 
    resize: vertical; 
    min-height: 250px; /* Cho ô nội dung cao hơn */
}
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
.post-title { font-weight: 600; color: var(--text-dark); }
.post-author { font-size: 13px; color: var(--text-light); }
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
    <div class="admin-nav">
        <div class="admin-nav-container">
            <a href="<?php echo BASE_URL; ?>admin/index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>admin/services.php">
                <i class="fas fa-cogs"></i> Quản lý Dịch vụ
            </a>
            <a href="<?php echo BASE_URL; ?>admin/users.php">
                <i class="fas fa-users-cog"></i> Quản lý User
            </a>
            <a href="<?php echo BASE_URL; ?>admin/contacts.php">
                <i class="fas fa-envelope"></i> Quản lý Tin nhắn
            </a>
            <a href="<?php echo BASE_URL; ?>admin/posts.php" class="active">
                <i class="fas fa-newspaper"></i> Quản lý Blog
            </a>
        </div>
    </div>
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
                <h2><?php echo ($form_action == 'update') ? 'Sửa Bài viết' : 'Viết Bài mới'; ?></h2>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>admin/posts.php">
                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                <input type="hidden" name="id" value="<?php echo $form_id; ?>">

                <div class="form-group">
                    <label for="title">Tiêu đề bài viết <span>*</span></label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($form_title); ?>"
                           placeholder="Ví dụ: 5 mẹo giúp máy tính chạy nhanh hơn">
                </div>

                <div class="form-group">
                    <label for="image_url">Link Hình ảnh Bìa (Tùy chọn)</label>
                    <input type="text" id="image_url" name="image_url" 
                           value="<?php echo htmlspecialchars($form_image_url); ?>"
                           placeholder="Dán URL hình ảnh minh họa (ví dụ: từ unsplash.com)">
                </div>

                <div class="form-group">
                    <label for="content">Nội dung <span>*</span></label>
                    <textarea id="content" name="content" required 
                              placeholder="Viết nội dung bài đăng của bạn ở đây..."><?php echo htmlspecialchars($form_content); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> <?php echo $form_button_text; ?>
                </button>
                
                <?php if ($form_action == 'update'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/posts.php" style="display: block; text-align: center; margin-top: 15px; color: #777; text-decoration: none;">
                        Hủy cập nhật
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-box table-box">
            <div class="admin-header">
                <i class="fas fa-list-alt"></i>
                <h2>Danh sách Bài viết (<?php echo count($posts); ?>)</h2>
            </div>
            
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Tác giả</th>
                            <th>Ngày đăng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">Chưa có bài viết nào.</td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><strong class="post-title"><?php echo htmlspecialchars($post['title']); ?></strong></td>
                                <td><span class="post-author"><?php echo htmlspecialchars($post['author_name'] ?? 'N/A'); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>admin/posts.php?action=edit&id=<?php echo $post['id']; ?>" 
                                           class="btn-action btn-edit" title="Sửa">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>admin/posts.php?action=delete&id=<?php echo $post['id']; ?>" 
                                           class="btn-action btn-delete" 
                                           title="Xóa"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?');">
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