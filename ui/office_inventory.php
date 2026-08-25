<?php
include_once 'connectdb.php';
session_start();

if ($_SESSION['useremail'] == "" || $_SESSION['role'] == "") {
    header('location:../index.php');
}

// Get office ID from URL
if (!isset($_GET['id'])) {
    echo "<script>alert('Office not specified!'); window.location='office.php';</script>";
    exit;
}

$office_id = $_GET['id'];

// Get office details
$office_select = $pdo->prepare("SELECT * FROM tbl_office WHERE id=:id");
$office_select->bindParam(':id', $office_id);
$office_select->execute();
$office = $office_select->fetch(PDO::FETCH_ASSOC);

if (!$office) {
    echo "<script>alert('Office not found!'); window.location='office.php';</script>";
    exit;
}

if($_SESSION['role']=="Admin"){
    include_once "header.php";
} else {
    include_once "headeruser.php";
}
?>

<style>
.inventory-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.inventory-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    flex: 1;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.stat-card p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.back-btn {
    display: inline-block;
    padding: 8px 16px;
    background-color: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 15px;
}

.back-btn:hover {
    background-color: #5a6268;
    color: white;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <a href="javascript:history.back()" class="btn btn-secondary btn-sm mr-2"><i class="fas fa-arrow-left"></i></a>
                        Office Inventory
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Office Info Header -->
            <div class="inventory-header">
                <h2><?php echo htmlspecialchars($office['office_name']); ?></h2>
                <p><strong>Officer:</strong> <?php echo htmlspecialchars($office['address']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($office['contact']); ?></p>
            </div>

            <!-- Statistics Cards -->
            <?php
            // Get inventory statistics
            $total_items = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(stock), 0) as total_stock FROM tbl_product WHERE office_id = :office_id");
            $total_items->bindParam(':office_id', $office_id);
            $total_items->execute();
            $stats = $total_items->fetch(PDO::FETCH_ASSOC);

            $total_value = $pdo->prepare("SELECT COALESCE(SUM(stock * purchaseprice), 0) as value FROM tbl_product WHERE office_id = :office_id");
            $total_value->bindParam(':office_id', $office_id);
            $total_value->execute();
            $value = $total_value->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div class="inventory-stats">
                <div class="stat-card">
                    <h3><?php echo $stats['count']; ?></h3>
                    <p>Total Items</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['total_stock']; ?></h3>
                    <p>Total Stock</p>
                </div>
                <div class="stat-card">
                    <h3>₱<?php echo number_format($value['value'], 2); ?></h3>
                    <p>Total Value</p>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Inventory List</h3>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover" id="table_inventory">
                        <thead>
                            <tr>
                                <th>Barcode</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Stock</th>
                                <th>Purchase Price</th>
                                <th>Sale Price</th>
                                <th>Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $select = $pdo->prepare("SELECT * FROM tbl_product WHERE office_id = :office_id ORDER BY pid DESC");
                            $select->bindParam(':office_id', $office_id);
                            $select->execute();

                            while ($row = $select->fetch(PDO::FETCH_OBJ)) {
                                $item_value = $row->stock * $row->purchaseprice;
                            ?>
                            <tr>
                                <td><?php echo $row->barcode; ?></td>
                                <td><?php echo $row->product; ?></td>
                                <td><?php echo $row->category; ?></td>
                                <td><?php echo $row->suppliername; ?></td>
                                <td>
                                    <?php if($row->stock <= 0): ?>
                                        <span class="badge badge-danger"><?php echo $row->stock; ?></span>
                                    <?php elseif($row->stock < 10): ?>
                                        <span class="badge badge-warning"><?php echo $row->stock; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?php echo $row->stock; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?php echo number_format($row->purchaseprice, 2); ?></td>
                                <td>₱<?php echo number_format($row->saleprice, 2); ?></td>
                                <td>₱<?php echo number_format($item_value, 2); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="viewproduct.php?id=<?php echo $row->pid; ?>" class="btn btn-info btn-xs" role="button">
                                            <span class="fa fa-eye" style="color:#ffffff" data-toggle="tooltip" title="View"></span>
                                        </a>
                                        <a href="editproduct.php?id=<?php echo $row->pid; ?>" class="btn btn-success btn-xs" role="button">
                                            <span class="fa fa-edit" style="color:#ffffff" data-toggle="tooltip" title="Edit"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include_once "footer.php"; ?>

<script>
$(document).ready(function() {
    $('#table_inventory').DataTable({
        "order": [[0, "desc"]]
    });
});
</script>
