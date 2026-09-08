<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once 'connection/config.php';

function column_exists($conn, $table, $column)
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $safeColumn = $conn->real_escape_string((string) $column);
    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $result = $conn->query($sql);
    if (!$result) {
        return false;
    }
    return $result->num_rows > 0;
}

function get_column_type($conn, $table, $column)
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $safeColumn = $conn->real_escape_string((string) $column);
    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    return $row['Type'] ?? null;
}

function normalize_doctor_id($doctorId, $columnType)
{
    if (!$doctorId) {
        return '';
    }
    if ($columnType && stripos($columnType, 'int') !== false) {
        $numeric = (int) preg_replace('/\D+/', '', (string) $doctorId);
        return $numeric > 0 ? $numeric : 0;
    }
    return strtoupper((string) $doctorId);
}

function handle_upload($field, $upload_dir, array $allowed, $maxBytes)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_tmp = $_FILES[$field]['tmp_name'];
    $file_name = basename($_FILES[$field]['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed, true)) {
        throw new Exception('Invalid file type for ' . $field);
    }

    if ($_FILES[$field]['size'] > $maxBytes) {
        throw new Exception('File size exceeds limit for ' . $field);
    }

    $unique_name = $field . '_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_name;

    if (!move_uploaded_file($file_tmp, $file_path)) {
        throw new Exception('Failed to upload ' . $field);
    }

    return $file_path;
}

$hasPhotoPath = column_exists($conn, 'doctor', 'photo_path');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$required = [
    'f_name',
    'l_name',
    'email',
    'password',
    'dob',
    'gender',
    'doctor_id',
    'specialization',
    'contact_no',
    'department',
    'qualification',
    'license_number',
    'joining_date'
];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing field: ' . $field]);
        exit();
    }
}

