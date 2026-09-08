<?php
session_start();
include 'connection/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_type = strtolower(trim((string) ($_SESSION['user_type'] ?? '')));
if (!in_array($user_type, ['pharmacist', 'admin'])) {
    die('Access denied.');
}

$prescription_id = (int) ($_POST['prescription_id'] ?? 0);
$status = trim((string) ($_POST['status'] ?? 'Pending'));
$allowed = ['Pending', 'Processing', 'Ready', 'Dispensed'];

if ($prescription_id <= 0 || !in_array($status, $allowed, true)) {
    header('Location: pharmacy_panel.php');
    exit();
}

$stmt = $conn->prepare("UPDATE prescriptions SET status = ? WHERE prescription_id = ?");
$stmt->bind_param('si', $status, $prescription_id);
$stmt->execute();
$stmt->close();

header('Location: pharmacy_panel.php?prescription_id=' . $prescription_id . '&status=updated');
exit();
?>