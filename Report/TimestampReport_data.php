<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'TimestampReport'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'TimestampReport'}[0] == 0) {
	echo "{ch:9,data:'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้'}";
	exit();
}

if (!isset($_REQUEST['type'])) {
	echo json_encode(array('ch' => 2, 'data' => 'ข้อมูลไม่ถูกต้อง'));
	exit();
}
$cBy = $_SESSION['xxxID'];
$fName = $_SESSION['xxxFName'];
$type  = intval($_REQUEST['type']);

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

	$where[] = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	$sqlWhere = join(' OR ', $where);
}

if ($type <= 10) //data
{
	if ($type == 1) {
		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:0:1',
			'obj=>Stop_Date:s:0:1',
			'obj=>Customer_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) {
			closeDBT($mysqli, 2, join('<br>', $chkPOST));
		}

		if ($Customer_Code != '') {
			$sql = "SELECT 
				BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
			FROM 
				tbl_customer_master 
			WHERE 
				Customer_Code = '$Customer_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];
			$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
		}

		$sql = "SELECT 
			CONCAT(FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 0 END) 
			/ SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 1 END)) * 100,0),'%') AS Percent_All,
			CONCAT(FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL AND ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END) 
			/ SUM(CASE WHEN ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END)) * 100,0),'%') AS Percent_Pickup,
			CONCAT(FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL AND ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END) 
			/ SUM(CASE WHEN ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END)) * 100,0),'%') AS Percent_Del,
			'Manual' AS Goal,
			'#e8591c' AS color
		FROM
			tbl_truck_master ttm
				INNER JOIN
			tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			ttm.Status = 'Active'
				AND ttm.Truck_Number != 'N/A'
				AND (tts.truckNo_Date BETWEEN '$Start_Date' AND '$Stop_Date')
				AND tts.tran_status != 'CANCEL'
				AND ttl.status != 'CANCEL'
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		ORDER BY ttm.Truck_ID , ttl.planin_time ASC;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Manual = jsonRow($re1, true, 0);


		$sql = "SELECT 
			CONCAT(FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL THEN 1 ELSE 0 END) 
			/ SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 1 END)) * 100,0),'%') AS Percent_All,
			CONCAT(FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL AND ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END) 
			/ SUM(CASE WHEN ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END)) * 100,0),'%') AS Percent_Pickup,
			CONCAT(FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL AND ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END) 
			/ SUM(CASE WHEN ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END)) * 100,0),'%') AS Percent_Del,
			'Auto' AS Goal,
			'#30b358' AS color
		FROM
			tbl_truck_master ttm
				INNER JOIN
			tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			ttm.Status = 'Active'
				-- AND ttm.Truck_Number != 'N/A'
				AND (tts.truckNo_Date BETWEEN '$Start_Date' AND '$Stop_Date')
				AND tts.tran_status != 'CANCEL'
				AND ttl.status != 'CANCEL'
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		ORDER BY ttm.Truck_ID , ttl.planin_time ASC;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Auto = jsonRow($re1, true, 0);

		$returnData = ['Auto' => $Auto, 'Manual' => $Manual];

		closeDBT($mysqli, 1, $returnData);


		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:0:1',
			'obj=>Stop_Date:s:0:1',
			'obj=>Customer_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) {
			closeDBT($mysqli, 2, join('<br>', $chkPOST));
		}

		if ($Customer_Code != '') {
			$sql = "SELECT 
				BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
			FROM 
				tbl_customer_master 
			WHERE 
				Customer_Code = '$Customer_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];
			$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
		}

		$sql = "WITH a AS (
			SELECT 
				tts.truckNo_Date AS truckNo_Date1,
				DAY(tts.truckNo_Date) AS truckNo_Date,
				FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL THEN 1 ELSE 0 END) 
				/ SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 1 END)) * 100,0) AS Percent_not_null,
				FORMAT((SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 0 END) 
				/ SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 1 END)) * 100,0) AS Percent_null
			FROM
				tbl_truck_master ttm
					INNER JOIN
				tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			WHERE
				ttm.Status = 'Active'
					-- AND ttm.Truck_Number != 'N/A'
					AND tts.truckNo_Date BETWEEN '$Start_Date' AND '$Stop_Date'
					AND tts.tran_status != 'CANCEL'
					AND ttl.status != 'CANCEL'
					AND ttl.Pick != 'N'
					AND ($sqlWhere)
			GROUP BY tts.truckNo_Date
			ORDER BY tts.truckNo_Date ASC, ttm.Truck_ID , ttl.planin_time ASC)
			SELECT 
			a.*,
            DAY(tdate.Day_Date) AS Day_Date
			FROM
				a 
				RIGHT JOIN 
			tbl_date_gen tdate ON tdate.Day_Date = a.truckNo_Date1
			WHERE
				tdate.Day_Date BETWEEN '$Start_Date' AND '$Stop_Date'
			ORDER BY tdate.Day_Date;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 3) {
		$dataParams = array(
			'obj',
			'obj=>truckNo_Date1:s:0:1',
			'obj=>Customer_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) {
			closeDBT($mysqli, 2, join('<br>', $chkPOST));
		}

		if ($Customer_Code != '') {
			$sql = "SELECT 
				BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
			FROM 
				tbl_customer_master 
			WHERE 
				Customer_Code = '$Customer_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];
			$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
		}
		$sql = "SELECT 
			tts.truckNo_Date AS truckNo_Date,
			SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 1 END) AS label_Total,
			(SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL THEN 1 ELSE 0 END)) AS label_Total_Auto, 
			(SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL THEN 1 ELSE 0 END)) AS label_Total_Manual,
		
			SUM(CASE WHEN ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END) AS label_Pick_Total,
			SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL AND ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END) AS label_Pick_Auto,
			SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL AND ttl.Status_Pickup = 'PICKUP' THEN 1 ELSE 0 END) AS label_Pick_Manual,
		
			SUM(CASE WHEN ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END) AS label_Delivery_Total,
			SUM(CASE WHEN ttl.gps_Updated_By_ID IS NOT NULL AND ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END) AS label_Delivery_Auto,
			SUM(CASE WHEN ttl.gps_Updated_By_ID IS NULL AND ttl.Status_Pickup = 'DELIVERY' THEN 1 ELSE 0 END) AS label_Delivery_Manual
		FROM
			tbl_truck_master ttm
				INNER JOIN
			tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			ttm.Status = 'Active'
				-- AND ttm.Truck_Number != 'N/A'
				AND tts.truckNo_Date = '$truckNo_Date1'
				AND tts.tran_status != 'CANCEL'
				AND ttl.status != 'CANCEL'
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		GROUP BY tts.truckNo_Date
		ORDER BY tts.truckNo_Date ASC, ttm.Truck_ID , ttl.planin_time ASC;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'TimestampReport'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'TimestampReport'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'TimestampReport'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'TimestampReport'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
