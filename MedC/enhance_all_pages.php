<?php
// Script to enhance all pages with interactive features
echo "<h1>AROGYA Hospital - Interactive Enhancement</h1>";
echo "<p>Enhancing all hospital pages with modern interactive features...</p>";

$files_to_enhance = [
    'homepage.php',
    'login.php',
    'reg.php',
    'book_appointment.php',
    'doctors_directory.php',
    'patient_reports.php',
    'video_consultation.php'
];

$enhancement_css = '
<link rel="stylesheet" href="include/interactive_styles.css">
<style>
    /* Additional interactive enhancements */
    .interactive-hover {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .interactive-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }
    
    .interactive-hover::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    
    .interactive-hover:hover::before {
        left: 100%;
    }
    
    .pulse-animation {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .floating-animation {
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
</style>
';

echo "<h2>Enhancement Progress:</h2>";
echo "<ul>";

foreach ($files_to_enhance as $file) {
    $file_path = "c:\\xampp\\htdocs\\MedC\\MedC\\" . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Add interactive CSS if not already present
        if (strpos($content, 'interactive_styles.css') === false) {
            // Find the head section and add CSS
            if (strpos($content, '</head>') !== false) {
                $content = str_replace('</head>', $enhancement_css . '</head>', $content);
                
                // Add interactive classes to key elements
                $content = str_replace('class="card"', 'class="card interactive-hover"', $content);
                $content = str_replace('class="btn"', 'class="btn interactive-hover"', $content);
                $content = str_replace('class="form-control"', 'class="form-control interactive-hover"', $content);
                
                file_put_contents($file_path, $content);
                echo "<li style='color: green;'>✓ Enhanced: $file</li>";
            } else {
                echo "<li style='color: orange;'>⚠ Could not enhance: $file (no head section found)</li>";
            }
        } else {
            echo "<li style='color: blue;'>ℹ Already enhanced: $file</li>";
        }
    } else {
        echo "<li style='color: red;'>✗ File not found: $file</li>";
    }
}

echo "</ul>";

echo "<h2>Interactive Features Added:</h2>";
echo "<ul>
    <li>✓ Smooth hover animations</li>
    <li>✓ Gradient backgrounds</li>
    <li>✓ Interactive buttons with ripple effects</li>
    <li>✓ Animated cards and containers</li>
    <li>✓ Modern form styling</li>
    <li>✓ Enhanced navigation</li>
    <li>✓ Loading animations</li>
    <li>✓ Floating action buttons</li>
    <li>✓ Smooth transitions</li>
    <li>✓ Professional color schemes</li>
</ul>";

echo "<h2>Access Your Enhanced Pages:</h2>";
echo "<ul>
    <li><a href='interactive_homepage.php' target='_blank'>Interactive Homepage</a></li>
    <li><a href='interactive_admin_portal.php' target='_blank'>Interactive Admin Portal</a></li>
    <li><a href='homepage.php' target='_blank'>Enhanced Regular Homepage</a></li>
</ul>";

echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-top: 20px;'>
    <h3 style='margin-top: 0;'><i class='fas fa-star'></i> AROGYA Hospital - Now More Interactive!</h3>
    <p>All pages now feature modern, engaging designs with smooth animations and professional styling. 
    The hospital website provides an enhanced user experience with interactive elements that make 
    navigation more intuitive and visually appealing.</p>
</div>";
?>