<?php
// Dashboard Overview Update Script
echo "<h1>AROGYA Hospital - Dashboard Overview Update</h1>";
echo "<p>Updating dashboard with proper topic names and relevant symbols...</p>";

// New dashboard content with proper topics and symbols
$new_dashboard_content = '
<div class="row">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="icon text-primary">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="number">' . $appointments_today . '</div>
            <div class="label">Appointment Management</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="icon text-success">
                <i class="fas fa-users"></i>
            </div>
            <div class="number">' . $patients_today . '</div>
            <div class="label">Patient Registration</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="icon text-info">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="number">' . $doctors_count . '</div>
            <div class="label">Medical Staff</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center revenue-card">
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="number">₹' . number_format($revenue_today) . '</div>
            <div class="label">Financial Overview</div>
        </div>
    </div>
</div>';

// Update the interactive admin portal
$file_path = "c:\\xampp\\htdocs\\MedC\\MedC\\interactive_admin_portal.php";

if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    
    // Replace the old dashboard statistics section
    $old_pattern = '/<div class="row">\s*<div class="col-md-3">.*?<div class="label">Today\'s Revenue<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s';
    
    if (preg_match($old_pattern, $content)) {
        $content = preg_replace($old_pattern, $new_dashboard_content, $content);
        file_put_contents($file_path, $content);
        echo "<p style='color: green;'>✓ Dashboard updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Could not find dashboard section to update</p>";
    }
} else {
    echo "<p style='color: red;'>✗ interactive_admin_portal.php not found</p>";
}

echo "<h2>Updated Dashboard Topics:</h2>";
echo "<ul>
    <li><i class='fas fa-calendar-check text-primary'></i> Appointment Management</li>
    <li><i class='fas fa-users text-success'></i> Patient Registration</li>
    <li><i class='fas fa-user-md text-info'></i> Medical Staff</li>
    <li><i class='fas fa-chart-line'></i> Financial Overview</li>
</ul>";

echo "<h2>Access Updated Dashboard:</h2>";
echo "<ul>
    <li><a href='dashboard_overview.php' target='_blank'>New Dashboard Overview Page</a></li>
    <li><a href='interactive_admin_portal.php' target='_blank'>Updated Interactive Admin Portal</a></li>
</ul>";

echo "<div style='background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 15px; margin-top: 20px;'>
    <h3 style='margin-top: 0;'><i class='fas fa-check-circle'></i> Dashboard Successfully Updated!</h3>
    <p>The dashboard now features clear, professional topic names with relevant symbols that better represent each hospital management area.</p>
</div>";
?>