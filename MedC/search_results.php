<?php
session_start();
// Include the database connection and hospital info
include("connection/config.php");
include("include/hospital_info.php");

$hospital_info = getHospitalInfo();
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

$results = [];
$error_message = '';

if (!empty($query)) {
    // Search across multiple tables
    $search_results = [];
    
    // Search in doctors
    $doctor_sql = "SELECT 'doctor' as type, CONCAT(f_name, ' ', l_name) as name, specialization as description, CONCAT('View Profile') as action, CONCAT('doctors_directory.php') as link FROM doctor WHERE CONCAT(f_name, ' ', l_name) LIKE ? OR specialization LIKE ?";
    $doctor_stmt = $conn->prepare($doctor_sql);
    $search_term = "%".$query."%";
    $doctor_stmt->bind_param("ss", $search_term, $search_term);
    $doctor_stmt->execute();
    $doctor_result = $doctor_stmt->get_result();
    
    while ($row = $doctor_result->fetch_assoc()) {
        $search_results[] = $row;
    }
    
    // Search in diseases
    $disease_sql = "SELECT 'disease' as type, disease_name as name, overview as description, CONCAT('Learn More') as action, CONCAT('disease_info_template.php?disease=', disease_name) as link FROM disease_information WHERE disease_name LIKE ? OR overview LIKE ? OR symptoms LIKE ? OR causes LIKE ?";
    $disease_stmt = $conn->prepare($disease_sql);
    $disease_stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $disease_stmt->execute();
    $disease_result = $disease_stmt->get_result();
    
    while ($row = $disease_result->fetch_assoc()) {
        // Truncate description if too long
        $row['description'] = strlen($row['description']) > 150 ? substr($row['description'], 0, 150) . "..." : $row['description'];
        $search_results[] = $row;
    }
    
    // Search in health topics
    $health_sql = "SELECT 'health_topic' as type, organ_name as name, CONCAT('Information about ', organ_name, ' health') as description, CONCAT('Explore') as action, CONCAT('organ_health_info.php?organ_name=', organ_name) as link FROM disease_information WHERE organ_name LIKE ?";
    $health_stmt = $conn->prepare($health_sql);
    $health_stmt->bind_param("s", $search_term);
    $health_stmt->execute();
    $health_result = $health_stmt->get_result();
    
    while ($row = $health_result->fetch_assoc()) {
        $search_results[] = $row;
    }
    
    $results = $search_results;
    
    if (empty($results)) {
        $error_message = "No results found for '$query'. Try different keywords.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Search Results - <?php echo htmlspecialchars($hospital_info['hospital_name']); ?></title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .search-result-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .search-result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .result-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 20px;
        }
        
        .result-body {
            padding: 20px;
        }
        
        .result-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #212529;
        }
        
        .result-description {
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .result-type {
            background-color: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .view-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .view-btn:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }
        
        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .search-summary {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .no-results {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .no-results i {
            font-size: 4rem;
            color: #e9ecef;
            margin-bottom: 20px;
        }
        
        .popular-searches {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .search-suggestion {
            display: inline-block;
            background-color: #e9ecef;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
        }
        
        .search-suggestion:hover {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>

<body>
    <?php include("include/navbar.php"); ?>

    <div class="container mt-4">
        <h1 class="section-title text-center mb-4">Search Results</h1>
        
        <div class="search-summary">
            <h4><i class="fas fa-search me-2"></i>Search Results for "<?php echo htmlspecialchars($query); ?>"</h4>
            <p class="mb-0"><?php echo count($results); ?> results found</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h4><?php echo htmlspecialchars($error_message); ?></h4>
                <p>Try searching for:</p>
                <div class="d-flex flex-wrap justify-content-center">
                    <span class="search-suggestion" onclick="searchSuggestion('doctors')">Doctors</span>
                    <span class="search-suggestion" onclick="searchSuggestion('cardiology')">Cardiology</span>
                    <span class="search-suggestion" onclick="searchSuggestion('pediatrics')">Pediatrics</span>
                    <span class="search-suggestion" onclick="searchSuggestion('heart disease')">Heart Disease</span>
                    <span class="search-suggestion" onclick="searchSuggestion('diabetes')">Diabetes</span>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <?php foreach ($results as $result): ?>
                        <div class="search-result-card">
                            <div class="result-header">
                                <span class="result-type"><?php echo ucfirst(htmlspecialchars($result['type'])); ?></span>
                                <h4 class="result-title"><?php echo htmlspecialchars($result['name']); ?></h4>
                            </div>
                            <div class="result-body">
                                <p class="result-description"><?php echo htmlspecialchars($result['description']); ?></p>
                                <a href="<?php echo htmlspecialchars($result['link']); ?>" class="view-btn">
                                    <?php echo htmlspecialchars($result['action']); ?> <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="popular-searches">
            <h4><i class="fas fa-fire me-2"></i>Popular Searches</h4>
            <div class="d-flex flex-wrap">
                <span class="search-suggestion" onclick="searchSuggestion('cardiologist')">Cardiologist</span>
                <span class="search-suggestion" onclick="searchSuggestion('neurologist')">Neurologist</span>
                <span class="search-suggestion" onclick="searchSuggestion('orthopedic')">Orthopedic</span>
                <span class="search-suggestion" onclick="searchSuggestion('diabetes')">Diabetes</span>
                <span class="search-suggestion" onclick="searchSuggestion('heart attack')">Heart Attack</span>
                <span class="search-suggestion" onclick="searchSuggestion('hypertension')">Hypertension</span>
                <span class="search-suggestion" onclick="searchSuggestion('appointment')">Book Appointment</span>
                <span class="search-suggestion" onclick="searchSuggestion('emergency')">Emergency</span>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function searchSuggestion(term) {
            document.getElementById('searchInput').value = term;
            document.querySelector('form[action="search_results.php"]').submit();
        }
    </script>
    
    <?php include("include/footer.php"); ?>
</body>

</html>