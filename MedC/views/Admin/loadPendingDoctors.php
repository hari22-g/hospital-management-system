<?php
session_start();
include("../../connection/config.php");

$sql_pending = "SELECT * FROM doctor WHERE approval = 'pending'";
$result_pending = mysqli_query($conn, $sql_pending);
$sr_no = 1;

$output = '<div class="container mt-2 pending_doctors_list" style="height:100vh;">
<table class="table table-striped">
<thead class="table-dark">
  <tr>
    <th scope="col">Sr No.</th>
    <th scope="col">Name</th>
    <th scope="col">Specialization</th>
    <th scope="col">Experience(Years)</th>
    <th scope="col">Consultation Fee(INR)</th>
    <th scope="col">Phone No.</th>
    <th scope="col">Document</th>
    <th scope="col">Operation</th>
  </tr>
</thead>
<tbody>';

if (mysqli_num_rows($result_pending) > 0) {
    while ($row_pending = mysqli_fetch_array($result_pending)) {
        $doctor_id = $row_pending["doctor_id"];
        $certificate_path = $row_pending["certificate_path"];
        $view = $localhost . 'uploads/Doctor_certi/' . $doctor_id . $certificate_path;
        $output .= '<tr>
            <td>' . $sr_no . '</td>
            <td>' . $row_pending['f_name'] . " " . $row_pending['l_name'] . '</td>
            <td>' . $row_pending['specialization'] . '</td>
            <td>' . $row_pending['experience'] . '</td>
            <td>' . $row_pending['consultation_fees'] . '</td>
            <td>' . $row_pending['contact_no'] . '</td>
            <td> <a href="' . $view . '">View</a></td>
            <td>
                <button type="button" class="btn btn-success approve-btn" data-id="' . $row_pending['d_id'] . '">Approve</button>
                <button type="button" class="btn btn-danger reject-btn" data-id="' . $row_pending['d_id'] . '">Reject</button>
            </td>
        </tr>';
        $sr_no++;
    }
    $output .= '</tbody></table></div>';
} else {
    $output = '<p class="m-4 text-danger">No Pending found.</p>';
}

echo $output;
?>
