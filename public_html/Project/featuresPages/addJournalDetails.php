<?php
require(__DIR__ . "/../../../partials/nav.php");

// Fetch the journal ID from the query parameters
$journal_id = $_GET['journal_id'];
$db = getDB();

// Fetch the journal details
$journal = $db->prepare("SELECT * FROM Journals WHERE id = :journal_id");
$journal->bindParam(':journal_id', $journal_id);
$journal->execute();
$journal = $journal->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission
    $detail_type = $_POST['detail_type'];
    $detail_id = $_POST['detail_id'] ?: null;
    $detail_name = $_POST['detail_name'];
    $detail_description = $_POST['detail_description'];
    $detail_image_url = $_POST['detail_image_url'] ?: '';

    error_log("Adding detail with type: $detail_type, id: $detail_id, name: $detail_name");

    $details_field = $detail_type . "_details";
    $details = json_decode($journal[$details_field], true) ?: [];

    $details[] = [
        'detail_id' => $detail_id,
        'detail_name' => $detail_name,
        'detail_description' => $detail_description,
        'detail_image_url' => $detail_image_url
    ];

    $stmt = $db->prepare("UPDATE Journals SET $details_field = :details WHERE id = :journal_id");
    $stmt->bindParam(':details', json_encode($details));
    $stmt->bindParam(':journal_id', $journal_id);

    if ($stmt->execute()) {
        flash("Detail added successfully.", "success");
        header("Location: exploreJournals.php"); // Redirect to explore_journal.php
        exit(); // Ensure no further code is executed
    } else {
        flash("Error adding detail.", "danger");
        error_log("Error updating journal: " . json_encode($stmt->errorInfo()));
    }
}

// Fetch distinct cities from the RestaurantLocationDetails table
$cities = $db->query("SELECT DISTINCT localized_name FROM RestaurantLocationDetails")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h1 class="text-center">Add Journal Details</h1>
    <div class="progress mb-3">
        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div class="card mx-auto" style="max-width: 600px;" id="hotelForm">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="location_query" class="form-label">Where did you stay?</label>
                    <div class="input-group">
                        <input type="text" id="location_query" class="form-control" placeholder="Enter location..." />
                        <button type="button" class="btn btn-primary" onclick="searchLocations()">Search Locations</button>
                    </div>
                    <div id="location_list" class="mt-2"></div>
                    <div id="hotel_search" class="mt-2" style="display:none;">
                        <label class="form-label">Hotel Info:</label>
                        <input type="hidden" id="selected_location_id" />
                        <button type="button" class="btn btn-primary" onclick="searchHotels()">Search Hotels</button>
                        <div id="hotel_list" class="mt-2"></div>
                        <button type="button" class="btn btn-secondary mt-2" onclick="skipHotelSearch()">Skip Hotel Search</button>
                    </div>
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
                    <button type="button" class="btn btn-primary" onclick="showRestaurantForm()">Next</button>
                </div>
                <input type="hidden" name="detail_type" value="hotel" />
                <input type="hidden" name="detail_id" id="detail_id" />
                <input type="hidden" name="detail_name" id="detail_name" />
                <input type="hidden" name="detail_description" id="detail_description" />
                <input type="hidden" name="detail_image_url" id="detail_image_url" />
            </form>
        </div>
    </div>

    <div class="card mx-auto" style="max-width: 600px; display: none;" id="restaurantForm">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <button type="button" class="btn btn-secondary" onclick="showHotelForm()">Back</button>
                </div>
                <div class="mb-3">
                    <label for="restaurant_city" class="form-label">Select City</label>
                    <select id="restaurant_city" class="form-select" onchange="loadRestaurants()">
                        <option value="">Select City</option>
                        <?php
                        foreach ($cities as $city) {
                            echo "<option value=\"{$city['localized_name']}\">{$city['localized_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <div id="restaurant_list" class="mt-2"></div>
                    <button type="button" class="btn btn-primary" onclick="showManualRestaurantEntry()">Add New Restaurant</button>
                </div>
                <div id="manual_restaurant_entry" class="mt-2" style="display:none;">
                    <label class="form-label">Manual Restaurant Entry:</label>
                    <input type="text" id="manual_restaurant_name" class="form-control" placeholder="Restaurant Name" />
                    <input type="text" id="manual_restaurant_description" class="form-control mt-2" placeholder="Description" />
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
                    <button type="submit" class="btn btn-success">Save Journal Details</button>
                </div>
                <input type="hidden" name="detail_type" value="restaurant" />
                <input type="hidden" name="detail_id" id="detail_id" />
                <input type="hidden" name="detail_name" id="detail_name" />
                <input type="hidden" name="detail_description" id="detail_description" />
                <input type="hidden" name="detail_image_url" id="detail_image_url" />
            </form>
        </div>
        <div id="restaurant_details" class="mt-3"></div> <!-- Display restaurant details -->
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
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

function searchLocations() {
    var query = document.getElementById('location_query').value;
    var locationList = document.getElementById('location_list');
    locationList.innerHTML = 'Searching...';
    console.log("Searching for locations with query: " + query);
    fetch('searchLocations.php?query=' + query)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            locationList.innerHTML = '';
            data.forEach(location => {
                var option = document.createElement('div');
                option.textContent = `${location.localizedName} (${location.locationV2.names.longOnlyHierarchyTypeaheadV2})`;
                option.classList.add('location-option');
                option.addEventListener('click', () => {
                    selectLocation(location);
                });
                locationList.appendChild(option);
            });
        })
        .catch(error => {
            locationList.innerHTML = 'Error fetching locations';
            console.error('There was a problem with the fetch operation:', error);
        });
}

