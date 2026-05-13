<?php
session_start();
header('Content-Type: application/json');

// Verify admin access
if (empty($_SESSION['peso_admin_logged_in']) || $_SESSION['peso_admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../../database/db.php';

// Initialize database connection
$conn = getConnection();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'get-categories':
            getCategories();
            break;

        case 'add-category':
            if ($method !== 'POST') throw new Exception('Invalid method');
            addCategory();
            break;

        case 'update-category':
            if ($method !== 'POST') throw new Exception('Invalid method');
            updateCategory();
            break;

        case 'delete-category':
            if ($method !== 'POST') throw new Exception('Invalid method');
            deleteCategory();
            break;

        case 'add-synonym':
            if ($method !== 'POST') throw new Exception('Invalid method');
            addSynonym();
            break;

        case 'remove-synonym':
            if ($method !== 'POST') throw new Exception('Invalid method');
            removeSynonym();
            break;

        case 'search-skill':
            searchSkill();
            break;

        case 'test-matching':
            testMatching();
            break;

        case 'export-data':
            exportData();
            break;

        case 'import-data':
            if ($method !== 'POST') throw new Exception('Invalid method');
            importData();
            break;

        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

closeConnection($conn);

function getCategories() {
    global $conn;
    
    $sql = "SELECT c.*, COUNT(s.synonym_id) as synonym_count 
            FROM skill_categories c 
            LEFT JOIN skill_synonyms s ON c.category_id = s.category_id 
            GROUP BY c.category_id 
            ORDER BY c.category_name";
    
    $result = $conn->query($sql);
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        $cat_id = $row['category_id'];
        $syn_sql = "SELECT synonym_id, synonym FROM skill_synonyms WHERE category_id = ?";
        $syn_stmt = $conn->prepare($syn_sql);
        $syn_stmt->bind_param('i', $cat_id);
        $syn_stmt->execute();
        $syn_result = $syn_stmt->get_result();
        
        $synonyms = [];
        while ($syn = $syn_result->fetch_assoc()) {
            $synonyms[] = $syn;
        }
        
        $row['synonyms'] = $synonyms;
        $categories[] = $row;
    }
    
    echo json_encode($categories);
}

function addCategory() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $category_name = $data['category_name'] ?? '';
    $canonical_name = $data['canonical_name'] ?? '';
    
    if (empty($category_name) || empty($canonical_name)) {
        throw new Exception('Missing required fields');
    }
    
    $sql = "INSERT INTO skill_categories (category_name, canonical_name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $category_name, $canonical_name);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'category_id' => $conn->insert_id,
        'message' => 'Category added successfully'
    ]);
}

function updateCategory() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $category_id = $data['category_id'] ?? 0;
    $category_name = $data['category_name'] ?? '';
    $canonical_name = $data['canonical_name'] ?? '';
    
    if (empty($category_id) || empty($category_name) || empty($canonical_name)) {
        throw new Exception('Missing required fields');
    }
    
    $sql = "UPDATE skill_categories SET category_name = ?, canonical_name = ? WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $category_name, $canonical_name, $category_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
}

function deleteCategory() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $category_id = $data['category_id'] ?? 0;
    
    if (empty($category_id)) {
        throw new Exception('Missing category_id');
    }
    
    $sql = "DELETE FROM skill_categories WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
}

function addSynonym() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $category_id = $data['category_id'] ?? 0;
    $synonym = $data['synonym'] ?? '';
    
    if (empty($category_id) || empty($synonym)) {
        throw new Exception('Missing required fields');
    }
    
    $sql = "INSERT INTO skill_synonyms (category_id, synonym) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $category_id, $synonym);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'synonym_id' => $conn->insert_id,
        'message' => 'Synonym added successfully'
    ]);
}

function removeSynonym() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $synonym_id = $data['synonym_id'] ?? 0;
    
    if (empty($synonym_id)) {
        throw new Exception('Missing synonym_id');
    }
    
    $sql = "DELETE FROM skill_synonyms WHERE synonym_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $synonym_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Synonym removed successfully']);
}

