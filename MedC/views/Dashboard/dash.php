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
    <style>
        .card-custom {
      background-color: white; 
      color: black;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
    .card-header {
      background-color: #1d3557; 
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        .cname {
            color: darkblue;
            font-size: 2.5rem;
            font-weight: bolder;
        }

        .sidebar {
            width: 280px;
            height: auto;
            /* Default height */
            background-color: white;
            color: black;
            box-shadow: 1px 1px 1px rgba(0, 0, 0, 0.1);
        }

        .sidebar li {
            padding: 2px 10px;
        }

        .sidebar a {
            color: black;
            text-decoration: none;
            padding: 15px 18px;
        }

        .sidebar .nav-link.active {
            background-color: darkblue;
            font-weight: bold;
        }

        .rounded-circle {
            border: 2px solid #007bff;
        }

        header {
            background-color: darkblue;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 80px;
            /* Navbar height */
        }

        header h2 {
            margin: 0;
        }

        .sidebar,
        main {
            height: 100vh;
            /* Sync height with viewport */
        }

        .sidebar-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80px;
            /* Adjust height of the logo area */
        }

        .graph {
            font-size: 20px;
            margin-right: 10px;
        }

        .card {
            margin-top: -24px;
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
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="d-flex flex-column sidebar">
            <div class="sidebar-logo">
                <a href="/" class="d-flex align-items-center text-decoration-none">
                    <h1 class="cname mt-2">MedC</h1>
                </a>
            </div>
            <ul class="nav nav-pills flex-column mb-auto mt-4">
                <li>
                <a href="#" class="nav-link" id="loadDashboard">
                    <i class="fa-solid fa-chart-simple graph"></i> Dashboard
                </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-user graph"></i> Family Members
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" id="loadReports">
                        <i class="fas fa-notes-medical graph"></i> Reports
                    </a>
                </li>


                <li class="nav-item">
                    <a href="#" class="nav-link text-secondary" aria-current="page">
                        <i class="fa-solid fa-chart-simple graph"></i> Edit
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link text-secondary">
                        <i class="fa fa-sign-out graph" aria-hidden="true"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="flex-grow-1">
            <header class="d-flex justify-content-between align-items-center py-3 px-4">
                <h2>Vidit's Dashboard</h2>
                <div class="d-flex align-items-center">
                    <!-- <img src="profile.jpeg" alt="User Profile" class="rounded-circle" style="width: 40px;"> -->
                </div>
            </header>
            <div id="dynamicContent">
            <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card card-custom">
          <div class="card-header">Patient Information</div>
          <div class="card-body">
            <p class="card-text"><strong>First Name:</strong> John</p>
            <p class="card-text"><strong>Last Name:</strong> Doe</p>
            <p class="card-text"><strong>Date of Birth:</strong> 1990-01-01</p>
            <p class="card-text"><strong>Gender:</strong> Male</p>
            <p class="card-text"><strong>Height:</strong> 180 cm</p>
            <p class="card-text"><strong>Weight:</strong> 75 kg</p>
            <p class="card-text"><strong>Blood Group:</strong> O+</p>
            <p class="card-text"><strong>Contact No:</strong> +1234567890</p>
          </div>
        </div>
      </div>
    </div>
  </div>
            </div>

        </main>

    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
    $("#loadReports").on("click", function (event) {
        event.preventDefault();
        console.log("Reports button clicked!");
        $("#dynamicContent").load("reports.html", function (response, status, xhr) {
            if (status == "error") {
                console.log("Error loading reports:", xhr.status, xhr.statusText);
            } else {
                console.log("Reports section loaded successfully!");
                $.ajax({
                    url: "fetch_reports.php",
                    method: "GET",
                    dataType: "json",
                    success: function (response) {
                        let reportsHtml = "";
                        $.each(response, function (index, report) {
                            reportsHtml += `
                                <tr>
                                    <td>${report.report_type}</td>
                                    <td><a href="${report.file_path}" target="_blank">View</a></td>
                                    <td>${report.referred_by}</td>
                                    <td>${report.report_date}</td>
                                </tr>`;
                        });
                        $("#reportsTableBody").html(reportsHtml);
                    },
                    error: function (xhr, status, error) {
                        console.log("AJAX Error:", status, error);
                        alert("Failed to load reports.");
                    }
                });
            }
        });
    });

    // Load Profile when Dashboard is clicked
    $("#loadDashboard").on("click", function (event) {
        event.preventDefault();
        console.log("Dashboard button clicked!");
        $("#dynamicContent").load("patient_profile.html", function (response, status, xhr) {
            if (status == "error") {
                console.log("Error loading profile:", xhr.status, xhr.statusText);
            } else {
                console.log("Profile section loaded successfully!");
            }
        });
    });
});

    </script>





</body>

</html>