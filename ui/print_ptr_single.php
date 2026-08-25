<?php
include_once 'connectdb.php';
session_start();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No PTR ID provided.");
}

$ptr_id = $_GET['id'];

// Fetch PTR header
$headerStmt = $pdo->prepare("
    SELECT h.*, 
           f.office_name AS from_office_name,
           f.address AS from_oic,
           t.office_name AS to_office_name,
           t.address AS to_oic
    FROM ptr_header h
    LEFT JOIN tbl_office f ON h.from_office = f.id
    LEFT JOIN tbl_office t ON h.to_office = t.id
    WHERE h.id = :id
    LIMIT 1
");
$headerStmt->execute([':id' => $ptr_id]);
$header = $headerStmt->fetch(PDO::FETCH_OBJ);

if (!$header) {
    die("Error: PTR not found.");
}

// Fetch PTR items with property details including image
$itemsStmt = $pdo->prepare("
    SELECT i.*, p.inventory_no, p.item_name, p.serial_no, p.brand, p.description, p.image, p.property_id
    FROM ptr_items i
    LEFT JOIN tbl_property p ON i.property_id = p.property_id
    WHERE i.ptr_id = :ptr_id
    ORDER BY i.id ASC
");
$itemsStmt->execute([':ptr_id' => $ptr_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_OBJ);

// Fetch Global OICs for Signatures (Matching borrowed.php logic)
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
    <title>Print PTR - <?= htmlspecialchars($header->ptr_no) ?></title>
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
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            display: flex;
            align-items: flex-start;
        }
        .label-custom {
            font-weight: 700;
            color: #495057;
            min-width: 140px;
            max-width: 140px;
            display: inline-block;
            font-size: 0.9rem;
        }
        .value-custom {
            flex: 1;
            font-size: 1rem;
            color: #212529;
        }
        .signature-section {
            margin-top: 50px;
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
        .item-card {
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
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

    <h4 class="text-center text-uppercase font-weight-bold mb-4" style="text-decoration: underline;">Property Transfer Receipt</h4>

    <div class="container-fluid px-0">
        <!-- PTR HEADER INFO -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="detail-row"><span class="label-custom">PTR No:</span><span class="value-custom"><b><?= htmlspecialchars($header->ptr_no) ?></b></span></div>
                <div class="detail-row"><span class="label-custom">Transfer Date:</span><span class="value-custom"><?= htmlspecialchars($header->transfer_date) ?></span></div>
            </div>
            <div class="col-6">
                <div class="detail-row"><span class="label-custom">From Office:</span><span class="value-custom"><?= htmlspecialchars($header->from_office_name ?: 'N/A') ?></span></div>
                <div class="detail-row"><span class="label-custom">To Office:</span><span class="value-custom"><?= htmlspecialchars($header->to_office_name ?: 'N/A') ?></span></div>
            </div>
            <div class="col-12">
                <div class="detail-row"><span class="label-custom">Remarks:</span><span class="value-custom"><?= htmlspecialchars($header->remarks ?: 'N/A') ?></span></div>
            </div>
        </div>

        <h5 class="font-weight-bold mb-3">Transferred Items</h5>

        <?php foreach ($items as $item): ?>
        <div class="item-card">
            <div class="row">
                <div class="col-7">
                    <div class="detail-row"><span class="label-custom">Inventory No:</span><span class="value-custom"><?= htmlspecialchars($item->inventory_no) ?></span></div>
                    <div class="detail-row"><span class="label-custom">Item Name:</span><span class="value-custom"><?= htmlspecialchars($item->item_name) ?></span></div>
                    <div class="detail-row"><span class="label-custom">Serial No:</span><span class="value-custom"><?= htmlspecialchars($item->serial_no ?: 'N/A') ?></span></div>
                    <div class="detail-row"><span class="label-custom">Brand:</span><span class="value-custom"><?= htmlspecialchars($item->brand ?: 'N/A') ?></span></div>
                    <div class="detail-row"><span class="label-custom">Quantity:</span><span class="value-custom"><?= htmlspecialchars($item->quantity) ?></span></div>
                </div>
                <div class="col-5 text-center">
                    <?php if(!empty($item->image) && file_exists("productimage/".$item->image)): ?>
                        <img src="productimage/<?= $item->image ?>" style="max-height: 120px; width: auto; border: 1px solid #eee;">
                    <?php else: ?>
                        <div style="height: 120px; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc; color: #999; font-size: 0.8rem;">No Image</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- SIGNATURE SECTION -->
    <div style="margin-top:50px; display: flex; justify-content: space-between;">
        
        <!-- Left Side: Prepared & Released -->
        <div>
            <div style="margin-bottom: 40px;">
                <p style="margin:0; font-weight:bold;">Prepared by:</p>
                <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">
                    <span style="font-weight:bold; text-transform:uppercase;"><?= htmlspecialchars($prop_oic) ?></span>
                </div>
                <p style="margin:0; text-align:center;">Property/Supplies Officer</p>
            </div>
            
            <div>
                <p style="margin:0; font-weight:bold;">Released by:</p>
                <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">
                    <span style="font-weight:bold; text-transform:uppercase;"><?= htmlspecialchars($header->from_oic ?: '____________________') ?></span>
                </div>
                <p style="margin:0; text-align:center;"><?= htmlspecialchars($header->from_office_name ?: 'Office') ?></p>
            </div>
        </div>

        <!-- Right Side: Received By -->
        <div>
            <div style="margin-top:100px;"> <!-- Space to align with Released By -->
                <p style="margin:0; font-weight:bold;">Received by:</p>
                <div style="margin-top:30px; width:250px; border-bottom:1px solid #000; text-align:center;">
                    <span style="font-weight:bold; text-transform:uppercase;"><?= htmlspecialchars($header->to_oic ?: '____________________') ?></span>
                </div>
                <p style="margin:0; text-align:center;"><?= htmlspecialchars($header->to_office_name ?: 'Office') ?></p>
            </div>
        </div>
        
    </div>

</body>
</html>
