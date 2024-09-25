<?php
ini_set('post_max_size', '2024M');
ini_set('upload_max_filesize', '2024M');
ini_set('memory_limit', '2024M');
ini_set('max_execution_time', 300);

if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Transaction'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Transaction'}[0] == 0) {
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
		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:5',
			'obj=>Stop_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sql = getData($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:5',
			'obj=>Stop_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];

		$mysqli->autocommit(FALSE);
		try {

			$sql = getData($mysqli, $data);
			$re = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$dataArray = array();
			while ($row = $re->fetch_assoc()) {
				$dataArray[] = $row;
			}
			include('excel/excel_transaction_route.php');

			$mysqli->commit();

			closeDBT($mysqli, 1, $filename);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Transaction'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Transaction'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Transaction'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Transaction'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

function getData($mysqli, $data)
{
	$where = [];
	$where[] = "DATE_FORMAT(t1.truckNo_Date, '%Y-%m-%d') between DATE_FORMAT('$data[Start_Date]', '%Y-%m-%d') and DATE_FORMAT('$data[Stop_Date]', '%Y-%m-%d')";
	$sqlWhere = join(' and ', $where);

	$sqlWhere1 = $data['sqlWhere'];
	$Customer_Code = $data['Customer_Code'];

	if ($Customer_Code != '') {
		$sql = "SELECT 
			BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
		FROM 
			tbl_customer_master 
		WHERE 
			Customer_Code = '$Customer_Code';";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];

		$sqlWhere1 = "t1.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	}

	$sql = "SELECT 
		t1.tran_status,
		t1.Truck_Number,
		t1.Truck_Type,
		t2.status,
		t1.truck_Control_No,
		SUBSTRING(t1.truck_Control_No, 1, 13)as truck_Control_No_show,
		t1.truckNo_Date,
		-- t1.Route_Code,
		if(trm.route_special = 'Y' OR trm.route_special = 'N', t1.Route_Code, CONCAT(t1.Route_Code,' ', trm.route_special)) as Route_Code,
		t2.pus_No,
		if(t2.Status_Pickup = 'DELIVERY','',SUBSTRING(t2.pus_No, 1, 13)) as pus_No_show,
		-- SUBSTRING(t2.pus_No, 1, 13)as pus_No_show,
		DATE_FORMAT(t2.pus_Date, '%Y-%m-%d') AS pus_Date,
		t5.Supplier_Name_Short,
		t5.Supplier_Name,
		t2.sequence_Stop,
		t2.Status_Pickup,
		DATE_FORMAT(t2.planin_time, '%Y-%m-%d %H:%i') AS planin_time,
		DATE_FORMAT(t2.planout_time, '%Y-%m-%d %H:%i') AS planout_time,
		DATE_FORMAT(t2.actual_in_time, '%Y-%m-%d %H:%i') AS actual_in_time,
		DATE_FORMAT(t2.actual_out_time, '%Y-%m-%d %H:%i') AS actual_out_time,
		-- t2.line_CBM,
		CONVERT(t2.line_CBM, CHAR) AS line_CBM,
		t2.Remark,
		(SELECT user_fName FROM tbl_user WHERE user_id = t2.Created_By_ID) AS Created_By_ID,
		t2.Creation_DateTime,
		(SELECT user_fName FROM tbl_user WHERE user_id = t2.Updated_By_ID) AS Updated_By_ID,
		t2.Last_Updated_DateTime,
		(SELECT user_fName FROM tbl_user WHERE user_id = t2.gps_Updated_By_ID) AS gps_Updated_By_ID,
		t2.gps_updateDatetime,
		t2.gps_connection,
		t2.gps_datetime_connect,
		t3.Customer_Code,
    	trm.route_special
	FROM
		tbl_transaction t1
			LEFT JOIN
		tbl_transaction_line t2 ON t1.transaction_ID = t2.transaction_ID
			INNER JOIN
		tbl_customer_master t3 ON t1.Customer_ID = t3.Customer_ID
			LEFT JOIN
		tbl_supplier_master t5 ON t2.Supplier_ID = t5.Supplier_ID
			LEFT JOIN
		tbl_route_master trm ON t2.Route_ID = trm.Route_ID
	WHERE
		$sqlWhere
		AND t2.status != 'PENDING'
		AND t2.status != 'CANCEL'
		AND t2.Pick != 'N'
		AND ($sqlWhere1)
	GROUP BY t2.pus_No
	ORDER BY t1.truckNo_Date ASC, t3.Customer_Code, t1.truck_Control_No ASC, t2.sequence_Stop ASC;";
	//exit($sql);
	return $sql;
}

$mysqli->close();
exit();
