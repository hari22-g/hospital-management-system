<!DOCTYPE html>
<html>
<head>
    <title>Logo Test</title>
</head>
<body>
    <h1>AAROGYA Logo Test</h1>
    
    <h2>Testing aarogya_logo.svg:</h2>
    <img src="include/homepage_slider/aarogya_logo.svg" alt="AAROGYA Logo" style="width: 220px; height: auto; border: 1px solid #ccc; border-radius: 16px;">
    
    <h2>Testing hello.png:</h2>
    <img src="include/homepage_slider/hello.png" alt="Hello" style="width: 100px; height: 100px; border: 1px solid #ccc;">
    
    <h2>Testing placeholder:</h2>
    <img src="include/homepage_slider/logo_placeholder.svg" alt="Placeholder" style="width: 100px; height: 100px; border: 1px solid #ccc;">
    
    <h2>Direct file check:</h2>
    <?php
    $logo_path = 'include/homepage_slider/aarogya_logo.svg';
    if (file_exists($logo_path)) {
        echo "<p style='color: green;'>aarogya_logo.svg exists (Size: " . filesize($logo_path) . " bytes)</p>";
        echo "<img src='$logo_path' style='width: 220px; height: auto; border-radius: 16px;'>";
    } else {
        echo "<p style='color: red;'>aarogya_logo.svg not found</p>";
    }
    
    $hello_path = 'include/homepage_slider/hello.png';
    if (file_exists($hello_path)) {
        echo "<p style='color: green;'>hello.png exists</p>";
    } else {
        echo "<p style='color: red;'>hello.png not found</p>";
    }
    ?>
</body>
</html>
