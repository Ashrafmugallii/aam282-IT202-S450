<?php
require(__DIR__ . "/../../../lib/db.php");

header('Content-Type: application/json');
$response = [];

// Function to log errors
function log_error($message) {
    file_put_contents(__DIR__ . '/logs/error_log_file.txt', $message . PHP_EOL, FILE_APPEND);
}

try {
    if (isset($_GET['location_id'])) {
        $location_id = $_GET['location_id'];
        $db = getDB();

        // Fetch hotels from API
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/hotels/searchHotels?geoId=" . urlencode($location_id) . "&checkIn=2024-08-02&checkOut=2024-08-09",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: tripadvisor16.p.rapidapi.com",
                "x-rapidapi-key: 05e6f92683mshfa71bf0b3e89f6fp149107jsn647aabae8d76"
            ],
        ]);
        $apiResponse = curl_exec($curl);
        if ($apiResponse === false) {
            throw new Exception('cURL Error: ' . curl_error($curl));
        }
        curl_close($curl);

        // Log the API response
        file_put_contents(__DIR__ . '/logs/api_response.txt', $apiResponse, FILE_APPEND);

        $responseData = json_decode($apiResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON Decode Error: ' . json_last_error_msg());
        }

        // Log the decoded response data
        file_put_contents(__DIR__ . '/logs/decoded_response.txt', print_r($responseData, true), FILE_APPEND);

        // Check if 'data' key exists in responseData
        if (!isset($responseData['data']) || !is_array($responseData['data'])) {
            throw new Exception('API response does not contain valid data key');
        }

        // Process and cache hotel data
        foreach ($responseData['data'] as $hotel) {
            // Log the hotel data
            file_put_contents(__DIR__ . '/logs/hotel_data.txt', print_r($hotel, true), FILE_APPEND);

            // Ensure the required keys exist
            if (!isset($hotel['id'])) {
                log_error('Invalid hotel data: ' . print_r($hotel, true));
                continue;
            }

            try {
                $stmt = $db->prepare("INSERT INTO Hotels (hotel_id, title, primary_info, secondary_info, location_id) VALUES (:hotel_id, :title, :primary_info, :secondary_info, :location_id) ON DUPLICATE KEY UPDATE title = VALUES(title), primary_info = VALUES(primary_info), secondary_info = VALUES(secondary_info)");
                $stmt->execute([
                    ':hotel_id' => $hotel['id'],
                    ':title' => $hotel['title'] ?? null,
                    ':primary_info' => $hotel['primaryInfo'] ?? null,
                    ':secondary_info' => $hotel['secondaryInfo'] ?? null,
                    ':location_id' => $location_id,
                ]);
                log_error("Hotel data cached successfully: " . $hotel['id']);
            } catch (Exception $e) {
                log_error("Failed to cache hotel data for ID: " . $hotel['id'] . " Error: " . $e->getMessage());
            }
        }

        // Retrieve cached hotels from the database
        $stmt = $db->prepare("SELECT * FROM Hotels WHERE location_id = :location_id");
        $stmt->bindParam(':location_id', $location_id);
        $stmt->execute();
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($hotels) {
            $response = $hotels;
        } else {
            $response = ['message' => 'No hotels found for the given location.'];
        }
    } else {
        throw new Exception("location_id parameter is missing");
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => $e->getMessage()];
    log_error('General error: ' . $e->getMessage());
}

echo json_encode($response);
?>
