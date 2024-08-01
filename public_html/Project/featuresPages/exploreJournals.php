<?php
require(__DIR__ . "/../../../partials/nav.php");

$db = getDB();
$user_id = get_user_id();

// Fetch all journals
$stmt = $db->prepare("SELECT Journals.*, Users.username FROM Journals JOIN Users ON Journals.user_id = Users.id");
$stmt->execute();
$journals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch followers
$follow_stmt = $db->prepare("SELECT followed_id FROM Follows WHERE follower_id = :user_id");
$follow_stmt->bindParam(':user_id', $user_id);
$follow_stmt->execute();
$following = $follow_stmt->fetchAll(PDO::FETCH_COLUMN);

?>

<div class="container mt-5">
    <h1>Explore Journals</h1>
    <div class="row">
        <?php foreach ($journals as $journal): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <img src="path/to/profile-pic.jpg" alt="Profile Picture" class="rounded-circle me-2" style="width: 40px; height: 40px;">
                        <strong><?php echo htmlspecialchars($journal['username']); ?></strong>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($journal['name']); ?></h5>
                        <p class="card-text">
                            <strong>From:</strong> <?php echo htmlspecialchars($journal['from_airport_code']); ?><br>
                            <strong>To:</strong> <?php echo htmlspecialchars($journal['to_airport_code']); ?><br>
                            <strong>Trip Dates:</strong> <?php echo htmlspecialchars($journal['trip_start_date']); ?> to <?php echo htmlspecialchars($journal['trip_end_date']); ?><br>
                            <strong>Summary:</strong> <?php echo nl2br(htmlspecialchars($journal['content'])); ?>
                        </p>
                        <?php if ($journal['photos']): ?>
                            <?php $photos = explode(',', $journal['photos']); ?>
                            <div id="carousel-<?php echo $journal['id']; ?>" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach ($photos as $index => $photo): ?>
                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                            <img src="<?php echo htmlspecialchars(trim($photo)); ?>" class="d-block w-100 img-thumbnail" alt="Photo">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($photos) > 1): ?>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo $journal['id']; ?>" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo $journal['id']; ?>" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button class="btn btn-primary like-button" data-journal-id="<?php echo $journal['id']; ?>">Like</button>
                            <button class="btn btn-secondary comment-button" data-journal-id="<?php echo $journal['id']; ?>">Comment</button>
                            <?php if (in_array($journal['user_id'], $following)): ?>
                                <button class="btn btn-danger follow-button" data-user-id="<?php echo $journal['user_id']; ?>">Unfollow</button>
                            <?php else: ?>
                                <button class="btn btn-success follow-button" data-user-id="<?php echo $journal['user_id']; ?>">Follow</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentModalLabel">Add Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="commentForm">
                    <div class="mb-3">
                        <label for="commentContent" class="form-label">Comment</label>
                        <textarea class="form-control" id="commentContent" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Like button functionality
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', function() {
            const journalId = this.getAttribute('data-journal-id');
            fetch(`likeJournal.php?journal_id=${journalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Journal liked successfully!');
                    } else {
                        alert('Failed to like journal.');
                    }
                });
        });
    });

    // Comment button functionality
    document.querySelectorAll('.comment-button').forEach(button => {
        button.addEventListener('click', function() {
            const journalId = this.getAttribute('data-journal-id');
            const commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
            document.getElementById('commentForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const content = document.getElementById('commentContent').value;
                fetch(`commentJournal.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ journal_id: journalId, content: content })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Comment added successfully!');
                        commentModal.hide();
                    } else {
                        alert('Failed to add comment.');
                    }
                });
            });
            commentModal.show();
        });
    });

    // Follow/Unfollow button functionality
    document.querySelectorAll('.follow-button').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            fetch(`followUser.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User follow/unfollow action successful!');
                        location.reload();
                    } else {
                        alert('Failed to follow/unfollow user.');
                    }
                });
        });
    });
});
</script>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
