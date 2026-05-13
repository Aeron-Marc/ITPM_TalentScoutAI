<?php
session_start();
require_once __DIR__ . '/auth.php';
peso_require_admin('../login.php');
require_once __DIR__ . '/../database/db.php';

// ── Helper: safe query ────────────────────────────────────────────────────────
function db_val($pdo, $sql, $params = [], $default = 0) {
    try {
        $s = $pdo->prepare($sql);
        $s->execute($params);
        return $s->fetchColumn() ?? $default;
    } catch (Exception $e) { return $default; }
}
function db_all($pdo, $sql, $params = []) {
    try {
        $s = $pdo->prepare($sql);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

// ── KPI Cards ────────────────────────────────────────────────────────────────
$total_seekers      = db_val($pdo, "SELECT COUNT(*) FROM employee");
$active_seekers     = db_val($pdo, "SELECT COUNT(*) FROM employee WHERE is_active=1");
$seekers_this_month = db_val($pdo, "SELECT COUNT(*) FROM employee WHERE is_active=1");
$seekers_last_month = db_val($pdo, "SELECT COUNT(*) FROM employee WHERE is_active=1");

$total_employers      = db_val($pdo, "SELECT COUNT(*) FROM employer");
$employers_this_month = db_val($pdo, "SELECT COUNT(*) FROM employer");
$employers_last_month = db_val($pdo, "SELECT COUNT(*) FROM employer");

$active_jobs      = db_val($pdo, "SELECT COUNT(*) FROM job_post");
$jobs_this_month  = db_val($pdo, "SELECT COUNT(*) FROM job_post");
$jobs_last_month  = db_val($pdo, "SELECT COUNT(*) FROM job_post");

$hires_this_month = db_val($pdo, "SELECT COUNT(*) FROM application WHERE hire_status='accepted'");
$hires_last_month = db_val($pdo, "SELECT COUNT(*) FROM application WHERE hire_status='accepted'");

$total_applications = db_val($pdo, "SELECT COUNT(*) FROM application");
$pending_approvals  = db_val($pdo, "SELECT COUNT(*) FROM application WHERE status='Pending'");
$unverified_users   = db_val($pdo, "SELECT COUNT(*) FROM employee WHERE is_active=0");

// Avg days to hire
$avg_days = db_val($pdo, "SELECT 0", [], 0);

// ── Application status breakdown ──────────────────────────────────────────────
$app_hired     = db_val($pdo, "SELECT COUNT(*) FROM application WHERE hire_status='accepted'");
$app_interview = db_val($pdo, "SELECT COUNT(*) FROM application WHERE status='Interview Scheduled'");
$app_pending   = db_val($pdo, "SELECT COUNT(*) FROM application WHERE status='Pending'");
$app_rejected  = db_val($pdo, "SELECT COUNT(*) FROM application WHERE status='Rejected'");

// ── Weekly applications chart (last 8 weeks) ──────────────────────────────────
$weekly_apps = db_all($pdo, "
    SELECT
        WEEK(application_date, 1) AS wk,
        YEARWEEK(application_date, 1) AS ywk,
        DATE_FORMAT(MIN(application_date), '%b %d') AS week_label,
        COUNT(*) AS total,
        SUM(CASE WHEN hire_status='accepted' THEN 1 ELSE 0 END) AS hired,
        SUM(CASE WHEN status='Interview Scheduled' THEN 1 ELSE 0 END) AS interviews
    FROM application
    WHERE application_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
    GROUP BY ywk
    ORDER BY ywk ASC
    LIMIT 8
");

// Fill missing weeks with zeros
$week_labels    = [];
$week_total     = [];
$week_hired     = [];
$week_interviews = [];
foreach ($weekly_apps as $row) {
    $week_labels[]     = $row['week_label'];
    $week_total[]      = (int)$row['total'];
    $week_hired[]      = (int)$row['hired'];
    $week_interviews[] = (int)$row['interviews'];
}
if (empty($week_labels)) {
    $week_labels     = ['W1','W2','W3','W4','W5','W6','W7','W8'];
    $week_total      = [0,0,0,0,0,0,0,0];
    $week_hired      = [0,0,0,0,0,0,0,0];
    $week_interviews = [0,0,0,0,0,0,0,0];
}

// ── Monthly user registrations (last 6 months) ────────────────────────────────
$monthly_seekers = db_all($pdo, "
    SELECT DATE_FORMAT(NOW(),'%b %Y') AS mo, COUNT(*) AS cnt
    FROM employee
");
$monthly_employers = db_all($pdo, "
    SELECT DATE_FORMAT(NOW(),'%b %Y') AS mo, COUNT(*) AS cnt
    FROM employer
");

// Merge into same label set
$mo_labels = array_unique(array_merge(
    array_column($monthly_seekers, 'mo'),
    array_column($monthly_employers, 'mo')
));
sort($mo_labels);
$s_map = array_column($monthly_seekers,  'cnt', 'mo');
$e_map = array_column($monthly_employers,'cnt', 'mo');
$mo_seekers   = array_map(fn($m) => (int)($s_map[$m] ?? 0), $mo_labels);
$mo_employers = array_map(fn($m) => (int)($e_map[$m] ?? 0), $mo_labels);

// ── Top job categories ────────────────────────────────────────────────────────
$top_categories = db_all($pdo, "
    SELECT
        IFNULL(NULLIF(TRIM(jp.job_category),''), 'Other') AS category,
        COUNT(a.application_id) AS app_count
    FROM job_post jp
    LEFT JOIN application a ON a.job_post_id = jp.job_post_id
    GROUP BY category
    ORDER BY app_count DESC
    LIMIT 6
");
if (empty($top_categories)) {
    $top_categories = [
        ['category'=>'No Data','app_count'=>0]
    ];
}

// ── Avg days to hire by job type ──────────────────────────────────────────────
$speed_by_type = db_all($pdo, "
    SELECT
        IFNULL(NULLIF(TRIM(jp.job_category),''), 'Other') AS category,
        ROUND(AVG(DATEDIFF(a.hire_response_date, a.application_date))) AS avg_days
    FROM application a
    JOIN job_post jp ON a.job_post_id = jp.job_post_id
    WHERE a.hire_status = 'accepted'
    GROUP BY category
    ORDER BY avg_days ASC
    LIMIT 6
");
if (empty($speed_by_type)) {
    $speed_by_type = [['category'=>'No Data','avg_days'=>0]];
}

// ── Recent applications ───────────────────────────────────────────────────────
$recent_apps = db_all($pdo, "
    SELECT
        CONCAT(e.first_name, ' ', LEFT(e.last_name,1), '.') AS applicant,
        jp.job_title AS job_title,
        emp.employer_name AS employer,
        a.status,
        DATE_FORMAT(a.application_date, '%b %d') AS date_applied
    FROM application a
    JOIN employee e  ON a.employee_id = e.employee_id
    JOIN job_post jp ON a.job_post_id = jp.job_post_id
    JOIN employer emp ON jp.employer_id = emp.employer_id
    ORDER BY a.application_date DESC
    LIMIT 8
");

// ── Recent employer registrations ─────────────────────────────────────────────
$recent_employers = db_all($pdo, "
    SELECT
        employer_name AS company_name,
        IFNULL(industry,'—') AS industry,
        'active' AS status,
        DATE_FORMAT(NOW(),'%b %d') AS registered
    FROM employer
    LIMIT 6
");

// ── Recent activity feed ──────────────────────────────────────────────────────
$activity_feed = db_all($pdo, "
    SELECT 'application' AS type,
        CONCAT(e.first_name,' applied for ', jp.job_title) AS msg,
        a.application_date AS ts
    FROM application a
    JOIN employee e ON a.employee_id=e.employee_id
    JOIN job_post jp ON a.job_post_id=jp.job_post_id
    UNION ALL
    SELECT 'employer', CONCAT(employer_name,' registered as employer'), NOW() FROM employer
    UNION ALL
    SELECT 'job', CONCAT('New job posted: ', job_title), NOW() FROM job_post
    UNION ALL
    SELECT 'hire', CONCAT(e.first_name,' was hired for ', jp.job_title), a.hire_response_date
    FROM application a
    JOIN employee e ON a.employee_id=e.employee_id
    JOIN job_post jp ON a.job_post_id=jp.job_post_id
    WHERE a.hire_status='accepted'
    ORDER BY ts DESC
    LIMIT 8
");

// ── Top skills in demand ──────────────────────────────────────────────────────
$skills_raw = db_all($pdo, "SELECT required_skills AS skills_required FROM job_post WHERE required_skills IS NOT NULL AND required_skills != '' LIMIT 200");
$skill_count = [];
foreach ($skills_raw as $row) {
    foreach (explode(',', $row['skills_required']) as $sk) {
        $sk = trim($sk);
        if ($sk) $skill_count[$sk] = ($skill_count[$sk] ?? 0) + 1;
    }
}
arsort($skill_count);
$top_skills = array_slice($skill_count, 0, 8, true);

// ── JSON encode for JS ────────────────────────────────────────────────────────
$j_week_labels     = json_encode($week_labels);
$j_week_total      = json_encode($week_total);
$j_week_hired      = json_encode($week_hired);
$j_week_interviews = json_encode($week_interviews);
$j_mo_labels       = json_encode(array_values($mo_labels));
$j_mo_seekers      = json_encode($mo_seekers);
$j_mo_employers    = json_encode($mo_employers);
$j_cat_labels      = json_encode(array_column($top_categories,'category'));
$j_cat_counts      = json_encode(array_map(fn($r)=>(int)$r['app_count'], $top_categories));
$j_speed_labels    = json_encode(array_column($speed_by_type,'category'));
$j_speed_days      = json_encode(array_map(fn($r)=>(int)$r['avg_days'], $speed_by_type));

// % change helpers
function pct_change($now, $prev) {
    if ($prev == 0) return $now > 0 ? '+100%' : '0%';
    $pct = round((($now - $prev) / $prev) * 100);
    return ($pct >= 0 ? '+' : '') . $pct . '%';
}
$seeker_change   = pct_change($seekers_this_month,   $seekers_last_month);
$employer_change = pct_change($employers_this_month, $employers_last_month);
$job_change      = pct_change($jobs_this_month,      $jobs_last_month);
$hire_change     = pct_change($hires_this_month,     $hires_last_month);

$seeker_up   = $seekers_this_month   >= $seekers_last_month;
$employer_up = $employers_this_month >= $employers_last_month;
$job_up      = $jobs_this_month      >= $jobs_last_month;
$hire_up     = $hires_this_month     >= $hires_last_month;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – TalentScout AI</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:       #3d6b50;
      --green-dark:  #2d5040;
      --green-deeper:#1e3a2e;
      --green-light: #5a8a68;
      --mint:        #e8f5ee;
      --mint-mid:    #c8e6d4;
      --mint-deep:   #a8d4b8;
      --gold:        #c8a46a;
      --gold-light:  #fef3d0;
      --gold-text:   #8a6030;
      --blue:        #3a7cbf;
      --blue-light:  #dce8f8;
      --blue-text:   #185fa5;
      --teal:        #1a8a6e;
      --teal-light:  #d4f0e6;
      --red:         #c0392b;
      --red-light:   #fde8e8;
      --bg:          #f0faf4;
      --bg-card:     #ffffff;
      --border:      #d4eddf;
      --text-main:   #1a2e22;
      --text-mid:    #3d5445;
      --text-soft:   #5a8a68;
      --text-muted:  #7a9a82;
      --shadow-sm:   0 2px 8px rgba(45,80,64,0.07);
      --shadow-md:   0 6px 24px rgba(45,80,64,0.10);
      --shadow-lg:   0 12px 40px rgba(45,80,64,0.14);
      --radius-sm:   8px;
      --radius-md:   12px;
      --radius-lg:   16px;
      --radius-xl:   20px;
    }

    html { scroll-behavior: smooth; }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
    }
    a { text-decoration: none; color: inherit; }

    /* ── SIDEBAR ── */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: 240px; background: var(--green-deeper);
      display: flex; flex-direction: column;
      z-index: 200; transition: transform 0.35s cubic-bezier(.22,1,.36,1);
    }

    .sidebar-logo {
      padding: 22px 20px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      display: flex; align-items: center; gap: 10px;
    }

    .logo-mark {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--green-light), var(--green));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: #fff; letter-spacing: 0.05em;
      flex-shrink: 0;
    }

    .logo-text {
      font-size: 14px; font-weight: 700; color: #fff; line-height: 1.2;
    }
    .logo-text span { color: var(--mint-deep); }
    .logo-sub { font-size: 9px; color: rgba(255,255,255,0.4); letter-spacing: 0.06em; }

    .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }

    .nav-section-label {
      font-size: 9px; font-weight: 700; letter-spacing: 0.15em;
      text-transform: uppercase; color: rgba(255,255,255,0.3);
      padding: 14px 10px 6px;
    }

    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: var(--radius-md);
      font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.65);
      cursor: pointer; transition: all 0.2s; margin-bottom: 2px;
      text-decoration: none;
    }

    .nav-item i { width: 18px; text-align: center; font-size: 14px; }

    .nav-item:hover { background: rgba(255,255,255,0.08); color: #fff; }
    .nav-item.active { background: rgba(168,212,184,0.18); color: #fff; font-weight: 600; }
    .nav-item.active i { color: var(--mint-deep); }

    .nav-badge {
      margin-left: auto; background: #c0392b;
      color: #fff; font-size: 9px; font-weight: 700;
      padding: 2px 7px; border-radius: 20px; min-width: 18px; text-align: center;
    }

    .nav-badge.gold { background: var(--gold); color: var(--green-deeper); }

    .sidebar-footer {
      padding: 14px 12px;
      border-top: 1px solid rgba(255,255,255,0.08);
    }

    .sidebar-user {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: var(--radius-md);
      background: rgba(255,255,255,0.06);
    }

    .sidebar-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--green-light), var(--teal));
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
    }

    .sidebar-user-info { flex: 1; overflow: hidden; }
    .sidebar-user-name { font-size: 12px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sidebar-user-role { font-size: 10px; color: rgba(255,255,255,0.4); }

    /* ── TOPBAR ── */
    .topbar {
      position: fixed; top: 0; left: 240px; right: 0; height: 62px;
      background: #fff; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 24px; z-index: 100;
      box-shadow: var(--shadow-sm);
    }

    .topbar-left {
      display: flex; align-items: center; gap: 14px;
    }

    .topbar-title { font-size: 16px; font-weight: 700; color: var(--text-main); }
    .topbar-sub   { font-size: 11px; color: var(--text-muted); }

    .topbar-right { display: flex; align-items: center; gap: 10px; }

    .live-indicator {
      display: flex; align-items: center; gap: 6px;
      font-size: 11px; font-weight: 600; color: var(--teal);
      background: var(--teal-light); padding: 5px 12px; border-radius: 20px;
      border: 1px solid rgba(26,138,110,0.2);
    }

    .live-dot {
      width: 7px; height: 7px; background: var(--teal); border-radius: 50%;
      animation: livePulse 2s ease-in-out infinite;
    }

    @keyframes livePulse {
      0%,100% { box-shadow: 0 0 0 0 rgba(26,138,110,0.5); }
      50%      { box-shadow: 0 0 0 6px rgba(26,138,110,0); }
    }

    .topbar-icon-btn {
      width: 36px; height: 36px; border-radius: var(--radius-sm);
      border: 1px solid var(--border); background: #fff;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.2s; position: relative;
      color: var(--text-muted); font-size: 15px;
    }
    .topbar-icon-btn:hover { background: var(--mint); color: var(--green); border-color: var(--mint-deep); }

    .notif-badge {
      position: absolute; top: -4px; right: -4px;
      width: 16px; height: 16px; border-radius: 50%;
      background: #c0392b; color: #fff; font-size: 9px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }

    .topbar-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: #fff; cursor: pointer;
    }

    /* ── MAIN CONTENT ── */
    .content {
      margin-left: 240px;
      padding-top: 62px;
      min-height: 100vh;
    }

    .content-inner {
      padding: 24px;
      max-width: 1400px;
    }

    /* ── PAGE HEADER ── */
    .page-header {
      display: flex; align-items: flex-start;
      justify-content: space-between; flex-wrap: wrap; gap: 12px;
      margin-bottom: 22px;
    }

    .page-header h1 { font-size: 20px; font-weight: 700; color: var(--text-main); }
    .page-header p  { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

    .header-actions { display: flex; gap: 8px; align-items: center; }

    .btn {
      padding: 8px 16px; border-radius: var(--radius-md);
      font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 600;
      cursor: pointer; transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 6px;
    }

    .btn-primary {
      background: var(--green); color: #fff;
      box-shadow: 0 4px 14px rgba(61,107,80,0.28);
    }
    .btn-primary:hover { background: var(--green-dark); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(61,107,80,0.36); }

    .btn-outline {
      background: #fff; color: var(--text-mid);
      border: 1px solid var(--border);
    }
    .btn-outline:hover { background: var(--mint); border-color: var(--mint-deep); color: var(--green); }

    /* ── KPI GRID ── */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px; margin-bottom: 18px;
    }

    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 20px;
      display: flex; flex-direction: column; gap: 6px;
      transition: transform 0.25s, box-shadow 0.25s;
      cursor: pointer; position: relative; overflow: hidden;
    }

    .kpi-card::after {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 4px;
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }

    .kpi-card.green::after  { background: var(--green); }
    .kpi-card.teal::after   { background: var(--teal); }
    .kpi-card.gold::after   { background: var(--gold); }
    .kpi-card.blue::after   { background: var(--blue); }

    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

    .kpi-label {
      font-size: 11px; font-weight: 600; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 0.08em;
      display: flex; align-items: center; gap: 6px;
    }

    .kpi-icon {
      width: 28px; height: 28px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px;
    }
    .kpi-card.green .kpi-icon { background: var(--mint); color: var(--green); }
    .kpi-card.teal  .kpi-icon { background: var(--teal-light); color: var(--teal); }
    .kpi-card.gold  .kpi-icon { background: var(--gold-light); color: var(--gold-text); }
    .kpi-card.blue  .kpi-icon { background: var(--blue-light); color: var(--blue-text); }

    .kpi-value { font-size: 34px; font-weight: 700; color: var(--text-main); line-height: 1; }

    .kpi-change {
      font-size: 11px; font-weight: 600;
      display: flex; align-items: center; gap: 4px;
    }
    .kpi-change.up   { color: var(--teal); }
    .kpi-change.down { color: var(--red); }
    .kpi-change.neutral { color: var(--text-muted); }
    .kpi-sub { font-size: 10px; color: var(--text-muted); }

    /* ── QUICK ACTIONS ── */
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 10px; margin-bottom: 18px;
    }

    .action-btn {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-md); padding: 14px 10px;
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      cursor: pointer; transition: all 0.2s; text-align: center;
      font-family: 'Poppins', sans-serif;
    }
    .action-btn:hover { background: var(--mint); border-color: var(--mint-deep); transform: translateY(-2px); box-shadow: var(--shadow-sm); }

    .action-btn i    { font-size: 18px; color: var(--green); }
    .action-btn strong { font-size: 11px; font-weight: 600; color: var(--text-main); display: block; line-height: 1.3; }
    .action-btn span { font-size: 9px; color: var(--text-muted); }

    /* ── CHART CARDS ── */
    .chart-row-main {
      display: grid; grid-template-columns: 2fr 1fr;
      gap: 14px; margin-bottom: 18px;
    }

    .chart-row-secondary {
      display: grid; grid-template-columns: 1fr 1fr 1fr;
      gap: 14px; margin-bottom: 18px;
    }

    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      box-shadow: var(--shadow-sm);
    }

    .card-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      margin-bottom: 14px;
    }

    .card-title { font-size: 13px; font-weight: 700; color: var(--text-main); }
    .card-sub   { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .card-badge {
      font-size: 10px; font-weight: 600; padding: 4px 10px;
      border-radius: 20px; background: var(--mint); color: var(--green);
      border: 1px solid var(--mint-mid); white-space: nowrap;
    }

    .chart-legend {
      display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 10px;
    }
    .legend-item {
      display: flex; align-items: center; gap: 5px;
      font-size: 11px; color: var(--text-muted); font-weight: 500;
    }
    .legend-dot {
      width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0;
    }

    /* ── TABLE SECTIONS ── */
    .tables-row {
      display: grid; grid-template-columns: 3fr 2fr;
      gap: 14px; margin-bottom: 18px;
    }

    .section-title {
      font-size: 13px; font-weight: 700; color: var(--text-main);
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 14px;
    }

    .view-all-btn {
      font-size: 11px; font-weight: 500; color: var(--green);
      background: none; border: none; cursor: pointer;
      font-family: 'Poppins', sans-serif; transition: color 0.2s;
    }
    .view-all-btn:hover { color: var(--green-dark); text-decoration: underline; }

    .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .data-table th {
      text-align: left; padding: 8px 10px;
      font-size: 10px; font-weight: 700; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 0.07em;
      background: #f8fdf9; border-bottom: 1px solid var(--border);
    }
    .data-table td {
      padding: 10px 10px; border-bottom: 1px solid #f0faf4;
      color: var(--text-main); font-weight: 400;
      vertical-align: middle;
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #f8fdf9; }

    .pill {
      padding: 3px 10px; border-radius: 20px;
      font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;
    }
    .pill.hired     { background: var(--teal-light); color: #0f5e48; }
    .pill.interview, .pill.shortlisted { background: var(--blue-light); color: var(--blue-text); }
    .pill.pending, .pill.applied       { background: var(--gold-light); color: var(--gold-text); }
    .pill.reviewing { background: #e8e0f8; color: #5a3d9a; }
    .pill.rejected  { background: var(--red-light); color: #8c1c1c; }
    .pill.active    { background: var(--teal-light); color: #0f5e48; }
    .pill.verified  { background: var(--blue-light); color: var(--blue-text); }
    .pill.approved  { background: var(--teal-light); color: #0f5e48; }

    /* ── ACTIVITY FEED ── */
    .activity-list { display: flex; flex-direction: column; gap: 8px; }
    .activity-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px; border-radius: var(--radius-sm);
      background: #f8fdf9; border: 1px solid var(--mint);
      transition: background 0.2s;
    }
    .activity-item:hover { background: var(--mint); }
    .activity-icon {
      width: 30px; height: 30px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; flex-shrink: 0;
    }
    .activity-icon.app  { background: var(--mint); color: var(--green); }
    .activity-icon.emp  { background: var(--blue-light); color: var(--blue-text); }
    .activity-icon.job  { background: var(--gold-light); color: var(--gold-text); }
    .activity-icon.hire { background: var(--teal-light); color: var(--teal); }
    .activity-text strong { font-size: 11px; font-weight: 600; color: var(--text-main); display: block; line-height: 1.4; }
    .activity-text span   { font-size: 10px; color: var(--text-muted); }

    /* ── SKILLS TAGS ── */
    .skills-cloud { display: flex; flex-wrap: wrap; gap: 7px; }
    .skill-tag {
      padding: 5px 12px; border-radius: 20px;
      font-size: 11px; font-weight: 600;
      background: var(--mint); color: var(--green-dark);
      border: 1px solid var(--mint-mid);
      display: flex; align-items: center; gap: 5px;
      transition: all 0.2s; cursor: default;
    }
    .skill-tag:hover { background: var(--mint-mid); transform: translateY(-1px); }
    .skill-count {
      background: var(--green); color: #fff;
      font-size: 9px; font-weight: 700;
      padding: 1px 6px; border-radius: 10px;
    }

    /* ── BOTTOM ROW ── */
    .bottom-row {
      display: grid; grid-template-columns: 2fr 1fr 1fr;
      gap: 14px;
    }

    /* ── STAT BARS ── */
    .stat-bar-item { margin-bottom: 12px; }
    .stat-bar-label {
      display: flex; justify-content: space-between;
      font-size: 11px; font-weight: 500; color: var(--text-mid);
      margin-bottom: 5px;
    }
    .stat-bar-track {
      height: 7px; background: var(--mint); border-radius: 4px; overflow: hidden;
    }
    .stat-bar-fill {
      height: 100%; border-radius: 4px;
      background: linear-gradient(90deg, var(--green-light), var(--green-dark));
      transition: width 1.2s cubic-bezier(.22,1,.36,1);
    }

    /* ── SIDEBAR TOGGLE (mobile) ── */
    .sidebar-toggle {
      display: none; position: fixed; bottom: 20px; left: 20px;
      width: 46px; height: 46px; border-radius: 50%;
      background: var(--green); color: #fff;
      border: none; cursor: pointer; z-index: 300;
      font-size: 18px; box-shadow: var(--shadow-md);
      align-items: center; justify-content: center;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 1100px) {
      .kpi-grid         { grid-template-columns: repeat(2, 1fr); }
      .quick-actions    { grid-template-columns: repeat(3, 1fr); }
      .chart-row-main   { grid-template-columns: 1fr; }
      .chart-row-secondary { grid-template-columns: 1fr 1fr; }
      .bottom-row       { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 860px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .topbar, .content { margin-left: 0; left: 0; }
      .sidebar-toggle { display: flex; }
      .tables-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 600px) {
      .kpi-grid            { grid-template-columns: 1fr; }
      .quick-actions       { grid-template-columns: repeat(2, 1fr); }
      .chart-row-secondary { grid-template-columns: 1fr; }
      .bottom-row          { grid-template-columns: 1fr; }
      .content-inner       { padding: 16px; }
    }

    /* ── ANIMATIONS ── */
    .fade-in {
      opacity: 0; transform: translateY(16px);
      transition: opacity 0.55s ease, transform 0.55s ease;
    }
    .fade-in.visible { opacity: 1; transform: translateY(0); }

    /* ── LOGOUT MODAL ── */
    .modal-overlay {
      display: none;
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(26, 46, 34, 0.5);
      z-index: 9999;
      justify-content: center; align-items: center;
      backdrop-filter: blur(2px);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .modal-overlay.active {
      display: flex;
      opacity: 1;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      padding: 32px 24px;
      max-width: 380px;
      width: 90%;
      box-shadow: 0 20px 60px rgba(45, 80, 64, 0.2);
      text-align: center;
      transform: scale(0.9);
      transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .modal-overlay.active .modal-content {
      transform: scale(1);
    }

    .modal-icon {
      font-size: 48px;
      color: var(--gold);
      margin-bottom: 16px;
    }

    .modal-title {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 8px;
    }

    .modal-text {
      font-size: 13px;
      color: var(--text-muted);
      line-height: 1.6;
      margin-bottom: 24px;
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      justify-content: center;
    }

    .modal-btn {
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .modal-btn-cancel {
      background: #f0f0f0;
      color: var(--text-main);
    }

    .modal-btn-cancel:hover {
      background: #e0e0e0;
    }

    .modal-btn-confirm {
      background: #c0392b;
      color: white;
    }

    .modal-btn-confirm:hover {
      background: #a02f23;
    }
  </style>
</head>
<body>

<!-- ════ SIDEBAR ════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">TS</div>
    <div>
      <div class="logo-text">Talent<span>Scout</span> AI</div>
      <div class="logo-sub">PESO NASUGBU, BATANGAS</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a href="./index.php" class="nav-item active">
      <i class="fa-solid fa-chart-pie"></i> Dashboard
    </a>

    <div class="nav-section-label">Management</div>
    <a href="./modules/employer-management/" class="nav-item">
      <i class="fa-solid fa-building"></i> Employers
    </a>
    <a href="./modules/employee-management/" class="nav-item">
      <i class="fa-solid fa-users"></i> Job Seekers
      <?php if ($unverified_users > 0): ?>
        <span class="nav-badge gold"><?= $unverified_users ?></span>
      <?php endif; ?>
    </a>
    <a href="./modules/application-tracking/" class="nav-item">
      <i class="fa-solid fa-clipboard-list"></i> Applications
    </a>
    <a href="./modules/skill-management/" class="nav-item">
      <i class="fa-solid fa-book-open"></i> Skill Management
    </a>

    <div class="nav-section-label">Insights</div>
    <a href="./modules/analytics/" class="nav-item">
      <i class="fa-solid fa-chart-line"></i> Analytics
    </a>

  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar">PA</div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">PESO Admin</div>
        <div class="sidebar-user-role">Administrator</div>
      </div>
    </div>
  </div>
</aside>

<!-- ════ TOPBAR ════ -->
<header class="topbar">
  <div class="topbar-left">
    <div>
      <div class="topbar-title">Admin Dashboard</div>
      <div class="topbar-sub"><?= date('l, F j, Y') ?> &mdash; PESO Nasugbu, Batangas</div>
    </div>
  </div>
  <div class="topbar-right">
    <button class="topbar-icon-btn" id="logoutBtn" title="Logout" onclick="openLogoutModal()">
      <i class="fa-solid fa-right-from-bracket"></i>
    </button>
    <div class="topbar-avatar" title="PESO Admin">PA</div>
  </div>
</header>

<!-- ════ MAIN CONTENT ════ -->
<main class="content">
<div class="content-inner">

  <!-- Page Header -->
  <div class="page-header fade-in">
    <div>
      <h1>Platform Overview</h1>
      <p>Real-time statistics pulled from the TalentScout AI database</p>
    </div>
    <div class="header-actions">
      
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid fade-in">
    <div class="kpi-card green">
      <div class="kpi-label">
        <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
        Job Seekers
      </div>
      <div class="kpi-value"><?= number_format($total_seekers) ?></div>
      <div class="kpi-change <?= $seeker_up ? 'up' : 'down' ?>">
        <i class="fa-solid fa-arrow-<?= $seeker_up ? 'up' : 'down' ?>"></i>
        <?= $seeker_change ?> vs last month
      </div>
      <div class="kpi-sub"><?= $active_seekers ?> active &bull; <?= $seekers_this_month ?> new this month</div>
    </div>

    <div class="kpi-card teal">
      <div class="kpi-label">
        <div class="kpi-icon"><i class="fa-solid fa-building"></i></div>
        Employers
      </div>
      <div class="kpi-value"><?= number_format($total_employers) ?></div>
      <div class="kpi-change <?= $employer_up ? 'up' : 'down' ?>">
        <i class="fa-solid fa-arrow-<?= $employer_up ? 'up' : 'down' ?>"></i>
        <?= $employer_change ?> vs last month
      </div>
      <div class="kpi-sub"><?= $employers_this_month ?> new this month &bull; <?= $pending_approvals ?> pending</div>
    </div>

    <div class="kpi-card gold">
      <div class="kpi-label">
        <div class="kpi-icon"><i class="fa-solid fa-briefcase"></i></div>
        Active Jobs
      </div>
      <div class="kpi-value"><?= number_format($active_jobs) ?></div>
      <div class="kpi-change <?= $job_up ? 'up' : 'down' ?>">
        <i class="fa-solid fa-arrow-<?= $job_up ? 'up' : 'down' ?>"></i>
        <?= $job_change ?> vs last month
      </div>
      <div class="kpi-sub"><?= $jobs_this_month ?> posted this month &bull; <?= $total_applications ?> total applications</div>
    </div>

    <div class="kpi-card blue">
      <div class="kpi-label">
        <div class="kpi-icon"><i class="fa-solid fa-check-circle"></i></div>
        Hires This Month
      </div>
      <div class="kpi-value"><?= number_format($hires_this_month) ?></div>
      <div class="kpi-change <?= $hire_up ? 'up' : 'down' ?>">
        <i class="fa-solid fa-arrow-<?= $hire_up ? 'up' : 'down' ?>"></i>
        <?= $hire_change ?> vs last month
      </div>
      <div class="kpi-sub">
        <?= $avg_days > 0 ? "Avg {$avg_days} days to hire" : "No hire data yet" ?>
      </div>
    </div>
  </div>

  

  <!-- Main Charts Row -->
  <div class="chart-row-main fade-in">
    <!-- Applications Over Time -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Applications Over Time</div>
          <div class="card-sub">Weekly volume — last 8 weeks from database</div>
        </div>
        <div class="card-badge">Live</div>
      </div>
      <div class="chart-legend">
        <span class="legend-item"><span class="legend-dot" style="background:#3d6b50;"></span>Total Applications</span>
        <span class="legend-item"><span class="legend-dot" style="background:#1a8a6e;"></span>Hired</span>
        <span class="legend-item"><span class="legend-dot" style="background:#3a7cbf;"></span>Interviews</span>
      </div>
      <div style="position:relative;width:100%;height:240px;">
        <canvas id="appChart" role="img" aria-label="Line chart showing weekly application trends over the last 8 weeks">Weekly application volume loaded from database.</canvas>
      </div>
    </div>

    <!-- Application Status Donut -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Pipeline Status</div>
          <div class="card-sub">All applications breakdown</div>
        </div>
      </div>
      <div class="chart-legend">
        <span class="legend-item"><span class="legend-dot" style="background:#1a8a6e;"></span>Hired <?= $app_hired ?></span>
        <span class="legend-item"><span class="legend-dot" style="background:#3a7cbf;"></span>Interview <?= $app_interview ?></span>
        <span class="legend-item"><span class="legend-dot" style="background:#c8a46a;"></span>Pending <?= $app_pending ?></span>
        <span class="legend-item"><span class="legend-dot" style="background:#c0392b;"></span>Rejected <?= $app_rejected ?></span>
      </div>
      <div style="position:relative;width:100%;height:210px;">
        <canvas id="statusChart" role="img" aria-label="Donut chart of application statuses">Application pipeline status breakdown.</canvas>
      </div>
    </div>
  </div>

  <!-- Secondary Charts -->
  <div class="chart-row-secondary fade-in">
    <!-- Job Categories -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Top Job Categories</div>
          <div class="card-sub">Applications per category</div>
        </div>
      </div>
      <div style="position:relative;width:100%;height:210px;">
        <canvas id="catChart" role="img" aria-label="Horizontal bar chart of top job categories by applications">Top job categories by application count.</canvas>
      </div>
    </div>

    <!-- User Registrations -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">User Registrations</div>
          <div class="card-sub">Monthly — seekers vs employers</div>
        </div>
      </div>
      <div class="chart-legend">
        <span class="legend-item"><span class="legend-dot" style="background:#3d6b50;"></span>Job Seekers</span>
        <span class="legend-item"><span class="legend-dot" style="background:#c8a46a;"></span>Employers</span>
      </div>
      <div style="position:relative;width:100%;height:185px;">
        <canvas id="userChart" role="img" aria-label="Grouped bar chart showing monthly registrations for seekers and employers">Monthly user registration trends.</canvas>
      </div>
    </div>

    <!-- Avg Days to Hire -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Avg. Days to Hire</div>
          <div class="card-sub">By job category</div>
        </div>
      </div>
      <div style="position:relative;width:100%;height:210px;">
        <canvas id="speedChart" role="img" aria-label="Bar chart showing average days to hire per job category">Average hiring speed by job category.</canvas>
      </div>
    </div>
  </div>

  

</div><!-- .content-inner -->
</main>

<!-- Sidebar Toggle (mobile) -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
  <i class="fa-solid fa-bars"></i>
</button>

<!-- ═══ LOGOUT MODAL ═══ -->
<div class="modal-overlay" id="logoutModal">
  <div class="modal-content">
    <div class="modal-icon">
      <i class="fa-solid fa-exclamation"></i>
    </div>
    <div class="modal-title">Confirm Logout</div>
    <div class="modal-text">Are you sure you want to log out? You'll need to log in again to access the admin dashboard.</div>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
      <button class="modal-btn modal-btn-confirm" onclick="confirmLogout()">Logout</button>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
  // PHP data → JS
  const weekLabels    = <?= $j_week_labels ?>;
  const weekTotal     = <?= $j_week_total ?>;
  const weekHired     = <?= $j_week_hired ?>;
  const weekInterviews= <?= $j_week_interviews ?>;
  const moLabels      = <?= $j_mo_labels ?>;
  const moSeekers     = <?= $j_mo_seekers ?>;
  const moEmployers   = <?= $j_mo_employers ?>;
  const catLabels     = <?= $j_cat_labels ?>;
  const catCounts     = <?= $j_cat_counts ?>;
  const speedLabels   = <?= $j_speed_labels ?>;
  const speedDays     = <?= $j_speed_days ?>;

  const appHired     = <?= $app_hired ?>;
  const appInterview = <?= $app_interview ?>;
  const appPending   = <?= $app_pending ?>;
  const appRejected  = <?= $app_rejected ?>;

  // Shared defaults
  Chart.defaults.font.family = "'Poppins', sans-serif";
  Chart.defaults.font.size   = 11;
  Chart.defaults.color       = '#7a9a82';

  // ── 1. Applications line chart ─────────────────────────────────────────────
  new Chart(document.getElementById('appChart'), {
    type: 'line',
    data: {
      labels: weekLabels,
      datasets: [
        {
          label: 'Applications',
          data: weekTotal,
          borderColor: '#3d6b50',
          backgroundColor: 'rgba(61,107,80,0.08)',
          borderWidth: 2.5,
          pointRadius: 4,
          pointBackgroundColor: '#3d6b50',
          tension: 0.4,
          fill: true,
          borderDash: []
        },
        {
          label: 'Hired',
          data: weekHired,
          borderColor: '#1a8a6e',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: '#1a8a6e',
          tension: 0.4,
          borderDash: [6, 3]
        },
        {
          label: 'Interviews',
          data: weekInterviews,
          borderColor: '#3a7cbf',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: '#3a7cbf',
          tension: 0.4,
          borderDash: [3, 3]
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(200,230,212,0.4)' }, ticks: { autoSkip: false, maxRotation: 35 } },
        y: { grid: { color: 'rgba(200,230,212,0.4)' }, beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // ── 2. Pipeline status donut ───────────────────────────────────────────────
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Hired', 'Interview', 'Pending', 'Rejected'],
      datasets: [{
        data: [appHired, appInterview, appPending, appRejected],
        backgroundColor: ['#1a8a6e', '#3a7cbf', '#c8a46a', '#c0392b'],
        borderColor: '#fff',
        borderWidth: 3,
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
              const pct   = total > 0 ? Math.round((ctx.raw / total) * 100) : 0;
              return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
            }
          }
        }
      }
    }
  });

  // ── 3. Top categories horizontal bar ──────────────────────────────────────
  new Chart(document.getElementById('catChart'), {
    type: 'bar',
    data: {
      labels: catLabels,
      datasets: [{
        label: 'Applications',
        data: catCounts,
        backgroundColor: [
          'rgba(61,107,80,0.82)',
          'rgba(26,138,110,0.78)',
          'rgba(58,124,191,0.78)',
          'rgba(200,164,106,0.82)',
          'rgba(90,138,104,0.72)',
          'rgba(61,107,80,0.55)'
        ],
        borderColor: 'transparent',
        borderRadius: 5
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(200,230,212,0.35)' }, beginAtZero: true, ticks: { stepSize: 1 } },
        y: { grid: { display: false } }
      }
    }
  });

  // ── 4. Monthly user registrations ─────────────────────────────────────────
  new Chart(document.getElementById('userChart'), {
    type: 'bar',
    data: {
      labels: moLabels.length ? moLabels : ['No data'],
      datasets: [
        {
          label: 'Job Seekers',
          data: moSeekers.length ? moSeekers : [0],
          backgroundColor: 'rgba(61,107,80,0.80)',
          borderRadius: 5,
          borderSkipped: false
        },
        {
          label: 'Employers',
          data: moEmployers.length ? moEmployers : [0],
          backgroundColor: 'rgba(200,164,106,0.80)',
          borderRadius: 5,
          borderSkipped: false
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 35 } },
        y: { grid: { color: 'rgba(200,230,212,0.35)' }, beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // ── 5. Avg days to hire ────────────────────────────────────────────────────
  new Chart(document.getElementById('speedChart'), {
    type: 'bar',
    data: {
      labels: speedLabels,
      datasets: [{
        label: 'Avg Days',
        data: speedDays,
        backgroundColor: speedDays.map((v, i) =>
          i === 0 ? 'rgba(26,138,110,0.82)' : `rgba(61,107,80,${0.75 - i * 0.08})`
        ),
        borderRadius: 5,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 35 } },
        y: { grid: { color: 'rgba(200,230,212,0.35)' }, beginAtZero: true, ticks: { stepSize: 1 } }
      }
    }
  });

  // ── Sidebar toggle ─────────────────────────────────────────────────────────
  const sidebar       = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });
  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });

  // ── Scroll reveal ──────────────────────────────────────────────────────────
  const revealObs = new IntersectionObserver((entries) => {
    entries.forEach((e, i) => {
      if (e.isIntersecting) {
        setTimeout(() => e.target.classList.add('visible'), i * 60);
        revealObs.unobserve(e.target);
      }
    });
  }, { threshold: 0.06 });

  document.querySelectorAll('.fade-in').forEach(el => revealObs.observe(el));

  // ── Animate stat bars ──────────────────────────────────────────────────────
  setTimeout(() => {
    document.querySelectorAll('.stat-bar-fill').forEach(bar => {
      const target = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => { bar.style.width = target; }, 300);
    });
  }, 500);

  // ── Logout modal functions ──────────────────────────────────────────────────
  function openLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.add('active');
  }

  function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    modal.classList.remove('active');
  }

  function confirmLogout() {
    // Close modal with animation
    const modal = document.getElementById('logoutModal');
    modal.classList.remove('active');
    
    // Wait for animation to complete, then redirect to login
    setTimeout(() => {
      window.location.href = './login.php';
    }, 300);
  }

  // Close modal when clicking outside of it
  document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeLogoutModal();
    }
  });

  // Close modal on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeLogoutModal();
    }
  });
</script>
</body>
</html>