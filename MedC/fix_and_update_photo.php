<?php
require_once 'connection/config.php';

// Add photo_path column if it doesn't exist
$check_sql = "SHOW COLUMNS FROM doctor WHERE Field = 'photo_path'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $alter_sql = "ALTER TABLE doctor ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "✓ Added photo_path column to doctor table\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
        exit();
    }
} else {
    echo "✓ photo_path column already exists\n";
}

// Now update Dr. Ramesh Kumar's photo
$update_sql = "UPDATE doctor SET photo_path = 'uploads/Doctor_photos/Ramesh kumar.jpg' 
                WHERE (LOWER(f_name) = 'ramesh' OR LOWER(l_name) = 'kumar')";

if ($conn->query($update_sql) === TRUE) {
    echo "✓ Photo updated successfully!\n";
    echo "Dr. Ramesh Kumar is now using: uploads/Doctor_photos/Ramesh kumar.jpg\n\n";
    
    // Verify
    $verify_sql = "SELECT f_name, l_name, photo_path FROM doctor WHERE LOWER(f_name) = 'ramesh' OR LOWER(l_name) = 'kumar'";
    $result = $conn->query($verify_sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "✓ Verified: Dr. " . ucfirst($row['f_name']) . " " . ucfirst($row['l_name']) . "\n";
            echo "  Photo: " . $row['photo_path'] . "\n";
        }
    }
} else {
    echo "Error updating photo: " . $conn->error . "\n";
}

$conn->close();
?>
