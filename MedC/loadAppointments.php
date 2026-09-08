<?php 
session_start();
include("connection/config.php");

// Set timezone (adjust as needed)
date_default_timezone_set('Asia/Kolkata'); // Change to your preferred timezone

function resolveDoctorId(mysqli $conn): int
{
    if (!empty($_SESSION['doctor_id'])) {
        return (int) $_SESSION['doctor_id'];
    }

    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '') {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    $stmt = $conn->prepare('SELECT d_id FROM doctor WHERE email = ? LIMIT 1');
    if (!$stmt) {
        return (int) ($_SESSION['user_id'] ?? 0);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($doctorId);
    $resolved = $stmt->fetch() ? (int) $doctorId : (int) ($_SESSION['user_id'] ?? 0);
    $stmt->close();

    return $resolved;
}

$doctor_id = resolveDoctorId($conn); // Ensure this holds the correct doctor ID

// Fetch doctor's appointments
$stmt = $conn->prepare("SELECT a.appointment_id, p.firstname, p.lastname, a.user_id, a.appointment_date, 
                                a.appointment_time, a.status, a.jitsi_meeting_link 
                        FROM appointment a 
                        JOIN patient p ON a.pid = p.pid 
                        WHERE a.did = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// Current timestamp
$currentTimestamp = time();


echo '<!-- Bootstrap Modal for Patient Details -->
    <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientModalLabel">Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="patient-details">
                        <p>Loading patient details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<div class="container loadappointments mt-3">
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="bg-black bg-gradient text-light">Appointment ID</th>
                <th scope="col" class="bg-black bg-gradient text-light">Patient Name</th>
                <th scope="col" class="bg-black bg-gradient text-light">Date</th>
                <th scope="col" class="bg-black bg-gradient text-light">Time</th>
                <th scope="col" class="bg-black bg-gradient text-light">Patient details</th>
                <th scope="col" class="action bg-black bg-gradient text-light">Status</th>
                <th scope="col" class="bg-black bg-gradient text-light">Action</th>
                <th scope="col" class="bg-black bg-gradient text-light">Session link</th>
            </tr>
        </thead>
            <tbody>';

while ($row = $result->fetch_assoc()) {
    $appointmentTimestamp = strtotime($row['appointment_date'] . ' ' . $row['appointment_time']);
    $currentTimestamp = time();
    
    echo '<tr>
            <td>' . $row['appointment_id'] . '</td>
            <td>' . $row['firstname'] . " " . $row['lastname'] . '</td>
            <td>' . $row['appointment_date'] . '</td>
            <td>' . $row['appointment_time'] . '</td>
            <td>
                <button class="btn btn-info view-more" data-bs-toggle="modal" data-bs-target="#patientModal"
                    data-pid="' . $row['user_id'] . '">
                    View More
                </button>
            </td>
            <td>' . $row['status'] . '</td>
            <td>';

    if ($row['status'] == 'Pending') {
        echo '<form action="update_appointment_status.php" method="POST">
                <input type="hidden" name="appointment_id" value="' . $row['appointment_id'] . '">
                <button type="submit" name="approve" class="btn btn-success approve_btn">Approve</button>
                <button type="submit" name="reject" class="btn btn-danger">Reject</button>
              </form>';
    } else {
        echo 'Action Performed';
    }

    echo '</td>
          <td class="meeting-container"
              data-appointment="' . $appointmentTimestamp . '"
              data-link="' . $row['jitsi_meeting_link'] . '">';

    if ($row['status'] == 'Confirmed') {
        if ($currentTimestamp >= $appointmentTimestamp) {
            echo '<a href="' . htmlspecialchars($row['jitsi_meeting_link']) . '" target="_blank" class="btn btn-primary">Join Meeting</a>';
        } else {
            echo '<span class="text-muted">Meeting available at appointment time</span>';
        }
    } else {
        echo 'Pending Approval';
    }

    echo '</td>
        </tr>';
}

echo '  </tbody></table>
    </div>
    <div id="patient-details" class="p-4 bg-light" style="display: none;"></div>';
?>
