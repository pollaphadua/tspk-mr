<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');
require '../../vendor/autoload.php';

$arrContextOptions = array(
	"ssl" => array(
		"verify_peer" => false,
		"verify_peer_name" => false,
	),
);


$pdf = new \Jurosh\PDFMerge\PDFMerger;

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$sql = "SELECT 
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
//exit($sql);
$re1 = sqlError($mysqli, __LINE__, $sql, 1);
if ($re1->num_rows == 0) {
	throw new Exception('ไม่พบข้อมูล ' . __LINE__);
}
while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
	$pus_No = $row['pus_No'];
	$truck_Control_No = $row['truck_Control_No'];
	$ip_server = $_SERVER['SERVER_NAME'];
	//echo($ip_server);
	$pus_file = file_get_contents('http://' . $ip_server . '/tspk-mr/print/doc/pickup_sheet.php?data=' . $pus_No, false, stream_context_create($arrContextOptions));
	$pdf->addPDF('pickupsheet/pus_pre/PICKUPSHEET_' . $pus_No . '.pdf', 'all', 'horizontal')
		->merge('file', 'pickupsheet/merge_pus/PICKUPSHEET_' . $truck_Control_No . '.pdf');

}

$sql = "SELECT 
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
//exit($sql);
$re1 = sqlError($mysqli, __LINE__, $sql, 1);
while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
	$pus_No = $row['pus_No'];
	/* -------- pickup sheet -------- */
	unlink('pickupsheet/pus_pre/PICKUPSHEET_' . $pus_No . '.pdf'); // delete file
}