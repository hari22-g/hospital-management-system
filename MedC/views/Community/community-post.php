<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Community Threads</title>
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 20px;
            padding: 10px;
        }

        .post {
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .post-info {
            font-size: 0.9rem;
        }

        .post-info .name {
            font-weight: bold;
            color: #333;
        }

        .post-info .time {
            color: gray;
        }

        .join-button {
            background-color: #0077b6;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .join-button:hover {
            background-color: #005f87;
        }

        .post-content {
            font-size: 1rem;
            margin-bottom: 15px;
            color: #555;
        }

        .post-media {
            margin-bottom: 15px;
        }

        .post-actions {
            display: flex;
            align-items: center;
        }

        .post-actions button {
            background-color: transparent;
            border: none;
            font-size: 0.9rem;
            color: #0077b6;
            /* cursor: pointer; */
            display: flex;
            align-items: center;
        }

        .post-actions button:hover {
            text-decoration: underline;
        }

        .post-actions button img {
            width: 20px;
            height: 20px;
            margin-right: 5px;
        }

        .like-count {
            color: #555;
        }

        i {
            margin: 10px;
        }

        .post-actions button {
            padding: 3px 4px;
            margin: 5px;
        }

        .post-actions button:hover {
            text-decoration: none;
            /* cursor: pointer; */

        }

        /* .post-actions i:hover {
            /* cursor: pointer; */


        .comments {
            display: none;
            margin-top: 10px;
            background: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
        }

        hr {
            margin-top: 1px;
            margin-bottom: 3px;
        }

        .comment .name {
            font-weight: bold;
            color: #333;
        }

        p {
            margin-top: 0;
            margin-bottom: 1rem;
            margin-left: 50px;
        }

        div.comment {
            padding: 0px;
        }
        .post .post-actions .like-btn:hover {
            cursor: pointer;
        }
        .post .post-actions .comment-btn:hover {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Text Post -->
        <div class="post">
            <div class="post-header">
                <div class="user-info">
                    <div class="profile-pic"
                        style="background-color: rgb(213, 213, 238); border:1px solid black; border-radius: 50%; margin-right: 10px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="post-info">
                        <span class="name">Fitness Guru</span>
                        <br>
                        <span class="time">2 hours ago</span>
                    </div>
                </div>
            </div>
            <div class="post-content">
                "Remember to drink water throughout the day! Hydration is key to maintaining your energy levels and
                overall health."
            </div>
            <hr>
            <div class="post-actions">
                <i class="fa-solid fa-thumbs-up fa-lg like-btn" style="color: #00008b;"></i>
                <i class="fa-solid fa-comment fa-lg comment-btn" style="color: #00008b;"></i>
                <button class="btn report-btn"
                    style="background-color:darkblue; color:white; border: 1px solid darkblue; border-radius: 15px; margin-left: auto;padding: 5px 10px;">Report</button>
            </div>
            <div class="comments">
                <h3>comments</h3>
                <div class="comment">
                    <div class="profile-pic"
                        style=" height: 35px; width: 35px;background-color: rgb(213, 213, 238); border:1px solid black; border-radius: 50%; margin-right: 2px;display: inline-block;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="name">Khushi Patel</span>
                    <p> Great reminder, I always forget!</p>
                </div>
                <div class="comment">
                    <div class="profile-pic"
                        style=" height: 35px; width: 35px;background-color: rgb(213, 213, 238); border:1px solid black; border-radius: 50%; margin-right: 2px;display: inline-block;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="name">Preeti Vaza</span>
                    <p>Staying hydrated has improved my focus!</p>
                </div>
            </div>
        </div>

        <!-- Image Post -->
        <div class="post">
            <div class="post-header">
                <div class="user-info">
                    <div class="profile-pic"
                        style="height: 35px;
            width: 35px;background-color: rgb(213, 213, 238); border:1px solid black; border-radius: 50%; margin-right: 10px;display: inline-block;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="post-info">
                        <span class="name">Healthy Chef</span>
                        <br>
                        <span class="time">1 day ago</span>
                    </div>
                </div>
            </div>
            <div class="post-media">
                <img src="com_post.jpg" alt="Healthy Food" style="width: 100%; border-radius: 10px;">
            </div>
            <div class="post-content">
                "Check out this delicious and nutritious salad recipe! Perfect for a light lunch."
            </div>
            <hr>
            <div class="post-actions">
                <i class="fa-solid fa-thumbs-up fa-lg like-btn" style="color: #00008b;"></i>
                <i class="fa-solid fa-comment fa-lg comment-btn" style="color: #00008b;"></i>
                <button class="btn report-btn"
                    style="background-color:darkblue; color:white; border: 1px solid darkblue; border-radius: 10px; margin-left: auto; ">Report</button>
            </div>
            <div class="comments">
                <h3>comments</h3>
                <div class="comment">
                    <div class="profile-pic"
                        style="height: 35px; width: 35px;background-color: rgb(213, 213, 238); border:1px solid black; border-radius: 50%; margin-right: 2px;display: inline-block;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="name">Aditi Kanojiya</span>
                    <p>Looks delicious! I will try this.</p>
                </div>
                <div class="comment">
                    <div class="profile-pic"
                        style="height: 35px; width: 35px;background-color: rgb(213, 213, 238); border:1px solid black; border-radius: 50%; margin-right:2px;display: inline-block;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span class="name">Keni Patel</span>
                    <p>Do you have a vegan alternative?</p>
                </div>
            </div>
        </div>


    </div>

    <script>
        // Function to toggle comments visibility
        document.querySelectorAll('.fa-comment').forEach(icon => {
            icon.addEventListener('click', function () {
                // Find the closest parent post and then find the comments section within it
                const post = this.closest('.post');
                const comments = post.querySelector('.comments');
                // Toggle the display of the comments section
                comments.style.display = comments.style.display === 'block' ? 'none' : 'block';
            });
        });

        // Example functions for like/dislike (already in your code)
        function likePost(button) {
            const likeCountSpan = button.parentElement.querySelector('.like-count');
            let count = parseInt(likeCountSpan.textContent.split(' ')[0]);
            count++;
            likeCountSpan.textContent = `${count} Likes`;
        }

        function dislikePost(button) {
            const likeCountSpan = button.parentElement.querySelector('.like-count');
            let count = parseInt(likeCountSpan.textContent.split(' ')[0]);
            if (count > 0) {
                count--;
            }
            likeCountSpan.textContent = `${count} Likes`;
        }
    </script>


</body>

</html>