<?php session_start();if(!isset($_SESSION['user_id'])){header('Location: signin.php');exit;}$dsn="pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";$db_user="neondb_owner";$db_password="npg_39CVAQbvIlOy";try{$pdo=new PDO($dsn,$db_user,$db_password);$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);}catch(PDOException $e){die("Database connection failed: ".$e->getMessage());}try{$stmt=$pdo->query("SELECT * FROM child");$childData=$stmt->fetchAll(PDO::FETCH_ASSOC);}catch(PDOException $e){die("Error fetching child records: ".$e->getMessage());}if($_SERVER["REQUEST_METHOD"]=="POST"&&isset($_POST['delete_id'])){try{$stmt=$pdo->prepare("DELETE FROM child WHERE child_id = :child_id");$stmt->execute([':child_id'=>$_POST['delete_id']]);echo json_encode(["status"=>"success"]);}catch(PDOException $e){echo json_encode(["status"=>"error","message"=>$e->getMessage()]);}exit;}if($_SERVER["REQUEST_METHOD"]==="POST"&&isset($_POST['child_id'],$_POST['column'],$_POST['value'])){$childId=$_POST['child_id'];$column=$_POST['column'];$value=$_POST['value'];$allowedColumns=['parent_id','name','birth_date','birth_hospital','breastfeeding_within_1h','congenital_hypothyroidism_check','hypothyroidism_test_results','reasons_to_preserve','breastfeeding_only_2m','breastfeeding_only_4m','breastfeeding_only_6m','start_feeding_other_foods','age_started_feeding_other_foods','age_stopped_breastfeeding','other_foods_at_1_year','weight','sex','health_medical_officer_division','family_health_medical_officer_division','started_feeding_other_foods_4m','started_feeding_other_foods_6m'];if(!in_array($column,$allowedColumns)){echo json_encode(["status"=>"error","message"=>"Invalid column name"]);exit;}try{$stmt=$pdo->prepare("UPDATE child SET $column = :value WHERE child_id = :child_id");$stmt->execute([':value'=>$value,':child_id'=>$childId,]);echo json_encode(["status"=>"success"]);}catch(PDOException $e){echo json_encode(["status"=>"error","message"=>$e->getMessage()]);}exit;}if(isset($_GET['ajax'])&&$_GET['ajax']=='true'){$search=isset($_GET['search'])?trim($_GET['search']):'';$query="SELECT * FROM child";$params=[];if(!empty($search)){$query.=" WHERE name ILIKE :search OR birth_hospital ILIKE :search";$params[':search']="%$search%";}$stmt=$pdo->prepare($query);$stmt->execute($params);$childData=$stmt->fetchAll(PDO::FETCH_ASSOC);echo json_encode($childData);exit;} ?><!doctypehtml><html lang="en"><meta charset="UTF-8"><meta content="width=device-width,initial-scale=1"name="viewport"><title>Child Profiles</title><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Arial,sans-serif;background-color:#f4f4f4;color:#333;display:flex;min-height:100vh}.main-content{margin-left:250px;padding:20px;box-sizing:border-box;overflow-x:auto;width:calc(100% - 250px)}.sidebar{width:250px;position:fixed;top:0;left:0;height:100%;background:linear-gradient(to bottom,#8fc4f1,#274fb4);color:#fff;display:flex;flex-direction:column;justify-content:flex-start;padding-top:20px;box-shadow:2px 0 5px rgba(0,0,0,.1)}.sidebar h1{font-size:20px;margin-bottom:30px;display:flex;align-items:center;gap:10px}.sidebar a{display:flex;align-items:center;padding:10px 20px;color:#fff;text-decoration:none;font-size:16px;gap:10px}.sidebar a i{font-size:18px}.sidebar a:hover{background-color:#34495e;color:#ecf0f1}.content{margin-left:250px;padding:20px;box-sizing:border-box;overflow-x:auto}.content-header{margin-bottom:10px;display:flex;justify-content:space-between;align-items:center}.content-header h2{font-size:24px;color:#333;margin:0}.content-header button{background-color:#274fb4;color:#fff;border:none;padding:10px 20px;font-size:16px;border-radius:5px;cursor:pointer;transition:background .3s;margin-top:-5px;align-self:flex-end}.content-header button a{text-decoration:none;color:#fff;display:block;width:100%;height:100%}.content-header button:hover{background-color:#1f3a8a}.table-container{overflow-x:auto;background:#fff;border-radius:10px;box-shadow:0 2px 4px rgba(0,0,0,.1);padding:20px;margin-top:10px;margin-left:280px;margin-right:30px;box-sizing:border-box;width:calc(100% - 250px)}.table-container .search-bar{position:sticky;top:50px;background-color:#fff;z-index:10;padding:5px 10px;margin-bottom:20px;border-radius:5px;box-shadow:0 1px 2px rgba(0,0,0,.1);width:300px}.table-container .content-header{position:sticky;top:0;z-index:10;padding:10px 0;margin-bottom:10px}.table-container .search-bar input{border:none;outline:0;flex:1;font-size:14px;padding:8px}.table-container .search-bar i{font-size:18px;color:#007bff}table{width:100%;border-collapse:collapse}td,th{padding:10px;text-align:left;border-bottom:1px solid #ddd}th{background-color:#f9f9f9;font-size:14px}td{font-size:14px}.edit-mode{box-sizing:border-box;height:100%;margin:-2px}.actions{display:flex;gap:10px}.actions i{cursor:pointer;color:#007bff;font-size:18px;transition:color .3s}.actions i:hover{color:#0056b3}.search-bar{display:flex;align-items:center;gap:10px;background-color:#fff;padding:10px 15px;border-radius:5px;margin-bottom:10px;box-shadow:0 1px 2px rgba(0,0,0,.1)}.search-bar input{border:none;outline:0;flex:1;font-size:14px}.search-bar i{font-size:16px;color:#aaa}.table-scroll{max-height:calc(100vh - 200px);overflow-y:auto;overflow-x:auto}.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.3);backdrop-filter:blur(5px);z-index:999}.modal{display:none;position:fixed;z-index:1001;left:50%;top:50%;transform:translate(-50%,-50%);width:600px;max-width:90%;background:#fff;border-radius:10px;padding:30px;box-shadow:0 8px 20px rgba(0,0,0,.2)}.modal-content{display:flex;flex-direction:column}.modal-content h2{font-size:20px;font-weight:600;text-align:center;margin-bottom:15px;color:#333}.form-group{margin-bottom:15px}.form-group label{display:block;font-size:14px;font-weight:500;color:#333;margin-bottom:5px}.form-control{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;background-color:#f5f5f5;transition:border-color .2s}.form-control:focus{border-color:#274fb4;outline:0}.radio-group{display:flex;gap:30px;margin-bottom:10px}.radio-option{display:flex;align-items:center;gap:8px}.radio-group input[type=radio]{appearance:none;width:16px;height:16px;border:2px solid #bbb;border-radius:50%;transition:all .2s;cursor:pointer}.radio-group input[type=radio]:checked{border-color:#274fb4;background-color:#274fb4}.button-group{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}.btn{padding:10px 20px;border-radius:5px;border:none;cursor:pointer;font-weight:500}.btn-cancel{background:#fff;border:1px solid #274fb4;color:#274fb4}.btn-cancel:hover{background:#e6e6e6}.btn-save{background:#274fb4;color:#fff}.btn-save:hover{background:#1a3a8a}.alert-box{position:fixed;top:10px;right:20px;padding:15px 20px;font-size:16px;border-radius:5px;color:#fff;font-weight:700;z-index:1000;animation:fadeIn .5s ease-in-out}.alert-box.success{background-color:#28a745}.alert-box.error{background-color:#dc3545}@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}</style><div class="sidebar"><h1><i class="fas fa-heartbeat"></i> Ministry of Health</h1><a href="#"><i class="fas fa-home"></i> Dashboard</a> <a href="child_profile.php"><i class="fas fa-child"></i> Child Profiles</a> <a href="parent_profile.php"><i class="fas fa-users"></i> Parent Profiles</a><?php if(isset($_SESSION['user_role'])&&$_SESSION['user_role']==='SuperAdmin'): ?><a href="add_admin.php"><i class="fas fa-user-shield"></i> Add Admin</a><?php endif; ?><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></div><div class="table-container"><div class="content-header"><h2>Child Profiles</h2><button><a href="add_child.php">Add Child</a></button></div><div class="search-bar"><input id="searchInput"placeholder="Search by name or hospital..."value="<?php echo isset($_GET['search'])?htmlspecialchars($_GET['search']):''; ?>"> <i class="fas fa-search"></i></div><div class="table-scroll"><table><thead><tr><th>Child ID<th>Parent ID<th>Name<th>Birth Date<th>Birth Hospital<th>Breastfeeding Within 1 Hour<th>Congenital Hypothyroidism Check<th>Hypothyroidism Test Result<th>Reasons to Preserve<th>Only Breastfeeding at 2 Months<th>Only Breastfeeding at 4 Months<th>Only Breastfeeding at 6 Months<th>Started Feeding Other Foods<th>Age Started Feeding Other Foods<th>Age Stopped Breastfeeding<th>Other Foods at 1 Year<th>Weight (kg)<th>Sex<th>Health Medical Officer Division<th>Family Health Medical Officer Division<th>Started Feeding Other Foods at 4 Months<th>Started Feeding Other Foods at 6 Months<th>Actions<tbody><?php foreach($childData as $child): ?><tr data-id="<?php echo htmlspecialchars($child['child_id']); ?>"><td><?php echo htmlspecialchars($child['child_id']); ?><td class="editable"data-column="parent_id"><?php echo htmlspecialchars($child['parent_id']); ?><td class="editable"data-column="name"><?php echo htmlspecialchars($child['name']); ?><td class="editable"data-column="birth_date"><?php echo htmlspecialchars($child['birth_date']); ?><td class="editable"data-column="birth_hospital"><?php echo htmlspecialchars($child['birth_hospital']); ?><td class="editable"data-column="breastfeeding_within_1h"><?php echo $child['breastfeeding_within_1h']?'Yes':'No'; ?><td class="editable"data-column="congenital_hypothyroidism_check"><?php echo $child['congenital_hypothyroidism_check']?'Yes':'No'; ?><td class="editable"data-column="hypothyroidism_test_results"><?php echo htmlspecialchars($child['hypothyroidism_test_results']); ?><td class="editable"data-column="reasons_to_preserve"><?php echo htmlspecialchars($child['reasons_to_preserve']); ?><td class="editable"data-column="breastfeeding_only_2m"><?php echo $child['breastfeeding_only_2m']?'Yes':'No'; ?><td class="editable"data-column="breastfeeding_only_4m"><?php echo $child['breastfeeding_only_4m']?'Yes':'No'; ?><td class="editable"data-column="breastfeeding_only_6m"><?php echo $child['breastfeeding_only_6m']?'Yes':'No'; ?><td class="editable"data-column="start_feeding_other_foods"><?php echo $child['start_feeding_other_foods']?'Yes':'No'; ?><td class="editable"data-column="age_started_feeding_other_foods"><?php echo htmlspecialchars($child['age_started_feeding_other_foods']); ?><td class="editable"data-column="age_stopped_breastfeeding"><?php echo htmlspecialchars($child['age_stopped_breastfeeding']); ?><td class="editable"data-column="other_foods_at_1_year"><?php echo $child['other_foods_at_1_year']?'Yes':'No'; ?><td class="editable"data-column="weight"><?php echo htmlspecialchars($child['weight']); ?>kg<td class="editable"data-column="sex"><?php echo htmlspecialchars($child['sex']); ?><td class="editable"data-column="health_medical_officer_division"><?php echo htmlspecialchars($child['health_medical_officer_division']); ?><td class="editable"data-column="family_health_medical_officer_division"><?php echo htmlspecialchars($child['family_health_medical_officer_division']); ?><td class="editable"data-column="started_feeding_other_foods_4m"><?php echo $child['started_feeding_other_foods_4m']?'Yes':'No'; ?><td class="editable"data-column="started_feeding_other_foods_6m"><?php echo $child['started_feeding_other_foods_6m']?'Yes':'No'; ?><td class="actions"><i class="fas edit fa-edit"></i> <i class="fas delete fa-trash-alt"data-id="<?php echo $child['child_id']; ?>"></i></tr><?php endforeach; ?></table></div></div><div class="modal-overlay"></div><div class="modal"id="editChildModal"><div class="card modal-content"><h2>UPDATE CHILD</h2><form id="editChildForm"><input id="edit-child-id"type="hidden"><div class="form-group"><label>Name :</label> <span class="bold-text"id="edit-child-name"></span></div><div class="form-group"><label>Health Medical Officer Division</label> <input id="edit-health-division"class="form-control"></div><div class="form-group"><label>Family Health Medical Officer Division</label> <input id="edit-family-health-division"class="form-control"></div><div class="form-group"><label>Supplementary Regional Record Number</label> <input id="edit-supplementary-record"class="form-control"></div><div class="form-group"><label>Grama Niladhari Record Number</label> <input id="edit-gn-record"class="form-control"></div><div class="form-group"><label>Is only Breastfeeding at 2 months?</label><div class="radio-group"><div class="radio-option"><input id="breast2-yes"type="radio"name="breast2"> <label for="breast2-yes">Yes</label></div><div class="radio-option"><input id="breast2-no"type="radio"name="breast2"> <label for="breast2-no">No</label></div></div></div><div class="form-group"><label>Is only Breastfeeding at 4 months?</label><div class="radio-group"><div class="radio-option"><input id="breast4-yes"type="radio"name="breast4"> <label for="breast4-yes">Yes</label></div><div class="radio-option"><input id="breast4-no"type="radio"name="breast4"> <label for="breast4-no">No</label></div></div></div><div class="button-group"><button class="btn btn-cancel"type="button"id="cancelEdit">Cancel</button> <button class="btn btn-save"type="submit">Save</button></div></form></div></div><script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script>document.addEventListener("DOMContentLoaded", function () {
    let userRole = "<?php echo $_SESSION['user_role']?? ''; ?>"; // Get role from PHP session

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
});</script>