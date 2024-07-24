<?php
require(__DIR__ . "/../../../partials/nav.php");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = get_user_id(); // Function to get the logged-in user ID
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

    // If no errors, insert into database
    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Journals (user_id, name, from_airport_code, to_airport_code, trip_start_date, trip_end_date, content, photos, created, modified) VALUES (:user_id, :name, :from_airport_code, :to_airport_code, :trip_start_date, :trip_end_date, :content, :photos, NOW(), NOW())");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':from_airport_code', $from_airport_code);
        $stmt->bindParam(':to_airport_code', $to_airport_code);
        $stmt->bindParam(':trip_start_date', $trip_start_date);
        $stmt->bindParam(':trip_end_date', $trip_end_date);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':photos', $photos);

        if ($stmt->execute()) {
            $journal_id = $db->lastInsertId();
            header("Location: addJournalDetails.php?journal_id=$journal_id");
            exit;
        } else {
            flash("Error creating journal.", "danger");
        }
    } else {
        foreach ($errors as $error) {
            flash($error, "danger");
        }
    }
}

// Fetch cached airports for dropdown
$db = getDB();
$stmt = $db->prepare("SELECT code, display_name FROM AirportCache");
$stmt->execute();
$airports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h1>Create Journal</h1>
    <form method="POST">
        <div>
            <label>Journal Name</label>
            <input type="text" name="name" required />
        </div>
        <div>
            <label>From Location</label>
            <select id="from_airport_dropdown" name="from_airport_code_dropdown" onchange="setFromAirportCode()">
                <option value="">Select from existing airports</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo $airport['code']; ?>"><?php echo $airport['display_name']; ?> (<?php echo $airport['code']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="from_location" name="from_location" placeholder="Or type location..." />
            <button type="button" onclick="fetchFromAirports()">Fetch Airports</button>
            <input type="hidden" id="from_airport_code" name="from_airport_code" />
            <div id="from_airport_list"></div>
        </div>
        <div>
            <label>To Location</label>
            <select id="to_airport_dropdown" name="to_airport_code_dropdown" onchange="setToAirportCode()">
                <option value="">Select from existing airports</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo $airport['code']; ?>"><?php echo $airport['display_name']; ?> (<?php echo $airport['code']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="to_location" name="to_location" placeholder="Or type location..." />
            <button type="button" onclick="fetchToAirports()">Fetch Airports</button>
            <input type="hidden" id="to_airport_code" name="to_airport_code" />
            <div id="to_airport_list"></div>
        </div>
        <div>
            <label>Trip Dates</label>
            <input type="date" name="trip_start_date" required />
            <input type="date" name="trip_end_date" required />
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
