<?php
session_start();
include("../../connection/config.php");
$user_id = $_SESSION['user_id'];
$sql = "SELECT DATE(created_at) AS date, non_fasting_glucose, glucose_post_fast, hba1c 
        FROM biomarker 
        WHERE user_id = '$user_id' 
        ORDER BY created_at DESC";  // Order by created_at instead of alias "date"
$result = mysqli_query($conn, $sql);

$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>
