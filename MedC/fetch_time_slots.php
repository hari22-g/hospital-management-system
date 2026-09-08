<?php
include("connection/config.php");

if (isset($_POST['doctor_id'], $_POST['appointment_date'])) {
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];

    // Define available time slots
    $time_slots = [
        "09:00:00" => "9:00 AM - 10:00 AM",
        "10:00:00" => "10:00 AM - 11:00 AM",
        "11:00:00" => "11:00 AM - 12:00 PM",
        "12:00:00" => "12:00 PM - 1:00 PM",
        "14:00:00" => "2:00 PM - 3:00 PM",
        "15:00:00" => "3:00 PM - 4:00 PM",
        "16:00:00" => "4:00 PM - 5:00 PM"
    ];

    // Fetch booked slots for the selected doctor and date
    $query = "SELECT appointment_time FROM appointment WHERE did = ? AND appointment_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $doctor_id, $appointment_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }

    // Generate available time slots
    echo '<option value="">Select Time Slot</option>';
    foreach ($time_slots as $time => $label) {
        if (!in_array($time, $booked_slots)) {
            echo "<option value='$time'>$label</option>";
        }
    }

    $stmt->close();
}
?>
