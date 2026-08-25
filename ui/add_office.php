<?php
include_once "connectdb.php";
session_start();

if (isset($_POST['btn_save'])) {
  $name = $_POST['office_name'];
  $location = $_POST['office_location'];
  $contact = $_POST['office_contact'];

  $insert = $pdo->prepare("INSERT INTO tbl_office(office_name, office_location, office_contact) VALUES (:name, :location, :contact)");
  $insert->bindParam(':name', $name);
  $insert->bindParam(':location', $location);
  $insert->bindParam(':contact', $contact);

  if ($insert->execute()) {
    header("location:office.php");
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Add New Office</h1>
  </section>
  <section class="content">
    <form method="POST">
      <div class="card-body">
        <div class="form-group">
          <label>Office Name</label>
          <input type="text" class="form-control" name="office_name" required>
        </div>
        <div class="form-group">
          <label>Officer In Charge</label>
          <input type="text" class="form-control" name="office_location">
        </div>
        <div class="form-group">
          <label>Contact</label>
          <input type="text" class="form-control" name="office_contact">
        </div>
        <button type="submit" name="btn_save" class="btn btn-primary">Save</button>
      </div>
    </form>
  </section>
</div>
