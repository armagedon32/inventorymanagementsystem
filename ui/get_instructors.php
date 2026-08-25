<?php
include_once 'connectdb.php';
header('Content-Type: application/json');

$office_id = $_GET['office_id'] ?? 0;
$oic_only = $_GET['oic_only'] ?? 0;
$data = [];

if($office_id){
    // 1. Get the office name first
    $stmtOffice = $pdo->prepare("SELECT office_name, address FROM tbl_office WHERE id = :id AND is_archived = 0");
    $stmtOffice->execute([':id' => $office_id]);
    $office = $stmtOffice->fetch(PDO::FETCH_ASSOC);

    if ($office) {
        $office_name = $office['office_name'];
        $oic_name = $office['address'];

        if(!$oic_only) {
            // 2. Get ALL instructors assigned to this office via assigned_dept
            $stmtInst = $pdo->prepare("
                SELECT id, fullname
                FROM tbl_instructors
                WHERE assigned_dept = :office_name
                  AND is_archived = 0
                ORDER BY fullname ASC
            ");
            $stmtInst->execute([':office_name' => $office_name]);
            $data = $stmtInst->fetchAll(PDO::FETCH_ASSOC);
        }

        // 3. Also check if the OIC (from address field) is in the list
        if(!empty($oic_name)){
            $alreadyIn = false;
            if(!$oic_only) {
                foreach($data as $d){
                    if($d['fullname'] == $oic_name){
                        $alreadyIn = true;
                        break;
                    }
                }
            }
            
            if(!$alreadyIn){
                $stmtCheck = $pdo->prepare("SELECT id, fullname FROM tbl_instructors WHERE fullname = ? AND is_archived = 0");
                $stmtCheck->execute([$oic_name]);
                $oic_inst = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                if($oic_inst){
                    $data[] = ['id'=>$oic_inst['id'], 'fullname'=>$oic_inst['fullname'] . " (OIC)"];
                } else {
                    $data[] = ['id'=>'', 'fullname'=>$oic_name . " (OIC)"];
                }
            }
        }
        
        // Reformat for select2 if needed, though current stockout.php expects id and name
        $data = array_map(function($item) {
            return ['id' => $item['id'], 'name' => $item['fullname']];
        }, $data);
    }
}

echo json_encode($data);