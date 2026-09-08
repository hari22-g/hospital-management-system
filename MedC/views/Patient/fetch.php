<?php
include("../../connection/config.php");
session_start();
$user_id = $_SESSION["user_id"];
header('Content-Type: application/json');

$sql = "SELECT DATE_FORMAT(month, '%d %M') AS month_name, weight 
        FROM weight_tracker 
        WHERE user_id = '$user_id' 
        ORDER BY created_at DESC
        LIMIT 4";   
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "weight" => (float) $row["weight"],  // Ensure numeric format
        "month" => $row["month_name"]
    ];
}

$conn->close();
echo json_encode($data);
?>
