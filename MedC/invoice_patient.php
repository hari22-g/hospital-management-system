<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

$patientId = intval($_GET['patient_id'] ?? 0);
$patientLabel = trim((string)($_GET['patient_label'] ?? ''));
$services = trim((string)($_GET['services'] ?? ''));
$amount = floatval($_GET['amount'] ?? 0);
$consultationFee = floatval($_GET['consultation_fee'] ?? 0);
$paymentStatus = strtolower(trim((string)($_GET['payment_status'] ?? 'not_paid')));
$generatedOn = trim((string)($_GET['generated_on'] ?? ''));
$prescriptionId = intval($_GET['prescription_id'] ?? 0);

function invoice_table_exists(mysqli $conn, string $table): bool
{
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $result && $result->num_rows > 0;
}

$invoiceItems = [];
$invoiceSource = '';
$invoiceDoctor = '';
$invoicePatientName = '';
$medicineSubtotal = 0.0;

if ($prescriptionId > 0 && invoice_table_exists($conn, 'prescriptions') && invoice_table_exists($conn, 'prescription_medicines')) {
    $stmt = $conn->prepare(
        "SELECT pr.prescription_id, pr.patient_id, pr.doctor_id, p.firstname, p.lastname, d.f_name, d.l_name
         FROM prescriptions pr
         INNER JOIN patient p ON p.pid = pr.patient_id
         LEFT JOIN doctor d ON d.d_id = pr.doctor_id
         WHERE pr.prescription_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $prescriptionId);
    $stmt->execute();
    $prescription = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($prescription) {
        $invoiceSource = 'prescription';
        $invoiceDoctor = trim((string)($prescription['f_name'] ?? '') . ' ' . (string)($prescription['l_name'] ?? ''));
        $invoicePatientName = trim((string)($prescription['firstname'] ?? '') . ' ' . (string)($prescription['lastname'] ?? ''));

        $itemStmt = $conn->prepare(
            "SELECT medicine_name, quantity, unit_price, dosage, frequency, food_timing, duration_days, instructions
             FROM prescription_medicines
             WHERE prescription_id = ?
             ORDER BY medicine_id ASC"
        );
        $itemStmt->bind_param('i', $prescriptionId);
        $itemStmt->execute();
        $items = $itemStmt->get_result();

        while ($row = $items->fetch_assoc()) {
            $quantity = (float) ($row['quantity'] ?? 0);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $rate = (float) ($row['unit_price'] ?? 0);
            $itemAmount = round($quantity * $rate, 2);
            $invoiceItems[] = [
                'medicine_name' => (string) ($row['medicine_name'] ?? ''),
                'quantity' => $quantity,
                'rate' => $rate,
                'amount' => $itemAmount,
                'dosage' => (string) ($row['dosage'] ?? ''),
                'frequency' => (string) ($row['frequency'] ?? ''),
                'food_timing' => (string) ($row['food_timing'] ?? ''),
                'duration_days' => (string) ($row['duration_days'] ?? ''),
                'instructions' => (string) ($row['instructions'] ?? '')
            ];
        }
        $itemStmt->close();

        if (empty($invoiceItems)) {
            $invoiceSource = '';
        } else {
            $medicineSubtotal = array_reduce($invoiceItems, function ($carry, $item) {
                return $carry + (float) ($item['amount'] ?? 0);
            }, 0.0);
            if ($amount <= 0) {
                $amount = $medicineSubtotal + ($consultationFee > 0 ? $consultationFee : 0);
            }
        }
    }
}

