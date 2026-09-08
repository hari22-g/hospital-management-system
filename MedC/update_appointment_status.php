<?php
session_start();
include("connection/config.php");

// Ensure the user is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    die("Error: Access denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    if (isset($_POST['approve'])) {
        $new_status = 'Confirmed';

        // Generate Jitsi Meeting Link
        $meeting_id = uniqid('medc_');  // Unique meeting ID
        $jitsi_meeting_link = "https://meet.jit.si/" . $meeting_id;

        // Update appointment status & save Jitsi link
        $stmt = $conn->prepare("UPDATE appointment SET status=?, jitsi_meeting_link=? WHERE appointment_id=?");
        $stmt->bind_param("ssi", $new_status, $jitsi_meeting_link, $appointment_id);

        if ($stmt->execute()) {
            header("Location: doctor_dashboard.php"); // Redirect back to dashboard
            exit();
        } else {
            echo "Error updating record: " . $stmt->error;
        }

        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $new_status = 'Cancelled';

        // Update appointment status
        $stmt = $conn->prepare("UPDATE appointment SET status=? WHERE appointment_id=?");
        $stmt->bind_param("si", $new_status, $appointment_id);

        if ($stmt->execute()) {
            header("Location: doctor_dashboard.php");
            exit();
        } else {
            echo "Error updating record: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>