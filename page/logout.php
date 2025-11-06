<?php
session_start();

require '../config/db.php';

session_destroy();

header("Location: " . BASE_URL . "index.php");
exit();
?>