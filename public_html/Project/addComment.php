<?php
require(__DIR__ . "/../../../lib/db.php");

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = get_user_id();
    $journal_id = $data['journal_id'];
    $comment = $data['comment'];

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO Comments (user_id, journal_id, content, created_at) VALUES (:user_id, :journal_id, :content, NOW())");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':journal_id', $journal_id, PDO::PARAM_INT);
    $stmt->bindParam(':content', $comment, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $response['success'] = true;
    }
}

echo json_encode($response);
?>