$full_name = trim($_POST['full_name'] ?? '');
$f_name = trim($_POST['f_name']);
$l_name = trim($_POST['l_name']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'] ?? '';
$dob = trim($_POST['dob']);
$gender = trim($_POST['gender']);
$doctor_id = trim($_POST['doctor_id']);
$specialization = trim($_POST['specialization']);
$department = trim($_POST['department'] ?? '');
$qualification = trim($_POST['qualification'] ?? '');
$license_number = trim($_POST['license_number'] ?? '');
$registration_council = trim($_POST['registration_council'] ?? '');
$joining_date = trim($_POST['joining_date'] ?? '');
$doctor_type = trim($_POST['doctor_type'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$consultation_fees = trim($_POST['consultation_fees'] ?? '');
$emergency_available = isset($_POST['emergency_available']) ? 1 : 0;
$online_consultation = isset($_POST['online_consultation']) ? 1 : 0;
$can_write_prescription = isset($_POST['can_write_prescription']) ? 1 : 0;
$working_days = isset($_POST['working_days']) && is_array($_POST['working_days']) ? implode(',', $_POST['working_days']) : '';
$available_from = trim($_POST['available_from'] ?? '');
$available_to = trim($_POST['available_to'] ?? '');
$break_start = trim($_POST['break_start'] ?? '');
$break_end = trim($_POST['break_end'] ?? '');
$username = trim($_POST['username'] ?? '');
$contact_no = trim($_POST['contact_no']);
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$country = trim($_POST['country'] ?? '');

if ($full_name !== '' && ($f_name === '' || $l_name === '')) {
    $parts = preg_split('/\s+/', $full_name);
    $f_name = $parts[0] ?? $f_name;
    $l_name = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ($l_name !== '' ? $l_name : 'NA');
}

if ($confirm_password !== '' && $confirm_password !== $password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit();
}

if ($experience === '') {
    $experience = '0';
}

if ($consultation_fees === '') {
    $consultation_fees = '0';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit();
}

$mobileDigits = preg_replace('/\D+/', '', $contact_no);
if ($mobileDigits === '' || strlen($mobileDigits) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number']);
    exit();
}

$doctorIdType = get_column_type($conn, 'doctor', 'doctor_id');
$doctor_id_db = normalize_doctor_id($doctor_id, $doctorIdType);

if ($doctor_id_db === '' || $doctor_id_db === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
    exit();
}

$check = $conn->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    $check->close();
    exit();
}
$check->close();

if ($doctor_id_db !== '') {
    $idCheck = $conn->prepare('SELECT 1 FROM doctor WHERE doctor_id = ? LIMIT 1');
    $idCheck->bind_param('s', $doctor_id_db);
    $idCheck->execute();
    $idCheck->store_result();
    if ($idCheck->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Doctor ID already exists']);
        $idCheck->close();
        exit();
    }
    $idCheck->close();
}

if ($license_number !== '' && column_exists($conn, 'doctor', 'license_number')) {
    $licenseCheck = $conn->prepare('SELECT 1 FROM doctor WHERE license_number = ? LIMIT 1');
    $licenseCheck->bind_param('s', $license_number);
    $licenseCheck->execute();
    $licenseCheck->store_result();
    if ($licenseCheck->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Medical license number already exists']);
        $licenseCheck->close();
        exit();
    }
    $licenseCheck->close();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$approval = 'approved';
$certificate_path = null;
$photo_path = null;
$digital_signature_path = null;
$degree_certificate_path = null;
$license_proof_path = null;
$id_proof_path = null;
$experience_certificate_path = null;

// Handle photo upload
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
    try {
        $photo_path = handle_upload('photo', 'uploads/Doctor_photos/', ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Handle document uploads
try {
    $digital_signature_path = handle_upload('digital_signature', 'uploads/Doctor_signatures/', ['jpg', 'jpeg', 'png', 'webp'], 3 * 1024 * 1024);
    $degree_certificate_path = handle_upload('degree_certificate', 'uploads/Doctor_docs/', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 5 * 1024 * 1024);
    $license_proof_path = handle_upload('license_proof', 'uploads/Doctor_docs/', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 5 * 1024 * 1024);
    $id_proof_path = handle_upload('id_proof', 'uploads/Doctor_docs/', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 5 * 1024 * 1024);
    $experience_certificate_path = handle_upload('experience_certificate', 'uploads/Doctor_docs/', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 5 * 1024 * 1024);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

$conn->begin_transaction();
try {
    $doctorColumns = [
        'f_name',
        'l_name',
        'dob',
        'gender',
        'doctor_id',
        'experience',
        'specialization',
        'consultation_fees',
        'contact_no',
        'address',
        'city',
        'state',
        'country',
        'email',
        'pass',
        'certificate_path',
        'approval'
    ];
    $doctorValues = [
        $f_name,
        $l_name,
        $dob,
        $gender,
        $doctor_id_db,
        $experience,
        $specialization,
        $consultation_fees,
        $contact_no,
        $address,
        $city,
        $state,
        $country,
        $email,
        $hashed_password,
        $certificate_path,
        $approval
    ];

    if ($hasPhotoPath) {
        $doctorColumns[] = 'photo_path';
        $doctorValues[] = $photo_path;
    }

    $optionalColumns = [
        'department' => $department,
        'qualification' => $qualification,
        'license_number' => $license_number,
        'registration_council' => $registration_council,
        'joining_date' => $joining_date,
        'doctor_type' => $doctor_type,
        'emergency_available' => $emergency_available,
        'online_consultation' => $online_consultation,
        'can_write_prescription' => $can_write_prescription,
        'working_days' => $working_days,
        'available_from' => $available_from,
        'available_to' => $available_to,
        'break_start' => $break_start,
        'break_end' => $break_end,
        'username' => $username,
        'digital_signature_path' => $digital_signature_path,
        'degree_certificate_path' => $degree_certificate_path,
        'license_proof_path' => $license_proof_path,
        'id_proof_path' => $id_proof_path,
        'experience_certificate_path' => $experience_certificate_path
    ];

    foreach ($optionalColumns as $column => $value) {
        if (!column_exists($conn, 'doctor', $column)) {
            continue;
        }
        if ($value === null || $value === '') {
            continue;
        }
        $doctorColumns[] = $column;
        $doctorValues[] = $value;
    }

    $doctorTypes = str_repeat('s', count($doctorValues));
    $doctorPlaceholders = implode(', ', array_fill(0, count($doctorValues), '?'));

    $insertDoctor = $conn->prepare(
        'INSERT INTO doctor (' . implode(', ', $doctorColumns) . ') VALUES (' . $doctorPlaceholders . ')'
    );
    $insertDoctor->bind_param($doctorTypes, ...$doctorValues);

    if (!$insertDoctor->execute()) {
        throw new Exception('Failed to insert doctor: ' . $insertDoctor->error);
    }
    $insertDoctor->close();

    $insertUser = $conn->prepare(
        "INSERT INTO users (email, fname, lname, password, user_type) VALUES (?, ?, ?, ?, 'doctor')"
    );
    $insertUser->bind_param('ssss', $email, $f_name, $l_name, $hashed_password);

    if (!$insertUser->execute()) {
        throw new Exception('Failed to create user account: ' . $insertUser->error);
    }
    $insertUser->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'doctor' => [
            'id' => $conn->insert_id,
            'doctor_id' => $doctor_id_db,
            'doctor_id_display' => strtoupper((string) $doctor_id),
            'f_name' => $f_name,
            'l_name' => $l_name,
            'email' => $email,
            'contact_no' => $contact_no,
            'specialization' => $specialization,
            'department' => $department,
            'joining_date' => $joining_date,
            'experience' => $experience,
            'consultation_fees' => $consultation_fees,
            'city' => $city
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
