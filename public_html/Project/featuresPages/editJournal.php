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
    flash("Journal not found or you do not have permission to edit this journal.", "danger");
    header("Location: listJournals.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $from_airport_code = $_POST['from_airport_code'];
    $to_airport_code = $_POST['to_airport_code'];
    $trip_start_date = $_POST['trip_start_date'];
    $trip_end_date = $_POST['trip_end_date'];
    $content = $_POST['content'];
    $photos = $_POST['photos']; // Assuming photos are uploaded and processed separately

    // Validate inputs
    $errors = [];
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($from_airport_code)) {
        $errors[] = "From airport code is required.";
    }
    if (empty($to_airport_code)) {
        $errors[] = "To airport code is required.";
    }
    if (empty($trip_start_date) || empty($trip_end_date)) {
        $errors[] = "Trip dates are required.";
    }
    if (empty($content)) {
        $errors[] = "Content is required.";
    }

    // If no errors, update the database
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE Journals SET name = :name, from_airport_code = :from_airport_code, to_airport_code = :to_airport_code, trip_start_date = :trip_start_date, trip_end_date = :trip_end_date, content = :content, photos = :photos, modified = NOW() WHERE id = :journal_id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':from_airport_code', $from_airport_code);
        $stmt->bindParam(':to_airport_code', $to_airport_code);
        $stmt->bindParam(':trip_start_date', $trip_start_date);
        $stmt->bindParam(':trip_end_date', $trip_end_date);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':photos', $photos);
        $stmt->bindParam(':journal_id', $journal_id);

        if ($stmt->execute()) {
            flash("Journal updated successfully.", "success");
            header("Location: viewJournal.php?journal_id=$journal_id");
            exit;
        } else {
            flash("Error updating journal.", "danger");
        }
    } else {
        foreach ($errors as $error) {
            flash($error, "danger");
        }
    }
}

// Fetch cached airports for dropdown
$stmt = $db->prepare("SELECT code, display_name FROM AirportCache");
$stmt->execute();
$airports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1>Edit Journal</h1>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Journal Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($journal['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="from_airport_dropdown" class="form-label">From Location</label>
            <select class="form-select" id="from_airport_dropdown" name="from_airport_code" onchange="setFromAirportCode()">
                <option value="">Select from existing airports</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo $airport['code']; ?>" <?php if ($journal['from_airport_code'] == $airport['code']) echo 'selected'; ?>>
                        <?php echo $airport['display_name']; ?> (<?php echo $airport['code']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" class="form-control mt-2" id="from_location" name="from_location" placeholder="Or type location...">
            <button type="button" class="btn btn-primary mt-2" onclick="fetchFromAirports()">Fetch Airports</button>
            <input type="hidden" id="from_airport_code" name="from_airport_code" value="<?php echo htmlspecialchars($journal['from_airport_code']); ?>">
            <div id="from_airport_list"></div>
        </div>
        <div class="mb-3">
            <label for="to_airport_dropdown" class="form-label">To Location</label>
            <select class="form-select" id="to_airport_dropdown" name="to_airport_code" onchange="setToAirportCode()">
                <option value="">Select from existing airports</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo $airport['code']; ?>" <?php if ($journal['to_airport_code'] == $airport['code']) echo 'selected'; ?>>
                        <?php echo $airport['display_name']; ?> (<?php echo $airport['code']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" class="form-control mt-2" id="to_location" name="to_location" placeholder="Or type location...">
            <button type="button" class="btn btn-primary mt-2" onclick="fetchToAirports()">Fetch Airports</button>
            <input type="hidden" id="to_airport_code" name="to_airport_code" value="<?php echo htmlspecialchars($journal['to_airport_code']); ?>">
            <div id="to_airport_list"></div>
        </div>
        <div class="mb-3">
            <label for="trip_start_date" class="form-label">Trip Start Date</label>
            <input type="date" class="form-control" id="trip_start_date" name="trip_start_date" value="<?php echo htmlspecialchars($journal['trip_start_date']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="trip_end_date" class="form-label">Trip End Date</label>
            <input type="date" class="form-control" id="trip_end_date" name="trip_end_date" value="<?php echo htmlspecialchars($journal['trip_end_date']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="content" class="form-label">Content</label>
            <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($journal['content']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="photos" class="form-label">Photos</label>
            <input type="text" class="form-control" id="photos" name="photos" value="<?php echo htmlspecialchars($journal['photos']); ?>" placeholder="Enter photo URLs separated by commas">
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-success">Update Journal</button>
            <a href="addJournalDetails.php?journal_id=<?php echo $journal_id; ?>" class="btn btn-primary">Add Journal Details</a>
        </div>
    </form>
</div>

<script>
function setFromAirportCode() {
    var dropdown = document.getElementById('from_airport_dropdown');
    var code = dropdown.value;
    document.getElementById('from_airport_code').value = code;
}

function setToAirportCode() {
    var dropdown = document.getElementById('to_airport_dropdown');
    var code = dropdown.value;
    document.getElementById('to_airport_code').value = code;
}

function fetchFromAirports() {
    var query = document.getElementById('from_location').value;
    if (query.length >= 3) {
        fetch('fetchAirports.php?query=' + query + '&type=from')
            .then(response => response.json())
            .then(data => {
                var fromAirportList = document.getElementById('from_airport_list');
                fromAirportList.innerHTML = '';
                data.forEach(airport => {
                    var option = document.createElement('div');
                    option.textContent = `${airport.name} (${airport.airportCode})`;
                    option.addEventListener('click', function() {
                        document.getElementById('from_location').value = airport.name;
                        document.getElementById('from_airport_code').value = airport.airportCode;
                        fromAirportList.innerHTML = '';
                    });
                    fromAirportList.appendChild(option);
                });
            });
    }
}

function fetchToAirports() {
    var query = document.getElementById('to_location').value;
    if (query.length >= 3) {
        fetch('fetchAirports.php?query=' + query + '&type=to')
            .then(response => response.json())
            .then(data => {
                var toAirportList = document.getElementById('to_airport_list');
                toAirportList.innerHTML = '';
                data.forEach(airport => {
                    var option = document.createElement('div');
                    option.textContent = `${airport.name} (${airport.airportCode})`;
                    option.addEventListener('click', function() {
                        document.getElementById('to_location').value = airport.name;
                        document.getElementById('to_airport_code').value = airport.airportCode;
                        toAirportList.innerHTML = '';
                    });
                    toAirportList.appendChild(option);
                });
            });
    }
}
</script>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
