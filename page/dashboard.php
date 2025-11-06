<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include '../templates/header.php';
?>

<h2>Chào mừng, <?php echo $_SESSION['name']; ?>!</h2>
<p>Role: <?php echo $_SESSION['role']; ?></p>
<a href="logout.php">Đăng xuất</a>

<?php include 'templates/footer.php'; ?>
