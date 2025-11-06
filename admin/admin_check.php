<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra 2 điều kiện: 
// 1. Đã đăng nhập (isset($_SESSION['user_id']))
// 2. Có vai trò là 'admin' (lấy từ CSDL lúc đăng nhập)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    
    // Nếu không phải admin, đá về trang chủ (hoặc trang login)
    header("Location: ../index.php"); // Dùng ../ để quay lại thư mục gốc
    exit();
}

// Nếu là admin, code sẽ tiếp tục chạy...
?>