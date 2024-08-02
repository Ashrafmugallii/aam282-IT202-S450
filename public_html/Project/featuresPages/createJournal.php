<?php
require(__DIR__ . "/../../../partials/nav.php");
$errors = [];

// handling the form submission 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = get_user_id(); // logged-in user ID
    $name = $_POST['name']; //the journal name using POST
    $from_airport_code = $_POST['from_airport_code']; //get from airport code
    $to_airport_code = $_POST['to_airport_code']; //to airport code
    $trip_start_date = $_POST['trip_start_date'];
    $trip_end_date = $_POST['trip_end_date'];
    $content = $_POST['content']; // info about the trip aka summary
    $photos = $_POST['photos']; //photos are uploaded and processed separately

    // inputs validation
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

    // If no errors, insert the data into the database
    if (empty($errors)) {
        $db = getDB(); //get db conn
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
            $journal_id = $db->lastInsertId(); //storing most recent journal id 
            header("Location: addJournalDetails.php?journal_id=$journal_id"); //redirecting to the page where the user can enter details about trip
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

// Fetch cached airports for the dropdown
$db = getDB();
$stmt = $db->prepare("SELECT code, display_name FROM AirportCache ORDER BY display_name");
$stmt->execute();
$airports = $stmt->fetchAll(PDO::FETCH_ASSOC); //getting all airports
?>

<div class="container mt-5">
    <h1 class="text-center">Create Journal</h1>
    <div class="progress mb-3">
        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endforeach; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="journalName" class="form-label">Journal Name</label>
                    <input type="text" name="name" class="form-control" id="journalName" required />
                </div>
                <div class="mb-3">
                    <label for="fromAirport" class="form-label">From Location</label>
                    <select id="from_airport_dropdown" name="from_airport_code_dropdown" class="form-select" onchange="setFromAirportCode()">
                        <option value="">Select from existing airports</option>
                        <?php foreach ($airports as $airport): ?>
                            <option value="<?php echo $airport['code']; ?>"><?php echo $airport['display_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="from_airport_code" name="from_airport_code" />
                </div>
                <div class="mb-3">
                    <label for="toAirport" class="form-label">To Location</label>
                    <select id="to_airport_dropdown" name="to_airport_code_dropdown" class="form-select" onchange="setToAirportCode()">
                        <option value="">Select from existing airports</option>
                        <?php foreach ($airports as $airport): ?>
                            <option value="<?php echo $airport['code']; ?>"><?php echo $airport['display_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="to_airport_code" name="to_airport_code" />
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Fetch Airports</label>
                    <div class="input-group">
                        <input type="text" id="location" class="form-control" placeholder="Type location..." />
                        <button type="button" class="btn btn-primary" onclick="fetchAirports()">Fetch</button>
                    </div>
                    <div id="fetched_airports_list" class="mt-2"></div>
                </div>
                <div class="mb-3">
                    <label for="tripStartDate" class="form-label">Trip Start Date</label>
                    <input type="date" name="trip_start_date" class="form-control datepicker" id="tripStartDate" required />
                </div>
                <div class="mb-3">
                    <label for="tripEndDate" class="form-label">Trip End Date</label>
                    <input type="date" name="trip_end_date" class="form-control datepicker" id="tripEndDate" required />
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea name="content" class="form-control rich-text-editor" id="content" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="photos" class="form-label">Photos</label>
                    <input type="text" name="photos" class="form-control" id="photos" placeholder="Enter photo URLs separated by commas" onchange="previewImages()" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Image Previews</label>
                    <div id="image_previews"></div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Create Journal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    tinymce.init({
        selector: '.rich-text-editor',
        height: 300,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic backcolor | \
                  alignleft aligncenter alignright alignjustify | \
                  bullist numlist outdent indent | removeformat | help'
    });
});

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

function fetchAirports() {
    var query = document.getElementById('location').value;
    console.log('Fetching airports with query:', query);
    fetch('fetchAirports.php?query=' + query)
        .then(response => response.text())
        .then(text => {
            console.log('Response text:', text);
            try {
                var data = JSON.parse(text);
            } catch (e) {
                throw new Error('Failed to parse JSON: ' + e.message);
            }
            console.log('Response data:', data);
            if (data.error) {
                alert('Error fetching airports: ' + data.error);
                return;
            }
            var fromAirportDropdown = document.getElementById('from_airport_dropdown');
            var toAirportDropdown = document.getElementById('to_airport_dropdown');
            var fetchedAirportsList = document.getElementById('fetched_airports_list');
            fetchedAirportsList.innerHTML = '<strong>Fetched airports:</strong>';
            var newOptions = [];

            data.forEach(airport => {
                var option = document.createElement('div');
                option.textContent = `${airport.name} (${airport.airportCode})`;
                fetchedAirportsList.appendChild(option);

                var dropdownOption = document.createElement('option');
                dropdownOption.value = airport.airportCode;
                dropdownOption.textContent = airport.name;
                newOptions.push(dropdownOption);
            });

            newOptions.sort((a, b) => a.textContent.localeCompare(b.textContent));

            newOptions.forEach(option => {
                fromAirportDropdown.appendChild(option.cloneNode(true));
                toAirportDropdown.appendChild(option.cloneNode(true));
            });

            alert('Airports successfully fetched and updated.');
        })
        .catch(error => {
            console.error('Error fetching airports:', error);
            alert('Error fetching airports: ' + error.message);
        });
}

function previewImages() {
    var photosInput = document.getElementById('photos');
    var imagePreviews = document.getElementById('image_previews');
    var urls = photosInput.value.split(',').map(url => url.trim());
    imagePreviews.innerHTML = '';
    urls.forEach(url => {
        if (url) {
            var img = document.createElement('img');
            img.src = url;
            img.classList.add('img-thumbnail', 'm-2');
            img.style.maxWidth = '100px';
            imagePreviews.appendChild(img);
        }
    });
}

document.querySelector('form').addEventListener('input', updateProgressBar);

function updateProgressBar() {
    var progressBar = document.getElementById('progressBar');
    var inputs = document.querySelectorAll('input, textarea, select');
    var filled = Array.from(inputs).filter(input => input.value).length;
    var total = inputs.length;
    var progress = Math.round((filled / total) * 100);
    progressBar.style.width = progress + '%';
    progressBar.setAttribute('aria-valuenow', progress);
}
</script>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
