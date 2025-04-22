<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the sign-in page if not logged in
    header('Location: signin.php');
    exit;
}

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

// Fetch parent data from the database
// Initialize $parentData as an empty array to prevent undefined variable errors
$parentData = [];

if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $query = "SELECT * FROM parent";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE mother_name ILIKE :search OR nic ILIKE :search";
        $params[':search'] = "%$search%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $parentData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($parentData);
    exit;
}

// If this is not an AJAX request, fetch all parent data by default
try {
    $stmt = $pdo->query("SELECT * FROM parent");
    $parentData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching parent records: " . $e->getMessage());
}



// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM parent WHERE parent_id = :parent_id");
        $stmt->execute([':parent_id' => $_POST['delete_id']]);
        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// Handle inline edit request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['parent_id'], $_POST['column'], $_POST['value'])) {
    $parentId = $_POST['parent_id'];
    $column = $_POST['column'];
    $value = $_POST['value'];

    $allowedColumns = [
        'mother_name',
        'nic',
        'dob',
        'address',
        'contact_number'
    ];

    if (!in_array($column, $allowedColumns)) {
        echo json_encode(["status" => "error", "message" => "Invalid column name"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE parent SET $column = :value WHERE parent_id = :parent_id");
        $stmt->execute([':value' => $value, ':parent_id' => $parentId]);
        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Profiles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            box-sizing: border-box;
            overflow-x: auto;
            width: calc(100% - 250px);
        }

        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(to bottom, #8FC4F1, #274FB4);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar h1 {
            font-size: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            gap: 10px;
        }

        .sidebar a i {
            font-size: 18px;
        }

        .sidebar a:hover {
            background-color: #34495e;
            color: #ecf0f1;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            box-sizing: border-box;
            overflow-x: auto;
        }

        .content-header {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h2 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        .content-header button {
            background-color: #274FB4;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: -5px;
            align-self: flex-end;
        }

        .content-header button:hover {
            background-color: #1f3a8a;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 10px;
            margin-left: 280px;
            margin-right: 30px;
            box-sizing: border-box;
            width: calc(100% - 250px);
        }

        .table-container .search-bar {
            position: sticky;
            top: 50px;
            background-color: white;
            z-index: 10;
            padding: 5px 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        .table-container .content-header {
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 10px 0;
            margin-bottom: 10px;
        }

        .table-container .search-bar input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 14px;
            padding: 8px;
        }

        .table-container .search-bar i {
            font-size: 18px;
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f9f9f9;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        .edit-mode {
            box-sizing: border-box;
            height: 100%;
            margin: -2px;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .actions i {
            cursor: pointer;
            color: #007bff;
            font-size: 18px;
            transition: color 0.3s;
        }

        .actions i:hover {
            color: #0056b3;
        }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .search-bar input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 14px;
        }

        .search-bar i {
            font-size: 16px;
            color: #aaa;
        }

        .table-scroll {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1><i class="fas fa-heartbeat"></i> Ministry of Health</h1>
        <a href="#"><i class="fas fa-home"></i> Dashboard</a>
        <a href="child_profile.php"><i class="fas fa-child"></i> Child Profiles</a>
        <a href="parent_profile.php"><i class="fas fa-users"></i> Parent Profiles</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'SuperAdmin'): ?>
        <a href="add_admin.php"><i class="fas fa-user-shield"></i> Add Admin</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </div>
    <div class="table-container">
        <div class="content-header">
            <h2>Parent Profiles</h2>
            <button>Add Parent</button>
        </div>
        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search by name or NIC..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <i class="fas fa-search"></i>
        </div>
        <!-- Scrollable Table -->
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Parent ID</th>
                        <th>Mother Name</th>
                        <th>NIC</th>
                        <th>Date of Birth</th>
                        <th>Address</th>
                        <th>Contact Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parentData as $parent) : ?>
                        <tr data-id="<?php echo htmlspecialchars($parent['parent_id'] ?? ''); ?>">
                            <td><?php echo htmlspecialchars($parent['parent_id'] ?? ''); ?></td>
                            <td class="editable" data-column="mother_name"><?php echo htmlspecialchars($parent['mother_name'] ?? ''); ?></td>
                            <td class="editable" data-column="nic"><?php echo htmlspecialchars($parent['nic'] ?? ''); ?></td>
                            <td class="editable" data-column="date_of_birth"><?php echo htmlspecialchars($parent['dob'] ?? ''); ?></td>
                            <td class="editable" data-column="address"><?php echo htmlspecialchars($parent['address'] ?? ''); ?></td>
                            <td class="editable" data-column="contact_number"><?php echo htmlspecialchars($parent['contact_number'] ?? ''); ?></td>
                            <td class="actions">
                                <i class="fas fa-edit edit"></i>
                                <i class="fas fa-trash-alt delete" data-id="<?php echo $parent['parent_id'] ?? ''; ?>"></i>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
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
                let parentId = $(this).data("id");
                if (confirm("Are you sure you want to delete this record?")) {
                    $.post("", { delete_id: parentId }, function (response) {
                        location.reload();
                    }, "json");
                }
            });

            // EDIT Functionality - Press "Ctrl + Enter" to Save
            $(document).on("click", ".edit", function () {
                let row = $(this).closest("tr");
                let editables = row.find(".editable");
                let inputs = [];

                // Replace each editable cell with an input
                editables.each(function () {
                    let cell = $(this);
                    let originalText = cell.text().trim();
                    let column = cell.data("column");
                    let parentId = row.data("id");

                    let input = $('<input>')
                        .val(originalText)
                        .addClass('edit-mode')
                        .css({
                            'width': '100%',
                            'border': '1px solid #ccc',
                            'padding': '5px',
                            'font-size': '14px'
                        });

                    cell.html(input);
                    inputs.push(input); // Store input for later focus
                });

                // Focus the first input in the row
                if (inputs.length > 0) {
                    inputs[0].focus();
                }

                // Save on Ctrl+Enter and handle blur
                inputs.forEach(input => {
                    input.on('keydown', function(e) {
                        if (e.ctrlKey && e.key === 'Enter') {
                            let cell = $(this).closest('.editable');
                            let newValue = $(this).val().trim();
                            let column = cell.data('column');
                            let parentId = row.data('id');

                            $.post("", {
                                parent_id: parentId,
                                column: column,
                                value: newValue
                            }, function(response) {
                                let res = JSON.parse(response);
                                if (res.status === 'success') {
                                    cell.text(newValue);
                                } else {
                                    alert('Error: ' + res.message);
                                    cell.text(cell.data('original-text'));
                                }
                            });
                        } else if (e.key === 'Escape') {
                            cell.text(cell.data('original-text'));
                        }
                    });

                    input.on('blur', function() {
                        let cell = $(this).closest('.editable');
                        cell.text(cell.data('original-text'));
                    });
                });
            });

            // Search Functionality
            $(document).ready(function () {
            let ajaxRequest = null;
            let searchTimeout = null;

            $("#searchInput").on("input", function () {
                let searchQuery = $(this).val().trim();

                clearTimeout(searchTimeout);
                if (ajaxRequest) {
                    ajaxRequest.abort();
                }

                searchTimeout = setTimeout(() => {
                    fetchFilteredData(searchQuery);
                }, 300);
            });

            function fetchFilteredData(query) {
                ajaxRequest = $.ajax({
                    url: window.location.pathname,
                    method: "GET",
                    data: {
                        search: query,
                        ajax: "true"
                    },
                    dataType: "json",
                    success: function (data) {
                        updateTable(data);
                    },
                    error: function (xhr, status, error) {
                        if (status !== "abort") {
                            console.error("Error fetching search results:", error);
                        }
                    }
                });
            }

            function updateTable(data) {
                let tableBody = $("tbody");
                tableBody.empty();

                if (data.length === 0) {
                    tableBody.append("<tr><td colspan='7'>No records found</td></tr>");
                    return;
                }

                data.forEach(parent => {
                    let row = `<tr data-id="${parent.parent_id}">
                        <td>${parent.parent_id}</td>
                        <td class="editable" data-column="mother_name">${parent.mother_name}</td>
                        <td class="editable" data-column="nic">${parent.nic}</td>
                        <td class="editable" data-column="dob">${parent.dob}</td>
                        <td class="editable" data-column="address">${parent.address}</td>
                        <td class="editable" data-column="contact_number">${parent.contact_number}</td>
                        <td class="actions">
                            <i class="fas fa-edit edit"></i>
                            <i class="fas fa-trash-alt delete" data-id="${parent.parent_id}"></i>
                        </td>
                    </tr>`;
                    tableBody.append(row);
                });
            }

            // Initial load
            fetchFilteredData("");
        });

    });
    </script>
</body>
</html>