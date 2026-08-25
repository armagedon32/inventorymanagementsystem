<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0 text-dark">Supply List</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">

                  <div class="card-header d-flex align-items-center">
    <div>
        <h5 class="m-0">Supplies</h5>
    </div>
    <div class="ml-auto">
        <button type="button" class="btn btn-primary btn-sm" id="btnPrintAll">
            <i class="fa fa-print"></i> Print All
        </button>
    </div>
</div>

                        <div class="card-body">

                            <table class="table table-striped table-hover" id="table_product">
                                <thead>
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Name</th>
                                        <th>Brand</th>
                                        <th>Acquisition Type</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Image</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php
                                $select = $pdo->prepare("
                                    SELECT p.*, c.category
                                    FROM tbl_product p
                                    LEFT JOIN tbl_category c ON p.category = c.catid
                                    ORDER BY p.pid DESC
                                ");
                                $select->execute();

                                $lowStockItems = [];

                                while ($row = $select->fetch(PDO::FETCH_OBJ)) {

                                    $rowClass = ($row->stock <= $row->reorder_level) ? "table-danger" : "";

                                    $image = (!empty($row->image) && file_exists("productimage/".$row->image))
                                        ? "productimage/".$row->image
                                        : "productimage/noimage.png";

                                    $restockBtn = '<a href="addstock.php?id='.$row->pid.'" class="btn btn-info btn-sm" title="Restock Supply">
                                             <span class="fa fa-plus-circle text-white"></span>
                                           </a>';

                                    echo '<tr class="'.$rowClass.'">
                                        <td class="barcode">'.htmlspecialchars($row->barcode).'</td>
                                        <td class="name">'.htmlspecialchars($row->name).'</td>
                                        <td class="brand">'.htmlspecialchars($row->brand).'</td>
                                        <td class="acq_type">'.htmlspecialchars($row->acquisition_type).'</td>
                                        <td class="category">'.htmlspecialchars($row->category).'</td>
                                        <td class="description">'.htmlspecialchars($row->description).'</td>
                                        <td class="stock">'.htmlspecialchars($row->stock).'</td>
                                        <td class="reorder">'.htmlspecialchars($row->reorder_level).'</td>
                                        <td class="product_image"><img src="'.$image.'" width="40" height="40" class="img-fluid rounded border shadow-sm"></td>
                                        <td>
                                            <div class="btn-group shadow-sm">

                                                <a href="printbarcode.php?id='.$row->pid.'" class="btn btn-dark btn-sm" title="Print Barcode">
                                                    <span class="fa fa-barcode text-white"></span>
                                                </a>

                                                <a href="viewproduct.php?id='.$row->pid.'" class="btn btn-warning btn-sm" title="View Product">
                                                    <span class="fa fa-eye text-white"></span>
                                                </a>

                                                <a href="editproduct.php?id='.$row->pid.'" class="btn btn-success btn-sm" title="Edit Product">
                                                    <span class="fa fa-edit text-white"></span>
                                                </a>

                                                '.$restockBtn.'

                                                <!-- Single row print -->
                                                <button type="button" class="btn btn-primary btn-sm btnprint" title="Print Details">
                                                    <span class="fa fa-print text-white"></span>
                                                </button>

                                            </div>
                                        </td>
                                    </tr>';

                                    if ($row->stock <= $row->reorder_level) {
                                        $lowStockItems[] = $row->name;
                                    }
                                }
                                ?>
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ================= PRINT SINGLE ROW SCRIPT ================= -->
<script>
$(document).ready(function() {

   $('#table_product').on('click', '.btnprint', function() {
    
    var row = $(this).closest('tr');

    // Get data using classes
    var barcode     = row.find(".barcode").text().trim();
    var name        = row.find(".name").text().trim();
    var brand       = row.find(".brand").text().trim();
    var acq_type    = row.find(".acq_type").text().trim();
    var category    = row.find(".category").text().trim();
    var description = row.find(".description").text().trim();
    var stock       = row.find(".stock").text().trim();
    var reorder     = row.find(".reorder").text().trim();
    var imageSrc    = row.find(".product_image img").attr("src");

    var printHTML = '<html><head><title>Supply Details Report</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';
    printHTML += '<style>';
    printHTML += 'body{padding:40px; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; color: #333;}';
    printHTML += '.header-section{border-bottom: 2px solid #1b5e20; padding-bottom: 20px; margin-bottom: 30px;}';
    printHTML += '.label-custom{font-weight: bold; color: #1b5e20; text-transform: uppercase; font-size: 0.85rem; margin-bottom: 5px; display: block;}';
    printHTML += '.value-custom{font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 5px; display: block; min-height: 1.5rem;}';
    printHTML += '.detail-row{margin-bottom: 20px;}';
    printHTML += '.signature-section{margin-top: 60px; display: flex; justify-content: space-between;}';
    printHTML += '.signature-box{width: 300px; text-align: center;}';
    printHTML += '.signature-line{border-bottom: 1px solid #000; margin-top: 50px; font-weight: bold; text-transform: uppercase;}';
    printHTML += '@media print{.no-print{display:none;}}';
    printHTML += '</style></head><body>';

    // COLLEGE HEADER
    printHTML += '<div class="header-section d-flex align-items-center justify-content-center">';
    var logoSrc = '../dist/img/logo.png';
    var loc = window.location;
    var baseUrl = loc.protocol + "//" + loc.host + loc.pathname.substring(0, loc.pathname.lastIndexOf('/'));
    var fullLogoSrc = baseUrl + '/' + logoSrc;
    printHTML += '<img src="' + fullLogoSrc + '" style="width:80px; margin-right:20px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h4 class="font-weight-bold mb-0">KOLEHIYO NG SUBIC</h4>';
    printHTML += '<p class="mb-0 text-muted">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p class="mb-0 text-muted small">Tel.no.: (047) 232 – 4896 / 232-4897</p>';
    printHTML += '</div></div>';

    printHTML += '<div class="container-fluid px-0">';
    printHTML += '<div class="mb-4 text-center"><h5 class="font-weight-bold text-uppercase" style="letter-spacing: 2px; color: #1b5e20;">Supply Information Details</h5></div>';
    
    printHTML += '<div class="row">';
    // LEFT COLUMN (Mirroring addproduct.php layout)
    printHTML += '<div class="col-6 pr-4 border-right">';
    printHTML += '<div class="detail-row"><span class="label-custom">Barcode</span><span class="value-custom">' + barcode + '</span></div>';
    printHTML += '<div class="detail-row"><span class="label-custom">Category</span><span class="value-custom">' + category + '</span></div>';
    printHTML += '<div class="detail-row"><span class="label-custom">Item Name</span><span class="value-custom">' + name + '</span></div>';
    printHTML += '<div class="detail-row"><span class="label-custom">Brand</span><span class="value-custom">' + brand + '</span></div>';
    printHTML += '<div class="detail-row"><span class="label-custom">Acquisition Type</span><span class="value-custom">' + acq_type + '</span></div>';
    printHTML += '</div>';

    // RIGHT COLUMN (Mirroring addproduct.php layout)
    printHTML += '<div class="col-6 pl-4">';
    printHTML += '<div class="detail-row"><span class="label-custom">Description</span><span class="value-custom">' + description + '</span></div>';
    printHTML += '<div class="row">';
    printHTML += '<div class="col-6"><div class="detail-row"><span class="label-custom">Stock Quantity</span><span class="value-custom">' + stock + '</span></div></div>';
    printHTML += '<div class="col-6"><div class="detail-row"><span class="label-custom">Reorder Level</span><span class="value-custom">' + reorder + '</span></div></div>';
    printHTML += '</div>';
    
    // IMAGE SECTION
    printHTML += '<div class="detail-row mt-3"><span class="label-custom">Supply Image</span>';
    if(imageSrc && !imageSrc.includes('noimage.png')) {
        var fullImageSrc = imageSrc;
        if (!imageSrc.startsWith('http')) {
            var loc = window.location;
            var baseUrl = loc.protocol + "//" + loc.host + loc.pathname.substring(0, loc.pathname.lastIndexOf('/'));
            fullImageSrc = baseUrl + '/' + imageSrc;
        }
        printHTML += '<div style="text-align: center; border: 1px solid #ddd; padding: 10px; border-radius: 8px; margin-top: 10px; background: #fff; width: fit-content; margin-left: auto; margin-right: auto;">';
        printHTML += '<img src="' + fullImageSrc + '" style="max-height: 180px; width: auto; border-radius: 4px;">';
        printHTML += '</div>';
    } else {
        printHTML += '<div class="text-muted small p-4 border bg-light rounded text-center mt-2">No Image Available</div>';
    }
    printHTML += '</div>';
    printHTML += '</div>';
    printHTML += '</div>'; // End Row

    // SIGNATURE SECTION
    printHTML += '<div class="signature-section">';
    printHTML += '<div class="signature-box">';
    printHTML += '<p class="text-left font-weight-bold mb-0">Prepared by:</p>';
    printHTML += '<div class="signature-line">' + (window.globalOIC ? window.globalOIC.property : "") + '</div>';
    printHTML += '<p class="mb-0 small">Property/Supplies Officer</p>';
    printHTML += '</div>';
    printHTML += '</div>';

    printHTML += '</div></body></html>';

    var printWindow = window.open('', '', 'height=900,width=1000');
    printWindow.document.write(printHTML);
    printWindow.document.close();
    printWindow.onload = function(){ 
        setTimeout(function(){ printWindow.print(); }, 500);
    };

  });

});
</script>

<!-- ================= PRINT ALL SCRIPT ================= -->
<script>
$(document).ready(function() {

  $('#btnPrintAll').click(function() {

    var headers = [];
    var rowsData = [];

    $('#table_product thead th').each(function(index){
      if(index !== 9) headers.push($(this).text());
    });

    $('#table_product tbody tr').each(function(){
      var rowData = [];
      $(this).find('td').each(function(index){
        if(index !== 9) rowData.push($(this).html());
      });
      rowsData.push(rowData);
    });

    var printHTML = '<html><head><title>All Supply Report</title>';
    printHTML += '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
    printHTML += '<style>body{padding:20px; font-size:12px;} table{width:100%; border-collapse: collapse;} th, td{padding:6px; border:1px solid #000;}</style>';
    printHTML += '</head><body>';

    // HEADER
    printHTML += '<div style="display:flex; align-items:center; justify-content:center; margin-bottom:20px;">';
    printHTML += '<img src="../dist/img/logo.png" style="width:80px; margin-right:15px;">';
    printHTML += '<div style="text-align:center;">';
    printHTML += '<h5 style="margin:0;">KOLEHIYO NG SUBIC</h5>';
    printHTML += '<p style="margin:0;">WFI Compound, Wawandue, Subic, Zambales</p>';
    printHTML += '<p style="margin:0;">Tel.no.: (047)232 – 4896/232-4897</p>';
    printHTML += '</div></div>';

    printHTML += '<h4>Supply Inventory Report</h4><table class="table table-bordered"><thead><tr>';
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
    printHTML += '    <span style="font-weight:bold; text-transform:uppercase;">' + (window.globalOIC ? window.globalOIC.property : "") + '</span>';
    printHTML += '  </div>';
    printHTML += '  <p style="margin:0; text-align:center;">Property/Supplies Officer</p>';
    printHTML += '</div>';
    
    printHTML += '</div>';

    printHTML += '</body></html>';

    var printWindow = window.open('', '', 'width=1000,height=800');
    printWindow.document.write(printHTML);
    printWindow.document.close();
    printWindow.onload = function(){ printWindow.print(); };

  });

});
</script>

<!-- SESSION ALERT -->
<?php if (!empty($_SESSION['status'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({
        icon: '<?php echo $_SESSION['status_code']; ?>',
        title: '<?php echo $_SESSION['status']; ?>'
    });
});
</script>
<?php
unset($_SESSION['status']);
unset($_SESSION['status_code']);
endif;
?>

<!-- LOW STOCK ALERT -->
<?php if (!empty($lowStockItems)): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({
        icon: 'warning',
        title: 'Low Stock Alert!',
        html: '<?php echo implode("<br>", array_map("htmlspecialchars", $lowStockItems)); ?>'
    });
});
</script>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('#table_product').DataTable({
        "order": [[1, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [8, 9] }
        ]
    });
});
</script>