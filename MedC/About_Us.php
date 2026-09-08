<?php
session_start();
// Include the database connection
include("connection/config.php");
$icr_count = "UPDATE visit_counter SET counter = counter + 1";
$icr_result = mysqli_query($conn, $icr_count);
$counter_query = "SELECT counter FROM visit_counter";
$counter_result = mysqli_query($conn, $counter_query);
$row_count = mysqli_fetch_assoc($counter_result);
$count = $row_count["counter"];
?>
<!doctype html>
<html lang="en">

<head>
    <title>About Us</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
</head>

<body>
    <?php
    include("include/navbar.php") ?>
    <main>
        <!-- Main About Us Section -->
        <section class="container py-5">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="fw-bold">About Us</h2>
                    <p class="text-muted">
                        Welcome to MedC, where innovation meets accessibility in healthcare. We are dedicated to
                        developing IT solutions that make healthcare information accessible, transparent, and
                        user-friendly for diverse patient populations.

                    </p>
                    <p class="text-muted">
                        Our platform is designed to bridge the communication gap in healthcare by offering multilingual
                        support, an intuitive health dashboard, and telehealth services. Patients can easily track their
                        health metrics, book online consultations, and access verified community support—all in one
                        place.
                    </p>

                    <p class="text-muted">
                        At MedC, we believe that everyone deserves access to simplified healthcare information,
                        regardless of language or background. By leveraging cutting-edge technology, we aim to transform
                        the healthcare experience into a more inclusive, efficient, and patient-centered journey.
                    </p>
                    <p class="text-muted">
                        Join us in making healthcare more accessible for all!
                    </p>

                </div>
                <div class="col-md-6">
                    <img src="<?php echo $localhost . 'include/homepage_slider/We.jpeg'?>" alt="About Us Image" class="img-fluid rounded shadow">
                </div>
            </div>
        </section>

    </main>
    <footer style="position:absolute;">
        <!-- place footer here -->
        <?php include("include/footer.php") ?>
    </footer>
    <!-- Bootstrap JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
        crossorigin="anonymous"></script>
</body>

</html>