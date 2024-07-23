<?php
require(__DIR__ . "/../../partials/nav.php");

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://tripadvisor16.p.rapidapi.com/api/v1/flights/searchAirport?query=london",
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

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}

require(__DIR__ . "/../../../partials/flash.php");