<?php
include_once 'connectdb.php';
session_start();

include_once "header.php";
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            View PTR
          </h1>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">

          <div class="card card-info card-outline">
            <div class="card-header">
              <h5 class="m-0">PTR Details</h5>
            </div>
            <div class="card-body">

<?php
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">No PTR selected.</div>';
} else {

    $ptr_id = $_GET['id'];

    // Fetch PTR header
    $headerStmt = $pdo->prepare("
        SELECT h.*, 
               f.office_name AS from_office_name,
               t.office_name AS to_office_name
        FROM ptr_header h
        LEFT JOIN tbl_office f ON h.from_office = f.id
        LEFT JOIN tbl_office t ON h.to_office = t.id
        WHERE h.id = :id
        LIMIT 1
    ");
    $headerStmt->execute([':id' => $ptr_id]);
    $header = $headerStmt->fetch(PDO::FETCH_OBJ);

    if (!$header) {
        echo '<div class="alert alert-danger">PTR not found.</div>';
    } else {
        // Fetch PTR items
        $itemsStmt = $pdo->prepare("
            SELECT i.*, p.inventory_no, p.item_name, p.serial_no, p.brand, p.quantity AS property_qty
            FROM ptr_items i
            LEFT JOIN tbl_property p ON i.property_id = p.property_id
            WHERE i.ptr_id = :ptr_id
            ORDER BY i.id ASC
        ");
        $itemsStmt->execute([':ptr_id' => $ptr_id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_OBJ);
?>

<!-- PTR Header Info -->
<div class="row">
  <div class="col-md-6">
    <ul class="list-group">
      <center><p class="list-group-item list-group-item-info"><b>PTR HEADER</b></p></center>
      <li class="list-group-item"> <b>PTR No</b> <span class="badge badge-light float-right"><?= htmlspecialchars($header->ptr_no) ?></span></li>
      <li class="list-group-item"> <b>Transfer Date</b> <span class="badge badge-primary float-right"><?= htmlspecialchars($header->transfer_date) ?></span></li>
      <li class="list-group-item"> <b>From Office</b> <span class="badge badge-warning float-right"><?= htmlspecialchars($header->from_office_name ?: 'N/A') ?></span></li>
      <li class="list-group-item"> <b>To Office</b> <span class="badge badge-success float-right"><?= htmlspecialchars($header->to_office_name ?: 'N/A') ?></span></li>
      <li class="list-group-item"> <b>Remarks</b> <span class="badge badge-info float-right"><?= htmlspecialchars($header->remarks) ?></span></li>
    </ul>
  </div>
</div>

<br>

<!-- PTR Items Table -->
<div class="row">
  <div class="col-md-12">
    <div class="card card-outline card-secondary">
      <div class="card-header">
        <h5 class="m-0">Transferred Items</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Inventory No</th>
              <th>Serial No</th>
              <th>Item Name</th>
              <th>Brand</th>
              <th>Quantity</th>
            </tr>
          </thead>
          <tbody>
<?php
$count = 1;
foreach ($items as $item) {
    echo '<tr>';
    echo '<td>'.$count.'</td>';
    echo '<td>'.htmlspecialchars($item->inventory_no).'</td>';
    echo '<td>'.htmlspecialchars($item->serial_no ?: 'N/A').'</td>';
    echo '<td>'.htmlspecialchars($item->item_name).'</td>';
    echo '<td>'.htmlspecialchars($item->brand).'</td>';
    echo '<td>'.htmlspecialchars($item->quantity).'</td>';
    echo '</tr>';
    $count++;
}
?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
    } // end header exists
} // end id exists
?>

            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once "footer.php"; ?>