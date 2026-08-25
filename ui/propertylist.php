<?php
include_once 'connectdb.php';
session_start();

if ($_SESSION['useremail'] == "") {
  header('location:../index.php');
  exit();
}

if (isset($_SESSION['must_change_password'])) {
  header('Location: ../reset_change_password.php');
  exit();
}

include_once "header.php";
?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <h4>Overall Property Inventory Per Office</h4>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-primary card-outline">
        <div class="card-header d-flex align-items-center">
          <div>
            <h5 class="m-0">Property Inventory</h5>
          </div>
          <div class="ml-auto">
            <button type="button" class="btn btn-primary btn-sm" id="btnPrintAll">
              <i class="fa fa-print"></i> Print All
            </button>
          </div>
        </div>

        <div class="card-body table-responsive">

          <table id="table_property" class="table table-striped table-hover table-sm w-100">
            <thead>
              <tr>
                <th style="width: 10%;">Office</th>
                <th style="width: 10%;">Inventory No.</th>
                <th style="width: 10%;">Serial No.</th>
                <th style="width: 10%;">Item Name</th>
                <th style="width: 8%;">Brand</th>
                <th style="width: 8%;">Acquisition</th>
                <th style="width: 15%;">Description</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 8%;">Remarks</th>
                <th style="width: 8%;">Received By</th>
                <th style="width: 130px;">Actions</th>
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
        p.office_id,
        p.instructor_id,
        p.warranty_image,
        o.office_name,
        o.address AS office_oic,
        i.fullname AS instructor_name
    FROM tbl_property p
    LEFT JOIN tbl_office o ON p.office_id = o.id
    LEFT JOIN tbl_instructors i ON p.instructor_id = i.id
    WHERE p.is_archived = 0
    ORDER BY 
        CASE WHEN o.office_name IS NULL THEN 1 ELSE 0 END,
        o.office_name ASC,
        p.property_id DESC
");

$select->execute();

