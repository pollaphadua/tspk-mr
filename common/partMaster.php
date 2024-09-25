<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
/*  if(!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName']) )
    {
        echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
        exit();
    }
 */
include('../php/connection.php');

if ($_REQUEST['type'] == 1) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT part_no AS value
    FROM
		tbl_part_master
    WHERE
	(part_no LIKE '%$val%' OR part_name LIKE '%$val%')
			AND status = 'active'
    LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
} else if ($_REQUEST['type'] == 2) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT part_no FROM tbl_part_master WHERE status = 'active'"), 1);
} else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT concat(part_no, ' | ', part_name) AS value
    FROM
		tbl_part_master
    WHERE
		(part_no LIKE '%$val%' OR part_name LIKE '%$val%')
			AND status = 'active'
    LIMIT 5;";

	if ($re1 = $mysqli->query($sql)) {
		$row = array();
		while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
			$row[] = $result['value'];
		}
		echo json_encode($row);
	} else {
		echo "[]";
	}
} else if ($_REQUEST['type'] == 4) {
	$supplier = $_REQUEST['supplier'];

	toArrayStringOne($mysqli->query("SELECT DISTINCT 
		concat(t1.part_no, ' | ', t1.part_name) as part_no 
	FROM 
		tbl_part_master t1
			INNER JOIN 
		tbl_supplier_master t2 ON t1.supplier_id = t2.supplier_id
	WHERE 
		t2.supplier_code = '$supplier'
			AND t1.status = 'active';"), 1);
} else if ($_REQUEST['type'] == 5) {
	$part_no = $_REQUEST['obj'];

	$sql = "SELECT
		snp_in,
		box_per_pallet_in,
		(snp_in*box_per_pallet_in) as qty
	FROM
		tbl_part_master
	WHERE
		part_no = '$part_no';";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	closeDBT($mysqli, 1, jsonRow($re1, true, 0));
}

$mysqli->close();
exit();
