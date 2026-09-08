<?php
session_start();
include("../../connection/config.php");
?>

<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> 
</head>

<body>

<div class="container mt-4">
    <h2>Weight Tracker</h2>
    <canvas id="bpChart" ></canvas> 

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('fetch.php')
    .then(response => response.json())
    .then(data => {
        let weightData = data.map(item => item.weight);
        let monthData = data.map(item => item.month); 

        var ctx = document.getElementById('bpChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthData,  // Use array directly
                datasets: [{
                    label: 'Weight',
                    data: weightData,
                    backgroundColor: 'rgba(235, 54, 54, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    })
    .catch(error => console.error('Error fetching data:', error));
});

</script>

</body>
</html>
