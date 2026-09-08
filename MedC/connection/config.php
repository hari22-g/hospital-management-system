<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$localhost = "http://" . $serverHost . "/MedC/MedC/";
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medc";

$conn = new mysqli($servername, $username, $password, $dbname); 
if ($conn->connect_error) {
    die("Connection Failed". $conn->connect_error);
}
