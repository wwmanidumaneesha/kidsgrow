<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.php');
    exit;
}

$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Sarah Smith';
$userRole = $_SESSION['user_role'];

// Database connection
$dsn         = "pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";
$db_user     = "neondb_owner";
$db_password = "npg_39CVAQbvIlOy";

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Common Queries for Both Dashboards
// 1) Count total children
$sqlChildren = "SELECT COUNT(*) FROM child";
$childrenCount = (int)$pdo->query($sqlChildren)->fetchColumn();

// 2) Count total parents
$sqlParents = "SELECT COUNT(*) FROM parent";
$parentsCount = (int)$pdo->query($sqlParents)->fetchColumn();

// 3) Count Admins (including SuperAdmin if desired)
$sqlAdmins = "SELECT COUNT(*) FROM users WHERE role IN ('Admin','SuperAdmin')";
$adminCount = (int)$pdo->query($sqlAdmins)->fetchColumn();

// 4) Count normal users (role = 'Parent')
$sqlUsers = "SELECT COUNT(*) FROM users WHERE role = 'Parent'";
$userCount = (int)$pdo->query($sqlUsers)->fetchColumn();

// 5) Count upcoming home visits (where visited = FALSE)
$sqlUpcomingVisits = "SELECT COUNT(*) FROM home_visit WHERE visited = FALSE";
$upcomingHomeVisitCount = (int)$pdo->query($sqlUpcomingVisits)->fetchColumn();

// Default placeholder image
$defaultImage = "https://placehold.co/150x150";

