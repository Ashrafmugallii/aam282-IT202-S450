<?php
require(__DIR__ . "/../../../lib/db.php");

header('Content-Type: application/json'); // response is JSON

$response = [];

try {
    if (isset($_GET['query'])) {
        $query = $_GET['query'];
        $db = getDB();

        // Checking to see if the query result is already cached in the database
        $stmt = $db->prepare("SELECT code, display_name FROM AirportCache WHERE display_name LIKE :query");
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->execute();
        $cachedResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($cachedResults) {
            // Use cached result
            foreach ($cachedResults as $airport) {
                $response[] = [
                    'name' => $airport['display_name'],
                    'airportCode' => $airport['code']
                ];
            }
        } else {
            // Fetch datsa from API
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/flights/searchAirport?query=" . urlencode($query),
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
                throw new Exception("cURL Error #:" . $err);
            } else {
                $responseData = json_decode($apiResponse, true);
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    $airports = $responseData['data'];
                    foreach ($airports as $airport) {
                        if (isset($airport['airportCode']) && isset($airport['name'])) {
                            $response[] = [
                                'name' => $airport['name'],
                                'airportCode' => $airport['airportCode']
                            ];
                            // Cache the results in the database
                            $stmt = $db->prepare("INSERT INTO AirportCache (code, display_name) VALUES (:code, :display_name) ON DUPLICATE KEY UPDATE display_name = :display_name");
                            $stmt->bindParam(':code', $airport['airportCode']);
                            $stmt->bindParam(':display_name', $airport['name']);
                            $stmt->execute();
                        }
                    }
                } else {
                    throw new Exception("Invalid API response format: " . json_encode($responseData));
                }
            }
        }
    } else {
        throw new Exception("Query parameter is missing");
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
?>
