<?php
session_start();
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../ai-matching/skill-normalizer.php';

function rbJsonResponse($payload, $statusCode = 200)
{
  http_response_code($statusCode);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function rbNormalizeMonthToDate($value)
{
  $clean = trim((string)$value);
  if (preg_match('/^\d{4}-\d{2}$/', $clean) !== 1) {
    return null;
  }

  return $clean . '-01';
}

function rbDateToMonth($value)
{
  if (!$value) {
    return '';
  }

  $timestamp = strtotime((string)$value);
  if ($timestamp === false) {
    return '';
  }

  return date('Y-m', $timestamp);
}

function rbSplitLines($value)
{
  $lines = preg_split('/\r\n|\r|\n/', (string)$value);
  $result = [];

  foreach ($lines as $line) {
    $clean = trim($line);
    if ($clean !== '') {
      $result[] = $clean;
    }
  }

  return $result;
}

function rbFindEmployeeId($conn, $employeeIdInput, $emailInput)
{
  $sessionEmployeeId = isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0;
  if ($sessionEmployeeId > 0) {
    $stmt = $conn->prepare('SELECT employee_id FROM employee WHERE employee_id = ? LIMIT 1');
    $stmt->bind_param('i', $sessionEmployeeId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      return (int)$row['employee_id'];
    }
  }

  $employeeId = (int)$employeeIdInput;
  if ($employeeId > 0) {
    $stmt = $conn->prepare('SELECT employee_id FROM employee WHERE employee_id = ? LIMIT 1');
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      return (int)$row['employee_id'];
    }
  }

  $sessionEmail = isset($_SESSION['employee_email']) ? trim((string)$_SESSION['employee_email']) : '';
  $email = $sessionEmail !== '' ? $sessionEmail : trim((string)$emailInput);
  if ($email !== '') {
    $stmt = $conn->prepare('SELECT employee_id FROM employee WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
      return (int)$row['employee_id'];
    }
  }

  return 0;
}

function rbDeleteResumeGraph($conn, $resumeId)
{
  $deleteSkillsStmt = $conn->prepare('DELETE FROM resume_skills WHERE resume_id = ?');
  $deleteSkillsStmt->bind_param('i', $resumeId);
  $deleteSkillsStmt->execute();

  $deleteAddStmt = $conn->prepare('DELETE FROM employee_additional_info WHERE resume_id = ?');
  $deleteAddStmt->bind_param('i', $resumeId);
  $deleteAddStmt->execute();

  $deleteBulletsStmt = $conn->prepare('DELETE eb FROM experience_bullets eb INNER JOIN employee_experience ee ON ee.experience_id = eb.experience_id WHERE ee.resume_id = ?');
  $deleteBulletsStmt->bind_param('i', $resumeId);
  $deleteBulletsStmt->execute();

  $deleteWorkStmt = $conn->prepare('DELETE FROM employee_experience WHERE resume_id = ?');
  $deleteWorkStmt->bind_param('i', $resumeId);
  $deleteWorkStmt->execute();

  $deleteEduStmt = $conn->prepare('DELETE FROM employee_education WHERE resume_id = ?');
  $deleteEduStmt->bind_param('i', $resumeId);
  $deleteEduStmt->execute();

  $deleteResumeStmt = $conn->prepare('DELETE FROM resumes WHERE resume_id = ?');
  $deleteResumeStmt->bind_param('i', $resumeId);
  $deleteResumeStmt->execute();
}

function rbKeepOnlyLatestResume($conn, $employeeId)
{
  $ids = [];
  $stmt = $conn->prepare('SELECT resume_id FROM resumes WHERE employee_id = ? ORDER BY updated_at DESC, resume_id DESC');
  $stmt->bind_param('i', $employeeId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $ids[] = (int)$row['resume_id'];
  }

  if (count($ids) <= 1) {
    return;
  }

  for ($i = 1; $i < count($ids); $i++) {
    rbDeleteResumeGraph($conn, $ids[$i]);
  }
}

$rawBodyForAction = file_get_contents('php://input');
$jsonBodyForAction = json_decode($rawBodyForAction ?: '', true);
if (!is_array($jsonBodyForAction)) {
  $jsonBodyForAction = [];
}

