<?php 
// report_posts.php
include("../../connection/config.php");

$sql = "
    SELECT p.post_id, p.content, p.media_url, COUNT(cr.report_id) AS report_count
    FROM posts p
    LEFT JOIN communityreports cr ON p.post_id = cr.post_id
    GROUP BY p.post_id
    HAVING COUNT(cr.report_id) >= 1;
";
$result = mysqli_query($conn, $sql);

$reported_posts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reported_posts[] = $row;
}

echo json_encode($reported_posts);

?>