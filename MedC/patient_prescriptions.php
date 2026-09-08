<?php
session_start();
include 'connection/config.php';
include 'include/prescription_helpers.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (strtolower(trim((string) ($_SESSION['user_type'] ?? ''))) !== 'patient') {
    header('Location: include/dashboard_link.php');
    exit();
}

$patient_id = (int) $_SESSION['user_id'];
$selected_id = (int) ($_GET['prescription_id'] ?? 0);

$listStmt = $conn->prepare(
    "SELECT pr.prescription_id, pr.created_at, pr.status, pr.follow_up_date,
            d.f_name, d.l_name,
            inv.invoice_id, inv.total_amount, inv.paid_amount, inv.status AS invoice_status
     FROM prescriptions pr
     JOIN doctor d ON pr.doctor_id = d.d_id
     LEFT JOIN invoices inv ON pr.invoice_id = inv.invoice_id
     WHERE pr.patient_id = ?
     ORDER BY pr.created_at DESC"
);
$listStmt->bind_param('i', $patient_id);
$listStmt->execute();
$prescriptionList = $listStmt->get_result();

$selectedPrescription = null;
$medicineRows = [];
$testRows = [];
$invoiceRow = null;
if ($selected_id > 0) {
    $detailStmt = $conn->prepare(
        "SELECT pr.*, d.f_name, d.l_name, inv.invoice_id, inv.total_amount, inv.paid_amount, inv.status AS invoice_status
         FROM prescriptions pr
         JOIN doctor d ON pr.doctor_id = d.d_id
         LEFT JOIN invoices inv ON pr.invoice_id = inv.invoice_id
         WHERE pr.prescription_id = ? AND pr.patient_id = ?"
    );
    $detailStmt->bind_param('ii', $selected_id, $patient_id);
    $detailStmt->execute();
    $selectedPrescription = $detailStmt->get_result()->fetch_assoc();
    $detailStmt->close();

    if ($selectedPrescription) {
        $medStmt = $conn->prepare(
            "SELECT * FROM prescription_medicines WHERE prescription_id = ? ORDER BY medicine_id ASC"
        );
        $medStmt->bind_param('i', $selected_id);
        $medStmt->execute();
        $medicineRows = $medStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $medStmt->close();

        $testStmt = $conn->prepare(
            "SELECT * FROM prescription_tests WHERE prescription_id = ? ORDER BY test_id ASC"
        );
        $testStmt->bind_param('i', $selected_id);
        $testStmt->execute();
        $testRows = $testStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $testStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions</title>
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
        .pill {
            background: #e8f1ff;
            color: #1f5fbf;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-timing {
            background: #1f5fbf;
            color: #fff;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 12px;
            margin-right: 6px;
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
        <h4>Patient Panel</h4>
        <p class="small mb-4">Prescription Center</p>
        <a class="active" href="patient_prescriptions.php"><i class="fa-solid fa-notes-medical me-2"></i>My Prescriptions</a>
        <a href="modern_patient_dashboard.php"><i class="fa-solid fa-user me-2"></i>Patient Dashboard</a>
        <a href="book_appointment.php"><i class="fa-regular fa-calendar-check me-2"></i>Book Appointment</a>
        <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout</a>
    </aside>

    <main class="content">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card-surface">
                    <h5 class="mb-3">My Prescriptions</h5>
                    <?php if ($prescriptionList && $prescriptionList->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($rx = $prescriptionList->fetch_assoc()): ?>
                                <a class="list-group-item list-group-item-action" href="patient_prescriptions.php?prescription_id=<?php echo (int) $rx['prescription_id']; ?>">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-semibold">Dr. <?php echo escape($rx['f_name'] . ' ' . $rx['l_name']); ?></div>
                                            <div class="text-muted small">Rx #<?php echo (int) $rx['prescription_id']; ?> | <?php echo format_date($rx['created_at']); ?></div>
                                        </div>
                                        <div class="text-end">
                                            <?php echo status_badge($rx['status']); ?>
                                            <div class="small mt-1"><?php echo status_badge(payment_status_label($rx)); ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No prescriptions yet. Your upcoming consultation will appear here.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card-surface">
                    <?php if ($selectedPrescription): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Prescription #<?php echo (int) $selectedPrescription['prescription_id']; ?></h5>
                                <div class="text-muted small">Dr. <?php echo escape($selectedPrescription['f_name'] . ' ' . $selectedPrescription['l_name']); ?> | Issued <?php echo format_date($selectedPrescription['created_at']); ?></div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="prescription_print.php?prescription_id=<?php echo (int) $selectedPrescription['prescription_id']; ?>" target="_blank">
                                <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                            </a>
                        </div>

                        <div class="mb-3">
                            <?php echo status_badge($selectedPrescription['status']); ?>
                            <span class="pill ms-2">Follow-up: <?php echo format_date($selectedPrescription['follow_up_date']); ?></span>
                        </div>

                        <h6>Medicines</h6>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Timing</th>
                                        <th>Duration</th>
                                        <th>Instructions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($medicineRows as $med): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo escape($med['medicine_name']); ?></div>
                                            <div class="text-muted small"><?php echo escape($med['generic_name'] ?? ''); ?></div>
                                        </td>
                                        <td><?php echo escape($med['dosage'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge-timing"><?php echo escape($med['frequency'] ?? '-'); ?></span>
                                            <span class="text-muted small"><?php echo escape($med['food_timing'] ?? ''); ?></span>
                                        </td>
                                        <td><?php echo escape($med['duration_days'] ?? '-') . ' days'; ?></td>
                                        <td><?php echo escape($med['instructions'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="mt-4">Recommended Tests</h6>
                        <?php if (!empty($testRows)): ?>
                            <ul class="list-group list-group-flush mb-4">
                                <?php foreach ($testRows as $test): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo escape($test['test_name']); ?></strong>
                                        <div class="text-muted small"><?php echo escape($test['notes'] ?? ''); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No tests recommended.</p>
                        <?php endif; ?>

                        <h6>Billing</h6>
                        <?php if (!empty($selectedPrescription['invoice_id'])): ?>
                            <div class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between">
                                    <span>Total Amount</span>
                                    <strong>Rs. <?php echo number_format((float) ($selectedPrescription['total_amount'] ?? 0), 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted">
                                    <span>Paid</span>
                                    <span>Rs. <?php echo number_format((float) ($selectedPrescription['paid_amount'] ?? 0), 2); ?></span>
                                </div>
                                <div class="mt-2">Payment Status: <?php echo status_badge(payment_status_label($selectedPrescription)); ?></div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Bill will be generated by the pharmacy once medicines are prepared.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">Select a prescription from the left to view details.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>