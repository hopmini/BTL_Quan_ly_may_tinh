<?php
require 'admin_check.php'; // 1. BẢO VỆ TRANG
require '../config/db.php'; // 2. KẾT NỐI CSDL
// Hàm rút gọn nội dung để hiển thị tóm tắt
function get_excerpt($content, $length = 100) {
    $content = strip_tags($content); 
    if (strlen($content) > $length) {
        $excerpt = substr($content, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        $excerpt = substr($excerpt, 0, $last_space);
        return $excerpt . '...';
    }
    return $content;
}
$message = '';
$error = '';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all'; // (Dùng cho menu)

// === 1. XỬ LÝ HÀNH ĐỘNG (GET) ===

// A. XỬ LÝ ĐÁNH DẤU LÀ "ĐÃ ĐỌC"
if (isset($_GET['action']) && $_GET['action'] == 'mark_read' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    if ($stmt->execute()) {
        $message = "Đã đánh dấu tin nhắn là đã đọc.";
    } else {
        $error = "Lỗi khi cập nhật: " . $stmt->error;
    }
    $stmt->close();
    // Chuyển hướng để xóa tham số GET
    header('Location: ' . BASE_URL . 'admin/contacts.php?message=' . urlencode($message));
    exit();
}

// B. XỬ LÝ XÓA TIN NHẮN
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    if ($stmt->execute()) {
        $message = "Đã xóa tin nhắn thành công.";
    } else {
        $error = "Lỗi khi xóa: " . $stmt->error;
    }
    $stmt->close();
    // Chuyển hướng
    header('Location: ' . BASE_URL . 'admin/contacts.php?message=' . urlencode($message));
    exit();
}

// Lấy thông báo (nếu có từ redirect)
if (isset($_GET['message'])) { $message = htmlspecialchars($_GET['message']); }
if (isset($_GET['error'])) { $error = htmlspecialchars($_GET['error']); }

// === 2. LẤY TẤT CẢ TIN NHẮN TỪ CSDL ===
// Sắp xếp: Tin "Chưa đọc" (is_read = 0) lên đầu, sau đó là tin mới nhất
$stmt_messages = $conn->prepare("SELECT * FROM contacts ORDER BY is_read ASC, created_at DESC");
$stmt_messages->execute();
$messages_result = $stmt_messages->get_result();
$all_messages = $messages_result->fetch_all(MYSQLI_ASSOC);
$stmt_messages->close();

include '../templates/header.php';
?>

