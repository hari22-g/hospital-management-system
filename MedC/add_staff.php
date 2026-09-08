<?php
// add_staff.php - Add and manage staff with roles
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'connection/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = trim($_POST['action'] ?? '');

if ($action === 'add') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'receptionist');
    $department = trim($_POST['department'] ?? '');
    $hire_date = trim($_POST['hire_date'] ?? date('Y-m-d'));

    if (empty($fname) || empty($lname) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit();
    }

    // Check email exists
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    $checkEmail->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Create user
    $insertUser = $conn->prepare(
        "INSERT INTO users (fname, lname, email, password, user_type, role, phone, department, hire_date, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );

    $userType = ($role === 'doctor') ? 'doctor' : 'staff';
    $insertUser->bind_param('ssssssss', $fname, $lname, $email, $hashedPassword, $userType, $role, $phone, $department, $hire_date);
    
    if (!$insertUser->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating staff: ' . $conn->error]);
        exit();
    }

    $userId = $insertUser->insert_id;
    $insertUser->close();

    // Create staff profile
    $staffRole = $role;
    $qualifications = trim($_POST['qualifications'] ?? '');
    $experience = intval($_POST['experience'] ?? 0);
    $license = trim($_POST['license_number'] ?? '');
    $license_expiry = trim($_POST['license_expiry'] ?? null);
    $shift = trim($_POST['shift'] ?? 'flexible');
    $salary = floatval($_POST['salary'] ?? 0);
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');

    $insertStaff = $conn->prepare(
        "INSERT INTO staff_profiles (user_id, qualifications, experience_years, license_number, license_expiry, shift, salary, emergency_contact, emergency_phone)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insertStaff->bind_param(
        'isisdsss',
        $userId, $qualifications, $experience, $license, $license_expiry, $shift, $salary, $emergency_contact, $emergency_phone
    );
    $insertStaff->execute();
    $insertStaff->close();

    echo json_encode([
        'success' => true,
        'message' => 'Staff member added successfully',
        'staff' => [
            'user_id' => $userId,
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'role' => $role,
            'department' => $department,
            'phone' => $phone,
            'qualifications' => $qualifications,
            'experience_years' => $experience,
            'license_number' => $license,
            'license_expiry' => $license_expiry,
            'shift' => $shift,
            'salary' => $salary,
            'emergency_contact' => $emergency_contact,
            'emergency_phone' => $emergency_phone
        ]
    ]);

} elseif ($action === 'update') {
    $staff_id = intval($_POST['staff_id'] ?? intval($_POST['user_id'] ?? 0));
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');
    $experience = intval($_POST['experience'] ?? 0);
    $license = trim($_POST['license_number'] ?? '');
    $license_expiry = trim($_POST['license_expiry'] ?? null);
    $shift = trim($_POST['shift'] ?? 'flexible');
    $salary = floatval($_POST['salary'] ?? 0);
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');

    if ($staff_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
        exit();
    }

    // Update user info
    $updateUser = $conn->prepare(
        "UPDATE users SET fname = ?, lname = ?, email = ?, role = ?, phone = ?, department = ? WHERE user_id = ?"
    );
    $updateUser->bind_param('ssssssi', $fname, $lname, $email, $role, $phone, $department, $staff_id);
    if (!$updateUser->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating staff: ' . $conn->error]);
        exit();
    }
    $updateUser->close();

    // Update password if provided
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updatePass = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $updatePass->bind_param('si', $hashedPassword, $staff_id);
        $updatePass->execute();
        $updatePass->close();
    }

    // Update staff profile
    $updateStaff = $conn->prepare(
        "UPDATE staff_profiles SET qualifications = ?, experience_years = ?, license_number = ?, license_expiry = ?, shift = ?, salary = ?, emergency_contact = ?, emergency_phone = ? WHERE user_id = ?"
    );
    $updateStaff->bind_param(
        'sisdssssi',
        $qualifications, $experience, $license, $license_expiry, $shift, $salary, $emergency_contact, $emergency_phone, $staff_id
    );
    $updateStaff->execute();
    $updateStaff->close();

    echo json_encode([
        'success' => true,
        'message' => 'Staff updated successfully',
        'staff' => [
            'user_id' => $staff_id,
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'role' => $role,
            'department' => $department,
            'phone' => $phone,
            'qualifications' => $qualifications,
            'experience_years' => $experience,
            'license_number' => $license,
            'license_expiry' => $license_expiry,
            'shift' => $shift,
            'salary' => $salary,
            'emergency_contact' => $emergency_contact,
            'emergency_phone' => $emergency_phone
        ]
    ]);

} elseif ($action === 'toggle_status') {
    $staff_id = intval($_POST['staff_id'] ?? intval($_POST['user_id'] ?? 0));

    if ($staff_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
        exit();
    }

    // Get current status
    $getStatus = $conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
    $getStatus->bind_param('i', $staff_id);
    $getStatus->execute();
    $result = $getStatus->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Staff not found']);
        exit();
    }

    $row = $result->fetch_assoc();
    $new_status = $row['is_active'] === 1 ? 0 : 1;
    $getStatus->close();

    // Toggle status
    $update = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $update->bind_param('ii', $new_status, $staff_id);
    if (!$update->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
        exit();
    }
    $update->close();

    echo json_encode([
        'success' => true,
        'message' => 'Staff status updated successfully',
        'is_active' => $new_status
    ]);

} elseif ($action === 'remove') {
    $staff_id = intval($_POST['staff_id'] ?? intval($_POST['user_id'] ?? 0));

    if ($staff_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
        exit();
    }

    // Verify staff member exists
    $checkStaff = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND (user_type = 'staff' OR role IN ('receptionist', 'nurse', 'pharmacist', 'lab_technician'))");
    $checkStaff->bind_param('i', $staff_id);
    $checkStaff->execute();
    if ($checkStaff->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Staff member not found']);
        exit();
    }
    $checkStaff->close();

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related records from reports table
        $deleteReports = $conn->prepare("DELETE FROM reports WHERE user_id = ?");
        $deleteReports->bind_param('i', $staff_id);
        $deleteReports->execute();
        $deleteReports->close();

        // Delete related records from medications table
        $deleteMeds = $conn->prepare("DELETE FROM medications WHERE user_id = ?");
        $deleteMeds->bind_param('i', $staff_id);
        $deleteMeds->execute();
        $deleteMeds->close();

        // Delete related records from biomarker table
        $deleteBiomarker = $conn->prepare("DELETE FROM biomarker WHERE user_id = ?");
        $deleteBiomarker->bind_param('i', $staff_id);
        $deleteBiomarker->execute();
        $deleteBiomarker->close();

        // Delete related records from weight_tracker table
        $deleteWeight = $conn->prepare("DELETE FROM weight_tracker WHERE user_id = ?");
        $deleteWeight->bind_param('i', $staff_id);
        $deleteWeight->execute();
        $deleteWeight->close();

        // Delete from staff_profiles
        $deleteProfile = $conn->prepare("DELETE FROM staff_profiles WHERE user_id = ?");
        $deleteProfile->bind_param('i', $staff_id);
        $deleteProfile->execute();
        $deleteProfile->close();

        // Delete from users
        $deleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $deleteUser->bind_param('i', $staff_id);
        if (!$deleteUser->execute()) {
            throw new Exception('Error deleting user: ' . $conn->error);
        }
        $deleteUser->close();

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Staff member removed successfully'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit();
    }

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}
?>
