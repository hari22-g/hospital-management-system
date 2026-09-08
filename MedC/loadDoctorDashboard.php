<?php
session_start();
include("connection/config.php");

$doctor_id = $_SESSION['user_id']; // Doctor ID from session
$sql_total_app = "SELECT * FROM appointment WHERE did = '$doctor_id'";
$result_total_app = mysqli_query($conn, $sql_total_app);
$count_total_app = mysqli_num_rows(mysqli_query($conn, $sql_total_app));

$sql_pen_app = "SELECT * FROM appointment WHERE status = 'Pending' AND did = '$doctor_id'";
$result_pen_app = mysqli_query($conn, $sql_pen_app);
$count_pen_app = mysqli_num_rows(mysqli_query($conn, $sql_pen_app));

$sql_approved_doc = "SELECT * FROM appointment WHERE status = 'Confirmed' AND did = '$doctor_id'";
$result_approved_doc = mysqli_query($conn, $sql_approved_doc);
$count_approved_doc = mysqli_num_rows(mysqli_query($conn, $sql_approved_doc));

$sql_today_app = "SELECT * FROM appointment WHERE (DATE(appointment_date) = CURDATE() AND did = '$doctor_id') AND status = 'Confirmed';";
$result_today_app = mysqli_query($conn, $sql_today_app);
$count_today_app = mysqli_num_rows(mysqli_query($conn, $sql_today_app));

$sql_tommorow_app = "SELECT * FROM appointment WHERE (DATE(appointment_date) = (CURDATE() + 1) AND did = '$doctor_id') AND status = 'Confirmed';";
$result_tommorow_app = mysqli_query($conn, $sql_tommorow_app);
$count_tommorow_app = mysqli_num_rows(mysqli_query($conn, $sql_tommorow_app));


echo ' <div class="container mt-4" style="height:100vh;">
                    <!-- First Row with 3 Divs -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="custom-box box1 bg-light"><span class="text-dark">Total appointments</span><br>' . $count_total_app . '</div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-box box2 bg-light"><span class="text-dark">Total pending appointments</span><br>' . $count_pen_app . '</div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-box box3 bg-light"><span class="text-dark">Total approved appointments</span><br>' . $count_approved_doc . '</div>
                        </div>
                    </div>

                    <!-- Second Row with 2 Divs -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-box box4 bg-light"><span class="text-dark">Today\'s appointments</span><br>' . $count_today_app . '</div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-box box5 bg-light"><span class="text-dark">Tommorow\'s appointments</span><br>' . $count_tommorow_app . '</div>
                        </div>
                    </div>
                </div>';
