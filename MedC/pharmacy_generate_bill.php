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
if ($prescription_id <= 0) {
    header('Location: pharmacy_panel.php');
    exit();
}

$rxStmt = $conn->prepare(
    "SELECT prescription_id, appointment_id, patient_id, doctor_id, status FROM prescriptions WHERE prescription_id = ?"
);
$rxStmt->bind_param('i', $prescription_id);
$rxStmt->execute();
$rx = $rxStmt->get_result()->fetch_assoc();
$rxStmt->close();

if (!$rx) {
    die('Prescription not found.');
}

$item_desc = $_POST['item_desc'] ?? [];
$item_qty = $_POST['item_qty'] ?? [];
$item_rate = $_POST['item_rate'] ?? [];
$item_type = $_POST['item_type'] ?? [];

$consultation_fee = (float) ($_POST['consultation_fee'] ?? 0);
$additional_charges = (float) ($_POST['additional_charges'] ?? 0);
$discount = (float) ($_POST['discount'] ?? 0);
$tax = (float) ($_POST['tax'] ?? 0);

$items = [];
$subtotal = 0.0;
for ($i = 0; $i < count($item_desc); $i++) {
    $desc = trim((string) ($item_desc[$i] ?? ''));
    $qty = (float) ($item_qty[$i] ?? 0);
    $rate = (float) ($item_rate[$i] ?? 0);
    if ($desc === '' || $qty <= 0) {
        continue;
    }
    $amount = $qty * $rate;
    $items[] = [
        'desc' => $desc,
        'qty' => $qty,
        'rate' => $rate,
        'amount' => $amount,
        'type' => $item_type[$i] ?? 'medicine'
    ];
    $subtotal += $amount;
}

if ($consultation_fee > 0) {
    $items[] = [
        'desc' => 'Consultation Fee',
        'qty' => 1,
        'rate' => $consultation_fee,
        'amount' => $consultation_fee,
        'type' => 'consultation'
    ];
    $subtotal += $consultation_fee;
}

if ($additional_charges > 0) {
    $items[] = [
        'desc' => 'Additional Charges',
        'qty' => 1,
        'rate' => $additional_charges,
        'amount' => $additional_charges,
        'type' => 'other'
    ];
    $subtotal += $additional_charges;
}

$total = $subtotal + $tax - $discount;

$conn->begin_transaction();
try {
    $invoice_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+7 days'));

    $insertInvoice = $conn->prepare(
        "INSERT INTO invoices (appointment_id, patient_id, doctor_id, invoice_date, due_date, subtotal, tax, discount, total_amount, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Sent')"
    );
    $insertInvoice->bind_param(
        'iiissdddd',
        $rx['appointment_id'],
        $rx['patient_id'],
        $rx['doctor_id'],
        $invoice_date,
        $due_date,
        $subtotal,
        $tax,
        $discount,
        $total
    );
    $insertInvoice->execute();
    $invoice_id = $conn->insert_id;
    $insertInvoice->close();

    if (!empty($items)) {
        $insertItem = $conn->prepare(
            "INSERT INTO invoice_items (invoice_id, description, quantity, rate, amount, item_type)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            $insertItem->bind_param(
                'isidds',
                $invoice_id,
                $item['desc'],
                $item['qty'],
                $item['rate'],
                $item['amount'],
                $item['type']
            );
            $insertItem->execute();
        }
        $insertItem->close();
    }

    $updateRx = $conn->prepare("UPDATE prescriptions SET invoice_id = ?, status = IF(status = 'Dispensed', status, 'Ready') WHERE prescription_id = ?");
    $updateRx->bind_param('ii', $invoice_id, $prescription_id);
    $updateRx->execute();
    $updateRx->close();

    $conn->commit();

    header('Location: pharmacy_panel.php?prescription_id=' . $prescription_id . '&bill=created');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    echo 'Error generating bill: ' . $e->getMessage();
}
?>