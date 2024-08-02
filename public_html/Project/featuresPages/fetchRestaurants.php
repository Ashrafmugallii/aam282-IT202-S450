<?php
require(__DIR__ . "/../../../lib/db.php");

function logMessage($message) {
    $logfile = __DIR__ . "/logs/restaurant_log.txt";
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

function fetchRestaurantLocation($query) {
    logMessage("Fetching restaurant location for query: $query");
    if (is_null($query)) {
        logMessage("Query parameter is required.");
        return ["error" => "Query parameter is required."];
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/restaurant/searchLocation?query=" . urlencode($query),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: tripadvisor16.p.rapidapi.com",
            "x-rapidapi-key: 05e6f92683mshfa71bf0b3e89f6fp149107jsn647aabae8d76"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        logMessage("cURL Error: " . $err);
        return ["error" => "cURL Error #:" . $err];
    } else {
        logMessage("Fetched location data: " . $response);
        return json_decode($response, true);
    }
}

function storeRestaurantLocation($db, $locationData) {
    if (!isset($locationData['data']) || empty($locationData['data'])) {
        logMessage("No location data found.");
        return ["error" => "No location data found."];
    }

    $stmt = $db->prepare("
        INSERT INTO RestaurantLocationDetails (location_id, document_id, property_id, localized_name, long_hierarchy, street1, place_type, latitude, longitude, thumbnail_url)
        VALUES (:location_id, :document_id, :property_id, :localized_name, :long_hierarchy, :street1, :place_type, :latitude, :longitude, :thumbnail_url)
        ON DUPLICATE KEY UPDATE 
            document_id = VALUES(document_id),
            property_id = VALUES(property_id),
            localized_name = VALUES(localized_name),
            long_hierarchy = VALUES(long_hierarchy),
            street1 = VALUES(street1),
            place_type = VALUES(place_type),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            thumbnail_url = VALUES(thumbnail_url)
    ");

    foreach ($locationData['data'] as $location) {
        try {
            $stmt->execute([
                ':location_id' => $location['locationId'] ?? 0,
                ':document_id' => $location['documentId'] ?? '',
                ':property_id' => $location['propertyId'] ?? 0,
                ':localized_name' => $location['localizedName'] ?? '',
                ':long_hierarchy' => $location['localizedAdditionalNames']['longOnlyHierarchy'] ?? '',
                ':street1' => $location['streetAddress']['street1'] ?? '',
                ':place_type' => $location['locationV2']['placeType'] ?? '',
                ':latitude' => $location['latitude'] ?? 0,
                ':longitude' => $location['longitude'] ?? 0,
                ':thumbnail_url' => isset($location['thumbnail']['photoSizeDynamic']['urlTemplate']) 
                    ? str_replace(['{width}', '{height}'], [200, 200], $location['thumbnail']['photoSizeDynamic']['urlTemplate']) 
                    : ''
            ]);
            logMessage("Stored location data: " . json_encode($location));
        } catch (PDOException $e) {
            logMessage("Error storing location data: " . $e->getMessage());
        }
    }
}

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = $_GET['query'];
    logMessage("Starting process for query: $query");

    $db = getDB(); // Assume you have a function to get the PDO database connection
    $locationData = fetchRestaurantLocation($query);
    logMessage("Location data fetched: " . json_encode($locationData));

    if (!isset($locationData['error'])) {
        $result = storeRestaurantLocation($db, $locationData);

        if (isset($result['error'])) {
            logMessage($result['error']);
            echo $result['error'];
        } else {
            logMessage("Location data stored successfully.");
            echo "Location data stored successfully.";
        }
    } else {
        logMessage("Error fetching location: " . $locationData['error']);
        echo $locationData['error'];
    }
} else {
    logMessage("Query parameter 'query' is required and cannot be empty.");
    echo "Query parameter 'query' is required and cannot be empty.";
}
?>
