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
        DISTINCT Supplier_Code AS value
    FROM
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
    WHERE
		Supplier_Code LIKE '%$val%'
			AND t1.Status = 'ACTIVE'
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
	toArrayStringOne($mysqli->query("SELECT DISTINCT Supplier_Code 
	FROM tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
	 WHERE t1.Status = 'ACTIVE'
	 AND ($sqlWhere);"), 1);
} else if ($_REQUEST['type'] == 3) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	$sql = "SELECT 
		DISTINCT concat(Supplier_Code, ' | ', Supplier_Name_Short) AS value
    FROM
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
    WHERE
		(Supplier_Code LIKE '%$val%' OR Supplier_Name_Short LIKE '%$val%')
			AND t1.Status = 'ACTIVE' AND ($sqlWhere)
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
	$sql = "SELECT DISTINCT concat(Supplier_Code, ' | ', Supplier_Name_Short) 
	FROM 
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
	WHERE 
		t1.Status = 'ACTIVE' 
			AND ($sqlWhere);";
	toArrayStringOne($mysqli->query($sql), 1);
} else if ($_REQUEST['type'] == 5) {
	$val = checkTXT($mysqli, $_REQUEST['filter']['value']);
	if (strlen(trim($val)) == 0) {
		echo "[]";
		exit();
	}
	//concat(Supplier_Code, ' | ', Supplier_Name_Short, ' | ', Customer_Code)
	$sql = "SELECT
        DISTINCT Supplier_Name_Short AS value
    FROM
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
    WHERE
		(Supplier_Code LIKE '%$val%' OR Supplier_Name_Short LIKE '%$val%')
			AND t1.Status = 'ACTIVE'
			AND ($sqlWhere)
	ORDER BY t2.Customer_ID
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
} else if ($_REQUEST['type'] == 6) {
	$sql = "SELECT
		DISTINCT Supplier_Name_Short AS value
	FROM
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
	WHERE
		t1.Status = 'ACTIVE'
			AND ($sqlWhere)
	ORDER BY t2.Customer_ID;";
	//exit($sql);
	toArrayStringOne($mysqli->query($sql), 1);
}

$mysqli->close();
exit();
