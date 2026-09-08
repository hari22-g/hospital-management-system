<?php
session_start();
$user_id =  $_SESSION['user_id'];
include '../../connection/config.php'; // Include your database connection file

$sql = "SELECT * FROM medications WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($drug_row = mysqli_fetch_assoc($result)) {
        echo '<tr class="drug">
            <td class="drugtd">' . htmlspecialchars($drug_row["brand_name"]) . '</td>
            <td class="drugtd">' . htmlspecialchars($drug_row["generic_name"]) . '</td>
            <td class="drugtd editable" contenteditable="true">' . htmlspecialchars($drug_row["strength"]) . '</td>
            <td class="drugtd editable" contenteditable="true">' . htmlspecialchars($drug_row["form"]) . '</td>
            <td class="drugtd editable" contenteditable="true">' . htmlspecialchars($drug_row["duration"]) . '</td>
        </tr>';
    }
} else {
    echo "<tr><td colspan='5'>No records found</td></tr>";
}
?>
