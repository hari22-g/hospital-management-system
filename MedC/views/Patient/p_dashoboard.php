<?php
session_start();
include("../../connection/config.php");
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../login.php');
    exit();
}

$userType = strtolower(trim((string) ($_SESSION['user_type'] ?? '')));
if ($userType !== 'patient') {
    header('Location: ../../include/dashboard_link.php');
    exit();
}

$user_id =  $_SESSION['user_id'];
$email =  $_SESSION['email'];
$sql = "SELECT * FROM patient WHERE email = '$email'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);
$dob = $row["dob"];
$age = date_diff(date_create($dob), date_create('today'))->y;

$drug_sql = "SELECT * FROM medications WHERE user_id = '$user_id'";
$drug_result = mysqli_query($conn, $drug_sql);

$output = "";
if ($result = mysqli_query($conn, $sql)) {
    if ($row = mysqli_fetch_array($result)) {
        $output = '<div class="container mt-2">
                    <div class="dashboard">
                        <!-- Horizontal Patient Profile -->
                        <div class="row pt-2">
                            <!-- Patient Profile Card -->
                            <div class="col-md-4 ">
                                <div class="card card-custom p-3 text-center profile">
                                    <div class="row">
                                        <div class="col">
                                            <img src="'. $localhost . 'views/Patient/profile.jpg" class="profile-img rounded-circle" alt="Patient Image" style="width: 110px; height: 110px;">
                                        </div>
                                        <div class="col">
                                            <h5 class="mt-2">' . $row["firstname"] . ' ' . $row["lastname"] . '</h5>
                                            <p class="text-muted">' . $row["gender"] . ' , ' . $age . ' Years </p>
                                            <p><i class="fas fa-map-marker-alt"></i> ' . $row["state"] . ', ' . $row["country"] . '</p>
                                            <!-- <button class="btn btn-primary btn-sm">Edit Profile</button> -->
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Health Summary -->
                            <div class="col-md-8">
                                <div class="card card-custom p-3 side">

                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><strong>Blood Type:</strong> ' . $row["bloodgroup"] . ' </li>
                                        <li class="list-group-item"><strong>Height:</strong> ' . $row["height"] . 'cm</li>
                                        <li class="list-group-item"><strong>Weight:</strong>    ' . $row["weight"] . 'kg</li>
                                    </ul>
                                </div>
                            </div>
                        </div>


                        <!-- Suggested Articles -->
                        <div class="container art">
                            <div class="section">
                                <h3 style="color: darkblue;">Recommended for you</h3>
                                <div class="articles">
                                    <div class="article">
                                        <a href="https://www.healthline.com/nutrition/best-diet-for-heart-health">
                                            <img
                                                src="https://media.post.rvohealth.io/wp-content/uploads/2025/01/man-tossing-salad-lettuce-spinach-green-vegetables-732x549-thumbnail.jpg">
                                        </a>
                                        <h3>How to Eat More Vegetables for a Healthy Heart</h3>
                                        <p>Here are several ways to add more veggies to your daily meals.</p>

                                    </div>
                                    <div class="article">
                                        <a href="https://www.healthline.com/nutrition/best-diet-for-heart-health">
                                            <img src="https://media.post.rvohealth.io/wp-content/uploads/2020/10/hummus-healthy-food-vegetarian-732x549-thumbnail.jpg"
                                                alt="Healthy Diet">
                                        </a>
                                        <h3>The 5 Best Diets for Heart Health</h3>
                                        <p>Learn how these heart-healthy diets stack up.</p>
                                    </div>



                                    <div class="article">
                                        <a href="https://www.healthline.com/nutrition/best-diet-for-heart-health">
                                            <img src="https://media.post.rvohealth.io/wp-content/uploads/2021/07/1411926-20-Best-Skin-Care-Products-for-All-Skin-Types-in-2021-732x549-Feature.jpg"
                                                alt="Skin Care">
                                        </a>
                                        <h3>16 Best Skin Care Products for All Skin Types 2025</h3>
                                        <p>If you want to update your winter skin care, here are 16 products to
                                            consider.</p>
                                    </div>
                                    <div class="article">
                                        <a href="https://www.healthline.com/nutrition/best-diet-for-heart-health"></a>
                                        <img src="https://media.post.rvohealth.io/wp-content/uploads/2025/01/man-at-home-sick-drinking-liquid-on-couch-with-cold-medicine-732x549-thumbnail.jpg"
                                            alt="Caffeine and Medicine">
                                        </a>
                                        <h3>Can You Drink Caffeine with Cold Medicine?</h3>
                                        <p>Skipping caffeine when taking cold medicine is often recommended. Learn why.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Drug Information Table -->
                        <div class="table-container">
                            <h2 style="color: darkblue;">Used Drugs</h2>
                            <button class="btn btn-success" id="add_drug" data-bs-toggle="modal" data-bs-target="#drug_add">Add</button>

                            <!-- Drug Modal -->
                            <div class="modal fade" id="drug_add" tabindex="-1" aria-labelledby="drug_add_label" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="drug_add_label">Add Drug</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="drug_form">
                                                <div class="mb-3">
                                                    <label class="form-label">Brand Name</label>
                                                    <input type="text" class="form-control" id="brand_name" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Generic Name</label>
                                                    <input type="text" class="form-control" id="generic_name" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Strength</label>
                                                    <input type="text" class="form-control" id="strength" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Form</label>
                                                    <input type="text" class="form-control" id="form" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Duration</label>
                                                    <input type="text" class="form-control" id="duration" required>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-primary" id="submit_drug">Add Drug</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Drug table -->

                            <table id="drugTable">
                                <thead>
                                    <tr class="drug">
                                        <th class="drugth">Brand Name</th>
                                        <th class="drugth">Generic Name</th>
                                        <th class="drugth">Strength</th>
                                        <th class="drugth">Form</th>
                                        <th class="drugth">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>';
        if ($drug_result) {
            while ($drug_row = mysqli_fetch_assoc($drug_result)) {
                $output .= '<tr class="drug">
                                        <td class="drugtd">' . $drug_row["brand_name"] . '</td>
                                        <td class="drugtd">' . $drug_row["generic_name"] . '</td>
                                        <td class="drugtd">' . $drug_row["strength"] . '</td>
                                        <td class="drugtd">' . $drug_row["form"] . '</td>
                                        <td class="drugtd">' . $drug_row["duration"] . '</td>
                                    </tr>';
            }
        } else {
            $output .= 'No data found of your medication';
        }

        $output .= '</tbody>
                            </table>
                        </div>
                        <div class="container feature-section">
                            <h1 class="text-center mb-5 mt-4" style="color: darkblue;">Discover More Features</h1>
                            <div class="row justify-content-center">
                                <!-- Community Feature Card -->
                                <div class="col-md-5 mb-4">
                                    <div class="feature-card">
                                        <div class="feature-icon">
                                            <i class="bi bi-people-fill"></i>
                                        </div>
                                        <h2 class="feature-title">Community</h2>
                                        <p class="feature-text">
                                            Connect with patients, doctors, and health enthusiasts. Share experiences
                                            and engage in meaningful discussions.
                                        </p>
                                    </div>
                                </div>

                                <!-- Health Information Feature Card -->
                                <div class="col-md-5 mb-4">
                                    <div class="feature-card">
                                        <div class="feature-icon">
                                            <i class="bi bi-journal-medical"></i>
                                        </div>
                                        <h2 class="feature-title">Health Information</h2>
                                        <p class="feature-text">
                                            Access reliable health resources, symptom guides, and preventive care tips
                                            to stay informed and healthy.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
    } else {
        $output = "No data found";
    }
} else {
    $output = "error_sql : " . mysqli_error($conn);
}
echo $output;
