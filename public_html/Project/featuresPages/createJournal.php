<?php
require(__DIR__ . "/../../../partials/nav.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = get_user_id(); // Function to get the logged-in user ID
    $title = $_POST['title'];
    $destination_id = $_POST['destination_id'];
    $entry_date = $_POST['entry_date'];
    $content = $_POST['content'];
    $photos = $_POST['photos']; // Assuming photos are uploaded and processed separately

    // Validate inputs
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($destination_id)) {
        $errors[] = "Destination is required.";
    }
    if (empty($entry_date)) {
        $errors[] = "Entry date is required.";
    }
    if (empty($content)) {
        $errors[] = "Content is required.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Journals (user_id, title, destination_id, entry_date, content, photos, created, modified) VALUES (:user_id, :title, :destination_id, :entry_date, :content, :photos, NOW(), NOW())");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':destination_id', $destination_id);
        $stmt->bindParam(':entry_date', $entry_date);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':photos', $photos);
        
        if ($stmt->execute()) {
            flash("Journal created successfully.", "success");
        } else {
            flash("Error creating journal.", "danger");
        }
    } else {
        foreach ($errors as $error) {
            flash($error, "danger");
        }
    }
}
?>

<div class="container-fluid">
    <h1>Create Journal</h1>
    <form method="POST">
        <div>
            <label>Title</label>
            <input type="text" name="title" required />
        </div>
        <div>
            <label>Destination</label>
            <select name="destination_id" required>
                <!-- Populate options from the Destinations table -->
                <?php
                $db = getDB();
                $stmt = $db->prepare("SELECT id, name FROM Destinations");
                $stmt->execute();
                $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($destinations as $destination) {
                    echo "<option value='" . $destination['id'] . "'>" . $destination['name'] . "</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label>Entry Date</label>
            <input type="date" name="entry_date" required />
        </div>
        <div>
            <label>Content</label>
            <textarea name="content" required></textarea>
        </div>
        <div>
            <label>Photos</label>
            <input type="text" name="photos" placeholder="Enter photo URLs separated by commas" />
        </div>
        <div>
            <input type="submit" value="Create Journal" />
        </div>
    </form>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
