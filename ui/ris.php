<?php
include_once 'connectdb.php';
session_start();

// Only Admin access
if (!isset($_SESSION['useremail']) || $_SESSION['role'] === "User") {
    header('location:../index.php');
    exit;
}

/* =============================== FETCH OFFICES =============================== */
$stmtoff = $pdo->prepare("SELECT id, office_name FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC");
$stmtoff->execute();
$offices = $stmtoff->fetchAll(PDO::FETCH_ASSOC);

/* =============================== AUTO GENERATE REQUEST NO =============================== */
$stmtCount = $pdo->query("SELECT COUNT(*) FROM ris_header");
$nextId = $stmtCount->fetchColumn() + 1;
$request_no = "RIS-" . date('Ymd') . "-" . str_pad($nextId, 4, "0", STR_PAD_LEFT);

/* =============================== SAVE RIS =============================== */
if (isset($_POST['submit_ris'])) {
    $request_no = $_POST['request_no'] ?? '';
    $last   = $_POST['last_name']  ?? '';
    $first  = $_POST['first_name'] ?? '';
    $mi     = $_POST['mi_name']    ?? '';
    $position = $_POST['position'] ?? '';
    $contact  = $_POST['cp_number'] ?? '';
    $event    = $_POST['event_name'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $start_dt = $_POST['start_datetime'] ?? '';
    $end_dt   = $_POST['end_datetime'] ?? '';

    $errors = [];

    // Validate quantities vs available stock
    if (!empty($_POST['item_description'])) {
        foreach ($_POST['item_description'] as $i => $property_id) {
            if ($property_id == "") continue;
            $requested_qty = $_POST['quantity'][$i] ?? 0;

            $stmtCheck = $pdo->prepare("SELECT quantity FROM tbl_property WHERE property_id = ? AND is_archived = 0");
            $stmtCheck->execute([$property_id]);
            $available = $stmtCheck->fetchColumn();

            if ($requested_qty > $available) {
                $errors[] = "Quantity for item #".($i+1)." exceeds available stock (Available: $available, Requested: $requested_qty).";
            }
        }
    }

    if (empty($errors)) {
        // Insert RIS Header
        $stmth = $pdo->prepare("
            INSERT INTO ris_header 
            (request_no, last_name, first_name, mi_name, cp_number, position, event_name, event_date, start_datetime, end_datetime)
            VALUES (:reqno, :last, :first, :mi, :cp, :pos, :event, :edate, :startdt, :enddt)
        ");
        $stmth->execute([
            ':reqno'   => $request_no,
            ':last'    => $last,
            ':first'   => $first,
            ':mi'      => $mi,
            ':cp'      => $contact,
            ':pos'     => $position,
            ':event'   => $event,
            ':edate'   => $event_date,
            ':startdt' => $start_dt,
            ':enddt'   => $end_dt
        ]);

        $ris_id = $pdo->lastInsertId();

        // Insert items & decrease stock
        foreach ($_POST['item_description'] as $i => $property_id) {
            if ($property_id == "") continue;

            $stmti = $pdo->prepare("INSERT INTO ris_items (ris_id, property_id, quantity, borrowed_from) VALUES (?,?,?,?)");
            $stmti->execute([
                $ris_id,
                $property_id,
                $_POST['quantity'][$i],
                $_POST['borrowed_from'][$i]
            ]);

            $stmtUpdate = $pdo->prepare("UPDATE tbl_property SET quantity = quantity - ? WHERE property_id = ?");
            $stmtUpdate->execute([$_POST['quantity'][$i], $property_id]);
        }

        // Log activity
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
        $stmtLog->execute([$_SESSION['userid'], "Created Requisition & Issue Slip (RIS): " . $request_no]);

        // 🔹 Redirect BEFORE any output
        header("Location: ".$_SERVER['PHP_SELF']."?success=1");
        exit;
    } else {
        // If errors exist, show SweetAlert after including header
        $errorJS = "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        $errorJS .= "<script>Swal.fire({icon:'error', title:'Quantity Error', html:`".implode('<br>', $errors)."`});</script>";
    }
}

// =============================== HEADER ===============================
include_once "header.php";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
.content-wrapper { margin-left: 250px; margin-top: 57px; height: calc(100vh - 57px); overflow-y: auto; padding: 20px; }
.card { padding: 20px; margin-bottom: 20px; position: relative; }
.table-responsive { max-height: 400px; overflow-y: auto; margin-top: 10px; }
.form-row { display: flex; gap: 15px; margin-bottom: 15px; }
.form-group { flex: 1; }
.invalid-qty { border: 2px solid red; background-color: #ffe6e6; }
.qty-note { font-size: 0.85em; color:red; display:block; }
.r-btn { position: absolute; right: 20px; top: 20px; }
.printable-area { display: none; }
@media print {
    body * { visibility: hidden; }
    .printable-area, .printable-area * { visibility: visible; }
    .printable-area { position: absolute; left:0; top:0; width:100%; font-family:"Times New Roman"; font-size:12pt; padding:20px; }
    .no-print { display:none; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid black; padding:6px; }
    h2, h4 { text-align:center; }
}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            Requisition & Issue Slip (RIS)
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-primary card-outline shadow-sm">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-file-signature mr-2"></i>RIS Form</h3>
         
        </div>
        <div class="card-body">

          <?php 
          // Show success or error messages
          if(isset($_GET['success'])): ?>
          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
          <script>
          Swal.fire({
              icon: 'success',
              title: 'RIS Submitted',
              text: 'The RIS has been successfully saved!',
              timer: 3000,
              showConfirmButton: false
          });
          </script>
          <?php 
          endif;
          if(isset($errorJS)) echo $errorJS; 
          ?>

          <form method="POST" id="risForm">
            <input type="hidden" name="request_no" value="<?= $request_no ?>">

            <div class="row">
              <!-- LEFT COLUMN: BORROWER INFO -->
              <div class="col-md-6 border-right">
                <h5 class="text-primary mb-3"><i class="fas fa-user-circle mr-2"></i>Borrower Information</h5>
                
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fas fa-user mr-1"></i> Last Name</label>
                      <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fas fa-user mr-1"></i> First Name</label>
                      <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><i class="fas fa-user mr-1"></i> M.I.</label>
                      <input type="text" name="mi_name" class="form-control" placeholder="M.I." required>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-phone mr-1"></i> Contact Number</label>
            <input type="text" name="cp_number" id="contact_number" class="form-control contact-mask" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="09xxxxxxxxx">
                </div>

                <div class="form-group">
                  <label><i class="fas fa-id-card mr-1"></i> Position / Designation</label>
                  <input type="text" name="position" class="form-control" placeholder="Position / Designation" required>
                </div>
              </div>

              <!-- RIGHT COLUMN: EVENT DETAILS -->
              <div class="col-md-6 pl-md-4">
                <h5 class="text-primary mb-3"><i class="fas fa-calendar-check mr-2"></i>Event Details</h5>
                
                <div class="form-group">
                  <label><i class="fas fa-star mr-1"></i> Name of Event</label>
                  <input type="text" name="event_name" class="form-control" placeholder="Enter Event Name" required>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-calendar-alt mr-1"></i> Date of Filing</label>
                  <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-clock mr-1"></i> Date & Time Borrowed</label>
                      <input type="datetime-local" name="start_datetime" class="form-control" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-history mr-1"></i> Date & Time Returned</label>
                      <input type="datetime-local" name="end_datetime" class="form-control" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <h5 class="text-primary mb-3"><i class="fas fa-list-ul mr-2"></i>Items to Borrow</h5>
            <div class="table-responsive">
              <table class="table table-striped table-bordered" id="ris_table">
                <thead class="bg-light">
                  <tr>
                    <th width="120"><i class="fas fa-sort-amount-up mr-1"></i> Qty</th>
                    <th><i class="fas fa-info-circle mr-1"></i> Item Name</th>
                    <th width="250"><i class="fas fa-building mr-1"></i> Borrowed From</th>
                    <th width="60" class="no-print text-center"><i class="fas fa-cog"></i></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <input type="number" name="quantity[]" class="form-control" min="1" value="1" placeholder="Qty" readonly>
                      <small class="qty-note"></small>
                    </td>
                    <td>
                      <select name="item_description[]" class="form-control description-select" required disabled>
                        <option value=''>Select Office First</option>
                      </select>
                    </td>
                    <td>
                      <select name="borrowed_from[]" class="form-control office-select" required>
                        <option value="">Select Office</option>
                        <?php foreach($offices as $off): ?>
                        <option value="<?= $off['id']; ?>"><?= htmlentities($off['office_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td class="no-print text-center">
                      <button type="button" class="btn btn-danger btn-sm removeRow">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              <button type="button" id="addRow" class="btn btn-secondary btn-sm no-print shadow-sm">
                <i class="fas fa-plus mr-1"></i> Add Item
              </button>
            </div>

            <hr class="my-4">

            <div class="text-right">
              <button type="submit" name="submit_ris" class="btn btn-primary btn-lg px-5 shadow-sm">
                <i class="fas fa-save mr-2"></i> Submit RIS
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- PRINTABLE AREA -->
<div id="printableRIS" class="printable-area">
<h2>Requisition & Issue Slip (RIS)</h2>
<p><strong>Name:</strong> <span id="p_borrower_name"></span></p>
<p><strong>Contact:</strong> <span id="p_cp_number"></span></p>
<p><strong>Position:</strong> <span id="p_position"></span></p>
<p><strong>Event:</strong> <span id="p_event_name"></span></p>
<p><strong>Date:</strong> <span id="p_event_date"></span></p>
<p><strong>Start:</strong> <span id="p_start_datetime"></span></p>
<p><strong>End:</strong> <span id="p_end_datetime"></span></p>
<table id="p_items_table">
<thead><tr><th>Qty</th><th>Item Name</th><th>Office</th></tr></thead>
<tbody></tbody>
</table>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
// ADD ROW
document.getElementById("addRow").onclick = function(){
    let tbody = document.querySelector("#ris_table tbody");
    let row = tbody.querySelector("tr").cloneNode(true);
    row.querySelectorAll("input").forEach(i => {
        if(i.name === "quantity[]"){
            i.value = "1";
        } else {
            i.value = "";
        }
    });
    let descSelect = row.querySelector(".description-select");
    descSelect.innerHTML = "<option value=''>Select Office First</option>";
    descSelect.disabled = true;
    row.querySelector(".office-select").value = "";
    row.querySelector(".qty-note").innerText = "";
    row.querySelector("input").classList.remove("invalid-qty");
    tbody.appendChild(row);
};

// REMOVE ROW
document.addEventListener("click", function(e){
    if(e.target.classList.contains("removeRow")){
        let rows = document.querySelectorAll("#ris_table tbody tr");
        if(rows.length > 1) e.target.closest("tr").remove();
    }
});

// OFFICE CHANGE -> LOAD ITEMS
document.addEventListener("change", function(e){
    if(e.target.classList.contains("office-select")){
        let row = e.target.closest("tr");
        let descSelect = row.querySelector(".description-select");
        let officeId = e.target.value;

        descSelect.disabled = true;
        descSelect.innerHTML = "<option>Loading...</option>";

        if(officeId){
            fetch("get_itemris.php?office_id=" + officeId)
            .then(res => res.json())
            .then(data => {
                descSelect.innerHTML = "<option value=''>Select Item</option>";
                data.forEach(item => {
                    let opt = document.createElement("option");
                    opt.value = item.property_id;
                    opt.text = item.item_name + (item.description ? " (" + item.description + ")" : "") + " - " + item.inventory_no;
                    opt.dataset.available = item.quantity;
                    descSelect.appendChild(opt);
                });
                descSelect.disabled = false;
            })
            .catch(err => {
                console.error(err);
                descSelect.innerHTML = "<option value=''>Error loading items</option>";
            });
        } else {
            descSelect.innerHTML = "<option value=''>Select Office First</option>";
        }
    }
    if(e.target.classList.contains("description-select")){
        let row = e.target.closest("tr");
        validateQuantity(row);
    }
});

// QUANTITY VALIDATION
document.addEventListener("input", function(e){
    if(e.target.name === "quantity[]"){
        let row = e.target.closest("tr");
        validateQuantity(row);
    }
});
function validateQuantity(row){
    let qtyInput = row.querySelector("input[name='quantity[]']");
    let selectedOption = row.querySelector(".description-select").selectedOptions[0];
    let available = selectedOption?.dataset.available;
    let note = row.querySelector(".qty-note");

    if(available){
        if(parseInt(qtyInput.value) > parseInt(available)){
            qtyInput.classList.add("invalid-qty");
            note.innerText = `Max available: ${available}`;
        } else {
            qtyInput.classList.remove("invalid-qty");
            note.innerText = "";
        }
    } else {
        qtyInput.classList.remove("invalid-qty");
        note.innerText = "";
    }
}

// PREVENT SUBMIT IF INVALID
document.getElementById("risForm").addEventListener("submit", function(e){
    if(document.querySelectorAll(".invalid-qty").length > 0){
        e.preventDefault();
        Swal.fire({icon:'error', title:'Quantity Error', text:'Please fix all quantities highlighted in red before submitting.'});
    }
});

// PRINT
function printRIS(){
    document.getElementById("p_borrower_name").innerText = document.querySelector("input[name='last_name']").value + ", " + document.querySelector("input[name='first_name']").value + " " + document.querySelector("input[name='mi_name']").value;
    document.getElementById("p_cp_number").innerText = document.querySelector("input[name='cp_number']").value;
    document.getElementById("p_position").innerText = document.querySelector("input[name='position']").value;
    document.getElementById("p_event_name").innerText = document.querySelector("input[name='event_name']").value;
    document.getElementById("p_event_date").innerText = document.querySelector("input[name='event_date']").value;
    document.getElementById("p_start_datetime").innerText = document.querySelector("input[name='start_datetime']").value;
    document.getElementById("p_end_datetime").innerText = document.querySelector("input[name='end_datetime']").value;

    let tbody = document.querySelector("#p_items_table tbody");
    tbody.innerHTML = "";
    document.querySelectorAll("#ris_table tbody tr").forEach(row=>{
        let qty = row.querySelector("input[name='quantity[]']").value;
        let desc = row.querySelector(".description-select").selectedOptions[0]?.text;
        let office = row.querySelector(".office-select").selectedOptions[0]?.text;
        if(desc && desc !== "Select Item"){
            let tr = document.createElement("tr");
            tr.innerHTML = `<td>${qty}</td><td>${desc}</td><td>${office}</td>`;
            tbody.appendChild(tr);
        }
    });
    window.print();
}
</script>

<?php include_once "footer.php"; ?>