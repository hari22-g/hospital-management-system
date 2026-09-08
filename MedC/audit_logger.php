<?php
// audit_logger.php - Audit logging and security
session_start();
header('Content-Type: application/json');

require_once 'connection/config.php';

// Log user action automatically
function logAuditEntry($user_id, $action, $resource, $resource_id, $changes = null, $status = 'success') {
    global $conn;
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $changesJson = !empty($changes) ? json_encode($changes) : null;

    $insert = $conn->prepare(
        "INSERT INTO audit_logs (user_id, action, resource, resource_id, changes, ip_address, user_agent, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insert->bind_param(
        'ississs',
        $user_id, $action, $resource, $resource_id, $changesJson, $ipAddress, $userAgent, $status
    );

    return $insert->execute();
}

// Get audit logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $action_filter = trim($_GET['action'] ?? '');
    $user_id_filter = intval($_GET['user_id'] ?? 0);
    $days = intval($_GET['days'] ?? 30);
    $from_date = date('Y-m-d H:i:s', strtotime("-$days days"));

    $query = "SELECT l.*, u.fname, u.lname FROM audit_logs l 
              LEFT JOIN users u ON l.user_id = u.user_id 
              WHERE l.created_at >= ?";
    $params = [$from_date];
    $types = 's';

    if (!empty($action_filter)) {
        $query .= " AND l.action LIKE ?";
        $params[] = "%$action_filter%";
        $types .= 's';
    }

    if ($user_id_filter > 0) {
        $query .= " AND l.user_id = ?";
        $params[] = $user_id_filter;
        $types .= 'i';
    }

    $query .= " ORDER BY l.created_at DESC LIMIT 500";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'count' => count($logs)
    ]);
    exit();
}

// Log action on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $action = trim($_POST['action'] ?? '');
    $resource = trim($_POST['resource'] ?? '');
    $resource_id = intval($_POST['resource_id'] ?? 0);
    $changes = json_decode($_POST['changes'] ?? '{}', true);
    $status = trim($_POST['status'] ?? 'success');

    if (empty($action) || empty($resource)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action and resource required']);
        exit();
    }

    if (logAuditEntry($_SESSION['user_id'], $action, $resource, $resource_id, $changes, $status)) {
        echo json_encode([
            'success' => true,
            'message' => 'Action logged successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error logging action']);
    }
    exit();
}

?>
