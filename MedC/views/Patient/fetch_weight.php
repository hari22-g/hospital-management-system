<?php
include("../../connection/config.php");

header('Content-Type: application/json');

$sql = "SELECT DATE_FORMAT(month, '%d %M') AS month_name, weight FROM weight_tracker WHERE user_id = 0";
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