// Retrieve profile image from database
$stmt = $pdo->prepare("SELECT profile_image_url FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user has an uploaded profile image
$profileImage = !empty($result['profile_image_url']) ? $result['profile_image_url'] : $defaultImage;

// Apply ImgBB Parameters for Optimization (Resize, Quality & Sharpening)
$optimizedImage = $profileImage . "?w=90&h=90&quality=95&sharpness=2";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>KidsGrow - Dashboard</title>

  <!-- Google Fonts (Poppins) -->
  <link rel="preconnect" href="https://fonts.gstatic.com" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />

  <!-- Combined Styles (Admin/SuperAdmin and Doctor/Midwife layouts) -->
  <style>
    :root {
      --primary-color: #274FB4;
      --secondary-color: #8FC4F1;
      --text-dark: #333;
      --text-light: #666;
      --white: #fff;
      --border-radius: 8px;
      --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      --gray-bg: #f0f0f0;
    }
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background-color: var(--gray-bg);
      margin: 0;
    }
    /* SIDEBAR */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      width: 220px;
      background: var(--primary-color);
      color: var(--white);
      padding: 20px;
      overflow-y: auto;
      z-index: 999;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 40px;
    }
    .logo span {
      font-size: 24px;
      font-weight: 800;
    }
    .menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 0;
      cursor: pointer;
      color: var(--white);
      text-decoration: none;
      font-size: 16px;
      font-weight: 700;
    }
    .menu-item:hover {
      background-color: rgba(255, 255, 255, 0.2);
      padding-left: 10px;
      border-radius: var(--border-radius);
    }
    .menu-item.active {
      background-color: rgba(255, 255, 255, 0.2);
      padding-left: 10px;
      border-radius: var(--border-radius);
    }
    /* USER PROFILE */
    .sidebar-user-profile {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 40px;
      padding: 10px;
      background: rgba(255,255,255,0.2);
      border-radius: var(--border-radius);
      cursor: pointer;
      position: relative;
    }
    .sidebar-user-profile img {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
    }
    .sidebar-user-info {
      display: flex;
      flex-direction: column;
      font-size: 14px;
      color: var(--white);
      line-height: 1.2;
    }
    .sidebar-user-name {
      font-weight: 700;
      font-size: 16px;
    }
    .sidebar-user-role {
      font-weight: 400;
      font-size: 14px;
    }
    .sidebar-user-menu {
      display: none;
      position: absolute;
      bottom: 60px;
      left: 0;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 5px;
      min-width: 120px;
      box-shadow: var(--shadow);
      padding: 5px 0;
      color: #333;
      z-index: 1000;
    }
    .sidebar-user-menu a {
      display: block;
      padding: 8px 12px;
      text-decoration: none;
      color: #333;
      font-size: 14px;
    }
    .sidebar-user-menu a:hover {
      background-color: #f0f0f0;
    }
    /* MAIN CONTENT */
    .main-content {
      margin-left: 220px;
      height: 100vh;
      overflow-y: auto;
      padding: 20px;
      position: relative;
    }
    .dashboard-container {
      background: #FFFCFC;
      border-radius: 20px;
      padding: 20px;
      min-height: calc(100vh - 40px);
      position: relative;
    }
    .dashboard-title {
      font-size: 32px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 20px;
    }
    /* TOP ROW (Common for both layouts) */
    .top-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }
    .info-card {
      flex: 1;
      min-width: 240px;
      background: #fff;
      border-radius: 20px;
      border: 1px solid rgba(39,79,180,0.98);
      position: relative;
      padding: 15px;
      color: rgba(39,79,180,0.98);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .info-card img {
      width: 80px;
      height: auto;
      object-fit: contain;
      margin-bottom: 10px;
    }
    .info-card h2 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .info-card .info-value {
      font-size: 40px;
      font-weight: 700;
      color: #000;
      margin-bottom: 16px;
    }
    .info-card .view-more-btn {
      background: var(--primary-color);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 8px 16px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
    }
    .info-card .view-more-btn:hover {
      opacity: 0.9;
    }
    /* Additional styles for Admin/SuperAdmin layout */
    .upcoming-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 20px;
    }
    .thriposha-card {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      padding: 20px;
    }
    .thriposha-card h2 {
      margin-bottom: 10px;
      font-size: 20px;
    }
    .thriposha-card .distribution-row {
      width: 100%;
      display: flex;
      justify-content: space-between;
      margin: 6px 0;
      font-size: 18px;
      color: #000;
    }
    .thriposha-card .distribution-row .number {
      font-size: 28px;
      color: #000;
      font-weight: 700;
    }
    /* Additional styles for Doctor/Midwife layout */
    .bottom-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 20px;
    }
    .vaccination-card {
      flex: 1;
      min-width: 300px;
      border: 1px solid rgba(39,79,180,0.98);
      border-radius: 20px;
      background: #fff;
      padding: 20px;
      color: rgba(39,79,180,0.98);
      position: relative;
    }
    .vaccination-card h2 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 20px;
    }
    .vaccination-card table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 40px !important;
    }
    .vaccination-card th, .vaccination-card td {
      text-align: center;
      padding: 8px;
      font-size: 13px;
      color: #333;
    }
    .vaccination-card th {
      font-weight: 600;
      background-color: #f5f5f5;
    }
    .vaccination-card td {
      font-weight: 500;
      background-color: #fff;
    }
    .vaccination-card .view-more-btn {
      position: absolute;
      bottom: 20px;
      left: 420px;
      background: var(--primary-color);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 8px 16px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
    }
    .vaccination-card .view-more-btn:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div>
      <div class="logo">
        <i class="fas fa-child" style="font-size:24px;"></i>
        <span>KidsGrow</span>
      </div>
      <!-- Navigation links (same for all roles) -->
      <a href="#" class="menu-item active"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
      <a href="child_profile.php" class="menu-item"><i class="fas fa-child"></i><span>Child Profiles</span></a>
      <a href="parent_profile.php" class="menu-item"><i class="fas fa-users"></i><span>Parent Profiles</span></a>
      <a href="vaccination.php" class="menu-item"><i class="fas fa-syringe"></i><span>Vaccination</span></a>
      <a href="home_visit.php" class="menu-item"><i class="fas fa-home"></i><span>Home Visit</span></a>
      <a href="thriposha_distribution.php" class="menu-item"><i class="fas fa-box"></i><span>Thriposha Distribution</span></a>
      <a href="growth_details.php" class="menu-item"><i class="fas fa-chart-line"></i><span>Growth Details</span></a>
      <?php if ($userRole === 'SuperAdmin'): ?>
        <a href="add_admin.php" class="menu-item"><i class="fas fa-user-shield"></i><span>Add Admin</span></a>
      <?php endif; ?>
    </div>
    <div class="sidebar-user-profile" id="sidebarUserProfile">
      <img src="<?php echo htmlspecialchars($optimizedImage); ?>" alt="User Profile" />
      <div class="sidebar-user-info">
        <span class="sidebar-user-name"><?php echo htmlspecialchars($userName); ?></span>
        <span class="sidebar-user-role"><?php echo htmlspecialchars($userRole); ?></span>
      </div>
      <div class="sidebar-user-menu" id="sidebarUserMenu">
        <a href="logout.php">Sign Out</a>
        <a href="manage_profile.php">Manage Profile</a>
      </div>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="dashboard-container">
      <div class="dashboard-title">Dashboard</div>

      <?php if (in_array($userRole, ['Admin', 'SuperAdmin'])): ?>
        <!-- Admin / SuperAdmin Dashboard Layout -->
        <div class="top-row">
          <!-- Children Card -->
          <div class="info-card">
            <img src="images/kids.png" alt="Children Icon"/>
            <h2>Children</h2>
            <div class="info-value"><?php echo $childrenCount; ?></div>
            <button class="view-more-btn">View More</button>
          </div>
          <!-- Parents Card -->
          <div class="info-card">
            <img src="images/mother-and-son.png" alt="Parents Icon"/>
            <h2>Parents</h2>
            <div class="info-value"><?php echo $parentsCount; ?></div>
            <button class="view-more-btn">View More</button>
          </div>
          <!-- Admin Card -->
          <div class="info-card">
            <img src="images/admin 1.png" alt="Admin Icon"/>
            <h2>Admin</h2>
            <div class="info-value"><?php echo $adminCount; ?></div>
            <button class="view-more-btn">View More</button>
          </div>
          <!-- Users Card -->
          <div class="info-card">
            <img src="images/user 1.png" alt="User Icon"/>
            <h2>Users</h2>
            <div class="info-value"><?php echo $userCount; ?></div>
            <button class="view-more-btn">View More</button>
          </div>
        </div>

        <div class="upcoming-row">
          <div class="info-card thriposha-card">
            <h2 class="card-title">Upcoming Vaccination</h2>
            <div class="distribution-row">
              <span class="date">2025-03-07</span>
              <span class="number">25</span>
            </div>
            <button class="view-more-btn">View More</button>
          </div>

          <div class="info-card thriposha-card">
            <h2 class="card-title">Upcoming Home Visit</h2>
            <div class="distribution-row">
              <span class="date">2025-03-07</span>
              <span class="number"><?php echo $upcomingHomeVisitCount; ?></span>
            </div>
            <button class="view-more-btn">View More</button>
          </div>


          <div class="info-card thriposha-card" style="text-align: center;">
        <h2 class="card-title">Thriposha Distribution</h2>
        <div class="distribution-row">
            <span>Parent</span>
            <span class="date">2025-03-07</span>
            <span class="number">25</span>
        </div>
        <div class="distribution-row">
            <span>Child</span>
            <span class="date">2025-03-07</span>
            <span class="number">15</span>
        </div>
        <!-- 'text-align: center;' on the parent ensures the button is centered -->
        <button class="view-more-btn">View More</button>
        </div>



      <?php elseif (in_array($userRole, ['Doctor', 'Midwife'])): ?>
        <!-- Doctor / Midwife Dashboard Layout -->
        <div class="top-row">
          <!-- Children Card -->
          <div class="info-card">
            <h2>Children</h2>
            <img src="images/kids.png" alt="Child Icon" />
            <div class="info-value"><?php echo $childrenCount; ?></div>
            <button class="view-more-btn">View More</button>
          </div>
          <!-- Parents Card -->
          <div class="info-card">
            <h2>Parents</h2>
            <img src="images/mother-and-son.png" alt="Parent Icon" />
            <div class="info-value"><?php echo $parentsCount; ?></div>
            <button class="view-more-btn">View More</button>
          </div>
          <!-- Thriposha Distribution Card -->
          <div class="info-card thriposha-card">
            <h2 class="card-title">Thriposha Distribution</h2>
            <div class="distribution-row">
              <span>Parent</span>
              <span class="date">2025-03-07</span>
              <span class="number">25</span>
            </div>
            <div class="distribution-row">
              <span>Child</span>
              <span class="date">2025-03-07</span>
              <span class="number">15</span>
            </div>
            <button class="view-more-btn">View More</button>
          </div>
        </div>

        <div class="bottom-row">
          <!-- Upcoming Vaccination Card -->
          <div class="vaccination-card">
            <h2>Upcoming Vaccination</h2>
            <table>
              <thead>
                <tr>
                  <th>Child ID</th>
                  <th>Vaccination Name</th>
                  <th>Date of Vaccination</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>001</td>
                  <td>B.C.G.</td>
                  <td>2025-03-14</td>
                </tr>
                <tr>
                  <td>001</td>
                  <td>B.C.G.</td>
                  <td>2025-03-14</td>
                </tr>
                <tr>
                  <td>001</td>
                  <td>B.C.G.</td>
                  <td>2025-03-14</td>
                </tr>
                <tr>
                  <td>001</td>
                  <td>B.C.G.</td>
                  <td>2025-03-14</td>
                </tr>
                <tr>
                  <td>001</td>
                  <td>B.C.G.</td>
                  <td>2025-03-14</td>
                </tr>
              </tbody>
            </table>
            <button class="view-more-btn">View More</button>
          </div>

          <!-- Upcoming Home Visit Card -->
          <div class="vaccination-card">
            <h2>Upcoming Home Visit</h2>
            <table>
              <thead>
                <tr>
                  <th>Child ID</th>
                  <th>Child Name</th>
                  <th>Upcoming Home Visit Date</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>101</td>
                  <td>Kaveesha Gimhani</td>
                  <td>2025-12-12</td>
                </tr>
                <tr>
                  <td>101</td>
                  <td>Kaveesha Gimhani</td>
                  <td>2025-12-12</td>
                </tr>
                <tr>
                  <td>101</td>
                  <td>Kaveesha Gimhani</td>
                  <td>2025-12-12</td>
                </tr>
                <tr>
                  <td>101</td>
                  <td>Kaveesha Gimhani</td>
                  <td>2025-12-12</td>
                </tr>
                <tr>
                  <td>101</td>
                  <td>Kaveesha Gimhani</td>
                  <td>2025-12-12</td>
                </tr>
              </tbody>
            </table>
            <button class="view-more-btn">View More</button>
          </div>
        </div>

      <?php else: ?>
        <p>You are not authorized to view this dashboard.</p>
      <?php endif; ?>

    </div>
  </div>

  <!-- JS for Sidebar User Menu -->
  <script>
    // Toggle user menu in the sidebar
    function toggleSidebarUserMenu() {
      const menu = document.getElementById('sidebarUserMenu');
      menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
    document.addEventListener('DOMContentLoaded', () => {
      const userProfile = document.getElementById('sidebarUserProfile');
      const userMenu = document.getElementById('sidebarUserMenu');
      userProfile.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSidebarUserMenu();
      });
      document.addEventListener('click', () => {
        userMenu.style.display = 'none';
      });
    });
  </script>
</body>
</html>