if ($invoiceSource === '' && $patientId > 0 && invoice_table_exists($conn, 'prescriptions') && invoice_table_exists($conn, 'prescription_medicines')) {
    $stmt = $conn->prepare(
        "SELECT pr.prescription_id, pr.patient_id, pr.doctor_id, p.firstname, p.lastname, d.f_name, d.l_name
         FROM prescriptions pr
         INNER JOIN patient p ON p.pid = pr.patient_id
         LEFT JOIN doctor d ON d.d_id = pr.doctor_id
         WHERE pr.patient_id = ?
         ORDER BY pr.created_at DESC, pr.prescription_id DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $prescription = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($prescription) {
        $prescriptionId = (int) ($prescription['prescription_id'] ?? 0);
        $invoiceSource = 'prescription';
        $invoiceDoctor = trim((string)($prescription['f_name'] ?? '') . ' ' . (string)($prescription['l_name'] ?? ''));
        $invoicePatientName = trim((string)($prescription['firstname'] ?? '') . ' ' . (string)($prescription['lastname'] ?? ''));

        $itemStmt = $conn->prepare(
            "SELECT medicine_name, quantity, unit_price, dosage, frequency, food_timing, duration_days, instructions
             FROM prescription_medicines
             WHERE prescription_id = ?
             ORDER BY medicine_id ASC"
        );
        $itemStmt->bind_param('i', $prescriptionId);
        $itemStmt->execute();
        $items = $itemStmt->get_result();

        while ($row = $items->fetch_assoc()) {
            $quantity = (float) ($row['quantity'] ?? 0);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $rate = (float) ($row['unit_price'] ?? 0);
            $itemAmount = round($quantity * $rate, 2);
            $invoiceItems[] = [
                'medicine_name' => (string) ($row['medicine_name'] ?? ''),
                'quantity' => $quantity,
                'rate' => $rate,
                'amount' => $itemAmount,
                'dosage' => (string) ($row['dosage'] ?? ''),
                'frequency' => (string) ($row['frequency'] ?? ''),
                'food_timing' => (string) ($row['food_timing'] ?? ''),
                'duration_days' => (string) ($row['duration_days'] ?? ''),
                'instructions' => (string) ($row['instructions'] ?? '')
            ];
        }
        $itemStmt->close();

        if (!empty($invoiceItems)) {
            $medicineSubtotal = array_reduce($invoiceItems, function ($carry, $item) {
                return $carry + (float) ($item['amount'] ?? 0);
            }, 0.0);
            if ($amount <= 0) {
                $amount = $medicineSubtotal + ($consultationFee > 0 ? $consultationFee : 0);
            }
        }
    }
}

if ($invoiceSource === '' && $amount <= 0) {
    $amount = 0.0;
}

if ($paymentStatus !== 'paid') {
    $paymentStatus = 'not_paid';
}

$statusLabel = ($paymentStatus === 'paid') ? 'Paid' : 'Not Paid';
$statusClass = ($paymentStatus === 'paid') ? 'paid' : 'not-paid';

function invoice_parse_patient_label(string $label, int $patientId): array
{
    $parts = array_map('trim', explode('|', $label));
    $namePart = $parts[1] ?? '';
    $emailPart = '';
    if (preg_match('/\(([^)]+)\)/', $namePart, $matches)) {
        $emailPart = trim((string) ($matches[1] ?? ''));
        $namePart = trim((string) preg_replace('/\s*\([^)]+\)\s*/', '', $namePart));
    }

    return [
        'id' => $patientId > 0 ? 'PID-' . $patientId : ($parts[0] ?? 'N/A'),
        'name' => $namePart !== '' ? $namePart : ($parts[1] ?? 'N/A'),
        'email' => $emailPart !== '' ? $emailPart : 'N/A',
        'appointment' => $parts[2] ?? 'N/A',
        'status' => $parts[3] ?? 'N/A'
    ];
}

$patientSummary = invoice_parse_patient_label($patientLabel, $patientId);

$generatedAtText = date('d M Y, h:i A');
if ($generatedOn !== '') {
    $timestamp = strtotime($generatedOn);
    if ($timestamp !== false) {
        $generatedAtText = date('d M Y, h:i A', $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Invoice</title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f7ff;
            color: #214b90;
        }
        .invoice-shell {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dce8fd;
            border-radius: 18px;
            box-shadow: 0 16px 34px rgba(33, 75, 144, 0.12);
            overflow: hidden;
        }
        .invoice-header {
            padding: 18px 22px;
            border-bottom: 1px solid #e6efff;
            background: linear-gradient(135deg, #f4f9ff 0%, #ecf4ff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .invoice-title {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
        }
        .status-chip {
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.86rem;
            padding: 8px 12px;
            display: inline-block;
        }
        .status-chip.paid {
            background: #1f9d62;
            color: #fff;
        }
        .status-chip.not-paid {
            background: #ffc944;
            color: #51390a;
        }
        .invoice-body {
            padding: 20px 22px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .card {
            border: 1px solid #e3ecfd;
            border-radius: 12px;
            padding: 12px;
            background: #fafcff;
        }
        .label {
            font-size: 0.84rem;
            color: #5f80ad;
            margin-bottom: 6px;
        }
        .value {
            font-size: 1rem;
            font-weight: 700;
            color: #1f4688;
            word-break: break-word;
        }
        .info-card {
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .info-card .value {
            margin-top: 4px;
            line-height: 1.35;
        }
        .amount-box {
            margin-top: 8px;
            border: 1px solid #bcd4ff;
            border-radius: 12px;
            padding: 14px;
            background: linear-gradient(135deg, #f2f8ff 0%, #eaf3ff 100%);
        }
        .amount-box .amount {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1f4f9c;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .btn {
            border: none;
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2f74de 0%, #4e8ff3 100%);
            color: #fff;
        }
        .btn-secondary {
            background: #eaf1ff;
            color: #214b90;
        }
        .btn-forward {
            background: linear-gradient(135deg, #1f9d62 0%, #2fb774 100%);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="invoice-shell">
        <div class="invoice-header">
            <h1 class="invoice-title">Patient Invoice</h1>
            <span class="status-chip <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
        </div>
        <div class="invoice-body">
            <div class="grid">
                <div class="card info-card">
                    <div class="label">Patient ID</div>
                    <div class="value"><?php echo $patientId > 0 ? $patientId : 'N/A'; ?></div>
                </div>
                <div class="card info-card">
                    <div class="label">Patient Name</div>
                    <div class="value"><?php echo htmlspecialchars($patientSummary['name'] !== '' ? $patientSummary['name'] : 'N/A'); ?></div>
                </div>
                <div class="card info-card">
                    <div class="label">Email</div>
                    <div class="value"><?php echo htmlspecialchars($patientSummary['email']); ?></div>
                </div>
                <div class="card info-card">
                    <div class="label">Appointment</div>
                    <div class="value"><?php echo htmlspecialchars($patientSummary['appointment']); ?></div>
                </div>
                <div class="card info-card">
                    <div class="label">Status</div>
                    <div class="value"><?php echo htmlspecialchars($patientSummary['status']); ?></div>
                </div>
                <div class="card info-card">
                    <div class="label">Generated On</div>
                    <div class="value"><?php echo htmlspecialchars($generatedAtText); ?></div>
                </div>
            </div>

            <div class="card">
                <div class="label">Services</div>
                <div class="value"><?php echo htmlspecialchars($services !== '' ? $services : 'Consultation'); ?></div>
            </div>

            <?php if (!empty($invoiceItems)): ?>
                <div class="card" style="margin-top: 14px;">
                    <div class="label">Doctor Prescription Medicines</div>
                    <div class="value"><?php echo htmlspecialchars($invoiceDoctor !== '' ? $invoiceDoctor : 'Doctor Prescription'); ?></div>
                    <div style="margin-top: 12px; overflow-x: auto;">
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #e3ecfd;">Medicine</th>
                                    <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #e3ecfd;">Qty</th>
                                    <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #e3ecfd;">Rate</th>
                                    <th style="text-align:left; padding:10px 8px; border-bottom:1px solid #e3ecfd;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoiceItems as $item): ?>
                                    <tr>
                                        <td style="padding:10px 8px; border-bottom:1px solid #eef4ff;"><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                                        <td style="padding:10px 8px; border-bottom:1px solid #eef4ff;"><?php echo htmlspecialchars((string) $item['quantity']); ?></td>
                                        <td style="padding:10px 8px; border-bottom:1px solid #eef4ff;">Rs. <?php echo number_format((float) $item['rate'], 2); ?></td>
                                        <td style="padding:10px 8px; border-bottom:1px solid #eef4ff;">Rs. <?php echo number_format((float) $item['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($consultationFee > 0): ?>
                <div class="card" style="margin-top: 14px;">
                    <div class="label">Doctor Consultation Fee</div>
                    <div class="value">INR <?php echo number_format($consultationFee, 2); ?></div>
                </div>
            <?php endif; ?>

            <div class="amount-box">
                <div class="label">Invoice Amount</div>
                <div class="amount">INR <?php echo number_format($amount, 2); ?></div>
                <div class="label" style="margin-top: 6px;">
                    <?php echo !empty($invoiceItems) ? 'Amount includes prescribed medicines and doctor consultation fee.' : 'Amount auto-calculated as 50% of booked appointment fee.'; ?>
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" onclick="window.print()">Print Invoice</button>
                <button class="btn btn-secondary" onclick="window.location.href='admin_portal.php?section=billing'">Back to Billing</button>
                <?php if ($patientId > 0): ?>
                    <button class="btn btn-forward" onclick="window.location.href='forward_invoice.php?patient_id=<?php echo (int) $patientId; ?>&patient_label=<?php echo urlencode($patientLabel); ?>&services=<?php echo urlencode($services); ?>&amount=<?php echo urlencode(number_format((float) $amount, 2, '.', '')); ?>&prescription_id=<?php echo (int) $prescriptionId; ?>&consultation_fee=<?php echo urlencode(number_format((float) $consultationFee, 2, '.', '')); ?>&payment_status=<?php echo urlencode($paymentStatus); ?>&generated_on=<?php echo urlencode($generatedOn !== '' ? $generatedOn : date('c')); ?>'">Forward Patient</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
