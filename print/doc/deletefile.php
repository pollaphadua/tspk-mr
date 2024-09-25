<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');
require '../../vendor/autoload.php';

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));

$truckfrom_file = 'truckfrom/TRUCKCONTROL_' . $doc . '.pdf';
$pus_file = 'pickupsheet/merge_pus/PICKUPSHEET_' . $doc . '.pdf';
$doc_all_file = 'merge_doc/'.$doc . '.pdf';
unlink($truckfrom_file);
unlink($pus_file);
unlink($doc_all_file);

$sql  = "SELECT 
tts.truck_Control_No,
ttl.pus_No
FROM
tbl_transaction tts
	INNER JOIN
tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
WHERE
tts.truck_Control_No = '$doc'
	AND tts.tran_status != 'PENDING'
	AND ttl.Pick != 'N'
	AND ttl.Status_Pickup = 'PICKUP'
GROUP BY ttl.pus_No
ORDER BY ttl.pus_No;";
$re1 = sqlError($mysqli, __LINE__, $sql, 1);
if ($re1->num_rows == 0) {
	throw new Exception('ไม่พบข้อมูล ' . __LINE__);
}
while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
	$pus_No = $row['pus_No'];
	$pus_file_pre = 'pickupsheet/pus_pre/PICKUPSHEET_' . $pus_No . '.pdf';
	unlink($pus_file_pre);
}