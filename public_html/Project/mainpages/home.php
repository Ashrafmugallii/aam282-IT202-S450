<?php
require(__DIR__ . "/../../../partials/nav.php");

if (is_logged_in()) {
    $user_id = get_user_id();
    $username = get_username();
    $profile_pic = $_SESSION["user"]["profile_pic"] ?? "/uploads/a348574239c899a8509210c2304c2ba5.jpg";

    // Fetch latest journals
    $db = getDB();
    $stmt = $db->prepare("SELECT Journals.id, Journals.name, Journals.content, Journals.photos, Users.username, Users.profile_pic FROM Journals JOIN Users ON Journals.user_id = Users.id ORDER BY Journals.created DESC LIMIT 5");
    $stmt->execute();
    $latest_journals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user statistics
    $stmt = $db->prepare("SELECT COUNT(*) as journal_count FROM Journals WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $journal_count = $stmt->fetch(PDO::FETCH_ASSOC)['journal_count'];

    $stmt = $db->prepare("SELECT COUNT(*) as followers_count FROM Follows WHERE followed_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $followers_count = $stmt->fetch(PDO::FETCH_ASSOC)['followers_count'];

    $stmt = $db->prepare("SELECT COUNT(*) as following_count FROM Follows WHERE follower_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $following_count = $stmt->fetch(PDO::FETCH_ASSOC)['following_count'];
}
?>

<div class="container mt-5">
    <?php if (is_logged_in()): ?>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="rounded-circle me-3" style="width: 60px; height: 60px;">
                        <div>
                            <h4 class="card-title">Welcome, <?php echo htmlspecialchars($username); ?>!</h4>
                            <p class="card-text">Here are your latest activities and stats.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card text-white bg-primary mb-3 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Your Statistics</h5>
                        <p class="card-text">Journals Created: <?php echo $journal_count; ?></p>
                        <p class="card-text">Followers: <?php echo $followers_count; ?></p>
                        <p class="card-text">Following: <?php echo $following_count; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <h3>Latest Journals</h3>
                <div id="latestJournalsCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($latest_journals as $index => $journal): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="card">
                                    <div class="card-header d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($journal['profile_pic'] ?: '/../mainpages/uploads/default-profile-pic.jpg'); ?>" alt="Profile Picture" class="rounded-circle me-2" style="width: 40px; height: 40px;">
                                        <strong><?php echo htmlspecialchars($journal['username']); ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($journal['name']); ?></h5>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($journal['content'], 0, 100))) . '...'; ?></p>
                                        <a href="viewJournal.php?journal_id=<?php echo $journal['id']; ?>" class="btn btn-primary">Read More</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#latestJournalsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#latestJournalsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            You're not logged in. Please <a href="login.php" class="alert-link">log in</a> to see your personalized home page.
        </div>
    <?php endif; ?>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
