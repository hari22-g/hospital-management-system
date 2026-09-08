<!DOCTYPE html>
<html>
<head>
    <title>Create AROGYA Logo</title>
</head>
<body>
    <h1>Creating AROGYA Hospital Logo</h1>
    <?php
    // Create a simple hospital logo
    $width = 800;
    $height = 400;

    // Create image
    $image = imagecreate($width, $height);

    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $blue = imagecolorallocate($image, 0, 102, 204);
    $light_blue = imagecolorallocate($image, 100, 180, 255);
    $green = imagecolorallocate($image, 34, 139, 34);

    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $white);

    // Draw hospital building
    imagefilledrectangle($image, 150, 150, 650, 350, $blue);
    imagefilledrectangle($image, 100, 200, 700, 350, $light_blue);

    // Draw windows
    for($i = 0; $i < 5; $i++) {
        for($j = 0; $j < 3; $j++) {
            $x = 180 + ($i * 100);
            $y = 220 + ($j * 50);
            imagefilledrectangle($image, $x, $y, $x + 40, $y + 30, $white);
            imagerectangle($image, $x, $y, $x + 40, $y + 30, $blue);
        }
    }

    // Draw cross symbol
    $cross_x = 400;
    $cross_y = 100;
    $cross_size = 40;
    // Vertical bar
    imagefilledrectangle($image, $cross_x - 10, $cross_y - $cross_size, $cross_x + 10, $cross_y + $cross_size, $green);
    // Horizontal bar
    imagefilledrectangle($image, $cross_x - $cross_size, $cross_y - 10, $cross_x + $cross_size, $cross_y + 10, $green);

    // Add text
    $font = 5;
    $text = "AROGYA";
    $text_width = imagefontwidth($font) * strlen($text);
    $text_x = ($width - $text_width) / 2;
    $text_y = 50;
    imagestring($image, $font, $text_x, $text_y, $text, $blue);

    $text2 = "HOSPITAL";
    $text_width2 = imagefontwidth($font) * strlen($text2);
    $text_x2 = ($width - $text_width2) / 2;
    $text_y2 = 80;
    imagestring($image, $font, $text_x2, $text_y2, $text2, $blue);

    // Save image
    $logo_path = 'include/homepage_slider/arogya_logo.png';
    imagepng($image, $logo_path);
    imagedestroy($image);

    echo "<p>Logo created successfully at: $logo_path</p>";
    echo "<img src='$logo_path' alt='AROGYA Logo' style='max-width: 100%; height: auto;'>";
    ?>
</body>
</html>