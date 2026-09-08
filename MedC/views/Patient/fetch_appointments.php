<?php
session_start();
include("../../connection/config.php");

date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'patient') {
    http_response_code(403);
    echo '<div class="alert alert-danger m-3">Access denied.</div>';
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.jitsi_meeting_link,
            d.f_name AS doctor_fname, d.l_name AS doctor_lname
     FROM appointment a
     LEFT JOIN doctor d ON a.did = d.d_id
     WHERE a.pid = ? OR a.user_id = ?
     ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.appointment_id DESC"
);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$currentTimestamp = time();
$upcomingAppointments = [];
$pendingAppointments = [];
$completedAppointments = [];

while ($row = $result->fetch_assoc()) {
    $appointmentTimestamp = strtotime(($row['appointment_date'] ?? '') . ' ' . ($row['appointment_time'] ?? ''));
    $status = trim((string) ($row['status'] ?? 'Pending'));
    $normalizedStatus = strtolower($status);
    $doctorName = trim((string) (($row['doctor_fname'] ?? '') . ' ' . ($row['doctor_lname'] ?? '')));

    if ($doctorName === '') {
        $doctorName = 'Doctor not assigned';
    } else {
        $doctorName = 'Dr. ' . $doctorName;
    }

    $row['doctor_name'] = $doctorName;
    $row['appointment_timestamp'] = $appointmentTimestamp;

    if ($normalizedStatus === 'pending') {
        $pendingAppointments[] = $row;
        continue;
    }

    if ($normalizedStatus === 'confirmed' && $appointmentTimestamp >= $currentTimestamp) {
        $upcomingAppointments[] = $row;
        continue;
    }

    if ($normalizedStatus === 'completed' || ($normalizedStatus === 'confirmed' && $appointmentTimestamp < $currentTimestamp)) {
        $completedAppointments[] = $row;
    }
}

$stmt->close();

function medc_patient_appointment_badge(string $status): string
{
    $normalizedStatus = strtolower(trim($status));
    if ($normalizedStatus === 'completed') {
        return 'bg-success';
    }
    if ($normalizedStatus === 'confirmed') {
        return 'bg-primary';
    }
    if ($normalizedStatus === 'pending') {
        return 'bg-warning text-dark';
    }
    if ($normalizedStatus === 'cancelled') {
        return 'bg-danger';
    }
    return 'bg-secondary';
}