while ($row = $select->fetch(PDO::FETCH_OBJ)) {

    $officeDisplay = (!empty($row->office_id) && !empty($row->office_name))
        ? htmlspecialchars($row->office_name)
        : '<span class="text-danger">Unassigned</span>';

    $instructorDisplay = !empty($row->instructor_name)
        ? htmlspecialchars($row->instructor_name)
        : (!empty($row->office_oic) 
            ? htmlspecialchars($row->office_oic) 
            : '<span class="text-danger">Unassigned</span>');

    $warrantyBtn = !empty($row->warranty_image)
        ? '<button type="button" class="btn btn-secondary btn-sm btnwarranty" data-image="productimage/' . $row->warranty_image . '" title="View Warranty">
             <span class="fa fa-file-contract"></span>
           </button>'
        : '';

    echo '
    <tr>
      <td>' . $officeDisplay . '</td>
      <td>' . htmlspecialchars($row->inventory_no) . '</td>
      <td>' . htmlspecialchars($row->serial_no) . '</td>
      <td>' . htmlspecialchars($row->item_name) . '</td>
      <td>' . htmlspecialchars($row->brand) . '</td>
      <td>' . htmlspecialchars($row->acquisition_type) . '</td>
      <td>' . htmlspecialchars($row->description) . '</td>
      <td>' . htmlspecialchars($row->quantity) . '</td>
      <td>' . htmlspecialchars($row->remarks) . '</td>
      <td>' . $instructorDisplay . '</td>
      <td class="text-center" style="white-space: nowrap;">
        <div class="btn-group shadow-sm">

          <a href="viewproperty.php?id=' . $row->property_id . '" 
             class="btn btn-warning btn-sm" title="View Details">
             <span class="fa fa-eye text-white"></span>
          </a>

          <a href="editproperty.php?id=' . $row->property_id . '" 
             class="btn btn-success btn-sm" title="Edit Property">
             <span class="fa fa-edit text-white"></span>
          </a>

          ' . $warrantyBtn . '

          <a href="printpropertybarcode.php?id=' . $row->property_id . '"
             class="btn btn-dark btn-sm" title="Print Barcode">
             <span class="fa fa-barcode text-white"></span>
          </a>

          <button type="button"
                  class="btn btn-info btn-sm btnprint"
                  data-id="' . $row->property_id . '" title="Print Details">
                  <span class="fa fa-print text-white"></span>
          </button>

          <button type="button"
                  class="btn btn-danger btn-sm btndelete"
                  data-id="' . $row->property_id . '" title="Archive Property">
                  <span class="fa fa-trash text-white"></span>
          </button>

        </div>
      </td>
    </tr>';
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

<!-- ========================= -->
<!-- Unified Script Block -->
<!-- ========================= -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
  // 1. Initialize DataTable
  var table = $('#table_property').DataTable({
    "order": [[0, "asc"]],
    "columnDefs": [
      { "orderable": false, "targets": 10 } // Actions column
    ]
  });

  // 2. Delete Button Click (Event Delegation)
  $(document).on('click', '.btnwarranty', function () {
    var image = $(this).data("image");
    
    Swal.fire({
      title: 'Warranty Image',
      imageUrl: image,
      imageAlt: 'Warranty image',
      showCloseButton: true,
      showDenyButton: true,
      confirmButtonText: 'Close',
      denyButtonText: '<i class="fa fa-print"></i> Print',
      denyButtonColor: '#17a2b8',
      customClass: {
        image: 'img-fluid rounded border shadow-sm'
      }
    }).then((result) => {
      if (result.isDenied) {
        var printWin = window.open('', '_blank');
        printWin.document.write('<html><head><title>Print Warranty</title></head><body style="text-align:center; margin:0; padding:20px;"><img src="' + image + '" style="max-width:100%; height:auto;" onload="window.print(); window.close();"></body></html>');
        printWin.document.close();
      }
    });
  });

  $(document).on('click', '.btndelete', function () {
    var button = $(this);
    var id = button.data("id");
    performDelete(id, button);
  });

  function performDelete(id, button) {
    $.ajax({
      url: 'propertydelete.php',
      type: 'POST',
      data: { property_id: id },
      success: function(response) {
        console.log("Delete Response:", response);
        if(response.trim() == "1"){
          var row = button.closest('tr');
          $('#table_property').DataTable().row(row).remove().draw();
          if (typeof Swal !== 'undefined') {
              Swal.fire("Archived!", "Property has been moved to archive.", "success");
          } else {
              alert("Property has been archived.");
          }
        } else {
          if (typeof Swal !== 'undefined') {
              Swal.fire("Error!", "Archive failed: " + response, "error");
          } else {
              alert("Archive failed: " + response);
          }
        }
      },
      error: function(xhr, status, error){
        if (typeof Swal !== 'undefined') {
            Swal.fire("Error!", "AJAX request failed: " + error, "error");
        } else {
            alert("AJAX request failed: " + error);
        }
      }
    });
  }

  // 3. Print Single Row
  $(document).on('click', '.btnprint', function() {
    var id = $(this).data("id");
    window.open('print_property_single.php?id=' + id, '_blank');
  });

  // 4. Print All Button
  $('#btnPrintAll').click(function(){
    var headers = [];
    var rowsData = [];

    $('#table_property thead th').each(function(index){
      if(index !== 10) headers.push($(this).text()); // exclude Actions
    });

    $('#table_property tbody tr').each(function(){
      var rowData = [];
      $(this).find('td').each(function(index){
        if(index !== 10) rowData.push($(this).html()); // exclude Actions
      });
      rowsData.push(rowData);
    });

    var printHTML = '<html><head><title>Property Inventory Report</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px;font-size:12px;}table{width:100%;border-collapse: collapse;}th,td{padding:6px;border:1px solid #000;}</style>';
    printHTML += '</head><body>';

    // Header
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p>';
    printHTML += '</div></div>';

    printHTML += '<h4>Property Inventory Report</h4><table class="table table-bordered"><thead><tr>';
    headers.forEach(function(h){ printHTML += '<th>' + h + '</th>'; });
    printHTML += '</tr></thead><tbody>';

    rowsData.forEach(function(row){
      printHTML += '<tr>';
      row.forEach(function(col){ printHTML += '<td>' + col + '</td>'; });
      printHTML += '</tr>';
    });

    printHTML += '</tbody></table>';

    printHTML += '<div style="margin-top:80px; display: flex; justify-content: space-between;">';
    
    // College President Section
    printHTML += '<div>';
    printHTML += '  <div style="width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + (window.globalOIC ? window.globalOIC.president : "") + '</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">College President</p>';
    printHTML += '</div>';

    // Property/Supplies Officer Section
    printHTML += '<div>';
    printHTML += '  <div style="width:250px; border-bottom:1px solid #000; text-align:center;">';
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + window.globalOIC.property + '</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">Property/Supplies Officer</p>';
    printHTML += '</div>';
    
    printHTML += '</div>';

    printHTML += '</body></html>';

    var printWindow = window.open('', '', 'width=1000,height=800');
    printWindow.document.write(printHTML);
    printWindow.document.close();
    printWindow.print();
  });

});
</script>

<!-- SweetAlert Notifications -->
<?php if(isset($_SESSION['status'])): ?>
<script>
Swal.fire({
    icon: '<?= $_SESSION['status_code'] ?>',
    title: '<?= $_SESSION['status'] ?>',
    showConfirmButton: false,
    timer: 2000
});
</script>
<?php unset($_SESSION['status'], $_SESSION['status_code']); ?>
<?php endif; ?>