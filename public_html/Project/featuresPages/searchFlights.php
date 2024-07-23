<?php
require(__DIR__ . "/../../../partials/nav.php");

$result = [];
if (isset($_GET["sourceAirportCode"]) && isset($_GET["destinationAirportCode"])) {
    $endpoint = "https://tripadvisor16.p.rapidapi.com/api/v1/flights/searchFlights";
    $isRapidAPI = true;
    $rapidAPIHost = "tripadvisor16.p.rapidapi.com";

    $data = [
        "sourceAirportCode" => $_GET["sourceAirportCode"],
        "destinationAirportCode" => $_GET["destinationAirportCode"],
        "itineraryType" => "ONE_WAY",
        "sortOrder" => "ML_BEST_VALUE",
        "numAdults" => 1,
        "numSeniors" => 0,
        "classOfService" => "ECONOMY",
        "pageNumber" => 1,
        "nearby" => "yes",
        "nonstop" => "yes",
        "currencyCode" => "USD",
        "region" => "USA"
    ];

    $result = get($endpoint, "TRIPADVISOR_API_KEY", $data, $isRapidAPI, $rapidAPIHost);

    error_log("Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}
?>
<div class="container-fluid">
    <h1>Flight Search</h1>
    <form>
        <div>
            <label>Source Airport Code</label>
            <input name="sourceAirportCode" required />
            <label>Destination Airport Code</label>
            <input name="destinationAirportCode" required />
            <input type="submit" value="Search Flights" />
        </div>
    </form>
    <div class="row ">
        <?php if (!empty($result)) : ?>
            <?php if (isset($result['data']['flights']) && !empty($result['data']['flights'])) : ?>
                <?php foreach ($result['data']['flights'] as $flight) : ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Flight: <?php echo htmlspecialchars($flight['segments'][0]['legs'][0]['flightNumber']); ?></h5>
                                <p class="card-text">From: <?php echo htmlspecialchars($flight['segments'][0]['legs'][0]['originStationCode']); ?></p>
                                <p class="card-text">To: <?php echo htmlspecialchars($flight['segments'][0]['legs'][0]['destinationStationCode']); ?></p>
                                <p class="card-text">Departure: <?php echo htmlspecialchars($flight['segments'][0]['legs'][0]['departureDateTime']); ?></p>
                                <p class="card-text">Arrival: <?php echo htmlspecialchars($flight['segments'][0]['legs'][0]['arrivalDateTime']); ?></p>
                                <p class="card-text">Price: <?php echo htmlspecialchars($flight['purchaseLinks'][0]['totalPrice']); ?> USD</p>
                                <a href="<?php echo htmlspecialchars($flight['purchaseLinks'][0]['url']); ?>" class="btn btn-primary">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No flights available.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
