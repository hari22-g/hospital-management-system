<?php
require_once 'connection/config.php';

// Photo mapping
$photo_mapping = [
    'Aditi' => 'uploads/Doctor_photos/Aditi.webp',
    'Vidit' => 'uploads/Doctor_photos/vidit jani.jfif',
    'Jaswant' => 'uploads/Doctor_photos/Jaswant dave.jpg',
    'Ramesh' => 'uploads/Doctor_photos/Ramesh kumar.jpg',
    'Keni' => 'uploads/Doctor_photos/Keni patel.png'
];

$updated = 0;

// Get all approved doctors
$sql = "SELECT d_id, f_name, l_name FROM doctor WHERE approval = 'approved' ORDER BY f_name";
$result = $conn->query($sql);

echo "Linking doctor photos...\n";
echo "=======================\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fname = $row['f_name'];
        $lname = $row['l_name'];
        $did = $row['d_id'];
        
        $matched_photo = null;
        
        // Try to match
        foreach ($photo_mapping as $key => $photo) {
            if (stripos($fname, $key) !== false || stripos($key, $fname) !== false) {
                $matched_photo = $photo;
                break;
            }
            if (stripos($lname, $key) !== false || stripos($key, $lname) !== false) {
                $matched_photo = $photo;
                break;
            }
        }
        
        if ($matched_photo && file_exists($matched_photo)) {
            $update_sql = "UPDATE doctor SET photo_path = '$matched_photo' WHERE d_id = $did";
            if ($conn->query($update_sql) === TRUE) {
                echo "✓ Dr. $fname $lname -> " . basename($matched_photo) . "\n";
                $updated++;
            }
        } else {
            echo "✗ Dr. $fname $lname -> No matching photo\n";
        }
    }
}

echo "\n=======================\n";
echo "Updated: $updated doctors\n";

$conn->close();
?>
