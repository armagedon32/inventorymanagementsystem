<?php

include_once 'connectdb.php';
session_start();

include_once "header.php";

include 'barcode/barcode128.php';


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
            View Supply
          </h1>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">

          <div class="card card-info card-outline">
            <div class="card-header">
              <h5 class="m-0">View Supplies</h5>
            </div>
            <div class="card-body">


              <?php
              $id = $_GET['id'];

              $select = $pdo->prepare("select * from tbl_product where pid =$id");
              $select->execute();

              // PRODUCT DETAILS AND INFO
              while ($row = $select->fetch(PDO::FETCH_OBJ)) {
                echo '
  
  <div class="row">
   <div class= "col-md-6" >

   <ul class="list-group">

   <center><p class="list-group-item list-group-item-info"><b>PRODUCT DETAILS</b></p></center>
   
     <li class="list-group-item"> <b> Barcode </b> <span class="badge badge-light float-right">' . bar128($row->barcode) . '</span></li>
     <li class="list-group-item"> <b> Name </b> <span class="badge badge-primary float-right">' . $row->name . '</span></li>
     <li class="list-group-item"> <b> Brand </b> <span class="badge badge-warning float-right">' . $row->brand . '</span></li>
     <li class="list-group-item"> <b> Acquisition Type </b> <span class="badge badge-success float-right">' . $row->acquisition_type . '</span></li>
     <li class="list-group-item"> <b> Category </b> <span class="badge badge-primary float-right">' . $row->category . '</span></li>
     <li class="list-group-item"> <b> Description </b> <span class="badge badge-danger float-right">' . $row->description . '</span></li>
     <li class="list-group-item"> <b> Stock </b> <span class="badge badge-secondary float-right">' . $row->stock . '</span></li>
     <li class="list-group-item"> <b> Reorder Level </b> <span class="badge badge-dark float-right">' . $row->reorder_level . '</span></li>

   </ul>

  </div>

  <div class= "col-md-6" >

   <ul class="list-group">

   <center><p class="list-group-item list-group-item-info"><b>PRODUCT IMAGE</b></p></center>

   <img src="productimage/' . $row->image . '" class="img-responsive"/>



   </ul>

  </div>
  </div>
  ';
              }


              ?>


            </div>
          </div>
        </div>
        <!-- /.col-md-6 -->
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->


<?php

include_once "footer.php";


?>