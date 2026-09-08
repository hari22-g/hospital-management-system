<?php
session_start();
include 'connection/config.php';
include 'include/prescription_helpers.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$prescription_id = (int) ($_GET['prescription_id'] ?? 0);
if ($prescription_id <= 0) {
    die('Invalid prescription.');
}

$user_type = strtolower(trim((string) ($_SESSION['user_type'] ?? '')));
$user_id = (int) ($_SESSION['user_id'] ?? 0);

$detailStmt = $conn->prepare(
    "SELECT pr.*, p.firstname, p.lastname, p.email, p.contact_no,
            d.f_name, d.l_name, d.specialization
     FROM prescriptions pr
     JOIN patient p ON pr.patient_id = p.pid
     JOIN doctor d ON pr.doctor_id = d.d_id
     WHERE pr.prescription_id = ?"
);
$detailStmt->bind_param('i', $prescription_id);
$detailStmt->execute();
$prescription = $detailStmt->get_result()->fetch_assoc();
$detailStmt->close();

if (!$prescription) {
    die('Prescription not found.');
}

$allowed = false;
if (in_array($user_type, ['admin', 'pharmacist'])) {
    $allowed = true;
} elseif ($user_type === 'patient' && $user_id === (int) $prescription['patient_id']) {
    $allowed = true;
} elseif ($user_type === 'doctor' && $user_id === (int) $prescription['doctor_id']) {
    $allowed = true;
}

if (!$allowed) {
    die('Access denied.');
}

$medStmt = $conn->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ? ORDER BY medicine_id ASC");
$medStmt->bind_param('i', $prescription_id);
$medStmt->execute();
$medicineRows = $medStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$medStmt->close();

$testStmt = $conn->prepare("SELECT * FROM prescription_tests WHERE prescription_id = ? ORDER BY test_id ASC");
$testStmt->bind_param('i', $prescription_id);
$testStmt->execute();
$testRows = $testStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$testStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription #<?php echo (int) $prescription_id; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', sans-serif;
            background: #f4f8ff;
            color: #15355a;
        }
        .print-card {
            background: #fff;
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 16px 28px rgba(21, 53, 90, 0.12);
            max-width: 900px;
            margin: 30px auto;
        }
        .brand {
            font-size: 20px;
            font-weight: 700;
            color: #1f5fbf;
        }
        .meta {
            font-size: 13px;
            color: #6d86b0;
        }
        @media print {
            body {
                background: #fff;
            }
            .print-actions {
                display: none;
            }
            .print-card {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
<div class="print-card">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="brand">MedC Hospital</div>
            <div class="meta">Digital Prescription</div>
        </div>
        <div class="text-end meta">
            <div>Prescription #<?php echo (int) $prescription_id; ?></div>
            <div><?php echo format_date($prescription['created_at']); ?></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <h6>Patient</h6>
            <div><?php echo escape($prescription['firstname'] . ' ' . $prescription['lastname']); ?></div>
            <div class="meta"><?php echo escape($prescription['email'] ?? ''); ?></div>
            <div class="meta"><?php echo escape($prescription['contact_no'] ?? ''); ?></div>
        </div>
        <div class="col-md-6">
            <h6>Doctor</h6>
            <div>Dr. <?php echo escape($prescription['f_name'] . ' ' . $prescription['l_name']); ?></div>
            <div class="meta"><?php echo escape($prescription['specialization'] ?? ''); ?></div>
        </div>
    </div>

    <h6>Medicines</h6>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Instructions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($medicineRows as $med): ?>
            <tr>
                <td>
                    <strong><?php echo escape($med['medicine_name']); ?></strong><br>
                    <span class="meta"><?php echo escape($med['generic_name'] ?? ''); ?></span>
                </td>
                <td><?php echo escape($med['dosage'] ?? '-'); ?></td>
                <td><?php echo escape($med['frequency'] ?? '-'); ?> (<?php echo escape($med['food_timing'] ?? '-'); ?>)</td>
                <td><?php echo escape($med['duration_days'] ?? '-') . ' days'; ?></td>
                <td><?php echo escape($med['instructions'] ?? '-'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row g-3">
        <div class="col-md-6">
            <h6>Symptoms</h6>
            <p class="meta mb-3"><?php echo escape($prescription['symptoms'] ?? '-'); ?></p>
            <h6>Diagnosis</h6>
            <p class="meta"><?php echo escape($prescription['diagnosis'] ?? '-'); ?></p>
        </div>
        <div class="col-md-6">
            <h6>Recommended Tests</h6>
            <?php if (!empty($testRows)): ?>
                <ul class="meta">
                    <?php foreach ($testRows as $test): ?>
                        <li><?php echo escape($test['test_name']); ?> <?php echo !empty($test['notes']) ? '(' . escape($test['notes']) . ')' : ''; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="meta">No tests recommended.</p>
            <?php endif; ?>
            <h6 class="mt-3">Follow-up</h6>
            <p class="meta"><?php echo format_date($prescription['follow_up_date']); ?></p>
        </div>
    </div>

    <div class="print-actions text-end mt-4">
        <button class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
    </div>
</div>
</body>
</html>