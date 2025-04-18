<?php
//save function for saving all the music that played
function saveTrackHistory($pdo, $stationId, $title) {
    // Haal de laatste titel voor dit station op
    $stmt = $pdo->prepare("SELECT title FROM track_history WHERE station_id = ? ORDER BY timestamp DESC LIMIT 1");
    $stmt->execute([$stationId]);
    $lastTitle = $stmt->fetchColumn();

    if ($title !== $lastTitle) {
        // Sla nieuwe unieke titel op
        $stmt = $pdo->prepare("INSERT INTO track_history (station_id, title, timestamp) VALUES (?, ?, NOW())");
        $stmt->execute([$stationId, $title]);
    }
}

// grabbing last history of music played
$stationId = $_GET['station_id'];
$stmt = $pdo->prepare("
    SELECT title, MAX(timestamp) as timestamp 
    FROM track_history 
    WHERE station_id = ? 
    GROUP BY title 
    ORDER BY timestamp DESC 
    LIMIT 10
");
$stmt->execute([$stationId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

<button id="show-history">Laatste nummers</button>
<div id="history-popup" style="display:none;">
    <ul id="history-list"></ul>
</div>

database (make) 
CREATE TABLE track_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT,
    title VARCHAR(255),
    timestamp DATETIME
);

<?php
$station = $_GET['station'] ?? '';
if (!$station) exit('Ongeldig verzoek');

$cacheFile = _DIR_ . "/cache/history_$station.json";
$cacheTime = 30; // seconden

// Als cache nog vers is, gewoon uitlezen
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    header('Content-Type: application/json');
    echo file_get_contents($cacheFile);
    exit;
}

// Anders: haal nieuwe data op (bijv. via StreamManager class)
require_once 'StreamManager.php';
$stream = new StreamManager($station);
$history = $stream->getRecentTitles(); // array met recente titels

// Dubbele weghalen
$unique = array_values(array_unique($history));

// Cache opslaan
file_put_contents($cacheFile, json_encode($unique));

// En response sturen
header('Content-Type: application/json');
echo json_encode($unique);
