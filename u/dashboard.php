<?php
session_start();

// 1. CHECK USER SESSION & ROLE (Parent Only)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: signin.php');
    exit;
}
if ($_SESSION['user_role'] !== 'Parent') {
    header('Location: unauthorized.php');
    exit;
}

// 2. DATABASE CONNECTION
$dsn         = "pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";
$db_user     = "neondb_owner";
$db_password = "npg_39CVAQbvIlOy";

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. FETCH THE PARENT RECORD
$loggedInUserId = $_SESSION['user_id'];
$stmtParent = $pdo->prepare("SELECT parent_id FROM parent WHERE user_id = :uid");
$stmtParent->execute([':uid' => $loggedInUserId]);
$parentRow = $stmtParent->fetch(PDO::FETCH_ASSOC);

if (!$parentRow) {
    die("No parent record found for this user.");
}
$parentId = $parentRow['parent_id'];

// 4. FETCH THE CHILD RECORD (first child for this parent)
$stmtChild = $pdo->prepare("SELECT * FROM child WHERE parent_id = :pid LIMIT 1");
$stmtChild->execute([':pid' => $parentId]);
$childRow = $stmtChild->fetch(PDO::FETCH_ASSOC);

// If no child found, show fallback
if (!$childRow) {
    $childData = [
        'child_id' => null,
        'name'   => 'No Child Found',
        'age'    => '-',
        'gender' => '-',
        'weight' => '-',
        'height' => '-'
    ];
} else {
    // Calculate the child's age from birth_date
    $birthDate = $childRow['birth_date'];
    $ageString = '-';
    if (!empty($birthDate)) {
        try {
            $dob = new DateTime($birthDate);
            $today = new DateTime('today'); // ensures no partial-day offsets

            $ageInterval = $dob->diff($today);

            if ($ageInterval->invert === 1) {
                // If DOB is in the future
                $ageString = '-';
            } else {
                $years = $ageInterval->y;
                $months = $ageInterval->m;
                $ageString = "{$years} year(s) {$months} month(s)";
            }
        } catch (Exception $ex) {
            $ageString = '-';
        }
    }

    // Build child data array
    $childData = [
        'child_id' => $childRow['child_id'],
        'name'     => $childRow['name'],
        'age'      => $ageString,
        'gender'   => $childRow['sex'],
        'weight'   => isset($childRow['weight']) ? $childRow['weight'].' Kg' : '-',
        'height'   => isset($childRow['height']) ? $childRow['height'].' cm' : '-',
    ];
}

// 5. FETCH BMI HISTORY FOR THE CHILD
$bmiData = [];
if (!empty($childData['child_id'])) {
    $stmtBmi = $pdo->prepare("
        SELECT bmi_id, recorded_at, weight, height, bmi
        FROM bmi_history
        WHERE child_id = :cid
        ORDER BY recorded_at ASC
    ");
    $stmtBmi->execute([':cid' => $childData['child_id']]);
    $bmiRows = $stmtBmi->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bmiRows as $row) {
        $recordedDate = (new DateTime($row['recorded_at']))->format('Y-m-d H:i:s');
        $bmiData[] = [
            'recorded_at' => $recordedDate,
            'weight'      => (float)$row['weight'],
            'height'      => (float)$row['height'],
            'bmi'         => (float)$row['bmi'],
        ];
    }
}

// 6. Hard-code or dynamically fetch upcoming vaccination info
$upcomingVaccination = [
    'name'         => 'MMR',
    'date'         => '2024-12-22',
    'time_left'    => '5 days',
    'status'       => 'Pending'
];

// 7. Health summary & recommendations
$healthSummary = [
    'last_checkup' => '2024-11-30',
    'next_checkup' => '2024-12-22'
];
$recommendation = 'Increase Iron intake';

