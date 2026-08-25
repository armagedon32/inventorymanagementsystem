<?php

include_once 'connectdb.php';
session_start();


include_once "header.php";


//Condition for the Save Button
if (isset($_POST['btnsave'])) {

  $suppliername = $_POST['txtsupplier_name'];

  // Fix variable name from $category to $company
  if (empty($suppliername)) {
    $_SESSION['status'] = "Supplier Field is Empty";
    $_SESSION['status_code'] = "warning";
  } else {
    $insert = $pdo->prepare("insert into tbl_supplier (supplier_name) values(:supname)");

    $insert->bindParam(':supname', $suppliername);

    if ($insert->execute()) {
      // Log activity
      $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
      $stmtLog->execute([$_SESSION['userid'], "Added New Supplier: " . $suppliername]);

      $_SESSION['status'] = "Supplier Added successfully";
      $_SESSION['status_code'] = "success";
    } else {
      $_SESSION['status'] = "Supplier Added Failed";
      $_SESSION['status_code'] = "warning";
    }
  }
}




//Condition for the Update Button
if (isset($_POST['btnupdate'])) {

  $company = $_POST['txtcompany'];
  $suppliername = $_POST['txtsupplier_name'];
  $id = $_POST['txtsup_id'];


  if (empty($suppliername)) {

    $_SESSION['status'] = "Supplier Feild is Empty";
    $_SESSION['status_code'] = "warning";
  } else {

    $update = $pdo->prepare("update tbl_supplier set supplier_name=:supname where sup_id=" . $id);

    $update->bindparam(':supname', $suppliername);

    if ($update->execute()) {
      // Log activity
      $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
      $stmtLog->execute([$_SESSION['userid'], "Updated Supplier: " . $suppliername]);

      $_SESSION['status'] = "Supplier Update successfully";
      $_SESSION['status_code'] = "success";
    } else {
      $_SESSION['status'] = "Supplier Update Failed";
      $_SESSION['status_code'] = "warning";
    }
  }
}

if (isset($_POST['btndelete'])) {
  $id = $_POST['btndelete'];

  // Fetch name for logging
  $stmtName = $pdo->prepare("SELECT supplier_name FROM tbl_supplier WHERE sup_id=:id");
  $stmtName->execute([':id'=>$id]);
  $supName = $stmtName->fetchColumn();

  $delete = $pdo->prepare("delete from tbl_supplier where sup_id=" . $id);

  if ($delete->execute()) {
    // Log activity
    $stmtLog = $pdo->prepare("INSERT INTO activity_log (user_id, action, date_created) VALUES (?, ?, NOW())");
    $stmtLog->execute([$_SESSION['userid'], "Deleted Supplier: " . $supName]);

    $_SESSION['status'] = "Deleted";
    $_SESSION['status_code'] = "success";
  } else {

    $_SESSION['status'] = "Delete Failed";
    $_SESSION['status_code'] = "warning";
  }
} else {
}

?>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
            Supplier Management
          </h1>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
      <div class="card card-warning card-outline">
        <div class="card-header">
          <h5 class="m-0">Supplier Form</h5>
        </div>
        <div class="card-body">

          <form action="" method="post">
            <div class="row">

              <?php

              if (isset($_POST['btnedit'])) {

                $select = $pdo->prepare("select * from tbl_supplier where sup_id =" . $_POST['btnedit']);
                $select->execute();

                if ($select) {

                  $row = $select->fetch(PDO::FETCH_OBJ);

                  echo '<div class="col-md-4">

                  
                      <div class="form-group">
                        <label for="exampleInputEmail1">Category</label>
                        <input type="hidden" class="form-control"  placeholder="Enter id" value="' . $row->sup_id . '" name="txtsup_id" >

                         <label for="exampleInputEmail1">Supplier</label>
                        <input type="text" class="form-control"  placeholder="Enter Supplier Name" value="' . $row->supplier_name . '" name="txtsupplier_name" >
                      </div>
    
                    <div class="card-footer">
                      <center><button type="submit" class="btn btn-info" name="btnupdate">Update</button></center>
                    </div>
                  
    
                  </div>';
                }
              } else {

                echo '<div class="col-md-4">

                <!-- First Supplier Column -->
                
                    <div class="form-group">
                      <label for="exampleInputEmail1">Supplier</label>
                      <input type="text" class="form-control"  placeholder="Enter Supplier Name" name="txtsupplier_name" >
                    </div>
  
                  <div class="card-footer">
                    <center><button type="submit" class="btn btn-warning" name="btnsave">Save</button></center>
                  </div>
                
  
                </div>';
              }



              ?>



              <!-- Second Table Category column -->
              <div class="col-md-8">

                <table id="table_category" class="table table-striped table-hover ">

                  <thead>
                    <tr>
                      <td>#</td>
                      <td>Supplier</td>
                      <td>Edit</td>
                      <td>Delete</td>
                    </tr>
                  </thead>
                  <tbody>

                    <?php
                    $select = $pdo->prepare("select * from tbl_supplier order by sup_id ASC");
                    $select->execute();

                    while ($row = $select->fetch(PDO::FETCH_OBJ)) {
                      echo '
                    <tr>
                    <td>' . $row->sup_id . '</td>
                    <td>' . $row->supplier_name . '</td>
                    
                    <td>
                    <button type="submit" class="btn btn-primary" value="' . $row->sup_id . '" name="btnedit">Edit</button>
                    </td>
                    <td>
                    <button type="button" class="btn btn-danger btn-delete-supplier" value="' . $row->sup_id . '">Delete</button>
                    </td>
                    </tr>';
                    }
                    ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td>#</td>
                      <td>Supplier</td>
                      <td>Edit</td>
                      <td>Delete</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include_once "footer.php";
?>

<?php
if (isset($_SESSION['status']) && $_SESSION['status'] != '') {
?>
  <!-- notification design -->

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo $_SESSION['status_code']; ?>',
        title: '<?php echo $_SESSION['status']; ?>',
        showConfirmButton: false,
        timer: 2000
      });
    });
  </script>

<?php
  unset($_SESSION['status'], $_SESSION['status_code']);
}
?>

<script>
  $(document).ready(function() {
    $('#table_category').DataTable();

    // Delete Confirmation - Removed as per user preference
    $(document).on('click', '.btn-delete-supplier', function(e) {
        let id = $(this).val();
        $('<form method="post" style="display:none;">' +
            '<input type="hidden" name="btndelete" value="' + id + '">' +
          '</form>').appendTo('body').submit();
    });

    // Confirm on Save/Update - Removed as per user preference
    $('form').on('submit', function(e) {
        // No confirmation, just submit
    });
  });
</script>