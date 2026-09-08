<?php
session_start();
include('../connection/config.php');  // Include your database connection
include('../include/community_image_helper.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Slider</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #EFF3EA;
            font-family: 'Arial', sans-serif;
        }

        /* Grid Card Layout */
        .slider {
            display: flex;
            justify-content: center;
            padding: 40px;
        }

        .card-wrapper {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 1200px;
            width: 100%;
        }

        .card-item {
            background: #ffffff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            min-height: 320px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 20px rgba(0, 0, 0, 0.2);
        }

        .card-item img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .card-item h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 5px;
        }

        .card-item p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 55px;
            /* Space for button */
        }

        /* Fixed "Explore" Button */
        .explore_now-btn {
            background-color: darkblue;
            height: 45px;
            width: 70%;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 20px;
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            transition: background-color 0.5s;
        }

        .explore_now-btn:hover {
            background-color: black;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .card-wrapper {
                grid-template-columns: repeat(2, 1fr);
                /* 2 columns on medium screens */
            }
        }

        @media (max-width: 768px) {
            .card-wrapper {
                grid-template-columns: 1fr;
                /* 1 column on small screens */
            }

            .card-item h3 {
                font-size: 1rem;
                /* Adjust font size for smaller screens */
            }

            .card-item p {
                font-size: 0.8rem;
                /* Adjust paragraph font size for smaller screens */
            }

            .explore_now-btn {
                font-size: 18px;
                /* Adjust button font size */
                width: 80%;
                /* Make the button more responsive */
            }
        }
    </style>
</head>

<body>
    <?php
    include("../include/navbar.php");
    ?>
    <div class="slider">
        <div class="card-wrapper">
            <?php
            $sql = "SELECT * FROM communities";
            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_array($result)) {
                $c_id = $row["community_id"];
                $communityImageSources = medc_get_community_image_sources($row, $localhost);
                $communityImageSrc = htmlspecialchars($communityImageSources[0], ENT_QUOTES, 'UTF-8');
                $communityFallbackSources = htmlspecialchars(json_encode(array_slice($communityImageSources, 1), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                $communityName = htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8');
                $communityDescription = htmlspecialchars($row["description"], ENT_QUOTES, 'UTF-8');
                echo '<div class="card-item">
                <img src="' . $communityImageSrc . '" class="rounded-circle" alt="' . $communityName . '" style="object-fit: contain;"
                    data-fallback-sources="' . $communityFallbackSources . '"
                    onerror="const sources = JSON.parse(this.dataset.fallbackSources || \'[]\'); const nextSource = sources.shift(); this.dataset.fallbackSources = JSON.stringify(sources); if (nextSource) { this.src = nextSource; } else { this.onerror = null; }">
                <h3>' . $communityName . '</h3>
                <p>' . $communityDescription . '</p>
                <button class="explore_now-btn" onclick="window.location.href=\'' . $localhost . 'Community/community_post.php?c_id=' . $c_id . '\'">Explore Now</button>
            </div>';
            }
            ?>

        </div>
    </div>
</body>

</html>
