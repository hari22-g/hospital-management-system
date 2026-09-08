<?php
include("submit_diseases.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disease Information Form</title>
  <link rel="stylesheet" href="styles.css">
</head>

<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    padding: 20px;
  }

  .container {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    max-width: 900px;
    margin: auto;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  }

  h2 {
    text-align: center;
    margin-bottom: 20px;
  }

  form {
    display: flex;
    flex-direction: column;
  }

  label {
    margin-bottom: 5px;
    font-weight: bold;
  }

  input,
  select,
  button,
  textarea {
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  button {
    background-color: #4CAF50;
    color: white;
    cursor: pointer;
    font-size: 16px;
  }

  button:hover {
    background-color: #45a049;
  }

  #formMessage {
    margin-top: 20px;
    text-align: center;
    font-size: 16px;
    color: green;
  }

  input:invalid,
  select:invalid,
  textarea:invalid {
    border-color: red;
  }
</style>

<body>

  <div class="container">
    <h2>Add Disease Information</h2>
    <form id="diseaseForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
      <label for="diseaseName">Disease Name:</label>
      <input type="text" id="diseaseName" name="diseaseName" required>

      <label for="organame">Organ name:</label>
      <input type="text" id="organ_name" name="organ_name">

      <label for="overview">Overview:</label>
      <textarea name="overview" id="overview" placeholder="Enter overview here" rows="5" required></textarea>

      <label for="symptoms">Symptoms:</label>
      <textarea name="symptoms" id="symptoms" placeholder="Enter symptoms here" rows="5" required></textarea>

      <label for="causes">Causes:</label>
      <textarea name="causes" id="causes" placeholder="Enter causes here" rows="5" required></textarea>

      <label for="video1">Video 1 (YouTube embedded code):</label>
      <input type="text" id="video1" name="video1" placeholder="Paste YouTube embed code" required>

      <label for="video2">Video 2 (Optional - YouTube embedded code):</label>
      <input type="text" id="video2" name="video2" placeholder="Paste YouTube embed code">

      <label for="video3">Video 3 (Optional - YouTube embedded code):</label>
      <input type="text" id="video3" name="video3" placeholder="Paste YouTube embed code">

      <label for="images">Images (JPEG, JPG, PNG - Up to 3):</label>
      <input type="file" id="image1" name="images[]" accept="image/jpeg, image/png" onchange="validateImages()" multiple>

      <button type="submit" name="submit">Submit</button>
    </form>
    <div id="formMessage"></div>
  </div>

  <script src="scripts.js"></script>

  <script>
    function validateForm() {
      // Video validation: Ensure at least 1 YouTube video link is provided
      const video1 = document.getElementById('video1').value;
      if (!video1) {
        alert('Please provide at least one YouTube video embedded code.');
        return false;
      }

      // Image validation: Limit to 3 images only and validate file type
      const images = document.getElementById('image1').files;
      if (images.length === 0) {
        alert('Please provide at least one image.');
        return false;
      }
      if (images.length > 3) {
        alert('You can upload up to 3 images only.');
        return false;
      }

      // Check if each image is of a valid format (JPEG, JPG, PNG)
      for (let i = 0; i < images.length; i++) {
        const file = images[i];
        const fileType = file.type;
        if (fileType !== 'image/jpeg' && fileType !== 'image/png') {
          alert('Only JPEG, JPG, or PNG images are allowed.');
          return false;
        }
      }

      // Display success message and reset form after successful submission
      document.getElementById('formMessage').innerHTML = "Form submitted successfully!";
      return true;
    }

    // Limit to 3 images only
    function validateImages() {
      const imageInput = document.getElementById('image1');
      const files = imageInput.files;

      if (files.length > 3) {
        alert('You can upload up to 3 images only.');
        imageInput.value = ""; // Reset the file input
      }
    }
  </script>


</body>

</html>