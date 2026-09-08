<?php
session_start();
include 'connection/config.php';
include 'include/prescription_helpers.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$dateFrom = trim((string) ($_GET['from'] ?? ''));
$dateTo = trim((string) ($_GET['to'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where[] = 'pr.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
if ($dateFrom !== '') {
    $where[] = 'DATE(pr.created_at) >= ?';
    $params[] = $dateFrom;
    $types .= 's';
}
if ($dateTo !== '') {
    $where[] = 'DATE(pr.created_at) <= ?';
    $params[] = $dateTo;
    $types .= 's';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$query =
    "SELECT pr.prescription_id, pr.status, pr.created_at,
            p.firstname, p.lastname,
            d.f_name, d.l_name,
            inv.invoice_id, inv.total_amount, inv.paid_amount, inv.status AS invoice_status
     FROM prescriptions pr
     JOIN patient p ON pr.patient_id = p.pid
     JOIN doctor d ON pr.doctor_id = d.d_id
     LEFT JOIN invoices inv ON pr.invoice_id = inv.invoice_id
     $whereSql
     ORDER BY pr.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$prescriptions = $stmt->get_result();

$billingStmt = $conn->prepare(
    "SELECT inv.invoice_id, inv.invoice_date, inv.total_amount, inv.paid_amount, inv.status,
            p.firstname, p.lastname, d.f_name, d.l_name
     FROM invoices inv
     JOIN patient p ON inv.patient_id = p.pid
     LEFT JOIN doctor d ON inv.doctor_id = d.d_id
     ORDER BY inv.invoice_date DESC"
);
$billingStmt->execute();
$bills = $billingStmt->get_result();

$revenue = $conn->query("SELECT SUM(total_amount) AS total FROM invoices WHERE status = 'Paid'");
$revenueTotal = $revenue ? (float) ($revenue->fetch_assoc()['total'] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Prescription Records</title>
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
        .metric {
            background: #e9f2ff;
            border-radius: 14px;
            padding: 16px;
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
        <h4>Admin Panel</h4>
        <p class="small mb-4">Prescription Records</p>
        <a class="active" href="admin_prescriptions.php"><i class="fa-solid fa-notes-medical me-2"></i>Prescription Records</a>
        <a href="admin_portal.php"><i class="fa-solid fa-chart-line me-2"></i>Admin Portal</a>
        <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout</a>
    </aside>

    <main class="content">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-surface">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Prescription Records</h5>
                        <form class="d-flex gap-2" method="GET" action="admin_prescriptions.php">
                            <input type="date" name="from" class="form-control" value="<?php echo escape($dateFrom); ?>">
                            <input type="date" name="to" class="form-control" value="<?php echo escape($dateTo); ?>">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach (['Pending', 'Processing', 'Ready', 'Dispensed'] as $status): ?>
                                    <option value="<?php echo escape($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo escape($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-primary" type="submit">Filter</button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Rx ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($prescriptions && $prescriptions->num_rows > 0): ?>
                                <?php while ($row = $prescriptions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo (int) $row['prescription_id']; ?></td>
                                        <td><?php echo escape($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                        <td>Dr. <?php echo escape($row['f_name'] . ' ' . $row['l_name']); ?></td>
                                        <td><?php echo format_date($row['created_at']); ?></td>
                                        <td><?php echo status_badge($row['status']); ?></td>
                                        <td>
                                            <?php if (!empty($row['invoice_id'])): ?>
                                                <span class="text-muted small">Rs. <?php echo number_format((float) ($row['total_amount'] ?? 0), 2); ?></span><br>
                                                <?php echo status_badge(payment_status_label($row)); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not billed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-muted">No prescriptions found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-surface mb-4">
                    <h5 class="mb-3">Billing Management</h5>
                    <div class="metric mb-3">
                        <div class="text-muted small">Total Revenue</div>
                        <div class="fs-4 fw-semibold">Rs. <?php echo number_format($revenueTotal, 2); ?></div>
                    </div>
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Patient</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($bills && $bills->num_rows > 0): ?>
                                <?php while ($bill = $bills->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo (int) $bill['invoice_id']; ?><br><span class="text-muted small">Rs. <?php echo number_format((float) $bill['total_amount'], 2); ?></span></td>
                                        <td><?php echo escape($bill['firstname'] . ' ' . $bill['lastname']); ?></td>
                                        <td><?php echo status_badge(payment_status_label($bill)); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-muted">No bills yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>