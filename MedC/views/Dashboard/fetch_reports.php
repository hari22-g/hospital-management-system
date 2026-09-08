<?php
include("../../connection/config.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "User not authenticated"]));
}

$user_id = $_SESSION['user_id'];

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

$sql = "SELECT id, report_type, referred_by, report_date, file_path FROM reports WHERE user_id = ? ORDER BY report_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode($reports);
$stmt->close();
$conn->close();
?>