function medc_render_patient_appointment_rows(array $appointments, int $currentTimestamp): string
{
    if (empty($appointments)) {
        return '<tr><td colspan="6" class="text-center text-muted py-4">No appointments found in this section.</td></tr>';
    }

    $rows = '';
    foreach ($appointments as $appointment) {
        $appointmentId = (int) ($appointment['appointment_id'] ?? 0);
        $appointmentDate = (string) ($appointment['appointment_date'] ?? '');
        $appointmentTime = (string) ($appointment['appointment_time'] ?? '');
        $status = trim((string) ($appointment['status'] ?? 'Pending'));
        $statusBadge = medc_patient_appointment_badge($status);
        $meetingLink = trim((string) ($appointment['jitsi_meeting_link'] ?? ''));
        $appointmentTimestamp = (int) ($appointment['appointment_timestamp'] ?? 0);
        $sessionCell = '<span class="text-muted">Not available</span>';

        if (strtolower($status) === 'confirmed') {
            if ($meetingLink !== '' && $appointmentTimestamp <= $currentTimestamp) {
                $sessionCell = '<a href="' . htmlspecialchars($meetingLink, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="btn btn-sm btn-primary">Join Meeting</a>';
            } elseif ($meetingLink !== '') {
                $sessionCell = '<span class="text-muted">Available at appointment time</span>';
            } else {
                $sessionCell = '<span class="text-muted">Will be shared after confirmation</span>';
            }
        } elseif (strtolower($status) === 'pending') {
            $sessionCell = '<span class="text-muted">Waiting for admin confirmation</span>';
        } elseif (strtolower($status) === 'completed') {
            $sessionCell = '<span class="text-muted">Consultation completed</span>';
        }

        $rows .= '<tr>';
        $rows .= '<td>' . $appointmentId . '</td>';
        $rows .= '<td>' . htmlspecialchars((string) ($appointment['doctor_name'] ?? 'Doctor not assigned'), ENT_QUOTES, 'UTF-8') . '</td>';
        $rows .= '<td>' . htmlspecialchars($appointmentDate, ENT_QUOTES, 'UTF-8') . '</td>';
        $rows .= '<td>' . htmlspecialchars(date('h:i A', strtotime($appointmentTime)), ENT_QUOTES, 'UTF-8') . '</td>';
        $rows .= '<td><span class="badge ' . $statusBadge . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span></td>';
        $rows .= '<td>' . $sessionCell . '</td>';
        $rows .= '</tr>';
    }

    return $rows;
}
?>
<style>
    .patient-appointments-shell {
        padding: 1rem;
    }

    .appointment-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .appointment-summary-card {
        border: 1px solid #dfe8f6;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 25px rgba(17, 43, 91, 0.08);
        padding: 1.1rem 1.2rem;
    }

    .appointment-summary-card h4 {
        margin: 0;
        font-size: 1rem;
        color: #22406f;
        font-weight: 600;
    }

    .appointment-summary-count {
        margin-top: 0.5rem;
        font-size: 2rem;
        font-weight: 700;
        color: #0d2e63;
    }

    .appointment-section-card {
        border: 1px solid #dfe8f6;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 10px 25px rgba(17, 43, 91, 0.08);
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .appointment-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.9rem;
    }

    .appointment-section-header h3 {
        margin: 0;
        font-size: 1.15rem;
        color: #12315f;
        font-weight: 700;
    }

    .appointment-section-header p {
        margin: 0.3rem 0 0;
        color: #6a7b98;
        font-size: 0.92rem;
    }

    .appointment-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 42px;
        border-radius: 999px;
        background: #edf4ff;
        color: #145be0;
        font-weight: 700;
    }

    .appointment-table-wrap {
        overflow-x: auto;
    }

    .appointment-table-wrap table {
        margin-bottom: 0;
    }

    @media (max-width: 767px) {
        .appointment-summary-grid {
            grid-template-columns: 1fr;
        }

        .appointment-section-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="container patient-appointments-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1" style="color:#12315f;">All Appointments</h2>
            <p class="text-muted mb-0">Here you can see your upcoming, pending, and completed appointments.</p>
        </div>
    </div>

    <div class="appointment-summary-grid">
        <div class="appointment-summary-card">
            <h4>Upcoming Appointments</h4>
            <div class="appointment-summary-count"><?php echo count($upcomingAppointments); ?></div>
        </div>
        <div class="appointment-summary-card">
            <h4>Pending Appointments</h4>
            <div class="appointment-summary-count"><?php echo count($pendingAppointments); ?></div>
        </div>
        <div class="appointment-summary-card">
            <h4>Completed Appointments</h4>
            <div class="appointment-summary-count"><?php echo count($completedAppointments); ?></div>
        </div>
    </div>

    <div class="appointment-section-card">
        <div class="appointment-section-header">
            <div>
                <h3>Upcoming Appointments</h3>
                <p>Confirmed appointments that are still scheduled ahead.</p>
            </div>
            <span class="appointment-count-badge"><?php echo count($upcomingAppointments); ?></span>
        </div>
        <div class="appointment-table-wrap">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Appointment ID</th>
                        <th>Doctor Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Session Link</th>
                    </tr>
                </thead>
                <tbody><?php echo medc_render_patient_appointment_rows($upcomingAppointments, $currentTimestamp); ?></tbody>
            </table>
        </div>
    </div>

    <div class="appointment-section-card">
        <div class="appointment-section-header">
            <div>
                <h3>Pending Appointments</h3>
                <p>Appointments waiting for confirmation from the admin side.</p>
            </div>
            <span class="appointment-count-badge"><?php echo count($pendingAppointments); ?></span>
        </div>
        <div class="appointment-table-wrap">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Appointment ID</th>
                        <th>Doctor Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Session Link</th>
                    </tr>
                </thead>
                <tbody><?php echo medc_render_patient_appointment_rows($pendingAppointments, $currentTimestamp); ?></tbody>
            </table>
        </div>
    </div>

    <div class="appointment-section-card">
        <div class="appointment-section-header">
            <div>
                <h3>Completed Appointments</h3>
                <p>Appointments that are already finished, including past confirmed sessions.</p>
            </div>
            <span class="appointment-count-badge"><?php echo count($completedAppointments); ?></span>
        </div>
        <div class="appointment-table-wrap">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Appointment ID</th>
                        <th>Doctor Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Session Link</th>
                    </tr>
                </thead>
                <tbody><?php echo medc_render_patient_appointment_rows($completedAppointments, $currentTimestamp); ?></tbody>
            </table>
        </div>
    </div>
</div>
