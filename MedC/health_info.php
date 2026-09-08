<?php
session_start();
// Include the database connection
include("connection/config.php");
?>
<?php
// Define the range of letters
$letters = range('A', 'Z');
$selected_letter = isset($_GET['letter']) ? strtoupper($_GET['letter']) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disease Information</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
  <style>
    /* Centralized CSS (reusable styles) */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background-color: #f9faff;
      color: #333;
    }

    .header h2 {
      margin: 0;
      font-weight: 700;
    }

    .main_list {
      margin-top: 10px;
      height: 2px;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
    }

    .main_list a {
      font-size: 23px;
      display: flex;
      padding: 20px;
      justify-content: center;
      align-items: center;
      border: 1px solid black;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      margin: 5px;
      text-decoration: none;
      color: white;
      background-color: darkblue;
      font-weight: bold;
    }

    .alphabets {
      padding-top: 20px;
      /* padding-bottom: 30px; */
      border: 1px solid black;
      height: 300px;
      background-color: lightblue;
      box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.7)
    }

    .main_list a:hover {
      padding: 16px;
      margin: 4px 9px;
      background-color: rgba(0, 0, 0, 0.90);
      transition: all 0.3s ease-out;
      transform: scale(1.2);
    }

    .main_list a:active {
      padding: 16px;
      margin: 5px 9px;
      background-color: black;
      transform: scale(1.2);
      box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.7)
    }

    .main_list a[style*="background-color: black"] {
      background-color: black !important;
    }

    .topic-list {
      background-color: #f6f7f9;
      padding: 4px 0px 8px 16px;
      font-size: 22px;
      border: 1px solid #ddd;
      margin-bottom: 20px;
      border-radius: 5px;
      position: relative;
    }

    .topic-list:hover {
      background-color: #e7eaef;
    }

    .disease-type {
      font-size: 15px;
    }

    .disease-name {
      font-size: 25px;
    }

    .info-button {
      height: 40px;
      position: absolute;
      /* Add this line */
      top: 50%;
      /* Vertically center the button */
      transform: translateY(-50%);
      /* Adjust for exact centering */
      right: 10px;
    }

    .no-results {
      color: #999;
    }
  </style>
</head>

<body>
  <?php include("include/navbar.php");
  ?>
  <div class="alphabets">
    <div class="container main_list">
      <h2 style="width:100%; text-align:center; margin-bottom:25px">Get The diasease list with the help of the alphabets
      </h2>
      <!-- Alphabet List -->
      <?php foreach ($letters as $letter): ?>
        <a href="?letter=<?= $letter ?>" <?= $letter === $selected_letter ? 'style="background-color: black; transform: scale(1.2);
      margin:5px 9px; "' : '' ?>>
          <?= $letter ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="container mt-4">
    <!-- Disease List -->
    <?php if ($selected_letter): ?>
      <?php
      // Fetch diseases starting with the selected letter 
      $query = "SELECT * FROM disease_information WHERE disease_name LIKE ?";
      $stmt = $conn->prepare($query);
      $like_param = $selected_letter . '%';
      $stmt->bind_param("s", $like_param);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows > 0): ?>
        <ul class="list-unstyled">
          <?php while ($row = $result->fetch_assoc()): ?>
            <li class="topic-list">
              <div class="">
                <span class="disease-name fw-bold"><?= htmlspecialchars($row['disease_name']) ?>
                </span>
                <button class="btn btn-primary info-button float-end"
                  onclick="window.location.href='disease_info_template.php?disease_name=<?= urlencode($row['disease_name']) ?>'">
                  More Info
                </button>
              </div>

            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p class="no-results">No diseases found starting with '<?= $selected_letter ?>'.</p>
      <?php endif;

      $stmt->close();
      ?>
    <?php else: ?>
      <p class="no-results">Please select a letter to display diseases.</p>
    <?php endif; ?>
  </div>
</body>

<script>
  
</script>
</html>