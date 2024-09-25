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
include('../common/common.php');

$entry_project = $_SESSION['xxxEntryProject'];

$where = [];
$exlode = explode(' | ', $entry_project);
foreach ($exlode as $Customer) {
	$sql = "SELECT 
		BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
	FROM 
		tbl_customer_master 
	WHERE 
		Customer_Code = '$Customer';";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];

	$where[] = "t1.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	$sqlWhere = join(' OR ', $where);
}

if ($_REQUEST['type'] == 1) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT Route_Code AS value
    FROM
		tbl_route_master t1
    WHERE
		Route_Code LIKE '%$val%'
			AND Status = 'ACTIVE' AND ($sqlWhere)
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
	toArrayStringOne($mysqli->query("SELECT DISTINCT Route_Code FROM tbl_route_master t1 WHERE Status = 'ACTIVE' AND ($sqlWhere)"), 1);
} else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "WITH a AS (
		SELECT
		CONCAT(
        if(route_special = 'Y' OR route_special = 'N', Route_Code, CONCAT(Route_Code,' ', route_special)),' | ', GROUP_CONCAT(DISTINCT Supplier_Name_Short ORDER BY t1.Route_Code, t1.Status_Pickup, t1.Route_ID, t1.planin_time SEPARATOR ', ')) AS Route_Code,
		t3.Customer_Code
		FROM `tspk-mr`.tbl_route_master t1
		INNER JOIN tbl_supplier_master t2 ON t1.Supplier_ID = t2.Supplier_ID
        INNER JOIN tbl_customer_master t3 ON t1.Customer_ID = t3.Customer_ID
		WHERE t1.Status = 'ACTIVE' AND ($sqlWhere)
		GROUP BY t1.Route_Code, t3.Customer_Code)
		SELECT Route_Code AS value FROM a
		WHERE Route_Code LIKE '%$val%'
		ORDER BY a.Customer_Code, a.Route_Code;";
	//exit($sql);
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
	$sql = "WITH a AS (
		SELECT
        CONCAT(
        if(route_special = 'Y' OR route_special = 'N', Route_Code, CONCAT(Route_Code,' ', route_special)),' | ', GROUP_CONCAT(DISTINCT Supplier_Name_Short ORDER BY t1.Route_Code, t1.Status_Pickup, t1.Route_ID, t1.planin_time SEPARATOR ', ')) AS Route_Code,
        t3.Customer_Code
		FROM `tspk-mr`.tbl_route_master t1
		INNER JOIN tbl_supplier_master t2 ON t1.Supplier_ID = t2.Supplier_ID
        INNER JOIN tbl_customer_master t3 ON t1.Customer_ID = t3.Customer_ID
		WHERE t1.Status = 'ACTIVE' AND ($sqlWhere)
		GROUP BY t1.Route_Code, t3.Customer_Code)
		SELECT a.Route_Code FROM a ORDER BY a.Customer_Code, a.Route_Code;";
	//exit($sql);
	toArrayStringOne($mysqli->query($sql), 1);
} else if ($_REQUEST['type'] == 5) {
	$sql = "WITH a AS (
		SELECT
        CONCAT(
        if(route_special = 'Y' OR route_special = 'N', Route_Code, CONCAT(Route_Code,' ', route_special)),' | ', GROUP_CONCAT(DISTINCT Supplier_Name_Short ORDER BY t1.Route_Code, t1.Status_Pickup, t1.Route_ID, t1.planin_time SEPARATOR ', ')) AS Route_Code,
        t3.Customer_Code
		FROM `tspk-mr`.tbl_route_master t1
		INNER JOIN tbl_supplier_master t2 ON t1.Supplier_ID = t2.Supplier_ID
        INNER JOIN tbl_customer_master t3 ON t1.Customer_ID = t3.Customer_ID
		WHERE ($sqlWhere) AND t1.Status = 'ACTIVE'
		GROUP BY t1.Route_Code, t3.Customer_Code)
		SELECT a.Route_Code FROM a ORDER BY a.Customer_Code, a.Route_Code;";
	//exit($sql);
	toArrayStringOne($mysqli->query($sql), 1);
} else if ($_REQUEST['type'] == 6) {

	$Customer_Code = $_REQUEST['customer'];

	if ($Customer_Code != '') {
		$sql = "SELECT 
			BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
		FROM 
			tbl_customer_master 
		WHERE 
			Customer_Code = '$Customer_Code';";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];

		$sqlWhere = "t1.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	}

	$sql = "WITH a AS (
		SELECT
        CONCAT(
        if(route_special = 'Y' OR route_special = 'N', Route_Code, CONCAT(Route_Code,' ', route_special)),' | ', GROUP_CONCAT(DISTINCT Supplier_Name_Short ORDER BY t1.Route_Code, t1.Status_Pickup, t1.Route_ID, t1.planin_time SEPARATOR ', ')) AS Route_Code,
        t3.Customer_Code
		FROM `tspk-mr`.tbl_route_master t1
		INNER JOIN tbl_supplier_master t2 ON t1.Supplier_ID = t2.Supplier_ID
        INNER JOIN tbl_customer_master t3 ON t1.Customer_ID = t3.Customer_ID
		WHERE t1.Status = 'ACTIVE' AND ($sqlWhere)
		GROUP BY t1.Route_Code, t3.Customer_Code)
		SELECT a.Route_Code FROM a ORDER BY a.Customer_Code, a.Route_Code;";
	//exit($sql);
	toArrayStringOne($mysqli->query($sql), 1);
}


$mysqli->close();
exit();
