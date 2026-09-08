<?php
include("../connection/config.php");
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$user_type = strtolower(trim((string) ($_SESSION['user_type'] ?? ''))); // Get user role from session
$approval = $_GET['approval'] ?? '';
if ($user_type == 'doctor') {
    if ($approval == 'pending') {
        echo "<script>
        alert('Your account is not approved yet.');
        window.location.href = '" . $localhost . "homepage.php'; // Change this to your homepage URL
    </script>";
    exit();
    } else {
        // Display the doctor dashboard
        header('Location:' . $localhost . 'doctor_dashboard.php');
        exit();
        // Include doctor dashboard components here
    }
} elseif ($user_type == 'patient') {
    // Display the patient dashboard
    header('Location:' . $localhost . 'views/Patient/patient_dashboard.php');
    exit();
    // Include patient dashboard components here
} elseif ($user_type == 'admin') {
    // Display the admin portal
    header('Location:' . $localhost . 'admin_portal.php');
    exit();
}

header('Location:' . $localhost . 'homepage.php');
exit();