<style>
/* Menu Admin (Giữ nguyên) */
.admin-nav { background: #343a40; padding: 10px 0; position: sticky; top: 72px; z-index: 999; margin-bottom: 30px; }
.admin-nav-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: center; gap: 15px; }
.admin-nav a { color: #f8f9fa; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: background-color 0.3s ease; }
.admin-nav a:hover { background-color: #495057; }
.admin-nav a.active { background-color: var(--primary, #4F46E5); color: white; }

/* Layout chung */
.admin-container { min-height: calc(100vh - 72px); padding: 30px 20px; background: var(--bg-light); }
.admin-content { max-width: 1400px; margin: 0 auto; }
.admin-box { background: white; border-radius: 20px; padding: 30px 40px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05); margin-bottom: 30px; }
.admin-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
.admin-header i { font-size: 22px; color: var(--primary); }
.admin-header h2 { font-size: 20px; font-weight: 700; color: #333; margin: 0; }

/* Thông báo */
.message-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; font-size: 15px; }
.message-box.success { background: #efe; color: #3c3; border: 1px solid #cfc; }
.message-box.error { background: #fee; color: #c33; border: 1px solid #fcc; }

/* Bảng Tin nhắn */
.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.admin-table th, .admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-table th { background-color: #f8f9fa; color: var(--text-light); font-size: 12px; font-weight: 600; text-transform: uppercase; }
.admin-table td { font-size: 14px; }
/* === NÂNG CẤP: CSS CHO MODAL POPUP === */
.modal-overlay {
    position: fixed; /* Che toàn bộ màn hình */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6); /* Lớp nền mờ */
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000; /* Luôn ở trên cùng */
    animation: fadeIn 0.3s ease;
}

.modal-box {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 600px; /* Độ rộng của popup */
    position: relative;
    padding: 30px 40px;
    animation: slideDown 0.4s ease;
}

.modal-close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    background: #f1f1f1;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    font-size: 20px;
    color: #999;
    cursor: pointer;
    transition: all 0.3s ease;
}
.modal-close-btn:hover {
    background: #e0e0e0;
    color: #333;
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}
.modal-header i {
    font-size: 24px;
    color: var(--primary);
}
.modal-header h3 {
    font-size: 22px;
    color: var(--text-dark);
    margin: 0;
}
.modal-subheader {
    font-size: 14px;
    color: var(--text-light);
    padding: 10px 0;
}

.modal-body {
    margin-top: 20px;
}
.modal-body h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 10px;
}
.modal-body p {
    font-size: 16px;
    color: #333;
    line-height: 1.7;
    white-space: normal; /* Cho phép xuống dòng */
    max-height: 40vh; /* Giới hạn chiều cao nếu tin quá dài */
    overflow-y: auto; /* Tự động cuộn */
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

/* Keyframes cho animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}
/* NÂNG CẤP: Highlight tin "Chưa đọc" */
.message-unread {
    background-color: #f8f9fa; /* Nền mờ */
}
.message-unread td {
    font-weight: 600; /* In đậm chữ */
    color: var(--text-dark);
}
.message-unread .message-content {
    color: var(--text-dark); /* In đậm nội dung */
}

.user-info { font-weight: 600; color: var(--text-dark); }
.user-email { font-size: 13px; color: var(--text-light); }
.message-content {
    font-size: 14px;
    color: var(--text-light);
    white-space: normal;
    min-width: 300px;
    max-width: 450px;
    line-height: 1.6;
}
/* Nút Hành động */
.action-buttons { display: flex; gap: 8px; }
.btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.3s ease;
}
/* Nút Đánh dấu đã đọc (Con mắt) */
.btn-read { background: #e0f2fe; color: #2980b9; }
.btn-read:hover { background: #2980b9; color: white; }
/* Nút Xóa (Thùng rác) */
.btn-delete { background: #fee; color: #c0392b; }
.btn-delete:hover { background: #c0392b; color: white; }

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
                <a href="<?php echo BASE_URL; ?>admin/contacts.php" class="active">
                    <i class="fas fa-envelope"></i> Quản lý Tin nhắn
                </a>
                <a href="<?php echo BASE_URL; ?>admin/posts.php">
                    <i class="fas fa-newspaper"></i> Quản lý Blog
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
                <i class="fas fa-envelope"></i>
                <h2>Hòm thư Liên hệ (<?php echo count($all_messages); ?>)</h2>
            </div>
            
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Người gửi</th>
                            <th>Email</th>
                            <th>Nội dung Tin nhắn</th>
                            <th>Ngày gửi</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_messages)): ?>
                            <tr><td colspan="5" style="text-align: center;">Chưa có tin nhắn nào.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($all_messages as $msg): ?>
                            <tr class="<?php echo ($msg['is_read'] == 0) ? 'message-unread' : ''; ?>">
                                <td><strong class="user-info"><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                                <td><span class="user-email"><?php echo htmlspecialchars($msg['email']); ?></span></td>
                                <td class="message-content">
                                    <?php echo htmlspecialchars(get_excerpt($msg['message'])); ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-read" 
                                                title="Xem chi tiết" 
                                                onclick='openMessageModal(
                                                    <?php echo json_encode($msg['name']); ?>, 
                                                    <?php echo json_encode($msg['email']); ?>, 
                                                    <?php echo json_encode(nl2br(htmlspecialchars($msg['message']))); ?>
                                                )'>
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($msg['is_read'] == 0): ?>
                                            <a href="<?php echo BASE_URL; ?>admin/contacts.php?action=mark_read&id=<?php echo $msg['id']; ?>" 
                                            class="btn-action btn-edit" title="Đánh dấu đã đọc">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?php echo BASE_URL; ?>admin/contacts.php?action=delete&id=<?php echo $msg['id']; ?>" 
                                        class="btn-action btn-delete" title="Xóa tin nhắn"
                                        onclick="return confirm('Bạn có chắc muốn xóa vĩnh viễn tin nhắn này?');">
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
<div id="messageModalOverlay" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeMessageModal()">&times;</button>
        
        <div class="modal-header">
            <i class="fas fa-user-circle"></i>
            <h3 id="modalSenderName">Tên Người Gửi</h3>
        </div>
        <div class="modal-subheader">
            <span id="modalSenderEmail">email@example.com</span>
        </div>
        
        <div class="modal-body">
            <h4>Nội dung tin nhắn:</h4>
            <p id="modalMessageContent">Đây là nội dung tin nhắn...</p>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
<script>
    // Lấy các element của Modal
    const modalOverlay = document.getElementById('messageModalOverlay');
    const modalName = document.getElementById('modalSenderName');
    const modalEmail = document.getElementById('modalSenderEmail');
    const modalContent = document.getElementById('modalMessageContent');

    /**
     * Mở Modal và điền thông tin
     */
    function openMessageModal(name, email, message) {
        // Điền dữ liệu vào
        modalName.innerText = name;
        modalEmail.innerText = email;
        modalContent.innerHTML = message; // Dùng innerHTML để giữ lại <br>
        
        // Hiển thị Modal
        modalOverlay.style.display = 'flex';
    }

    /**
     * Đóng Modal
     */
    function closeMessageModal() {
        modalOverlay.style.display = 'none';
    }

    // Tùy chọn: Đóng Modal khi bấm ra ngoài nền mờ
    modalOverlay.addEventListener('click', function(event) {
        if (event.target === modalOverlay) {
            closeMessageModal();
        }
    });
</script>