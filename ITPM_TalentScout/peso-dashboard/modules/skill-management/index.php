<?php
session_start();
require_once __DIR__ . '/../../auth.php';
peso_require_admin('../../login.php');
require_once '../../../database/db.php';

$conn = getConnection();

// Get skill statistics
$stats = [
    'total_categories' => 0,
    'total_synonyms' => 0
];

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM skill_categories");
    if ($row = $result->fetch_assoc()) {
        $stats['total_categories'] = $row['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM skill_synonyms");
    if ($row = $result->fetch_assoc()) {
        $stats['total_synonyms'] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Skill Management Error: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skill Management – TalentScout AI</title>
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
    }

    html { scroll-behavior: smooth; }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
    }
    a { text-decoration: none; color: inherit; }

    /* SIDEBAR */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: 240px; background: var(--green-deeper);
      display: flex; flex-direction: column;
      z-index: 200; box-shadow: 2px 0 8px rgba(0,0,0,0.1);
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
      font-size: 11px; font-weight: 700; color: #fff;
      flex-shrink: 0;
    }

    .logo-text { font-size: 14px; font-weight: 700; color: #fff; line-height: 1.2; }
    .logo-text span { color: var(--mint-deep); }
    .logo-sub { font-size: 9px; color: rgba(255,255,255,0.4); }

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

    /* MAIN CONTENT */
    .content {
      margin-left: 240px;
      min-height: 100vh;
      padding: 24px;
    }

    .page-header {
      display: flex; align-items: flex-start;
      justify-content: space-between; flex-wrap: wrap; gap: 12px;
      margin-bottom: 22px;
    }

    .page-header h1 { font-size: 20px; font-weight: 700; color: var(--text-main); }
    .page-header p { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

    .page-header-right {
      display: flex; gap: 10px; flex-wrap: wrap;
    }

    /* BUTTONS */
    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: var(--radius-sm);
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-primary {
      background: var(--green);
      color: white;
    }

    .btn-primary:hover {
      background: var(--green-dark);
      box-shadow: var(--shadow-md);
    }

    .btn-secondary {
      background: var(--bg-card);
      color: var(--text-main);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover {
      background: var(--mint);
      border-color: var(--mint-mid);
    }

    .btn-danger {
      background: var(--red);
      color: white;
    }

    .btn-danger:hover {
      background: #a02f23;
      box-shadow: var(--shadow-md);
    }

    .btn-small {
      padding: 6px 10px;
      font-size: 12px;
    }

    /* CARDS */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      margin-bottom: 18px;
      box-shadow: var(--shadow-sm);
    }

    .card-title {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 16px;
    }

    /* STATS */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
      margin-bottom: 18px;
    }

    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 20px;
      display: flex; flex-direction: column; gap: 6px;
      position: relative; overflow: hidden;
      box-shadow: var(--shadow-sm);
    }

    .kpi-card::after {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 4px;
      background: var(--green);
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }

    .kpi-value { font-size: 28px; font-weight: 700; color: var(--text-main); }
    .kpi-label { font-size: 12px; font-weight: 500; color: var(--text-muted); }

    /* TABLE */
    .table-wrapper {
      overflow-x: auto;
      max-height: 600px;
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      margin-bottom: 18px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    thead {
      position: sticky;
      top: 0;
      background: var(--mint);
      border-bottom: 2px solid var(--border);
    }

    th {
      padding: 14px 16px;
      text-align: left;
      font-weight: 700;
      color: var(--text-main);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      color: var(--text-mid);
    }

    tr:last-child td {
      border-bottom: none;
    }

    tbody tr:hover {
      background: var(--mint);
    }

    /* MODAL */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      padding: 24px;
      width: 90%;
      max-width: 500px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
    }

    .modal-header {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--text-muted);
      transition: all 0.2s;
    }

    .modal-close:hover {
      color: var(--text-main);
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      color: var(--text-main);
      transition: all 0.2s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(61, 107, 80, 0.1);
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
      justify-content: flex-end;
    }

    /* MESSAGES */
    .alert {
      padding: 12px 16px;
      border-radius: var(--radius-md);
      margin-bottom: 16px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background: var(--teal-light);
      color: var(--teal);
      border: 1px solid var(--teal-light);
    }

    .alert-error {
      background: var(--red-light);
      color: var(--red);
      border: 1px solid var(--red-light);
    }

    /* TABS */
    .tabs-container {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid var(--border);
    }

    .tab-btn {
      padding: 12px 16px;
      background: none;
      border: none;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      color: var(--text-muted);
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
      transition: all 0.2s;
    }

    .tab-btn.active {
      color: var(--green);
      border-bottom-color: var(--green);
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .tag {
      display: inline-block;
      background: var(--mint-mid);
      color: var(--green-deeper);
      padding: 4px 8px;
      border-radius: var(--radius-sm);
      font-size: 11px;
      font-weight: 600;
      margin: 2px;
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">TS</div>
    <div>
      <div class="logo-text">Talent<span>Scout</span> AI</div>
      <div class="logo-sub">PESO NASUGBU, BATANGAS</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a href="../../index.php" class="nav-item">
      <i class="fa-solid fa-chart-pie"></i> Dashboard
    </a>

    <div class="nav-section-label">Management</div>
    <a href="../employer-management/" class="nav-item">
      <i class="fa-solid fa-building"></i> Employers
    </a>
    <a href="../employee-management/" class="nav-item">
      <i class="fa-solid fa-users"></i> Job Seekers
    </a>
    <a href="../application-tracking/" class="nav-item">
      <i class="fa-solid fa-clipboard-list"></i> Applications
    </a>
    <a href="./" class="nav-item active">
      <i class="fa-solid fa-book-open"></i> Skill Management
    </a>

    <div class="nav-section-label">Insights</div>
    <a href="../analytics/" class="nav-item">
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

<!-- MAIN CONTENT -->
<main class="content">
  <div class="page-header">
    <div>
      <h1>Skill Management</h1>
      <p>Manage skill categories, synonyms, and test matching algorithms</p>
    </div>
    <div class="page-header-right">
      <button class="btn btn-primary" onclick="openAddCategoryModal()">
        <i class="fa-solid fa-plus"></i> Add Category
      </button>
    </div>
  </div>

  <!-- ALERTS -->
  <div id="messages"></div>

  <!-- TABS -->
  <div class="tabs-container">
    <button class="tab-btn active" onclick="switchTab('categories')">
      <i class="fa-solid fa-list"></i> Categories
    </button>
    <button class="tab-btn" onclick="switchTab('search')">
      <i class="fa-solid fa-magnifying-glass"></i> Search
    </button>
    <button class="tab-btn" onclick="switchTab('test')">
      <i class="fa-solid fa-flask-vial"></i> Test Matching
    </button>
  </div>

  <!-- TAB: CATEGORIES -->
  <div id="categories" class="tab-content active">
    <div class="card">
      <div class="card-title">Skill Categories & Synonyms</div>
      <div class="table-wrapper">
        <table id="categoriesTable">
          <thead>
            <tr>
              <th>Category Name</th>
              <th>Canonical Name</th>
              <th>Synonyms</th>
              <th style="width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody id="categoriesBody">
            <tr>
              <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB: SEARCH -->
  <div id="search" class="tab-content">
    <div class="card">
      <div class="card-title">Search Skills</div>
      <div class="form-group">
        <label>Skill Name or Category</label>
        <input type="text" id="searchInput" placeholder="e.g., web developer, frontend, UI designer..."
               onkeyup="searchSkill()" style="padding: 12px;">
      </div>
      <div id="searchResults"></div>
    </div>
  </div>

  <!-- TAB: TEST MATCHING -->
  <div id="test" class="tab-content">
    <div class="card">
      <div class="card-title">Test Skill Matching</div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
        <div class="form-group">
          <label>First Skill</label>
          <input type="text" id="skill1" placeholder="e.g., web developer">
        </div>
        <div class="form-group">
          <label>Second Skill</label>
          <input type="text" id="skill2" placeholder="e.g., web programmer">
        </div>
      </div>
      <button class="btn btn-primary" onclick="testMatching()">
        <i class="fa-solid fa-play"></i> Test Match
      </button>
      <div id="testResult" style="margin-top: 16px;"></div>
    </div>
  </div>

</main>

<!-- ADD/EDIT CATEGORY MODAL -->
<div id="categoryModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span id="modalTitle">Add New Category</span>
      <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
    </div>

    <form id="categoryForm" onsubmit="saveCategoryForm(event)">
      <input type="hidden" id="categoryId">

      <div class="form-group">
        <label>Category Name (Display)</label>
        <input type="text" id="categoryName" required placeholder="e.g., Web Development Roles">
      </div>

      <div class="form-group">
        <label>Canonical Name (Normalized)</label>
        <input type="text" id="canonicalName" required placeholder="e.g., web developer">
      </div>

      <div class="form-group">
        <label>Synonyms (comma-separated)</label>
        <textarea id="synonyms" placeholder="web programmer, web designer, website developer" 
                  style="height: 100px; resize: vertical;"></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">
          <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-save"></i> Save Category
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ADD SYNONYM MODAL -->
<div id="synonymModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span>Add Synonym</span>
      <button class="modal-close" onclick="closeSynonymModal()">&times;</button>
    </div>

    <form onsubmit="saveSynonymForm(event)">
      <input type="hidden" id="synonymCategoryId">

      <div class="form-group">
        <label>Synonym</label>
        <input type="text" id="synonymText" required placeholder="Enter synonym...">
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="closeSynonymModal()">
          <i class="fa-solid fa-times"></i> Cancel
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Add Synonym
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Tab switching
  function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');
    document.getElementById(tabName).classList.add('active');
  }

  // Load categories on page load
  window.addEventListener('load', loadCategories);

  async function loadCategories() {
    try {
      const response = await fetch('api.php?action=get-categories');
      const data = await response.json();

      const tbody = document.getElementById('categoriesBody');
      tbody.innerHTML = '';

      if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">No categories found</td></tr>';
        return;
      }

      data.forEach(category => {
        const synonymsList = category.synonyms && category.synonyms.length > 0
          ? category.synonyms.map(syn => `<span class="tag">${syn.synonym}</span>`).join('')
          : '<span style="color: var(--text-muted); font-size: 12px;">None</span>';

        const row = `
          <tr>
            <td><strong>${category.category_name}</strong></td>
            <td><code style="background: var(--mint); padding: 3px 6px; border-radius: 4px; font-size: 12px;">${category.canonical_name}</code></td>
            <td>${synonymsList}</td>
            <td style="display: flex; gap: 6px;">
              <button class="btn btn-small btn-secondary" onclick="openEditCategoryModal(${category.category_id}, '${category.category_name.replace(/'/g, "\\'")}', '${category.canonical_name.replace(/'/g, "\\'")}')" title="Edit">
                <i class="fa-solid fa-edit"></i>
              </button>
              <button class="btn btn-small btn-primary" onclick="openAddSynonymModal(${category.category_id})" title="Add Synonym">
                <i class="fa-solid fa-plus"></i>
              </button>
              <button class="btn btn-small btn-danger" onclick="deleteCategory(${category.category_id})" title="Delete">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
      });

      updateStats();
    } catch (error) {
      showMessage('Error loading categories: ' + error.message, 'error');
    }
  }

  function openAddCategoryModal() {
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryForm').reset();
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('categoryModal').classList.add('active');
  }

  function openEditCategoryModal(id, name, canonical) {
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = name;
    document.getElementById('canonicalName').value = canonical;
    document.getElementById('synonyms').value = '';
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('categoryModal').classList.add('active');
  }

  function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
  }

  async function saveCategoryForm(e) {
    e.preventDefault();

    const categoryId = document.getElementById('categoryId').value;
    const categoryName = document.getElementById('categoryName').value;
    const canonicalName = document.getElementById('canonicalName').value;
    const synonymsStr = document.getElementById('synonyms').value;

    try {
      const action = categoryId ? 'update-category' : 'add-category';
      const payload = {
        category_id: categoryId || undefined,
        category_name: categoryName,
        canonical_name: canonicalName
      };

      const response = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();

      if (response.ok) {
        const newCategoryId = result.category_id || categoryId;

        if (synonymsStr && !categoryId) {
          const synonyms = synonymsStr.split(',').map(s => s.trim()).filter(s => s);
          for (const syn of synonyms) {
            await fetch('api.php?action=add-synonym', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ category_id: newCategoryId, synonym: syn })
            });
          }
        }

        showMessage('Category saved successfully!', 'success');
        closeCategoryModal();
        loadCategories();
      } else {
        showMessage(result.error || 'Error saving category', 'error');
      }
    } catch (error) {
      showMessage('Error: ' + error.message, 'error');
    }
  }

  function openAddSynonymModal(categoryId) {
    document.getElementById('synonymCategoryId').value = categoryId;
    document.getElementById('synonymText').value = '';
    document.getElementById('synonymModal').classList.add('active');
  }

  function closeSynonymModal() {
    document.getElementById('synonymModal').classList.remove('active');
  }

  async function saveSynonymForm(e) {
    e.preventDefault();

    const categoryId = document.getElementById('synonymCategoryId').value;
    const synonym = document.getElementById('synonymText').value;

    try {
      const response = await fetch('api.php?action=add-synonym', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: categoryId, synonym: synonym })
      });

      const result = await response.json();

      if (response.ok) {
        showMessage('Synonym added successfully!', 'success');
        closeSynonymModal();
        loadCategories();
      } else {
        showMessage(result.error || 'Error adding synonym', 'error');
      }
    } catch (error) {
      showMessage('Error: ' + error.message, 'error');
    }
  }

  async function deleteCategory(categoryId) {
    if (!confirm('Delete this category and all its synonyms?')) return;

    try {
      const response = await fetch('api.php?action=delete-category', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id: categoryId })
      });

      const result = await response.json();

      if (response.ok) {
        showMessage('Category deleted!', 'success');
        loadCategories();
      } else {
        showMessage(result.error || 'Error deleting category', 'error');
      }
    } catch (error) {
      showMessage('Error: ' + error.message, 'error');
    }
  }

  async function searchSkill() {
    const skill = document.getElementById('searchInput').value.trim();
    const resultsDiv = document.getElementById('searchResults');

    if (!skill) {
      resultsDiv.innerHTML = '';
      return;
    }

    try {
      const response = await fetch(`api.php?action=search-skill&skill=${encodeURIComponent(skill)}`);
      const results = await response.json();

      if (results.length === 0) {
        resultsDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: var(--text-muted);">No results found</p>';
        return;
      }

      let html = '<div style="display: grid; gap: 12px;">';
      results.forEach(result => {
        html += `
          <div class="card" style="margin-bottom: 0; padding: 16px;">
            <div><strong>${result.category_name}</strong></div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
              Canonical: <code style="background: var(--mint); padding: 2px 4px; border-radius: 3px;">${result.canonical_name}</code>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
              Synonyms: ${result.synonyms || 'None'}
            </div>
          </div>
        `;
      });
      html += '</div>';
      resultsDiv.innerHTML = html;
    } catch (error) {
      resultsDiv.innerHTML = '<p style="color: var(--red); padding: 20px;">Error: ' + error.message + '</p>';
    }
  }

  async function testMatching() {
    const skill1 = document.getElementById('skill1').value.trim();
    const skill2 = document.getElementById('skill2').value.trim();
    const resultDiv = document.getElementById('testResult');

    if (!skill1 || !skill2) {
      resultDiv.innerHTML = '<p style="color: var(--red);">Please enter both skills</p>';
      return;
    }

    try {
      const response = await fetch(`api.php?action=test-matching&skill1=${encodeURIComponent(skill1)}&skill2=${encodeURIComponent(skill2)}`);
      const result = await response.json();

      const matchBadgeColor = result.match ? 'var(--teal)' : 'var(--red)';
      const matchBadgeText = result.match ? 'MATCH' : 'NO MATCH';

      let html = `
        <div class="card" style="margin-bottom: 0; padding: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
              <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Skill 1</div>
              <div style="font-size: 13px; color: var(--text-main);">${result.skill1}</div>
            </div>
            <div>
              <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Skill 2</div>
              <div style="font-size: 13px; color: var(--text-main);">${result.skill2}</div>
            </div>
          </div>

          <hr style="border: none; border-top: 1px solid var(--border); margin: 16px 0;">

          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
            <div>
              <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Result</div>
              <span style="display: inline-block; background: ${matchBadgeColor}; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;">${matchBadgeText}</span>
            </div>
            <div>
              <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Match Type</div>
              <div style="font-size: 13px; color: var(--text-main); text-transform: capitalize;">${result.match_type}</div>
            </div>
            <div>
              <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Score</div>
              <div style="font-size: 13px; color: var(--text-main);">${result.match_score}%</div>
            </div>
          </div>

          ${result.category ? `
            <hr style="border: none; border-top: 1px solid var(--border); margin: 16px 0;">
            <div style="font-size: 12px; color: var(--text-muted);">
              <strong>Category:</strong> ${result.category.category_name}<br>
              <strong>Canonical:</strong> ${result.category.canonical_name}
            </div>
          ` : ''}
        </div>
      `;
      resultDiv.innerHTML = html;
    } catch (error) {
      resultDiv.innerHTML = '<p style="color: var(--red);">Error: ' + error.message + '</p>';
    }
  }



  async function updateStats() {
    try {
      const response = await fetch('api.php?action=get-categories');
      const categories = await response.json();

      document.getElementById('statCategories').textContent = categories.length;
    } catch (error) {
      console.error('Error updating stats:', error);
    }
  }

  function showMessage(msg, type) {
    const messagesDiv = document.getElementById('messages');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
      <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
      <span>${msg}</span>
    `;

    messagesDiv.innerHTML = '';
    messagesDiv.appendChild(alertDiv);

    setTimeout(() => alertDiv.remove(), 4000);
  }
</script>

</body>
</html>
