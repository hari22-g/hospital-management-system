<?php
// manage_inventory.php - Stock tracking and medicine management
session_start();
header('Content-Type: application/json');

require_once 'connection/config.php';

if (!isset($_SESSION['user_type'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentUserId = intval($_SESSION['user_id'] ?? 0);
if ($currentUserId <= 0 && !empty($_SESSION['email'])) {
    $userLookup = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    if ($userLookup) {
        $userLookup->bind_param('s', $_SESSION['email']);
        $userLookup->execute();
        $userRow = $userLookup->get_result()->fetch_assoc();
        if (!empty($userRow['user_id'])) {
            $currentUserId = (int) $userRow['user_id'];
            $_SESSION['user_id'] = $currentUserId;
        }
        $userLookup->close();
    }
}

$action = trim($_POST['action'] ?? '');

if ($action === 'add_medicine') {
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $stock_level = intval($_POST['stock_level'] ?? 0);
    $expiry_date = trim($_POST['expiry_date'] ?? null);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $low_stock_threshold = intval($_POST['low_stock_threshold'] ?? 10);
    $vendor_id = intval($_POST['vendor_id'] ?? 0);

    if (empty($medicine_name) || empty($unit)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit();
    }

    // Check if medicine already exists
    $checkMedicine = $conn->prepare("SELECT drug_id FROM medications WHERE medicine_name = ?");
    $checkMedicine->bind_param('s', $medicine_name);
    $checkMedicine->execute();

    if ($checkMedicine->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Medicine already exists']);
        exit();
    }
    $checkMedicine->close();

    // Insert medicine
    $insertMedicine = $conn->prepare(
        "INSERT INTO medications (medicine_name, unit, stock_level, expiry_date, unit_price, low_stock_threshold, vendor_id, user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insertMedicine->bind_param(
        'ssiisiii',
        $medicine_name, $unit, $stock_level, $expiry_date, $unit_price, $low_stock_threshold, $vendor_id, $currentUserId
    );

    if (!$insertMedicine->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding medicine']);
        exit();
    }

    $medicineId = $insertMedicine->insert_id;
    $insertMedicine->close();

    // Log stock movement (purchase)
    $movementType = 'purchase';
    $logMovement = $conn->prepare(
        "INSERT INTO stock_movements (medicine_id, movement_type, quantity, created_by)
         VALUES (?, ?, ?, ?)"
    );

    $userId = $_SESSION['user_id'];
    $logMovement->bind_param('isii', $medicineId, $movementType, $stock_level, $userId);
    $logMovement->execute();
    $logMovement->close();

    echo json_encode([
        'success' => true,
        'message' => 'Medicine added successfully',
        'medicine_id' => $medicineId
    ]);

} elseif ($action === 'update_stock') {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $movement_type = trim($_POST['movement_type'] ?? 'adjustment');
    $quantity = intval($_POST['quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($medicine_id <= 0 || empty($quantity)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    // Get current stock
    $getMedicine = $conn->prepare("SELECT stock_level FROM medications WHERE drug_id = ?");
    $getMedicine->bind_param('i', $medicine_id);
    $getMedicine->execute();
    $result = $getMedicine->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Medicine not found']);
        exit();
    }

    $medicine = $result->fetch_assoc();
    $getMedicine->close();

    // Calculate new stock
    if ($movement_type === 'usage' || $movement_type === 'damaged' || $movement_type === 'expired') {
        $newStock = $medicine['stock_level'] - $quantity;
    } else {
        $newStock = $medicine['stock_level'] + $quantity;
    }

    if ($newStock < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit();
    }

    // Update stock
    $updateStock = $conn->prepare("UPDATE medications SET stock_level = ? WHERE drug_id = ?");
    $updateStock->bind_param('ii', $newStock, $medicine_id);
    $updateStock->execute();
    $updateStock->close();

    // Log movement
    $userId = $_SESSION['user_id'];
    $logMovement = $conn->prepare(
        "INSERT INTO stock_movements (medicine_id, movement_type, quantity, notes, created_by)
         VALUES (?, ?, ?, ?, ?)"
    );

    $logMovement->bind_param('isiss', $medicine_id, $movement_type, $quantity, $notes, $userId);
    $logMovement->execute();
    $logMovement->close();

    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'new_stock' => $newStock
    ]);

} elseif ($action === 'update_medicine') {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $unit_price = floatval($_POST['unit_price'] ?? 0);

    if ($medicine_id <= 0 || $medicine_name === '' || $unit_price < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid medicine details']);
        exit();
    }

    $checkMedicine = $conn->prepare("SELECT drug_id FROM medications WHERE medicine_name = ? AND drug_id <> ?");
    $checkMedicine->bind_param('si', $medicine_name, $medicine_id);
    $checkMedicine->execute();
    if ($checkMedicine->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Medicine name already exists']);
        exit();
    }
    $checkMedicine->close();

    $updateMedicine = $conn->prepare("UPDATE medications SET medicine_name = ?, unit_price = ? WHERE drug_id = ?");
    $updateMedicine->bind_param('sdi', $medicine_name, $unit_price, $medicine_id);
    if (!$updateMedicine->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to update medicine']);
        exit();
    }
    $updateMedicine->close();

    echo json_encode([
        'success' => true,
        'message' => 'Medicine updated successfully',
        'medicine_id' => $medicine_id,
        'medicine_name' => $medicine_name,
        'unit_price' => number_format($unit_price, 2, '.', '')
    ]);

} elseif ($action === 'save_inventory_medicine') {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $stock_level = intval($_POST['stock_level'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);

    if ($medicine_name === '' || $stock_level < 0 || $unit_price < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid medicine details']);
        exit();
    }

    $existingId = $medicine_id;

    if ($existingId <= 0) {
        $lookup = $conn->prepare("SELECT drug_id FROM medications WHERE medicine_name = ? LIMIT 1");
        $lookup->bind_param('s', $medicine_name);
        $lookup->execute();
        $lookupResult = $lookup->get_result();
        if ($lookupResult && $lookupResult->num_rows > 0) {
            $existingRow = $lookupResult->fetch_assoc();
            $existingId = (int) ($existingRow['drug_id'] ?? 0);
        }
        $lookup->close();
    }

    if ($existingId > 0) {
        $updateMedicine = $conn->prepare("UPDATE medications SET stock_level = ?, unit_price = ? WHERE drug_id = ?");
        $updateMedicine->bind_param('idi', $stock_level, $unit_price, $existingId);
        if (!$updateMedicine->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to update inventory item']);
            exit();
        }
        $updateMedicine->close();
    } else {
        $insertMedicine = $conn->prepare(
            "INSERT INTO medications (medicine_name, unit, stock_level, expiry_date, unit_price, low_stock_threshold, vendor_id, user_id)
             VALUES (?, 'Tablet', ?, NULL, ?, 10, 0, ?)"
        );
        $insertMedicine->bind_param('sidi', $medicine_name, $stock_level, $unit_price, $currentUserId);
        if (!$insertMedicine->execute()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to add inventory item']);
            exit();
        }
        $existingId = $insertMedicine->insert_id;
        $insertMedicine->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Inventory item saved successfully',
        'medicine_id' => $existingId,
        'medicine_name' => $medicine_name,
        'stock_level' => $stock_level,
        'unit_price' => number_format($unit_price, 2, '.', '')
    ]);

} elseif ($action === 'check_expiry') {
    // Get medicines expiring within 30 days
    $expiryDate = date('Y-m-d', strtotime('+30 days'));
    $getExpiring = $conn->prepare(
        "SELECT drug_id, medicine_name, expiry_date, stock_level FROM medications 
         WHERE expiry_date IS NOT NULL AND expiry_date <= ? AND stock_level > 0 ORDER BY expiry_date ASC"
    );

    $getExpiring->bind_param('s', $expiryDate);
    $getExpiring->execute();
    $result = $getExpiring->get_result();

    $expiring_medicines = [];
    while ($medicine = $result->fetch_assoc()) {
        $expiring_medicines[] = $medicine;
    }
    $getExpiring->close();

    echo json_encode([
        'success' => true,
        'expiring_medicines' => $expiring_medicines,
        'count' => count($expiring_medicines)
    ]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
