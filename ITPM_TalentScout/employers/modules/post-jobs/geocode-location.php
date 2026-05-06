<?php
// Reverse Geocoding API endpoint - handles reverse location lookup via PHP to bypass CORS issues

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$boundary = isset($_GET['boundary']) ? trim($_GET['boundary']) : '';

function isNasugbuCoordinates($lat, $lon)
{
    return $lat !== null && $lon !== null
        && $lat >= 13.90 && $lat <= 14.30
        && $lon >= 120.40 && $lon <= 120.95;
}

function isNasugbuDisplayName($displayName)
{
    $normalized = strtolower($displayName);
    return strpos($normalized, 'nasugbu') !== false && strpos($normalized, 'batangas') !== false;
}

function nasugbuWarningMessage()
{
    return 'Location must be within Nasugbu, Batangas only.';
}

if (empty($location) && ($lat === null || $lon === null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Location or coordinates required']);
    exit;
}

try {
    // Determine which API endpoint to use
    if (!empty($location)) {
        // Forward geocoding (location string to coordinates)
        $searchLocation = trim($location . ', Nasugbu, Batangas, Philippines');
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&polygon_geojson=1&limit=5&countrycodes=ph&viewbox=120.40,14.30,120.95,13.90&bounded=1&q=' . urlencode($searchLocation);
    } else {
        // Reverse geocoding (coordinates to location string)
        if (!isNasugbuCoordinates($lat, $lon)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => nasugbuWarningMessage()]);
            exit;
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' . urlencode($lat) . '&lon=' . urlencode($lon);
    }

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

    $data = json_decode($response, true);

    if (!empty($boundary)) {
        if (!is_array($data) || empty($data[0]) || empty($data[0]['geojson'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => nasugbuWarningMessage()]);
            exit;
        }

        $result = $data[0];
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'display_name' => isset($result['display_name']) ? $result['display_name'] : 'Nasugbu, Batangas, Philippines',
                'boundingbox' => isset($result['boundingbox']) ? $result['boundingbox'] : [],
                'geojson' => $result['geojson']
            ]
        ]);
        exit;
    }

    if (empty($location)) {
        // Reverse geocoding response
        if (!is_array($data) || empty($data['display_name']) || !isNasugbuDisplayName($data['display_name'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => nasugbuWarningMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'display_name' => $data['display_name'],
                'lat' => (float)$data['lat'],
                'lon' => (float)$data['lon']
            ]
        ]);
    } else {
        // Forward geocoding response
        if (!is_array($data) || empty($data)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => nasugbuWarningMessage(), 'location' => $location]);
            exit;
        }

        $result = null;
        foreach ($data as $candidate) {
            $candidateLat = isset($candidate['lat']) ? (float)$candidate['lat'] : null;
            $candidateLon = isset($candidate['lon']) ? (float)$candidate['lon'] : null;
            $candidateDisplayName = isset($candidate['display_name']) ? $candidate['display_name'] : '';

            if ($candidateLat !== null && $candidateLon !== null && isNasugbuCoordinates($candidateLat, $candidateLon) && isNasugbuDisplayName($candidateDisplayName)) {
                $result = $candidate;
                break;
            }
        }

        if ($result === null) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => nasugbuWarningMessage(), 'location' => $location]);
            exit;
        }

        $lat = isset($result['lat']) ? (float)$result['lat'] : null;
        $lon = isset($result['lon']) ? (float)$result['lon'] : null;
        $display_name = isset($result['display_name']) ? $result['display_name'] : $location;

        if ($lat === null || $lon === null || is_nan($lat) || is_nan($lon)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid coordinates returned']);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'lat' => $lat,
                'lng' => $lon,
                'display_name' => $display_name
            ]
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
