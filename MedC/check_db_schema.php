<?php
require_once 'connection/config.php';

// Check doctor table structure
$sql = "DESCRIBE doctor";
$result = $conn->query($sql);

echo "Doctor table columns:\n";
echo "====================\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
    }
}

$conn->close();
?>
