<?php
session_start();
include("../../connection/config.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedC Dashboard</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* .popup {
            background-color: rgba(0, 0, 0, 0.6);
            width: 100%;
            height: 100%;
            position: absolute;
            display: none;
            justify-content: center;
            align-items: center;


        }

        .pop {
            background-color: rgba(0, 0, 0, 0.6);
            width: 100%;
            height: 100%;
            position: absolute;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .pop-content {
            height: 500px;
            width: 1016px;
            background: #fff;
            border-radius: 5px;
        }

        .popcontent {
            height: 500px;
            width: 1016px;
            background: #fff;
            border-radius: 5px;
        }

        .close {
            height: 40px;
            width: 66px;
            position: relative;
            top: -87px;
            left: 951px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        } */

        .cname {
            color: darkblue;
            font-size: 2.5rem;
            font-weight: bolder;
        }





        .addButton {
            display: flex;
            justify-content: center;
        }

        .add {
            background-color: rgb(0, 0, 139);
            color: #f8f9fa;
            border-radius: 10px;
            font-size: large;
            text-align: center;
            padding: 1px 15px;
        }


        .biomarker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .add-btn {
            background: #008CBA;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-buttons button {
            border: none;
            background: #eee;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
        }

        .filter-buttons .active {
            background: #008CBA;
            color: white;
        }

        .biomarker-content h3 {
            color: #333;
            margin-bottom: 10px;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }

        li span {
            color: #999;
        }

        @import url("https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600&display=swap");

        :root {
            --underweight: orange;
            --normal: green;
            --overweight: lightcoral;
            --obese: crimson;
        }

        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
            font-family: "Jost", sans-serif;
        }


        .modal-content {
            width: 107%;
        }

        h1 {
            text-align: center;
        }

        .biomarker-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            margin: 10px;
        }

        .con {

            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 380px;
            height: 600px;
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 10px;
            display: grid;
            gap: 1rem;
        }

        .calculator {
            display: flex;
            flex-direction: column;
        }

        .calculator>*:not(:last-child) {
            margin-bottom: 1rem;
        }

        .calculator div {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .calculator label {
            flex: 0 0 120px;
            font-weight: 600;

        }

        .calculator input {
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            outline-color: #555;
            font-size: 1.25rem;
            text-align: center;
        }

        .calculator button {
            width: 50%;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            padding: 10px;
            background: #00a1a3;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            font-size: 0.875rem;
        }

        .calculator button[type="reset"] {
            background: #444;
        }

        .calculator button:hover {
            filter: brightness(120%);
        }

        .output {
            text-align: center;
        }

        .output #bmi {
            font-size: 3rem;
            margin-bottom: 0px;
        }

        h5 {
            margin-bottom: 0px;
        }

        .output #desc strong {
            text-transform: uppercase;
        }

        .bmi-scale {
            display: flex;
        }

        .bmi-scale div {
            flex: 1;
            text-align: center;
            text-transform: uppercase;
            border-top: 5px solid var(--color);
            padding: 10px;
        }

        .bmi-scale h4 {
            font-size: 0.75rem;
            color: slategray;
        }

        @media (max-width: 480px) {
            .bmi-scale {
                flex-direction: column;
            }

            .bmi-scale div {
                padding: 4px 10px;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                border-top: 1px solid #eee;
                border-right: 1px solid #eee;
                border-left: 5px solid var(--color);
            }

            .bmi-scale div:last-child {
                border-bottom: 1px solid #eee;
            }

            .bmi-scale h4 {
                width: 120px;
                text-align: start;
            }

            .bmi-scale p {
                font-size: 0.875rem;
                margin-left: auto;
            }
        }

        .underweight {
            color: var(--underweight);
        }

        .healthy {
            color: var(--normal);
        }

        .overweight {
            color: var(--overweight);
        }

        .obese {
            color: var(--obese);
        }

        .mainContainer {
            margin-top: 10px;
            display: flex;
            justify-content: space-evenly;
        }

        .fa-chart-line {
            font-weight: 900;
            font-size: 20px;
            margin-right: 10px;
        }

        .containerr {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }

        h2 {
            text-align: center;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* .btn {
            margin-top: 15px;
            width: 50px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 20px;

        } */

        /* .btn:hover {
            background-color: #218838;
        } */

        .close {
            padding: 5px 10px;
            height: 40px;
            width: 66px;
            position: relative;
            top: -405px;
            left: 950px;
            border: none;
            border-radius: 5px;
        }

        .sclose {
            padding: 5px 10px;
            height: 40px;
            width: 66px;
            position: relative;
            top: -289px;
            left: 950px;
            border: none;
            border-radius: 5px;
        }

        .medical {
            width: 600px;
            display: flex;

        }

        .heart,
        .sugar {
            margin: 10px;
        }

        .last {
            padding: 10px;
            width: 100px;
            position: relative;
            left: 180px;
            /* background-color:lightgreen;
            border: none;
            font-weight: 700;
            color:white; */
        }

        hr {
            margin: 0px;
            position: relative;
            bottom: 89px;
        }

        #bpChart {
            position: relative;
            bottom: 150px;
        }

        .view {
            width: 100px;
        }

        .biotable {
            width: 1000px;
        }

        .flex {
            display: flex;
            justify-content: space-between;
            align-items: end;

        }

        #sugar {
            height: 35px;
            margin-bottom: 5px;
            border: none;
            border-radius: 10px;
        }

        #button {
            margin-bottom: 5px;
            height: 35px;
            border: none;
            border-radius: 10px;

        }
    </style>
