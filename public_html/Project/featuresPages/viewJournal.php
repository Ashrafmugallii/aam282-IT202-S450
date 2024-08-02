<?php
require(__DIR__ . "/../../../partials/nav.php");

// Get the journal ID from the URL
$journal_id = $_GET['journal_id'];

// Fetch the journal details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM Journals WHERE id = :journal_id AND user_id = :user_id");
$stmt->bindParam(':journal_id', $journal_id);
$stmt->bindParam(':user_id', get_user_id());
$stmt->execute();
$journal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$journal) {
    flash("Journal not found or you do not have permission to view this journal.", "danger");
    header("Location: listJournals.php");
    exit;
}
?>

<div class="container-fluid">
    <h1><?php echo htmlspecialchars($journal['name']); ?></h1>
    <p><strong>From:</strong> <?php echo htmlspecialchars($journal['from_airport_code']); ?></p>
    <p><strong>To:</strong> <?php echo htmlspecialchars($journal['to_airport_code']); ?></p>
    <p><strong>Trip Dates:</strong> <?php echo htmlspecialchars($journal['trip_start_date']); ?> to <?php echo htmlspecialchars($journal['trip_end_date']); ?></p>
    <p><strong>Content:</strong> <?php echo nl2br(htmlspecialchars($journal['content'])); ?></p>
    <?php if ($journal['photos']): ?>
        <p><strong>Photos:</strong></p>
        <?php foreach (explode(',', $journal['photos']) as $photo): ?>
            <img src="<?php echo htmlspecialchars(trim($photo)); ?>" alt="Photo" class="img-thumbnail" />
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    // Fetch and display hotel details
    $hotel_details = json_decode($journal['hotel_details'], true);
    if (!empty($hotel_details)): ?>
        <h2>Hotel Details</h2>
        <?php foreach ($hotel_details as $hotel): ?>
            <div>
                <h3><?php echo htmlspecialchars($hotel['detail_name']); ?></h3>
                <p><?php echo htmlspecialchars($hotel['detail_description']); ?></p>
                <?php if ($hotel['detail_image_url']): ?>
                    <img src="<?php echo htmlspecialchars($hotel['detail_image_url']); ?>" alt="Hotel Image" class="img-thumbnail" />
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    // Fetch and display restaurant details
    $restaurant_details = json_decode($journal['restaurant_details'], true);
    if (!empty($restaurant_details)): ?>
        <h2>Restaurant Details</h2>
        <?php foreach ($restaurant_details as $restaurant): ?>
            <div>
                <h3><?php echo htmlspecialchars($restaurant['detail_name']); ?></h3>
                <p><?php echo htmlspecialchars($restaurant['detail_description']); ?></p>
                <?php if ($restaurant['detail_image_url']): ?>
                    <img src="<?php echo htmlspecialchars($restaurant['detail_image_url']); ?>" alt="Restaurant Image" class="img-thumbnail" />
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="editJournal.php?journal_id=<?php echo $journal['id']; ?>" class="btn btn-primary">Edit</a>
    <a href="deleteJournal.php?journal_id=<?php echo $journal['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this journal?');">Delete</a>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
