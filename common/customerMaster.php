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

$entry_project = $_SESSION['xxxEntryProject'];

$where = [];
$exlode = explode(' | ', $entry_project);
foreach ($exlode as $Customer) {
	$where[] = "Customer_Code = '$Customer'";
	$sqlWhere = join(' OR ', $where);
}

if ($_REQUEST['type'] == 1) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT Customer_Code AS value
    FROM
		tbl_customer_master
    WHERE
		Customer_Code LIKE '%$val%'
			AND Status = 'ACTIVE'
			AND ($sqlWhere)
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
	toArrayStringOne($mysqli->query("SELECT DISTINCT Customer_Code FROM tbl_customer_master WHERE Status = 'ACTIVE' AND ($sqlWhere);"), 1);
} else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT concat(Customer_Code, ' | ', Customer_Name) AS value
    FROM
		tbl_customer_master
    WHERE
		(Customer_Code LIKE '%$val%' OR Customer_Name LIKE '%$val%')
			AND Status = 'ACTIVE'
			AND ($sqlWhere)
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
	$sql = "SELECT DISTINCT concat(Customer_Code, ' | ', Customer_Name) FROM tbl_customer_master 
		WHERE Status = 'ACTIVE'
		AND ($sqlWhere);";
	toArrayStringOne($mysqli->query($sql), 1);
} else if ($_REQUEST['type'] == 5) {

	$sql = "SELECT DISTINCT Customer_Code FROM tbl_customer_master WHERE Status = 'ACTIVE' AND ($sqlWhere);";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	closeDBT($mysqli, 1, jsonRow($re1, true, 0));
}

$mysqli->close();
exit();
