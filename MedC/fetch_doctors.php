<?php
include("connection/config.php");

if (isset($_POST['specialization'])) {
    $specialization = $_POST['specialization'];
    
    $sql = "SELECT d_id, f_name FROM doctor WHERE specialization = '$specialization' AND approval = 'approved'";
    $result = mysqli_query($conn, $sql);
    
    echo "<option value=''>Select Doctor</option>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<option value='{$row['d_id']}'>{$row['f_name']}</option>";
    }
}
?>
