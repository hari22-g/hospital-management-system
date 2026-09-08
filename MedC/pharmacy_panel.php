<?php
session_start();
include 'connection/config.php';
include 'include/prescription_helpers.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_type = strtolower(trim((string) ($_SESSION['user_type'] ?? '')));
if (!in_array($user_type, ['pharmacist', 'admin'])) {
    die('Access denied.');
}

$selected_id = (int) ($_GET['prescription_id'] ?? 0);

$listStmt = $conn->prepare(
    "SELECT pr.prescription_id, pr.status, pr.created_at,
            p.firstname, p.lastname,
            d.f_name, d.l_name
     FROM prescriptions pr
     JOIN patient p ON pr.patient_id = p.pid
     JOIN doctor d ON pr.doctor_id = d.d_id
     ORDER BY pr.created_at DESC"
);
$listStmt->execute();
$prescriptionList = $listStmt->get_result();

$selectedPrescription = null;
$medicineRows = [];
$testRows = [];
$invoiceRow = null;
$hasMedTable = false;
$hasMedResult = $conn->query("SHOW TABLES LIKE 'medications'");
if ($hasMedResult && $hasMedResult->num_rows > 0) {
    $hasMedTable = true;
}

if ($selected_id > 0) {
    $detailStmt = $conn->prepare(
        "SELECT pr.*, p.firstname, p.lastname, d.f_name, d.l_name,
                inv.invoice_id, inv.total_amount, inv.paid_amount, inv.status AS invoice_status
         FROM prescriptions pr
         JOIN patient p ON pr.patient_id = p.pid
         JOIN doctor d ON pr.doctor_id = d.d_id
         LEFT JOIN invoices inv ON pr.invoice_id = inv.invoice_id
         WHERE pr.prescription_id = ?"
    );
    $detailStmt->bind_param('i', $selected_id);
    $detailStmt->execute();
    $selectedPrescription = $detailStmt->get_result()->fetch_assoc();
    $detailStmt->close();

    if ($selectedPrescription) {
        $medStmt = $conn->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ? ORDER BY medicine_id ASC");
        $medStmt->bind_param('i', $selected_id);
        $medStmt->execute();
        $medicineRows = $medStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $medStmt->close();

        $testStmt = $conn->prepare("SELECT * FROM prescription_tests WHERE prescription_id = ? ORDER BY test_id ASC");
        $testStmt->bind_param('i', $selected_id);
        $testStmt->execute();
        $testRows = $testStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $testStmt->close();
    }
}

