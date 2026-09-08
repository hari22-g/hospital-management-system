<?php
session_start();
include 'connection/config.php';
include 'include/prescription_helpers.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'doctor') {
    die('Error: Access denied.');
}

$doctor_id = (int) $_SESSION['user_id'];
$doctor_name = trim((string) ($_SESSION['f_name'] ?? 'Doctor'));

$todayAppointments = $conn->prepare(
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.reason,
            p.firstname, p.lastname
     FROM appointment a
     JOIN patient p ON a.pid = p.pid
     WHERE a.did = ? AND DATE(a.appointment_date) = CURDATE()
     ORDER BY a.appointment_time ASC"
);
$todayAppointments->bind_param('i', $doctor_id);
$todayAppointments->execute();
$appointments = $todayAppointments->get_result();

$recentPrescriptions = $conn->prepare(
    "SELECT pr.prescription_id, pr.appointment_id, pr.created_at, pr.status,
            p.firstname, p.lastname
     FROM prescriptions pr
     JOIN patient p ON pr.patient_id = p.pid
     WHERE pr.doctor_id = ?
     ORDER BY pr.created_at DESC
     LIMIT 6"
);
$recentPrescriptions->bind_param('i', $doctor_id);
$recentPrescriptions->execute();
$prescriptions = $recentPrescriptions->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Panel - Prescriptions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', sans-serif;
            background: #f1f6ff;
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
        .sidebar h4 {
            font-weight: 700;
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
        .hero {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 18px 35px rgba(18, 62, 120, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .card-surface {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 16px 28px rgba(21, 53, 90, 0.08);
        }
        .status-pill {
            font-size: 12px;
            border-radius: 999px;
            padding: 6px 12px;
        }
        .table thead th {
            background: #e8f1ff;
            color: #1d4a7a;
            border: none;
        }
        .btn-consult {
            background: #1f5fbf;
            border: none;
        }
        .btn-consult:hover {
            background: #1a4f9a;
        }
        .empty-state {
            color: #6d86b0;
            font-size: 14px;
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
        <h4>Doctor Panel</h4>
        <p class="small mb-4">Welcome, <?php echo escape($doctor_name); ?></p>
        <a class="active" href="doctor_prescriptions.php"><i class="fa-solid fa-notes-medical me-2"></i>Prescriptions</a>
        <a href="doctor_dashboard.php"><i class="fa-solid fa-stethoscope me-2"></i>Doctor Dashboard</a>
        <a href="view_appointments.php"><i class="fa-regular fa-calendar me-2"></i>Appointments</a>
        <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Logout</a>
    </aside>
    <main class="content">
        <section class="hero">
            <div>
                <h2 class="mb-1">Digital Prescription Center</h2>
                <p class="text-muted mb-0">Track today appointments, consult, and issue prescriptions seamlessly.</p>
            </div>
            <div class="text-end">
                <div class="fw-semibold">Today</div>
                <div class="text-muted small"><?php echo date('d M Y'); ?></div>
            </div>
        </section>

        <section class="card-surface mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Today&#39;s Appointments</h5>
                <span class="text-muted small">Click consult to start prescription</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                        <?php while ($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo escape($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                <td><?php echo escape(date('h:i A', strtotime($row['appointment_time']))); ?></td>
                                <td><?php echo escape($row['reason'] ?? 'General consultation'); ?></td>
                                <td><?php echo status_badge($row['status']); ?></td>
                                <td>
                                    <?php if (in_array($row['status'], ['Confirmed', 'In Consultation', 'Completed'])): ?>
                                        <a class="btn btn-sm btn-consult text-white" href="doctor_consultation.php?appointment_id=<?php echo (int) $row['appointment_id']; ?>">Consult</a>
                                    <?php else: ?>
                                        <span class="text-muted small">Awaiting confirmation</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">No appointments scheduled for today.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card-surface">
            <h5 class="mb-3">Recent Prescriptions</h5>
            <div class="row g-3">
                <?php if ($prescriptions && $prescriptions->num_rows > 0): ?>
                    <?php while ($rx = $prescriptions->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="fw-semibold"><?php echo escape($rx['firstname'] . ' ' . $rx['lastname']); ?></div>
                                        <div class="text-muted small">Rx #<?php echo (int) $rx['prescription_id']; ?></div>
                                    </div>
                                    <?php echo status_badge($rx['status']); ?>
                                </div>
                                <div class="text-muted small mt-2">Issued on <?php echo format_date($rx['created_at']); ?></div>
                                <a class="btn btn-sm btn-outline-primary mt-3" href="doctor_consultation.php?appointment_id=<?php echo (int) ($rx['appointment_id'] ?? 0); ?>">View</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 empty-state">No prescriptions issued yet.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>