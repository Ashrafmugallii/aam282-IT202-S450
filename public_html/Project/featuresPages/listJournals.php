<?php
require(__DIR__ . "/../../../partials/nav.php");

// Get the logged-in user ID
$user_id = get_user_id();

// Fetch journals created by the logged-in user
$db = getDB();
$stmt = $db->prepare("SELECT * FROM Journals WHERE user_id = :user_id ORDER BY created DESC");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1>Your Journals</h1>
    <?php if (count($journals) > 0): ?>
        <ul class="list-group">
            <?php foreach ($journals as $journal): ?>
                <li class="list-group-item">
                    <a href="viewJournal.php?journal_id=<?php echo $journal['id']; ?>">
                        <?php echo htmlspecialchars($journal['name']); ?>
                    </a>
                    <span class="float-right">
                        <a href="editJournal.php?journal_id=<?php echo $journal['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="deleteJournal.php?journal_id=<?php echo $journal['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this journal?');">Delete</a>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No journals found. <a href="createJournal.php">Create a new journal</a></p>
    <?php endif; ?>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
