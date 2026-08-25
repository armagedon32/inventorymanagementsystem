<?php
include_once 'connectdb.php';
session_start();

if ($_SESSION['useremail'] == "" || $_SESSION['role'] == "") {
    header('location:../index.php');
}

if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}

// Get office ID from URL
$location_id = $_GET['location_id'] ?? 0;

// Get office details
$office_stmt = $pdo->prepare("SELECT office_name, address FROM tbl_office WHERE id = :id");
$office_stmt->bindParam(':id', $location_id);
$office_stmt->execute();
$office = $office_stmt->fetch(PDO::FETCH_ASSOC);
$office_name = $office['office_name'] ?? "Library";

?>

<div class="content-wrapper">
<div class="content-header">
<div class="container-fluid">
    <h4>
        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
        <strong>Inventory - <?php echo htmlspecialchars($office_name); ?></strong><br>
        <small style="margin-left: 45px;">Officer-In-Charge: <?php echo htmlspecialchars($office['address'] ?? "N/A"); ?></small>
    </h4>
</div>
</div>

<div class="content">
<div class="container-fluid">

<div class="card card-primary card-outline">
<div class="card-body">

<table class="table table-striped table-hover" id="table_property">
<thead>
<tr>
<th>Office</th>
<th>Inventory No.</th>
<th>Serial No.</th>
<th>Item Name</th>
<th>Brand</th>
<th>Acquisition</th>
<th>Description</th>
<th>Quantity</th>
<th>Remarks</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
$select = $pdo->prepare("
    SELECT 
        p.property_id,
        p.inventory_no,
        p.serial_no,
        p.item_name,
        p.brand,
        p.description,
        p.acquisition_type,
        p.quantity,
        p.remarks,
        p.warranty_image,
        o.office_name
    FROM tbl_property p
    LEFT JOIN tbl_office o ON p.office_id = o.id
    WHERE p.office_id = :office_id AND p.is_archived = 0
    ORDER BY p.property_id DESC
");
$select->bindParam(':office_id', $location_id, PDO::PARAM_INT);
$select->execute();

if ($select->rowCount() > 0) {
        while ($row = $select->fetch(PDO::FETCH_OBJ)) {
            $warrantyBtn = "";
            if(!empty($row->warranty_image) && file_exists(__DIR__ . "/productimage/" . $row->warranty_image)) {
                $warrantyBtn = '<button class="btn btn-primary btn-sm btnwarranty" data-img="productimage/' . $row->warranty_image . '" title="View Warranty"><span class="fa fa-file-contract"></span></button>';
            }

            // Loop based on quantity to display each item as a separate row
            $qty = (int)$row->quantity;
            for ($i = 0; $i < $qty; $i++) {
                // If quantity > 1 and it's a single record, append suffix to serial/inventory no for display
                $display_serial = $row->serial_no;
                $display_inventory = $row->inventory_no;
                
                if ($qty > 1) {
                    $display_serial .= " (" . ($i + 1) . "/" . $qty . ")";
                }

                echo '
                <tr data-property-id="'.$row->property_id.'">
                  <td>' . htmlspecialchars($row->office_name ?? "Unassigned") . '</td>
                  <td>' . htmlspecialchars($display_inventory) . '</td>
                  <td>' . htmlspecialchars($display_serial) . '</td>
                  <td>' . htmlspecialchars($row->item_name) . '</td>
                  <td>' . htmlspecialchars($row->brand) . '</td>
                  <td>' . htmlspecialchars($row->acquisition_type) . '</td>
                  <td>' . htmlspecialchars($row->description) . '</td>
                  <td>1</td>
                  <td>' . htmlspecialchars($row->remarks) . '</td>
                  <td>
                    <div class="btn-group">
                      <a href="viewproperty.php?id=' . $row->property_id . '" class="btn btn-warning btn-sm"><span class="fa fa-eye"></span></a>
                      <a href="editproperty.php?id=' . $row->property_id . '" class="btn btn-success btn-sm"><span class="fa fa-edit"></span></a>
                      ' . $warrantyBtn . '
                      <button id="' . $row->property_id . '" class="btn btn-danger btn-sm btndelete"><span class="fa fa-trash"></span></button>
                    </div>
                  </td>
                </tr>';
            }
        }
    } else {
    echo '<tr><td colspan="10" class="text-center">No property records found.</td></tr>';
}
?>
</tbody>
</table>

</div>
</div>

</div>
</div>
</div>

<?php include_once "footer.php"; ?>

<!-- SweetAlert & DataTables -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

  var table = $('#table_property').DataTable({
      searching: false,
      paging: false,
      info: false,
      ordering: false
  });

  $('.btndelete').click(function() {
    var tdh = $(this);
    var id = $(this).attr("id");

    $.ajax({
      url: 'propertydelete.php',
      type: 'post',
      data: { property_id: id },
      success: function(data) {
        table.row(tdh.closest('tr')).remove().draw();
        Swal.fire("Deleted!","Property has been deleted.","success");
      }
    });
  });

  $('.btnwarranty').click(function() {
    var imgSrc = $(this).data('img');
    Swal.fire({
      title: 'Warranty Information',
      imageUrl: imgSrc,
      imageAlt: 'Warranty Image',
      width: '80%',
      showCloseButton: true,
      showConfirmButton: false
    });
  });

  // SweetAlert Notifications
  <?php if(isset($_SESSION['status'])): ?>
  Swal.fire({
      icon: '<?= $_SESSION['status_code'] ?>',
      title: '<?= $_SESSION['status'] ?>',
      showConfirmButton: false,
      timer: 2000
  });
  <?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
  <?php endif; ?>

// ================= LISTEN FOR AJAX DISPOSAL EVENTS =================
window.addEventListener('itemDisposed', function(e){
    var property_id = e.detail.property_id;
    var new_qty = e.detail.new_qty;
    var borrowed_qty = e.detail.borrowed_qty || 0; // borrow info sent from backend

    // Find the row by data-property-id
    var row = $('#table_property tbody tr').filter(function(){
        return $(this).data('property-id') == property_id;
    });

    if(row.length){
        if(new_qty <= 0 && borrowed_qty <= 0){
            // Remove row if last item disposed and nothing borrowed
            table.row(row).remove().draw();
        } else {
            // Update quantity column (8th column)
            row.find('td').eq(7).text(new_qty);

            // Optional: show a warning if quantity is 0 but borrowed items exist
            if(new_qty <= 0 && borrowed_qty > 0){
                row.find('td').eq(7).text("0 (borrowed)");
            }
        }
    }
});

});
</script>