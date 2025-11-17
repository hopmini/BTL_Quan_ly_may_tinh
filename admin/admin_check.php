<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    
    // Nếu không phải admin, đá về trang chủ (hoặc trang login)
    header("Location: ../index.php"); // Dùng ../ để quay lại thư mục gốc
    exit();
}

?>