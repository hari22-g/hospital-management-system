<?php
session_start();
include('../connection/config.php');  // Include your database connection
$sql = "SELECT * FROM communities WHERE community_id = $_GET[c_id]";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $row['name'] ?>   Support Group</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap 5 JavaScript Bundle (Includes Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (Required for AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="BT_community.css">
</head>

<body>
    <?php
    include("../include/navbar.php"); ?>

    <main>
        <div class="tabs">
            <button class="tab-btn active" data-tab="about">About Community</button>
            <button class="tab-btn" data-tab="discussions">Discussions</button>
        </div>

        <!-- About Community Section -->
        <section id="about" class="tab-content active">
            <div class="about-community">
                <h2>Welcome to the
                    <?php
                    echo $row['name'];
                    ?> Support</h2>
                <p>
                    This community is a safe space for individuals affected by brain tumors, including patients, caregivers, and loved ones.
                    Here, you can share your experiences, ask questions, and find support from others who understand your journey.
                </p>
                <p>
                    Our mission is to provide a platform for connection, education, and empowerment. Together, we can navigate the challenges
                    of brain tumors and support each other every step of the way.
                </p>
                <div class="admin-info">
                    <h3>Admin Message</h3>
                    <p>
                        "As the admin of this community, I am here to ensure that this remains a respectful and supportive environment for everyone.
                        Please feel free to reach out if you have any questions or concerns."
                    </p>
                </div>
            </div>
        </section>

        <!-- Discussions Section -->
        <section id="discussions" class="tab-content">
            <div class="discussion-header">
                <h2>Discussions</h2>

                <div class="buttons">
                    <button class="btn btn-primary" id="refresh">Refresh</button>
                    <?php
                    if (isset($_SESSION['logged_in'])) {
                        echo '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#post_form">
                    Add Post
                </button>';
                    } else {
                        echo '<button type="button" class="btn btn-primary" onclick="window.location.href=\'' . $localhost . 'login.php\'">Add Post</button>';
                    }
                    ?>
                </div>
            </div>


            <!-- Image Post -->
            <div class="post">
                <!-- Posts will be loaded here by AJAX -->
            </div>
        </section>

        <footer class="footer">
            <!-- Add post pop-up -->
            <div class="modal fade" id="post_form" tabindex="-1" aria-labelledby="addPost" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-dark" id="addPost">Add Post</h5>
                            <button type="button" class="btn-close post_cls_btn" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <?php
                        include("post_form.php");
                        ?>
                    </div>
                </div>
            </div>

            <p>&copy; 2025 MedC</p>
        </footer>

        <!-- Bootstrap 5 Bundle (Includes Popper.js) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <!-- jQuery (For AJAX) -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

        <script src="../include/jquery.js"></script>
        <script src="BT_community.js"></script>
        <script>
            $(document).ready(function() {
                // Function to load posts via AJAX
                function loadPosts() {
                    var community_id = <?php echo isset($_GET['c_id']) ? $_GET['c_id'] : 0; ?>;
                    $.ajax({
                        url: "load_posts.php",
                        data: {
                            community_id: community_id
                        },
                        type: "POST",
                        success: function(data) {
                            console.log(data); // Debug the response
                            $(".post").html(data); // Update the posts section with the data
                        },
                        error: function(xhr, status, error) {
                            console.error("Error loading posts: ", error);
                        }
                    });
                }

                // Call the loadPosts function initially to load posts on page load
                loadPosts();

                // Event listener for the refresh button
                $("#refresh").on("click", function(e) {
                    e.preventDefault(); // Prevent default button behavior
                    loadPosts(); // Reload posts when the Refresh button is clicked
                });

                // Event listener for the add post button (inside modal)
                $("#addPostBtn").on("click", function(e) {
                    e.preventDefault(); // Prevent default form submission

                    var community_id = <?php echo isset($_GET['c_id']) ? $_GET['c_id'] : 0; ?>;
                    var user_id = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>; // Fetch user ID from PHP
                    var fname = <?php echo isset($_SESSION['f_name']) ? "'" . $_SESSION['f_name'] . "'" : "'Guest'"; ?>; // Fetch first name from PHP
                    var lname = <?php echo isset($_SESSION['l_name']) ? "'" . $_SESSION['l_name'] . "'" : "'User'"; ?>; // Fetch last name from PHP
                    var content = $("#content").val(); // Get the content from the textarea
                    var media_url = $("#media_url").val(); // Get the media URL (file input)

                    // ✅ Condition: Ensure either content or media is provided
                    if (content === "" && !media_url) {
                        alert("Please enter content or upload an image before submitting.");
                        return; // Stop execution if both fields are empty
                    }

                    // Form data including the file upload
                    var formData = new FormData();
                    formData.append("user_id", user_id);
                    formData.append("community_id", community_id);
                    formData.append("fname", fname);
                    formData.append("lname", lname);
                    formData.append("content", content);
                    formData.append("media_url", $("input[name='media_url']")[0].files[0]); // Appending the file

                    // Make the AJAX request to send the data
                    $.ajax({
                        url: "add_post_query.php", // The PHP file that handles the post submission
                        type: "POST",
                        data: formData,
                        contentType: false, // Don't set content type
                        processData: false, // Don't process data (important for file upload)
                        success: function(data) {
                            if (data == 1) {
                                loadPosts(); // Reload posts after successful submission
                                $("#post_form form")[0].reset(); // Reset the form fields inside the modal
                                $(".post_cls_btn").click(); // Close modal
                            } else {
                                alert("Post submission failed!");
                            }
                        },
                        error: function() {
                            alert("An error occurred while submitting the post.");
                        }
                    });
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                // Like Button Click Event
                $(document).on("click", ".like-btn", function() {
                    let postId = $(this).data("post-id"); // Corrected attribute
                    let likeBtn = $(this);
                    let likeCountElement = $("#like-count-" + postId);

                    console.log("Clicked Like for Post ID:", postId); // Debugging

                    $.ajax({
                        url: "like.php",
                        type: "POST",
                        data: {
                            post_id: postId
                        },
                        dataType: "json",
                        success: function(response) {
                            console.log("Server Response:", response); // Debugging

                            if (response.status === "liked") {
                                likeBtn.addClass("liked");
                            } else if (response.status === "unliked") {
                                likeBtn.removeClass("liked");
                            }

                            if (likeCountElement.length > 0) {
                                likeCountElement.text(response.like_count); // Update like count
                            }
                        },
                        error: function(xhr) {
                            console.error("AJAX Error:", xhr.responseText);
                        }
                    });
                });

                // Report Button Click Event
                $(document).on("click", ".report-btn", function() {
                    // Clear the input field
                    $("#reportReason").val("");
                    let postId = $(this).data("post-id");
                    $("#reportPostId").val(postId);
                });

                // Submit Report via AJAX
                $(document).on("click", "#submitReport", function() {
                    let postId = $("#reportPostId").val();
                    let reason = $("#reportReason").val().trim();

                    if (reason === "") {
                        alert("Please enter a reason for reporting.");
                        return;
                    }

                    $.ajax({
                        url: "report.php",
                        type: "POST",
                        data: {
                            post_id: postId,
                            reason: reason
                        },
                        dataType: "json",
                        success: function(response) {
                            console.log("Report Response:", response);

                            if (response.status === "success") {
                                alert("Report submitted successfully.");
                                $("#reportReason").val("");
                                $(".cls_btn").click(); // Close modal
                            } else if (response.status === "already_reported") {
                                alert("You have already reported this post.");
                            } else {
                                alert("Error: " + response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error("AJAX Error:", xhr.responseText);
                            alert("An error occurred while submitting the report.");
                        }
                    });
                });
            });
        </script>

</body>

</html>