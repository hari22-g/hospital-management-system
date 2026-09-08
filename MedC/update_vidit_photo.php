<?php
require_once 'connection/config.php';

// Update Dr. Vidit Jani's photo
$update_sql = "UPDATE doctor SET photo_path = 'uploads/Doctor_photos/vidit jani.jfif' 
                WHERE (LOWER(f_name) = 'vidit' OR LOWER(l_name) = 'jani')";

if ($conn->query($update_sql) === TRUE) {
    echo "✓ Photo replaced successfully!\n";
    echo "Dr. Vidit Jani's photo has been updated\n\n";
    
    // Verify
    $verify_sql = "SELECT f_name, l_name, photo_path FROM doctor WHERE LOWER(f_name) = 'vidit' OR LOWER(l_name) = 'jani'";
    $result = $conn->query($verify_sql);
    
    if ($result->num_rows > 0) {
        echo "Verification:\n";
        echo "=============\n";
        while ($row = $result->fetch_assoc()) {
            echo "Doctor: Dr. " . ucfirst($row['f_name']) . " " . ucfirst($row['l_name']) . "\n";
            echo "Photo: " . $row['photo_path'] . "\n";
        }
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
