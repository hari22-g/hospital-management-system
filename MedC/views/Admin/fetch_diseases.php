<?php
session_start();
include("../../connection/config.php");

$sql = "SELECT * FROM disease_information";
$result = mysqli_query($conn, $sql);
$sr_no = 1;

$output = '<button type="button" class="btn btn-success m-3 add-disease-btn" onclick="window.location.href=\'' . $localhost . 'views/Admin/addDiseaseForm.php\'">Add Disease</button>
<div class="container mt-2">
<table class="table table-striped">
<thead class="table-dark">
  <tr>
    <th scope="col">Sr No.</th>
    <th scope="col">Disease Name</th>
    <th scope="col">Related Organ</th>
    <th scope="col">Operation</th>
  </tr>
</thead>
<tbody>';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $output .= '<tr>
            <td>' . $sr_no . '</td>
            <td>' . $row['disease_name'] . '</td>
            <td>' . $row['organ_name'] . '</td>
            <td>
                <button type="button" class="btn btn-warning" onclick="window.location.href=\'updateDiseaseForm.php?disease_id=' . $row['disease_id'] . '\'">Edit</button>
                <button type="button" class="btn btn-danger delete-btn" data-id="' . $row['disease_id'] . '">Delete</button>
            </td>
        </tr>';
        $sr_no++;
    }
    $output .= '</tbody></table></div>';
} else {
    $output .= '<p class="mt-4 text-danger">No diseases found.</p>';
}

echo $output;
?>