$apiAction = $_POST['api_action'] ?? $_GET['api_action'] ?? ($jsonBodyForAction['api_action'] ?? '');
if ($apiAction === 'save' || $apiAction === 'load') {
  $conn = getConnection();
  $normalizer = new SkillNormalizer();
  $inTransaction = false;

  try {
    $jsonBody = $jsonBodyForAction;

    $employeeIdInput = $_POST['employee_id'] ?? $_GET['employee_id'] ?? ($jsonBody['employee_id'] ?? 0);
    $emailInput = $_POST['email'] ?? $_GET['email'] ?? ($jsonBody['email'] ?? '');
    $employeeId = rbFindEmployeeId($conn, $employeeIdInput, $emailInput);

    if ($employeeId <= 0) {
      rbJsonResponse([
        'success' => false,
        'message' => 'No logged-in employee identity found. Please log in again.',
      ], 400);
    }

    rbKeepOnlyLatestResume($conn, $employeeId);

    if ($apiAction === 'load') {
      $stmt = $conn->prepare('SELECT resume_id, full_name, photo_data_url, address, phone, email, website, summary FROM resumes WHERE employee_id = ? ORDER BY updated_at DESC, resume_id DESC LIMIT 1');
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $resumeRes = $stmt->get_result();
      $resumeRow = $resumeRes->fetch_assoc();

      if (!$resumeRow) {
        // No resume exists yet, load existing skills from employee_skill table
        $skills = [];
        $employeeSkillsStmt = $conn->prepare('SELECT skill_name FROM employee_skill WHERE employee_id = ? ORDER BY skill_id ASC');
        $employeeSkillsStmt->bind_param('i', $employeeId);
        $employeeSkillsStmt->execute();
        $employeeSkillsRes = $employeeSkillsStmt->get_result();
        while ($row = $employeeSkillsRes->fetch_assoc()) {
          $skills[] = (string)$row['skill_name'];
        }

        rbJsonResponse([
          'success' => true,
          'employee_id' => $employeeId,
          'data' => null,
          'existing_skills' => $skills, // Pre-populate with existing skills
        ]);
      }

      $resumeId = (int)$resumeRow['resume_id'];

      $skills = [];
      $skillsStmt = $conn->prepare('SELECT skill_name FROM resume_skills WHERE resume_id = ? ORDER BY skill_id ASC');
      $skillsStmt->bind_param('i', $resumeId);
      $skillsStmt->execute();
      $skillsRes = $skillsStmt->get_result();
      while ($row = $skillsRes->fetch_assoc()) {
        $skills[] = (string)$row['skill_name'];
      }

      $employeeSkillsStmt = $conn->prepare('SELECT skill_name FROM employee_skill WHERE employee_id = ?');
      $employeeSkillsStmt->bind_param('i', $employeeId);
      $employeeSkillsStmt->execute();
      $employeeSkillsRes = $employeeSkillsStmt->get_result();
      while ($row = $employeeSkillsRes->fetch_assoc()) {
        if (!in_array($row['skill_name'], $skills)) {
          $skills[] = (string)$row['skill_name'];
        }
      }

      $additionalLines = [];
      $addStmt = $conn->prepare('SELECT description FROM employee_additional_info WHERE resume_id = ? ORDER BY info_id ASC');
      $addStmt->bind_param('i', $resumeId);
      $addStmt->execute();
      $addRes = $addStmt->get_result();
      while ($row = $addRes->fetch_assoc()) {
        $line = trim((string)$row['description']);
        if ($line !== '') {
          $additionalLines[] = $line;
        }
      }

      $workExperience = [];
      $workStmt = $conn->prepare('SELECT experience_id, job_title, company_name, start_date, end_date, is_present FROM employee_experience WHERE resume_id = ? ORDER BY experience_id ASC');
      $workStmt->bind_param('i', $resumeId);
      $workStmt->execute();
      $workRes = $workStmt->get_result();

      while ($workRow = $workRes->fetch_assoc()) {
        $experienceId = (int)$workRow['experience_id'];

        $bulletLines = [];
        $bulletStmt = $conn->prepare('SELECT description FROM experience_bullets WHERE experience_id = ? ORDER BY bullet_id ASC');
        $bulletStmt->bind_param('i', $experienceId);
        $bulletStmt->execute();
        $bulletRes = $bulletStmt->get_result();
        while ($bulletRow = $bulletRes->fetch_assoc()) {
          $bullet = trim((string)$bulletRow['description']);
          if ($bullet !== '') {
            $bulletLines[] = $bullet;
          }
        }

        $workExperience[] = [
          'title' => (string)($workRow['job_title'] ?? ''),
          'startDate' => rbDateToMonth($workRow['start_date'] ?? null),
          'endDate' => ((int)($workRow['is_present'] ?? 0) === 1) ? '' : rbDateToMonth($workRow['end_date'] ?? null),
          'company' => (string)($workRow['company_name'] ?? ''),
          'bullets' => implode("\n", $bulletLines),
        ];
      }

      $education = [];
      $eduStmt = $conn->prepare('SELECT degree, school, start_date, end_date, is_current, details FROM employee_education WHERE resume_id = ? ORDER BY education_id ASC');
      $eduStmt->bind_param('i', $resumeId);
      $eduStmt->execute();
      $eduRes = $eduStmt->get_result();
      while ($eduRow = $eduRes->fetch_assoc()) {
        $education[] = [
          'degree' => (string)($eduRow['degree'] ?? ''),
          'startDate' => rbDateToMonth($eduRow['start_date'] ?? null),
          'endDate' => ((int)($eduRow['is_current'] ?? 0) === 1) ? '' : rbDateToMonth($eduRow['end_date'] ?? null),
          'school' => (string)($eduRow['school'] ?? ''),
          'details' => (string)($eduRow['details'] ?? ''),
        ];
      }

      rbJsonResponse([
        'success' => true,
        'employee_id' => $employeeId,
        'data' => [
          'fullName' => (string)($resumeRow['full_name'] ?? ''),
          'photoDataUrl' => (string)($resumeRow['photo_data_url'] ?? ''),
          'address' => (string)($resumeRow['address'] ?? ''),
          'phone' => (string)($resumeRow['phone'] ?? ''),
          'email' => (string)($resumeRow['email'] ?? ''),
          'website' => (string)($resumeRow['website'] ?? ''),
          'summary' => (string)($resumeRow['summary'] ?? ''),
          'skills' => $skills,
          'additional' => implode("\n", $additionalLines),
          'workExperience' => $workExperience,
          'education' => $education,
        ],
      ]);
    }

    $payloadData = $jsonBody['data'] ?? [];
    if (!is_array($payloadData)) {
      $payloadData = [];
    }

    $fullName = trim((string)($payloadData['fullName'] ?? ''));
    $photoDataUrl = (string)($payloadData['photoDataUrl'] ?? '');
    $address = trim((string)($payloadData['address'] ?? ''));
    $phone = trim((string)($payloadData['phone'] ?? ''));
    $email = trim((string)($payloadData['email'] ?? $emailInput));
    $website = trim((string)($payloadData['website'] ?? ''));
    $summary = trim((string)($payloadData['summary'] ?? ''));
    $skills = isset($payloadData['skills']) && is_array($payloadData['skills']) ? $payloadData['skills'] : [];
    $additionalText = (string)($payloadData['additional'] ?? '');
    $workExperience = isset($payloadData['workExperience']) && is_array($payloadData['workExperience']) ? $payloadData['workExperience'] : [];
    $education = isset($payloadData['education']) && is_array($payloadData['education']) ? $payloadData['education'] : [];

    $conn->begin_transaction();
    $inTransaction = true;

    $resumeId = 0;
    $findStmt = $conn->prepare('SELECT resume_id FROM resumes WHERE employee_id = ? ORDER BY updated_at DESC, resume_id DESC LIMIT 1');
    $findStmt->bind_param('i', $employeeId);
    $findStmt->execute();
    $findRes = $findStmt->get_result();
    if ($existing = $findRes->fetch_assoc()) {
      $resumeId = (int)$existing['resume_id'];
    }

    if ($resumeId > 0) {
      $updateStmt = $conn->prepare('UPDATE resumes SET full_name = ?, photo_data_url = ?, address = ?, phone = ?, email = ?, website = ?, summary = ? WHERE resume_id = ?');
      $updateStmt->bind_param('sssssssi', $fullName, $photoDataUrl, $address, $phone, $email, $website, $summary, $resumeId);
      $updateStmt->execute();
    } else {
      $insertStmt = $conn->prepare('INSERT INTO resumes (employee_id, full_name, photo_data_url, address, phone, email, website, summary) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
      $insertStmt->bind_param('isssssss', $employeeId, $fullName, $photoDataUrl, $address, $phone, $email, $website, $summary);
      $insertStmt->execute();
      $resumeId = (int)$conn->insert_id;
    }

    $deleteSkillsStmt = $conn->prepare('DELETE FROM resume_skills WHERE resume_id = ?');
    $deleteSkillsStmt->bind_param('i', $resumeId);
    $deleteSkillsStmt->execute();

    $deleteAddStmt = $conn->prepare('DELETE FROM employee_additional_info WHERE resume_id = ?');
    $deleteAddStmt->bind_param('i', $resumeId);
    $deleteAddStmt->execute();

    $deleteBulletsStmt = $conn->prepare('DELETE eb FROM experience_bullets eb INNER JOIN employee_experience ee ON ee.experience_id = eb.experience_id WHERE ee.resume_id = ?');
    $deleteBulletsStmt->bind_param('i', $resumeId);
    $deleteBulletsStmt->execute();

    $deleteWorkStmt = $conn->prepare('DELETE FROM employee_experience WHERE resume_id = ?');
    $deleteWorkStmt->bind_param('i', $resumeId);
    $deleteWorkStmt->execute();

    $deleteEduStmt = $conn->prepare('DELETE FROM employee_education WHERE resume_id = ?');
    $deleteEduStmt->bind_param('i', $resumeId);
    $deleteEduStmt->execute();

    if (!empty($skills)) {
      $insertSkillStmt = $conn->prepare('INSERT INTO resume_skills (resume_id, skill_name) VALUES (?, ?)');

      foreach ($skills as $skill) {
        $skillName = trim((string)$skill);
        if ($skillName === '') {
          continue;
        }

        // Normalize skill before saving
        $normalizedSkill = $normalizer->getCanonicalForm($skillName, $conn);

        $insertSkillStmt->bind_param('is', $resumeId, $normalizedSkill);
        $insertSkillStmt->execute();
      }

      $conn->query('INSERT IGNORE INTO employee_skill (employee_id, skill_name) SELECT DISTINCT ' . (int)$employeeId . ', skill_name FROM resume_skills WHERE resume_id = ' . (int)$resumeId);
    }

    $additionalLines = rbSplitLines($additionalText);
    if (!empty($additionalLines)) {
      $insertAdditionalStmt = $conn->prepare('INSERT INTO employee_additional_info (resume_id, description) VALUES (?, ?)');
      foreach ($additionalLines as $line) {
        $insertAdditionalStmt->bind_param('is', $resumeId, $line);
        $insertAdditionalStmt->execute();
      }
    }

    if (!empty($workExperience)) {
      $insertWorkStmt = $conn->prepare('INSERT INTO employee_experience (resume_id, job_title, company_name, start_date, end_date, is_present) VALUES (?, ?, ?, ?, ?, ?)');
      $insertBulletStmt = $conn->prepare('INSERT INTO experience_bullets (experience_id, description) VALUES (?, ?)');

      foreach ($workExperience as $item) {
        if (!is_array($item)) {
          continue;
        }

        $title = trim((string)($item['title'] ?? ''));
        $company = trim((string)($item['company'] ?? ''));
        $startDate = rbNormalizeMonthToDate($item['startDate'] ?? '');
        $endDate = rbNormalizeMonthToDate($item['endDate'] ?? '');
        $isPresent = $endDate === null ? 1 : 0;

        $insertWorkStmt->bind_param('issssi', $resumeId, $title, $company, $startDate, $endDate, $isPresent);
        $insertWorkStmt->execute();

        $experienceId = (int)$conn->insert_id;
        $bullets = rbSplitLines($item['bullets'] ?? '');
        foreach ($bullets as $bullet) {
          $insertBulletStmt->bind_param('is', $experienceId, $bullet);
          $insertBulletStmt->execute();
        }
      }
    }

    if (!empty($education)) {
      $insertEduStmt = $conn->prepare('INSERT INTO employee_education (resume_id, degree, school, start_date, end_date, is_current, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
      foreach ($education as $item) {
        if (!is_array($item)) {
          continue;
        }

        $degree = trim((string)($item['degree'] ?? ''));
        $school = trim((string)($item['school'] ?? ''));
        $startDate = rbNormalizeMonthToDate($item['startDate'] ?? '');
        $endDate = rbNormalizeMonthToDate($item['endDate'] ?? '');
        $isCurrent = $endDate === null ? 1 : 0;
        $details = trim((string)($item['details'] ?? ''));

        $insertEduStmt->bind_param('issssis', $resumeId, $degree, $school, $startDate, $endDate, $isCurrent, $details);
        $insertEduStmt->execute();
      }
    }

    rbKeepOnlyLatestResume($conn, $employeeId);

    $conn->commit();
    $inTransaction = false;

    rbJsonResponse([
      'success' => true,
      'employee_id' => $employeeId,
      'resume_id' => $resumeId,
      'message' => 'Resume saved to database successfully.',
    ]);
  } catch (Throwable $error) {
    if ($inTransaction) {
      $conn->rollback();
    }

    rbJsonResponse([
      'success' => false,
      'message' => 'Failed to process resume request.',
      'error' => $error->getMessage(),
    ], 500);
  } finally {
    closeConnection($conn);
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resume Builder | Job Seekers - TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sand:        #f5f0e8;
      --sand-dark:   #ece5d5;
      --sage:        #6b8f71;
      --sage-light:  #9ab89f;
      --sage-pale:   #d4e6d6;
      --sage-deep:   #4a6b50;
      --stone:       #8a8070;
      --stone-light: #c4b9a8;
      --cream:       #faf8f3;
      --charcoal:    #2a2a22;
      --warm-mid:    #5a5448;
      --warm-light:  #9a9288;
      --gold:        #c8a96e;
      --gold-pale:   #f0e4c8;
      --white-t:     rgba(255,255,255,0.92);
      --radius-xl:   24px;
      --radius-lg:   16px;
      --radius-md:   10px;
      --radius-pill: 999px;
      --ease-out:    cubic-bezier(0.22, 1, 0.36, 1);
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--cream);
      color: var(--charcoal);
      min-height: 100vh;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 9999;
      opacity: 0.03;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
    }

    a { text-decoration: none; color: inherit; }

    /* ── NAVBAR ── */
    .navbar {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 3rem;
      height: 64px;
      background: rgba(250, 248, 243, 0.88);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(139, 128, 112, 0.12);
      animation: slideDown 0.6s var(--ease-out) both;
    }

    @keyframes slideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to   { transform: translateY(0); opacity: 1; }
    }

    .nav-logo {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-size: 1.15rem;
      color: var(--charcoal);
      letter-spacing: -0.01em;
    }

    .nav-logo-mark {
      width: 34px; height: 34px;
      background: var(--sage-deep);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.72rem;
      font-weight: 600;
      color: #fff;
      letter-spacing: 0.04em;
    }

    .nav-logo em { font-style: italic; color: var(--sage); }

    .nav-links {
      display: flex;
      list-style: none;
      gap: 0.15rem;
    }

    .nav-links a {
      padding: 0.38rem 0.85rem;
      border-radius: var(--radius-pill);
      font-size: 0.84rem;
      font-weight: 400;
      color: var(--warm-mid);
      transition: background 0.2s, color 0.2s;
      letter-spacing: 0.01em;
    }

    .nav-links a:hover, .nav-links a.active {
      background: var(--sage-pale);
      color: var(--sage-deep);
    }

    .nav-right {
      display: flex; align-items: center; gap: 0.7rem;
    }

    .nav-user {
      font-size: 0.83rem;
      color: var(--warm-mid);
    }

    .btn-nav-ghost {
      padding: 0.4rem 1rem;
      border-radius: var(--radius-pill);
      border: 1px solid var(--stone-light);
      color: var(--warm-mid);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.83rem;
      font-weight: 500;
      background: transparent;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }

    .btn-nav-ghost:hover { background: var(--sand); border-color: var(--stone); }

    .btn-nav-solid {
      padding: 0.44rem 1.2rem;
      border-radius: var(--radius-pill);
      background: var(--sage-deep);
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.83rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      display: flex; align-items: center; gap: 0.35rem;
    }

    .btn-nav-solid:hover { background: var(--sage); transform: translateY(-1px); }

    /* ── PAGE SHELL ── */
    .builder-shell {
      max-width: 1400px;
      margin: 0 auto;
      padding: 6rem 2rem 4rem;
      animation: fadeUp 0.7s var(--ease-out) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .builder-header {
      margin-bottom: 2rem;
    }

    .builder-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.8rem, 3vw, 2.4rem);
      font-weight: 900;
      color: var(--charcoal);
      letter-spacing: -0.025em;
      line-height: 1.2;
    }

    .builder-header h1 em {
      font-style: italic;
      color: var(--sage);
    }

    .builder-header p {
      color: var(--warm-mid);
      margin-top: 0.5rem;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    .builder-grid {
      display: grid;
      grid-template-columns: minmax(360px, 1fr) minmax(420px, 1fr);
      gap: 1.5rem;
      align-items: start;
    }

    /* ── PANELS ── */
    .panel {
      background: var(--white-t);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(139, 128, 112, 0.12);
      border-radius: var(--radius-xl);
      box-shadow: 0 4px 24px rgba(42,42,34,0.07);
      overflow: hidden;
      transition: box-shadow 0.3s;
    }

    .panel:hover {
      box-shadow: 0 12px 40px rgba(42,42,34,0.12);
    }

    .panel-head {
      padding: 1rem 1.3rem;
      border-bottom: 1px solid rgba(139, 128, 112, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.7rem;
      background: rgba(245, 240, 232, 0.5);
    }

    .panel-title {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--charcoal);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .template-chip {
      font-size: 0.72rem;
      color: var(--sage-deep);
      font-weight: 600;
      background: var(--sage-pale);
      border: 1px solid rgba(107, 143, 113, 0.2);
      padding: 0.22rem 0.7rem;
      border-radius: var(--radius-pill);
    }

    /* ── PREVIEW ── */
    .preview-wrap {
      padding: 1.2rem;
      position: sticky;
      top: calc(64px + 1rem);
      max-height: calc(100vh - 64px - 2rem);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .resume-preview {
      border: 1px solid rgba(139, 128, 112, 0.15);
      border-radius: var(--radius-lg);
      padding: 1.4rem;
      color: var(--charcoal);
      background: #fff;
      font-family: 'DM Sans', sans-serif;
      line-height: 1.4;
      overflow-y: auto;
      flex: 1;
      min-height: 0;
    }

    .rp-head {
      display: grid;
      grid-template-columns: 80px 1fr;
      gap: 1rem;
      align-items: start;
    }

    .rp-photo {
      width: 80px;
      height: 95px;
      border: 2px solid var(--sage-pale);
      border-radius: var(--radius-md);
      object-fit: cover;
      background: var(--sand);
    }

    .rp-name {
      margin: 0;
      color: var(--charcoal);
      font-family: 'Playfair Display', serif;
      font-size: 1.55rem;
      font-weight: 900;
      line-height: 1.15;
      letter-spacing: -0.01em;
    }

    .rp-contact {
      margin-top: 0.4rem;
      font-size: 0.78rem;
      color: var(--warm-mid);
    }

    .rp-contact b {
      color: var(--charcoal);
      margin-right: 0.25rem;
      font-weight: 600;
    }

    .rp-section {
      margin-top: 1rem;
    }

    .rp-title {
      color: var(--sage-deep);
      font-size: 0.82rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border-bottom: 2px solid var(--sage-pale);
      padding-bottom: 0.2rem;
      margin-bottom: 0.45rem;
    }

    .rp-text {
      font-size: 0.79rem;
      color: var(--warm-mid);
      line-height: 1.6;
    }

    .rp-job {
      margin-bottom: 0.65rem;
    }

    .rp-job-head {
      display: flex;
      justify-content: space-between;
      gap: 0.7rem;
      font-size: 0.79rem;
    }

    .rp-job-title {
      font-weight: 700;
      color: var(--charcoal);
    }

    .rp-date {
      font-weight: 600;
      white-space: nowrap;
      color: var(--sage);
      font-size: 0.76rem;
    }

    .rp-company {
      font-size: 0.77rem;
      color: var(--warm-mid);
    }

    .rp-list {
      margin: 0.25rem 0 0.1rem 1rem;
      font-size: 0.76rem;
      color: var(--warm-mid);
      line-height: 1.55;
    }

    .rp-additional-list {
      margin: 0.2rem 0 0.1rem 1rem;
      font-size: 0.76rem;
      color: var(--warm-mid);
      line-height: 1.55;
    }

    .rp-skills-list {
      margin: 0.2rem 0 0.1rem;
      padding: 0;
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      gap: 0.25rem 0.85rem;
      font-size: 0.76rem;
    }

    .rp-skills-list li {
      position: relative;
      padding-left: 0.7rem;
      line-height: 1.3;
      color: var(--warm-mid);
    }

    .rp-skills-list li::before {
      content: "•";
      position: absolute;
      left: 0;
      top: 0;
      color: var(--sage);
      font-weight: 700;
    }

    /* ── FORM ── */
    .form-wrap {
      padding: 1.2rem;
    }

    .resume-form {
      display: grid;
      gap: 1.1rem;
    }

    .group {
      border: 1px solid rgba(139, 128, 112, 0.12);
      border-radius: var(--radius-lg);
      padding: 1rem 1.1rem;
      background: var(--sand);
      transition: border-color 0.2s;
    }

    .group:hover {
      border-color: var(--sage-pale);
    }

    .group h3 {
      font-family: 'DM Sans', sans-serif;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--charcoal);
      margin-bottom: 0.65rem;
    }

    .group-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.7rem;
      margin-bottom: 0.65rem;
    }

    .group-head h3 {
      margin-bottom: 0;
    }

    .add-btn {
      border: 1px solid var(--sage-pale);
      color: var(--sage-deep);
      background: rgba(212, 230, 214, 0.4);
      font-size: 0.74rem;
      font-weight: 700;
      border-radius: var(--radius-pill);
      padding: 0.38rem 0.75rem;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s, border-color 0.2s;
    }

    .add-btn:hover {
      background: var(--sage-pale);
      border-color: var(--sage-light);
    }

    .entry-card {
      border: 1px dashed var(--stone-light);
      border-radius: var(--radius-lg);
      padding: 0.85rem;
      margin-bottom: 0.75rem;
      background: var(--white-t);
      transition: border-color 0.2s;
    }

    .entry-card:hover {
      border-color: var(--sage-light);
    }

    .entry-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.7rem;
      margin-bottom: 0.55rem;
    }

    .entry-title {
      font-size: 0.74rem;
      font-weight: 700;
      color: var(--warm-mid);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .remove-btn {
      border: 1px solid rgba(170, 79, 79, 0.2);
      background: rgba(255, 246, 246, 0.8);
      color: #aa4f4f;
      border-radius: var(--radius-pill);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.22rem 0.55rem;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s, border-color 0.2s;
    }

    .remove-btn:hover {
      background: rgba(255, 230, 230, 1);
      border-color: rgba(170, 79, 79, 0.35);
    }

    .field-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
    }

    .resume-form label {
      display: block;
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--warm-mid);
    }

    .resume-form input,
    .resume-form textarea {
      width: 100%;
      margin-top: 0.3rem;
      border: 1.5px solid var(--stone-light);
      border-radius: var(--radius-md);
      padding: 0.66rem 0.8rem;
      font-size: 0.86rem;
      font-family: 'DM Sans', sans-serif;
      background: #fff;
      color: var(--charcoal);
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .resume-form input:focus,
    .resume-form textarea:focus {
      outline: none;
      border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(107, 143, 113, 0.15);
    }

    .resume-form textarea {
      min-height: 78px;
      resize: vertical;
    }

    .helper {
      margin-top: 0.25rem;
      font-size: 0.73rem;
      color: var(--warm-light);
    }

    .resume-actions {
      display: flex;
      gap: 0.7rem;
      flex-wrap: wrap;
    }

    .btn-primary {
      padding: 0.68rem 1.5rem;
      background: var(--sage-deep);
      color: #fff;
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.86rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      display: inline-flex; align-items: center; gap: 0.35rem;
      transition: background 0.2s, transform 0.15s;
      box-shadow: 0 4px 14px rgba(74,107,80,0.28);
    }

    .btn-primary:hover { background: var(--sage); transform: translateY(-1px); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .btn-outline {
      padding: 0.68rem 1.4rem;
      background: transparent;
      color: var(--warm-mid);
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.86rem;
      font-weight: 500;
      border: 1.5px solid var(--stone-light);
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s;
      display: inline-flex; align-items: center; gap: 0.35rem;
    }

    .btn-outline:hover { background: var(--sand); border-color: var(--stone); }

    /* ── SKILLS ── */
    .skill-input-wrap {
      display: flex;
      gap: 0.6rem;
      margin-bottom: 1rem;
      align-items: flex-end;
    }

    .skill-input-wrap label {
      flex: 1;
      min-width: 200px;
    }

    .skill-input-wrap input {
      width: 100%;
    }

    .skill-add-btn {
      border: 1px solid var(--sage-pale);
      color: var(--sage-deep);
      background: rgba(212, 230, 214, 0.4);
      font-size: 0.76rem;
      font-weight: 700;
      border-radius: var(--radius-pill);
      padding: 0.66rem 0.9rem;
      cursor: pointer;
      white-space: nowrap;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s, border-color 0.2s;
    }

    .skill-add-btn:hover {
      background: var(--sage-pale);
      border-color: var(--sage-light);
    }

    .skills-pills-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 0.75rem;
    }

    .skill-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background: var(--sage-pale);
      color: var(--sage-deep);
      border: 1px solid rgba(107, 143, 113, 0.2);
      padding: 0.4rem 0.7rem;
      border-radius: var(--radius-pill);
      font-size: 0.8rem;
      font-weight: 600;
      max-width: 300px;
      transition: background 0.2s;
    }

    .skill-pill-text {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      flex: 1;
      min-width: 0;
    }

    .skill-pill-remove {
      border: none;
      background: none;
      color: var(--sage-deep);
      cursor: pointer;
      font-size: 1.1rem;
      padding: 0;
      display: flex;
      align-items: center;
      line-height: 1;
      flex-shrink: 0;
      transition: color 0.15s;
    }

    .skill-pill-remove:hover {
      color: #aa4f4f;
    }

    .helper-note {
      font-size: 0.8rem;
      color: var(--warm-light);
      line-height: 1.5;
    }

    /* ── SUCCESS MODAL ── */
    .success-modal {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(42, 42, 34, 0.5);
      backdrop-filter: blur(4px);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .success-modal.show {
      display: flex;
    }

    .success-modal-content {
      background: var(--cream);
      border-radius: var(--radius-xl);
      padding: 2.2rem 2rem;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 24px 64px rgba(42, 42, 34, 0.22);
      border: 1px solid rgba(139, 128, 112, 0.12);
      animation: modalSlide 0.35s var(--ease-out);
    }

    @keyframes modalSlide {
      from { opacity: 0; transform: translateY(24px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .success-modal-icon {
      width: 64px; height: 64px;
      background: var(--sage-deep);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.2rem;
      font-size: 1.8rem;
      color: #fff;
    }

    .success-modal-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--charcoal);
      margin-bottom: 0.5rem;
    }

    .success-modal-message {
      color: var(--warm-mid);
      margin-bottom: 1.5rem;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    .success-modal-btn {
      background: var(--sage-deep);
      color: #fff;
      border: none;
      padding: 0.75rem 2rem;
      border-radius: var(--radius-pill);
      font-weight: 700;
      cursor: pointer;
      font-size: 0.9rem;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s, transform 0.15s;
      box-shadow: 0 4px 14px rgba(74,107,80,0.28);
    }

    .success-modal-btn:hover {
      background: var(--sage);
      transform: translateY(-1px);
    }

    /* ── FOOTER ── */
    .footer {
      background: #1e1e18;
      color: rgba(255,255,255,0.5);
      padding: 4rem 2rem 2rem;
      margin-top: 3rem;
    }

    .footer-inner { max-width: 1080px; margin: 0 auto; }

    .footer-top {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 2.5rem;
      padding-bottom: 2.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      margin-bottom: 1.8rem;
    }

    .footer-brand h3 {
      font-family: 'Playfair Display', serif;
      font-size: 1.1rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 0.7rem;
    }

    .footer-brand p {
      font-size: 0.81rem;
      line-height: 1.68;
      color: rgba(255,255,255,0.42);
    }

    .footer-col h4 {
      font-size: 0.72rem;
      font-weight: 700;
      color: rgba(255,255,255,0.7);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 1rem;
    }

    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }

    .footer-col ul a {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.4);
      transition: color 0.15s;
    }

    .footer-col ul a:hover { color: var(--sage-light); }

    .footer-bottom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.77rem;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    /* ── SCROLL REVEAL ── */
    .reveal {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity 0.7s var(--ease-out), transform 0.7s var(--ease-out);
    }

    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .reveal-delay-1 { transition-delay: 0.1s; }
    .reveal-delay-2 { transition-delay: 0.2s; }
    .reveal-delay-3 { transition-delay: 0.3s; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1200px) {
      .builder-grid {
        grid-template-columns: 1fr;
      }
      .preview-wrap {
        position: static;
        max-height: none;
      }
    }

    @media (max-width: 700px) {
      .builder-shell {
        padding: 5rem 1rem 3rem;
      }
      .field-grid {
        grid-template-columns: 1fr;
      }
      .rp-head {
        grid-template-columns: 66px 1fr;
      }
      .rp-photo {
        width: 66px;
        height: 78px;
      }
      .rp-name {
        font-size: 1.25rem;
      }
      .navbar { padding: 0 1.2rem; }
      .nav-links { display: none; }
      .footer-top { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 480px) {
      .footer-top { grid-template-columns: 1fr; }
      .footer-bottom { flex-direction: column; text-align: center; }
      .resume-actions { flex-direction: column; }
      .resume-actions .btn-primary,
      .resume-actions .btn-outline { width: 100%; justify-content: center; }
    }
  </style>
</head>

<body>
  <nav class="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-mark">TS</div>
      <span>Talent<em>Scout</em> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="../../index.php">Home</a></li>
      <li><a href="../job-postings/index.php">Browse Jobs</a></li>
      <li><a href="../ai-matching/index.php">AI Matching</a></li>
      <li><a href="./index.php" class="active">Resume Builder</a></li>
      <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
      <li><a href="../applicant-tracking/index.php">Applications</a></li>
      <li><a href="../messages/index.php">Messages</a></li>
    </ul>
    <div class="nav-right">
      <?php if (isset($_SESSION['employee_id'])): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
        <a href="../../logout.php" class="btn-nav-ghost">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn-nav-ghost">Login</a>
        <a href="../../signup.php" class="btn-nav-solid">Get Started →</a>
      <?php endif; ?>
    </div>
  </nav>

  <main class="builder-shell">
    <div class="builder-header">
      <h1>Resume <em>Builder</em></h1>
      <p>
        Standard template only. Fill the fields and see your resume update in
        real time.
      </p>
    </div>

    <div class="builder-grid">
      <section class="panel preview-wrap">
        <div class="panel-head">
          <span class="panel-title">Live Preview</span>
          <span class="template-chip">Standard Template</span>
        </div>

        <article class="resume-preview" id="resumePreview">
          <div class="rp-head">
            <img
              class="rp-photo"
              id="previewPhoto"
              src="../../../placeholder.png"
              alt="Profile Photo" />
            <div>
              <h2 class="rp-name" id="previewName">YOUR NAME</h2>
              <div class="rp-contact">
                <div>
                  <b>Address:</b>
                  <span id="previewAddress">Your Address</span>
                </div>
                <div>
                  <b>Phone:</b>
                  <span id="previewPhone">+63 XXX XXX XXXX</span>
                </div>
                <div>
                  <b>Email:</b> <span id="previewEmail">you@email.com</span>
                </div>
                <div>
                  <b>Website:</b>
                  <span id="previewWebsite">linkedin.com/in/you</span>
                </div>
              </div>
            </div>
          </div>

          <section class="rp-section">
            <div class="rp-title">Summary</div>
            <p class="rp-text" id="previewSummary">
              Results-oriented professional with practical experience and
              strong problem solving skills.
            </p>
          </section>

          <section class="rp-section">
            <div class="rp-title">Work Experience</div>
            <div id="previewWorkList"></div>
          </section>

          <section class="rp-section">
            <div class="rp-title">Education</div>
            <div id="previewEducationList"></div>
          </section>

          <section class="rp-section">
            <div class="rp-title">Skills</div>
            <ul class="rp-additional-list rp-skills-list" id="previewSkills">
              <li>List your key technical and soft skills.</li>
            </ul>
          </section>

          <section class="rp-section">
            <div class="rp-title">Additional Information</div>
            <ul class="rp-additional-list" id="previewAdditional">
              <li>Certifications, awards, languages, and other info.</li>
            </ul>
          </section>
        </article>
      </section>

      <section class="panel form-wrap">
        <div class="panel-head">
          <span class="panel-title">Resume Fields</span>
          <span class="template-chip">Edit Here</span>
        </div>

        <form class="resume-form" id="resumeForm" action="#" method="post">
          <div class="group">
            <h3>Personal Information</h3>
            <div class="field-grid">
              <label>
                Full Name
                <input
                  id="fullName"
                  type="text"
                  placeholder="Benjamin Shah" />
              </label>
              <label>
                Profile Photo (Optional)
                <input id="photoFile" type="file" accept="image/*" />
              </label>
              <label>
                Address
                <input
                  id="address"
                  type="text"
                  placeholder="123 Anywhere St., Any City" />
              </label>
              <label>
                Phone
                <input id="phone" type="text" placeholder="123-456-7890" />
              </label>
              <label>
                Email
                <input
                  id="email"
                  type="email"
                  placeholder="hello@example.com" />
              </label>
              <label>
                Website / Portfolio
                <input
                  id="website"
                  type="text"
                  placeholder="linkedin.com/in/yourname" />
              </label>
            </div>
          </div>

          <div class="group">
            <h3>Summary</h3>
            <label>
              Professional Summary
              <textarea
                id="summary"
                placeholder="Write a 2-4 sentence summary of your profile."></textarea>
            </label>
          </div>

          <div class="group">
            <div class="group-head">
              <h3>Work Experience</h3>
              <button type="button" class="add-btn" id="addWorkBtn">
                + Add Work Experience
              </button>
            </div>
            <div id="workFieldsContainer"></div>
          </div>

          <div class="group">
            <div class="group-head">
              <h3>Education</h3>
              <button type="button" class="add-btn" id="addEducationBtn">
                + Add Education
              </button>
            </div>
            <div id="educationFieldsContainer"></div>
          </div>

          <div class="group">
            <div class="group-head">
              <h3>Skills</h3>
            </div>
            <div class="skills-pills-wrap" id="skillFieldsContainer"></div>
            <p class="helper">
              Your skills from your profile are displayed above as removable pills.
            </p>
          </div>

          <div class="group">
            <h3>Additional Information</h3>
            <label>
              Certifications, Awards, Languages, Others
              <textarea
                id="additional"
                placeholder="Certifications: Professional Engineer (PE)&#10;Awards: Employee of the Year&#10;Languages: English, Filipino"></textarea>
            </label>
            <p class="helper">
              Tip: Use labels like "Certifications:" and "Awards:" to keep
              this section organized.
            </p>
            <p class="helper">
              Put one item per line. Example: "Technical Skills: ..." then
              press Enter for next line.
            </p>
          </div>

          <div class="resume-actions">
            <button type="submit" class="btn-primary">Save Resume</button>
            <button type="button" class="btn-primary" id="downloadPdfBtn">
              &#8595; Download PDF
            </button>
            <button type="button" class="btn-outline" id="fillSampleBtn">
              Fill Sample Data
            </button>
            <a href="../job-postings/" class="btn-outline">Browse Matching Jobs</a>
          </div>

          <p class="helper-note">
            Resume saves to database, with local backup if database is
            temporarily unavailable.
          </p>
        </form>
      </section>
    </div>

    <div class="success-modal" id="successModal">
      <div class="success-modal-content">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
          <div></div>
          <button type="button" id="successModalClose" style="background: none; border: none; font-size: 1.8rem; color: var(--warm-light); cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">×</button>
        </div>
        <div class="success-modal-icon">✓</div>
        <div class="success-modal-title">Resume Saved!</div>
        <div class="success-modal-message">Your resume has been saved successfully to the database.</div>
        <button class="success-modal-btn" onclick="closeSuccessModal()">Great!</button>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-brand">
          <h3>TalentScout AI</h3>
          <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting local talent with local opportunities.</p>
        </div>
        <div class="footer-col">
          <h4>For Job Seekers</h4>
          <ul>
            <li><a href="../job-postings/index.php">Browse Jobs</a></li>
            <li><a href="../ai-matching/index.php">AI Matching</a></li>
            <li><a href="../skill-gap-analysis/index.php">Skill Gap Analysis</a></li>
            <li><a href="../applicant-tracking/index.php">Track Applications</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>For Employers</h4>
          <ul>
            <li><a href="../../employers/index.php">Post Jobs</a></li>
            <li><a href="../../employers/modules/blind-hiring/index.php">Blind Hiring</a></li>
            <li><a href="../../employers/index.php">Find Talent</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>PESO Nasugbu</h4>
          <ul>
            <li><a href="#">Nasugbu, Batangas</a></li>
            <li><a href="#">About PESO</a></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
        <span>Built for Local Employment &amp; Community Growth</span>
      </div>
    </div>
  </footer>

  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"
  ></script>
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"
  ></script>
  <script src="../../employee-auth.js"></script>
  <script>
    const STORAGE_KEY = "tsEmployeeResumeData";
    const ACTIVE_USER_KEY = "tsResumeBuilderActiveUser";
    const API_URL = window.location.pathname;
    const ACCOUNT_KEY = "tsEmployeeAccount";
    const SESSION_EMPLOYEE_ID = <?php echo isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0; ?>;
    const SESSION_EMPLOYEE_EMAIL = <?php echo json_encode(isset($_SESSION['employee_email']) ? (string)$_SESSION['employee_email'] : ''); ?>;

    const baseFields = {
      fullName: document.getElementById("fullName"),
      photoFile: document.getElementById("photoFile"),
      address: document.getElementById("address"),
      phone: document.getElementById("phone"),
      email: document.getElementById("email"),
      website: document.getElementById("website"),
      summary: document.getElementById("summary"),
      additional: document.getElementById("additional"),
    };

    let selectedPhotoDataUrl = "";

    const preview = {
      photo: document.getElementById("previewPhoto"),
      name: document.getElementById("previewName"),
      address: document.getElementById("previewAddress"),
      phone: document.getElementById("previewPhone"),
      email: document.getElementById("previewEmail"),
      website: document.getElementById("previewWebsite"),
      summary: document.getElementById("previewSummary"),
      workList: document.getElementById("previewWorkList"),
      educationList: document.getElementById("previewEducationList"),
      skills: document.getElementById("previewSkills"),
      additional: document.getElementById("previewAdditional"),
    };

    const workFieldsContainer = document.getElementById(
      "workFieldsContainer",
    );
    const educationFieldsContainer = document.getElementById(
      "educationFieldsContainer",
    );
    const skillFieldsContainer = document.getElementById(
      "skillFieldsContainer",
    );
    const resumeForm = document.getElementById("resumeForm");

    function showSuccessModal() {
      const modal = document.getElementById("successModal");
      modal.classList.add("show");
    }

    function closeSuccessModal() {
      const modal = document.getElementById("successModal");
      modal.classList.remove("show");
    }

    function getStoredAccount() {
      try {
        const raw = localStorage.getItem(ACCOUNT_KEY);
        if (!raw) {
          return null;
        }

        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== "object") {
          return null;
        }

        return parsed;
      } catch (error) {
        return null;
      }
    }

    function getCurrentUserIdentity() {
      if (SESSION_EMPLOYEE_ID > 0) {
        return `employee:${SESSION_EMPLOYEE_ID}`;
      }

      const sessionEmail = String(SESSION_EMPLOYEE_EMAIL || "").trim().toLowerCase();
      if (sessionEmail) {
        return sessionEmail;
      }

      const account = getStoredAccount();
      const email = account && account.email ? String(account.email).trim().toLowerCase() : "";
      return email || "guest";
    }

    function getCurrentStorageKey() {
      return `${STORAGE_KEY}:${getCurrentUserIdentity()}`;
    }

    function clearDraftStorage() {
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem(getCurrentStorageKey());
    }

    function oneLine(value, fallback) {
      const clean = (value || "").trim();
      return clean || fallback;
    }

    function listFromTextarea(textareaValue, fallbackText) {
      const lines = (textareaValue || "")
        .split("\n")
        .map((line) => line.trim())
        .filter(Boolean);

      if (!lines.length) {
        return [fallbackText];
      }

      return lines;
    }

    function createWorkEntry(data = {}) {
      const index = workFieldsContainer.children.length + 1;
      const card = document.createElement("div");
      card.className = "entry-card work-entry";
      card.innerHTML = `
          <div class="entry-head">
            <span class="entry-title">Work Entry ${index}</span>
            <button type="button" class="remove-btn">Remove</button>
          </div>
          <div class="field-grid">
            <label>
              Job Title
              <input class="work-title" type="text" placeholder="Mechatronics Engineer" value="${(data.title || "").replace(/"/g, "&quot;")}" />
            </label>
            <label>
              Start Date
              <input class="work-start-date" type="month" value="${(data.startDate || "").replace(/"/g, "&quot;")}" />
            </label>
          </div>
          <div class="field-grid">
            <label>
              End Date
              <input class="work-end-date" type="month" value="${(data.endDate || "").replace(/"/g, "&quot;")}" />
            </label>
            <label>
              &nbsp;
              <div class="helper">Leave end date blank to show "Present".</div>
            </label>
          </div>
          <label>
            Company
            <input class="work-company" type="text" placeholder="Borcelle Technologies" value="${(data.company || "").replace(/"/g, "&quot;")}" />
          </label>
          <label>
            Achievements (one bullet per line)
            <textarea class="work-bullets" placeholder="Led development...&#10;Reduced costs...">${data.bullets || ""}</textarea>
          </label>
        `;

      card
        .querySelector(".remove-btn")
        .addEventListener("click", function() {
          if (workFieldsContainer.children.length === 1) {
            return;
          }
          card.remove();
          renumberEntries(workFieldsContainer, "Work Entry");
          updatePreview();
        });

      workFieldsContainer.appendChild(card);
    }

    function createEducationEntry(data = {}) {
      const index = educationFieldsContainer.children.length + 1;
      const card = document.createElement("div");
      card.className = "entry-card education-entry";
      card.innerHTML = `
          <div class="entry-head">
            <span class="entry-title">Education Entry ${index}</span>
            <button type="button" class="remove-btn">Remove</button>
          </div>
          <div class="field-grid">
            <label>
              Degree / Program
              <input class="edu-degree" type="text" placeholder="Bachelor of Mechanical Engineering" value="${(data.degree || "").replace(/"/g, "&quot;")}" />
            </label>
            <label>
              Start Date
              <input class="edu-start-date" type="month" value="${(data.startDate || "").replace(/"/g, "&quot;")}" />
            </label>
          </div>
          <div class="field-grid">
            <label>
              End Date
              <input class="edu-end-date" type="month" value="${(data.endDate || "").replace(/"/g, "&quot;")}" />
            </label>
            <label>
              &nbsp;
              <div class="helper">Leave end date blank if still studying.</div>
            </label>
          </div>
          <label>
            School
            <input class="edu-school" type="text" placeholder="Engineering University" value="${(data.school || "").replace(/"/g, "&quot;")}" />
          </label>
          <label>
            Details
            <textarea class="edu-details" placeholder="Honors, thesis, or key coursework.">${data.details || ""}</textarea>
          </label>
        `;

      card
        .querySelector(".remove-btn")
        .addEventListener("click", function() {
          if (educationFieldsContainer.children.length === 1) {
            return;
          }
          card.remove();
          renumberEntries(educationFieldsContainer, "Education Entry");
          updatePreview();
        });

      educationFieldsContainer.appendChild(card);
    }

    function createSkillEntry(value = "") {
      if (!value.trim()) {
        return;
      }

      const pill = document.createElement("div");
      pill.className = "skill-pill";
      pill.innerHTML = `
          <span class="skill-pill-text">${value.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</span>
          <button type="button" class="skill-pill-remove" title="Remove skill">×</button>
        `;

      pill
        .querySelector(".skill-pill-remove")
        .addEventListener("click", function(e) {
          e.preventDefault();
          pill.remove();
          updatePreview();
        });

      skillFieldsContainer.appendChild(pill);
    }

    function renumberEntries(container, prefix) {
      Array.from(container.children).forEach((entry, idx) => {
        const title = entry.querySelector(".entry-title");
        if (title) {
          title.textContent = `${prefix} ${idx + 1}`;
        }
      });
    }

    function readWorkEntries() {
      return Array.from(
        workFieldsContainer.querySelectorAll(".work-entry"),
      ).map((entry) => ({
        title: entry.querySelector(".work-title").value,
        startDate: entry.querySelector(".work-start-date").value,
        endDate: entry.querySelector(".work-end-date").value,
        company: entry.querySelector(".work-company").value,
        bullets: entry.querySelector(".work-bullets").value,
      }));
    }

    function readEducationEntries() {
      return Array.from(
        educationFieldsContainer.querySelectorAll(".education-entry"),
      ).map((entry) => ({
        degree: entry.querySelector(".edu-degree").value,
        startDate: entry.querySelector(".edu-start-date").value,
        endDate: entry.querySelector(".edu-end-date").value,
        school: entry.querySelector(".edu-school").value,
        details: entry.querySelector(".edu-details").value,
      }));
    }

    function readSkillEntries() {
      return Array.from(skillFieldsContainer.querySelectorAll(".skill-pill"))
        .map((pill) =>
          pill.querySelector(".skill-pill-text").textContent.trim(),
        )
        .filter(Boolean);
    }

    function formatMonthValue(monthValue) {
      if (!monthValue || !/^\d{4}-\d{2}$/.test(monthValue)) {
        return "";
      }

      const [year, month] = monthValue.split("-");
      const monthIndex = Number(month) - 1;
      const monthNames = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ];
      if (monthIndex < 0 || monthIndex > 11) {
        return "";
      }

      return `${monthNames[monthIndex]} ${year}`;
    }

    function formatDateRange(startDate, endDate, legacyDate) {
      const start = formatMonthValue(startDate);
      const end = formatMonthValue(endDate);

      if (start && end) {
        return `${start} - ${end}`;
      }
      if (start && !end) {
        return `${start} - Present`;
      }
      if (!start && end) {
        return end;
      }
      if (legacyDate) {
        return legacyDate;
      }

      return "Date Range";
    }

    function renderWorkPreview(entries) {
      preview.workList.innerHTML = "";
      const safeEntries = entries.length ?
        entries : [{
          title: "Role / Position",
          startDate: "",
          endDate: "",
          company: "Company Name",
          bullets: "Add accomplishments as bullet points.",
        }, ];

      safeEntries.forEach((entry) => {
        const wrap = document.createElement("div");
        wrap.className = "rp-job";

        const bullets = listFromTextarea(
          entry.bullets,
          "Add accomplishments as bullet points.",
        );
        const bulletMarkup = bullets.map((b) => `<li>${b}</li>`).join("");

        wrap.innerHTML = `
            <div class="rp-job-head">
              <span class="rp-job-title">${oneLine(entry.title, "Role / Position")}</span>
              <span class="rp-date">${formatDateRange(entry.startDate, entry.endDate, entry.date)}</span>
            </div>
            <div class="rp-company">${oneLine(entry.company, "Company Name")}</div>
            <ul class="rp-list">${bulletMarkup}</ul>
          `;
        preview.workList.appendChild(wrap);
      });
    }

    function renderEducationPreview(entries) {
      preview.educationList.innerHTML = "";
      const safeEntries = entries.length ?
        entries : [{
          degree: "Degree / Program",
          startDate: "",
          endDate: "",
          school: "School Name",
          details: "Optional education highlights.",
        }, ];

      safeEntries.forEach((entry) => {
        const wrap = document.createElement("div");
        wrap.className = "rp-job";
        wrap.innerHTML = `
            <div class="rp-job-head">
              <span class="rp-job-title">${oneLine(entry.degree, "Degree / Program")}</span>
              <span class="rp-date">${formatDateRange(entry.startDate, entry.endDate, entry.date)}</span>
            </div>
            <div class="rp-company">${oneLine(entry.school, "School Name")}</div>
            <p class="rp-text">${oneLine(entry.details, "Optional education highlights.")}</p>
          `;
        preview.educationList.appendChild(wrap);
      });
    }

    function renderAdditionalPreview(value) {
      const lines = (value || "")
        .split("\n")
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => line.replace(/^[-*•]\s*/, ""));

      preview.additional.innerHTML = "";

      const safeLines = lines.length ?
        lines : ["Certifications, awards, languages, and other info."];

      safeLines.forEach((line) => {
        const li = document.createElement("li");
        li.textContent = line;
        preview.additional.appendChild(li);
      });
    }

    function renderSkillsPreview(items) {
      preview.skills.innerHTML = "";

      const safeLines = items.length ?
        items : ["List your key technical and soft skills."];

      safeLines.forEach((line) => {
        const li = document.createElement("li");
        li.textContent = line;
        preview.skills.appendChild(li);
      });
    }

    function updatePreview() {
      preview.name.textContent = oneLine(
        baseFields.fullName.value,
        "YOUR NAME",
      );
      preview.address.textContent = oneLine(
        baseFields.address.value,
        "Your Address",
      );
      preview.phone.textContent = oneLine(
        baseFields.phone.value,
        "+63 XXX XXX XXXX",
      );
      preview.email.textContent = oneLine(
        baseFields.email.value,
        "you@email.com",
      );
      preview.website.textContent = oneLine(
        baseFields.website.value,
        "linkedin.com/in/you",
      );
      preview.summary.textContent = oneLine(
        baseFields.summary.value,
        "Results-oriented professional with practical experience and strong problem solving skills.",
      );

      renderWorkPreview(readWorkEntries());
      renderEducationPreview(readEducationEntries());

      renderSkillsPreview(readSkillEntries());
      renderAdditionalPreview(baseFields.additional.value);

      preview.photo.src =
        selectedPhotoDataUrl ||
        "../../../placeholder.png";
    }

    function collectData() {
      return {
        fullName: baseFields.fullName.value || "",
        photoDataUrl: selectedPhotoDataUrl || "",
        address: baseFields.address.value || "",
        phone: baseFields.phone.value || "",
        email: baseFields.email.value || "",
        website: baseFields.website.value || "",
        summary: baseFields.summary.value || "",
        skills: readSkillEntries(),
        additional: baseFields.additional.value || "",
        workExperience: readWorkEntries(),
        education: readEducationEntries(),
      };
    }

    function getEmployeeContext() {
      let employeeId = SESSION_EMPLOYEE_ID > 0 ? String(SESSION_EMPLOYEE_ID) : "";
      let employeeEmail = String(SESSION_EMPLOYEE_EMAIL || "").trim();

      const account = getStoredAccount();
      if (!employeeId && account && account.employee_id) {
        employeeId = String(account.employee_id).trim();
      }
      if (!employeeEmail && account && account.email) {
        employeeEmail = String(account.email).trim();
      }
      if (!employeeEmail) {
        employeeEmail = (baseFields.email.value || "").trim();
      }

      return {
        employeeId,
        email: employeeEmail,
      };
    }

    async function saveResumeToDatabase(data) {
      const context = getEmployeeContext();
      const payload = {
        api_action: "save",
        employee_id: context.employeeId,
        email: context.email,
        data,
      };

      const response = await fetch(API_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || "Database save failed.");
      }

      return result;
    }

    async function loadResumeFromDatabase() {
      const context = getEmployeeContext();
      const params = new URLSearchParams({
        api_action: "load"
      });
      if (context.employeeId) {
        params.set("employee_id", context.employeeId);
      }
      if (context.email) {
        params.set("email", context.email);
      }

      const response = await fetch(`${API_URL}?${params.toString()}`, {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      });

      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || "Database load failed.");
      }

      return result;
    }

    function applyData(data) {
      baseFields.fullName.value = data.fullName || "";
      selectedPhotoDataUrl = data.photoDataUrl || data.photoUrl || "";
      baseFields.photoFile.value = "";
      baseFields.address.value = data.address || "";
      baseFields.phone.value = data.phone || "";
      baseFields.email.value = data.email || "";
      baseFields.website.value = data.website || "";
      baseFields.summary.value = data.summary || "";
      baseFields.additional.value = data.additional || "";

      workFieldsContainer.innerHTML = "";
      educationFieldsContainer.innerHTML = "";
      skillFieldsContainer.innerHTML = "";

      const workItems = Array.isArray(data.workExperience) ?
        data.workExperience : [];
      const educationItems = Array.isArray(data.education) ?
        data.education : [];
      const skillItems = Array.isArray(data.skills) ? data.skills : (Array.isArray(data.existing_skills) ? data.existing_skills : []);

      if (workItems.length) {
        workItems.forEach((item) => createWorkEntry(item));
      } else {
        createWorkEntry();
      }

      if (educationItems.length) {
        educationItems.forEach((item) => createEducationEntry(item));
      } else {
        createEducationEntry();
      }

      if (skillItems.length) {
        skillItems.forEach((item) => createSkillEntry(item));
      }

      renumberEntries(workFieldsContainer, "Work Entry");
      renumberEntries(educationFieldsContainer, "Education Entry");
      updatePreview();
    }

    function normalizeLegacyData(parsed) {
      function parseMonthLabel(label) {
        const clean = (label || "").trim();
        const match = clean.match(
          /^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})$/i,
        );
        if (!match) {
          return "";
        }

        const monthMap = {
          jan: "01",
          feb: "02",
          mar: "03",
          apr: "04",
          may: "05",
          jun: "06",
          jul: "07",
          aug: "08",
          sep: "09",
          oct: "10",
          nov: "11",
          dec: "12",
        };

        const month = monthMap[match[1].toLowerCase()] || "";
        const year = match[2];
        return month ? `${year}-${month}` : "";
      }

      function parseLegacyRange(rangeText) {
        const text = (rangeText || "").trim();
        if (!text) {
          return {
            startDate: "",
            endDate: ""
          };
        }

        const parts = text.split("-").map((part) => part.trim());
        if (parts.length >= 2) {
          const startDate = parseMonthLabel(parts[0]);
          const endPart = parts.slice(1).join("-").trim();
          const endDate = /present/i.test(endPart) ?
            "" :
            parseMonthLabel(endPart);
          return {
            startDate,
            endDate
          };
        }

        return {
          startDate: parseMonthLabel(text),
          endDate: ""
        };
      }

      const normalized = {
        fullName: parsed.fullName || "",
        photoDataUrl: parsed.photoDataUrl || parsed.photoUrl || "",
        address: parsed.address || "",
        phone: parsed.phone || "",
        email: parsed.email || "",
        website: parsed.website || "",
        summary: parsed.summary || "",
        skills: Array.isArray(parsed.skills) ? parsed.skills : (Array.isArray(parsed.existing_skills) ? parsed.existing_skills : []),
        additional: parsed.additional || "",
        workExperience: [],
        education: [],
      };

      if (!normalized.skills.length && typeof parsed.skills === "string") {
        normalized.skills = parsed.skills
          .split("\n")
          .map((line) => line.trim())
          .filter(Boolean)
          .map((line) => line.replace(/^[-*•]\s*/, ""));
      }

      if (!normalized.skills.length && normalized.additional) {
        const lines = normalized.additional
          .split("\n")
          .map((line) => line.trim())
          .filter(Boolean);

        const skillLines = lines.filter(
          (line) =>
          /^skills?:/i.test(line) || /^technical skills?:/i.test(line),
        );
        if (skillLines.length) {
          normalized.skills = skillLines
            .map((line) =>
              line
              .replace(/^technical skills?:/i, "")
              .replace(/^skills?:/i, "")
              .trim(),
            )
            .flatMap((line) => line.split(",").map((part) => part.trim()))
            .filter(Boolean)
            .map((line) => line.replace(/^[-*•]\s*/, ""));

          normalized.additional = lines
            .filter(
              (line) =>
              !/^skills?:/i.test(line) &&
              !/^technical skills?:/i.test(line),
            )
            .join("\n");
        }
      }

      if (Array.isArray(parsed.workExperience)) {
        normalized.workExperience = parsed.workExperience.map((item) => {
          if (item.startDate || item.endDate) {
            return item;
          }

          const converted = parseLegacyRange(item.date || "");
          return {
            ...item,
            startDate: converted.startDate,
            endDate: converted.endDate,
          };
        });
      } else {
        if (
          parsed.work1Title ||
          parsed.work1Company ||
          parsed.work1Bullets ||
          parsed.work1Date
        ) {
          const converted = parseLegacyRange(parsed.work1Date || "");
          normalized.workExperience.push({
            title: parsed.work1Title || "",
            startDate: converted.startDate,
            endDate: converted.endDate,
            company: parsed.work1Company || "",
            bullets: parsed.work1Bullets || "",
          });
        }
        if (
          parsed.work2Title ||
          parsed.work2Company ||
          parsed.work2Bullets ||
          parsed.work2Date
        ) {
          const converted = parseLegacyRange(parsed.work2Date || "");
          normalized.workExperience.push({
            title: parsed.work2Title || "",
            startDate: converted.startDate,
            endDate: converted.endDate,
            company: parsed.work2Company || "",
            bullets: parsed.work2Bullets || "",
          });
        }
      }

      if (Array.isArray(parsed.education)) {
        normalized.education = parsed.education.map((item) => {
          if (item.startDate || item.endDate) {
            return item;
          }

          const converted = parseLegacyRange(item.date || "");
          return {
            ...item,
            startDate: converted.startDate,
            endDate: converted.endDate,
          };
        });
      } else if (
        parsed.eduDegree ||
        parsed.eduSchool ||
        parsed.eduDate ||
        parsed.eduDetails
      ) {
        const converted = parseLegacyRange(parsed.eduDate || "");
        normalized.education.push({
          degree: parsed.eduDegree || "",
          startDate: converted.startDate,
          endDate: converted.endDate,
          school: parsed.eduSchool || "",
          details: parsed.eduDetails || "",
        });
      }

      return normalized;
    }

    async function loadSavedResume() {
      const currentUser = getCurrentUserIdentity();
      const lastActiveUser = localStorage.getItem(ACTIVE_USER_KEY) || "";
      const switchedUser = lastActiveUser !== "" && lastActiveUser !== currentUser;
      localStorage.setItem(ACTIVE_USER_KEY, currentUser);

      if (switchedUser) {
        clearDraftStorage();
      }

      try {
        const result = await loadResumeFromDatabase();
        if (result && result.success) {
          const dataToUse = result.data || {};
          if (!dataToUse.skills && result.existing_skills) {
            dataToUse.existing_skills = result.existing_skills;
          }
          const normalized = normalizeLegacyData(dataToUse);
          applyData(normalized);
          localStorage.setItem(getCurrentStorageKey(), JSON.stringify(normalized));
          return;
        }
      } catch (error) {
        // Fall back to local storage when DB is unavailable.
      }

      const raw =
        localStorage.getItem(getCurrentStorageKey()) ||
        localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        applyData({});
        return;
      }

      try {
        const parsed = JSON.parse(raw);
        applyData(normalizeLegacyData(parsed));
      } catch (error) {
        applyData({});
      }
    }

    document
      .getElementById("addWorkBtn")
      .addEventListener("click", function() {
        createWorkEntry();
        renumberEntries(workFieldsContainer, "Work Entry");
        updatePreview();
      });

    document
      .getElementById("addEducationBtn")
      .addEventListener("click", function() {
        createEducationEntry();
        renumberEntries(educationFieldsContainer, "Education Entry");
        updatePreview();
      });



    baseFields.photoFile.addEventListener("change", function() {
      const file =
        baseFields.photoFile.files && baseFields.photoFile.files[0];

      if (!file) {
        selectedPhotoDataUrl = "";
        updatePreview();
        return;
      }

      const reader = new FileReader();
      reader.onload = function(event) {
        selectedPhotoDataUrl = (event.target && event.target.result) || "";
        updatePreview();
      };
      reader.readAsDataURL(file);
    });

    resumeForm.addEventListener("input", updatePreview);

    // Close success modal when X button is clicked
    document.getElementById("successModalClose")?.addEventListener("click", closeSuccessModal);

    // Close success modal when clicking outside of it
    document.getElementById("successModal")?.addEventListener("click", function(e) {
      if (e.target === this) {
        closeSuccessModal();
      }
    });

    resumeForm.addEventListener("submit", async function(event) {
      event.preventDefault();

      const data = collectData();
      localStorage.setItem(getCurrentStorageKey(), JSON.stringify(data));

      try {
        await saveResumeToDatabase(data);
        showSuccessModal();
      } catch (error) {
        alert(
          "Saved locally, but database save failed. Please check XAMPP MySQL and try again.",
        );
      }
    });

    document
      .getElementById("fillSampleBtn")
      .addEventListener("click", function() {
        applyData({
          fullName: "Benjamin Shah",
          address: "123 Anywhere St., Any City",
          phone: "123-456-7890",
          email: "hello@reallygreatsite.com",
          website: "www.reallygreatsite.com",
          summary: "Results-oriented Mechanical and Mechatronics Engineer seeking a challenging role to apply expertise in designing and implementing innovative solutions for complex engineering challenges.",
          skills: [
            "Mechatronics System Integration",
            "Automotive Engineering Technology",
            "Robotics and Automation",
            "CAD for Mechatronics",
            "Project Management",
          ],
          workExperience: [{
              title: "Mechatronics Engineer",
              startDate: "2023-01",
              endDate: "",
              company: "Borcelle Technologies",
              bullets: "Led development of an advanced automation system, achieving a 15% increase in operational efficiency.\nStreamlined manufacturing processes, reducing production costs by 10%.\nImplemented preventive maintenance strategies, resulting in a 20% decrease in equipment downtime.",
            },
            {
              title: "System Engineer",
              startDate: "2021-02",
              endDate: "2022-12",
              company: "Arrowai Industries",
              bullets: "Designed and optimized a robotic control system, realizing a 12% performance improvement.\nCoordinated testing and validation, ensuring compliance with industry standards.\nProvided technical expertise, contributing to a 15% reduction in system failures.",
            },
          ],
          education: [{
            degree: "Bachelor of Mechanical Engineering with Honors",
            startDate: "2016-08",
            endDate: "2019-10",
            school: "University of Engineering Excellence",
            details: "Major in Automotive Technology. Thesis on technological advancements in mechatronics.",
          }, ],
          additional: "Certifications: Professional Engineer (PE), PMP\nAwards: Active participant in engineering community projects.",
        });
      });

    preview.photo.addEventListener("error", function() {
      preview.photo.src = "../../../placeholder.png";
    });

    document
      .getElementById("downloadPdfBtn")
      .addEventListener("click", function() {
        const element = document.getElementById("resumePreview");
        const rawName = (baseFields.fullName.value || "resume").trim();
        const safeName =
          rawName.replace(/[^a-z0-9_\-\s]/gi, "").replace(/\s+/g, "_") ||
          "resume";

        const btn = document.getElementById("downloadPdfBtn");
        btn.disabled = true;
        btn.textContent = "Generating…";

        console.log("Starting PDF generation for element:", element);
        console.log("html2canvas:", typeof html2canvas);
        console.log("jsPDF:", typeof jsPDF);
        console.log("Element visible:", element.offsetParent !== null);
        console.log("Element dimensions:", element.offsetWidth, "x", element.offsetHeight);

        html2canvas(element, {
          scale: 2,
          useCORS: true,
          allowTaint: true,
          backgroundColor: "#ffffff",
          logging: false
        }).then(function(canvas) {
          console.log("Canvas generated:", canvas.width, "x", canvas.height);
          
          const imgData = canvas.toDataURL("image/jpeg", 0.98);
          console.log("Image data generated, length:", imgData.length);
          
          const pdf = new jsPDF({
            unit: "in",
            format: "letter",
            orientation: "portrait"
          });
          
          const pdfWidth = pdf.internal.pageSize.getWidth();
          const pdfHeight = pdf.internal.pageSize.getHeight();
          const margin = 0.45;
          
          const availableWidth = pdfWidth - (margin * 2);
          const availableHeight = pdfHeight - (margin * 2);
          
          const canvasWidth = canvas.width;
          const canvasHeight = canvas.height;
          const canvasRatio = canvasWidth / canvasHeight;
          
          let imgWidth = availableWidth;
          let imgHeight = availableWidth / canvasRatio;
          
          if (imgHeight > availableHeight) {
            imgHeight = availableHeight;
            imgWidth = availableHeight * canvasRatio;
          }
          
          const x = (pdfWidth - imgWidth) / 2;
          const y = margin;
          
          pdf.addImage(imgData, "JPEG", x, y, imgWidth, imgHeight);
          pdf.save(safeName + "_resume.pdf");
          
          console.log("PDF saved successfully");
          btn.disabled = false;
          btn.innerHTML = "&#8595; Download PDF";
        }).catch(function(err) {
          console.error("PDF generation error:", err);
          btn.disabled = false;
          btn.innerHTML = "&#8595; Download PDF";
          alert("Could not generate PDF: " + err.message);
        });
      });

    function shouldResetForNavigation(anchor) {
      if (!anchor || !anchor.href) {
        return false;
      }

      if (anchor.target === "_blank" || anchor.hasAttribute("download")) {
        return false;
      }

      const nextUrl = new URL(anchor.href, window.location.origin);
      const isSamePage =
        nextUrl.pathname === window.location.pathname &&
        nextUrl.search === window.location.search &&
        nextUrl.hash !== "";

      return !isSamePage;
    }

    // Reset only when user leaves this interface, not when browser tab focus changes.
    document.addEventListener("click", function(event) {
      const anchor = event.target.closest("a[href]");
      if (!anchor || !shouldResetForNavigation(anchor)) {
        return;
      }

      clearDraftStorage();
    });

    loadSavedResume();
  </script>

  <script>
    // Scroll reveal for builder page elements
    const builderReveals = document.querySelectorAll('.reveal');
    const builderIO = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          builderIO.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    builderReveals.forEach(el => builderIO.observe(el));
  </script>
</body>

</html>
