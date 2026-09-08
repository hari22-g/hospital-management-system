<?php
header("Content-Type: application/json"); // Set JSON response type

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
    $height = isset($_POST['height']) ? floatval($_POST['height']) / 100 : 0; // Convert cm to meters

    if ($weight > 0 && $height > 0) {
        $bmi = round($weight / ($height * $height), 1);
        $description = "N/A";

        if ($bmi < 18.5) {
            $description = "Underweight";
        } elseif ($bmi >= 18.5 && $bmi < 25) {
            $description = "Normal";
        } elseif ($bmi >= 25 && $bmi < 30) {
            $description = "Overweight";
        } else {
            $description = "Obese";
        }

        echo json_encode(["bmi" => $bmi, "desc" => $description]);
    } else {
        echo json_encode(["bmi" => "Error", "desc" => "Invalid weight or height"]);
    }
} else {
    echo json_encode(["bmi" => "Error", "desc" => "Invalid request"]);
}
?>
