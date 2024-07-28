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

    // Check if the detail is already cached
    $stmt = $db->prepare("SELECT * FROM DetailCache WHERE detail_id = :detail_id AND detail_type = :detail_type");
    $stmt->bindParam(':detail_id', $detail_id);
    $stmt->bindParam(':detail_type', $detail_type);
    $stmt->execute();
    $cachedDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cachedDetail) {
        // Use cached detail
        $detail_name = $cachedDetail['detail_name'];
        $detail_description = $cachedDetail['detail_description'];
        $detail_image_url = $cachedDetail['detail_image_url'];
    } else {
        // Fetch from API and cache the result
        $apiUrl = '';
        if ($detail_type == 'hotel') {
            $apiUrl = "https://tripadvisor16.p.rapidapi.com/api/v1/hotels/getDetails?locationId=" . urlencode($detail_id);
        } elseif ($detail_type == 'restaurant') {
            $apiUrl = "https://tripadvisor16.p.rapidapi.com/api/v1/restaurants/getDetails?locationId=" . urlencode($detail_id);
        } elseif ($detail_type == 'attraction') {
            $apiUrl = "https://tripadvisor16.p.rapidapi.com/api/v1/attractions/getDetails?locationId=" . urlencode($detail_id);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: tripadvisor16.p.rapidapi.com",
                "x-rapidapi-key: YOUR_API_KEY"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $detailData = json_decode($response, true);
            $detail_name = $detailData['name'];
            $detail_description = $detailData['description'];
            $detail_image_url = $detailData['photo']['images']['large']['url'];

            // Cache the detail
            $stmt = $db->prepare("INSERT INTO DetailCache (detail_id, detail_type, detail_name, detail_description, detail_image_url, created) VALUES (:detail_id, :detail_type, :detail_name, :detail_description, :detail_image_url, NOW())");
            $stmt->bindParam(':detail_id', $detail_id);
            $stmt->bindParam(':detail_type', $detail_type);
            $stmt->bindParam(':detail_name', $detail_name);
            $stmt->bindParam(':detail_description', $detail_description);
            $stmt->bindParam(':detail_image_url', $detail_image_url);
            $stmt->execute();
        }
    }

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
    }
}
?>

<div class="container-fluid">
    <h1>Add Details to Journal</h1>
    <form method="POST">
        <input type="hidden" name="journal_id" value="<?php echo htmlspecialchars($journal_id); ?>" />
        <div>
            <label>Detail Type</label>
            <select name="detail_type" required>
                <option value="hotel">Hotel</option>
                <option value="restaurant">Restaurant</option>
                <option value="attraction">Attraction</option>
            </select>
        </div>
        <div>
            <label>Detail ID</label>
            <input type="text" name="detail_id" required />
        </div>
        <div>
            <label>Detail Name</label>
            <input type="text" name="detail_name" />
        </div>
        <div>
            <label>Detail Description</label>
            <textarea name="detail_description"></textarea>
        </div>
        <div>
            <label>Detail Image URL</label>
            <input type="text" name="detail_image_url" />
        </div>
        <div>
            <input type="submit" value="Add Detail" />
        </div>
    </form>
    <div>
        <h2>Existing Details</h2>
        <!-- Display existing details for this journal -->
        <ul>
            <?php
            $detail_types = ['hotel', 'restaurant', 'attraction'];
            foreach ($detail_types as $type) {
                $details = json_decode($journal[$type . '_details'], true) ?: [];
                foreach ($details as $detail) {
                    echo "<li>" . htmlspecialchars($detail['detail_name']) . " (" . htmlspecialchars($type) . ")</li>";
                }
            }
            ?>
        </ul>
    </div>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
