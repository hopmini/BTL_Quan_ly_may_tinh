<?php
// THÊM DÒNG NÀY VÀO ĐẦU FILE
define('BASE_URL', 'http://localhost/computer_service/');

// --- Code cũ của bạn bên dưới ---
$host = "localhost";
$user = "root"; 
$password = "Hop13102005@"; 
$dbname = "computer_service_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>