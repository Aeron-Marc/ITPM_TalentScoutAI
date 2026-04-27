<?php
/**
 * AI Matching Engine
 * Calculates job match scores based on skills, location, and experience
 */

require_once __DIR__ . '/skill-normalizer.php';

class MatchEngine {
    private $conn;
    private $employee_id;
    private $normalizer;

    public function __construct($db_connection, $employee_id) {
        $this->conn = $db_connection;
        $this->employee_id = $employee_id;
        $this->normalizer = new SkillNormalizer();
    }

    /**
     * Get employee profile with skills and experience
     */
    public function getEmployeeProfile() {
        $query = "
            SELECT 
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                e.address,
                COUNT(DISTINCT es.skill_name) as skill_count,
                COUNT(DISTINCT ee.experience_id) as experience_count,
                GROUP_CONCAT(DISTINCT es.skill_name) as skills
            FROM employee e
            LEFT JOIN employee_skill es ON e.employee_id = es.employee_id
            LEFT JOIN resumes r ON e.employee_id = r.employee_id
            LEFT JOIN employee_experience ee ON r.resume_id = ee.resume_id
            WHERE e.employee_id = ?
            GROUP BY e.employee_id
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Get employee skills as array (normalized)
     */
    public function getEmployeeSkills() {
        $query = "SELECT DISTINCT skill_name FROM employee_skill WHERE employee_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $skills = [];
        while ($row = $result->fetch_assoc()) {
            $normalized = $this->normalizer->normalize($row['skill_name']);
            $skills[] = strtolower($normalized);
        }
        
        return array_unique($skills); // Remove duplicates
    }

    /**
     * Normalize skill names for matching (uses SkillNormalizer)
     */
    private function normalizeSkill($skill) {
        return strtolower($this->normalizer->normalize(trim($skill)));
    }

    /**
     * Calculate skill match percentage
     */
    private function calculateSkillMatch($employee_skills, $job_skills_str) {
        if (empty($job_skills_str)) {
            return 100; // No skills required = 100% match
        }

        // Parse job skills
        $job_skills = array_map('trim', explode(',', $job_skills_str));
        $job_skills = array_map([$this, 'normalizeSkill'], $job_skills);
        $job_skills = array_filter($job_skills); // Remove empty strings
        
        if (empty($job_skills)) {
            return 100;
        }

        $matches = 0;
        foreach ($job_skills as $job_skill) {
            foreach ($employee_skills as $emp_skill) {
                // Exact match
                if ($emp_skill === $job_skill) {
                    $matches++;
                    break;
                }
                // Partial match (e.g., "javascript" contains "script")
                if (strlen($job_skill) > 3 && strpos($emp_skill, $job_skill) !== false) {
                    $matches += 0.7;
                    break;
                }
                if (strlen($emp_skill) > 3 && strpos($job_skill, $emp_skill) !== false) {
                    $matches += 0.5;
                    break;
                }
            }
        }

        return round(($matches / count($job_skills)) * 100);
    }

    /**
     * Get all jobs and calculate match scores
     */
    public function getMatchedJobs($limit = 50) {
        $employee_skills = $this->getEmployeeSkills();
        $employee_profile = $this->getEmployeeProfile();
        
        $query = "
            SELECT 
                jp.job_post_id,
                jp.employer_id,
                jp.title,
                jp.description,
                jp.salary,
                jp.location,
                jp.work_type,
                jp.application_deadline,
                jp.skills,
                jp.experience_level,
                jp.job_category,
                jp.job_post_created,
                e.company_name
            FROM job_post jp
            LEFT JOIN employer e ON jp.employer_id = e.employer_id
            ORDER BY jp.job_post_created DESC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $matches = [];
        while ($job = $result->fetch_assoc()) {
            // Calculate individual scores
            $skill_match = $this->calculateSkillMatch($employee_skills, $job['skills']);
            $experience_match = $this->calculateExperienceMatch($employee_profile['experience_count'], $job['experience_level']);
            $location_match = $this->calculateLocationMatch($employee_profile['address'], $job['location'], $job['work_type']);
            
            // Weighted overall score (50% skills, 30% location, 20% experience)
            $overall_match = round(
                ($skill_match * 0.50) + 
                ($location_match * 0.30) + 
                ($experience_match * 0.20)
            );
            
            // Only include jobs with 30%+ match
            if ($overall_match >= 30) {
                $job['skill_match'] = $skill_match;
                $job['experience_match'] = $experience_match;
                $job['location_match'] = $location_match;
                $job['overall_match'] = $overall_match;
                $job['missing_skills'] = $this->getMissingSkills($employee_skills, $job['skills']);
                $job['matched_skills'] = $this->getMatchedSkills($employee_skills, $job['skills']);
                
                $matches[] = $job;
            }
        }
        
        // Sort by overall match score (highest first)
        usort($matches, function($a, $b) {
            return $b['overall_match'] <=> $a['overall_match'];
        });
        
        return $matches;
    }

    /**
     * Calculate experience level match
     */
    private function calculateExperienceMatch($emp_experience_count, $job_experience_level) {
        $level_map = [
            'entry level' => 1,
            'entry' => 1,
            'junior' => 1,
            'mid level' => 2,
            'mid' => 2,
            'intermediate' => 2,
            'senior' => 3,
            'lead' => 4,
            'manager' => 4,
        ];
        
        $required_exp = $level_map[strtolower(trim($job_experience_level))] ?? 1;
        
        // Convert employee experience count to level
        // Assume: 0 = entry, 1-2 = mid, 3+ = senior
        $emp_level = ($emp_experience_count === 0) ? 1 : (($emp_experience_count <= 2) ? 2 : 3);
        
        if ($emp_level >= $required_exp) {
            return 100; // Employee meets or exceeds requirement
        } else {
            // Partial match: 50% per missing level
            return max(50, 100 - (($required_exp - $emp_level) * 30));
        }
    }

    /**
     * Calculate location match
     */
    private function calculateLocationMatch($employee_location, $job_location, $work_type) {
        $work_type = strtolower(trim($work_type));
        
        // Remote jobs are always 100% match
        if (strpos($work_type, 'remote') !== false) {
            return 100;
        }
        
        // Exact location match
        if (strtolower(trim($employee_location)) === strtolower(trim($job_location))) {
            return 100;
        }
        
        // Same city/barangay partial match
        $emp_parts = explode(',', $employee_location);
        $job_parts = explode(',', $job_location);
        
        if (!empty($emp_parts) && !empty($job_parts)) {
            // Check if last part (city) matches
            $emp_city = strtolower(trim(end($emp_parts)));
            $job_city = strtolower(trim(end($job_parts)));
            
            if ($emp_city === $job_city) {
                return 90; // Same city
            }
        }
        
        // Hybrid slightly better than on-site
        if (strpos($work_type, 'hybrid') !== false) {
            return 70;
        }
        
        // Different location = 50%
        return 50;
    }

    /**
     * Get matched skills between employee and job
     */
    private function getMatchedSkills($employee_skills, $job_skills_str) {
        if (empty($job_skills_str)) {
            return [];
        }
        
        $job_skills = array_map('trim', explode(',', $job_skills_str));
        $matched = [];
        
        foreach ($job_skills as $job_skill) {
            $normalized_job = $this->normalizeSkill($job_skill);
            foreach ($employee_skills as $emp_skill) {
                if ($emp_skill === $normalized_job || 
                    strpos($emp_skill, $normalized_job) !== false ||
                    strpos($normalized_job, $emp_skill) !== false) {
                    $matched[] = trim($job_skill);
                    break;
                }
            }
        }
        
        return $matched;
    }

    /**
     * Get missing skills for the job
     */
    private function getMissingSkills($employee_skills, $job_skills_str) {
        if (empty($job_skills_str)) {
            return [];
        }
        
        $job_skills = array_map('trim', explode(',', $job_skills_str));
        $missing = [];
        
        foreach ($job_skills as $job_skill) {
            $normalized_job = $this->normalizeSkill($job_skill);
            $has_skill = false;
            
            foreach ($employee_skills as $emp_skill) {
                if ($emp_skill === $normalized_job || 
                    strpos($emp_skill, $normalized_job) !== false ||
                    strpos($normalized_job, $emp_skill) !== false) {
                    $has_skill = true;
                    break;
                }
            }
            
            if (!$has_skill) {
                $missing[] = trim($job_skill);
            }
        }
        
        return $missing;
    }

    /**
     * Get employer name
     */
    public function getEmployerName($employer_id) {
        $query = "SELECT company_name FROM employer WHERE employer_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $employer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['company_name'] ?? 'Unknown Company';
    }
}
?>
