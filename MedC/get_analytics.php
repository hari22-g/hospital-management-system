<?php
// get_analytics.php - Analytics and reporting data
session_start();
header('Content-Type: application/json');

require_once 'connection/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$metric = trim($_GET['metric'] ?? 'dashboard');
$from_date = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date = trim($_GET['to_date'] ?? date('Y-m-d'));

if ($metric === 'dashboard') {
    // Get dashboard metrics
    $today = date('Y-m-d');

    // Today's appointments
    $appts = $conn->query("SELECT COUNT(*) as count FROM appointment WHERE appointment_date = '$today' AND status != 'Cancelled'")->fetch_assoc();

    // Today's revenue
    $revenue = $conn->query(
        "SELECT SUM(consultation_fees) as total FROM appointment a 
         JOIN doctor d ON a.did = d.d_id 
         WHERE a.appointment_date = '$today' AND a.status = 'Confirmed'"
    )->fetch_assoc();

    // New patients this month
    $patients = $conn->query(
        "SELECT COUNT(*) as count FROM patient WHERE MONTH(FROM_UNIXTIME(UNIX_TIMESTAMP())) = MONTH(CURDATE()) 
         AND YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP())) = YEAR(CURDATE())"
    )->fetch_assoc();

    // Pending invoices
    $invoices = $conn->query("SELECT COUNT(*) as count FROM invoices WHERE status IN ('Partial', 'Pending')")->fetch_assoc();

    // Low stock items
    $stock = $conn->query(
        "SELECT COUNT(*) as count FROM medications WHERE stock_level <= low_stock_threshold AND stock_level > 0"
    )->fetch_assoc();

    // Expiring soon
    $expiring = $conn->query(
        "SELECT COUNT(*) as count FROM medications WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND stock_level > 0"
    )->fetch_assoc();

    echo json_encode([
        'success' => true,
        'metrics' => [
            'today_appointments' => intval($appts['count']),
            'today_revenue' => floatval($revenue['total'] ?? 0),
            'new_patients_month' => intval($patients['count']),
            'pending_invoices' => intval($invoices['count']),
            'low_stock_items' => intval($stock['count']),
            'expiring_soon' => intval($expiring['count'])
        ]
    ]);

} elseif ($metric === 'revenue') {
    // Revenue by date range
    $revenueData = $conn->query(
        "SELECT DATE(a.appointment_date) as date, SUM(d.consultation_fees) as total 
         FROM appointment a 
         JOIN doctor d ON a.did = d.d_id 
         WHERE a.appointment_date BETWEEN '$from_date' AND '$to_date' AND a.status = 'Confirmed'
         GROUP BY DATE(a.appointment_date) ORDER BY DATE(a.appointment_date)"
    );

    $data = [];
    while ($row = $revenueData->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'metric_type' => 'revenue'
    ]);

} elseif ($metric === 'appointments') {
    // Appointments by status
    $statusData = $conn->query(
        "SELECT status, COUNT(*) as count FROM appointment 
         WHERE appointment_date BETWEEN '$from_date' AND '$to_date'
         GROUP BY status"
    );

    $data = [];
    while ($row = $statusData->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'metric_type' => 'appointments'
    ]);

} elseif ($metric === 'doctor_performance') {
    // Top doctors by appointments
    $doctorData = $conn->query(
        "SELECT d.d_id, d.f_name, d.l_name, d.specialization, COUNT(a.appointment_id) as appointments, 
         SUM(d.consultation_fees) as revenue
         FROM doctor d 
         LEFT JOIN appointment a ON d.d_id = a.did AND a.appointment_date BETWEEN '$from_date' AND '$to_date'
         GROUP BY d.d_id ORDER BY appointments DESC LIMIT 10"
    );

    $data = [];
    while ($row = $doctorData->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'metric_type' => 'doctor_performance'
    ]);

} elseif ($metric === 'patient_demographics') {
    // Patient distribution by gender
    $genderData = $conn->query(
        "SELECT gender, COUNT(*) as count FROM patient GROUP BY gender"
    );

    $data = [];
    while ($row = $genderData->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'metric_type' => 'patient_demographics'
    ]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid metric']);
}
?>
