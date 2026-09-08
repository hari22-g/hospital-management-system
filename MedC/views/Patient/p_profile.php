<?php
session_start();
include("../../connection/config.php");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Please login to view your profile.</div></div>";
    exit;
}

$patientData = null;
$email = $_SESSION['email'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if (!empty($email)) {
    $stmt = $conn->prepare("SELECT * FROM patient WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $patientData = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

if (!$patientData && !empty($userId)) {
    $stmt = $conn->prepare("SELECT * FROM patient WHERE pid = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patientData = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

if (!$patientData) {
    echo "<div class='container mt-4'><div class='alert alert-info'>Profile data not found.</div></div>";
    exit;
}

$firstName = htmlspecialchars($patientData['firstname'] ?? 'N/A');
$lastName = htmlspecialchars($patientData['lastname'] ?? 'N/A');
$dob = htmlspecialchars($patientData['dob'] ?? 'N/A');
$gender = htmlspecialchars($patientData['gender'] ?? 'N/A');
$height = htmlspecialchars((string)($patientData['height'] ?? 'N/A'));
$weight = htmlspecialchars((string)($patientData['weight'] ?? 'N/A'));
$bloodGroup = htmlspecialchars($patientData['bloodgroup'] ?? 'N/A');
$contactNo = htmlspecialchars($patientData['contact_no'] ?? 'N/A');

echo '
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Patient Information</div>
                <div class="card-body">
                    <p class="card-text"><strong>First Name:</strong> ' . $firstName . '</p>
                    <p class="card-text"><strong>Last Name:</strong> ' . $lastName . '</p>
                    <p class="card-text"><strong>Date of Birth:</strong> ' . $dob . '</p>
                    <p class="card-text"><strong>Gender:</strong> ' . $gender . '</p>
                    <p class="card-text"><strong>Height:</strong> ' . $height . ($height !== 'N/A' ? ' cm' : '') . '</p>
                    <p class="card-text"><strong>Weight:</strong> ' . $weight . ($weight !== 'N/A' ? ' kg' : '') . '</p>
                    <p class="card-text"><strong>Blood Group:</strong> ' . $bloodGroup . '</p>
                    <p class="card-text"><strong>Contact No:</strong> ' . $contactNo . '</p>
                </div>
            </div>
        </div>
    </div>
</div>';
