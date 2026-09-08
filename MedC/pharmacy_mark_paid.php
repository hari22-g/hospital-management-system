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

$invoice_id = (int) ($_GET['invoice_id'] ?? 0);
$prescription_id = (int) ($_GET['prescription_id'] ?? 0);

if ($invoice_id <= 0) {
    header('Location: pharmacy_panel.php');
    exit();
}

$invoiceStmt = $conn->prepare("SELECT invoice_id, patient_id, total_amount FROM invoices WHERE invoice_id = ?");
$invoiceStmt->bind_param('i', $invoice_id);
$invoiceStmt->execute();
$invoice = $invoiceStmt->get_result()->fetch_assoc();
$invoiceStmt->close();

if (!$invoice) {
    die('Invoice not found.');
}

$conn->begin_transaction();
try {
    $paid_amount = (float) $invoice['total_amount'];
    $updateInvoice = $conn->prepare("UPDATE invoices SET status = 'Paid', paid_amount = ? WHERE invoice_id = ?");
    $updateInvoice->bind_param('di', $paid_amount, $invoice_id);
    $updateInvoice->execute();
    $updateInvoice->close();

    $payment_date = date('Y-m-d');
    $insertPayment = $conn->prepare(
        "INSERT INTO payments (invoice_id, patient_id, amount_paid, payment_date, payment_method, notes)
         VALUES (?, ?, ?, ?, 'Cash', 'Marked paid by pharmacy')"
    );
    $insertPayment->bind_param('iids', $invoice_id, $invoice['patient_id'], $paid_amount, $payment_date);
    $insertPayment->execute();
    $insertPayment->close();

    $conn->commit();

    $redirect = 'pharmacy_panel.php';
    if ($prescription_id > 0) {
        $redirect .= '?prescription_id=' . $prescription_id . '&paid=1';
    }
    header('Location: ' . $redirect);
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo 'Error updating payment: ' . $e->getMessage();
}
?>