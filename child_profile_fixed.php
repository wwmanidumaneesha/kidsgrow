<?php
session_start(); // Start the session

// Check if the user is logged in and allowed (Admin or SuperAdmin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.php');
    exit;
}
$allowed_roles = ['Admin', 'SuperAdmin'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: unauthorized.php'); // Create an unauthorized access page if needed
    exit;
}

// Database connection details
$dsn         = "pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";
$db_user     = "neondb_owner";
$db_password = "npg_39CVAQbvIlOy";

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ---------------------------------
// AJAX & POST Request Handling
// ---------------------------------

// 1. Handle delete request (AJAX)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM child WHERE child_id = :child_id");
        $stmt->execute([':child_id' => $_POST['delete_id']]);
        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 2. Handle update via modal (for updating an existing child record)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['child_id']) && isset($_POST['health_division'])) {
    $child_id               = $_POST['child_id'];
    $health_division        = $_POST['health_division'];
    $family_health_division = $_POST['family_health_division'];
    $supplementary_record   = $_POST['supplementary_record'];
    $gn_record              = $_POST['gn_record'];
    // Safely retrieve the radio values as booleans
    $breast2 = isset($_POST['breast2']) ? $_POST['breast2'] === 'Yes' : false;
    $breast4 = isset($_POST['breast4']) ? $_POST['breast4'] === 'Yes' : false;

    try {
        $stmt = $pdo->prepare("UPDATE child 
            SET health_medical_officer_division = :health_division, 
                family_health_medical_officer_division = :family_health_division,
                supplementary_record_number = :supplementary_record,
                gn_record_number = :gn_record,
                breastfeeding_only_2m = :breast2,
                breastfeeding_only_4m = :breast4
            WHERE child_id = :child_id");
        // Bind parameters with explicit types for booleans
        $stmt->bindParam(':health_division', $health_division);
        $stmt->bindParam(':family_health_division', $family_health_division);
        $stmt->bindParam(':supplementary_record', $supplementary_record);
        $stmt->bindParam(':gn_record', $gn_record);
        $stmt->bindParam(':breast2', $breast2, PDO::PARAM_BOOL);
        $stmt->bindParam(':breast4', $breast4, PDO::PARAM_BOOL);
        $stmt->bindParam(':child_id', $child_id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// 3. Handle Add Child submission via modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && !isset($_POST['child_id'])) {
    // Retrieve and sanitize form data for adding a new child record.
    $name = trim($_POST['name']);
    $birth_date = $_POST['birth_date'];
    $registered_date = $_POST['registered_date'];
    $health_medical_officer_division = trim($_POST['health_medical_officer_division']);
    $weight = $_POST['weight'];
    $gender = $_POST['gender'];
    $family_health_medical_officer_division = trim($_POST['family_health_medical_officer_division']);
    $supplementary_regional_record_number = trim($_POST['supplementary_regional_record_number']);
    $grama_niladhari_record_number = trim($_POST['grama_niladhari_record_number']);
    $birth_hospital = trim($_POST['birth_hospital']);
    $mother_id = $_POST['mother_id'];
    $breastfeeding = ($_POST['breastfeeding'] === 'Yes') ? true : false;
    $hypothyroidism = ($_POST['hypothyroidism'] === 'Yes') ? true : false;
    $hypothyroidism_result = $_POST['hypothyroidism_result'];
    $reasons_to_preserve = trim($_POST['reasons_to_preserve']);

    // Validate mother_id as a positive integer.
    if (!filter_var($mother_id, FILTER_VALIDATE_INT) || (int)$mother_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid Mother ID. Please provide a valid positive integer."]);
        exit;
    }

    // Check if the mother exists in the parent table.
    $checkParent = $pdo->prepare("SELECT COUNT(*) FROM parent WHERE parent_id = ?");
    $checkParent->execute([$mother_id]);
    $parentExists = $checkParent->fetchColumn();

    if (!$parentExists) {
        echo json_encode(["status" => "error", "message" => "Mother is not registered. Please register the mother first."]);
        exit;
    }

    try {
        // Insert the new child record.
        $stmt = $pdo->prepare("INSERT INTO child (
            parent_id, name, birth_date, registered_date, birth_hospital, breastfeeding_within_1h, 
            congenital_hypothyroidism_check, hypothyroidism_test_results, reasons_to_preserve, 
            weight, sex, health_medical_officer_division, family_health_medical_officer_division, 
            supplementary_record_number, gn_record_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $mother_id, $name, $birth_date, $registered_date, $birth_hospital, $breastfeeding,
            $hypothyroidism, $hypothyroidism_result, $reasons_to_preserve,
            $weight, $gender, $health_medical_officer_division, $family_health_medical_officer_division,
            $supplementary_regional_record_number, $grama_niladhari_record_number
        ]);
        echo json_encode(["status" => "success", "message" => "Child record added successfully."]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error adding child record: " . $e->getMessage()]);
    }
    exit;
}

// 4. Handle AJAX search (and filter) requests
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    // Free-text search parameter
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Additional filter parameters from the filter modal
    $child_id_filter = isset($_GET['child_id']) ? trim($_GET['child_id']) : '';
    $name_filter = isset($_GET['name']) ? trim($_GET['name']) : '';
    $birth_date_filter = isset($_GET['birth_date']) ? trim($_GET['birth_date']) : '';
    $weight_filter = isset($_GET['weight']) ? trim($_GET['weight']) : '';
    $gender_filter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
    $birth_hospital_filter = isset($_GET['birth_hospital']) ? trim($_GET['birth_hospital']) : '';
    $health_division_filter = isset($_GET['health_division']) ? trim($_GET['health_division']) : '';
    $family_health_division_filter = isset($_GET['family_health_division']) ? trim($_GET['family_health_division']) : '';
    $parent_id_filter = isset($_GET['parent_id']) ? trim($_GET['parent_id']) : '';
    $breastfeeding_filter = isset($_GET['breastfeeding']) ? trim($_GET['breastfeeding']) : '';
    $tested_filter = isset($_GET['tested']) ? trim($_GET['tested']) : '';
    $result_filter = isset($_GET['result']) ? trim($_GET['result']) : '';

    $query = "SELECT * FROM child WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $query .= " AND (name ILIKE :search OR birth_hospital ILIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($child_id_filter !== '') {
        $query .= " AND child_id = :child_id";
        $params[':child_id'] = $child_id_filter;
    }
    if ($name_filter !== '') {
        $query .= " AND name ILIKE :name";
        $params[':name'] = "%$name_filter%";
    }
    if ($birth_date_filter !== '') {
        $query .= " AND birth_date = :birth_date";
        $params[':birth_date'] = $birth_date_filter;
    }
    if ($weight_filter !== '') {
        $query .= " AND weight = :weight";
        $params[':weight'] = $weight_filter;
    }
    if ($gender_filter !== '') {
        $query .= " AND sex = :gender";
        $params[':gender'] = $gender_filter;
    }
    if ($birth_hospital_filter !== '') {
        $query .= " AND birth_hospital ILIKE :birth_hospital";
        $params[':birth_hospital'] = "%$birth_hospital_filter%";
    }
    if ($health_division_filter !== '') {
        $query .= " AND health_medical_officer_division ILIKE :health_division";
        $params[':health_division'] = "%$health_division_filter%";
    }
    if ($family_health_division_filter !== '') {
        $query .= " AND family_health_medical_officer_division ILIKE :family_health_division";
        $params[':family_health_division'] = "%$family_health_division_filter%";
    }
    if ($parent_id_filter !== '') {
        $query .= " AND parent_id = :parent_id";
        $params[':parent_id'] = $parent_id_filter;
    }
    if ($breastfeeding_filter !== '') {
        $query .= " AND breastfeeding_within_1h = :breastfeeding";
        $params[':breastfeeding'] = ($breastfeeding_filter === "Yes") ? true : false;
    }
    if ($tested_filter !== '') {
        $query .= " AND congenital_hypothyroidism_check = :tested";
        $params[':tested'] = ($tested_filter === "Yes") ? true : false;
    }
    if ($result_filter !== '') {
        $query .= " AND hypothyroidism_test_results = :result";
        $params[':result'] = $result_filter;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $childData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($childData);
    exit;
}

// For normal page load, fetch all child records
$stmt = $pdo->query("SELECT * FROM child");
$childData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KidsGrow - Child Health Records</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <!-- Full CSS as provided -->
  <style>
    :root {
      --primary-color: #274FB4;
      --secondary-color: #8FC4F1;
      --text-dark: #333;
      --text-light: #666;
      --white: #fff;
      --border-radius: 8px;
      --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
    body {
      display: flex;
      background-color: #f0f0f0;
    }
    /* Sidebar */
    .sidebar {
      width: 200px;
      background: linear-gradient(180deg, #4a90e2, #357abd);
      min-height: 100vh;
      padding: 20px;
      color: white;
    }
    .sidebar .menu-item:hover {
      background-color: rgba(255, 255, 255, 0.2);
      padding-left: 10px;
      border-radius: var(--border-radius);
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 40px;
    }
    .menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 0;
      cursor: pointer;
      color: white;
      text-decoration: none;
    }
    /* Main Content */
    .main-content {
      flex: 1;
      padding: 20px;
    }
    .search-bar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 30px;
    }
    .search-container {
      position: relative;
      width: 400px;
    }
    .search-container input {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      border: 1px solid #ddd;
    }
    .search-container .search-icon {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #4a90e2;
    }
    .add-child-btn, .add-button {
      background-color: #1a47b8;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
    }
    /* Child Profiles Box */
    .child-profiles {
      background: white;
      border-radius: 10px;
      padding: 20px;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .header h2 {
      font-size: 1.8rem;
      color: var(--text-dark);
    }
    /* Filter Icon in Table Header */
    .filter-icon {
      font-size: 18px;
      cursor: pointer;
      color: #4a90e2;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      text-align: left;
      padding: 10px;
      font-size: 14px;
      border: none;
    }
    th {
      color: #666;
      font-weight: normal;
    }
    .action-icons, .actions {
      display: flex;
      gap: 10px;
    }
    .action-icons i, .actions i {
      color: #4a90e2;
      cursor: pointer;
    }
    .delete-icon {
      color: #ff4444 !important;
    }
    /* Extra (non-default) columns are hidden by default */
    .default-hidden {
      display: none;
    }
    /* Modal Styles (for update, filter, add, view and delete modals) */
    .modal {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: var(--white);
      padding: 2rem;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      width: 500px;
      max-width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      z-index: 1000;
      display: none;
    }
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(5px);
      z-index: 999;
      display: none;
    }
    /* Filter Modal (Form) Styles */
    .filter-title {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 30px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-size: 14px;
    }
    .form-control {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background: #f8f8f8;
    }
    .radio-group {
      display: flex;
      gap: 20px;
    }
    .radio-option {
      display: flex;
      align-items: center;
    }
    .radio-option input[type="radio"] {
      margin-right: 5px;
    }
    .hide-columns {
      margin-top: 30px;
    }
    .column-list {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
    }
    .column-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
    }
    .checkbox {
      width: 18px;
      height: 18px;
      border: 1px solid #ddd;
      border-radius: 3px;
      background: #f8f8f8;
    }
    .button-group {
      margin-top: 30px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .btn {
      padding: 8px 24px;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      font-size: 14px;
    }
    .btn-cancel {
      background: white;
      border: 1px solid #3366cc;
      color: #3366cc;
    }
    .btn-apply {
      background: #3366cc;
      color: white;
    }
    /* Update Modal Overrides */
    .btn-primary {
      background: #274FB4;
      color: #fff;
      border: none;
    }
    .btn-secondary {
      background: #fff;
      color: #274FB4;
      border: 1px solid #274FB4;
    }
    /* Alert Box */
    .alert {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      border-radius: var(--border-radius);
      color: #fff;
      font-size: 14px;
      font-weight: 500;
      z-index: 1001;
      display: none;
      animation: slideIn 0.3s ease-out;
    }
    .alert-success {
      background: #28a745;
    }
    .alert-error {
      background: #dc3545;
    }
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    /* ===== VIEW CHILD MODAL STYLES ===== */
    .view-child-modal .card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      padding-bottom: 40px; /* extra space at bottom */
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .view-child-modal .child-details {
      margin-bottom: 20px;
    }
    .view-child-modal .detail-row {
      display: flex;
      padding: 8px 0;
    }
    .view-child-modal .detail-row span:first-child {
      width: 250px;
      font-weight: 500;
    }
    .view-child-modal .section-title {
      font-weight: 600;
      font-size: 16px;
      margin: 20px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      border: 1px solid #c8d6e5;
      padding: 10px;
      border-radius: 5px 5px 0 0;
      background-color: #e3eaf2;
    }
    /* Hide the section content by default */
    .view-child-modal .section-content {
      display: none;
      border: 1px solid #c8d6e5;
      border-top: none;
      border-radius: 0 0 5px 5px;
      padding: 15px;
      background-color: #f1f6fb;
    }
    .view-child-modal .health-chart {
      width: 100%;
      border-collapse: collapse;
    }
    .view-child-modal .health-chart th, 
    .view-child-modal .health-chart td {
      border: 1px solid #c8d6e5;
      padding: 10px;
      text-align: left;
    }
    .view-child-modal .close-btn {
      background-color: #ff4444;
      color: white;
      border: none;
      padding: 10px 30px;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 50px;
      margin-bottom: 20px;
      float: right;
    }
  </style>
</head>
<body>
  <!-- Left Navigation Sidebar -->
  <div class="sidebar">
      <div class="logo">
          <i class="fas fa-heartbeat"></i>
          <span>Ministry of Health</span>
      </div>
      <a href="#" class="menu-item">
          <i class="fas fa-th-large"></i>
          <span>Dashboard</span>
      </a>
      <a href="child_profile.php" class="menu-item">
          <i class="fas fa-child"></i>
          <span>Child Profiles</span>
      </a>
      <a href="parent_profile.php" class="menu-item">
          <i class="fas fa-users"></i>
          <span>Parent Profiles</span>
      </a>
      <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
      <a href="add_admin.php" class="menu-item">
          <i class="fas fa-user-shield"></i>
          <span>Add Admin</span>
      </a>
      <?php endif; ?>
      <a href="logout.php" class="menu-item">
          <i class="fas fa-sign-out-alt"></i>
          <span>Sign Out</span>
      </a>
  </div>

  <!-- Main Content Area -->
  <div class="main-content">
      <div class="search-bar">
          <div class="search-container">
              <input type="text" id="searchInput" placeholder="Search by name or hospital...">
              <i class="fas fa-search search-icon"></i>
          </div>
          <!-- Instead of redirecting, open Add Child Modal -->
          <button class="add-child-btn" id="openAddChildModal">
              <i class="fas fa-plus"></i> Add Child
          </button>
      </div>

      <!-- Child Profiles Table -->
      <div class="child-profiles">
          <div class="header">
              <h2>Child Profiles</h2>
              <!-- Filter Icon to open Filter Modal -->
              <i class="fas fa-filter filter-icon" id="tableFilterIcon"></i>
          </div>
          <table>
              <thead>
                  <tr>
                      <th>Child ID</th>
                      <th>Name</th>
                      <th>Date of Birth</th>
                      <th>Registered Date</th>
                      <th>Health Medical Officer Division</th>
                      <th>Family Health Medical Officer Division</th>
                      <th>Mother's ID</th>
                      <th>Birth Hospital</th>
                      <th>Weight</th>
                      <th>Gender</th>
                      <th>Supplementary Regional Record Number</th>
                      <th>Grama Niladhari Record Number</th>
                      <th class="default-hidden" data-col="breastfeeding_within_1h">Breastfeeding Within 1 Hour</th>
                      <th class="default-hidden" data-col="congenital_hypothyroidism_check">Congenital Hypothyroidism Check</th>
                      <th class="default-hidden" data-col="hypothyroidism_test_results">Hypothyroidism Test Result</th>
                      <th class="default-hidden" data-col="reasons_to_preserve">Reasons To Preserve</th>
                      <th class="default-hidden" data-col="breastfeeding_only_2m">Only Breastfeeding At 2 Months</th>
                      <th class="default-hidden" data-col="breastfeeding_only_4m">Only Breastfeeding At 4 Months</th>
                      <th class="default-hidden" data-col="breastfeeding_only_6m">Only Breastfeeding At 6 Months</th>
                      <th class="default-hidden" data-col="start_feeding_other_foods">Started Feeding Other Foods</th>
                      <th class="default-hidden" data-col="age_started_feeding_other_foods">Age Started Feeding Other Foods</th>
                      <th class="default-hidden" data-col="age_stopped_breastfeeding">Age Stopped Breastfeeding</th>
                      <th class="default-hidden" data-col="other_foods_at_1_year">Other Foods At 1 Year</th>
                      <th class="default-hidden" data-col="started_feeding_other_foods_4m">Started Feeding Other Foods At 4 Months</th>
                      <th class="default-hidden" data-col="started_feeding_other_foods_6m">Started Feeding Other Foods At 6 Months</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody id="tableBody">
                  <?php foreach ($childData as $child): ?>
                  <tr data-id="<?php echo htmlspecialchars($child['child_id']); ?>">
                      <td><?php echo htmlspecialchars($child['child_id']); ?></td>
                      <td><?php echo htmlspecialchars($child['name']); ?></td>
                      <td><?php echo htmlspecialchars($child['birth_date']); ?></td>
                      <td><?php echo isset($child['registered_date']) ? htmlspecialchars($child['registered_date']) : '-'; ?></td>
                      <td><?php echo htmlspecialchars($child['health_medical_officer_division']); ?></td>
                      <td><?php echo htmlspecialchars($child['family_health_medical_officer_division']); ?></td>
                      <td><?php echo htmlspecialchars($child['parent_id']); ?></td>
                      <td><?php echo htmlspecialchars($child['birth_hospital']); ?></td>
                      <td><?php echo htmlspecialchars($child['weight']); ?></td>
                      <td><?php echo htmlspecialchars($child['sex']); ?></td>
                      <td><?php echo htmlspecialchars($child['supplementary_record_number'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($child['gn_record_number'] ?? ''); ?></td>
                      <td class="default-hidden" data-col="breastfeeding_within_1h"><?php echo ($child['breastfeeding_within_1h']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="congenital_hypothyroidism_check"><?php echo ($child['congenital_hypothyroidism_check']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="hypothyroidism_test_results"><?php echo htmlspecialchars($child['hypothyroidism_test_results']); ?></td>
                      <td class="default-hidden" data-col="reasons_to_preserve"><?php echo htmlspecialchars($child['reasons_to_preserve']); ?></td>
                      <td class="default-hidden" data-col="breastfeeding_only_2m"><?php echo ($child['breastfeeding_only_2m']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="breastfeeding_only_4m"><?php echo ($child['breastfeeding_only_4m']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="breastfeeding_only_6m"><?php echo ($child['breastfeeding_only_6m']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="start_feeding_other_foods"><?php echo ($child['start_feeding_other_foods']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="age_started_feeding_other_foods"><?php echo htmlspecialchars($child['age_started_feeding_other_foods']); ?></td>
                      <td class="default-hidden" data-col="age_stopped_breastfeeding"><?php echo htmlspecialchars($child['age_stopped_breastfeeding']); ?></td>
                      <td class="default-hidden" data-col="other_foods_at_1_year"><?php echo ($child['other_foods_at_1_year']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="started_feeding_other_foods_4m"><?php echo ($child['started_feeding_other_foods_4m']) ? 'Yes' : 'No'; ?></td>
                      <td class="default-hidden" data-col="started_feeding_other_foods_6m"><?php echo ($child['started_feeding_other_foods_6m']) ? 'Yes' : 'No'; ?></td>
                      <td class="action-icons">
                          <i class="fas fa-eye view-btn"></i>
                          <i class="fas fa-edit edit-btn"></i>
                          <i class="fas fa-trash delete-icon delete-btn" data-id="<?php echo htmlspecialchars($child['child_id']); ?>"></i>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
  </div>

  <!-- Single Modal Overlay for all modals -->
  <div class="modal-overlay" id="globalModalOverlay"></div>

  <!-- ===================== UPDATE MODAL ====================== -->
  <div class="modal" id="editModal">
      <h2>Update Child</h2>
      <form id="editForm">
          <input type="hidden" id="editChildId">
          <div class="form-group">
              <label>Child Name</label>
              <input type="text" class="form-control" id="editChildName" readonly>
          </div>
          <div class="form-group">
              <label>Health Medical Officer Division</label>
              <input type="text" class="form-control" id="editHealthDivision">
          </div>
          <div class="form-group">
              <label>Family Health Medical Officer Division</label>
              <input type="text" class="form-control" id="editFamilyHealthDivision">
          </div>
          <div class="form-group">
              <label>Supplementary Regional Record Number</label>
              <input type="text" class="form-control" id="editSupplementaryRecord">
          </div>
          <div class="form-group">
              <label>Grama Niladhari Record Number</label>
              <input type="text" class="form-control" id="editGnRecord">
          </div>
          <div class="form-group">
              <label>Is only Breastfeeding at 2 Months?</label>
              <div class="radio-group">
                  <label><input type="radio" name="breast2" id="editBreast2Yes" value="Yes"> Yes</label>
                  <label><input type="radio" name="breast2" id="editBreast2No" value="No"> No</label>
              </div>
          </div>
          <div class="form-group">
              <label>Is only Breastfeeding at 4 Months?</label>
              <div class="radio-group">
                  <label><input type="radio" name="breast4" id="editBreast4Yes" value="Yes"> Yes</label>
                  <label><input type="radio" name="breast4" id="editBreast4No" value="No"> No</label>
              </div>
          </div>
          <div class="button-group">
              <button type="button" class="btn btn-secondary" id="cancelEdit">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
      </form>
  </div>

  <!-- ===================== ADD CHILD MODAL ====================== -->
  <div class="modal" id="addChildModal">
      <h2 class="filter-title">ADD NEW CHILD</h2>
      <form id="addChildForm">
          <div class="form-grid">
              <div class="form-group">
                  <label>Name</label>
                  <input type="text" class="form-control" name="name" required>
              </div>
              <div class="form-group">
                  <label>Birth Date</label>
                  <input type="date" class="form-control" name="birth_date" required>
              </div>
              <div class="form-group">
                  <label>Registered Date</label>
                  <input type="date" class="form-control" name="registered_date" required>
              </div>
              <div class="form-group">
                  <label>Health Medical Officer Division</label>
                  <input type="text" class="form-control" name="health_medical_officer_division" required>
              </div>
              <div class="form-group">
                  <label>Weight (kg)</label>
                  <input type="number" step="0.1" class="form-control" name="weight" min="0.5" max="4.5" required>
              </div>
              <div class="form-group">
                  <label>Gender</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" id="addGenderMale" name="gender" value="Male" required>
                          <label for="addGenderMale">Male</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" id="addGenderFemale" name="gender" value="Female" required>
                          <label for="addGenderFemale">Female</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Family Health Medical Officer Division</label>
                  <input type="text" class="form-control" name="family_health_medical_officer_division" required>
              </div>
              <div class="form-group">
                  <label>Supplementary Regional Record Number</label>
                  <input type="text" class="form-control" name="supplementary_regional_record_number">
              </div>
              <div class="form-group">
                  <label>Grama Niladhari Record Number</label>
                  <input type="text" class="form-control" name="grama_niladhari_record_number">
              </div>
              <div class="form-group">
                  <label>Birth Hospital</label>
                  <input type="text" class="form-control" name="birth_hospital" required>
              </div>
              <div class="form-group">
                  <label>Mother's ID</label>
                  <input type="text" class="form-control" name="mother_id" required>
              </div>
              <div class="form-group">
                  <label>Did start breastfeeding within an hour after delivery?</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" id="addBreastfeedingYes" name="breastfeeding" value="Yes" required>
                          <label for="addBreastfeedingYes">Yes</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" id="addBreastfeedingNo" name="breastfeeding" value="No" required>
                          <label for="addBreastfeedingNo">No</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Tested for hypothyroidism?</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" id="addHypothyroidismYes" name="hypothyroidism" value="Yes" required>
                          <label for="addHypothyroidismYes">Yes</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" id="addHypothyroidismNo" name="hypothyroidism" value="No" required>
                          <label for="addHypothyroidismNo">No</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Hypothyroidism Test Result</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" id="addHypothyroidismPositive" name="hypothyroidism_result" value="Positive" required>
                          <label for="addHypothyroidismPositive">Positive</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" id="addHypothyroidismNegative" name="hypothyroidism_result" value="Negative" required>
                          <label for="addHypothyroidismNegative">Negative</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Reasons to Preserve</label>
                  <input type="text" class="form-control" name="reasons_to_preserve">
              </div>
          </div>
          <div class="button-group">
              <button type="button" class="btn btn-cancel" id="cancelAddChild">Cancel</button>
              <button type="submit" class="btn btn-apply">Save</button>
          </div>
      </form>
  </div>
  <!-- End Add Child Modal -->

  <!-- Filter Modal -->
  <div class="modal" id="filterModal">
      <h2 class="filter-title">FILTER CHILD DETAILS</h2>
      <form id="filterForm">
          <div class="form-grid">
              <div class="form-group">
                  <label>Child ID</label>
                  <input type="text" class="form-control" name="child_id">
              </div>
              <div class="form-group">
                  <label>Name</label>
                  <input type="text" class="form-control" name="name">
              </div>
              <div class="form-group">
                  <label>Date of Birth</label>
                  <input type="date" class="form-control" name="birth_date">
              </div>
              <div class="form-group">
                  <label>Weight</label>
                  <input type="text" class="form-control" name="weight">
              </div>
              <div class="form-group">
                  <label>Gender</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" name="gender" id="filterMale" value="Male">
                          <label for="filterMale">Male</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" name="gender" id="filterFemale" value="Female">
                          <label for="filterFemale">Female</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Birth Hospital</label>
                  <input type="text" class="form-control" name="birth_hospital">
              </div>
              <div class="form-group">
                  <label>Health Medical Officer Division</label>
                  <input type="text" class="form-control" name="health_division">
              </div>
              <div class="form-group">
                  <label>Family Health Medical Officer Division</label>
                  <input type="text" class="form-control" name="family_health_division">
              </div>
              <div class="form-group">
                  <label>Mother's ID</label>
                  <input type="text" class="form-control" name="parent_id">
              </div>
              <div class="form-group">
                  <label>Did start breastfeeding within an hour after delivery?</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" name="breastfeeding" id="filterBreastYes" value="Yes">
                          <label for="filterBreastYes">Yes</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" name="breastfeeding" id="filterBreastNo" value="No">
                          <label for="filterBreastNo">No</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Tested for hypothyroidism?</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" name="tested" id="filterTestedYes" value="Yes">
                          <label for="filterTestedYes">Yes</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" name="tested" id="filterTestedNo" value="No">
                          <label for="filterTestedNo">No</label>
                      </div>
                  </div>
              </div>
              <div class="form-group">
                  <label>Hypothyroidism Test Result</label>
                  <div class="radio-group">
                      <div class="radio-option">
                          <input type="radio" name="result" id="filterResultPositive" value="Positive">
                          <label for="filterResultPositive">Positive</label>
                      </div>
                      <div class="radio-option">
                          <input type="radio" name="result" id="filterResultNegative" value="Negative">
                          <label for="filterResultNegative">Negative</label>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Hide Columns Section -->
          <div class="hide-columns">
              <h3 class="filter-title">HIDE COLUMNS</h3>
              <div class="column-list">
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_breastfeeding_within_1h" value="breastfeeding_within_1h" checked>
                      <span>Breastfeeding Within 1 Hour</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_congenital_hypothyroidism_check" value="congenital_hypothyroidism_check" checked>
                      <span>Congenital Hypothyroidism Check</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_hypothyroidism_test_results" value="hypothyroidism_test_results" checked>
                      <span>Hypothyroidism Test Result</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_reasons_to_preserve" value="reasons_to_preserve" checked>
                      <span>Reasons To Preserve</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_breastfeeding_only_2m" value="breastfeeding_only_2m" checked>
                      <span>Only Breastfeeding At 2 Months</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_breastfeeding_only_4m" value="breastfeeding_only_4m" checked>
                      <span>Only Breastfeeding At 4 Months</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_breastfeeding_only_6m" value="breastfeeding_only_6m" checked>
                      <span>Only Breastfeeding At 6 Months</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_start_feeding_other_foods" value="start_feeding_other_foods" checked>
                      <span>Started Feeding Other Foods</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_age_started_feeding_other_foods" value="age_started_feeding_other_foods" checked>
                      <span>Age Started Feeding Other Foods</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_age_stopped_breastfeeding" value="age_stopped_breastfeeding" checked>
                      <span>Age Stopped Breastfeeding</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_other_foods_at_1_year" value="other_foods_at_1_year" checked>
                      <span>Other Foods At 1 Year</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_started_feeding_other_foods_4m" value="started_feeding_other_foods_4m" checked>
                      <span>Started Feeding Other Foods At 4 Months</span>
                  </div>
                  <div class="column-item">
                      <input type="checkbox" class="checkbox hide-col" name="hide_started_feeding_other_foods_6m" value="started_feeding_other_foods_6m" checked>
                      <span>Started Feeding Other Foods At 6 Months</span>
                  </div>
              </div>
          </div>
          <div class="button-group">
              <button type="button" class="btn btn-cancel" id="cancelFilter">Cancel</button>
              <button type="submit" class="btn btn-apply">Apply</button>
          </div>
      </form>
  </div>
  <!-- End Filter Modal -->

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteConfirmModal">
      <h2>Confirm Delete</h2>
      <p>Are you sure you want to delete this record?</p>
      <div class="button-group">
           <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
           <button type="button" class="btn btn-primary" id="confirmDelete">Delete</button>
      </div>
  </div>

  <!-- VIEW CHILD DETAILS MODAL -->
  <div class="modal view-child-modal" id="viewChildModal">
      <div class="card">
          <h2>Child Details</h2>
          <div><strong>Child ID:</strong> <span id="viewChildId"></span></div>
          <div class="child-details">
              <div class="detail-row">
                  <span>Name</span>
                  <span id="viewChildName"></span>
              </div>
              <div class="detail-row">
                  <span>Date of Birth</span>
                  <span id="viewBirthDate"></span>
              </div>
              <div class="detail-row">
                  <span>Registered Date</span>
                  <span id="viewRegisteredDate"></span>
              </div>
              <div class="detail-row">
                  <span>Health Medical Officer Division</span>
                  <span id="viewHealthDivision"></span>
              </div>
              <div class="detail-row">
                  <span>Family Health Medical Officer Division</span>
                  <span id="viewFamilyHealthDivision"></span>
              </div>
              <div class="detail-row">
                  <span>Mother's ID</span>
                  <span id="viewParentId"></span>
              </div>
              <div class="detail-row">
                  <span>Birth Hospital</span>
                  <span id="viewBirthHospital"></span>
              </div>
              <div class="detail-row">
                  <span>Birth Weight</span>
                  <span id="viewWeight"></span>
              </div>
              <div class="detail-row">
                  <span>Gender</span>
                  <span id="viewGender"></span>
              </div>
              <div class="detail-row">
                  <span>Supplementary Regional Record Number</span>
                  <span id="viewSupplementary"></span>
              </div>
              <div class="detail-row">
                  <span>Grama Niladhari Record Number</span>
                  <span id="viewGnRecord"></span>
              </div>
          </div>
          <div class="section-title">
              More Information <i class="fas fa-chevron-down"></i>
          </div>
          <div class="section-content">
              <div class="detail-row">
                  <span>Did start breastfeeding within an hour after delivery?</span>
                  <span id="viewBreastfeeding"></span>
              </div>
              <div class="detail-row">
                  <span>Tested for hypothyroidism?</span>
                  <span id="viewHypothyroidism"></span>
              </div>
              <div class="detail-row">
                  <span>Hypothyroidism Test Result</span>
                  <span id="viewHypothyroidismResult"></span>
              </div>
              <div class="detail-row">
                  <span>Reasons to Preserve</span>
                  <span id="viewReasons"></span>
              </div>
              <div class="detail-row">
                  <span>Is only Breastfeeding at 2 months?</span>
                  <span id="viewBreastfeeding2m"></span>
              </div>
              <div class="detail-row">
                  <span>Is only Breastfeeding at 4 months?</span>
                  <span id="viewBreastfeeding4m"></span>
              </div>
              <div class="detail-row">
                  <span>Is only Breastfeeding at 6 months?</span>
                  <span id="viewBreastfeeding6m"></span>
              </div>
              <div class="detail-row">
                  <span>Starting complementary foods at 4 months?</span>
                  <span id="viewStartFeeding4m"></span>
              </div>
              <div class="detail-row">
                  <span>Starting complementary foods at 6 months?</span>
                  <span id="viewStartFeeding6m"></span>
              </div>
              <div class="detail-row">
                  <span>Age at first initiation of complementary feeding?</span>
                  <span id="viewAgeStartedFeeding"></span>
              </div>
              <div class="detail-row">
                  <span>Age when breastfeeding is completely stopped?</span>
                  <span id="viewAgeStoppedFeeding"></span>
              </div>
              <div class="detail-row">
                  <span>Does the child eat normal family meals by the first year?</span>
                  <span id="viewOtherFoods"></span>
              </div>
          </div>
          <div class="section-title">
              Health Chart <i class="fas fa-chevron-down"></i>
          </div>
          <div class="section-content">
              <table class="health-chart">
                  <tr>
                      <th></th>
                      <th>1 - 5 Days</th>
                      <th>6 - 10 Days</th>
                      <th>14 - 21 Days</th>
                      <th>Within 42 Days</th>
                  </tr>
                  <tr>
                      <td>Date</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Skin Color</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Eyes</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>The characteristics of the navel</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Temperature</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Exclusive Breastfeeding</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Breastfeeding attachment and relationship</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Stool Color</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
                  <tr>
                      <td>Identified Complications</td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                  </tr>
              </table>
          </div>
          <button class="close-btn" id="closeViewChild">Close</button>
      </div>
  </div>
  <!-- End View Child Modal -->

  <!-- Alert Box -->
  <div class="alert" id="alertBox"></div>

  <!-- jQuery and Script -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){

      // --- Toggle Filter Modal ---
      $("#tableFilterIcon").click(function(){
          $("#globalModalOverlay, #filterModal").fadeIn();
      });
      $("#cancelFilter").click(function(){
          $("#globalModalOverlay, #filterModal").fadeOut();
      });
      
      // --- Toggle Add Child Modal ---
      $("#openAddChildModal").click(function(){
          $("#globalModalOverlay, #addChildModal").fadeIn();
      });
      $("#cancelAddChild").click(function(){
          $("#globalModalOverlay, #addChildModal").fadeOut();
      });
      
      // --- Filter Form Submission ---
      $("#filterForm").submit(function(e){
          e.preventDefault();
          var filterData = $(this).serialize();
          $.ajax({
              url: window.location.pathname,
              method: "GET",
              data: filterData + "&ajax=true",
              dataType: "json",
              success: function(data){
                  updateTable(data);
                  $("#globalModalOverlay, #filterModal").fadeOut();
              },
              error: function(xhr, status, error){
                  console.error("Error applying filters:", error);
              }
          });
      });
      
      // --- Reapply Hide Columns after table update ---
      function reapplyHideColumns(){
          $(".hide-col").each(function(){
              var colKey = $(this).val();
              if($(this).is(":checked")){
                  $("th[data-col='"+colKey+"'], td[data-col='"+colKey+"']").addClass("default-hidden");
              } else {
                  $("th[data-col='"+colKey+"'], td[data-col='"+colKey+"']").removeClass("default-hidden");
              }
          });
      }
      
      // --- AJAX Search Functionality ---
      let ajaxRequest = null;
      let searchTimeout = null;
      $("#searchInput").on("input", function(){
          let query = $(this).val().trim();
          clearTimeout(searchTimeout);
          if(ajaxRequest){
              ajaxRequest.abort();
          }
          searchTimeout = setTimeout(function(){
              ajaxRequest = $.ajax({
                  url: window.location.pathname,
                  method: "GET",
                  data: { search: query, ajax: "true" },
                  dataType: "json",
                  success: function(data){
                      updateTable(data);
                  },
                  error: function(xhr, status, error){
                      if(status !== "abort"){
                          console.error("Error fetching search results:", error);
                      }
                  }
              });
          }, 300);
      });
      
      function updateTable(data) {
        let tableBody = $("#tableBody");
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append('<tr><td colspan="26">No records found</td></tr>');
            return;
        }

        data.forEach(function(child) {
            let row = `
                <tr data-id="${child.child_id}">
                    <td>${child.child_id}</td>
                    <td>${child.name}</td>
                    <td>${child.birth_date}</td>
                    <td>${child.registered_date ? child.registered_date : '-'}</td>
                    <td>${child.health_medical_officer_division}</td>
                    <td>${child.family_health_medical_officer_division}</td>
                    <td>${child.parent_id}</td>
                    <td>${child.birth_hospital}</td>
                    <td>${child.weight}</td>
                    <td>${child.sex}</td>
                    <td>${child.supplementary_record_number || ''}</td>
                    <td>${child.gn_record_number || ''}</td>
                    <td class="default-hidden" data-col="breastfeeding_within_1h">${child.breastfeeding_within_1h ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="congenital_hypothyroidism_check">${child.congenital_hypothyroidism_check ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="hypothyroidism_test_results">${child.hypothyroidism_test_results || ''}</td>
                    <td class="default-hidden" data-col="reasons_to_preserve">${child.reasons_to_preserve || ''}</td>
                    <td class="default-hidden" data-col="breastfeeding_only_2m">${child.breastfeeding_only_2m ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="breastfeeding_only_4m">${child.breastfeeding_only_4m ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="breastfeeding_only_6m">${child.breastfeeding_only_6m ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="start_feeding_other_foods">${child.start_feeding_other_foods ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="age_started_feeding_other_foods">${child.age_started_feeding_other_foods || ''}</td>
                    <td class="default-hidden" data-col="age_stopped_breastfeeding">${child.age_stopped_breastfeeding || ''}</td>
                    <td class="default-hidden" data-col="other_foods_at_1_year">${child.other_foods_at_1_year ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="started_feeding_other_foods_4m">${child.started_feeding_other_foods_4m ? 'Yes' : 'No'}</td>
                    <td class="default-hidden" data-col="started_feeding_other_foods_6m">${child.started_feeding_other_foods_6m ? 'Yes' : 'No'}</td>
                    <td class="action-icons">
                        <i class="fas fa-eye view-btn"></i>
                        <i class="fas fa-edit edit-btn"></i>
                        <i class="fas fa-trash delete-icon delete-btn" data-id="${child.child_id}"></i>
                    </td>
                </tr>
            `;
            tableBody.append(row);
        });
        reapplyHideColumns();
      }
      
      // --- Delete Confirmation Modal ---
      $(document).on("click", ".delete-btn", function(){
          let childId = $(this).data("id");
          $("#deleteConfirmModal").data("childId", childId);
          $("#globalModalOverlay, #deleteConfirmModal").fadeIn();
      });
      
      $("#cancelDelete").click(function(){
          $("#globalModalOverlay, #deleteConfirmModal").fadeOut();
      });
      
      $("#confirmDelete").click(function(){
          let childId = $("#deleteConfirmModal").data("childId");
          $.post("", { delete_id: childId }, function(response){
              let res = (typeof response === "string") ? JSON.parse(response) : response;
              if(res.status === "success"){
                  showAlert("success", "Record deleted successfully!");
                  setTimeout(function(){ location.reload(); }, 1500);
              } else {
                  showAlert("error", "Error deleting record: " + res.message);
              }
              $("#globalModalOverlay, #deleteConfirmModal").fadeOut();
          }, "json");
      });
      
      // =============== OPEN EDIT MODAL & POPULATE FIELDS ===============
      $(document).on("click", ".edit-btn", function(){
          let row = $(this).closest("tr");
          let childId              = row.data("id");
          let childName            = row.children().eq(1).text().trim();
          let healthDivision       = row.children().eq(4).text().trim();
          let familyHealthDivision = row.children().eq(5).text().trim();
          let supplementaryRecord  = row.children().eq(10).text().trim();
          let gnRecord             = row.children().eq(11).text().trim();

          $("#editChildId").val(childId);
          $("#editChildName").val(childName);
          $("#editHealthDivision").val(healthDivision);
          $("#editFamilyHealthDivision").val(familyHealthDivision);
          $("#editSupplementaryRecord").val(supplementaryRecord);
          $("#editGnRecord").val(gnRecord);

          // Populate radio buttons for breastfeeding only values
          let breast2Val = row.find('td[data-col="breastfeeding_only_2m"]').text().trim();
          if(breast2Val === 'Yes') {
              $("#editBreast2Yes").prop("checked", true);
          } else {
              $("#editBreast2No").prop("checked", true);
          }
          let breast4Val = row.find('td[data-col="breastfeeding_only_4m"]').text().trim();
          if(breast4Val === 'Yes') {
              $("#editBreast4Yes").prop("checked", true);
          } else {
              $("#editBreast4No").prop("checked", true);
          }
          $("#globalModalOverlay, #editModal").fadeIn();
      });
      
      // --- CANCEL EDIT MODAL ---
      $("#cancelEdit").click(function(){
          $("#globalModalOverlay, #editModal").fadeOut();
      });
      
      // --- SUBMIT EDIT FORM ---
      $("#editForm").submit(function(e){
          e.preventDefault();
          let childId             = $("#editChildId").val();
          let healthDivision      = $("#editHealthDivision").val();
          let familyHealthDiv     = $("#editFamilyHealthDivision").val();
          let supplementaryRecord = $("#editSupplementaryRecord").val();
          let gnRecord            = $("#editGnRecord").val();
          let breast2             = $("input[name='breast2']:checked").val();
          let breast4             = $("input[name='breast4']:checked").val();
          
          $.post("", {
              child_id: childId,
              health_division: healthDivision,
              family_health_division: familyHealthDiv,
              supplementary_record: supplementaryRecord,
              gn_record: gnRecord,
              breast2: breast2,
              breast4: breast4
          }, function(response){
              let res = (typeof response === "string") ? JSON.parse(response) : response;
              if(res.status === "success"){
                  alert("Child record updated successfully!");
                  setTimeout(function(){ location.reload(); }, 1500);
              } else {
                  alert("Error updating record: " + res.message);
              }
          }, "json");
          $("#globalModalOverlay, #editModal").fadeOut();
      });
      
      // --- ADD CHILD FORM SUBMISSION ---
      $("#addChildForm").submit(function(e){
          e.preventDefault();
          var formData = $(this).serialize();
          $.ajax({
              url: window.location.pathname,
              method: "POST",
              data: formData,
              dataType: "json",
              success: function(response){
                  if(response.status === "success"){
                      showAlert("success", response.message);
                      setTimeout(function(){ location.reload(); }, 1500);
                  } else {
                      showAlert("error", response.message);
                  }
                  $("#globalModalOverlay, #addChildModal").fadeOut();
              },
              error: function(xhr, status, error){
                  console.error("Error adding child:", error);
              }
          });
      });
      
      // --- VIEW CHILD MODAL LOGIC ---
      $(document).on("click", ".view-btn", function(){
          let row = $(this).closest("tr");
          $("#viewChildId").text(row.children().eq(0).text().trim());
          $("#viewChildName").text(row.children().eq(1).text().trim());
          $("#viewBirthDate").text(row.children().eq(2).text().trim());
          $("#viewRegisteredDate").text(row.children().eq(3).text().trim());
          $("#viewHealthDivision").text(row.children().eq(4).text().trim());
          $("#viewFamilyHealthDivision").text(row.children().eq(5).text().trim());
          $("#viewParentId").text(row.children().eq(6).text().trim());
          $("#viewBirthHospital").text(row.children().eq(7).text().trim());
          $("#viewWeight").text(row.children().eq(8).text().trim());
          $("#viewGender").text(row.children().eq(9).text().trim());
          $("#viewSupplementary").text(row.children().eq(10).text().trim());
          $("#viewGnRecord").text(row.children().eq(11).text().trim());
          
          $("#viewBreastfeeding").text(row.find('td[data-col="breastfeeding_within_1h"]').text().trim());
          $("#viewHypothyroidism").text(row.find('td[data-col="congenital_hypothyroidism_check"]').text().trim());
          $("#viewHypothyroidismResult").text(row.find('td[data-col="hypothyroidism_test_results"]').text().trim());
          $("#viewReasons").text(row.find('td[data-col="reasons_to_preserve"]').text().trim());
          $("#viewBreastfeeding2m").text(row.find('td[data-col="breastfeeding_only_2m"]').text().trim());
          $("#viewBreastfeeding4m").text(row.find('td[data-col="breastfeeding_only_4m"]').text().trim());
          $("#viewBreastfeeding6m").text(row.find('td[data-col="breastfeeding_only_6m"]').text().trim());
          $("#viewStartFeeding4m").text(row.find('td[data-col="start_feeding_other_foods"]').text().trim());
          $("#viewStartFeeding6m").text(row.find('td[data-col="age_started_feeding_other_foods"]').text().trim());
          $("#viewAgeStartedFeeding").text(row.find('td[data-col="age_started_feeding_other_foods"]').text().trim());
          $("#viewAgeStoppedFeeding").text(row.find('td[data-col="age_stopped_breastfeeding"]').text().trim());
          $("#viewOtherFoods").text(row.find('td[data-col="other_foods_at_1_year"]').text().trim());
          
          $("#globalModalOverlay, #viewChildModal").fadeIn();
      });
      
      // --- Toggle Collapse for Sections in View Modal ---
      $(".view-child-modal .section-title").click(function(){
          $(this).next(".section-content").slideToggle();
          $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
      });
      
      // --- Close View Modal ---
      $("#closeViewChild, #viewChildModalOverlay").click(function(){
          $("#globalModalOverlay, #viewChildModal").fadeOut();
      });
      
      // --- Alert Function ---
      function showAlert(type, message){
          let alertBox = $("#alertBox");
          alertBox.removeClass("alert-success alert-error");
          if(type === "success"){
              alertBox.addClass("alert-success");
          } else {
              alertBox.addClass("alert-error");
          }
          alertBox.text(message).fadeIn();
          setTimeout(function(){ alertBox.fadeOut(); }, 3000);
      }
    });
  </script>
</body>
</html>
