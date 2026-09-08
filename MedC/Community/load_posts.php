<?php
include("../connection/config.php");
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

$post_sql = "SELECT posts.*, 
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.post_id) AS like_count,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.post_id AND likes.user_id = ?) AS user_liked
            FROM posts 
            WHERE community_id = ? 
            ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $post_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $_POST['community_id']);
mysqli_stmt_execute($stmt);
$post_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($post_result) > 0) {
    while ($post_row = mysqli_fetch_assoc($post_result)) {
        $likedClass = ($post_row['user_liked'] > 0) ? "liked" : ""; // Check if user liked
        $media = $post_row["media_url"];
        $content = trim($post_row['content']); // Remove extra spaces

        echo '<div class="post" data-post-id="' . $post_row['post_id'] . '">
            <div class="post-header">
                <div class="user-info">
                    <div class="profile-pic">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="post-info">
                        <span class="name">' . $post_row['fname'] . " " .  $post_row['lname'] . '</span>
                        <br>    
                        <span class="time">' . timeAgo($post_row['created_at']) . '</span>
                    </div>
                </div>
            </div>';

        // ✅ Show Media Only If Available
        if (!empty($media)) {
            echo '<div class="post-media">
                    <img src="../uploads/Posts/' . $media . '" alt="Post Image">
                  </div>';
        }

        // ✅ Show Content Only If Available
        if (!empty($content)) {
            echo '<div class="post-content">
                    ' . $content . '
                  </div>';
        }

        echo '<hr>
            <div class="post-actions">
                <i class="fa-solid fa-thumbs-up fa-lg like-btn ' . $likedClass . '" data-post-id="' . $post_row['post_id'] . '"></i>
                <span id="like-count-' . $post_row['post_id'] . '">' . $post_row['like_count'] . '</span>
                <button class="btn btn-danger report-btn" data-post-id="' . $post_row['post_id'] . '" data-bs-toggle="modal" data-bs-target="#reportModal">Report</button>
            </div>
            <hr>
        </div>';
    }
} else {
    echo '<p class="text-danger fs-1">Be the first to add the post</p>';
}

// Time ago function
function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return $diff . " seconds ago";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " minutes ago";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " hours ago";
    } elseif ($diff < 2592000) { // 30 days
        return floor($diff / 86400) . " days ago";
    } elseif ($diff < 31536000) { // 12 months
        return floor($diff / 2592000) . " months ago";
    } else {
        return floor($diff / 31536000) . " years ago";
    }
}
?>

<!-- Report Modal (Moved Outside the Loop) -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Report Post</h5>
                <button type="button" class="btn-close cls_btn" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reportPostId">
                <label for="reportReason">Reason for reporting:</label>
                <textarea id="reportReason" class="form-control" rows="3" placeholder="Enter your reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="submitReport">Submit Report</button>
            </div>
        </div>
    </div>
</div>