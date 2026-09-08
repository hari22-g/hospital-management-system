<?php
session_start();
include("connection/config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in as a patient to book an appointment.");
}
$user_id  = $_SESSION["user_id"];
$pid = $_POST['patient_id'];
$doctor_id = $_POST['doctor_id'];
$appointment_date = $_POST['appointment_date'];
$appointment_time = $_POST['appointment_time'];

if (!empty($appointment_date)) {
    $parsedDate = date_create($appointment_date);
    if ($parsedDate !== false) {
        $appointment_date = $parsedDate->format('Y-m-d');
    }
}

if (!empty($appointment_time)) {
    $parsedTime = date_create($appointment_time);
    if ($parsedTime !== false) {
        $appointment_time = $parsedTime->format('H:i:s');
    }
}

// ✅ Check if the selected time slot is already booked
$checkQuery = "SELECT COUNT(*) FROM appointment WHERE did = ? AND appointment_date = ? AND appointment_time = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo "<script>alert('This time slot is already booked. Please choose another time.'); window.history.back();</script>";
    exit;
}


$insertQuery = "INSERT INTO appointment (pid, did, appointment_date, appointment_time,user_id,status) VALUES (?, ?, ?, ?,?, 'Pending')";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("iissi", $pid, $doctor_id, $appointment_date, $appointment_time,$user_id);

if ($stmt->execute()) {
   
    $doctor_result = $conn->prepare("SELECT email, f_name, l_name FROM doctor WHERE d_id = ?");
    $doctor_result->bind_param("i", $doctor_id);
    $doctor_result->execute();
    $doctor_result->bind_result($doctor_email, $doctor_fname, $doctor_lname);
    $doctor_result->fetch();
    $doctor_result->close();

    if (!$doctor_email) {
        echo "<script>alert('Appointment booked, but doctor email not found.'); window.location.href = '" . $localhost . "views/Patient/patient_dashboard.php';</script>";
        exit;
    }

    // ✅ Fetch patient details
    $patient_result = $conn->prepare("SELECT firstname, email FROM patient WHERE pid = ?");
    $patient_result->bind_param("i", $pid);
    $patient_result->execute();
    $patient_result->bind_result($patient_name, $patient_email);
    $patient_result->fetch();
    $patient_result->close();

    // ✅ Send email to doctor
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'medc.appointments@gmail.com'; // Your email
        $mail->Password   = 'xjvu akab tklh ehcs';  // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email details
        $mail->setFrom('medc.appointments@gmail.com', 'MedC');
        $mail->addAddress($doctor_email, $doctor_fname . " " . $doctor_lname);
        $mail->Subject = 'New Appointment Notification';
        $mail->Body    = "Hello Dr. $doctor_fname $doctor_lname,\n\nA new appointment has been booked by $patient_name.\n\n"
                        . "📅 Date: $appointment_date\n⏰ Time: $appointment_time\n\n"
                        . "Please review and approve it.\n\nThank you,\nMedC Team";

        $mail->send();
        echo "<script>alert('Appointment booked successfully! Email notification sent to Dr. $doctor_fname.'); window.location.href = '" . $localhost . "views/Patient/patient_dashboard.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Appointment booked, but email could not be sent. Error: {$mail->ErrorInfo}'); window.location.href = '" . $localhost . "views/Patient/patient_dashboard.php';</script>";
    }
} else {
    if ($stmt->errno === 1062) {
        echo "<script>alert('This date and time slot is already booked. Please choose another slot.'); window.history.back();</script>";
    } else {
        echo "<script>alert('Error booking appointment. Please try again later.'); window.history.back();</script>";
    }
}

$stmt->close();
$conn->close();
?>
