<?php
session_start();
include("../../connection/config.php");

$sql = "SELECT * FROM communities";
$result = mysqli_query($conn, $sql);
$sr_no = 1;

$output = '<div class="container mt-4" style="height:100vh;">
<button type="button" class="btn btn-success m-3" onclick="window.location.href=\'add_community.php\'">Add Community</button>
<div class="container mt-2">
<table class="table table-striped">
<thead class="table-dark">
  <tr>
    <th scope="col-lg-1">Sr No.</th>
    <th scope="col-lg-3">Community Name</th>
    <th scope="col-lg-5">Description</th>
    <th scope="col-lg-3">Operation</th>
  </tr>
</thead>
<tbody>';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $output .= '<tr>
            <td>' . $sr_no . '</td>
            <td>' . $row['name'] . '</td>
            <td>' . $row['description'] . '</td>
            
            <td>
                <button type="button" class="btn btn-warning mb-1" onclick="window.location.href=\'updateCommunityForm.php?community_id=' . $row['community_id'] . '\'">Edit</button>
                <button type="button" class="btn btn-danger community_delete_btn" data-id="' . $row['community_id'] . '">Delete</button>
            </td>
        </tr>';
        $sr_no++;
    }
    $output .= '</tbody></table></div>';
} else {
    $output .= '<p class="mt-4 text-danger">No communities found.</p>';
}

echo $output;
?>
