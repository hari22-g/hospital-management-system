<?php
session_start();
// Include the database connection
include("connection/config.php");
include("include/community_image_helper.php");
$icr_count = "UPDATE visit_counter SET counter = counter + 1";
$icr_result = mysqli_query($conn, $icr_count);
$counter_query = "SELECT counter FROM visit_counter";
$counter_result = mysqli_query($conn, $counter_query);
$row_count = mysqli_fetch_assoc($counter_result);
$count = $row_count["counter"];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="./images/https://kit.fontawesome.com/933631f95f.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>MedC</title>
    
    <style>
        .card {
            display: inline-block;
        }

        .ltext {
            display: flex;
            justify-content: center;
        }

        .loJoin-text {
            margin-left: 8px;
            color: rgb(186, 17, 17);
        }

        .loJoin {
            border: 2px solid rgb(255, 255, 255);
            border-radius: 40%;
            background-image: url("LOJoin.jpeg");
            background-repeat: no-repeat;
            background-size: contain;
            height: 54px;
            width: 54px;
        }

        .class {

            background-color: darkblue;
        }

        header {
            background-color: #e0f7ff;
            /* padding: 40px 20px; */
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transform: none !important;
            animation: none !important;
            perspective: none !important;
        }

        header h1 {
            color: #004aad;
        }

        .explore-section,
        .login-section {
            padding: 20px 0px;
            margin: 20px 0px;
            max-width: 100%;
            background-color: white;
            border-radius: 0px;
            /* box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); */
            transform: none !important;
            animation: none !important;
            perspective: none !important;
        }

        .bpart_hover {
            max-width: 190px;
            margin-top: 10px;
            margin-right: 10px;
            margin-bottom: 52px;
            cursor: pointer;
            transition: transform 0.3s ease;
            border-radius: 50%;
            padding: 50px;
            display: inline-block;
        }

        .bpart_hover:hover {
            transform: scale(1.1);

        }

        .explore-section {
            text-align: center;
        }

        .login-section {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .login-box {
            width: 45%;
            padding: 20px;
            text-align: center;
            background-color: #f1f9ff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .login-box:hover {
            background-color: #d6ebf9;

        }

        .login-box h3 {
            margin-bottom: 15px;
            color: #004aad;
        }

        .login-box a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #004aad;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .login-box a:hover {
            background-color: #00337a;
        }

        h5 {
            color: #d6ebf9;
            padding-top: 10px;
        }

        bpart_hover:hover {
            box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.7);
        }

        .bpart-container {
            display: inline-block;
            text-align: center;
            margin: 10px;
            margin-bottom: -50px;
        }

        .bpart-label {
            font-size: 20px;
            font-weight: bold;
            color: #333;

            margin-top: -89px;
        }

        .containerr {
            display: flex;
            overflow-x: auto;
            padding: 20px 0px;
            gap: 20px;

        }


        .card-container {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-top: 0;
            perspective: 1000px;
            /* Enables the 3D effect */
        }

        .custom-card {
            width: 300px;
            height: 400px;
            transition: transform 0.8s;
            transform-style: preserve-3d;
            /* Ensures 3D rotation works */
            position: relative;
        }

        .card-container:hover .custom-card {
            transform: rotateY(180deg);
        }

        .card-front,
        .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            /* Hides the back when the front is visible */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .card-front {
            background-color: #ffffff;
        }

        .card-back {
            background-color: #E8F9FF;
            transform: rotateY(180deg);
        }

        .card-img-top-doc {
            width: 140px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
        }

        .maincointainer {
            display: flex;
            gap: 10px;
            padding: 15px 0px;
            overflow: hidden;
            background: #f8f9fa;
            border-radius: 0px;
            position: relative;
            z-index: 1;
            height: auto;
            width: 100%;
            margin-top: 20px;
            flex-wrap: nowrap;
            justify-content: flex-start;
        }

        .doctor-slider-track {
            display: flex;
            gap: 10px;
            width: max-content;
            animation: doctorSlider 45s linear infinite;
        }

        .maincointainer:hover .doctor-slider-track {
            animation-play-state: paused;
        }

        .doctor-card-wrapper {
            margin: 0px;
            flex: 0 0 calc(16.666% - 10px);
            min-width: 200px;
            max-width: 220px;
        }

        @keyframes doctorSlider {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        /* @keyframes scrollRight {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-4480px);
            }
        } */

        .name {
            font-size: 14px;
            display: flex;
            font-weight: 700;
        }

        .name_one {
            margin: bottom -5px;
            font-weight: 500;

        }

        /* .card-footer-icons a {
            margin: 0 10px;
            text-decoration: none;
            color: #007bff;
        }
        */

        .card-footer-icons a:hover {
            color: #0056b3;
        }

        .hospital-gallery-section {
            padding: 48px 0 20px;
            background: linear-gradient(180deg, #ffffff 0%, #eef7ff 100%);
        }

        .gallery-shell {
            padding: clamp(24px, 4vw, 42px);
            border-radius: 32px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(231, 243, 255, 0.95) 100%);
            box-shadow: 0 24px 60px rgba(0, 74, 173, 0.12);
        }

        .gallery-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 28px;
        }

        .gallery-copy {
            max-width: 700px;
        }

        .gallery-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(0, 74, 173, 0.08);
            color: #004aad;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .gallery-title {
            margin: 0 0 12px;
            color: #073a7a;
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 700;
        }

        .gallery-description {
            margin: 0;
            color: #506a8a;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .gallery-badge {
            min-width: 220px;
            padding: 18px 22px;
            border-radius: 22px;
            background: linear-gradient(135deg, #dff0ff 0%, #ffffff 100%);
            border: 1px solid rgba(0, 74, 173, 0.08);
            box-shadow: 0 14px 30px rgba(0, 74, 173, 0.08);
        }

        .gallery-stat-label,
        .gallery-stat-note {
            display: block;
        }

        .gallery-stat-label {
            color: #5a7597;
            font-size: 0.92rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .gallery-stat-value {
            display: block;
            margin: 6px 0;
            color: #004aad;
            font-size: clamp(2rem, 3vw, 2.6rem);
            line-height: 1;
        }

        .gallery-stat-note {
            color: #6b7f99;
            font-size: 0.95rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.9fr 0.9fr;
            gap: 18px;
        }

        .gallery-card {
            position: relative;
            min-height: 220px;
            border-radius: 26px;
            overflow: hidden;
            background: #dfeffc;
            box-shadow: 0 16px 40px rgba(4, 44, 110, 0.14);
            isolation: isolate;
        }

        .gallery-card--feature {
            grid-row: span 2;
            min-height: 458px;
        }

        .gallery-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(8, 30, 64, 0.08) 0%, rgba(4, 27, 59, 0.82) 100%);
        }

        .gallery-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.45s ease, filter 0.45s ease;
        }

        .gallery-card:hover img {
            transform: scale(1.05);
            filter: saturate(1.08);
        }

        .gallery-card-content {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
            padding: 24px;
            color: #ffffff;
        }

        .gallery-card-content h3 {
            margin: 0 0 8px;
            font-size: 1.42rem;
            font-weight: 700;
        }

        .gallery-card-content p {
            margin: 0;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }

        .gallery-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            backdrop-filter: blur(6px);
        }

        .gallery-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.28);
        }

        @media (max-width: 992px) {
            .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gallery-card--feature {
                grid-column: span 2;
                grid-row: auto;
                min-height: 380px;
            }
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .hospital-gallery-section {
                padding: 34px 0 10px;
            }

            .gallery-shell {
                border-radius: 24px;
                padding: 20px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-card,
            .gallery-card--feature {
                min-height: 280px;
            }

            .gallery-card--feature {
                grid-column: auto;
            }

            .gallery-card-content {
                padding: 18px;
            }

            .bpart_hover {
                max-width: 120px;
                padding: 30px;
                margin: 10px;
            }
            
            .login-section {
                flex-direction: column;
            }
            
            .login-box {
                width: 90%;
                margin: 10px 0;
            }
            
            .custom-card {
                width: 100%;
                margin: 10px 0;
            }
            
            #slide {
                height: 300px !important;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .nav-link {
                padding: 0.5rem 0.8rem !important;
            }
            
            .explore-section h2, .explore-section p {
                padding: 0 10px;
            }
        }
        
        @media (max-width: 576px) {
            .bpart_hover {
                max-width: 100px;
                padding: 20px;
            }
            
            .bpart-container {
                margin: 5px;
            }
            
            #slide {
                height: 200px !important;
            }
            
            .hello {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .custom-card {
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
    <script>
        // Slider animation disabled
        // var i = 0;
        // var images = [];
        // var time = 1400;

        // // Image list
        // images[0] = 'include/homepage_slider/arogya_logo.png';
        // images[1] = 'include/homepage_slider/Picsart_25-02-15_01-17-09-154.jpg';
        // images[2] = 'include/homepage_slider/We.jpeg';
        // images[3] = 'include/homepage_slider/img3.jpg'

        // function changeImg() {
        //     document.getElementById('slide').src = images[i];
        //     if (i < images.length - 1) {
        //         i++;
        //     } else {
        //         i = 0;
        //     }
        //     setTimeout(changeImg, time);
        // }

        // window.onload = changeImg;
    </script>
</head>

<body>
    <?php include("include/navbar.php") ?>

    <header>

        <img id="slide" src="include/homepage_slider/img3.jpg" alt="Image Slider" style="width:100%; height:560px;">

    </header>
    <section class="hospital-gallery-section">
        <div class="container">
            <div class="gallery-shell">
                <div class="gallery-header">
                    <div class="gallery-copy">
                        <span class="gallery-kicker"><i class="fa-solid fa-camera-retro"></i>Inside MedC</span>
                        <h2 class="gallery-title">Hospital Gallery</h2>
                        <p class="gallery-description">Take a quick look at the spaces where our hospital welcomes patients, supports families, and delivers modern care in a calm, trusted environment.</p>
                    </div>
                    <div class="gallery-badge">
                        <span class="gallery-stat-label">Visitors welcomed</span>
                        <strong class="gallery-stat-value"><?php echo number_format((int) $count); ?>+</strong>
                        <span class="gallery-stat-note">People have explored MedC through this portal.</span>
                    </div>
                </div>

                <div class="gallery-grid">
                    <article class="gallery-card gallery-card--feature">
                        <img src="<?php echo $localhost; ?>include/homepage_slider/img3.jpg" alt="Exterior view of the MedC hospital building" onerror="this.onerror=null;this.src='<?php echo $localhost; ?>include/homepage_slider/img5.jpg';">
                        <div class="gallery-card-content">
                            <h3>Welcoming Front Entrance</h3>
                            <p>A bright and easy-to-access entry area designed to make every patient and visitor feel guided from the moment they arrive.</p>
                            <a href="hospital_facilities.php" class="gallery-link">Explore facilities <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </article>

                    <article class="gallery-card">
                        <img src="<?php echo $localhost; ?>include/homepage_slider/emergency_care_banner.svg" alt="24/7 emergency care services banner" onerror="this.onerror=null;this.src='<?php echo $localhost; ?>include/homepage_slider/img3.jpg';">
                        <div class="gallery-card-content">
                            <h3>24/7 Emergency Care</h3>
                            <p>Round-the-clock emergency support with expert response, rapid attention, and dependable hospital readiness.</p>
                        </div>
                    </article>

                    <article class="gallery-card">
                        <img src="<?php echo $localhost; ?>include/homepage_slider/Picsart_25-02-15_01-17-09-154.jpg" alt="Hospital facility and support area" onerror="this.onerror=null;this.src='<?php echo $localhost; ?>include/homepage_slider/img3.jpg';">
                        <div class="gallery-card-content">
                            <h3>Supportive Facilities</h3>
                            <p>Comfortable spaces that help patients and families feel reassured throughout their hospital visit.</p>
                        </div>
                    </article>

                    <article class="gallery-card">
                        <img src="<?php echo $localhost; ?>include/homepage_slider/We.jpeg" alt="Care environment at the hospital" onerror="this.onerror=null;this.src='<?php echo $localhost; ?>include/homepage_slider/img5.jpg';">
                        <div class="gallery-card-content">
                            <h3>Care with Compassion</h3>
                            <p>A welcoming atmosphere where medical expertise and human support come together every day.</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
    <section class="explore-section pt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>
                        Explore by Body Organ</h2>
                    <p>Click on a body part to identify potential health risks and early warning signs.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="doctors_directory.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-calendar-check me-2"></i>Book Appointment
                    </a>
                    <a href="doctors_directory.php" class="btn btn-success btn-lg">
                        <i class="fas fa-user-md me-2"></i>Find Doctors
                    </a>
                </div>
            </div>
        </div>
        <div class="hello">
            <div class="bpart-container">
                <img src="include/Organ_image/Brain.png" class="bpart_hover" alt="Brain" name="brain"
                    onclick="window.location.href='organ_health_info.php?organ_name=Brain'">
                <p class="bpart-label pe-2">Brain</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Kidney.png" class="bpart_hover" alt="Kidney"
                    onclick="window.location.href='organ_health_info.php?organ_name=Kidney'">
                <p class="bpart-label">Kidney</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Heart.png" class="bpart_hover" alt="Heart"
                    onclick="window.location.href='organ_health_info.php?organ_name=Heart'">
                <p class="bpart-label">Heart</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Pancreas.png" class="bpart_hover" alt="Pancreas"
                    onclick="window.location.href='organ_health_info.php?organ_name=Pancreas'">
                <p class="bpart-label pe-4">Pancreas</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Stomach.png" class="bpart_hover" alt="Heart"
                    onclick="window.location.href='organ_health_info.php?organ_name=Stomach'">
                <p class="bpart-label pe-2">Stomach</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Intestine.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Intestine'">
                <p class="bpart-label pe-2">Intestine</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Bladder.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Bladder'">
                <p class="bpart-label pe-1">Bladder</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Eye.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Eye'">
                <p class="bpart-label pe-2">Eye</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Lungs.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Lungs'">
                <p class="bpart-label pe-2">Lungs</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/throat.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Throat'">
                <p class="bpart-label">Throat</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Ear.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Ear'">
                <p class="bpart-label pe-4">Ear</p>
            </div>
            <div class="bpart-container">
                <img src="include/Organ_image/Liver.png" class="bpart_hover" alt="liver"
                    onclick="window.location.href='organ_health_info.php?organ_name=Liver'">
                <p class="bpart-label pe-2">Liver</p>
            </div>
        </div>

        </div>
    </section>
    <hr class="mt-5" style="border: 1px solid black;">
    
    <div style="position: relative; z-index: 10;">
        <h2
            style="font-size:35px; color: #212529; font-weight: 600; text-align:center; text-shadow: 1px 1px 1px rgba(132, 17, 17, 0.1); padding-top:40px; margin-bottom: 10px;">
            Consult with trusted doctors</h2>
        <p style="text-align:center; margin-bottom: 30px;">Book appointments easily and get expert advice from top doctors</p>
    </div>

    <div class="maincointainer">
        <div class="doctor-slider-track">
        <?php
        function medc_encode_web_path(string $path): string {
            $segments = array_map('rawurlencode', array_filter(explode('/', str_replace('\\', '/', $path)), 'strlen'));
            return implode('/', $segments);
        }

        function medc_find_homepage_doctor_photo_fallback(array $doctor): ?string {
            static $photoFiles = null;

            if ($photoFiles === null) {
                $photoFiles = [];
                $photoDirectories = [
                    __DIR__ . DIRECTORY_SEPARATOR . 'doctor photos',
                    __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'Doctor_photos'
                ];

                foreach ($photoDirectories as $photoDirectory) {
                    if (!is_dir($photoDirectory)) {
                        continue;
                    }

                    foreach (scandir($photoDirectory) as $fileName) {
                        if ($fileName === '.' || $fileName === '..') {
                            continue;
                        }

                        $filePath = $photoDirectory . DIRECTORY_SEPARATOR . $fileName;
                        if (!is_file($filePath)) {
                            continue;
                        }

                        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'avif'], true)) {
                            $photoFiles[] = [
                                'file' => $fileName,
                                'dir' => basename($photoDirectory)
                            ];
                        }
                    }
                }
            }

            $firstName = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) ($doctor['f_name'] ?? '')));
            $lastName = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) ($doctor['l_name'] ?? '')));
            $fullName = $firstName . $lastName;

            $exactMatches = [];
            $partialMatches = [];
            foreach ($photoFiles as $photoFile) {
                $normalizedFileName = strtolower(preg_replace('/[^a-z0-9]+/', '', pathinfo($photoFile['file'], PATHINFO_FILENAME)));
                if ($fullName !== '' && $normalizedFileName === $fullName) {
                    $exactMatches[] = $photoFile;
                    continue;
                }

                if (
                    ($fullName !== '' && str_contains($normalizedFileName, $fullName)) ||
                    ($firstName !== '' && str_contains($normalizedFileName, $firstName)) ||
                    ($lastName !== '' && str_contains($normalizedFileName, $lastName))
                ) {
                    $partialMatches[] = $photoFile;
                }
            }

            $matchedFile = $exactMatches[0] ?? $partialMatches[0] ?? null;
            if ($matchedFile !== null) {
                return ($matchedFile['dir'] === 'Doctor_photos')
                    ? 'uploads/Doctor_photos/' . $matchedFile['file']
                    : 'doctor photos/' . $matchedFile['file'];
            }

            return null;
        }

        function medc_get_homepage_doctor_photo_url(array $doctor, string $localhost): ?string {
            $fallbackPhoto = medc_find_homepage_doctor_photo_fallback($doctor);
            if ($fallbackPhoto !== null) {
                return $localhost . medc_encode_web_path($fallbackPhoto);
            }

            $photoPath = trim((string) ($doctor['photo_path'] ?? ''));
            if ($photoPath !== '') {
                $absolutePhotoPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoPath);
                if (is_file($absolutePhotoPath)) {
                    $cacheBuster = (string) @filemtime($absolutePhotoPath);
                    return $localhost . medc_encode_web_path($photoPath) . ($cacheBuster !== '' ? '?v=' . rawurlencode($cacheBuster) : '');
                }
            }

            return null;
        }

        $sql = "SELECT * FROM doctor";
        $result = $conn->query($sql);
        $doctorCards = "";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $photoPath = medc_get_homepage_doctor_photo_url($row, $localhost) ?: "https://th.bing.com/th?id=OIP.29VUfn60cwIHwtvm2kzAogHaH6&w=241&h=258&c=8&rs=1&qlt=90&o=6&dpr=1.3&pid=3.1&rm=2";

                $doctorCards .= '<div class="doctor-card-wrapper">
            <div class="card-container">
                <!-- Rotating Card -->
                <div class="custom-card">
                    <!-- Front Side -->
                    <div class="card-front">
                        <img src="' . $photoPath . '"
                            alt="Profile Image" class="card-img-top-doc">
                        <div class="name m-2">
                            <div class="fname me-1">
                                Dr. ' . $row["f_name"] . '
                            </div>
                            <div class="lastname">
                                ' . $row["l_name"] . '
                            </div>
                        </div>
    
                    </div>
                    <!-- Back Side -->
                    <div class="card-back">
                        <div class="name m-2">
                            <div><p class="name_one"></p></div>
                            <div class="fname me-1">
                             ' . $row["f_name"] . '
                            </div>
                            <div class="lastname">
                                ' . $row["l_name"] . '
                            </div>
                        </div>
                        <div class="speciality">
                            ' . $row["specialization"] . '
                        </div>
                        <div class="experience">
                            ' . $row["experience"] . ' years of experience
                        </div>
                        <div class="consoltation">
                           Consultation Fees: <i class="fa-solid fa-indian-rupee-sign me-1"></i>' . $row["consultation_fees"] . ' 
                        </div>
                        <div class="d-grid mt-3">
                            <button class="btn btn-dark book-appointment-btn" type="button" data-doctor-id="' . $row["d_id"] . '" data-doctor-name="Dr. ' . $row["f_name"] . ' ' . $row["l_name"] . '">Book Appointment</button>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>';
            }

            echo $doctorCards . $doctorCards;
        } else {
            echo "<p class=\"ms-3\">0 results</p>";
        }
        ?>
        </div>
    </div>

    <hr class="mt-5" style="border: 1px solid black;">
    <h2
        style="font-size:35px; color:#212529; font-weight: 600; text-shadow: 1px 1px 1px rgba(132, 17, 17, 0.1); padding-top:40px; padding-bottom:20px; text-align: center;">
        Connect with health communities and share your journey</h2>

    <div class="containerr mt-5 mb-5" style="display: flex; justify-content: space-evenly;">
        <?php
        $community_sql = "SELECT * FROM communities LIMIT 4";
        $community_result = $conn->query($community_sql);
        if ($community_result->num_rows > 0) {
            while ($row = $community_result->fetch_assoc()) {
                $c_id = $row["community_id"];
                $communityImageSources = medc_get_community_image_sources($row, $localhost);
                $communityImageSrc = htmlspecialchars($communityImageSources[0], ENT_QUOTES, 'UTF-8');
                $communityFallbackSources = htmlspecialchars(json_encode(array_slice($communityImageSources, 1), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                $communityName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                $communityDescription = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');

                echo '<div class="card" style="width: 18rem;">
            <img src="' . $communityImageSrc . '"
                class="card-img-top" alt="' . $communityName . '" height="178px" style="object-fit: cover;"
                data-fallback-sources="' . $communityFallbackSources . '"
                onerror="const sources = JSON.parse(this.dataset.fallbackSources || \'[]\'); const nextSource = sources.shift(); this.dataset.fallbackSources = JSON.stringify(sources); if (nextSource) { this.src = nextSource; } else { this.onerror = null; }">
            <div class="card-body">
                <h5 class="card-title">' . $communityName .  '</h5>
                <p class="card-text">
                    ' . $communityDescription .  '</p>
                <div class="d-grid">
                    <button class="btn btn-dark" type="button" onclick="window.location.href=\'' . $localhost . 'Community/community_post.php?c_id=' . $c_id . '\'">Explore</button>
                </div>
            </div>
        </div>';
            }
        }
        ?>

        <!-- <div class="card" style="width: 18rem;">
            <img src="https://th.bing.com/th/id/OIP.jbQK-EX6WYNCTiQUoz1o4QHaEn?w=294&h=183&c=7&r=0&o=5&dpr=1.3&pid=1.7"
                class="card-img-top" alt="...">
            <div class="card-body">
                <h5 class="card-title">Diabetes</h5>
                <p class="card-text">
                    Diabetes is a chronic condition where the body struggles to regulate blood sugar due to
                    insufficient insulin or ineffective usage.</p>
                <div class="d-grid">
                    <button class="btn btn-dark" type="button">Explore</button>
                </div>
            </div>
        </div>
        <div class="card" style="width: 18rem;">
            <img src="https://th.bing.com/th/id/OIP.xKv7vsM1tknuBPCZvTLsfgHaD4?w=307&h=180&c=7&r=0&o=5&dpr=1.3&pid=1.7"
                height="178px" class="card-img-top" alt="...">
            <div class="card-body">
                <h5 class="card-title">PCOD</h5>
                <p class="card-text">
                    PCOD (Polycystic Ovarian Disease) is a hormonal disorder causing enlarged ovaries with
                    cysts,
                    leading to irregular periods, weight gain, and infertility.</p>
                <div class="d-grid">
                    <button class="btn btn-dark" type="button">Explore</button>
                </div>
            </div>
        </div>

        <div class="card" style="width: 18rem;">
            <img class="doc_img"
                src="https://th.bing.com/th/id/OIP.MPc4EAzBKvWEqIS8DdGqJQHaHa?w=177&h=180&c=7&r=0&o=5&dpr=1.3&pid=1.7"
                height="178px" class="card-img-top" alt="...">
            <div class="card-body">
                <h5 class="card-title">Migraine</h5>
                <p class="card-text">
                    Migraine is a neurological condition causing intense headaches, often with nausea,
                    sensitivity to light or sound, and visual disturbances, triggered by various factors.</p>
                <div class="d-grid">
                    <button class="btn btn-dark" type="button">Explore</button>
                </div>
            </div>
        </div> -->
    </div>

    <!-- Book Appointment Modal -->
    <div class="modal fade" id="bookAppointmentModal" tabindex="-1" aria-labelledby="bookAppointmentLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookAppointmentLabel">Book Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="appointmentForm">
                        <input type="hidden" id="doctorId" name="doctor_id">
                        
                        <div class="mb-3">
                            <label for="doctorName" class="form-label">Doctor</label>
                            <input type="text" class="form-control" id="doctorName" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointmentDate" class="form-label">Select Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="appointmentDate" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="appointmentTime" class="form-label">Select Time <span class="text-danger">*</span></label>
                            <select class="form-select" id="appointmentTime" required>
                                <option value="">-- Select Time Slot --</option>
                                <option value="09:00">09:00 AM</option>
                                <option value="09:30">09:30 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="10:30">10:30 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="11:30">11:30 AM</option>
                                <option value="14:00">02:00 PM</option>
                                <option value="14:30">02:30 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="15:30">03:30 PM</option>
                                <option value="16:00">04:00 PM</option>
                                <option value="16:30">04:30 PM</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="consultationReason" class="form-label">Reason for Consultation</label>
                            <textarea class="form-control" id="consultationReason" rows="3" placeholder="Describe your symptoms or reason for visit"></textarea>
                        </div>

                        <div id="appointmentMessage" class="alert d-none" role="alert"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAppointmentBtn">Book Appointment</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'include/footer.php'; ?>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookModal = new bootstrap.Modal(document.getElementById('bookAppointmentModal'));
        const queryParams = new URLSearchParams(window.location.search);
        const preselectedDoctorId = queryParams.get('doctor_id');
        
        // Handle book appointment button clicks
        document.querySelectorAll('.book-appointment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const doctorId = this.dataset.doctorId;
                const doctorName = this.dataset.doctorName;
                
                // Check if user is logged in
                if (!doctorId || !doctorName) {
                    alert('Error: Invalid doctor information');
                    return;
                }
                
                // Set form values
                document.getElementById('doctorId').value = doctorId;
                document.getElementById('doctorName').value = doctorName;
                document.getElementById('appointmentDate').value = '';
                document.getElementById('appointmentTime').value = '';
                document.getElementById('consultationReason').value = '';
                document.getElementById('appointmentMessage').className = 'alert d-none';
                document.getElementById('appointmentMessage').innerHTML = '';
                
                // Show modal
                bookModal.show();
            });
        });

        if (preselectedDoctorId) {
            const preselectedButton = document.querySelector('.book-appointment-btn[data-doctor-id="' + preselectedDoctorId + '"]');
            if (preselectedButton) {
                preselectedButton.click();
            }
        }
        
        // Handle form submission
        document.getElementById('submitAppointmentBtn').addEventListener('click', function() {
            const submitButton = this;
            const doctorId = document.getElementById('doctorId').value;
            const appointmentDate = document.getElementById('appointmentDate').value;
            const appointmentTime = document.getElementById('appointmentTime').value;
            const reason = document.getElementById('consultationReason').value;
            const messageDiv = document.getElementById('appointmentMessage');
            
            if (!appointmentDate || !appointmentTime) {
                messageDiv.className = 'alert alert-danger';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Please select both date and time';
                return;
            }
            
            // Check if user is logged in by verifying session
            fetch('<?php echo $localhost; ?>check_session.php', {
                method: 'GET',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    messageDiv.className = 'alert alert-warning';
                    messageDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Please <a href="login.php" class="alert-link">login</a> to book an appointment';
                    return;
                }

                if (data.user_type !== 'patient') {
                    messageDiv.className = 'alert alert-warning';
                    messageDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Please login with a patient account to book an appointment.';
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Booking...';
                
                // Submit appointment booking
                const formData = new FormData();
                formData.append('action', 'book');
                formData.append('doctor_id', doctorId);
                formData.append('appointment_date', appointmentDate);
                formData.append('appointment_time', appointmentTime);
                formData.append('reason', reason);
                
                fetch('<?php echo $localhost; ?>book_appointment.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        messageDiv.className = 'alert alert-success';
                        messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Appointment booked successfully.<br><strong>Appointment ID:</strong> #' + result.appointment_id + '<br><strong>Status:</strong> ' + (result.status || 'Pending') + '<br>Please wait for admin confirmation.';
                    } else {
                        messageDiv.className = 'alert alert-danger';
                        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (result.message || 'Failed to book appointment. Please try again.');
                    }
                })
                .catch(error => {
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error booking appointment: ' + error.message;
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Book Appointment';
                });
            })
            .catch(error => {
                messageDiv.className = 'alert alert-danger';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error checking login status: ' + error.message;
            });
        });
    });
</script>

</html>
