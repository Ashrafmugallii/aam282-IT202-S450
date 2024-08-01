<?php
require(__DIR__ . "/../../../lib/db.php");

if (isset($_GET['user_id'])) {
    $followed_id = $_GET['user_id'];
    $follower_id = get_user_id();

    $db = getDB();

    // Check if the user is already following
    $stmt = $db->prepare("SELECT * FROM Follows WHERE follower_id = :follower_id AND followed_id = :followed_id");
    $stmt->bindParam(':follower_id', $follower_id);
    $stmt->bindParam(':followed_id', $followed_id);
    $stmt->execute();
    $follow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($follow) {
        // Unfollow
        $stmt = $db->prepare("DELETE FROM Follows WHERE follower_id = :follower_id AND followed_id = :followed_id");
    } else {
        // Follow
        $stmt = $db->prepare("INSERT INTO Follows (follower_id, followed_id) VALUES (:follower_id, :followed_id)");
    }
    $stmt->bindParam(':follower_id', $follower_id);
    $stmt->bindParam(':followed_id', $followed_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