function searchSkill() {
    global $conn;
    
    $skill = strtolower($_GET['skill'] ?? '');
    
    if (empty($skill)) {
        throw new Exception('Missing skill parameter');
    }
    
    // Search in canonical names
    $sql = "SELECT c.*, GROUP_CONCAT(s.synonym) as synonyms 
            FROM skill_categories c 
            LEFT JOIN skill_synonyms s ON c.category_id = s.category_id 
            WHERE LOWER(c.canonical_name) LIKE ? OR LOWER(c.category_name) LIKE ? 
               OR LOWER(s.synonym) LIKE ?
            GROUP BY c.category_id";
    
    $search_term = "%$skill%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $search_term, $search_term, $search_term);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $results = [];
    
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    echo json_encode($results);
}

function testMatching() {
    global $conn;
    
    $skill1 = strtolower($_GET['skill1'] ?? '');
    $skill2 = strtolower($_GET['skill2'] ?? '');
    
    if (empty($skill1) || empty($skill2)) {
        throw new Exception('Missing skill parameters');
    }
    
    // Normalize and find categories
    $cat1 = findSkillCategory($skill1);
    $cat2 = findSkillCategory($skill2);
    
    $match = false;
    $match_type = 'no match';
    $match_score = 0;
    
    if ($cat1 && $cat2) {
        if ($cat1['category_id'] === $cat2['category_id']) {
            if ($skill1 === $cat1['canonical_normalized'] || $skill1 === $cat2['canonical_normalized']) {
                $match_type = 'exact match';
                $match_score = 100;
            } else {
                $match_type = 'synonym match';
                $match_score = 70;
            }
            $match = true;
        }
    }
    
    echo json_encode([
        'skill1' => $skill1,
        'skill2' => $skill2,
        'match' => $match,
        'match_type' => $match_type,
        'match_score' => $match_score,
        'category1' => $cat1,
        'category2' => $cat2
    ]);
}

function findSkillCategory($skill) {
    global $conn;
    
    $skill_normalized = strtolower(trim($skill));
    
    // Check canonical names
    $sql = "SELECT category_id, category_name, canonical_name FROM skill_categories 
            WHERE LOWER(canonical_name) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $skill_normalized);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $row['canonical_normalized'] = strtolower($row['canonical_name']);
        return $row;
    }
    
    // Check synonyms
    $sql = "SELECT c.category_id, c.category_name, c.canonical_name 
            FROM skill_categories c 
            JOIN skill_synonyms s ON c.category_id = s.category_id 
            WHERE LOWER(s.synonym) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $skill_normalized);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $row['canonical_normalized'] = strtolower($row['canonical_name']);
        return $row;
    }
    
    return null;
}

function exportData() {
    global $conn;
    
    $sql = "SELECT c.category_id, c.category_name, c.canonical_name, 
                   GROUP_CONCAT(s.synonym SEPARATOR ', ') as synonyms 
            FROM skill_categories c 
            LEFT JOIN skill_synonyms s ON c.category_id = s.category_id 
            GROUP BY c.category_id";
    
    $result = $conn->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'category_name' => $row['category_name'],
            'canonical_name' => $row['canonical_name'],
            'synonyms' => $row['synonyms'] ? explode(', ', $row['synonyms']) : []
        ];
    }
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="skill_categories_' . date('Y-m-d') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function importData() {
    global $conn;
    
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['file']['tmp_name'];
    $json = json_decode(file_get_contents($file), true);
    
    if (!is_array($json)) {
        throw new Exception('Invalid JSON format');
    }
    
    $count = 0;
    foreach ($json as $item) {
        $category_name = $item['category_name'] ?? '';
        $canonical_name = $item['canonical_name'] ?? '';
        $synonyms = $item['synonyms'] ?? [];
        
        if (empty($category_name) || empty($canonical_name)) continue;
        
        // Check if category exists
        $check_sql = "SELECT category_id FROM skill_categories WHERE canonical_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $canonical_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            continue; // Skip existing
        }
        
        // Insert category
        $insert_sql = "INSERT INTO skill_categories (category_name, canonical_name) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('ss', $category_name, $canonical_name);
        $insert_stmt->execute();
        
        $category_id = $conn->insert_id;
        
        // Insert synonyms
        foreach ($synonyms as $synonym) {
            $syn_sql = "INSERT INTO skill_synonyms (category_id, synonym) VALUES (?, ?)";
            $syn_stmt = $conn->prepare($syn_sql);
            $syn_stmt->bind_param('is', $category_id, $synonym);
            $syn_stmt->execute();
        }
        
        $count++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Imported $count categories successfully"
    ]);
}
?>
