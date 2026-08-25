<?php
include_once 'connectdb.php';
session_start();

// ================= ADMIN CHECK =================
if (!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin") {
    header('location:../index.php');
    exit;
}

include_once "header.php";

/* ================= FETCH OFFICES & ORGANIZATIONS ================= */
$stmtoff = $pdo->prepare("
    SELECT t1.id, t1.office_name, t1.max_capacity, t2.office_name AS parent_name 
    FROM tbl_office t1 
    LEFT JOIN tbl_office t2 ON t1.parent_id = t2.id 
    WHERE t1.parent_id IS NOT NULL AND t1.parent_id != 0 AND t1.is_archived = 0 
    ORDER BY t1.office_name ASC
");
$stmtoff->execute();
$offices = $stmtoff->fetchAll(PDO::FETCH_ASSOC);

$stmtorg = $pdo->prepare("SELECT id, org_name FROM tbl_organization WHERE is_archived = 0 ORDER BY org_name ASC");
$stmtorg->execute();
$organizations = $stmtorg->fetchAll(PDO::FETCH_ASSOC);

/* ================= FETCH ALL PROPERTIES ================= */
$stmtProp = $pdo->prepare("
    SELECT p.property_id, p.item_name, p.description, p.inventory_no, p.serial_no, p.quantity, o.office_name 
    FROM tbl_property p
    LEFT JOIN tbl_office o ON p.office_id = o.id
    WHERE p.is_archived = 0 
    ORDER BY p.item_name ASC
");
$stmtProp->execute();
$properties = $stmtProp->fetchAll(PDO::FETCH_ASSOC);

/* ================= AUTO GENERATE REQUEST NO ================= */
$stmtCount = $pdo->query("SELECT COUNT(*) FROM facility_header");
$nextId = $stmtCount->fetchColumn() + 1;
$request_no = "FAC-" . date('Ymd') . "-" . str_pad($nextId, 4, "0", STR_PAD_LEFT);

/* ================= SUBMIT FACILITY REQUEST ================= */
if (isset($_POST['submit_rse'])) {

    $request_no       = $_POST['request_no'] ?? '';
    $type             = $_POST['office_or_org'];
    $office_org_id    = intval($_POST['office_org_id']);
    $first_name       = $_POST['first_name'];
    $last_name        = $_POST['last_name'];
    $mi               = $_POST['mi'];
    $position         = $_POST['position_designation'];
    $contact_no       = $_POST['contact_no'];
    $address          = $_POST['address'];
    $date_of_filing   = $_POST['date_of_filing'];
    $event_name       = $_POST['event_name'];
    $num_participants = $_POST['num_participants'];
    
    // Combine date and time
    $start_date       = $_POST['start_date'];
    $start_time       = $_POST['start_time'];
    $end_date         = $_POST['end_date'];
    $end_time         = $_POST['end_time'];
    
    $start            = $start_date . ' ' . $start_time;
    $end              = $end_date . ' ' . $end_time;
    
    $created_at       = date('Y-m-d H:i:s');

    // Get office/org name
    $office_org_name = '';
    if ($type === 'office') {
        $nameStmt = $pdo->prepare("SELECT office_name FROM tbl_office WHERE id=?");
        $nameStmt->execute([intval($office_org_id)]);
        $office_org_name = $nameStmt->fetchColumn();
    } else if ($type === 'organization') {
        $nameStmt = $pdo->prepare("SELECT org_name FROM tbl_organization WHERE id=?");
        $nameStmt->execute([intval($office_org_id)]);
        $office_org_name = $nameStmt->fetchColumn();
    }

    // ================= CHECK FOR FACILITY AVAILABILITY =================
    if (!empty($_POST['facility_name'])) {
        $conflict = false;
        $conflict_facility = '';
        $maxCapacity = PHP_INT_MAX; // For participant check

        $selected_facility_id = intval($_POST['facility_name']); // Cast to integer
        if ($selected_facility_id !== 0) { // Only proceed if valid ID

            // Check facility booking conflict (exclude Cancelled requests)
            $checkStmt = $pdo->prepare("
                SELECT fh.request_no, fh.event_name
                FROM facility_items fi
                JOIN facility_header fh ON fi.facility_id = fh.id
                WHERE fi.office_id = ?
                AND (fh.status != 'Cancelled' OR fh.status IS NULL)
                AND (
                    (fh.start_datetime <= ? AND fh.end_datetime >= ?) OR
                    (fh.start_datetime <= ? AND fh.end_datetime >= ?) OR
                    (fh.start_datetime >= ? AND fh.end_datetime <= ?)
                )
            ");
            $checkStmt->execute([
                $selected_facility_id,
                $start, $start,
                $end, $end,
                $start, $end
            ]);

            if ($checkStmt->rowCount() > 0) {
                $conflict = true;
                $conflict_facility_stmt = $pdo->prepare("SELECT office_name FROM tbl_office WHERE id=?");
                $conflict_facility_stmt->execute([$selected_facility_id]);
                $conflict_facility = $conflict_facility_stmt->fetchColumn();
            }

            // Get max capacity for participant check
            $capStmt = $pdo->prepare("SELECT max_capacity FROM tbl_office WHERE id=?");
            $capStmt->execute([$selected_facility_id]);
            $cap = $capStmt->fetchColumn();
            if ($cap < $maxCapacity) $maxCapacity = $cap;
        }

        // Conflict check
        if ($conflict) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                Swal.fire({
                    icon:'error', 
                    title:'Facility Already Booked', 
                    text:'The facility \"".htmlspecialchars($conflict_facility)."\" is already booked during the requested time.'
                }).then(()=>{history.back();});
            </script>";
            exit;
        }

        // Participant number check
        if ($maxCapacity > 0 && $num_participants > $maxCapacity) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                Swal.fire({
                    icon:'error',
                    title:'Exceeds Capacity',
                    text:'Number of participants cannot exceed the maximum capacity of selected facility/facilities ({$maxCapacity}).'
                }).then(()=>{history.back();});
            </script>";
            exit;
        }
    }

    /* ================= INSERT HEADER ================= */
    $stmth = $pdo->prepare("
        INSERT INTO facility_header
        (request_no, requesting_office, first_name, last_name, mi, position_designation, contact_no, address, date_of_filing, event_name, num_participants, start_datetime, end_datetime, created_at, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmth->execute([
        $request_no,
        $office_org_name,
        $first_name,
        $last_name,
        $mi,
        $position,
        $contact_no,
        $address,
        $date_of_filing,
        $event_name,
        $num_participants,
        $start,
        $end,
        $created_at,
        'Pending'
    ]);

    $facility_request_id = $pdo->lastInsertId();

    /* ================= INSERT FACILITIES ================= */
    if (!empty($_POST['facility_name'])) {
        $selected_facility_id = intval($_POST['facility_name']); // Cast to integer
        if ($selected_facility_id !== 0) { // Only proceed if valid ID
            $stmti = $pdo->prepare("
                INSERT INTO facility_items
                (facility_id, office_id)
                VALUES (?,?)
            ");
            $stmti->execute([
                $facility_request_id,
                $selected_facility_id
            ]);
        }
    }

    /* ================= INSERT EQUIPMENT & CREATE RSE SLIP ================= */
    if (!empty($_POST['eq_name'])) {
        $eq_names = $_POST['eq_name']; // This will now be property_id
        $eq_quants = $_POST['eq_qty'];
        $eq_descs = $_POST['eq_desc'];

        $rse_id = null;

        for ($i = 0; $i < count($eq_names); $i++) {
            if (!empty($eq_names[$i])) {
                $property_id = intval($eq_names[$i]);
                $qty = intval($eq_quants[$i]);
                $desc = $eq_descs[$i];

                // Get item name for facility_equipment
                $stmtGetName = $pdo->prepare("SELECT item_name FROM tbl_property WHERE property_id = ?");
                $stmtGetName->execute([$property_id]);
                $item_name = $stmtGetName->fetchColumn();

                // 1. Insert into facility_equipment
                $stmtEq = $pdo->prepare("INSERT INTO facility_equipment (facility_request_id, quantity, item_name, description) VALUES (?,?,?,?)");
                $stmtEq->execute([
                    $facility_request_id,
                    $qty,
                    $item_name,
                    $desc
                ]);

                // 2. Create RSE Header if not exists
                if ($rse_id === null) {
                    $rse_request_no = "RSE-" . date('YmdHis');
                    $stmtrh = $pdo->prepare("
                        INSERT INTO rse_header 
                        (request_no, requesting_office, contact_no, address, date_of_filing, event_name, start_datetime, end_datetime, created_at)
                        VALUES (?,?,?,?,?,?,?,?,?)
                    ");
                    $stmtrh->execute([
                        $rse_request_no,
                        $office_org_name,
                        $contact_no,
                        $address,
                        $date_of_filing,
                        $event_name,
                        $start,
                        $end,
                        $created_at
                    ]);
                    $rse_id = $pdo->lastInsertId();
                }

                // 3. Insert into rse_items
                $stmti = $pdo->prepare("
                    INSERT INTO rse_items
                    (rse_id, property_id, quantity, borrowed_from)
                    VALUES (?,?,?,?)
                ");
                $stmti->execute([
                    $rse_id,
                    $property_id,
                    $qty,
                    $selected_facility_id // Borrowed from the facility selected
                ]);

                // 4. Update Stock
                $stmtUpdate = $pdo->prepare("
                    UPDATE tbl_property
                    SET quantity = quantity - ?
                    WHERE property_id = ?
                ");
                $stmtUpdate->execute([$qty, $property_id]);
            }
        }
    }

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        Swal.fire({icon:'success', title:'Facility Request Submitted', text:'The request has been successfully saved!'}).then(()=>{window.location='facility_record.php';});
    </script>";
}
?>

<div class="content-wrapper">
  <div class="content-header border-bottom mb-4">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark font-weight-bold text-uppercase">
            <i class="fas fa-plus-circle mr-2 text-primary"></i>Submit Facility Request
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 pt-4 px-4">
          <h5 class="card-title text-muted mb-0">Request Information Details</h5>
        </div>
        <div class="card-body p-4">
          <form method="POST">
            <input type="hidden" name="request_no" value="<?= htmlspecialchars($request_no) ?>">
            <input type="hidden" name="date_of_filing" value="<?= date('Y-m-d') ?>">
            
            <div class="row">
              <!-- LEFT COLUMN -->
              <div class="col-md-6 border-right pr-md-4">
                <h5 class="text-primary mb-3">Requesting Information</h5>
                
                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Request Type</label>
                  <select class="form-control form-control-sm shadow-sm" name="office_or_org" id="office_or_org" onchange="toggleOfficeOrg()" required>
                    <option value="">-- Select Type --</option>
                    <option value="office">Office</option>
                    <option value="organization">Organization</option>
                  </select>
                </div>

                <div class="form-group mb-3" id="office_div" style="display:none;">
                  <label class="small text-dark font-weight-bold mb-1">Select Office</label>
                  <select class="form-control form-control-sm shadow-sm" name="office_org_id" id="office_org_id_office">
                    <option value="">-- Select Office --</option>
                    <?php foreach($offices as $office): 
                      // Exclude Classrooms and Computer Laboratories from Requesting Office selection
                      $p_name = strtolower($office['parent_name'] ?? '');
                      $o_name = strtolower($office['office_name'] ?? '');
                      $is_classroom_or_lab = ($p_name == 'classroom' || strpos($p_name, 'computer laboratory') !== false || strpos($p_name, 'computer laboratories') !== false ||
                          $o_name == 'classroom' || strpos($o_name, 'computer laboratory') !== false || strpos($o_name, 'computer laboratories') !== false);
                      
                      if ($is_classroom_or_lab) continue;
                    ?>
                      <option value="<?= $office['id'] ?>" data-max="<?= $office['max_capacity'] ?>"><?= htmlspecialchars($office['office_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group mb-3" id="org_div" style="display:none;">
                  <label class="small text-dark font-weight-bold mb-1">Select Organization</label>
                  <select class="form-control form-control-sm shadow-sm" name="office_org_id" id="office_org_id_organization">
                    <option value="">-- Select Organization --</option>
                    <?php foreach($organizations as $org): ?>
                      <option value="<?= $org['id'] ?>"><?= htmlspecialchars($org['org_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Address</label>
                  <input type="text" name="address" class="form-control form-control-sm shadow-sm" value="Kolehiyo Ng Subic, Wawandue Subic, Zambales" required>
                </div>

                <h5 class="text-primary mt-4 mb-3">Contact Person Details</h5>
                <div class="row">
                  <div class="col-md-5">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">First Name</label>
                      <input type="text" name="first_name" class="form-control form-control-sm shadow-sm" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">Last Name</label>
                      <input type="text" name="last_name" class="form-control form-control-sm shadow-sm" required>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">M.I.</label>
                      <input type="text" name="mi" class="form-control form-control-sm shadow-sm" maxlength="5">
                    </div>
                  </div>
                </div>

                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Position/Designation</label>
                  <input type="text" name="position_designation" class="form-control form-control-sm shadow-sm" required>
                </div>

                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Contact No.</label>
                  <input type="text" name="contact_no" class="form-control form-control-sm shadow-sm" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="09xxxxxxxxx" required>
                </div>
              </div>

              <!-- RIGHT COLUMN -->
              <div class="col-md-6 pl-md-4">
                <h5 class="text-primary mb-3">Event & Facility Details</h5>
                
                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Event Name</label>
                  <input type="text" class="form-control form-control-sm shadow-sm" name="event_name" required>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">Start Date</label>
                      <input type="date" class="form-control form-control-sm shadow-sm" name="start_date" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">Start Time</label>
                      <input type="time" class="form-control form-control-sm shadow-sm" name="start_time" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">Ending Date</label>
                      <input type="date" class="form-control form-control-sm shadow-sm" name="end_date" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label class="small text-dark font-weight-bold mb-1">Ending Time</label>
                      <input type="time" class="form-control form-control-sm shadow-sm" name="end_time" required>
                    </div>
                  </div>
                </div>

                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Number of Participants</label>
                  <input type="number" class="form-control form-control-sm shadow-sm" name="num_participants" id="num_participants" required>
                </div>

                <div class="form-group mb-3">
                  <label class="small text-dark font-weight-bold mb-1">Facility to use</label>
                  <select class="form-control form-control-sm shadow-sm" name="facility_name" id="facility_select" required>
                    <option value="">-- Select Facility --</option>
                    <?php foreach($offices as $fac): 
                      $p_name = strtolower($fac['parent_name'] ?? '');
                      $o_name = strtolower($fac['office_name'] ?? '');
                      $is_lab = ($p_name == 'classroom' || strpos($p_name, 'computer laboratory') !== false || strpos($p_name, 'computer laboratories') !== false ||
                                 $o_name == 'classroom' || strpos($o_name, 'computer laboratory') !== false || strpos($o_name, 'computer laboratories') !== false);
                    ?>
                      <option value="<?= $fac['id'] ?>" data-max="<?= $fac['max_capacity'] ?>" data-lab="<?= $is_lab ? '1' : '0' ?>"><?= htmlspecialchars($fac['office_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <small id="capacity_info" class="form-text text-danger"></small>
                </div>
              </div>
            </div>

            <!-- EQUIPMENT SECTION -->
            <div id="equipmentSection" style="display:none;">
              <hr class="my-4">
              <h5 class="text-primary mb-3">Facilities Equipment Needed</h5>
              <div class="table-responsive">
                <table class="table table-sm table-bordered" id="equipmentTable">
                  <thead class="bg-light">
                    <tr>
                      <th width="15%">Quantity</th>
                      <th width="40%">Item Name</th>
                      <th width="40%">Remarks</th>
                      <th width="5%"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Rows will be added dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-info mt-2" onclick="addRow()"><i class="fas fa-plus"></i> Add Row</button>

            <hr class="my-4">
            <div class="d-flex justify-content-end">
              <a href="facility_record.php" class="btn btn-light btn-lg px-4 mr-2 border shadow-sm">
                <i class="fas fa-times mr-2"></i>Cancel
              </a>
              <button type="submit" name="submit_rse" class="btn btn-primary btn-lg px-5 shadow">
                <i class="fas fa-save mr-2"></i>Submit Request
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function toggleOfficeOrg() {
    var type = document.getElementById('office_or_org').value;
    var officeDiv = document.getElementById('office_div');
    var orgDiv = document.getElementById('org_div');
    var officeSelect = document.getElementById('office_org_id_office');
    var orgSelect = document.getElementById('office_org_id_organization');
    var facilitySelect = document.getElementById('facility_select');

    // Filter Facility dropdown based on Request Type
    var facilityOptions = facilitySelect.options;
    for (var i = 0; i < facilityOptions.length; i++) {
        var option = facilityOptions[i];
        if (option.value === "") continue;

        if (type === 'office' && option.getAttribute('data-lab') === '1') {
            option.style.display = 'none';
            if (facilitySelect.value === option.value) {
                facilitySelect.value = "";
            }
        } else {
            option.style.display = 'block';
        }
    }

    if (type === 'office') {
        officeDiv.style.display = 'block';
        orgDiv.style.display = 'none';
        officeSelect.setAttribute('name', 'office_org_id');
        orgSelect.removeAttribute('name');
    } else if (type === 'organization') {
        officeDiv.style.display = 'none';
        orgDiv.style.display = 'block';
        orgSelect.setAttribute('name', 'office_org_id');
        officeSelect.removeAttribute('name');
    } else {
        officeDiv.style.display = 'none';
        orgDiv.style.display = 'none';
    }
}

function addRow() {
    document.getElementById('equipmentSection').style.display = 'block';
    var table = document.getElementById("equipmentTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow();
    
    // Generate the options HTML from PHP array
    var options = '<option value="">-- Select Item --</option>';
    <?php foreach($properties as $prop): 
        $label = ($prop['item_name'] ?? '') . 
                 ' (' . ($prop['description'] ?? '') . ') ' . 
                 ' (' . ($prop['serial_no'] ?? '') . ') - [' . 
                 ($prop['office_name'] ?? 'Unassigned') . ']';
        $label_js = json_encode($label);
    ?>
      options += '<option value="<?= $prop['property_id'] ?>" data-available="<?= $prop['quantity'] ?>">' + <?= $label_js ?> + '</option>';
    <?php endforeach; ?>

    newRow.innerHTML = '<td><input type="number" name="eq_qty[]" class="form-control form-control-sm qty-input" value="1" readonly></td>' +
                       '<td><select name="eq_name[]" class="form-control form-control-sm select2 item-select" required>' + options + '</select></td>' +
                       '<td><input type="text" name="eq_desc[]" class="form-control form-control-sm"></td>' +
                       '<td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>';
    
    // Initialize Select2 on the new select element if Select2 is used
    if ($.fn.select2) {
        $(newRow).find('.select2').select2();
    }
}

function removeRow(btn) {
    var row = btn.parentNode.parentNode;
    var tbody = row.parentNode;
    tbody.removeChild(row);
    
    // Hide section if no rows left
    if (tbody.rows.length === 0) {
        document.getElementById('equipmentSection').style.display = 'none';
    }
}

// Quantity Validation
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-select')) {
        validateRow(e.target.closest('tr'));
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input')) {
        validateRow(e.target.closest('tr'));
    }
});

function validateRow(row) {
    var select = row.querySelector('.item-select');
    var qtyInput = row.querySelector('.qty-input');
    var selectedOption = select.options[select.selectedIndex];
    var available = parseInt(selectedOption.getAttribute('data-available')) || 0;
    var requested = parseInt(qtyInput.value) || 0;

    if (requested > available) {
        qtyInput.setCustomValidity('Exceeds available stock (' + available + ')');
        qtyInput.classList.add('is-invalid');
    } else {
        qtyInput.setCustomValidity('');
        qtyInput.classList.remove('is-invalid');
    }
}

var facilitySelect = document.getElementById('facility_select');
var numInput = document.getElementById('num_participants');
var capacityInfo = document.getElementById('capacity_info');

facilitySelect.addEventListener('change', function() {
    var selectedOption = this.selectedOptions[0];
    if (!selectedOption || selectedOption.value === "") {
        numInput.removeAttribute('max');
        capacityInfo.innerText = '';
        return;
    }
    var maxCapacity = parseInt(selectedOption.dataset.max);
    if (!isNaN(maxCapacity) && maxCapacity > 0) {
        numInput.setAttribute('max', maxCapacity);
        capacityInfo.innerText = "Max capacity for this facility: " + maxCapacity;
    } else {
        numInput.removeAttribute('max');
        capacityInfo.innerText = '';
    }
});
</script>

<?php include_once "footer.php"; ?>
