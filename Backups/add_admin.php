<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'SuperAdmin') {
    header('Location: signin.php');
    exit;
}

$dsn         = "pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";
$db_user     = "neondb_owner";
$db_password = "npg_39CVAQbvIlOy";

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Delete Admin (only if role is 'Admin')
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id']; // Using column "id"
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'Admin'");
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Admin not found or already deleted."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// ADD Admin (only add; update is not allowed)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password']) && !isset($_POST['user_id'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    if (empty($full_name) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "All fields are required!"]);
        exit;
    }
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, 'Admin')");
        $stmt->execute([
            ':full_name' => $full_name,
            ':email'    => $email,
            ':password' => $hashed_password,
        ]);
        echo json_encode(["status" => "success", "message" => "Admin added successfully!"]);
    } catch(PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error adding admin: " . $e->getMessage()]);
    }
    exit;
}

// AJAX Search Request for Admin
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $query = "SELECT * FROM users WHERE role = 'Admin'";
    $params = [];
    if (!empty($search)) {
        $query .= " AND (full_name ILIKE :search OR email ILIKE :search)";
        $params[':search'] = "%$search%";
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $adminData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($adminData);
    exit;
}

// Fetch Admin records (role = 'Admin')
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'Admin'");
    $adminData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching admin records: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KidsGrow - Admin Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #274FB4;
      --secondary-color: #8FC4F1;
      --text-dark: #333;
      --text-light: #666;
      --white: #fff;
      --border-radius: 8px;
      --shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background-color: #f0f0f0; display: flex; }
    /* Sidebar (Child Profile Design with updated content) */
    .sidebar {
      width: 200px;
      background: linear-gradient(180deg, #4a90e2, #357abd);
      min-height: 100vh;
      padding: 20px;
      color: #fff;
    }
    .sidebar .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 40px;
    }
    .sidebar .menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 0;
      color: #fff;
      text-decoration: none;
      cursor: pointer;
    }
    .sidebar .menu-item:hover {
      background-color: rgba(255,255,255,0.2);
      padding-left: 10px;
      border-radius: var(--border-radius);
    }
    /* Main Content (Child Profile Design) */
    .main-content {
      flex: 1;
      padding: 20px;
      margin-left: 20px;
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
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
    }
    /* Table Container */
    .child-profiles {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    table caption {
      caption-side: top;
      text-align: left;
      font-size: 1.8rem;
      color: var(--text-dark);
      margin-bottom: 10px;
      font-weight: bold;
    }
    th, td {
      text-align: left;
      padding: 10px;
      font-size: 14px;
      border-bottom: 1px solid #ddd;
    }
    th { color: #666; font-weight: normal; }
    .action-icons, .actions {
      display: flex;
      gap: 10px;
    }
    .action-icons i, .actions i {
      color: #4a90e2;
      cursor: pointer;
      font-size: 18px;
      transition: color 0.3s;
    }
    .action-icons i:hover { color: #1f3a8a; }
    .delete-icon { color: #ff4444 !important; }
    /* Modal Styles */
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
    /* Old Modal Form Styles (Restored) */
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      font-size: 14px;
      color: var(--text-dark);
    }
    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }
    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }
    .btn {
      padding: 8px 20px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      font-size: 14px;
    }
    .btn-primary {
      background: var(--primary-color);
      color: var(--white);
    }
    .btn-secondary {
      background: var(--white);
      border: 1px solid var(--primary-color);
      color: var(--primary-color);
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
      z-index: 1100;
      display: none;
      animation: slideIn 0.3s ease-out;
    }
    .alert-success { background: #28a745; }
    .alert-error { background: #dc3545; }
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
      <div class="logo">
          <i class="fas fa-heartbeat"></i>
          <span>Ministry of Health</span>
      </div>
      <a href="dashboard.php" class="menu-item"><i class="fas fa-th-large"></i> Dashboard</a>
      <a href="child_profile.php" class="menu-item"><i class="fas fa-child"></i> Child Profiles</a>
      <a href="parent_profile.php" class="menu-item"><i class="fas fa-users"></i> Parent Profiles</a>
      <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
      <a href="add_admin.php" class="menu-item"><i class="fas fa-user-shield"></i> Add Admin</a>
      <?php endif; ?>
      <a href="logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>

  <!-- Main Content Area -->
  <div class="main-content">
      <div class="search-bar">
          <div class="search-container">
              <input type="text" id="searchInput" placeholder="Search by name or NIC...">
              <i class="fas fa-search search-icon"></i>
          </div>
          <!-- Add Admin Button -->
          <button class="add-child-btn" id="openAddAdminBtn"><i class="fas fa-plus"></i> Add Admin</button>
      </div>
      <!-- Table Container -->
      <div class="child-profiles">
          <table>
              <caption>Admin Management</caption>
              <thead>
                  <tr>
                      <th>Email</th>
                      <th>Name</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody id="tableBody">
                  <?php if (!empty($adminData) && is_array($adminData)): ?>
                      <?php foreach ($adminData as $admin): ?>
                          <?php 
                              $admin_id = isset($admin['id']) ? htmlspecialchars($admin['id']) : '';
                              $admin_email = isset($admin['email']) ? htmlspecialchars($admin['email']) : 'N/A';
                              $admin_name = isset($admin['full_name']) ? htmlspecialchars($admin['full_name']) : 'N/A';
                          ?>
                          <tr data-id="<?= $admin_id; ?>">
                              <td><?= $admin_email; ?></td>
                              <td><?= $admin_name; ?></td>
                              <td class="action-icons">
                                  <?php if (!empty($admin_id)): ?>
                                      <i class="fas fa-trash delete-icon delete-btn" data-id="<?= $admin_id; ?>"></i>
                                  <?php else: ?>
                                      <span style="color: gray;">No ID</span>
                                  <?php endif; ?>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <tr>
                          <td colspan="3" style="text-align: center; color: gray;">No admins found.</td>
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>
  </div>

  <!-- Add Admin Modal -->
  <div class="modal-overlay" id="addAdminModalOverlay"></div>
  <div class="modal" id="addAdminModal">
      <h2>Add New Admin</h2>
      <form id="addAdminForm">
          <div class="form-group">
              <label for="full_name">Full Name</label>
              <input type="text" id="full_name" name="full_name" required>
          </div>
          <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required>
          </div>
          <div class="button-group">
              <button type="button" class="btn btn-secondary" id="cancelAddAdmin">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Admin</button>
          </div>
      </form>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal-overlay" id="deleteConfirmModalOverlay"></div>
  <div class="modal" id="deleteConfirmModal">
      <h2>Confirm Delete</h2>
      <p>Are you sure you want to delete this admin?</p>
      <div class="button-group">
          <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmDelete">Delete</button>
      </div>
  </div>

  <!-- Alert Box -->
  <div class="alert" id="alertBox"></div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function(){
        // Open Add Admin Modal
        $("#openAddAdminBtn").click(function(){
            $("#addAdminModalOverlay, #addAdminModal").fadeIn();
        });
        $("#cancelAddAdmin").click(function(){
            $("#addAdminModalOverlay, #addAdminModal").fadeOut();
        });
        // Add Admin Form Submission
        $("#addAdminForm").submit(function(e){
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
                    $("#addAdminModalOverlay, #addAdminModal").fadeOut();
                },
                error: function(xhr, status, error){
                    console.error("Error adding admin:", error);
                }
            });
        });
        // Delete Admin
        $(document).on("click", ".delete-btn", function(){
            var adminId = $(this).data("id");
            $("#deleteConfirmModal").data("adminId", adminId);
            $("#deleteConfirmModalOverlay, #deleteConfirmModal").fadeIn();
        });
        $("#cancelDelete").click(function(){
            $("#deleteConfirmModalOverlay, #deleteConfirmModal").fadeOut();
        });
        $("#confirmDelete").click(function(){
            var adminId = $("#deleteConfirmModal").data("adminId");
            $.ajax({
                url: window.location.pathname,
                method: "POST",
                data: { delete_id: adminId },
                dataType: "json",
                success: function(response){
                    if(response.status === "success"){
                        showAlert("success", "Admin deleted successfully!");
                        setTimeout(function(){ location.reload(); }, 1500);
                    } else {
                        showAlert("error", response.message || "Error deleting admin.");
                    }
                },
                error: function(xhr, status, error){
                    console.error("Delete error:", error);
                },
                complete: function(){
                    $("#deleteConfirmModalOverlay, #deleteConfirmModal").fadeOut();
                }
            });
        });
        // Search Functionality
        let ajaxRequest = null, searchTimeout = null;
        $("#searchInput").on("input", function(){
            let query = $(this).val().trim();
            clearTimeout(searchTimeout);
            if(ajaxRequest){ ajaxRequest.abort(); }
            searchTimeout = setTimeout(() => {
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
        function updateTable(data){
            let tableBody = $("#tableBody");
            tableBody.empty();
            if(data.length === 0){
                tableBody.append('<tr><td colspan="3">No records found</td></tr>');
                return;
            }
            data.forEach(function(admin){
                let row = `<tr data-id="${admin.id}">
                    <td>${admin.email}</td>
                    <td>${admin.full_name}</td>
                    <td class="action-icons">
                        <i class="fas fa-trash delete-icon delete-btn" data-id="${admin.id}"></i>
                    </td>
                </tr>`;
                $("#tableBody").append(row);
            });
        }
        // Alert Function
        function showAlert(type, message){
            var alertBox = $("#alertBox");
            alertBox.removeClass("alert-success alert-error")
                    .addClass(type === "success" ? "alert-success" : "alert-error")
                    .text(message)
                    .fadeIn();
            setTimeout(function(){ alertBox.fadeOut(); }, 3000);
        }
    });
  </script>
</body>
</html>
