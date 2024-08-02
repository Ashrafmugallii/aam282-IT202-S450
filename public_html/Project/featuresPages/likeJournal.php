<?php
require(__DIR__ . "/../../../lib/db.php");

if (isset($_GET['journal_id'])) {
    $journal_id = $_GET['journal_id'];
    $user_id = get_user_id();

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO Likes (user_id, journal_id) VALUES (:user_id, :journal_id) ON DUPLICATE KEY UPDATE id = id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':journal_id', $journal_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
