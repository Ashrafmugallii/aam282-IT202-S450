
<?php
require(__DIR__ . "/../../../partials/nav.php");

// Get the journal id from the URL
$journal_id = $_GET['journal_id'];

// Fetch the journal details to see if it exists and belongs to the logged-in user
$db = getDB();
$stmt = $db->prepare("SELECT * FROM Journals WHERE id = :journal_id AND user_id = :user_id");
$stmt->bindParam(':journal_id', $journal_id);
$stmt->bindParam(':user_id', get_user_id());
$stmt->execute();
$journal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$journal) {
    flash("Journal not found or you do not have permission to delete this journal.", "danger");
    header("Location: listJournals.php");
    exit;
}

// Handle deletion
$stmt = $db->prepare("DELETE FROM Journals WHERE id = :journal_id");
$stmt->bindParam(':journal_id', $journal_id);
if ($stmt->execute()) {
    flash("Journal deleted successfully.", "success");
} else {
    flash("Error deleting journal.", "danger");
}

header("Location: listJournals.php");
exit;
?>
