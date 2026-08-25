<?php
include_once 'connectdb.php';
session_start();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No property ID provided.");
}

$id = $_GET['id'];

$select = $pdo->prepare("
    SELECT 
        p.*, 
        o.office_name, 
        o.address AS office_oic,
        i.fullname AS instructor_name,
        c.category AS category_name
    FROM tbl_property p
    LEFT JOIN tbl_office o ON p.office_id = o.id
    LEFT JOIN tbl_instructors i ON p.instructor_id = i.id
    LEFT JOIN tbl_item it ON p.item_name = it.item_name
    LEFT JOIN tbl_category c ON it.category_id = c.catid
    WHERE p.property_id = :id
    LIMIT 1
");
$select->execute([':id' => $id]);
$row = $select->fetch(PDO::FETCH_OBJ);

if (!$row) {
    die("Error: Property not found.");
}

// OIC and President names for signatures
$stmtOIC = $pdo->query("SELECT id, office_name, address FROM tbl_office WHERE office_name LIKE '%Property%' OR office_name LIKE '%President%'");
$oics = $stmtOIC->fetchAll(PDO::FETCH_ASSOC);
$prop_oic = "MARITES MENDIGORIN";
$pres_oic = "DR. ROSELY H. AGUSTIN";

foreach($oics as $o){
    if(stripos($o['office_name'], 'Property') !== false && !empty($o['address'])) $prop_oic = $o['address'];
    if(stripos($o['office_name'], 'President') !== false && !empty($o['address'])) $pres_oic = $o['address'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Property Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            padding: 40px;
            font-family: "Source Sans Pro",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
            background-color: #fff;
            color: #212529;
        }
        .header-section {
            border-bottom: 2px solid #212529;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .detail-row {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            display: flex;
            align-items: flex-start;
        }
        .label-custom {
            font-weight: 700;
            color: #495057;
            min-width: 160px;
            max-width: 160px;
            display: inline-block;
            font-size: 0.9rem;
        }
        .value-custom {
            flex: 1;
            font-size: 1rem;
            color: #212529;
            text-align: justify;
        }
        .signature-section {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 250px;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #212529;
            margin-bottom: 5px;
            font-weight: 700;
            text-transform: uppercase;
            padding-bottom: 5px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- COLLEGE HEADER -->
    <div class="header-section d-flex align-items-center justify-content-center">
        <img src="../dist/img/logo.png" style="width:80px; margin-right:20px;">
        <div style="text-align:center;">
            <h4 class="font-weight-bold mb-0">KOLEHIYO NG SUBIC</h4>
            <p class="mb-0">WFI Compound, Wawandue, Subic, Zambales</p>
            <p class="mb-0">Tel.no.: (047)232 – 4896/232-4897</p>
        </div>
    </div>

    <h4 class="text-center text-uppercase font-weight-bold mb-5" style="text-decoration: underline;">Property Information Details</h4>

    <div class="container-fluid px-0">
        <div class="row">
            
            <!-- LEFT COLUMN -->
            <div class="col-6 pr-4 border-right">
                
                <div class="detail-row"><span class="label-custom">Serial No:</span><span class="value-custom"><?= htmlspecialchars($row->serial_no ?: 'N/A') ?></span></div>
                <div class="detail-row"><span class="label-custom">Category:</span><span class="value-custom"><?= htmlspecialchars($row->category_name ?: 'N/A') ?></span></div>
                <div class="detail-row"><span class="label-custom">Item Name:</span><span class="value-custom"><?= htmlspecialchars($row->item_name) ?></span></div>
                <div class="detail-row"><span class="label-custom">Brand:</span><span class="value-custom"><?= htmlspecialchars($row->brand ?: 'N/A') ?></span></div>
                <div class="detail-row"><span class="label-custom">Acquisition:</span><span class="value-custom"><?= htmlspecialchars($row->acquisition_type ?: 'N/A') ?></span></div>
                <div class="detail-row"><span class="label-custom">Description:</span><span class="value-custom"><?= htmlspecialchars($row->description ?: 'N/A') ?></span></div>
                
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-6 pl-4">
                
                <div class="detail-row"><span class="label-custom">Quantity:</span><span class="value-custom"><?= htmlspecialchars($row->quantity) ?></span></div>
                <div class="detail-row"><span class="label-custom">Date Added:</span><span class="value-custom"><?= htmlspecialchars($row->date_added) ?></span></div>
                <div class="detail-row"><span class="label-custom">Office:</span><span class="value-custom"><?= htmlspecialchars($row->office_name ?: 'Unassigned') ?></span></div>
                <div class="detail-row"><span class="label-custom">Received By:</span><span class="value-custom">
                    <?php 
                    if(!empty($row->instructor_name)){
                        echo htmlspecialchars($row->instructor_name);
                    } elseif(!empty($row->office_oic)){
                        echo htmlspecialchars($row->office_oic);
                    } else {
                        echo 'Unassigned';
                    }
                    ?>
                </span></div>
                <div class="detail-row"><span class="label-custom">Inventory No:</span><span class="value-custom"><?= htmlspecialchars($row->inventory_no) ?></span></div>
                <div class="detail-row"><span class="label-custom">Remarks:</span><span class="value-custom"><?= htmlspecialchars($row->remarks ?: 'N/A') ?></span></div>

            </div>

        </div>

        <!-- IMAGE SECTION (BELOW DETAILS) -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <p class="label-custom mb-3 text-left">Property Image</p>
                <?php if(!empty($row->image) && file_exists("productimage/".$row->image)): ?>
                    <div style="text-align: center; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px; display: inline-block;">
                        <img src="productimage/<?= $row->image ?>" style="max-height: 250px; width: auto;">
                    </div>
                <?php else: ?>
                    <div class="p-4 border bg-light text-muted small text-center">No Image Available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SIGNATURE SECTION (AT THE BOTTOM) -->
    <div class="signature-section">
        
        <!-- Prepared By -->
        <div class="signature-box">
            <p class="text-left font-weight-bold mb-5">Prepared by:</p>
            <div class="signature-line"><?= htmlspecialchars($prop_oic) ?></div>
            <p class="mb-0">Property/Supplies Officer</p>
        </div>

        <!-- Received By -->
        <div class="signature-box">
            <p class="text-left font-weight-bold mb-5">Received by:</p>
            <div class="signature-line">
                <?php 
                if(!empty($row->instructor_name)){
                    echo htmlspecialchars($row->instructor_name);
                } elseif(!empty($row->office_oic)){
                    echo htmlspecialchars($row->office_oic);
                } else {
                    echo '____________________';
                }
                ?>
            </div>
            <p class="small text-muted"><?= htmlspecialchars($row->office_name ?: '____________________') ?></p>
        </div>
        
    </div>

</body>
</html>
