<?php
session_start();
include("../../connection/config.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location:" . $localhost . "login.php");
    exit();
}

if (strtolower(trim((string) ($_SESSION['user_type'] ?? ''))) !== 'admin') {
    header("Location:" . $localhost . "include/dashboard_link.php");
    exit();
}

header("Location:" . $localhost . "admin_portal.php");
exit();
