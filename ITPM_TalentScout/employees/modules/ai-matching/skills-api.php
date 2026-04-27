<?php
/**
 * Skills API
 * Endpoints for skill management, normalization, and suggestions
 * JSON API - returns application/json
 */

session_start();
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/skill-normalizer.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$employee_id = $_SESSION['employee_id'];
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
        case 'add':
            handleAddSkill();
            break;
        case 'remove':
            handleRemoveSkill();
            break;
        case 'get_all':
            handleGetAllSkills();
            break;
        case 'get_duplicates':
            handleGetDuplicates();
            break;
        case 'deduplicate_all':
            handleDeduplicateAll();
            break;
        case 'normalize_all':
            handleNormalizeAll();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

closeConnection($conn);

/**
 * Suggest skills based on partial input (autocomplete)
 */
function handleSuggest() {
    global $normalizer;
    
    $input = trim($_GET['q'] ?? '');
    if (strlen($input) < 2) {
        echo json_encode(['suggestions' => []]);
        return;
    }
    
    $suggestions = $normalizer->suggestSkills($input);
    echo json_encode(['suggestions' => array_slice($suggestions, 0, 10)]);
}

/**
 * Normalize a single skill input
 */
function handleNormalize() {
    global $normalizer;
    
    $skill = trim($_GET['skill'] ?? '');
    if (empty($skill)) {
        http_response_code(400);
        echo json_encode(['error' => 'Skill required']);
        return;
    }
    
    $result = $normalizer->normalizeWithConfidence($skill);
    echo json_encode($result);
}

/**
 * Add a skill to employee's profile
 */
function handleAddSkill() {
    global $conn, $employee_id, $normalizer;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $skill = trim($input['skill'] ?? '');
    
    if (empty($skill)) {
        http_response_code(400);
        echo json_encode(['error' => 'Skill required']);
        return;
    }
    
    // Normalize the skill
    $normalized = $normalizer->normalize($skill);
    
    // Check if skill already exists
    $check_query = "SELECT employee_skill_id FROM employee_skill WHERE employee_id = ? AND skill_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('is', $employee_id, $normalized);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        echo json_encode([
            'success' => false,
            'message' => 'Skill already exists',
            'skill' => $normalized
        ]);
        return;
    }
    
    // Add new skill
    $insert_query = "INSERT INTO employee_skill (employee_id, skill_name) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('is', $employee_id, $normalized);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Skill added successfully',
            'skill' => $normalized,
            'id' => $conn->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add skill']);
    }
}

/**
 * Remove a skill from employee's profile
 */
function handleRemoveSkill() {
    global $conn, $employee_id;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $skill = trim($input['skill'] ?? '');
    
    if (empty($skill)) {
        http_response_code(400);
        echo json_encode(['error' => 'Skill required']);
        return;
    }
    
    $delete_query = "DELETE FROM employee_skill WHERE employee_id = ? AND skill_name = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('is', $employee_id, $skill);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Skill removed successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove skill']);
    }
}

/**
 * Get all skills for current employee
 */
function handleGetAllSkills() {
    global $conn, $employee_id;
    
    $query = "SELECT DISTINCT skill_name FROM employee_skill WHERE employee_id = ? ORDER BY skill_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $skills = [];
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row['skill_name'];
    }
    
    echo json_encode([
        'success' => true,
        'skills' => $skills,
        'count' => count($skills)
    ]);
}

/**
 * Find duplicate skills across all employees
 */
function handleGetDuplicates() {
    global $conn;
    
    // Find skills with variations (case-insensitive duplicates)
    $query = "
        SELECT 
            LOWER(skill_name) as skill_lower,
            GROUP_CONCAT(DISTINCT skill_name) as variations,
            COUNT(DISTINCT employee_id) as employee_count,
            COUNT(*) as total_count
        FROM employee_skill
        GROUP BY LOWER(skill_name)
        HAVING COUNT(DISTINCT skill_name) > 1
        ORDER BY total_count DESC
    ";
    
    $result = $conn->query($query);
    $duplicates = [];
    
    while ($row = $result->fetch_assoc()) {
        $duplicates[] = [
            'base_skill' => $row['skill_lower'],
            'variations' => explode(',', $row['variations']),
            'employee_count' => $row['employee_count'],
            'total_count' => $row['total_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'duplicates' => $duplicates,
        'duplicate_count' => count($duplicates)
    ]);
}

/**
 * Deduplicate all skills in database
 * Merges variations to canonical names
 */
function handleDeduplicateAll() {
    global $conn, $normalizer;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get all employee skills
        $query = "SELECT DISTINCT employee_id, skill_name FROM employee_skill";
        $result = $conn->query($query);
        
        $updated = 0;
        $duplicates_merged = 0;
        
        while ($row = $result->fetch_assoc()) {
            $employee_id = $row['employee_id'];
            $skill = $row['skill_name'];
            $normalized = $normalizer->normalize($skill);
            
            if ($skill !== $normalized) {
                // Check if normalized skill already exists for this employee
                $check = "SELECT employee_skill_id FROM employee_skill WHERE employee_id = ? AND skill_name = ?";
                $check_stmt = $conn->prepare($check);
                $check_stmt->bind_param('is', $employee_id, $normalized);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    // Delete the duplicate
                    $delete = "DELETE FROM employee_skill WHERE employee_id = ? AND skill_name = ?";
                    $delete_stmt = $conn->prepare($delete);
                    $delete_stmt->bind_param('is', $employee_id, $skill);
                    $delete_stmt->execute();
                    $duplicates_merged++;
                } else {
                    // Update to normalized name
                    $update = "UPDATE employee_skill SET skill_name = ? WHERE employee_id = ? AND skill_name = ?";
                    $update_stmt = $conn->prepare($update);
                    $update_stmt->bind_param('sss', $normalized, $employee_id, $skill);
                    $update_stmt->execute();
                    $updated++;
                }
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Deduplication complete',
            'updated' => $updated,
            'merged' => $duplicates_merged
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Deduplication failed: ' . $e->getMessage()]);
    }
}

/**
 * Normalize all job skills in the database
 */
function handleNormalizeAll() {
    global $conn, $normalizer;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get all job postings
        $query = "SELECT job_post_id, skills FROM job_post WHERE skills IS NOT NULL AND skills != ''";
        $result = $conn->query($query);
        
        $updated = 0;
        
        while ($row = $result->fetch_assoc()) {
            $job_id = $row['job_post_id'];
            $skills_str = $row['skills'];
            
            // Parse and normalize skills
            $skills = array_map('trim', explode(',', $skills_str));
            $normalized_skills = array_map(function($skill) use ($normalizer) {
                return $normalizer->normalize($skill);
            }, $skills);
            
            // Remove duplicates and rejoin
            $normalized_str = implode(', ', array_unique($normalized_skills));
            
            if ($normalized_str !== $skills_str) {
                $update = "UPDATE job_post SET skills = ? WHERE job_post_id = ?";
                $update_stmt = $conn->prepare($update);
                $update_stmt->bind_param('si', $normalized_str, $job_id);
                $update_stmt->execute();
                $updated++;
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Job skill normalization complete',
            'jobs_updated' => $updated
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Normalization failed: ' . $e->getMessage()]);
    }
}
?>
