<?php
session_start(); // Start the session

// // Check if the user is logged in
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
//     // Redirect to the sign-in page if not logged in
//     header('Location: signin.php');
//     exit;
// }

// // Restrict access to only Admin and SuperAdmin
// $allowed_roles = ['Admin', 'SuperAdmin'];
// if (!in_array($_SESSION['user_role'], $allowed_roles)) {
//     // Redirect unauthorized users
//     header('Location: unauthorized.php'); // Create an unauthorized access page if needed
//     exit;
// }

// Database connection details
$dsn = "pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";
$db_user = "neondb_owner";
$db_password = "npg_39CVAQbvIlOy";

try {
    // Establish a connection to the database
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch child data from the database
try {
    $stmt = $pdo->query("SELECT * FROM child");
    $childData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching child records: " . $e->getMessage());
}


// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM child WHERE child_id = :child_id");
        $stmt->execute([':child_id' => $_POST['delete_id']]);
        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}


// Check if the request is an inline edit request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['child_id'], $_POST['column'], $_POST['value'])) {
    $childId = $_POST['child_id'];
    $column = $_POST['column'];
    $value = $_POST['value'];

    // Validate the column name to prevent SQL injection
    $allowedColumns = [
        'parent_id',
        'name',
        'birth_date',
        'birth_hospital',
        'breastfeeding_within_1h',
        'congenital_hypothyroidism_check',
        'hypothyroidism_test_results',
        'reasons_to_preserve',
        'breastfeeding_only_2m',
        'breastfeeding_only_4m',
        'breastfeeding_only_6m',
        'start_feeding_other_foods',
        'age_started_feeding_other_foods',
        'age_stopped_breastfeeding',
        'other_foods_at_1_year',
        'weight',
        'sex',
        'health_medical_officer_division',
        'family_health_medical_officer_division',
        'started_feeding_other_foods_4m',
        'started_feeding_other_foods_6m'
    ]; // Removed supplementary_record_number and gn_record_number
    


    if (!in_array($column, $allowedColumns)) {
        echo json_encode(["status" => "error", "message" => "Invalid column name"]);
        exit;
    }

    try {
        // Prepare and execute the update query
        $stmt = $pdo->prepare("UPDATE child SET $column = :value WHERE child_id = :child_id");
        $stmt->execute([
            ':value' => $value,
            ':child_id' => $childId,
        ]);
        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}


    if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        $query = "SELECT * FROM child";
        $params = [];

        if (!empty($search)) {
            $query .= " WHERE name ILIKE :search OR birth_hospital ILIKE :search";
            $params[':search'] = "%$search%";
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $childData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($childData);
        exit;
    }




?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Profiles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    font-family: 'Inter', sans-serif;
}

body {
    background-color: #f5f7fa;
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 250px;
    position: fixed;
    height: 100vh;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: var(--white);
    padding: 1.5rem;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.sidebar-header i {
    font-size: 2rem;
}

.sidebar-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    color: var(--white);
    text-decoration: none;
    border-radius: var(--border-radius);
    margin-bottom: 0.5rem;
    transition: background 0.3s;
}

.nav-item i {
    margin-right: 1rem;
    font-size: 1.2rem;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Main Content */
.main-content {
    margin-left: 250px;
    padding: 2rem;
    width: calc(100% - 250px);
}

/* Header with Search Bar & Add Button */
.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

/* Search Bar */
.search-bar {
    display: flex;
    align-items: center;
    background: var(--white);
    padding: 0.8rem 1rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    width: 350px;
}

.search-bar i {
    color: var(--text-light);
    margin-right: 10px;
}

.search-bar input {
    border: none;
    outline: none;
    flex: 1;
    font-size: 14px;
    background: transparent;
}

/* Add Button */
.add-button {
    background: var(--primary-color);
    color: var(--white);
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: background 0.3s;
    font-size: 14px;
    font-weight: 600;
}

.add-button:hover {
    background: #1a3a8a;
}

/* Table Container */
.table-container {
    margin-top: 1rem;
    overflow-x: auto;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 1rem;
    width: 100%;
}

/* Table Wrapper - Makes the table scrollable if needed */
.table-wrapper {
    max-height: 65vh;
    overflow-y: auto;
    border-radius: var(--border-radius);
}

/* Modern Table */
.modern-table {
    width: 100%;
    border-collapse: collapse;
    border-radius: var(--border-radius);
    overflow: hidden;
}

/* Table Header */
.modern-table thead {
    background: var(--primary-color);
    color: white;
    font-size: 14px;
}

.modern-table th, 
.modern-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    min-width: 150px; /* Prevents excessive shrinking */
    white-space: nowrap;
}

