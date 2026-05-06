<?php
/**
 * Job Skills Normalization Script
 * Normalizes all job skills in the database to use canonical skill names
 * Can be run from CLI or web
 */

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/skill-normalizer.php';

$normalizer = new SkillNormalizer();
$conn = getConnection();

// Check if user is logged in and is admin (if web request)
if (PHP_SAPI !== 'cli') {
    session_start();
    
    // For web access, check if user is logged in
    if (!isset($_SESSION['employee_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    header('Content-Type: application/json');
}

echo PHP_SAPI === 'cli' ? "\n=== Normalizing Job Skills ===\n\n" : '';

try {
    // Get all job postings with skills
    $query = "SELECT job_post_id, title, skills FROM job_post WHERE skills IS NOT NULL AND skills != ''";
    $result = $conn->query($query);
    
    $totalJobs = 0;
    $updatedJobs = 0;
    $allSkillChanges = [];
    
    while ($row = $result->fetch_assoc()) {
        $totalJobs++;
        $job_id = $row['job_post_id'];
        $job_title = $row['title'];
        $skills_str = trim($row['skills']);
        
        if (empty($skills_str)) continue;
        
        // Parse skills
        $skills = array_map('trim', explode(',', $skills_str));
        $skills = array_filter($skills); // Remove empty values
        
        // Normalize each skill
        $normalized_skills = [];
        $changed = false;
        
        foreach ($skills as $skill) {
            $normalized = $normalizer->normalize($skill);
            $normalized_skills[] = $normalized;
            
            if ($normalized !== $skill) {
                $changed = true;
                if (!isset($allSkillChanges[$skill])) {
                    $allSkillChanges[$skill] = $normalized;
                }
            }
        }
        
        // Remove duplicates and rejoin
        $normalized_skills = array_unique($normalized_skills);
        $normalized_str = implode(', ', $normalized_skills);
        
        // Update if changed
        if ($changed || $normalized_str !== $skills_str) {
            $update = "UPDATE job_post SET skills = ? WHERE job_post_id = ?";
            $update_stmt = $conn->prepare($update);
            $update_stmt->bind_param('si', $normalized_str, $job_id);
            
            if ($update_stmt->execute()) {
                $updatedJobs++;
                echo PHP_SAPI === 'cli' ? "✓ Updated: {$job_title}\n  Before: {$skills_str}\n  After:  {$normalized_str}\n\n" : '';
            }
        }
    }
    
    // Summary
    $summary = [
        'success' => true,
        'message' => 'Job skill normalization complete',
        'total_jobs' => $totalJobs,
        'updated_jobs' => $updatedJobs,
        'skill_changes' => count($allSkillChanges),
        'examples' => array_slice($allSkillChanges, 0, 5)
    ];
    
    if (PHP_SAPI === 'cli') {
        echo "\n=== SUMMARY ===\n";
        echo "Total jobs: {$totalJobs}\n";
        echo "Updated: {$updatedJobs}\n";
        echo "Skill variations normalized: " . count($allSkillChanges) . "\n";
        echo "\nExamples of normalizations:\n";
        foreach (array_slice($allSkillChanges, 0, 5) as $from => $to) {
            echo "  '{$from}' → '{$to}'\n";
        }
        echo "\nDone!\n";
    } else {
        echo json_encode($summary, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    if (PHP_SAPI === 'cli') {
        echo "ERROR: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} finally {
    closeConnection($conn);
}
?>
