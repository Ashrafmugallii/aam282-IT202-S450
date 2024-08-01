<?php
require(__DIR__ . "/../../../lib/db.php");

function log_error($message) {
    file_put_contents(__DIR__ . '/logs/error_log_file.txt', $message . PHP_EOL, FILE_APPEND);
}

// Check if query parameter is set
if (!isset($_GET['query']) || empty($_GET['query'])) {
    http_response_code(400);
    echo json_encode(["error" => "Query parameter is missing."]);
    exit;
}

// Get the query parameter
$query = urlencode($_GET['query']);

// Initialize cURL for searchLocation
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/restaurant/searchLocation?query=$query",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "x-rapidapi-host: tripadvisor16.p.rapidapi.com",
        "x-rapidapi-key: 05e6f92683mshfa71bf0b3e89f6fp149107jsn647aabae8d76"
    ],
]);

// Execute cURL request for searchLocation
$locationResponse = curl_exec($curl);
$locationErr = curl_error($curl);

// Close cURL
curl_close($curl);

if ($locationErr) {
    http_response_code(500);
    echo json_encode(["error" => "cURL Error: " . $locationErr]);
    log_error("cURL Error: " . $locationErr);
    exit;
} else {
    // Decode API response for searchLocation
    $locationData = json_decode($locationResponse, true);

    // Check for JSON decoding error
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(["error" => "JSON Decode Error: " . json_last_error_msg()]);
        log_error("JSON Decode Error: " . json_last_error_msg());
        exit;
    }

    // Extract locationId from searchLocation response
    if (!isset($locationData['data']) || !is_array($locationData['data'])) {
        http_response_code(500);
        echo json_encode(["error" => "Invalid searchLocation API response structure."]);
        log_error("Invalid searchLocation API response structure.");
        exit;
    }

    $locationId = $locationData['data'][0]['locationId'];

    // Initialize cURL for searchRestaurants
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/restaurant/searchRestaurants?locationId=$locationId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: tripadvisor16.p.rapidapi.com",
            "x-rapidapi-key: 05e6f92683mshfa71bf0b3e89f6fp149107jsn647aabae8d76"
        ],
    ]);

    // Execute cURL request for searchRestaurants
    $restaurantResponse = curl_exec($curl);
    $restaurantErr = curl_error($curl);

    // Close cURL
    curl_close($curl);

    if ($restaurantErr) {
        http_response_code(500);
        echo json_encode(["error" => "cURL Error: " . $restaurantErr]);
        log_error("cURL Error: " . $restaurantErr);
        exit;
    } else {
        // Decode API response for searchRestaurants
        $restaurantData = json_decode($restaurantResponse, true);

        // Check for JSON decoding error
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(500);
            echo json_encode(["error" => "JSON Decode Error: " . json_last_error_msg()]);
            log_error("JSON Decode Error: " . json_last_error_msg());
            exit;
        }

        // Check if the API response contains the 'data' key
        if (!isset($restaurantData['data']) || !is_array($restaurantData['data'])) {
            http_response_code(500);
            echo json_encode(["error" => "Invalid searchRestaurants API response structure."]);
            log_error("Invalid searchRestaurants API response structure.");
            exit;
        }

        // Get the database connection
        $db = getDB();

        // Prepare the SQL statement
        $stmt = $db->prepare("INSERT INTO restaurantDetails (locationId, name, averageRating, userReviewCount, currentOpenStatusCategory, currentOpenStatusText, establishmentTypeAndCuisineTags, priceTag, heroImgUrl, reviewSnippets) VALUES (:locationId, :name, :averageRating, :userReviewCount, :currentOpenStatusCategory, :currentOpenStatusText, :establishmentTypeAndCuisineTags, :priceTag, :heroImgUrl, :reviewSnippets) ON DUPLICATE KEY UPDATE name = VALUES(name), averageRating = VALUES(averageRating), userReviewCount = VALUES(userReviewCount), currentOpenStatusCategory = VALUES(currentOpenStatusCategory), currentOpenStatusText = VALUES(currentOpenStatusText), establishmentTypeAndCuisineTags = VALUES(establishmentTypeAndCuisineTags), priceTag = VALUES(priceTag), heroImgUrl = VALUES(heroImgUrl), reviewSnippets = VALUES(reviewSnippets)");

        // Process and store restaurant data
        foreach ($restaurantData['data'] as $restaurant) {
            // Extract relevant restaurant information
            $locationId = $restaurant['locationId'] ?? '';
            $name = $restaurant['name'] ?? '';
            $averageRating = $restaurant['averageRating'] ?? 0;
            $userReviewCount = $restaurant['userReviewCount'] ?? 0;
            $currentOpenStatusCategory = $restaurant['currentOpenStatusCategory'] ?? '';
            $currentOpenStatusText = $restaurant['currentOpenStatusText'] ?? '';
            $establishmentTypeAndCuisineTags = json_encode($restaurant['establishmentTypeAndCuisineTags'] ?? []);
            $priceTag = $restaurant['priceTag'] ?? '';
            $heroImgUrl = $restaurant['heroImgUrl'] ?? '';
            $reviewSnippets = json_encode($restaurant['reviewSnippets']['reviewSnippetsList'] ?? []);

            // Execute the SQL statement
            try {
                $stmt->execute([
                    ':locationId' => (string)$locationId,
                    ':name' => (string)$name,
                    ':averageRating' => $averageRating,
                    ':userReviewCount' => $userReviewCount,
                    ':currentOpenStatusCategory' => (string)$currentOpenStatusCategory,
                    ':currentOpenStatusText' => (string)$currentOpenStatusText,
                    ':establishmentTypeAndCuisineTags' => $establishmentTypeAndCuisineTags,
                    ':priceTag' => (string)$priceTag,
                    ':heroImgUrl' => (string)$heroImgUrl,
                    ':reviewSnippets' => $reviewSnippets
                ]);
                log_error("Restaurant data cached successfully: " . $locationId);
            } catch (Exception $e) {
                log_error("Failed to cache restaurant data for ID: " . $locationId . " Error: " . $e->getMessage());
            }
        }

        // Return the stored restaurant data
        $stmt = $db->prepare("SELECT * FROM restaurantDetails WHERE locationId = :locationId");
        $stmt->bindParam(':locationId', $locationId);
        $stmt->execute();
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($restaurants);
    }
}
?>