/* Sticky Header */
.modern-table thead tr {
    position: sticky;
    top: 0;
    background: var(--primary-color);
    z-index: 10;
}

/* Table Row */
.modern-table tbody tr {
    transition: background 0.3s;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
}

/* Action Buttons */
.actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    transition: color 0.3s;
}

.action-btn.edit {
    color: #274FB4;
}

.action-btn.delete {
    color: #d9534f;
}

.action-btn:hover {
    opacity: 0.8;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    z-index: 999;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    max-width: 90%;
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* Buttons */
.button-group {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-weight: 500;
}

.btn-cancel {
    background: white;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-cancel:hover {
    background: #e6e6e6;
}

.btn-save {
    background: var(--primary-color);
    color: white;
}

.btn-save:hover {
    background: #1a3a8a;
}

/* Alerts */
.alert-box {
    position: fixed;
    top: 10px;
    right: 20px;
    padding: 15px 20px;
    font-size: 16px;
    border-radius: 5px;
    color: white;
    font-weight: bold;
    z-index: 1000;
    animation: fadeIn 0.5s ease-in-out;
}

.alert-box.success {
    background-color: #28a745;
}

.alert-box.error {
    background-color: #dc3545;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar {
        width: 220px;
    }

    .main-content {
        margin-left: 220px;
        width: calc(100% - 220px);
    }

    .table-header, .table-row {
        grid-template-columns: repeat(10, minmax(120px, 1fr));
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 200px;
        padding: 1rem;
    }

    .main-content {
        margin-left: 200px;
        width: calc(100% - 200px);
    }

    .header-container {
        flex-direction: column;
        align-items: flex-start;
    }

    .table-header, .table-row {
        grid-template-columns: repeat(6, minmax(120px, 1fr));
    }
}

    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-child"></i>
            <h1>KidsGrow</h1>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="child_profile.php" class="nav-item">
                <i class="fas fa-child"></i>
                Child Profiles
            </a>
            <a href="parent_profile.php" class="nav-item">
                <i class="fas fa-users"></i>
                Parent Profiles
            </a>
            <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
            <a href="add_admin.php" class="nav-item">
                <i class="fas fa-user-shield"></i>
                Add Admin
            </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                Sign Out
            </a>
        </nav>
    </aside>

    <div class="table-container">
    <div class="content-header">
        <h2>Child Profiles</h2>
        <button class="add-btn"><a href="add_child.php">+ Add Child</a></button>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search by name or hospital..." 
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button id="clearSearch" class="clear-button"><i class="fas fa-times"></i></button>
    </div>

    <!-- Scrollable Table -->
    <div class="table-scroll">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Child ID</th>
                    <th>Parent ID</th>
                    <th>Name</th>
                    <th>Birth Date</th>
                    <th>Birth Hospital</th>
                    <th>Breastfeeding Within 1 Hour</th>
                    <th>Congenital Hypothyroidism Check</th>
                    <th>Hypothyroidism Test Result</th>
                    <th>Reasons to Preserve</th>
                    <th>Only Breastfeeding at 2 Months</th>
                    <th>Only Breastfeeding at 4 Months</th>
                    <th>Only Breastfeeding at 6 Months</th>
                    <th>Started Feeding Other Foods</th>
                    <th>Age Started Feeding Other Foods</th>
                    <th>Age Stopped Breastfeeding</th>
                    <th>Other Foods at 1 Year</th>
                    <th>Weight (kg)</th>
                    <th>Sex</th>
                    <th>Health Medical Officer Division</th>
                    <th>Family Health Medical Officer Division</th>
                    <th>Started Feeding Other Foods at 4 Months</th>
                    <th>Started Feeding Other Foods at 6 Months</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($childData as $child) : ?>
                <tr data-id="<?php echo htmlspecialchars($child['child_id']); ?>">
                    <td><?php echo htmlspecialchars($child['child_id']); ?></td>
                    <td class="editable" data-column="parent_id"><?php echo htmlspecialchars($child['parent_id']); ?></td>
                    <td class="editable" data-column="name"><?php echo htmlspecialchars($child['name']); ?></td>
                    <td class="editable" data-column="birth_date"><?php echo htmlspecialchars($child['birth_date']); ?></td>
                    <td class="editable" data-column="birth_hospital"><?php echo htmlspecialchars($child['birth_hospital']); ?></td>
                    <td><?php echo $child['breastfeeding_within_1h'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $child['congenital_hypothyroidism_check'] ? 'Yes' : 'No'; ?></td>
                    <td class="editable" data-column="hypothyroidism_test_results"><?php echo htmlspecialchars($child['hypothyroidism_test_results']); ?></td>
                    <td class="editable" data-column="reasons_to_preserve"><?php echo htmlspecialchars($child['reasons_to_preserve']); ?></td>
                    <td><?php echo $child['breastfeeding_only_2m'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $child['breastfeeding_only_4m'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $child['breastfeeding_only_6m'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $child['start_feeding_other_foods'] ? 'Yes' : 'No'; ?></td>
                    <td class="editable" data-column="age_started_feeding_other_foods"><?php echo htmlspecialchars($child['age_started_feeding_other_foods']); ?></td>
                    <td class="editable" data-column="age_stopped_breastfeeding"><?php echo htmlspecialchars($child['age_stopped_breastfeeding']); ?></td>
                    <td><?php echo $child['other_foods_at_1_year'] ? 'Yes' : 'No'; ?></td>
                    <td class="editable" data-column="weight"><?php echo htmlspecialchars($child['weight']); ?> kg</td>
                    <td class="editable" data-column="sex"><?php echo htmlspecialchars($child['sex']); ?></td>
                    <td class="editable" data-column="health_medical_officer_division"><?php echo htmlspecialchars($child['health_medical_officer_division']); ?></td>
                    <td class="editable" data-column="family_health_medical_officer_division"><?php echo htmlspecialchars($child['family_health_medical_officer_division']); ?></td>
                    <td><?php echo $child['started_feeding_other_foods_4m'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $child['started_feeding_other_foods_6m'] ? 'Yes' : 'No'; ?></td>
                    <td class="actions">
                        <button class="action-btn edit"><i class="fas fa-edit"></i></button>
                        <button class="action-btn delete" data-id="<?php echo $child['child_id']; ?>"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
    
    <div class="modal-overlay"></div>

    <div id="editChildModal" class="modal">
        <div class="modal-content card">
            <h2>UPDATE CHILD</h2>
            <form id="editChildForm">
                <input type="hidden" id="edit-child-id">

                <div class="form-group">
                    <label>Name :</label> 
                    <span id="edit-child-name" class="bold-text"></span>
                </div>

                <div class="form-group">
                    <label>Health Medical Officer Division</label>
                    <input type="text" class="form-control" id="edit-health-division">
                </div>

                <div class="form-group">
                    <label>Family Health Medical Officer Division</label>
                    <input type="text" class="form-control" id="edit-family-health-division">
                </div>

                <div class="form-group">
                    <label>Supplementary Regional Record Number</label>
                    <input type="text" class="form-control" id="edit-supplementary-record">
                </div>

                <div class="form-group">
                    <label>Grama Niladhari Record Number</label>
                    <input type="text" class="form-control" id="edit-gn-record">
                </div>

                <div class="form-group">
                    <label>Is only Breastfeeding at 2 months?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="breast2" id="breast2-yes">
                            <label for="breast2-yes">Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="breast2" id="breast2-no">
                            <label for="breast2-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Is only Breastfeeding at 4 months?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="breast4" id="breast4-yes">
                            <label for="breast4-yes">Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="breast4" id="breast4-no">
                            <label for="breast4-no">No</label>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-cancel" id="cancelEdit">Cancel</button>
                    <button type="submit" class="btn btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    let userRole = "<?php echo $_SESSION['user_role'] ?? ''; ?>"; // Get role from PHP session

    if (userRole !== "SuperAdmin") {
        let addAdminLink = document.querySelector(".sidebar a[href='add_admin.php']");
        if (addAdminLink) {
            addAdminLink.style.display = "none"; // Hide the Add Admin link
        }
    }
});

$(document).ready(function () {
    // DELETE Functionality
    $(document).on("click", ".delete", function () {
        let childId = $(this).data("id");
        if (confirm("Are you sure you want to delete this record?")) {
            $.post("", { delete_id: childId }, function (response) {
                location.reload();
            }, "json");
        }
    });

    $(document).ready(function () {
    let ajaxRequest = null; // To track the current AJAX request
    let searchTimeout = null; // To track the debounce timeout

    // Search input handler with debouncing
    $("#searchInput").on("input", function () {
        let searchQuery = $(this).val().trim();

        // Clear the previous timeout and abort any ongoing AJAX request
        clearTimeout(searchTimeout);
        if (ajaxRequest) {
            ajaxRequest.abort();
        }

        // Debounce the input with a 300ms delay
        searchTimeout = setTimeout(() => {
            fetchFilteredData(searchQuery);
        }, 300);
    });

    // Function to fetch filtered data from the server
    function fetchFilteredData(query) {
        ajaxRequest = $.ajax({
            url: window.location.pathname, // Use the current page URL
            method: "GET",
            data: {
                search: query,
                ajax: "true" // Ensure the server knows this is an AJAX request
            },
            dataType: "json",
            success: function (data) {
                updateTable(data); // Update the table with the fetched data
            },
            error: function (xhr, status, error) {
                if (status !== "abort") {
                    console.error("Error fetching search results:", error);
                }
            }
        });
    }

    // Function to update the table with fetched data
    function updateTable(data) {
        let tableBody = $("tbody");
        tableBody.empty(); // Clear existing rows

        if (data.length === 0) {
            // Show a message if no records are found
            tableBody.append("<tr><td colspan='22'>No records found</td></tr>");
            return;
        }

        // Loop through the data and append rows to the table
        data.forEach(child => {
            let row = `<tr data-id="${child.child_id}">
                <td>${child.child_id}</td>
                <td class="editable" data-column="parent_id">${child.parent_id}</td>
                <td class="editable" data-column="name">${child.name}</td>
                <td class="editable" data-column="birth_date">${child.birth_date}</td>
                <td class="editable" data-column="birth_hospital">${child.birth_hospital}</td>
                <td class="editable" data-column="breastfeeding_within_1h">${child.breastfeeding_within_1h ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="congenital_hypothyroidism_check">${child.congenital_hypothyroidism_check ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="hypothyroidism_test_results">${child.hypothyroidism_test_results}</td>
                <td class="editable" data-column="reasons_to_preserve">${child.reasons_to_preserve}</td>
                <td class="editable" data-column="breastfeeding_only_2m">${child.breastfeeding_only_2m ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="breastfeeding_only_4m">${child.breastfeeding_only_4m ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="breastfeeding_only_6m">${child.breastfeeding_only_6m ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="start_feeding_other_foods">${child.start_feeding_other_foods ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="age_started_feeding_other_foods">${child.age_started_feeding_other_foods}</td>
                <td class="editable" data-column="age_stopped_breastfeeding">${child.age_stopped_breastfeeding}</td>
                <td class="editable" data-column="other_foods_at_1_year">${child.other_foods_at_1_year ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="weight">${child.weight} kg</td>
                <td class="editable" data-column="sex">${child.sex}</td>
                <td class="editable" data-column="health_medical_officer_division">${child.health_medical_officer_division}</td>
                <td class="editable" data-column="family_health_medical_officer_division">${child.family_health_medical_officer_division}</td>
                <td class="editable" data-column="started_feeding_other_foods_4m">${child.started_feeding_other_foods_4m ? 'Yes' : 'No'}</td>
                <td class="editable" data-column="started_feeding_other_foods_6m">${child.started_feeding_other_foods_6m ? 'Yes' : 'No'}</td>
                <td class="actions">
                    <i class="fas fa-edit edit"></i>
                    <i class="fas fa-trash-alt delete" data-id="${child.child_id}"></i>
                </td>
            </tr>`;
            tableBody.append(row);
        });
    }

    // Initial load of all data when the page loads
    fetchFilteredData("");
});



// EDIT Functionality - Open Modal with Child Data
$(document).on("click", ".edit", function () {
    let row = $(this).closest("tr");

    // Extract child data safely
    let childId = row.data("id") || "";
    let childName = row.find("[data-column='name']").text().trim() || "";
    let healthDivision = row.find("[data-column='health_medical_officer_division']").text().trim() || "";
    let familyHealthDivision = row.find("[data-column='family_health_medical_officer_division']").text().trim() || "";
    let supplementaryRecord = row.find("[data-column='supplementary_record_number']").text().trim() || "";
    let gnRecord = row.find("[data-column='gn_record_number']").text().trim() || "";

    // Populate modal fields (handle undefined values)
    $("#edit-child-id").val(childId);
    $("#edit-child-name").text(childName);
    $("#edit-health-division").val(healthDivision);
    $("#edit-family-health-division").val(familyHealthDivision);
    $("#edit-supplementary-record").val(supplementaryRecord);
    $("#edit-gn-record").val(gnRecord);

    // Show modal and overlay (ensure it appears)
    $("#editChildModal").fadeIn();
    $(".modal-overlay").fadeIn();
    $("body").addClass("modal-active");
});



$("#editChildForm").submit(function (e) {
    e.preventDefault(); // Prevent default form submission

    let childId = $("#edit-child-id").val();
    let healthDivision = $("#edit-health-division").val();
    let familyHealthDivision = $("#edit-family-health-division").val();

    $.post("", {
        child_id: childId,
        column: "health_medical_officer_division",
        value: healthDivision
    });

    $.post("", {
        child_id: childId,
        column: "family_health_medical_officer_division",
        value: familyHealthDivision
    }, function (response) {
        let res = JSON.parse(response);
        if (res.status === "success") {
            showAlert("success", "Child record updated successfully!");
            setTimeout(function () {
                location.reload(); // Refresh the page after success
            }, 2000);
        } else {
            showAlert("error", "Error updating record: " + res.message);
        }
    });

    closeModal(); // Close the modal after saving
});

// Function to display success/error messages (Same as Sign-In Page)
function showAlert(type, message) {
    let alertBox = $("<div>").addClass("alert-box " + type).text(message);
    $("body").append(alertBox);
    setTimeout(function () {
        alertBox.fadeOut(500, function () {
            $(this).remove();
        });
    }, 3000);
}

    // Close modal on cancel button click
    $("#cancelEdit").click(function () {
        closeModal();
    });

    // Close modal when clicking outside (on overlay)
    $(".modal-overlay").click(function () {
        closeModal();
    });

    // Close modal when pressing ESC key
    $(document).keydown(function (e) {
        if (e.key === "Escape" && $("#editChildModal").is(":visible")) {
            closeModal();
        }
    });

    // Function to close modal and remove blur
    function closeModal() {
        $("#editChildModal").fadeOut();
        $(".modal-overlay").fadeOut();
        $("body").removeClass("modal-active");
    }
});

    </script>

</body>
</html>
