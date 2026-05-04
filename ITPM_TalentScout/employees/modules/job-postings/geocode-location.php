<?php
// Geocoding API endpoint - handles location geocoding via PHP to bypass CORS issues

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$location = isset($_GET['location']) ? trim($_GET['location']) : '';

if (empty($location)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Location parameter required']);
    exit;
}

try {
    // Use Nominatim API via PHP (server-to-server, bypasses CORS)
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($location);
    
    // Set User-Agent header (Nominatim requires this)
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: TalentScoutAI\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Geocoding service unavailable']);
        exit;
    }
    
    $results = json_decode($response, true);
    
    if (!is_array($results) || empty($results)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Location not found', 'location' => $location]);
        exit;
    }
    
    $result = $results[0];
    $lat = isset($result['lat']) ? (float)$result['lat'] : null;
    $lon = isset($result['lon']) ? (float)$result['lon'] : null;
    $display_name = isset($result['display_name']) ? $result['display_name'] : $location;
    
    if ($lat === null || $lon === null || is_nan($lat) || is_nan($lon)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid coordinates returned']);
        exit;
    }
    
    // Return successful geocoding result
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'lat' => $lat,
            'lng' => $lon,
            'label' => $display_name
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
