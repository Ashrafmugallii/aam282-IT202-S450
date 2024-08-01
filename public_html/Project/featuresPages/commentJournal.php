<?php
require(__DIR__ . "/../../../lib/db.php");

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['journal_id']) && isset($data['content'])) {
    $journal_id = $data['journal_id'];
    $content = $data['content'];
    $user_id = get_user_id();

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO Comments (user_id, journal_id, content) VALUES (:user_id, :journal_id, :content)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':journal_id', $journal_id);
    $stmt->bindParam(':content', $content);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
