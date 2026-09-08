<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

function forward_table_exists(mysqli $conn, string $table): bool
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return false;
    }

    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($safeTable) . "'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function forward_sql_value($value): string
{
    if ($value === null || $value === '' || $value === 0 || $value === '0') {
        return 'NULL';
    }

    return (string) (int) $value;
}

$patientId = (int) ($_GET['patient_id'] ?? 0);
$patientLabel = trim((string) ($_GET['patient_label'] ?? ''));
$services = trim((string) ($_GET['services'] ?? ''));
$amount = (float) ($_GET['amount'] ?? 0);
$prescriptionId = (int) ($_GET['prescription_id'] ?? 0);
$consultationFee = (float) ($_GET['consultation_fee'] ?? 0);
$paymentStatus = strtolower(trim((string) ($_GET['payment_status'] ?? 'not_paid')));
$generatedOn = trim((string) ($_GET['generated_on'] ?? ''));

if ($patientId <= 0) {
    header('Location: admin_portal.php?section=billing');
    exit();
}

$invoiceId = 0;
$appointmentId = 0;
$doctorId = 0;
$invoiceItems = [];

if ($prescriptionId > 0 && forward_table_exists($conn, 'prescriptions')) {
    $stmt = $conn->prepare(
        "SELECT prescription_id, appointment_id, patient_id, doctor_id, invoice_id
         FROM prescriptions
         WHERE prescription_id = ? AND patient_id = ?
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('ii', $prescriptionId, $patientId);
        $stmt->execute();
        $prescription = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($prescription) {
            $appointmentId = (int) ($prescription['appointment_id'] ?? 0);
            $doctorId = (int) ($prescription['doctor_id'] ?? 0);
            $invoiceId = (int) ($prescription['invoice_id'] ?? 0);

            if ($invoiceId <= 0 && forward_table_exists($conn, 'prescription_medicines')) {
                $itemStmt = $conn->prepare(
                    "SELECT medicine_name, quantity, unit_price
                     FROM prescription_medicines
                     WHERE prescription_id = ?
                     ORDER BY medicine_id ASC"
                );
                if ($itemStmt) {
                    $itemStmt->bind_param('i', $prescriptionId);
                    $itemStmt->execute();
                    $itemResult = $itemStmt->get_result();
                    while ($row = $itemResult->fetch_assoc()) {
                        $qty = (float) ($row['quantity'] ?? 0);
                        if ($qty <= 0) {
                            $qty = 1;
                        }
                        $rate = (float) ($row['unit_price'] ?? 0);
                        $invoiceItems[] = [
                            'description' => (string) ($row['medicine_name'] ?? 'Medicine'),
                            'quantity' => $qty,
                            'rate' => $rate,
                            'amount' => round($qty * $rate, 2),
                            'type' => 'medicine'
                        ];
                    }
                    $itemStmt->close();
                }

                if ($consultationFee > 0) {
                    $invoiceItems[] = [
                        'description' => 'Doctor Consultation Fee',
                        'quantity' => 1,
                        'rate' => $consultationFee,
                        'amount' => $consultationFee,
                        'type' => 'consultation'
                    ];
                }

                $computedTotal = 0.0;
                foreach ($invoiceItems as $item) {
                    $computedTotal += (float) ($item['amount'] ?? 0);
                }
                if ($amount <= 0) {
                    $amount = $computedTotal;
                }
                $subtotal = $amount > 0 ? $amount : $computedTotal;
                $status = ($paymentStatus === 'paid') ? 'Paid' : 'Sent';
                $invoiceDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+7 days'));
                $notes = 'Forwarded from invoice page';

                $conn->begin_transaction();
                try {
                    $appointmentSql = forward_sql_value($appointmentId > 0 ? $appointmentId : null);
                    $doctorSql = forward_sql_value($doctorId > 0 ? $doctorId : null);
                    $insertInvoice = $conn->prepare(
                        "INSERT INTO invoices (appointment_id, patient_id, doctor_id, invoice_date, due_date, subtotal, tax, discount, total_amount, status, notes)
                         VALUES ($appointmentSql, ?, $doctorSql, ?, ?, ?, 0, 0, ?, ?, ?)"
                    );
                    if (!$insertInvoice) {
                        throw new Exception('Unable to prepare invoice insert.');
                    }

                    $insertInvoice->bind_param(
                        'issddss',
                        $patientId,
                        $invoiceDate,
                        $dueDate,
                        $subtotal,
                        $amount,
                        $status,
                        $notes
                    );
                    if (!$insertInvoice->execute()) {
                        throw new Exception('Unable to save invoice.');
                    }
                    $invoiceId = (int) $insertInvoice->insert_id;
                    $insertInvoice->close();

                    if (!empty($invoiceItems)) {
                        $insertItem = $conn->prepare(
                            "INSERT INTO invoice_items (invoice_id, description, quantity, rate, amount, item_type)
                             VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        if (!$insertItem) {
                            throw new Exception('Unable to prepare invoice item insert.');
                        }

                        foreach ($invoiceItems as $item) {
                            $insertItem->bind_param(
                                'isidds',
                                $invoiceId,
                                $item['description'],
                                $item['quantity'],
                                $item['rate'],
                                $item['amount'],
                                $item['type']
                            );
                            if (!$insertItem->execute()) {
                                throw new Exception('Unable to save invoice item.');
                            }
                        }
                        $insertItem->close();
                    }

                    $updatePrescription = $conn->prepare("UPDATE prescriptions SET invoice_id = ? WHERE prescription_id = ? AND patient_id = ?");
                    if ($updatePrescription) {
                        $updatePrescription->bind_param('iii', $invoiceId, $prescriptionId, $patientId);
                        $updatePrescription->execute();
                        $updatePrescription->close();
                    }

                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    die('Unable to forward bill: ' . $e->getMessage());
                }
            }
        }
    }
}

if ($invoiceId <= 0 && forward_table_exists($conn, 'invoices')) {
    $invoiceDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+7 days'));
    $status = ($paymentStatus === 'paid') ? 'Paid' : 'Sent';
    $subtotal = $amount > 0 ? $amount : 0.0;
    $notes = 'Forwarded bill';

    $insertInvoice = $conn->prepare(
        "INSERT INTO invoices (appointment_id, patient_id, doctor_id, invoice_date, due_date, subtotal, tax, discount, total_amount, status, notes)
         VALUES (NULL, ?, NULL, ?, ?, ?, 0, 0, ?, ?, ?)"
    );
    if ($insertInvoice) {
        $insertInvoice->bind_param('issddss', $patientId, $invoiceDate, $dueDate, $subtotal, $amount, $status, $notes);
        if ($insertInvoice->execute()) {
            $invoiceId = (int) $insertInvoice->insert_id;
        }
        $insertInvoice->close();
    }
}

$redirect = 'views/Patient/patient_dashboard.php?invoice_id=' . urlencode((string) $invoiceId);
if ($patientId > 0) {
    $redirect .= '&patient_id=' . urlencode((string) $patientId);
}
if ($patientLabel !== '') {
    $redirect .= '&patient_label=' . urlencode($patientLabel);
}
if ($services !== '') {
    $redirect .= '&services=' . urlencode($services);
}
if ($amount > 0) {
    $redirect .= '&amount=' . urlencode(number_format($amount, 2, '.', ''));
}
if ($consultationFee > 0) {
    $redirect .= '&consultation_fee=' . urlencode(number_format($consultationFee, 2, '.', ''));
}
if ($paymentStatus !== '') {
    $redirect .= '&payment_status=' . urlencode($paymentStatus);
}
if ($generatedOn !== '') {
    $redirect .= '&generated_on=' . urlencode($generatedOn);
}
if ($prescriptionId > 0) {
    $redirect .= '&prescription_id=' . urlencode((string) $prescriptionId);
}

header('Location: ' . $redirect . '#forwarded-bill');
exit();
