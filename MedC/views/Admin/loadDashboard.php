<?php
session_start();
include("../../connection/config.php");
require_once __DIR__ . "/dashboard_overview_fragment.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || strtolower(trim((string) ($_SESSION['user_type'] ?? ''))) !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

renderAdminDashboardOverview($conn, $localhost);
