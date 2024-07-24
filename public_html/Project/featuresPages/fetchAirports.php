<?php
require(__DIR__ . "/../../../partials/nav.php");

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $db = getDB();

    // Check if the query result is already cached in the database
    $stmt = $db->prepare("SELECT code, display_name FROM AirportCache WHERE display_name LIKE :query");
    $stmt->bindValue(':query', '%' . $query . '%');
    $stmt->execute();
    $cachedResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($cachedResults) {
        // Use cached result
        $result = [];
        foreach ($cachedResults as $airport) {
            $result[] = [
                'name' => $airport['display_name'],
                'airportCode' => $airport['code']
            ];
        }
        echo json_encode($result);
    } else {
        // Fetch from API
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/flights/searchAirport?query=" . urlencode($query),
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
            echo "cURL Error #:" . $err;
        } else {
            $responseData = json_decode($response, true);
            $airports = isset($responseData['data']) ? $responseData['data'] : [];
            $result = [];
            foreach ($airports as $airport) {
                if (isset($airport['children'])) {
                    foreach ($airport['children'] as $child) {
                        if (isset($child['airportCode']) && isset($child['shortName'])) {
                            $result[] = [
                                'name' => $child['shortName'],
                                'airportCode' => $child['airportCode']
                            ];
                            // Cache the result in the database
                            $stmt = $db->prepare("INSERT INTO AirportCache (code, display_name) VALUES (:code, :display_name) ON DUPLICATE KEY UPDATE display_name = :display_name");
                            $stmt->bindParam(':code', $child['airportCode']);
                            $stmt->bindParam(':display_name', $child['shortName']);
                            $stmt->execute();
                        }
                    }
                }
            }
            echo json_encode($result);
        }
    }
}
?>
