<?php
session_start();
include("connection/config.php");

// Secure input to prevent SQL Injection
$disease_name = mysqli_real_escape_string($conn, $_GET['disease_name'] ?? '');

// Fetch data from the database
$query = "SELECT * FROM disease_information WHERE disease_name = '{$disease_name}'";
$data = mysqli_query($conn, $query);

if ($data) {
    $result = mysqli_fetch_assoc($data);
    if (!$result) {
        echo "No data found.";
        exit;
    }
} else {
    echo "Error fetching data: " . mysqli_error($conn);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title><?php echo htmlspecialchars($result['disease_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

       /* Header styling */
        header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: darkblue;
            color: white;
            padding: 10px;
        }   

        /* Home icon styling */
        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 15px;
        }

        /* Disease name (centered) */
        .header-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }


        .container {
            padding: 10px;
        }

        .info-sections {
            margin-bottom: 30px;
        }

        .section {
            margin-top: 20px;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0px 3px rgba(0, 0, 0, 0.7);
        }

        h2 {
            font-size: 24px;
            color: #004080;
            text-align: center;
        }

        p {
            margin-left: 13px;
        }

        .media {
            display: flex;
            justify-content: space-evenly;
        }

        .media img {
            width: 500px;
            height: 400px;
            border-radius: 5px;
            box-shadow: 0 0px 3px rgba(0, 0, 0, 0.7);
        }

        #videos {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        #videos iframe {
            flex: 1 1 320px;
            max-width: 360px;
            width: 100%;
            height: 260px;
            border-radius: 15px;
            box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.7);
            border: 0;
        }

        .box_title h2 {
            font-size: 2rem;
            font-weight: 700;
            color: darkblue;
        }

        .r_one{
            border: 1px solid #ccc;
            border-radius: 10px ;
            max-width: inherit;
            box-shadow: 0 0px 3px rgba(0, 0, 0, 0.7);
        }

        .one_section {
            /* margin-top: 20px; */
            background: white;
            padding: 20px;
            /* border: 1px solid #ccc; */
            /* border-radius: 10px;
            box-shadow: 0 0px 3px rgba(0, 0, 0, 0.7); */
        }
        .one_section h2 {
            font-size: 2rem;
            font-weight: 700;
            color: darkblue;
        }

        .sym{
            border-right: 2px solid #ccc;
        }
        /* Style Google Translate Dropdown */
        #google_translate_element {
            display: flex;
            align-items: center;
            height: 65px;
        }

        /* Style the Google Translate frame */
        .goog-te-gadget {
            color: white !important;
            font-size: 16px;
            display: flex;
            align-items: center;
            margin-top: 3px;
        }

        /* Hide the "powered by Google" text */
        .goog-te-gadget span {
            display: none;
        }

        /* Style the dropdown button */
        .goog-te-combo {
    background-color: white;
    color: black !important;
    width: 180px;
    border: 2px solid white;
    border-radius: 5px;
    padding: 5px;
    font-weight: 500;
    cursor: pointer;
    text-align: center;

        }

        /* Change dropdown color on hover */
        .goog-te-combo:hover {
        background-color: rgba(255, 255, 255, 0.2);
        }

        /* Style the translate toolbar */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        .goog-te-combo option {
            color: black !important;
            background-color: white !important;
            font-size: 16px;
            padding: 5px;
            text-align: left; /* Align text to the left */
        }

        .goog-te-gadget span,
        .goog-te-gadget img {
            display: none !important;
        }

        /* Hide the Google Translate toolbar */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        /* Style Google Translate Dropdown */
        #google_translate_element {
            display: flex;
            align-items: center;
            height: 65px;
        }

        /* Hide all text elements in the Google Translate gadget except the dropdown */
        .goog-te-gadget {
            color: transparent !important;
            font-size: 0 !important;
        }

        /* Ensure the select box remains visible */
        .goog-te-combo {
            background-color: darkblue;
            color: white !important;
            width: 180px;
            border: 2px solid white;
            border-radius: 5px;
            padding: 5px;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
        }       


        /* Completely hide the Google Translate footer */
        .goog-te-footer {
            display: none !important;
        }

        /* Hide any remaining logos or links */
        .goog-logo-link,
        .goog-te-gadget img {
            display: none !important;
            visibility: hidden !important;
        }
        #google_translate_element select {
            padding: 6px;
            font-size: 14px;
            border-radius: 5px;
            outline: none;
         }
         
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <header>
        <!-- Left side: Home icon -->
        <div class="header-left">
        <a href="<?php $localhost ?>homepage.php">
        <i class="fa-sharp fa-solid fa-house fa-2xl" style="color: #ffffff;"></i>
        </a>
        </div>

        <!-- Center: Disease Name -->
        <h1 class="header-center"><?php echo htmlspecialchars($result['disease_name']); ?></h1>

        <!-- Right side: Language Selector -->
        <div id="google_translate_element"></div>
    </header>

    <div class="container">
        <div class="info-sections">
            <!-- Overview Section -->
            <div class="row">
                <div class="col">
                    <div class="section" id="overview">
                        <div class="box_title">
                            <h2 class="mb-3">Overview</h2>
                            <hr>
                        </div>
                        <p><?php echo htmlspecialchars($result['overview']); ?></p>
                    </div>
                </div>

            </div>


        </div>

        <!-- Videos Section -->
        <?php
        if (!empty($result['videos'])) {
            $video_links = json_decode($result['videos'], true);
            $video_links = array_values(array_filter(array_map('trim', is_array($video_links) ? $video_links : [])));
            if (!empty($video_links)) {
                echo '<div class="section mb-4">
                        <div class="box_title">
                            <h2>Videos</h2>
                            <hr>
                        </div>
                    <div class="row" id="videos">';
                foreach ($video_links as $link) {
                    echo '<iframe src="' . htmlspecialchars($link) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                }
                echo '</div></div>';
            }
        }
        ?>
        <div class="row r_one gx-3">
            <div class="col-6  one_section sym "> <!-- Symptoms Section -->


                <h2 class="mb-3">Symptoms</h2>
                <hr>
                <p><?php echo nl2br(htmlspecialchars($result['symptoms'])); ?></p>

            </div>
            <div class="col-6 one_section"> <!-- Causes section -->


                <h2 class="mb-3">Causes</h2>
                <hr>

                <p><?php echo nl2br(htmlspecialchars($result['causes'])); ?></p>

            </div>
        </div>



        <!-- Infographics Section -->
        <?php
        if (!empty($result['infographics'])) {
            $image_paths = json_decode($result['infographics'], true);
            if (!empty($image_paths)) {
                echo '<div class="section">
                        <div class="box_title">
                            <h2>Infographics</h2>
                            <hr>
                        </div>
                        <div class="media mt-3">';
                foreach ($image_paths as $img) {
                    echo '<img src="uploads/Diseases_img/' . $disease_name . '/' . htmlspecialchars($img) . '" alt="Disease Image" class="m-2">';
                }
                echo '</div></div>';
            }
        }
        ?>
    </div>

    

    <script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({ pageLanguage: 'en' }, 'google_translate_element');
    }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>

</html>