</head>

<body>
    <div class="d-flex">
        <main class="flex-grow-1">

            <div class="mainContainer">
                <div class="con">
                    <h2>Weight Tracker</h2>

                    <canvas id="bpChart"></canvas>

                </div>
                <div class="biomarker-container">
                    <div class="biomarker-header">
                        <h2>Biomarkers</h2>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter" style="padding: 5px 10px">
                            Add
                        </button>
                        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="containerr">
                                            <h2 class="mb-4 text-secondary">Biomarker Input Form</h2>
                                            <form action="#" method="POST">
                                                <div class="medical">
                                                    <div class="heart">
                                                        <h4>Heart Health</h4>
                                                        <label for="total_cholesterol">Total Cholesterol:</label>
                                                        <input type="text" id="total_cholesterol" name="total_cholesterol">

                                                        <label for="hdl_cholesterol">HDL Cholesterol:</label>
                                                        <input type="text" id="hdl_cholesterol" name="hdl_cholesterol">

                                                        <label for="ldl_cholesterol">LDL Cholesterol:</label>
                                                        <input type="text" id="ldl_cholesterol" name="ldl_cholesterol">

                                                        <label for="triglycerides">Triglycerides:</label>
                                                        <input type="text" id="triglycerides" name="triglycerides">

                                                        <label for="sytolic_blood_pressure">Systolic Blood Pressure:</label>
                                                        <input type="text" id="sytolic_blood_pressure" name="sytolic_blood_pressure">

                                                        <label for="diastolic_blood_pressure">Diastolic Blood Pressure:</label>
                                                        <input type="text" id="diastolic_blood_pressure" name="diastolic_blood_pressure">
                                                    </div>
                                                    <div class="sugar">


                                                        <h4>Blood Sugar</h4>
                                                        <label for="non_fasting_glucose">Non-Fasting Glucose:</label>
                                                        <input type="text" id="non_fasting_glucose" name="non_fasting_glucose">

                                                        <label for="glucose_post_fast">Glucose, Post Fast:</label>
                                                        <input type="text" id="glucose_post_fast" name="glucose_post_fast">

                                                        <label for="hba1c">HbA1c:</label>
                                                        <input type="text" id="hba1c" name="hba1c">
                                                    </div>
                                                </div>
                                                <button type="submit" class="last biomakers_submit btn btn-primary">Submit</button>
                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="filter-buttons">
                        <button class="active">ALL</button>
                        <button>❤️</button>
                        <button>🧬</button>
                        <button>🩸</button>
                        <button>🩺</button>
                    </div> -->
                    <div class="biomarker-content">
                        <div class="flex">
                            <h3>❤️ Heart Health</h3>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#heart_health">
                                View More
                            </button>
                        </div>
                        <!-- Heart data Modal -->
                        <div class="modal fade" id="heart_health" tabindex="-1" aria-labelledby="heart_healthLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="heart_healthlLabel">Heart Health</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">First</th>
                                                    <th scope="col">Last</th>
                                                    <th scope="col">Handle</th>
                                                    <th scope="col">Handle</th>
                                                    <th scope="col">Handle</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <ul>
                            <li>Total Cholesterol <span>no result</span></li>
                            <li>HDL Cholesterol <span>no result</span></li>
                            <li>LDL Cholesterol <span>no result</span></li>
                            <li>Triglycerides <span>no result</span></li>
                            <li>Systolic Blood Pressure<span>no result</span></li>
                            <li>Diastolic Blood Pressure<span>no result</span></li>
                        </ul>
                        <div class="flex">
                            <h3 class="mt-5">🩸 Blood Sugar</h3>
                            <button id="sugar" style="padding: 5px 10px">
                                View More
                            </button>
                        </div>

                        <ul>
                            <li>Non-Fasting Glucose <span class="editable">no result</span></li>
                            <li>Glucose, Post Fast <span class="editable">no result</span></li>
                            <li>HbA1c <span class="editable">no result</span></li>
                        </ul>

                    </div>
                </div>
                <div class="con">
                    <h2 class="text-center">BMI Calculator</h2>

                    <form class="calculator">
                        <div>
                            <label for="weight">Weight (kg)</label>
                            <input
                                type="number"
                                id="weight"
                                min="0"
                                step="any"
                                value="0">
                        </div>

                        <div>
                            <label for="height">Height (cm)</label>
                            <input
                                type="number"
                                id="height"
                                min="0"
                                step="any"
                                value="0">
                        </div>

                        <div>
                            <button type="button" id="bmi_reset">Reset</button>
                            <button type="button" id="bmi_submit">Submit</button>
                        </div>
                    </form>

                    <section class="output">
                        <h5>Your BMI is</h5>
                        <p id="bmi">0</p>
                        <p id="desc">N/A</p>
                    </section>

                    <section class="bmi-scale">
                        <div style="--color: var(--underweight)">
                            <h4>Underweight</h4>
                            <p>&lt; 18.5</p>
                        </div>

                        <div style="--color: var(--normal)">
                            <h4>Normal</h4>
                            <p>18.5 – 25</p>
                        </div>

                        <div style="--color: var(--overweight)">
                            <h4>Overweight</h4>
                            <p>25 – 30</p>
                        </div>

                        <div style="--color: var(--obese)">
                            <h4>Obese</h4>
                            <p>≥ 30</p>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
    <!-- Bootstrap Bundle with Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

