<?php
/**
 * Job Skills API
 * Endpoints for skill suggestions and normalization in job postings
 */

session_start();
require_once('../../../database/db.php');
require_once('../../../employees/modules/ai-matching/skill-normalizer.php');

header('Content-Type: application/json');

// Check if user is logged in as employer
if (!isset($_SESSION['employer_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$conn = getConnection();
$normalizer = new SkillNormalizer();

try {
    switch ($action) {
        case 'suggest':
            handleSuggest();
            break;
        case 'normalize':
            handleNormalize();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Suggest skills based on partial input (autocomplete)
 * Returns database-backed skill categories and synonyms
 */
function handleSuggest() {
    global $normalizer, $conn;
    
    $input = trim($_GET['q'] ?? '');
    if (strlen($input) < 2) {
        echo json_encode(['suggestions' => []]);
        return;
    }
    
    $suggestions = [];
    $seen = [];
    
    // Get canonical skill names from database that match the input
    $query = "
        SELECT DISTINCT 
            c.canonical_name as skill,
            c.category_name,
            'category' as source
        FROM skill_categories c
        WHERE LOWER(c.canonical_name) LIKE LOWER(?) OR LOWER(c.category_name) LIKE LOWER(?)
        ORDER BY c.canonical_name
        LIMIT 10
    ";
    
    $like_input = '%' . $input . '%';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $like_input, $like_input);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $skill = $row['skill'];
        if (!isset($seen[$skill])) {
            $suggestions[] = [
                'text' => $skill,
                'category' => $row['category_name'],
                'type' => 'category'
            ];
            $seen[$skill] = true;
        }
    }
    
    // Also get synonyms that match the input
    if (count($suggestions) < 10) {
        $query = "
            SELECT DISTINCT 
                s.synonym as skill,
                c.canonical_name,
                c.category_name,
                'synonym' as source
            FROM skill_synonyms s
            JOIN skill_categories c ON s.category_id = c.category_id
            WHERE LOWER(s.synonym) LIKE LOWER(?)
            ORDER BY s.synonym
            LIMIT " . (10 - count($suggestions)) . "
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $like_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $skill = $row['skill'];
            if (!isset($seen[$skill])) {
                $suggestions[] = [
                    'text' => $skill,
                    'canonical' => $row['canonical_name'],
                    'category' => $row['category_name'],
                    'type' => 'synonym'
                ];
                $seen[$skill] = true;
            }
        }
    }
    
    // Format suggestions for display
    $formatted = [];
    foreach ($suggestions as $sugg) {
        if ($sugg['type'] === 'synonym') {
            $formatted[] = $sugg['text'] . ' → ' . $sugg['canonical'];
        } else {
            $formatted[] = $sugg['text'];
        }
    }
    
    echo json_encode(['suggestions' => array_slice($formatted, 0, 10)]);
}

/**
 * Normalize a single skill to its canonical form
 */
function handleNormalize() {
    global $normalizer, $conn;
    
    $skill = trim($_GET['skill'] ?? '');
    if (empty($skill)) {
        http_response_code(400);
        echo json_encode(['error' => 'Skill required']);
        return;
    }
    
    $normalized = $normalizer->getCanonicalForm($skill, $conn);
    echo json_encode([
        'original' => $skill,
        'normalized' => $normalized,
        'changed' => strtolower($skill) !== strtolower($normalized)
    ]);
}
