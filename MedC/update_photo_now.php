<?php
require_once 'connection/config.php';

// Update Dr. Ramesh Kumar's photo
$sql = "UPDATE doctor SET photo_path = 'uploads/Doctor_photos/Ramesh kumar.jpg' 
        WHERE (LOWER(f_name) = 'ramesh' OR LOWER(l_name) = 'kumar')";

if ($conn->query($sql) === TRUE) {
    echo "✓ Photo updated successfully!<br>";
    echo "Dr. Ramesh Kumar is now using the new photo.<br>";
    
    // Verify the update
    $verify_sql = "SELECT f_name, l_name, photo_path FROM doctor WHERE LOWER(f_name) = 'ramesh' OR LOWER(l_name) = 'kumar'";
    $result = $conn->query($verify_sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<strong>Doctor: Dr. " . $row['f_name'] . " " . $row['l_name'] . "</strong><br>";
            echo "Photo: " . $row['photo_path'] . "<br><br>";
        }
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
