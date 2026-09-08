<?php
session_start();
$user_id = $_SESSION["user_id"];
include("../../connection/config.php");
$sql = "SELECT * FROM reports WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $sql);
$output = '<div class = "container mt-5">
    <button type="button" class="btn btn-success mb-3 " onclick="window.location.href=\'reportForm.php\'">Add new report</button>
    <table class="table">
    <thead>
    <tr>
      <th scope="col">Report Type</th>
      <th scope="col">Referred By</th>
      <th scope="col">Date</th>
      <th scope="col">Document</th>
      </tr>
      </thead>';
if (mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    $output .= '<tbody>
    <tr>
      <td>' . $row["report_type"] . '</td>
      <td>' . $row["referred_by"] . '</td>
      <td>' . $row["report_date"] . '</td>
      <td><p class = "fs-5"><a href="' . $localhost . 'uploads/Patient_Reports/' . $row["file_path"] . '" class="link-success link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">View</a></p></td>
    </tr>';
  }
  $output .= '</tbody></table></div>';
}
echo $output;
?>
<!-- 
<table class="table">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">First</th>
      <th scope="col">Last</th>
      <th scope="col">Handle</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">1</th>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
    </tr>
    <tr>
      <th scope="row">2</th>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
    </tr>
    <tr>
      <th scope="row">3</th>
      <td colspan="2">Larry the Bird</td>
      <td>@twitter</td>
    </tr>
  </tbody>
</table> -->