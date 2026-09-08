<?php
session_start();
header('Content-Type: application/json');

include('connection/config.php');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Admin login required.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

$appointmentId = intval($_POST['appointment_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowedStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

if ($appointmentId <= 0 || !in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid appointment ID or status'
    ]);
    exit();
}

$checkStmt = $conn->prepare('SELECT appointment_id, jitsi_meeting_link FROM appointment WHERE appointment_id = ? LIMIT 1');
$checkStmt->bind_param('i', $appointmentId);
$checkStmt->execute();
$appointmentResult = $checkStmt->get_result();

if (!$appointmentResult || $appointmentResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Appointment not found'
    ]);
    $checkStmt->close();
    exit();
}

$appointment = $appointmentResult->fetch_assoc();
$existingMeetingLink = $appointment['jitsi_meeting_link'] ?? '';
$checkStmt->close();

$meetingLink = $existingMeetingLink;
if ($status === 'Confirmed' && empty($meetingLink)) {
    $meetingId = uniqid('medc_');
    $meetingLink = 'https://meet.jit.si/' . $meetingId;
}

if ($status === 'Confirmed') {
    $updateStmt = $conn->prepare('UPDATE appointment SET status = ?, jitsi_meeting_link = ? WHERE appointment_id = ?');
    $updateStmt->bind_param('ssi', $status, $meetingLink, $appointmentId);
} else {
    $updateStmt = $conn->prepare('UPDATE appointment SET status = ? WHERE appointment_id = ?');
    $updateStmt->bind_param('si', $status, $appointmentId);
}

if (!$updateStmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update appointment status'
    ]);
    $updateStmt->close();
    exit();
}

$updateStmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Appointment ID ' . $appointmentId . ' updated to ' . $status,
    'appointment_id' => $appointmentId,
    'status' => $status
]);
?>
