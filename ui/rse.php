<?php
include_once 'connectdb.php';
session_start();

// Only Admin can access
if (!isset($_SESSION['useremail']) || $_SESSION['useremail'] == "" || ($_SESSION['role'] ?? "") == "User") {
    header('location:../index.php');
    exit;
}


/* SUBMIT RSE */
if (isset($_POST['submit_rse'])) {

    $request_no     = $_POST['request_no'] ?? '';
    $type           = $_POST['office_or_org'] ?? '';
    $office_org_id  = $_POST['office_org_id'] ?? '';
    $contact_no     = $_POST['contact_number'] ?? '';
    $address        = $_POST['address'] ?? '';
    $date_of_filing = $_POST['date_of_filing'] ?? '';
    $event_name     = $_POST['event_name'] ?? '';
    $start          = $_POST['start_datetime'] ?? '';
    $end            = $_POST['end_datetime'] ?? '';
    $created_at     = date('Y-m-d H:i:s');

    // Get office/org name
    if ($type === 'office') {
        $nameStmt = $pdo->prepare("SELECT office_name FROM tbl_office WHERE id=?");
    } else {
        $nameStmt = $pdo->prepare("SELECT org_name FROM tbl_organization WHERE id=?");
    }
    $nameStmt->execute([$office_org_id]);
    $office_org_name = $nameStmt->fetchColumn();

    /* INSERT HEADER */
    $stmth = $pdo->prepare("
        INSERT INTO rse_header
        (request_no, requesting_office, contact_no, address, date_of_filing, event_name, start_datetime, end_datetime, created_at)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $stmth->execute([
        $request_no,
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


    /* INSERT ITEMS */
    if (!empty($_POST['item_description'])) {

        foreach ($_POST['item_description'] as $i => $property_id) {

            if ($property_id == "") continue;

            $qty = $_POST['quantity'][$i];
            $borrowed_from = $_POST['borrowed_from'][$i];

            $stmti = $pdo->prepare("
                INSERT INTO rse_items
                (rse_id, property_id, quantity, borrowed_from)
                VALUES (?,?,?,?)
            ");

            $stmti->execute([
                $rse_id,
                $property_id,
                $qty,
                $borrowed_from
            ]);

            // decrease stock
            $stmtUpdate = $pdo->prepare("
                UPDATE tbl_property
                SET quantity = quantity - ?
                WHERE property_id = ?
            ");

            $stmtUpdate->execute([$qty,$property_id]);

        }
    }

    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit;
}

include_once "header.php";

/* FETCH OFFICES AND ORGANIZATIONS */
$stmtoff = $pdo->prepare("SELECT id, office_name, address AS oic_name FROM tbl_office WHERE parent_id IS NOT NULL AND parent_id != 0 AND is_archived = 0 ORDER BY office_name ASC");
$stmtoff->execute();
$offices = $stmtoff->fetchAll(PDO::FETCH_ASSOC);

$stmtorg = $pdo->prepare("SELECT id, org_name, president AS oic_name FROM tbl_organization WHERE is_archived = 0 ORDER BY org_name ASC");
$stmtorg->execute();
$organizations = $stmtorg->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
.invalid-qty{
border:2px solid red;
background:#ffe6e6;
}

.qty-note{
font-size:0.85em;
color:red;
display:block;
}
</style>


<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            Request for Equipment (RSE)
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-primary card-outline shadow-sm">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>RSE Form</h3>
        </div>
        <div class="card-body">

          <form method="POST" id="rseForm">
            <input type="hidden" name="request_no" value="<?= date('YmdHis'); ?>">

            <div class="row">
              <!-- LEFT COLUMN: REQUESTER INFO -->
              <div class="col-md-6 border-right">
                <h5 class="text-primary mb-3"><i class="fas fa-building mr-2"></i>Requester Information</h5>
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-users mr-1"></i> Office / Organization</label>
                      <select name="office_or_org" id="officeOrOrg" class="form-control" required>
                        <option value="">-- Select --</option>
                        <option value="office">Office</option>
                        <option value="organization">Organization</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-signature mr-1"></i> Name</label>
                      <select name="office_org_id" id="officeOrgSelect" class="form-control" required>
                        <option value="">-- Select --</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label><i class="fas fa-user-tie mr-1"></i> President / OIC</label>
                  <input type="text" id="president_oic" class="form-control" readonly placeholder="Select Office/Organization first">
                </div>

                <div class="form-group">
                  <label><i class="fas fa-phone mr-1"></i> Contact No.</label>
            <input type="text" name="contact_number" id="contact_number" class="form-control contact-mask" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="09xxxxxxxxx">
                </div>

                <div class="form-group">
                  <label><i class="fas fa-map-marker-alt mr-1"></i> Address</label>
                  <input type="text" name="address" class="form-control" value="Wawandue Subic, Zambales" required>
                </div>
              </div>

              <!-- RIGHT COLUMN: EVENT DETAILS -->
              <div class="col-md-6 pl-md-4">
                <h5 class="text-primary mb-3"><i class="fas fa-calendar-alt mr-2"></i>Event Details</h5>
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-calendar-day mr-1"></i> Date of Filing</label>
                      <input type="date" name="date_of_filing" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-star mr-1"></i> Event Name</label>
                      <input type="text" name="event_name" class="form-control" placeholder="Enter Event Name" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-clock mr-1"></i> Start Date & Time</label>
                      <input type="datetime-local" name="start_datetime" class="form-control" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><i class="fas fa-history mr-1"></i> End Date & Time</label>
                      <input type="datetime-local" name="end_datetime" class="form-control" required>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <h5 class="text-primary mb-3"><i class="fas fa-boxes mr-2"></i>Equipment Borrowed</h5>
            <div class="table-responsive">
              <table class="table table-striped table-bordered" id="rse_table">
                <thead class="bg-light">
                  <tr>
                    <th width="120"><i class="fas fa-sort-amount-up mr-1"></i> Qty</th>
                    <th><i class="fas fa-info-circle mr-1"></i> Item Name</th>
                    <th width="250"><i class="fas fa-building mr-1"></i> Borrowed From</th>
                    <th width="60" class="text-center"><i class="fas fa-cog"></i></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <input type="number" name="quantity[]" class="form-control" min="1" value="1" placeholder="Qty" required readonly>
                      <small class="qty-note"></small>
                    </td>
                    <td>
                      <select name="item_description[]" class="form-control description-select" required disabled>
                        <option value="">Select Office First</option>
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
                    <td class="text-center">
                      <button type="button" class="btn btn-danger btn-sm removeRow">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-3">
              <button type="button" id="addRow" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-plus mr-1"></i> Add Item
              </button>
            </div>

            <hr class="my-4">

            <div class="text-right">
              <button type="submit" name="submit_rse" class="btn btn-primary btn-lg px-5 shadow-sm">
                <i class="fas fa-save mr-2"></i> Submit Request
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

const offices = <?= json_encode($offices) ?>;
const organizations = <?= json_encode($organizations) ?>;


// populate office/org
document.getElementById("officeOrOrg").addEventListener("change", function(){

const type = this.value;

const select = document.getElementById("officeOrgSelect");

select.innerHTML = '<option value="">-- Select --</option>';

let list = type === 'office' ? offices : organizations;

list.forEach(item=>{
let opt = document.createElement("option");
opt.value = item.id;
opt.text = type === "office" ? item.office_name : item.org_name;
opt.dataset.oic = item.oic_name;
select.appendChild(opt);
});
});

// Update President/OIC field when selection changes
document.getElementById("officeOrgSelect").addEventListener("change", function(){
  const selected = this.options[this.selectedIndex];
  document.getElementById("president_oic").value = selected.dataset.oic || "";
});


// ADD ROW
document.getElementById("addRow").onclick = function(){

let tbody = document.querySelector("#rse_table tbody");

let row = tbody.querySelector("tr").cloneNode(true);

row.querySelectorAll("input").forEach(i=>{
    if(i.name === "quantity[]"){
        i.value = "1";
    } else {
        i.value = "";
    }
});

let descSelect = row.querySelector(".description-select");

descSelect.innerHTML="<option value=''>Select Office First</option>";

descSelect.disabled=true;

row.querySelector(".office-select").value="";

row.querySelector(".qty-note").innerText="";

row.querySelector("input").classList.remove("invalid-qty");

tbody.appendChild(row);

};


// REMOVE ROW
document.addEventListener("click", function(e){

if(e.target.classList.contains("removeRow")){

let rows = document.querySelectorAll("#rse_table tbody tr");

if(rows.length>1){

e.target.closest("tr").remove();

}

}

});


// LOAD ITEMS
document.addEventListener("change", function(e){

if(e.target.classList.contains("office-select")){

let row = e.target.closest("tr");

let descSelect = row.querySelector(".description-select");

let officeId = e.target.value;

descSelect.disabled=true;

descSelect.innerHTML="<option>Loading...</option>";

if(officeId){

fetch("get_itemris.php?office_id="+officeId)

.then(res=>res.json())

.then(data=>{

descSelect.innerHTML="<option value=''>Select Item</option>";

data.forEach(item=>{
let opt=document.createElement("option");
opt.value=item.property_id;
opt.text=item.item_name + (item.description ? " (" + item.description + ")" : "") + " - " + item.inventory_no;
opt.dataset.available=item.quantity;
descSelect.appendChild(opt);
});

descSelect.disabled=false;

});

}

}


if(e.target.classList.contains("description-select")){

let row=e.target.closest("tr");

validateQuantity(row);

}

});


// VALIDATE QTY
document.addEventListener("input", function(e){

if(e.target.name==="quantity[]"){

let row=e.target.closest("tr");

validateQuantity(row);

}

});


function validateQuantity(row){

let qtyInput=row.querySelector("input[name='quantity[]']");

let selected=row.querySelector(".description-select").selectedOptions[0];

let available=selected?.dataset.available;

let note=row.querySelector(".qty-note");

if(available){

if(parseInt(qtyInput.value)>parseInt(available)){

qtyInput.classList.add("invalid-qty");

note.innerText="Max available: "+available;

}else{

qtyInput.classList.remove("invalid-qty");

note.innerText="";

}

}

}


// PREVENT SUBMIT
document.getElementById("rseForm").addEventListener("submit",function(e){

if(document.querySelectorAll(".invalid-qty").length>0){

        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Invalid Quantity',
            text: 'Please fix quantities exceeding available stock.',
            confirmButtonColor: '#d33'
        });

}

});

</script>

<?php
if(isset($_GET['success']) && $_GET['success'] == 1) {
?>
<script>
Swal.fire({
    icon: 'success',
    title: 'RSE Submitted',
    text: 'The RSE has been successfully saved!',
    timer: 3000,
    showConfirmButton: false
});
</script>
<?php
}
?>

<?php include_once "footer.php"; ?>