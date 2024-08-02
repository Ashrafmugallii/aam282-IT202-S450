<?php
require(__DIR__ . "/../../../lib/db.php");

header('Content-Type: application/json');

$response = [];

try {
    if (isset($_GET['query'])) {
        $query = $_GET['query'];
        $db = getDB();

        error_log("Searching for locations in the database with query: $query");

        // Search for locations in the database
        $stmt = $db->prepare("SELECT title, geo_id, secondary_text FROM Locations WHERE title LIKE :query");
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($locations) {
            error_log("Found locations in the database: " . json_encode($locations));
            $response = $locations;
        } else {
            error_log("No locations found in the database, fetching from API");
            // Fetch from API if not found in database
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/hotels/searchLocation?query=" . urlencode($query),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "x-rapidapi-host: tripadvisor16.p.rapidapi.com",
                    "x-rapidapi-key: 05e6f92683mshfa71bf0b3e89f6fp149107jsn647aabae8d76"
                ],
            ]);
            $apiResponse = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                error_log("cURL Error: $err");
                throw new Exception("cURL Error #:" . $err);
            } else {
                $responseData = json_decode($apiResponse, true);
                error_log("API Response: " . print_r($responseData, true));
                foreach ($responseData['data'] as $location) {
                    $response[] = [
                        'title' => $location['title'],
                        'geo_id' => $location['geoId'],
                        'secondary_text' => $location['secondaryText']
                    ];

                    // Cache the location
                    $stmt = $db->prepare("INSERT INTO Locations (title, geo_id, secondary_text) VALUES (:title, :geo_id, :secondary_text)");
                    $stmt->bindParam(':title', $location['title']);
                    $stmt->bindParam(':geo_id', $location['geoId']);
                    $stmt->bindParam(':secondary_text', $location['secondaryText']);
                    $stmt->execute();

                    error_log("Cached new location: " . json_encode($location));
                }
            }
        }
    } else {
        throw new Exception("Query parameter is missing");
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => $e->getMessage()];
    error_log("Error: " . $e->getMessage());
}

echo json_encode($response);
?>
