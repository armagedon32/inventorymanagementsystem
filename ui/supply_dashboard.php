<?php
include_once 'connectdb.php';
session_start();

// ================= SESSION CHECK =================
if ($_SESSION['useremail'] == "" || $_SESSION['role'] == "User") {
    header('location:../index.php');
    exit();
}

// Include headers
if ($_SESSION['role'] == "User") {
    include_once "headeruser.php";
} else {
    include_once "header.php";
}

// ================= HANDLE SAVE / UPDATE SUPPLY =================
if (isset($_POST['btn_save'])) {
    $id = $_POST['supply_id'] ?? null;
    $name = $_POST['supply_name'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $supplier = $_POST['supplier'];
    $status = $_POST['status'];

    if (!$id) {
        $date_part = date('Ymd');
        $last_id = $pdo->query("SELECT MAX(id) AS last_id FROM tbl_supply")->fetchColumn();
        $new_id = $last_id ? $last_id + 1 : 1;
        $asset_tag = "SUP-{$date_part}-" . str_pad($new_id, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO tbl_supply 
            (asset_tag, supply_name, category, quantity, unit, supplier, status)
            VALUES (:asset_tag, :name, :category, :quantity, :unit, :supplier, :status)");
        $stmt->execute(compact('asset_tag','name','category','quantity','unit','supplier','status'));
        $_SESSION['status'] = "Supply registered successfully!";
    } else {
        $stmt = $pdo->prepare("UPDATE tbl_supply SET supply_name=:name, category=:category, quantity=:quantity, unit=:unit, supplier=:supplier, status=:status WHERE id=:id");
        $stmt->execute(compact('name','category','quantity','unit','supplier','status','id'));
        $_SESSION['status'] = "Supply updated successfully!";
    }
}

// ================= HANDLE BULK CSV UPLOAD =================
if (isset($_POST['btn_bulk']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if(count($data) >= 6) {
                $date_part = date('Ymd');
                $last_id = $pdo->query("SELECT MAX(id) AS last_id FROM tbl_supply")->fetchColumn();
                $new_id = $last_id ? $last_id + 1 : 1;
                $asset_tag = "SUP-{$date_part}-" . str_pad($new_id, 3, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO tbl_supply 
                    (asset_tag, supply_name, category, quantity, unit, supplier, status)
                    VALUES (:asset_tag, :name, :category, :quantity, :unit, :supplier, :status)");
                $stmt->execute([
                    'asset_tag' => $asset_tag,
                    'name' => $data[0],
                    'category' => $data[1],
                    'quantity' => $data[2],
                    'unit' => $data[3],
                    'supplier' => $data[4],
                    'status' => $data[5],
                ]);
            }
        }
        fclose($handle);
        $_SESSION['status'] = "CSV uploaded successfully!";
    }
}

// ================= FETCH TOTAL AND LOW STOCK =================
$total_supplies = $pdo->query("SELECT COUNT(*) FROM tbl_supply")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM tbl_supply WHERE quantity < 5")->fetchColumn();

// ================= FETCH ALL SUPPLIES =================
$supplies = $pdo->query("SELECT * FROM tbl_supply ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ================= Classic Design ================= */
body { font-family: Arial, sans-serif; background: #f4f4f4; }
.content-wrapper { padding:20px; }
.card { background:#fff; padding:15px; margin-bottom:20px; border:1px solid #ccc; border-radius:5px; }
.card h2 { margin-top:0; font-size:18px; }
.card .card-footer { margin-top:10px; font-size:14px; }

.table { width:100%; border-collapse:collapse; margin-top:10px; }
.table th, .table td { border:1px solid #999; padding:8px; text-align:left; }
.table th { background:#eee; }

input, select, button { padding:6px; margin:4px 0; border:1px solid #999; border-radius:3px; }
button { background:#007bff; color:#fff; cursor:pointer; }
button:hover { background:#0056b3; }

.btn-edit { background:#28a745; color:white; border:none; padding:4px 8px; border-radius:3px; cursor:pointer; }
.btn-edit:hover { background:#1e7e34; }

.notice { color:green; font-weight:bold; margin-bottom:10px; }
</style>

<div class="content-wrapper">
    <h1>Supply Dashboard</h1>

    <!-- <div class="card">
        <h2>Total Supplies: <?= $total_supplies ?></h2>
        <h2>Low Stock (&lt;5): <?= $low_stock ?></h2>
    </div> -->

    <!-- Registration Form -->
    <div class="card">
        <h2>Add / Update Supply</h2>
        <?php if(isset($_SESSION['status'])): ?>
            <div class="notice"><?= $_SESSION['status']; unset($_SESSION['status']); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="supply_id" id="supply_id">
            <label>Supply Name</label>
            <input type="text" name="supply_name" id="supply_name" required>
            <label>Category</label>
            <input type="text" name="category" id="category">
            <label>Quantity</label>
            <input type="number" name="quantity" id="quantity" required>
            <label>Unit</label>
            <input type="text" name="unit" id="unit">
            <label>Supplier</label>
            <input type="text" name="supplier" id="supplier">
            <label>Status</label>
            <select name="status" id="status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
            <br>
            <button type="submit" name="btn_save">Save Supply</button>
        </form>

        <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
            <label>Bulk CSV Upload</label>
            <input type="file" name="csv_file" accept=".csv">
            <button type="submit" name="btn_bulk">Upload CSV</button>
            <small>Format: Name,Category,Quantity,Unit,Supplier,Status</small>
        </form>
    </div>

    <!-- Supplies Table -->
    <div class="card">
        <h2>Supplies List</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($supplies as $supply): ?>
                <tr>
                    <td><?= $supply['asset_tag'] ?></td>
                    <td><?= $supply['supply_name'] ?></td>
                    <td><?= $supply['category'] ?></td>
                    <td><?= $supply['quantity'] ?></td>
                    <td><?= $supply['unit'] ?></td>
                    <td><?= $supply['supplier'] ?></td>
                    <td><?= $supply['status'] ?></td>
                    <td>
                        <button class="btn-edit"
                            data-id="<?= $supply['id'] ?>"
                            data-name="<?= $supply['supply_name'] ?>"
                            data-category="<?= $supply['category'] ?>"
                            data-quantity="<?= $supply['quantity'] ?>"
                            data-unit="<?= $supply['unit'] ?>"
                            data-supplier="<?= $supply['supplier'] ?>"
                            data-status="<?= $supply['status'] ?>">Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Edit supply
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('supply_id').value = this.dataset.id;
        document.getElementById('supply_name').value = this.dataset.name;
        document.getElementById('category').value = this.dataset.category;
        document.getElementById('quantity').value = this.dataset.quantity;
        document.getElementById('unit').value = this.dataset.unit;
        document.getElementById('supplier').value = this.dataset.supplier;
        document.getElementById('status').value = this.dataset.status;
        window.scrollTo({top:0, behavior:'smooth'});
    });
});
</script>

<?php include_once "footer.php"; ?>