// 8. USER NAME from session
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Parent User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>KidsGrow - Dashboard</title>

  <!-- Fonts & Icons -->
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
  />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
  />
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    :root {
      --bg-teal: #009688;
      --bg-card: #ffffff;
      --bg-gray: #f2f2f2;
      --text-color: #333;
      --sidebar-width: 220px;
      --primary-accent: #009688;
    }
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background-color: var(--bg-gray);
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: var(--sidebar-width); height: 100vh;
      background-color: var(--primary-accent); color: #fff;
      display: flex; flex-direction: column;
      justify-content: space-between; padding: 20px 0;
    }
    .logo {
      text-align: center; margin-bottom: 40px; font-size: 24px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .logo i {
      font-size: 28px;
    }
    .nav-links {
      flex: 1; display: flex; flex-direction: column; gap: 8px;
      padding: 0 20px;
    }
    .nav-links a {
      text-decoration: none; color: #fff;
      font-weight: 500; padding: 12px; border-radius: 8px;
      transition: background 0.2s;
      display: flex; align-items: center; gap: 12px;
    }
    .nav-links a:hover {
      background-color: rgba(255,255,255,0.2);
    }

    /* User Profile highlight area with sign-out menu */
    .user-profile {
      position: relative;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      background-color: rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      margin: 0 20px 20px 20px;
    }
    .user-profile img {
      width: 45px; height: 45px;
      border-radius: 50%; object-fit: cover;
    }
    .user-info {
      display: flex; flex-direction: column;
      font-size: 14px; line-height: 1.2;
    }

    .profile-menu {
      display: none;
      position: absolute;
      bottom: 70px;
      left: 0;
      background-color: #fff;
      color: #333;
      border-radius: 8px;
      min-width: 150px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      z-index: 999;
      padding: 10px 0;
    }
    .profile-menu a {
      display: block;
      padding: 8px 12px;
      color: #333; text-decoration: none;
    }
    .profile-menu a:hover {
      background-color: #f2f2f2;
    }

    /* Main content */
    .main-content {
      margin-left: var(--sidebar-width);
      padding: 20px;
    }
    .dashboard-header {
      font-size: 28px; font-weight: 700; margin-bottom: 24px;
    }

    /* Cards */
    .cards-container {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 20px; margin-bottom: 20px;
    }
    .card {
      background-color: var(--bg-card);
      padding: 20px; border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      color: var(--text-color);
    }
    .card h3 {
      margin-bottom: 12px; font-size: 18px; font-weight: 600;
      color: var(--primary-accent);
    }

    .bottom-cards-container {
      display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 20px;
    }
    #bmiChart {
      width: 100%; height: 220px;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div>
      <div class="logo">
        <i class="fas fa-child"></i>
        <span>KidsGrow</span>
      </div>
      <div class="nav-links">
        <a href="dashboard.php">
          <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="vaccination.php">
          <i class="fas fa-syringe"></i> Vaccination
        </a>
        <a href="growth_tracker.php">
          <i class="fas fa-chart-line"></i> Growth Tracker
        </a>
        <a href="learning.php">
          <i class="fas fa-book"></i> Learning
        </a>
      </div>
    </div>
    <div class="user-profile" id="userProfile">
      <img src="images/user.png" alt="User" />
      <div class="user-info">
        <span class="name"><?php echo htmlspecialchars($userName); ?></span>
      </div>
      <div class="profile-menu" id="profileMenu">
        <a href="logout.php">Sign Out</a>
      </div>
    </div>
  </div>
  <!-- END SIDEBAR -->

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="dashboard-header">Dashboard</div>

    <!-- Child & Vaccination Cards -->
    <div class="cards-container">
      <div class="card">
        <h3>Child Details</h3>
        <p><strong>Child Name:</strong> <?php echo htmlspecialchars($childData['name']); ?></p>
        <p><strong>Age:</strong> <?php echo htmlspecialchars($childData['age']); ?></p>
        <p><strong>Gender:</strong> <?php echo htmlspecialchars($childData['gender']); ?></p>
        <p><strong>Weight:</strong> <?php echo htmlspecialchars($childData['weight']); ?></p>
        <p><strong>Height:</strong> <?php echo htmlspecialchars($childData['height']); ?></p>
      </div>

      <div class="card">
        <h3>Upcoming Vaccinations</h3>
        <p><strong>Vaccination Name:</strong> <?php echo htmlspecialchars($upcomingVaccination['name']); ?></p>
        <p><strong>Scheduled Date:</strong> <?php echo htmlspecialchars($upcomingVaccination['date']); ?></p>
        <p><strong>Time Left:</strong> <?php echo htmlspecialchars($upcomingVaccination['time_left']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($upcomingVaccination['status']); ?></p>
      </div>
    </div>

    <!-- BMI Chart, Health & Growth, Recommendation -->
    <div class="bottom-cards-container">
      <div class="card">
        <h3>BMI Chart</h3>
        <canvas id="bmiChart"></canvas>
      </div>
      <div class="card">
        <h3>Health & Growth Summary</h3>
        <p><strong>Last Checkup Date:</strong> <?php echo htmlspecialchars($healthSummary['last_checkup']); ?></p>
        <p><strong>Next Checkup Date:</strong> <?php echo htmlspecialchars($healthSummary['next_checkup']); ?></p>
      </div>
      <div class="card">
        <h3>Recommendation</h3>
        <p><?php echo htmlspecialchars($recommendation); ?></p>
      </div>
    </div>
  </div>
  <!-- END MAIN CONTENT -->

  <!-- CHART & MENU SCRIPT -->
  <script>
    // Toggle the profile menu on user profile click
    const userProfile = document.getElementById('userProfile');
    const profileMenu = document.getElementById('profileMenu');

    userProfile.addEventListener('click', function(e) {
      e.stopPropagation();
      if (profileMenu.style.display === 'block') {
        profileMenu.style.display = 'none';
      } else {
        profileMenu.style.display = 'block';
      }
    });

    // Hide menu if clicked outside
    document.addEventListener('click', function() {
      profileMenu.style.display = 'none';
    });

    // Build Chart.js line chart from PHP array
    const bmiDataFromPHP = <?php echo json_encode($bmiData); ?>;
    const labels = bmiDataFromPHP.map(item => item.recorded_at);
    const bmiValues = bmiDataFromPHP.map(item => item.bmi);

    const ctx = document.getElementById('bmiChart').getContext('2d');
    const bmiChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'BMI Over Time',
          data: bmiValues,
          borderColor: 'rgb(75, 192, 192)',
          borderWidth: 2,
          fill: false,
          tension: 0.1,
          pointRadius: 3
        }]
      },
      options: {
        responsive: true,
        scales: {
          x: {
            display: true,
            title: { display: true, text: 'Date' }
          },
          y: {
            display: true,
            title: { display: true, text: 'BMI' }
          }
        }
      }
    });
  </script>
</body>
</html>
