<?php
require(__DIR__ . "/../../../partials/nav.php");

$journal_id = $_GET['journal_id']; // Get the journal ID from the query parameters
$db = getDB();
$journal = $db->prepare("SELECT * FROM Journals WHERE id = :journal_id");
$journal->bindParam(':journal_id', $journal_id);
$journal->execute();
$journal = $journal->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $detail_type = $_POST['detail_type'];
    $detail_id = $_POST['detail_id'];
    $detail_name = $_POST['detail_name'];
    $detail_description = $_POST['detail_description'];
    $detail_image_url = $_POST['detail_image_url'];

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
    } else {
        flash("Error adding detail.", "danger");
        error_log("Error updating journal: " . json_encode($stmt->errorInfo()));
    }
}
?>

<div class="container-fluid">
    <h1>Add Journal Details</h1>
    <form method="POST">
        <div>
            <label>Where did you stay?</label>
            <input type="text" id="location_query" placeholder="Enter location..." />
            <button type="button" onclick="searchLocations()">Search Locations</button>
            <div id="location_list"></div>
            <div id="hotel_search" style="display:none;">
                <label>Hotel Info:</label>
                <input type="hidden" id="selected_location_id" />
                <button type="button" onclick="searchHotels()">Search Hotels</button>
                <div id="hotel_list"></div>
                <button type="button" onclick="skipHotelSearch()">Skip Hotel Search</button>
            </div>
        </div>
        <!-- New Section for Restaurant Search -->
        <div id="restaurant_section" style="display:none;">
            <label>Where did you eat?</label>
            <input type="text" id="restaurant_query" placeholder="Enter Location..." />
            <button type="button" onclick="searchRestaurants()">Search Restaurants</button>
            <div id="restaurant_list"></div>
        </div>
        <!-- Other form fields for journal details -->
        <div>
            <label>Content</label>
            <textarea name="content" required></textarea>
        </div>
        <div>
            <label>Photos</label>
            <input type="text" name="photos" placeholder="Enter photo URLs separated by commas" />
        </div>
        <div>
            <input type="submit" value="Save Journal Details" />
        </div>
        <input type="hidden" name="detail_type" value="hotel" />
        <input type="hidden" name="detail_id" id="detail_id" />
        <input type="hidden" name="detail_name" id="detail_name" />
        <input type="hidden" name="detail_description" id="detail_description" />
        <input type="hidden" name="detail_image_url" id="detail_image_url" />
    </form>
</div>

<script>
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
                option.textContent = `${location.title} (${location.secondary_text})`;
                option.addEventListener('click', function() {
                    document.getElementById('selected_location_id').value = location.geo_id;
                    document.getElementById('hotel_search').style.display = 'block';
                    locationList.innerHTML = '';
                });
                locationList.appendChild(option);
            });
            console.log("Locations found: ", data);
        })
        .catch(error => {
            console.error('Error searching locations:', error);
            locationList.innerHTML = 'Error searching locations.';
        });
}

function searchHotels() {
    var locationId = document.getElementById('selected_location_id').value;
    var hotelList = document.getElementById('hotel_list');
    hotelList.innerHTML = 'Searching...';
    console.log("Searching for hotels with location ID: " + locationId);

    fetch('searchHotels.php?location_id=' + locationId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            hotelList.innerHTML = '';
            if (data.length === 0) {
                hotelList.innerHTML = 'No hotels found.';
            } else {
                data.forEach(hotel => {
                    var option = document.createElement('div');
                    option.className = 'hotel-item';
                    option.innerHTML = `
                        <h3>${hotel.title}</h3>
                        <p>${hotel.primary_info}</p>
                        <p>${hotel.secondary_info}</p>
                        <p>Rating: ${hotel.rating} (${hotel.rating_count} reviews)</p>
                        <p>Provider: ${hotel.provider}</p>
                        <img src="${hotel.image_url_1 ? hotel.image_url_1.replace('{width}', 200).replace('{height}', 200) : ''}" alt="${hotel.title}">
                    `;
                    option.addEventListener('click', function() {
                        document.getElementById('detail_id').value = hotel.hotel_id;
                        document.getElementById('detail_name').value = hotel.title;
                        document.getElementById('detail_description').value = hotel.primary_info;
                        document.getElementById('detail_image_url').value = hotel.image_url_1;
                    });
                    hotelList.appendChild(option);
                });
                console.log("Hotels found: ", data);
            }
        })
        .catch(error => {
            console.error('Error searching hotels:', error);
            hotelList.innerHTML = 'Error searching hotels.';
        });
}

function skipHotelSearch() {
    document.getElementById('hotel_search').style.display = 'none';
    document.getElementById('restaurant_section').style.display = 'block';
}

function searchRestaurants() {
    var query = document.getElementById('restaurant_query').value;
    var restaurantList = document.getElementById('restaurant_list');
    restaurantList.innerHTML = 'Searching...';
    console.log("Searching for restaurants with query: " + query);

    fetch('searchRestaurants.php?query=' + query)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            restaurantList.innerHTML = '';
            data.forEach(restaurant => {
                var option = document.createElement('div');
                option.className = 'restaurant-item';
                option.innerHTML = `
                    <h3>${restaurant.name}</h3>
                    <p>${restaurant.address}</p>
                    <p>Rating: ${restaurant.rating}</p>
                `;
                option.addEventListener('click', function() {
                    document.getElementById('detail_id').value = restaurant.id;
                    document.getElementById('detail_name').value = restaurant.name;
                    document.getElementById('detail_description').value = restaurant.address;
                    document.getElementById('detail_image_url').value = restaurant.image_url;
                });
                restaurantList.appendChild(option);
            });
            console.log("Restaurants found: ", data);
        })
        .catch(error => {
            console.error('Error searching restaurants:', error);
            restaurantList.innerHTML = 'Error searching restaurants.';
        });
}
</script>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
