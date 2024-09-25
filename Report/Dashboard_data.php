<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'Dashboard'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'Dashboard'}[0] == 0) {
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


include('../php/xlsxwriter.class.php');
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
			'obj=>Operation_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Operation_Date' => $Operation_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$re1 = getTruckData($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>Operation_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Operation_Date' => $Operation_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$re1 = getRouteDayData($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 3) {
		$dataParams = array(
			'obj',
			'obj=>Operation_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Operation_Date' => $Operation_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$re1 = getRouteNightData($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 5) {
		if (
			!isset($_REQUEST['Operation_Date'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Operation_Date = checkTXT($mysqli, $_REQUEST['Operation_Date']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);

		// $Time = date("his");
		// $Date = date("d.m.Y");
		$date_create = date_create($Operation_Date);
		$date = date_format($date_create, "d.m.Y");

		// $data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'document_no' => $document_no];
		$data = ['Operation_Date' => $Operation_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sql = sqlexport_excel($mysqli, $data);
		//exit($sql);

		if ($sql != '') {
			$data = [];
			if ($re1 = $mysqli->query($sql)) {
				if ($re1->num_rows > 0) {
					$header = array(
						'Truck No.' => 'string',
						'Plan In' => 'string',
						'Supplier Code' => 'string',
						'Truck Control No.' => 'string',
						'Remark' => 'string',
					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData = array(
							$row['Truck_Number'], $row['planin_time'], $row['Supplier_Name_Short'], $row['truck_Control_No'], $row['Remark']
						);
						array_push($data, $lineData);
					}

					$writer = new XLSXWriter();

					$styles1 = array('font' => 'Calibri', 'font-size' => 12, 'halign' => 'center', 'fill' => '#DAEEF3', 'border-style' => 'medium', 'border' => 'left,right,top,bottom', 'widths' => [15, 20, 15, 20, 30]);
					$styles2 = array('font' => 'Calibri', 'font-size' => 12, 'halign' => 'left',);


					$writer->writeSheetHeader('Route Truck', $header, $col_options = $styles1);

					foreach ($data as $row) {
						$writer->writeSheetRow('Route Truck', $row, $col_options = $styles2);
					}

					if ($Customer_Code == '') {
						$Customer_Code = 'TSPK';
					}

					$filename = "Route Truck " . $Customer_Code . " " . $date . ".xlsx";
					//$writer->writeToFile($filename);

					header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
					header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
					header('Content-Transfer-Encoding: binary');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');

					$writer->writeToStdOut();
				} else {
					echo json_encode(array('ch' => 2, 'data' => "ไม่พบข้อมูลในระบบ"));
				}
			} else {
				echo json_encode(array('ch' => 2, 'data' => "Error SP"));
			}
		} else {
			echo json_encode(array('ch' => 2, 'data' => "Error SP"));
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'Dashboard'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'Dashboard'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'Dashboard'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'Dashboard'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

function getTruckData($mysqli, $data, $line)
{

	$sqlWhere = $data['sqlWhere'];
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

		$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	}

	$sql = "WITH a AS (
	SELECT
			ROW_NUMBER() OVER (partition by tts.Truck_Number ORDER BY tts.Truck_Number, ttl.planin_time ASC) as rank_num,
			tts.truck_Control_No,
			ttl.pus_No,
			tts.Truck_Number,
			tts.truckNo_Date,
			ttl.planin_time,
			ttl.actual_in_time
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
		truckNo_Date = DATE('$data[Operation_Date]')
				AND tts.Truck_Number != 'N/A'
				AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT' OR tts.tran_status = 'COMPLETE')
				AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT' OR ttl.status = 'COMPLETE')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
				),
	b AS (
			SELECT a.*,
			SUM(if((a.actual_in_time IS NULL AND TIMESTAMPDIFF(SECOND, a.planin_time,NOW()) < 1800),1,0)) AS count_actual
			FROM a 
			GROUP BY a.Truck_Number
			ORDER BY a.planin_time ASC),
	c AS (
			SELECT b.*, if(b.count_actual = 0,'COMPLETE','WAIT') AS status_actual FROM b )
			SELECT 
			ROW_NUMBER() OVER (ORDER BY c.status_actual DESC, c.planin_time ASC) as row_num, 
			c.* FROM c
			ORDER BY c.status_actual DESC, c.planin_time ASC;";
	//exit($sql);
	return sqlError($mysqli, $line, $sql, 1);
}

function getRouteDayData($mysqli, $data, $line)
{

	$sqlWhere = $data['sqlWhere'];
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

		$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	}

	$sql = "WITH a AS (
	SELECT
		tts.Truck_Number,
		tts.Route_Code,
		-- ttl.Status_Pickup,
		IF(ttl.Status_Pickup = 'DELIVERY', ttl.Status_Pickup,CONCAT(ttl.Status_Pickup,' ',ROW_NUMBER() OVER ( partition by tts.Truck_Number, tts.Route_Code ORDER BY tts.Truck_Number ASC, ttl.planin_time, ttl.planout_time ASC))) AS Status_Pickup,
		tsm.Supplier_Name_Short,
		TIME(ttl.planin_time) AS planin_time,
		IF(ttl.actual_in_time IS NULL,'',TIME(ttl.actual_in_time)) AS actual_in_time,
		ttl.pus_No,
		ttl.Status,
		TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference,
		if(ttl.actual_in_time IS NULL,TIMESTAMPDIFF(SECOND, ttl.planin_time,now()),null) AS difference2,
		if(TIME(ttl.planin_time) BETWEEN '08:00:00' AND '17:00:00','Day','Night') AS time_in,
		ttl.Pick
	FROM
		tbl_transaction tts
			INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			INNER JOIN
		tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
	WHERE
		truckNo_Date = DATE('$data[Operation_Date]')
			AND tts.Truck_Number != 'N/A'
			AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT' OR tts.tran_status = 'COMPLETE')
			AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT' OR ttl.status = 'COMPLETE')
			AND ttl.Pick != 'N'
			AND ($sqlWhere)
	ORDER BY tts.Truck_Number ASC, ttl.planin_time, ttl.planout_time ASC )
	SELECT a.*,
		CASE
		WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
		WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
		WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
		WHEN a.difference IS NULL AND difference2 > 1800 THEN 'Overdue'
		ELSE 'Waiting'
		END AS Time_Status
	FROM a WHERE a.time_in = 'Day';";
	return sqlError($mysqli, $line, $sql, 1);
}

function getRouteNightData($mysqli, $data, $line)
{

	$sqlWhere = $data['sqlWhere'];
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

		$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	}

	$sql = "WITH a AS (
		SELECT
			tts.Truck_Number,
			tts.Route_Code,
			-- ttl.Status_Pickup,
			IF(ttl.Status_Pickup = 'DELIVERY', ttl.Status_Pickup,CONCAT(ttl.Status_Pickup,' ',ROW_NUMBER() OVER ( partition by tts.Truck_Number, tts.Route_Code ORDER BY tts.Truck_Number ASC, ttl.planin_time, ttl.planout_time ASC))) AS Status_Pickup,
			tsm.Supplier_Name_Short,
			TIME(ttl.planin_time) AS planin_time,
			IF(ttl.actual_in_time IS NULL,'',TIME(ttl.actual_in_time)) AS actual_in_time,
			ttl.pus_No,
			ttl.Status,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference,
			if(ttl.actual_in_time IS NULL,TIMESTAMPDIFF(SECOND, ttl.planin_time,now()),null) AS difference2,
			if(TIME(ttl.planin_time) BETWEEN '08:00:00' AND '17:00:00','Day','Night') AS time_in,
			ttl.Pick
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				INNER JOIN
			tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
		WHERE
			truckNo_Date = DATE('$data[Operation_Date]')
				AND tts.Truck_Number != 'N/A'
				AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT' OR tts.tran_status = 'COMPLETE')
				AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT' OR ttl.status = 'COMPLETE')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		ORDER BY tts.Truck_Number ASC, ttl.planin_time, ttl.planout_time ASC )
		SELECT a.*,
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			WHEN a.difference IS NULL AND difference2 > 1800 THEN 'Overdue'
			ELSE 'Waiting'
			END AS Time_Status
		FROM a WHERE a.time_in = 'Night';";
	return sqlError($mysqli, $line, $sql, 1);
}

function sqlexport_excel($mysqli, $data)
{
	$sqlWhere = $data['sqlWhere'];
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

		$sqlWhere = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
	}

	$sql = "WITH a AS (
		SELECT
			ROW_NUMBER() OVER (partition by tts.Truck_Number ORDER BY tts.Truck_Number ASC, ttl.planin_time, ttl.planout_time ASC) AS row_num,
			tts.Truck_Number,
			date_format(ttl.planin_time, '%Y-%m-%d %H:%i') AS planin_time,
			tsm.Supplier_Name_Short,
			-- tts.truck_Control_No,
			SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				INNER JOIN
			tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
		WHERE
			truckNo_Date = DATE('$data[Operation_Date]')
				AND tts.Truck_Number != 'N/A'
				AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT' OR tts.tran_status = 'COMPLETE')
				AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT' OR ttl.status = 'COMPLETE')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		ORDER BY tts.Truck_Number ASC, ttl.planin_time, ttl.planout_time ASC)
		SELECT 
		if(a.row_num=1,a.Truck_Number,'') AS Truck_Number, 
		a.planin_time, a.Supplier_Name_Short, a.truck_Control_No, '' AS Remark
		FROM a;";
	//exit($sql);
	return $sql;
}

$mysqli->close();
exit();
