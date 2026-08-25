<?php
include_once 'connectdb.php';
session_start();

/* ================= LOGIN CHECK ================= */
if(!isset($_SESSION['useremail']) || ($_SESSION['role'] ?? '') !== "Admin"){
    header("Location: ../index.php");
    exit;
}

/* ================= FETCH OFFICES ================= */
$stmtOffice = $pdo->prepare("
    SELECT id, office_name
    FROM tbl_office
    WHERE parent_id IS NOT NULL AND parent_id != 0
      AND is_archived = 0
    ORDER BY office_name ASC
");
$stmtOffice->execute();
$offices = $stmtOffice->fetchAll(PDO::FETCH_ASSOC);

/* ================= FETCH PROPERTIES FOR SELECTED FROM OFFICE ================= */
$propertyList       = [];
$fromOfficeSelected = intval($_GET['from_office'] ?? $_POST['from_office'] ?? 0);

if($fromOfficeSelected > 0){
    $stmtProperty = $pdo->prepare("
        SELECT property_id, inventory_no, item_name, quantity
        FROM tbl_property
        WHERE office_id = ?
          AND (is_archived = 0 OR is_archived IS NULL)
        ORDER BY item_name ASC
    ");
    $stmtProperty->execute([$fromOfficeSelected]);
    $propertyList = $stmtProperty->fetchAll(PDO::FETCH_ASSOC);
}

/* ================= SAVE PTR ================= */
if(isset($_POST['save_ptr'])){

    $ptr_no        = "PTR-".date("YmdHis");
    $transfer_date = $_POST['transfer_date'];
    $from_office   = intval($_POST['from_office']);
    $to_office     = intval($_POST['to_office']);
    $remarks       = $_POST['remarks'];

    if ($from_office === 0 || $to_office === 0 || $from_office === $to_office) {
        $_SESSION['status']      = "Invalid office selection. 'From Office' and 'To Office' must be different and valid.";
        $_SESSION['status_code'] = "error";
        header("Location: ptr.php");
        exit;
    }

    $insertHeader = $pdo->prepare("
        INSERT INTO ptr_header (ptr_no, transfer_date, from_office, to_office, remarks)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertHeader->execute([$ptr_no, $transfer_date, $from_office, $to_office, $remarks]);
    $ptr_id = $pdo->lastInsertId();

    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Created Property Transfer Receipt (PTR): " . $ptr_no]);

    $property_ids = $_POST['property_id'] ?? [];

    foreach($property_ids as $prop_id){
        $prop_id = intval($prop_id);
        if($prop_id === 0) continue;

        $stmt = $pdo->prepare("SELECT inventory_no, item_name, quantity FROM tbl_property WHERE property_id = ? AND office_id = ? AND is_archived = 0");
        $stmt->execute([$prop_id, $from_office]);
        $prop = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$prop) continue;

        $pdo->prepare("UPDATE tbl_property SET office_id = ? WHERE property_id = ?")->execute([$to_office, $prop_id]);

        $pdo->prepare("
            INSERT INTO ptr_items (ptr_id, property_id, inventory_no, description, quantity)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$ptr_id, $prop_id, $prop['inventory_no'], $prop['item_name'], $prop['quantity']]);
    }

    $_SESSION['status']      = "PTR Saved Successfully";
    $_SESSION['status_code'] = "success";
}

include_once "header.php";

if(isset($_SESSION['status'], $_SESSION['status_code'])){
    $flashIcon  = $_SESSION['status_code'];
    $flashTitle = $_SESSION['status'];
    unset($_SESSION['status'], $_SESSION['status_code']);
}
?>

<div class="content-wrapper">
  <div class="content-header border-bottom mb-4">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0 text-dark font-weight-bold text-uppercase">
            <i class="fas fa-exchange-alt mr-2 text-primary"></i>Property Transfer Report (PTR)
          </h4>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="card card-outline card-primary shadow-sm">
        <div class="card-header border-0 pt-4 px-4">
          <h5 class="card-title text-muted mb-0">Transfer Details</h5>
        </div>
        <div class="card-body p-4">
          <form method="post" id="ptrForm">
            <input type="hidden" name="from_office" value="<?= $fromOfficeSelected ?>">
            <div class="row">

              <!-- LEFT COLUMN -->
              <div class="col-md-6 border-right pr-md-4">
                <h5 class="text-primary mb-3">Transfer Information</h5>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1">Transfer Date</label>
                  <input type="date" name="transfer_date" class="form-control form-control-lg shadow-sm"
                         value="<?= htmlspecialchars($_POST['transfer_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1">From Office</label>
                  <select id="fromOfficeSelect" class="form-control form-control-lg shadow-sm" required
                          onchange="window.location='ptr.php?from_office='+this.value">
                    <option value="">-- Select Office --</option>
                    <?php foreach($offices as $office): ?>
                      <option value="<?= $office['id'] ?>"
                        <?= ($fromOfficeSelected == $office['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($office['office_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if($fromOfficeSelected > 0): ?>
                    <small class="text-muted"><?= count($propertyList) ?> propert<?= count($propertyList) === 1 ? 'y' : 'ies' ?> found</small>
                  <?php endif; ?>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1">To Office</label>
                  <select name="to_office" id="toOfficeSelect" class="form-control form-control-lg shadow-sm" required>
                    <option value="">-- Select Office --</option>
                    <?php foreach($offices as $office): ?>
                      <?php if($office['id'] == $fromOfficeSelected) continue; ?>
                      <option value="<?= $office['id'] ?>"
                        <?= (isset($_POST['to_office']) && $_POST['to_office'] == $office['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($office['office_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- RIGHT COLUMN -->
              <div class="col-md-6 pl-md-4">
                <h5 class="text-primary mb-3">Property Details</h5>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-1">Remarks</label>
                  <textarea name="remarks" class="form-control form-control-lg shadow-sm" rows="3"><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
                </div>

                <div class="form-group mb-4">
                  <label class="small text-dark font-weight-bold mb-2 d-block">Properties to Transfer</label>

                  <?php if($fromOfficeSelected === 0): ?>
                    <div class="alert alert-info py-2">
                      <i class="fas fa-info-circle mr-1"></i> Please select a <strong>From Office</strong> first to load its properties.
                    </div>
                  <?php elseif(count($propertyList) === 0): ?>
                    <div class="alert alert-warning py-2">
                      <i class="fas fa-exclamation-triangle mr-1"></i> No properties found for this office.
                    </div>
                  <?php else: ?>
                    <table class="table table-bordered table-sm">
                      <thead class="thead-light">
                        <tr>
                          <th width="75%">Property</th>
                          <th width="25%">Quantity</th>
                        </tr>
                      </thead>
                      <tbody id="item_table">
                        <tr>
                          <td>
                            <select name="property_id[]" id="propertySelect" class="form-control form-control-sm" required>
                              <option value="">-- Select Property --</option>
                              <?php foreach($propertyList as $prop): ?>
                                <option value="<?= $prop['property_id'] ?>" data-qty="<?= $prop['quantity'] ?>">
                                  <?= htmlspecialchars($prop['inventory_no'].' - '.$prop['item_name'].' (Qty: '.$prop['quantity'].')') ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                          <td>
                            <input type="number" name="quantity[]" id="qtyInput" class="form-control form-control-sm qty-input" value="1" readonly>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  <?php endif; ?>
                </div>
              </div>

            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-end">
              <a href="javascript:history.back()" class="btn btn-light btn-lg px-4 mr-2 border shadow-sm">
                <i class="fas fa-times mr-2"></i>Cancel
              </a>
              <button type="submit" name="save_ptr" id="savePtrBtn" class="btn btn-primary btn-lg px-5 shadow"
                      <?= ($fromOfficeSelected === 0 || count($propertyList) === 0) ? 'disabled' : '' ?>>
                <i class="fas fa-save mr-2"></i>Save PTR
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>

<script>

/* ================= FLASH MESSAGE ================= */
<?php if(isset($flashIcon, $flashTitle)): ?>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: '<?= $flashIcon ?>',
        title: '<?= addslashes($flashTitle) ?>',
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        <?php if($flashIcon === 'success'): ?>
        window.location.href = 'ptr_record.php';
        <?php endif; ?>
    });
});
<?php endif; ?>

/* ================= AUTO-FILL QUANTITY ================= */
var itemTable = document.getElementById('item_table');
if (itemTable) {
    itemTable.addEventListener('change', function (e) {
        if (e.target.tagName === 'SELECT') {
            var selected = e.target.options[e.target.selectedIndex];
            var qty = selected.getAttribute('data-qty') || 1;
            var row = e.target.closest('tr');
            if (row) row.querySelector('.qty-input').value = qty;
        }
    });
}

/* ================= FORM VALIDATION ================= */
var ptrForm = document.getElementById('ptrForm');
if (ptrForm) {
    ptrForm.addEventListener('submit', function (e) {
        var fromOffice = <?= $fromOfficeSelected ?>;
        var toOffice   = document.getElementById('toOfficeSelect').value;

        if (!toOffice) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Missing To Office', text: 'Please select a destination office.', confirmButtonColor: '#3085d6' });
            return;
        }

        if (fromOffice == toOffice) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Invalid Transfer', text: 'From Office and To Office must be different.', confirmButtonColor: '#d33' });
            return;
        }

        var selects = document.querySelectorAll('select[name="property_id[]"]');
        var hasProperty = false;
        selects.forEach(function (sel) { if (sel.value) hasProperty = true; });

        if (!hasProperty) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'No Property Selected', text: 'Please select at least one property to transfer.', confirmButtonColor: '#3085d6' });
        }
    });
}

</script>