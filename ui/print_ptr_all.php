<?php
include_once 'connectdb.php';
session_start();

// Fetch ALL PTR RECORDS
$stmt = $pdo->prepare("
    SELECT 
        h.id,
        h.ptr_no,
        h.transfer_date,
        o1.office_name AS from_office,
        o2.office_name AS to_office,
        h.remarks
    FROM ptr_header h
    LEFT JOIN tbl_office o1 ON h.from_office = o1.id
    LEFT JOIN tbl_office o2 ON h.to_office = o2.id
    ORDER BY h.id DESC
");
$stmt->execute();
$ptr_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Print All Property Transfer Records</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 30px;
            font-family: Arial, sans-serif;
            background-color: #fff;
        }
        .header-section {
            border-bottom: 2px solid #212529;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .table {
            font-size: 0.9rem;
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

    <h4 class="text-center text-uppercase font-weight-bold mb-4">Property Transfer Records List</h4>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>PTR No</th>
                <th>Transfer Date</th>
                <th>From Office</th>
                <th>To Office</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ptr_records as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['ptr_no']) ?></td>
                <td><?= htmlspecialchars($row['transfer_date']) ?></td>
                <td><?= htmlspecialchars($row['from_office'] ?: 'N/A') ?></td>
                <td><?= htmlspecialchars($row['to_office'] ?: 'N/A') ?></td>
                <td><?= htmlspecialchars($row['remarks'] ?: 'N/A') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- SIGNATURE SECTION -->
    <div class="signature-section">
        <div class="signature-box">
            <p class="text-left font-weight-bold mb-4">Attested by:</p>
            <div class="signature-line"><?= htmlspecialchars($pres_oic) ?></div>
            <p class="mb-0 small">College President</p>
        </div>

        <div class="signature-box">
            <p class="text-left font-weight-bold mb-4">Prepared by:</p>
            <div class="signature-line"><?= htmlspecialchars($prop_oic) ?></div>
            <p class="mb-0 small">Property/Supplies Officer</p>
        </div>
    </div>

</body>
</html>
