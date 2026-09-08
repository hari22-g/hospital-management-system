<?php

echo ' <div class="d-flex">
        <main class="flex-grow-1">
            <div class="mainContainer">
                <div class="con">

<!-- Weight Tracker - Display Only -->
                    <h2>Weight Tracker</h2>
                    <canvas id="bpChart"></canvas>
                    <div class="alert alert-info alert-dismissible fade show mt-3" role="alert" style="background-color: #d1ecf1; border-color: #bee5eb;">
                        <small><i class="fas fa-lock me-2"></i>Weight data is managed by your healthcare provider. Contact them to update records.</small>
                    </div>

                </div>
                <div class="biomarker-container">
                    <div class="biomarker-header">
                        <h2>Biomarkers</h2>
                    </div>
                    
                    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" style="margin-top: -10px; background-color: #d1ecf1; border-color: #bee5eb;">
                        <small><i class="fas fa-info-circle me-2"></i>Your health data is managed by your healthcare provider. Please contact them for updates.</small>
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
                            <button type="button" class="btn btn-primary h_btn" data-bs-toggle="modal" data-bs-target="#heart_health">
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
                                        <table class="table table-striped">
                                            <thead>
                                               <tr>
                                                    <th scope="col" class = "biomarker_head bg-success">Date</th>
                                                    <th scope="col" class = "biomarker_head bg-success">Total Cholesterol</th>
                                                    <th scope="col" class = "biomarker_head bg-success">HDL Cholesterol</th>
                                                    <th scope="col" class = "biomarker_head bg-success">LDL Cholesterol</th>
                                                    <th scope="col" class = "biomarker_head bg-success">Triglycerides</th>
                                                    <th scope="col" class = "biomarker_head bg-success">Systolic Blood Pressure</th>
                                                    <th scope="col" class = "biomarker_head bg-success">Diastolic Blood Pressure</th>
                                                </tr>
                                            </thead>
                                            <tbody class="h_data">

                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div id="heart_data" class="alert alert-info mb-3" style="padding: 10px 15px; background-color: #d1ecf1; border-color: #bee5eb;">
                            <small class="mb-0"><i class="fas fa-heartbeat me-1"></i>Loading heart health data...</small>
                        </div>
                        <div class="flex">
                            <h3 class="mt-5">🩸 Blood Sugar</h3>
                            <button type="button" class="btn btn-primary glucosebtn" data-bs-toggle="modal" data-bs-target="#glucose_data">
                                View More
                            </button>
                        </div>
<!-- Glucose data Modal -->
                        <div class="modal fade" id="glucose_data" tabindex="-1" aria-labelledby="glucose_dataLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="glucose_dataLabel">Glucose Data</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table  table-striped ">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class = "biomarker_head bg-success">Date</th>
                                                    <th scope="col" class = "biomarker_head bg-success">Non-Fasting Glucose</th>
                                                    <th scope="col" class = "biomarker_head bg-success">Glucose(Post Fast)</th>
                                                    <th scope="col" class = "biomarker_head bg-success">HbA1c</th>
                                                </tr>
                                            </thead>
                                            <tbody class = "glucose_data">
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="g_data" class="alert alert-info mb-3" style="padding: 10px 15px; background-color: #d1ecf1; border-color: #bee5eb;">
                            <small class="mb-0"><i class="fas fa-tint me-1"></i>Loading blood sugar data...</small>
                        </div>

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
                        <h5 class="mt-5">Your BMI is</h5>
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
        </main>';
