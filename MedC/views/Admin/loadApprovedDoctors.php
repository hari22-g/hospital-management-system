<?php
session_start();
include("../../connection/config.php");

$sql_approved = "SELECT * FROM doctor WHERE approval = 'approved'";
$result_approved = mysqli_query($conn, $sql_approved);
$sr_no = 1;
$output = '<div class="container mt-2 approved_doctors_list" style="height:100vh;">
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

if (mysqli_num_rows($result_approved) > 0) {
    while ($row_approved = mysqli_fetch_array($result_approved)) {
        $doctor_id = $row_approved["doctor_id"];
        $certificate_path = $row_approved["certificate_path"];
        $view = $localhost . 'uploads/Doctor_certi/' . $doctor_id . $certificate_path;

        $output .= '<tr>
            <td>' . $sr_no . '</td>
            <td>' . $row_approved['f_name'] . " " . $row_approved['l_name'] . '</td>
            <td>' . $row_approved['specialization'] . '</td>
            <td>' . $row_approved['experience'] . '</td>
            <td>' . $row_approved['consultation_fees'] . '</td>
            <td>' . $row_approved['contact_no'] . '</td>
            <td> <a href="' . $view . '">View</a></td>
            <td>
                <button type="button" class="btn btn-danger reject-btn" data-id="' . $row_approved['d_id'] . '">Remove</button>
            </td>
        </tr>';
        $sr_no++;
    }
    $output .= '</tbody></table></div>';
} else {
    $output = '<p class="m-4 text-danger">No approved found.</p>';
}

echo $output;
