<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'TruckMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'TruckMaster'}[0] == 0) {
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

	$where[] = "t1.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	$sqlWhere = join(' OR ', $where);
}

if ($type <= 10) //data
{
	if ($type == 1) {
		$sql = "SELECT 
			BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID,
			Truck_Number,
			Truck_Type,
			Weight,
			Width,
			Length,
			Height,
			CONCAT(Width,
						'x',
						Length,
						'x',
						Height) AS Dimansion,
			t1.Status,
			ST_AsText(geo) AS truck_geo,
			gps_updateDatetime,
			DATE_FORMAT(t1.Creation_DateTime, '%Y-%m-%d %H:%i') AS Creation_DateTime,
			DATE_FORMAT(t1.Last_Updated_DateTime, '%Y-%m-%d %H:%i') AS Last_Updated_DateTime,
			t1.Created_By_ID,
			t2.Customer_Code
		FROM 
			tbl_truck_master t1
				INNER JOIN
			tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
		WHERE 
			$sqlWhere
		ORDER BY t1.Status, t2.Customer_Code, Creation_DateTime;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'TruckMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
		$dataParams = array(
			'obj',
			'obj=>Truck_Number:s:0:1',
			'obj=>Truck_Type:s:0:1',
			'obj=>Weight:i:0:0',
			'obj=>Width:i:0:0',
			'obj=>Length:i:0:0',
			'obj=>Height:i:0:0',
			'obj=>Truck_Type:s:0:1',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			
			$sql = "SELECT 
				Truck_Number 
			FROM 
				tbl_truck_master 
			WHERE 
				Truck_Number = '$Truck_Number'
					AND Truck_Type = '$Truck_Type'
					AND Customer_ID = uuid_to_bin('$Customer_ID',TRUE)";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีรถเลขทะเบียนนี้อยู่แล้ว');
			}

			$sql = "INSERT INTO tbl_truck_master (
				Truck_Number,
			Truck_Type,
			Weight,
			Width,
			Length,
			Height,
			geo,
			Customer_ID,
			Creation_Date,
			Creation_DateTime,
			Created_By_ID )
			VALUES (
				'$Truck_Number',
				'$Truck_Type',
				'$Weight',
				$Width,
				$Length,
				$Height,
				ST_GeomFromText('point(13.030953 101.136099)'),
				uuid_to_bin('$Customer_ID',TRUE),
				curdate(),
				now(),
				$cBy )";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}
			$mysqli->commit();


			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'TruckMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>Truck_ID:s:0:1',
			'obj=>Truck_Number:s:0:1',
			'obj=>Truck_Type:s:0:1',
			'obj=>Weight:i:0:0',
			'obj=>Width:i:0:0',
			'obj=>Length:i:0:0',
			'obj=>Height:i:0:0',
			'obj=>Status:s:0:1',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID
			FROM 
				tbl_truck_master 
			WHERE 
				BIN_TO_UUID(Truck_ID,TRUE) = '$Truck_ID'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			
			$Customer_ID = getCustomerID($mysqli, $Customer_Code);

			$sql = "UPDATE tbl_truck_master 
			SET 
				Truck_Number = '$Truck_Number',
				Truck_Type = '$Truck_Type',
				Weight = '$Weight',
				Width = $Width,
				Length = $Length,
				Height = $Height,
				Customer_ID = uuid_to_bin('$Customer_ID',TRUE),
				Status = '$Status',
				Last_Updated_Date = CURDATE(),
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Truck_ID,TRUE) = '$Truck_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
			}

			//exit($Supplier_ID);

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'TruckMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'TruckMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

$mysqli->close();
exit();