<script>
    let btn = document.getElementById("button").addEventListener("click", () => {
        document.querySelector(".popup").style.display = "flex"
        document.querySelector("#bpChart").style.display = "none"

    })
    let close = document.querySelector(".close").addEventListener("click", () => {
        document.querySelector(".popup").style.display = "none"
        document.querySelector("#bpChart").style.display = "block"

    })
    let sugar = document.getElementById("sugar").addEventListener("click", () => {
        document.querySelector(".pop").style.display = "flex"
        document.querySelector("#bpChart").style.display = "none"
    })

    let sclose = document.querySelector(".sclose").addEventListener("click", () => {
        document.querySelector(".pop").style.display = "none"
        document.querySelector("#bpChart").style.display = "block"

    })

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
                        labels: monthData, // Use array directly
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
    // document.addEventListener("DOMContentLoaded", function() {
    //     const form = document.querySelector(".calculator");
    //     const weightInput = document.getElementById("weight");
    //     const heightInput = document.getElementById("height");
    //     const bmiOutput = document.getElementById("bmi");
    //     const descOutput = document.getElementById("desc");

    //     form.addEventListener("submit", function(event) {
    //         event.preventDefault();

    //         const weight = parseFloat(weightInput.value);
    //         const height = parseFloat(heightInput.value) / 100; // Convert cm to meters

    //         if (weight > 0 && height > 0) {
    //             const bmi = (weight / (height * height)).toFixed(1);
    //             bmiOutput.innerText = bmi;

    //             let description = "";
    //             if (bmi < 18.5) {
    //                 description = "Underweight";
    //             } else if (bmi >= 18.5 && bmi < 25) {
    //                 description = "Normal";
    //             } else if (bmi >= 25 && bmi < 30) {
    //                 description = "Overweight";
    //             } else {
    //                 description = "Obese";
    //             }
    //             descOutput.textContent = description;
    //         } else {
    //             bmiOutput.textContent = "0";
    //             descOutput.textContent = "N/A";
    //         }
    //     });

    //     form.addEventListener("reset", function() {
    //         bmiOutput.textContent = "0";
    //         descOutput.textContent = "N/A";
    //     });
    // });
</script>
<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        $("#bmi_sumbit").on("click", function(event) {
            event.preventDefault(); // Prevent form refresh

            let weight = $("#weight").val();
            let height = $("#height").val();

            $.ajax({
                url: "bmi_calculator.php",
                type: "POST",
                data: {
                    weight: weight,
                    height: height
                },
                dataType: "json",
                success: function(response) {
                    $("#bmi").text(response.bmi);
                    $("#desc").text(response.desc);
                },
                error: function() {
                    $("#bmi").text("Error");
                    $("#desc").text("Could not calculate BMI");
                }
            });
        });

        // Reset button functionality
        $(".calculator").on("reset", function() {
            $("#bmi").text("0");
            $("#desc").text("N/A");
        });
    });
</script>

</html>