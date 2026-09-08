<?php
session_start();
// Include the database connection
include("connection/config.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disease Information</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
    <style>
        /* Centralized CSS (reusable styles) */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f9faff;
            color: #333;
        }
        .organs {
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            border: 1px solid black;
            height: 300px;
            background-color: lightblue;
            box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.7)
        }
        .organ_image img {
            max-width: 15%;
            height: 15%;
        }
        .topic-list {
            background-color: #f6f7f9;
            padding: 8px 16px;
            font-size: 22px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            border-radius: 5px;
            position: relative;
        }
        .info-button {
            height: 40px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 10px;
        }
        .no-results {
            color: #999;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="organs">
        <div class="container main_list">
            <h2 style="width:100%; text-align:center; margin-bottom:25px">Get the disease list with the help of the organs</h2>

            <!-- Organ Image -->
            <div class="organ_image text-center">
                <img src="include/Organ_image/<?= htmlspecialchars($_GET['organ_name'] ?? 'default') ?>.png" alt="Organ Image">
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <ul class="list-unstyled">
            <?php
            $organ_name = $_GET['organ_name'] ?? '';
            if (!empty($organ_name)) {
                $query = "SELECT * FROM disease_information WHERE organ_name = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $organ_name);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '
                        <li class="topic-list">
                            <div class="">
                                <span class="disease-name fw-bold">' . htmlspecialchars($row['disease_name']) . '</span>
                                <button class="btn btn-primary info-button float-end"
                                    onclick="window.location.href=\'disease_info_template.php?disease_name=' . urlencode($row['disease_name']) . '\'">
                                    More Info
                                </button>
                            </div>
                        </li>';
                    }
                } else {
                    echo "<p class='no-results'>No diseases found for the selected organ.</p>";
                }
                $stmt->close();
            } else {
                echo "<p class='no-results'>Please select an organ to display diseases.</p>";
            }
            ?>
        </ul>
    </div>
</body>
</html>
