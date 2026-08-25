<?php
ob_start();
include_once 'connectdb.php';
session_start();

if ($_SESSION['useremail'] == "" || ($_SESSION['role'] == "")) {
    header('location:../index.php');
    exit;
}

$id = $_GET['id'];
$select = $pdo->prepare("SELECT * FROM tbl_organization WHERE id=:id");
$select->execute([':id'=>$id]);
$row = $select->fetch(PDO::FETCH_ASSOC);

if(!$row){
    header('location:organization.php');
    exit;
}

$org_name = $row['org_name'];
$president = $row['president'];
$org_logo = $row['org_logo'];

if(isset($_POST['btn_update'])){
    $org_name_txt = $_POST['org_name'];
    $president_txt = $_POST['president'];
    
    $f_name = $_FILES['org_logo']['name'];
    if(!empty($f_name)){
        $f_tmp = $_FILES['org_logo']['tmp_name'];
        $f_size = $_FILES['org_logo']['size'];
        $f_extension = explode('.', $f_name);
        $f_extension = strtolower(end($f_extension));
        $f_newfile = uniqid() . '.' . $f_extension;
        $store = "uploads/org_logos/" . $f_newfile;
        
        if(move_uploaded_file($f_tmp, "../" . $store)){
            $update = $pdo->prepare("UPDATE tbl_organization SET org_name=:name, president=:president, org_logo=:logo WHERE id=:id");
            $update->execute([':name'=>$org_name_txt, ':president'=>$president_txt, ':logo'=>$store, ':id'=>$id]);
        }
    }else{
        $update = $pdo->prepare("UPDATE tbl_organization SET org_name=:name, president=:president WHERE id=:id");
        $update->execute([':name'=>$org_name_txt, ':president'=>$president_txt, ':id'=>$id]);
    }
    
    $_SESSION['status'] = "Organization Updated Successfully";
    $_SESSION['status_code'] = "success";
    header('location:office.php');
    exit;
}

if($_SESSION['role']=="Admin"){
    include_once "header.php";
}else{
    include_once "headeruser.php";
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Organization</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Edit Organization Details</h3>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Organization Name</label>
                                    <input type="text" class="form-control" name="org_name" value="<?= $org_name ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>President</label>
                                    <input type="text" class="form-control" name="president" value="<?= $president ?>" placeholder="Enter President Full Name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Logo</label><br>
                                    <?php if($org_logo): ?>
                                        <img src="../<?= $org_logo ?>" class="img-responsive" style="height:100px;">
                                    <?php else: ?>
                                        <p>No Logo Uploaded</p>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Update Logo</label>
                                    <input type="file" class="form-control" name="org_logo" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-warning shadow-sm" name="btn_update"><i class="fas fa-save mr-1"></i> Update Organization</button>
                        <a href="office.php" class="btn btn-secondary shadow-sm">Back to List</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include_once "footer.php"; ?>
