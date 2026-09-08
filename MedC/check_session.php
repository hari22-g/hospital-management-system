<?php
session_start();
header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user_type' => null,
    'user_id' => null
];

if (isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
    $response['logged_in'] = true;
    $response['user_type'] = $_SESSION['user_type'];
    $response['user_id'] = $_SESSION['user_id'];
}

echo json_encode($response);
?>
