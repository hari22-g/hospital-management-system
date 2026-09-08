<?php
session_start();
header('Content-Type: application/json');
require_once 'connection/config.php';

function medc_is_admin_session(): bool
{
    return isset($_SESSION['user_type']) && strtolower(trim((string) $_SESSION['user_type'])) === 'admin';
}

if (!medc_is_admin_session()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
if ($doctor_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid doctor id']);
    exit();
}

$hasPhotoPath = false;
$columnResult = $conn->query("SHOW COLUMNS FROM `doctor` LIKE 'photo_path'");
if ($columnResult && $columnResult->num_rows > 0) {
    $hasPhotoPath = true;
}

$optionalColumns = [
    'department',
    'qualification',
    'license_number',
    'registration_council',
    'joining_date',
    'doctor_type',
    'emergency_available',
    'online_consultation',
    'can_write_prescription',
    'working_days',
    'available_from',
    'available_to',
    'break_start',
    'break_end',
    'username'
];

$selectColumns = [
    'd_id',
    'f_name',
    'l_name',
    'email',
    'dob',
    'gender',
    'doctor_id',
    'specialization',
    'experience',
    'consultation_fees',
    'contact_no',
    'address',
    'city',
    'state',
    'country'
];

foreach ($optionalColumns as $column) {
    $check = $conn->query("SHOW COLUMNS FROM `doctor` LIKE '{$column}'");
    if ($check && $check->num_rows > 0) {
        $selectColumns[] = $column;
    }
}

if ($hasPhotoPath) {
    $selectColumns[] = 'photo_path';
}

$selectSql = 'SELECT ' . implode(', ', $selectColumns) . ' FROM doctor WHERE d_id = ? LIMIT 1';
$stmt = $conn->prepare($selectSql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query']);
    exit();
}
$stmt->bind_param('i', $doctor_id);
$stmt->execute();

$result = $stmt->get_result();
$doctor = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$doctor) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Doctor not found']);
    exit();
}

echo json_encode(['success' => true, 'doctor' => $doctor]);
?>
