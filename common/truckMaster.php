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

include('../common/common.php');
include('../php/connection.php');

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
        DISTINCT Truck_Number AS value
    FROM
		tbl_truck_master t1
    WHERE
		Truck_Number LIKE '%$val%'
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
	toArrayStringOne($mysqli->query("SELECT DISTINCT Truck_Number FROM tbl_truck_master t1 WHERE Status = 'ACTIVE' AND ($sqlWhere)"), 1);
} else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT
        DISTINCT concat(Truck_Number, ' | ', Truck_Type) AS value
    FROM
		tbl_truck_master t1
    WHERE
		(Truck_Number LIKE '%$val%' OR Truck_Type LIKE '%$val%')
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
	$sql = "SELECT DISTINCT concat(Truck_Number, ' | ', Truck_Type) as truck 
		FROM tbl_truck_master t1
	WHERE 
		Status = 'ACTIVE'
			AND ($sqlWhere);";
	//exit($sql);
	toArrayStringOne($mysqli->query($sql), 1);
}
else if ($_REQUEST['type'] == 6) {
	toArrayStringOne($mysqli->query("SELECT DISTINCT concat(Truck_Number, '|', Truck_Type) as truck FROM tbl_truck_master t1
	WHERE Status = 'ACTIVE'  AND ($sqlWhere);"), 1);
} else if ($_REQUEST['type'] == 7) {
	$route = $_REQUEST['obj'];
	//exit($route);
	$explode = explode(' | ', $route);
	$Route_Code1 = $explode[0];

	$explode1 = explode(" ", $Route_Code1);
	$Route_Code = $explode1[0];

	$sql = "SELECT DISTINCT CONCAT(t2.Truck_Number,' | ',t1.Truck_Type) Truck FROM tbl_route_master t1 
	INNER JOIN tbl_truck_master t2 ON t1.Truck_ID = t2.Truck_ID
	WHERE t1.Route_Code = '$Route_Code'
	AND ($sqlWhere)
    AND t1.Status = 'ACTIVE';";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	closeDBT($mysqli, 1, jsonRow($re1, true, 0));
}

$mysqli->close();
exit();
