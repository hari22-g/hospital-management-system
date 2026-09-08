<?php
session_start();
header('Content-Type: application/json');
require_once 'connection/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$nextId = null;
$autoRes = $conn->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctor'");
if ($autoRes && $row = $autoRes->fetch_assoc()) {
    $nextId = (int) ($row['AUTO_INCREMENT'] ?? 0);
}

if (!$nextId) {
    $fallback = $conn->query('SELECT MAX(d_id) AS max_id FROM doctor');
    if ($fallback && $row = $fallback->fetch_assoc()) {
        $nextId = (int) ($row['max_id'] ?? 0) + 1;
    } else {
        $nextId = 1;
    }
}

$doctorCode = 'DOC-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);

echo json_encode(['success' => true, 'doctor_id' => $doctorCode]);
?>