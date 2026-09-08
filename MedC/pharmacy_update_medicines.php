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
$medicine_ids = $_POST['medicine_id'] ?? [];
$availability = $_POST['availability'] ?? [];
$dispensed = $_POST['dispensed_quantity'] ?? [];

if ($prescription_id <= 0 || empty($medicine_ids)) {
    header('Location: pharmacy_panel.php');
    exit();
}

$updateStmt = $conn->prepare(
    "UPDATE prescription_medicines SET availability = ?, dispensed_quantity = ? WHERE medicine_id = ? AND prescription_id = ?"
);

foreach ($medicine_ids as $index => $medicine_id) {
    $status = $availability[$index] ?? 'Available';
    $dispensedQty = (int) ($dispensed[$index] ?? 0);
    $medId = (int) $medicine_id;

    $updateStmt->bind_param('siii', $status, $dispensedQty, $medId, $prescription_id);
    $updateStmt->execute();
}
$updateStmt->close();

header('Location: pharmacy_panel.php?prescription_id=' . $prescription_id . '&updated=1');
exit();
?>