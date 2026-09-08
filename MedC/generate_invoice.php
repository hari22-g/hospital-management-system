<?php
// generate_invoice.php - Create and manage invoices
session_start();
header('Content-Type: application/json');

require_once 'connection/config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = trim($_POST['action'] ?? '');
$appointment_id = intval($_POST['appointment_id'] ?? 0);
$patient_id = intval($_POST['patient_id'] ?? 0);
$doctor_id = intval($_POST['doctor_id'] ?? 0);
$items = json_decode($_POST['items'] ?? '[]', true);
$discount = floatval($_POST['discount'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($action === 'create') {
    if ($patient_id <= 0 || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Patient and items required']);
        exit();
    }

    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += ($item['quantity'] * $item['rate']);
    }

    $tax = $subtotal * 0.05; // 5% tax
    $total = $subtotal + $tax - $discount;

    // Create invoice
    $invoiceDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+15 days'));
    $status = 'Draft';

    $insertInvoice = $conn->prepare(
        "INSERT INTO invoices (appointment_id, patient_id, doctor_id, invoice_date, due_date, subtotal, tax, discount, total_amount, status, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insertInvoice->bind_param(
        'iiiisddddss',
        $appointment_id, $patient_id, $doctor_id, $invoiceDate, $dueDate,
        $subtotal, $tax, $discount, $total, $status, $notes
    );

    if (!$insertInvoice->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error creating invoice']);
        exit();
    }

    $invoiceId = $insertInvoice->insert_id;
    $insertInvoice->close();

    // Add line items
    foreach ($items as $item) {
        $amount = $item['quantity'] * $item['rate'];
        $itemType = $item['type'] ?? 'consultation';
        $insertItem = $conn->prepare(
            "INSERT INTO invoice_items (invoice_id, description, quantity, rate, amount, item_type)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insertItem->bind_param('issids', $invoiceId, $item['description'], $item['quantity'], $item['rate'], $amount, $itemType);
        $insertItem->execute();
        $insertItem->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Invoice created successfully',
        'invoice_id' => $invoiceId,
        'total' => $total
    ]);

} elseif ($action === 'record_payment') {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    $transaction_id = trim($_POST['transaction_id'] ?? '');

    if ($invoice_id <= 0 || $amount_paid <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    // Get invoice details
    $getInvoice = $conn->prepare("SELECT patient_id, total_amount, paid_amount FROM invoices WHERE invoice_id = ?");
    $getInvoice->bind_param('i', $invoice_id);
    $getInvoice->execute();
    $result = $getInvoice->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        exit();
    }

    $invoice = $result->fetch_assoc();
    $getInvoice->close();

    // Record payment
    $paymentDate = date('Y-m-d');
    $insertPayment = $conn->prepare(
        "INSERT INTO payments (invoice_id, patient_id, amount_paid, payment_date, payment_method, transaction_id)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $insertPayment->bind_param('iidsss', $invoice_id, $invoice['patient_id'], $amount_paid, $paymentDate, $payment_method, $transaction_id);
    $insertPayment->execute();
    $insertPayment->close();

    // Update invoice paid amount and status
    $newPaidAmount = $invoice['paid_amount'] + $amount_paid;
    $newStatus = ($newPaidAmount >= $invoice['total_amount']) ? 'Paid' : 'Partial';

    $updateInvoice = $conn->prepare("UPDATE invoices SET paid_amount = ?, status = ? WHERE invoice_id = ?");
    $updateInvoice->bind_param('dsi', $newPaidAmount, $newStatus, $invoice_id);
    $updateInvoice->execute();
    $updateInvoice->close();

    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully',
        'new_status' => $newStatus,
        'remaining_balance' => max(0, $invoice['total_amount'] - $newPaidAmount)
    ]);

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