function get_stock_info($conn, $hasMedTable, $medicineName)
{
    if (!$hasMedTable) {
        return ['stock_level' => null, 'unit_price' => null];
    }

    $stmt = $conn->prepare("SELECT stock_level, unit_price FROM medications WHERE medicine_name = ? LIMIT 1");
    if (!$stmt) {
        return ['stock_level' => null, 'unit_price' => null];
    }
    $stmt->bind_param('s', $medicineName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['stock_level' => null, 'unit_price' => null];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', sans-serif;
            background: #f4f8ff;
            color: #15355a;
        }
        .page-shell {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: linear-gradient(160deg, #1f5fbf 0%, #2f8dd6 100%);
            color: #fff;
            padding: 24px;
        }
        .sidebar a {
            color: #e4efff;
            text-decoration: none;
            display: block;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .sidebar a.active,
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.18);
        }
        .content {
            flex: 1;
            padding: 28px 32px;
        }
        .card-surface {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 16px 28px rgba(21, 53, 90, 0.08);
        }
        .price-input {
            max-width: 110px;
        }
        @media (max-width: 992px) {
            .page-shell {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <aside class="sidebar">
        <h4>Pharmacy Panel</h4>
        <p class="small mb-4">Prescription Fulfillment</p>
        <a class="active" href="pharmacy_panel.php"><i class="fa-solid fa-pills me-2"></i>New Prescriptions</a>
        <a href="admin_portal.php"><i class="fa-solid fa-chart-line me-2"></i>Admin Portal</a>
        <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout</a>
    </aside>

    <main class="content">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-surface">
                    <h5 class="mb-3">Incoming Prescriptions</h5>
                    <?php if ($prescriptionList && $prescriptionList->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($rx = $prescriptionList->fetch_assoc()): ?>
                                <a class="list-group-item list-group-item-action" href="pharmacy_panel.php?prescription_id=<?php echo (int) $rx['prescription_id']; ?>">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-semibold"><?php echo escape($rx['firstname'] . ' ' . $rx['lastname']); ?></div>
                                            <div class="text-muted small">Dr. <?php echo escape($rx['f_name'] . ' ' . $rx['l_name']); ?></div>
                                            <div class="text-muted small">Rx #<?php echo (int) $rx['prescription_id']; ?> | <?php echo format_date($rx['created_at']); ?></div>
                                        </div>
                                        <?php echo status_badge($rx['status']); ?>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No prescriptions yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-surface">
                    <?php if ($selectedPrescription): ?>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">Prescription #<?php echo (int) $selectedPrescription['prescription_id']; ?></h5>
                                <div class="text-muted small">Patient: <?php echo escape($selectedPrescription['firstname'] . ' ' . $selectedPrescription['lastname']); ?> | Dr. <?php echo escape($selectedPrescription['f_name'] . ' ' . $selectedPrescription['l_name']); ?></div>
                            </div>
                            <?php echo status_badge($selectedPrescription['status']); ?>
                        </div>

                        <form method="POST" action="pharmacy_update_medicines.php">
                            <input type="hidden" name="prescription_id" value="<?php echo (int) $selectedPrescription['prescription_id']; ?>">
                            <h6>Medicine Availability</h6>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Required</th>
                                            <th>Stock</th>
                                            <th>Dispensed Qty</th>
                                            <th>Availability</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($medicineRows as $med): ?>
                                        <?php $stockInfo = get_stock_info($conn, $hasMedTable, $med['medicine_name']); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo escape($med['medicine_name']); ?></div>
                                                <div class="text-muted small"><?php echo escape($med['dosage'] ?? ''); ?></div>
                                            </td>
                                            <td><?php echo (int) ($med['quantity'] ?? 0); ?></td>
                                            <td><?php echo $stockInfo['stock_level'] !== null ? (int) $stockInfo['stock_level'] : 'N/A'; ?></td>
                                            <td>
                                                <input type="hidden" name="medicine_id[]" value="<?php echo (int) $med['medicine_id']; ?>">
                                                <input type="number" name="dispensed_quantity[]" class="form-control" min="0" value="<?php echo (int) ($med['dispensed_quantity'] ?? 0); ?>">
                                            </td>
                                            <td>
                                                <select name="availability[]" class="form-select">
                                                    <?php
                                                    $availability = $med['availability'] ?? 'Available';
                                                    $options = ['Available', 'Not Available', 'Partial'];
                                                    foreach ($options as $option) {
                                                        $selected = $availability === $option ? 'selected' : '';
                                                        echo '<option value="' . escape($option) . '" ' . $selected . '>' . escape($option) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-outline-primary" type="submit">Update Availability</button>
                            </div>
                        </form>

                        <hr class="my-4">
                        <form method="POST" action="pharmacy_update_status.php" class="d-flex align-items-center gap-3">
                            <input type="hidden" name="prescription_id" value="<?php echo (int) $selectedPrescription['prescription_id']; ?>">
                            <label class="form-label mb-0">Update Status</label>
                            <select name="status" class="form-select w-auto">
                                <?php
                                $statuses = ['Pending', 'Processing', 'Ready', 'Dispensed'];
                                foreach ($statuses as $status) {
                                    $selected = $selectedPrescription['status'] === $status ? 'selected' : '';
                                    echo '<option value="' . escape($status) . '" ' . $selected . '>' . escape($status) . '</option>';
                                }
                                ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>

                        <hr class="my-4">
                        <h6>Billing System</h6>
                        <form method="POST" action="pharmacy_generate_bill.php" oninput="calculateTotals()">
                            <input type="hidden" name="prescription_id" value="<?php echo (int) $selectedPrescription['prescription_id']; ?>">
                            <div class="table-responsive mb-3">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Rate (Rs.)</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($medicineRows as $index => $med): ?>
                                        <?php $stockInfo = get_stock_info($conn, $hasMedTable, $med['medicine_name']); ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" name="item_type[]" value="medicine">
                                                <input type="hidden" name="item_desc[]" value="<?php echo escape($med['medicine_name']); ?>">
                                                <?php echo escape($med['medicine_name']); ?>
                                            </td>
                                            <td>
                                                <input type="number" name="item_qty[]" class="form-control" value="<?php echo (int) ($med['dispensed_quantity'] ?: $med['quantity']); ?>" min="0">
                                            </td>
                                            <td>
                                                <input type="number" name="item_rate[]" class="form-control price-input" value="<?php echo number_format((float) ($stockInfo['unit_price'] ?? 0), 2, '.', ''); ?>" step="0.01" min="0">
                                            </td>
                                            <td class="item-amount">0.00</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Consultation Fee</label>
                                    <input type="number" name="consultation_fee" class="form-control" value="0" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Additional Charges</label>
                                    <input type="number" name="additional_charges" class="form-control" value="0" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Discount</label>
                                    <input type="number" name="discount" class="form-control" value="0" step="0.01" min="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tax</label>
                                    <input type="number" name="tax" class="form-control" value="0" step="0.01" min="0">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <div class="text-end">
                                    <div class="text-muted">Total</div>
                                    <div class="fs-5 fw-semibold">Rs. <span id="billTotal">0.00</span></div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-outline-primary" type="submit">Generate Bill</button>
                                <?php if (!empty($selectedPrescription['invoice_id'])): ?>
                                    <a class="btn btn-success" href="pharmacy_mark_paid.php?invoice_id=<?php echo (int) $selectedPrescription['invoice_id']; ?>&prescription_id=<?php echo (int) $selectedPrescription['prescription_id']; ?>">Mark as Paid</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">Select a prescription from the left to view and process.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function calculateTotals() {
    const rows = document.querySelectorAll('table tbody tr');
    let total = 0;

    rows.forEach(row => {
        const qtyInput = row.querySelector('input[name="item_qty[]"]');
        const rateInput = row.querySelector('input[name="item_rate[]"]');
        const amountCell = row.querySelector('.item-amount');

        if (!qtyInput || !rateInput || !amountCell) {
            return;
        }

        const qty = parseFloat(qtyInput.value || 0);
        const rate = parseFloat(rateInput.value || 0);
        const amount = qty * rate;
        amountCell.textContent = amount.toFixed(2);
        total += amount;
    });

    const consultation = parseFloat(document.querySelector('input[name="consultation_fee"]').value || 0);
    const additional = parseFloat(document.querySelector('input[name="additional_charges"]').value || 0);
    const discount = parseFloat(document.querySelector('input[name="discount"]').value || 0);
    const tax = parseFloat(document.querySelector('input[name="tax"]').value || 0);

    total = total + consultation + additional + tax - discount;
    document.getElementById('billTotal').textContent = total.toFixed(2);
}

calculateTotals();
</script>
</body>
</html>