function selectLocation(location) {
    document.getElementById('selected_location_id').value = location.locationId;
    document.getElementById('hotel_search').style.display = 'block';
}

function searchHotels() {
    var locationId = document.getElementById('selected_location_id').value;
    var hotelList = document.getElementById('hotel_list');
    hotelList.innerHTML = 'Searching...';
    console.log("Searching for hotels with location ID: " + locationId);
    fetch('searchHotels.php?locationId=' + locationId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            hotelList.innerHTML = '';
            data.forEach(hotel => {
                var option = document.createElement('div');
                option.textContent = `${hotel.name} (${hotel.address})`;
                option.classList.add('hotel-option');
                option.addEventListener('click', () => {
                    selectHotel(hotel);
                });
                hotelList.appendChild(option);
            });
        })
        .catch(error => {
            hotelList.innerHTML = 'Error fetching hotels';
            console.error('There was a problem with the fetch operation:', error);
        });
}

function selectHotel(hotel) {
    document.getElementById('detail_id').value = hotel.locationId;
    document.getElementById('detail_name').value = hotel.name;
    document.getElementById('detail_description').value = hotel.description;
    document.getElementById('detail_image_url').value = hotel.heroImgUrl;
}

function skipHotelSearch() {
    document.getElementById('hotel_search').style.display = 'none';
    showRestaurantForm();
}

function showRestaurantForm() {
    document.getElementById('hotelForm').style.display = 'none';
    document.getElementById('restaurantForm').style.display = 'block';
}

function showHotelForm() {
    document.getElementById('restaurantForm').style.display = 'none';
    document.getElementById('hotelForm').style.display = 'block';
}

function loadRestaurants() {
    var city = document.getElementById('restaurant_city').value;
    var restaurantList = document.getElementById('restaurant_list');
    restaurantList.innerHTML = '';
    console.log("Loading restaurants for city: " + city);

    fetch(`fetchRestaurants.php?city=${city}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            data.forEach(restaurant => {
                var option = document.createElement('div');
                option.innerHTML = `<strong>${restaurant.localized_name}</strong><br>${restaurant.long_hierarchy}<br><img src="${restaurant.thumbnail_url}" alt="Image" style="width: 100px; height: auto;">`;
                option.classList.add('restaurant-option');
                option.addEventListener('click', () => {
                    selectRestaurant(restaurant);
                });
                restaurantList.appendChild(option);
            });
        })
        .catch(error => {
            restaurantList.innerHTML = 'Error fetching restaurants';
            console.error('There was a problem with the fetch operation:', error);
        });
}

function selectRestaurant(restaurant) {
    document.getElementById('detail_id').value = restaurant.location_id;
    document.getElementById('detail_name').value = restaurant.localized_name;
    document.getElementById('detail_description').value = restaurant.long_hierarchy;
    document.getElementById('detail_image_url').value = restaurant.thumbnail_url;

    // Display selected restaurant details
    var restaurantDetails = document.getElementById('restaurant_details');
    restaurantDetails.innerHTML = `<strong>Name:</strong> ${restaurant.localized_name}<br>
                                   <strong>Description:</strong> ${restaurant.long_hierarchy}<br>
                                   <img src="${restaurant.thumbnail_url}" alt="Image" style="width: 100px; height: auto;">`;
}

function showManualRestaurantEntry() {
    document.getElementById('manual_restaurant_entry').style.display = 'block';
}

function previewImages() {
    var photos = document.getElementById('photos').value;
    var imagePreviews = document.getElementById('image_previews');
    imagePreviews.innerHTML = '';

    photos.split(',').forEach(url => {
        var img = document.createElement('img');
        img.src = url.trim();
        img.classList.add('img-thumbnail', 'm-2');
        img.style.height = '100px';
        imagePreviews.appendChild(img);
    });
}
</script>
<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
