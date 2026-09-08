<?php
include("connection/config.php");   

$result = mysqli_query($conn, "SELECT a.appointment_id, p.firstname, d.f_name, a.appointment_date, a.appointment_time, a.status 
FROM appointment a 
JOIN patient p ON a.pid = p.pid 
JOIN doctor d ON a.did = d.d_id");

echo "<table border='1'><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
            <td>{$row['appointment_id']}</td>
            <td>{$row['firstname']}</td>
            <td>{$row['f_name']}</td>
            <td>{$row['appointment_date']}</td>
            <td>{$row['appointment_time']}</td>
            <td>{$row['status']}</td>
          </tr>";
}

echo "</table>";

mysqli_close($conn);
?>
