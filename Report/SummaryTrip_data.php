<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'SummaryTrip'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'SummaryTrip'}[0] == 0) {
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
		$re1 = getdata_tripDay($mysqli, $data, __LINE__);
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
		$re1 = getdata_delDay($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 3) {
		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:5',
			'obj=>Stop_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$re1 = getdata_tripDate($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 4) {
		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:5',
			'obj=>Stop_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

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
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			tts.truckNo_Date between DATE('$Start_Date') AND DATE('$Stop_Date')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
				),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			b.truckNo_Date AS 'date',
			count(*) AS total,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'total_ontime_date',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'total_early_date',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'total_delay_date',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'total_waiting_date'
		FROM b;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Total = jsonRow($re1, true, 0);


		$sql = "WITH a AS (
		SELECT 
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			tts.truckNo_Date between DATE('$Start_Date') AND DATE('$Stop_Date')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			DATE_FORMAT(b.truckNo_Date, '%d') AS 'date',
			DATE_FORMAT(b.truckNo_Date, '%b') AS 'month',
			count(*) AS total_del_date,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_date',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_date',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_date',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_date'
		FROM b
		GROUP BY b.truckNo_Date;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Sum_Del_Date = jsonRow($re1, true, 0);

		$returnData = ['Total' => $Total, 'Sum_Del_Date' => $Sum_Del_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];

		closeDBT($mysqli, 1, $returnData);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 5) {
		$dataParams = array(
			'obj',
			'obj=>Start_Month:s:5',
			'obj=>Stop_Month:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

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

		$Start_Month = format_date($Start_Month);
		$Stop_Month = format_date($Stop_Month);

		$data = ['Start_Month' => $Start_Month, 'Stop_Month' => $Stop_Month, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$re1 = getdata_tripMonth($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 6) {
		$dataParams = array(
			'obj',
			'obj=>Start_Month:s:5',
			'obj=>Stop_Month:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

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

		$Start_Month = format_date($Start_Month);
		$Stop_Month = format_date($Stop_Month);

		$sql = "WITH a AS (
		SELECT 
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			EXTRACT(YEAR_MONTH FROM tts.truckNo_Date) between EXTRACT(YEAR_MONTH FROM '$Start_Month') AND EXTRACT(YEAR_MONTH FROM '$Stop_Month')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			count(*) AS total,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'total_ontime_month',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'total_early_month',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'total_delay_month',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'total_waiting_month'
		FROM b;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Total = jsonRow($re1, true, 0);


		$sql = "WITH a AS (
		SELECT 
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			EXTRACT(YEAR_MONTH FROM tts.truckNo_Date) between EXTRACT(YEAR_MONTH FROM '$Start_Month') AND EXTRACT(YEAR_MONTH FROM '$Stop_Month')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
				),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			DATE_FORMAT(b.truckNo_Date, '%b') AS 'month',
			DATE_FORMAT(b.truckNo_Date, '%Y') AS 'year',
			count(*) AS total_del_month,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_month',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_month',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_month',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_month'
		FROM b
		GROUP BY EXTRACT(YEAR_MONTH FROM b.truckNo_Date);";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Sum_Del_Date = jsonRow($re1, true, 0);

		$returnData = ['Total' => $Total, 'Sum_Del_Date' => $Sum_Del_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];

		closeDBT($mysqli, 1, $returnData);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 7) {
		$dataParams = array(
			'obj',
			'obj=>Start_Year:s:5',
			'obj=>Stop_Year:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$Start_Year = format_date($Start_Year);
		$Stop_Year = format_date($Stop_Year);

		$data = ['Start_Year' => $Start_Year, 'Stop_Year' => $Stop_Year, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$re1 = getdata_tripYear($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 8) {
		$dataParams = array(
			'obj',
			'obj=>Start_Year:s:5',
			'obj=>Stop_Year:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

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

		$Start_Year = format_date($Start_Year);
		$Stop_Year = format_date($Stop_Year);

		$sql = "WITH a AS (
		SELECT 
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			EXTRACT(YEAR FROM tts.truckNo_Date) between EXTRACT(YEAR FROM '$Start_Year') AND EXTRACT(YEAR FROM '$Stop_Year')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			count(*) AS total,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'total_ontime_year',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'total_early_year',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'total_delay_year',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'total_waiting_year'
		FROM b;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Total = jsonRow($re1, true, 0);


		$sql = "WITH a AS (
		SELECT 
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference,
			tts.tran_status
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			EXTRACT(YEAR FROM tts.truckNo_Date) between EXTRACT(YEAR FROM '$Start_Year') AND EXTRACT(YEAR FROM '$Stop_Year')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
			),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			DATE_FORMAT(b.truckNo_Date, '%Y') AS 'year',
			count(*) AS total_del_year,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_year',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_year',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_year',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_year'
		FROM b
		GROUP BY EXTRACT(YEAR FROM b.truckNo_Date);";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$Sum_Del_Date = jsonRow($re1, true, 0);

		$returnData = ['Total' => $Total, 'Sum_Del_Date' => $Sum_Del_Date];

		closeDBT($mysqli, 1, $returnData);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'SummaryTrip'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'SummaryTrip'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'SummaryTrip'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'SummaryTrip'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 50 && $type <= 60) //export
{
	if ($_SESSION['xxxRole']->{'SummaryTrip'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 51) {
		if (
			!isset($_REQUEST['Operation_Date'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Operation_Date = checkTXT($mysqli, $_REQUEST['Operation_Date']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "SummaryReport_" . $Date . "_" . $randomString . ".xlsx";
		

		$data = ['Operation_Date' => $Operation_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sqlexport_Day = sqlexport_Day($mysqli, $data);

		if ($sqlexport_Day != '') {
			$data_tripDay = [];
			$data_delDay = [];

			if ($re1 = $mysqli->query($sqlexport_Day)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$header_tripDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'Completed' => 'integer',
						'In-Trinsit' => 'integer',
						'Pending' => 'integer',
					);

					$header_delDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'On Time' => 'integer',
						'Early' => 'integer',
						'Delay' => 'integer',
						'Waiting' => 'integer',

					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData_tripDay = array(
							$row['date'], $row['total'],
							$row['completed'], $row['in_transit'], $row['pending']
						);
						array_push($data_tripDay, $lineData_tripDay);

						$lineData_delDay = array(
							$row['date'], $row['total'],
							$row['ontime'], $row['early'], $row['delay'], $row['waiting']
						);
						array_push($data_delDay, $lineData_delDay);
					}

					$writer->writeSheetHeader('Summary Trip', $header_tripDay);
					foreach ($data_tripDay as $row) {
						$writer->writeSheetRow('Summary Trip', $row);
					}

					$writer->writeSheetHeader('Summary Delivery', $header_delDay);
					foreach ($data_delDay as $row) {
						$writer->writeSheetRow('Summary Delivery', $row);
					}

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
	} else if ($type == 52) {
		if (
			!isset($_REQUEST['Start_Date']) || !isset($_REQUEST['Stop_Date'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Start_Date = checkTXT($mysqli, $_REQUEST['Start_Date']);
		$Stop_Date = checkTXT($mysqli, $_REQUEST['Stop_Date']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "SummaryReport_" . $Date . "_" . $randomString . ".xlsx";

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sqlexport_Date = sqlexport_Date($mysqli, $data);

		if ($sqlexport_Date != '') {
			$data_tripDay = [];
			$data_delDay = [];

			if ($re1 = $mysqli->query($sqlexport_Date)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$header_tripDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'Completed' => 'integer',
						'In-Trinsit' => 'integer',
						'Pending' => 'integer',
					);

					$header_delDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'On Time' => 'integer',
						'Early' => 'integer',
						'Delay' => 'integer',
						'Waiting' => 'integer',

					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData_tripDay = array(
							$row['date'], $row['total_trip_date'],
							$row['completed_date'], $row['in_transit_date'], $row['pending_date']
						);
						array_push($data_tripDay, $lineData_tripDay);

						$lineData_delDay = array(
							$row['date'], $row['total_del_date'],
							$row['ontime_date'], $row['early_date'], $row['delay_date'], $row['waiting_date']
						);
						array_push($data_delDay, $lineData_delDay);
					}

					$writer->writeSheetHeader('Summary Trip', $header_tripDay);
					foreach ($data_tripDay as $row) {
						$writer->writeSheetRow('Summary Trip', $row);
					}

					$writer->writeSheetHeader('Summary Delivery', $header_delDay);
					foreach ($data_delDay as $row) {
						$writer->writeSheetRow('Summary Delivery', $row);
					}

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
	} else if ($type == 53) {
		if (
			!isset($_REQUEST['Start_Month']) || !isset($_REQUEST['Stop_Month'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Start_Month = checkTXT($mysqli, $_REQUEST['Start_Month']);
		$Stop_Month = checkTXT($mysqli, $_REQUEST['Stop_Month']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);


		$Start_Month = format_date($Start_Month);
		$Stop_Month = format_date($Stop_Month);

		
		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "SummaryReport_" . $Date . "_" . $randomString . ".xlsx";

		$data = ['Start_Month' => $Start_Month, 'Stop_Month' => $Stop_Month, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sqlexport_Month = sqlexport_Month($mysqli, $data);

		if ($sqlexport_Month != '') {
			$data_tripDay = [];
			$data_delDay = [];

			if ($re1 = $mysqli->query($sqlexport_Month)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$header_tripDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'Completed' => 'integer',
						'In-Trinsit' => 'integer',
						'Pending' => 'integer',
					);

					$header_delDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'On Time' => 'integer',
						'Early' => 'integer',
						'Delay' => 'integer',
						'Waiting' => 'integer',

					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData_tripDay = array(
							$row['date'], $row['total_trip_month'],
							$row['completed_month'], $row['in_transit_month'], $row['pending_month']
						);
						array_push($data_tripDay, $lineData_tripDay);

						$lineData_delDay = array(
							$row['date'], $row['total_del_month'],
							$row['ontime_month'], $row['early_month'], $row['delay_month'], $row['waiting_month']
						);
						array_push($data_delDay, $lineData_delDay);
					}

					$writer->writeSheetHeader('Summary Trip', $header_tripDay);
					foreach ($data_tripDay as $row) {
						$writer->writeSheetRow('Summary Trip', $row);
					}

					$writer->writeSheetHeader('Summary Delivery', $header_delDay);
					foreach ($data_delDay as $row) {
						$writer->writeSheetRow('Summary Delivery', $row);
					}

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
	} else if ($type == 54) {
		if (
			!isset($_REQUEST['Start_Year']) || !isset($_REQUEST['Stop_Year'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Start_Year = checkTXT($mysqli, $_REQUEST['Start_Year']);
		$Stop_Year = checkTXT($mysqli, $_REQUEST['Stop_Year']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);

		$Start_Year = format_date($Start_Year);
		$Stop_Year = format_date($Stop_Year);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "SummaryReport_" . $Date . "_" . $randomString . ".xlsx";

		$data = ['Start_Year' => $Start_Year, 'Stop_Year' => $Stop_Year, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sqlexport_Year = sqlexport_Year($mysqli, $data);

		if ($sqlexport_Year != '') {
			$data_tripDay = [];
			$data_delDay = [];

			if ($re1 = $mysqli->query($sqlexport_Year)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$header_tripDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'Completed' => 'integer',
						'In-Trinsit' => 'integer',
						'Pending' => 'integer',
					);

					$header_delDay = array(
						'Operation Date' => 'string',
						'Total' => 'integer',

						'On Time' => 'integer',
						'Early' => 'integer',
						'Delay' => 'integer',
						'Waiting' => 'integer',

					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData_tripDay = array(
							$row['date'], $row['total_trip_year'],
							$row['completed_year'], $row['in_transit_year'], $row['pending_year']
						);
						array_push($data_tripDay, $lineData_tripDay);

						$lineData_delDay = array(
							$row['date'], $row['total_del_year'],
							$row['ontime_year'], $row['early_year'], $row['delay_year'], $row['waiting_year']
						);
						array_push($data_delDay, $lineData_delDay);
					}

					$writer->writeSheetHeader('Summary Trip', $header_tripDay);
					foreach ($data_tripDay as $row) {
						$writer->writeSheetRow('Summary Trip', $row);
					}

					$writer->writeSheetHeader('Summary Delivery', $header_delDay);
					foreach ($data_delDay as $row) {
						$writer->writeSheetRow('Summary Delivery', $row);
					}

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
} else closeDBT($mysqli, 2, 'TYPE ERROR');


function format_date($date)
{
	$explode = explode(" ", $date);
	$month = $explode[1];
	$date = $explode[2];
	$year = $explode[3];

	$date_new = date_create($date . '-' . $month . '-' . $year);
	$Start = date_format($date_new, "Y-m-d");

	return $Start;
}


function getdata_tripDay($mysqli, $data, $line)
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

	$sql = "SELECT 
		truckNo_Date AS 'date',
		count(*) AS total_trip_day,
		sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_day',
		sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_day',
		sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_day'
	FROM 
		tbl_transaction tts
	WHERE
		tts.truckNo_Date =  DATE('$data[Operation_Date]')
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ($sqlWhere);";
	//exit($sql);
	//return $sql;
	return sqlError($mysqli, $line, $sql, 1);
}

function getdata_delDay($mysqli, $data, $line)
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
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			tts.truckNo_Date =  DATE('$data[Operation_Date]')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
				),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a)
		SELECT 
			b.truckNo_Date AS 'date',
			count(*) AS total_del_day,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_day',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_day',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_day',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_day'
		FROM b;";
	//exit($sql);
	//return $sql;
	return sqlError($mysqli, $line, $sql, 1);
}

function sqlexport_Day($mysqli, $data)
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
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			tts.truckNo_Date =  DATE('$data[Operation_Date]')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
				),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a),
		c AS (
		SELECT 
			b.truckNo_Date,
			count(*) AS total,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting'
		FROM b )
		SELECT 
			DATE_FORMAT(c.truckNo_Date, '%d-%m-%Y') AS 'date',
			c.*,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending'
		FROM 
			tbl_transaction tts
				INNER JOIN
			c ON tts.truckNo_Date = c.truckNo_Date
		WHERE
			tts.truckNo_Date =  DATE('$data[Operation_Date]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere);";
	//exit($sql);
	return $sql;
}


function getdata_tripDate($mysqli, $data, $line)
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
			count(*) AS total,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'total_completed_date',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'total_in_transit_date',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'total_pending_date'
		FROM 
			tbl_transaction tts
		WHERE
			tts.truckNo_Date between DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		)
		SELECT 
			DATE_FORMAT(truckNo_Date, '%d') AS 'date',
			DATE_FORMAT(truckNo_Date, '%b') AS 'month',
			a.*,
			count(*) AS total_trip_date,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_date',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_date',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_date'
		FROM 
			tbl_transaction tts
				CROSS JOIN a
		WHERE
			tts.truckNo_Date between DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		group by tts.truckNo_Date;";
	//exit($sql);
	//return $sql;
	return sqlError($mysqli, $line, $sql, 1);
}


function sqlexport_Date($mysqli, $data)
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
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			tts.truckNo_Date between DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
				),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a),
		c AS (
		SELECT 
			truckNo_Date,
			count(*) AS total_del_date,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_date',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_date',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_date',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_date'
		FROM b
		GROUP BY b.truckNo_Date)
		SELECT 
			DATE_FORMAT(c.truckNo_Date, '%d-%m-%Y') AS 'date',
			c.*,
			count(*) AS total_trip_date,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_date',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_date',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_date'
		FROM 
			tbl_transaction tts
				CROSS JOIN
			c ON tts.truckNo_Date = c.truckNo_Date
		WHERE
			tts.truckNo_Date between DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		group by tts.truckNo_Date;";
	return $sql;
}


function getdata_tripMonth($mysqli, $data, $line)
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
			count(*) AS total,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'total_completed_month',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'total_in_transit_month',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'total_pending_month'
		FROM 
			tbl_transaction tts
		WHERE
			EXTRACT(YEAR_MONTH FROM tts.truckNo_Date) between EXTRACT(YEAR_MONTH FROM '$data[Start_Month]') AND EXTRACT(YEAR_MONTH FROM '$data[Stop_Month]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		)
		SELECT 
			DATE_FORMAT(truckNo_Date, '%b') AS 'month',
			DATE_FORMAT(truckNo_Date, '%Y') AS 'year',
			a.*,
			count(*) AS total_trip_month,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_month',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_month',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_month'
		FROM 
			tbl_transaction tts
				CROSS JOIN a
		WHERE
			EXTRACT(YEAR_MONTH FROM tts.truckNo_Date) between EXTRACT(YEAR_MONTH FROM '$data[Start_Month]') AND EXTRACT(YEAR_MONTH FROM '$data[Stop_Month]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		group by EXTRACT(YEAR_MONTH FROM tts.truckNo_Date);";
	//exit($sql);
	//return $sql;
	return sqlError($mysqli, $line, $sql, 1);
}


function sqlexport_Month($mysqli, $data)
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
			tts.truckNo_Date,
			TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			EXTRACT(YEAR_MONTH FROM tts.truckNo_Date) between EXTRACT(YEAR_MONTH FROM '$data[Start_Month]') AND EXTRACT(YEAR_MONTH FROM '$data[Stop_Month]')
				AND ttl.Status_Pickup = 'DELIVERY'
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ttl.Pick != 'N'
				AND ($sqlWhere)
		),
		b AS (
		SELECT 
			*, 
			CASE
			WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
			WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
			WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
			ELSE 'Waiting'
			END AS Time_Status FROM a),
		c AS (
		SELECT 
			DATE_FORMAT(b.truckNo_Date, '%b-%Y') AS 'month',
			count(*) AS total_del_month,
			sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_month',
			sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_month',
			sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_month',
			sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_month'
		FROM b
		GROUP BY EXTRACT(YEAR_MONTH FROM b.truckNo_Date))
		SELECT 
			DATE_FORMAT(tts.truckNo_Date, '%b-%Y') AS 'date',
			c.*,
			count(*) AS total_trip_month,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_month',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_month',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_month'
		FROM 
			tbl_transaction tts
				CROSS JOIN
			c ON DATE_FORMAT(tts.truckNo_Date, '%b-%Y') = c.month
		WHERE
			EXTRACT(YEAR_MONTH FROM tts.truckNo_Date) between EXTRACT(YEAR_MONTH FROM '$data[Start_Month]') AND EXTRACT(YEAR_MONTH FROM '$data[Stop_Month]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		group by EXTRACT(YEAR_MONTH FROM tts.truckNo_Date);";
	return $sql;
}


function getdata_tripYear($mysqli, $data, $line)
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
			count(*) AS total,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'total_completed_year',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'total_in_transit_year',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'total_pending_year'
		FROM 
			tbl_transaction tts
		WHERE
			EXTRACT(YEAR FROM tts.truckNo_Date) between EXTRACT(YEAR FROM '$data[Start_Year]') AND EXTRACT(YEAR FROM '$data[Stop_Year]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		)
		SELECT 
			DATE_FORMAT(truckNo_Date, '%Y') AS 'year',
			a.*,
			count(*) AS total_trip_year,
			sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_year',
			sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_year',
			sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_year'
		FROM 
			tbl_transaction tts
				CROSS JOIN a
		WHERE
			EXTRACT(YEAR FROM tts.truckNo_Date) between EXTRACT(YEAR FROM '$data[Start_Year]') AND EXTRACT(YEAR FROM '$data[Stop_Year]')
				AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
				AND ($sqlWhere)
		group by EXTRACT(YEAR FROM tts.truckNo_Date);";
	//exit($sql);
	//return $sql;
	return sqlError($mysqli, $line, $sql, 1);
}


function sqlexport_Year($mysqli, $data)
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
		tts.truckNo_Date,
		TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference,
		tts.tran_status
	FROM
		tbl_transaction tts
			INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
	WHERE
		EXTRACT(YEAR FROM tts.truckNo_Date) between EXTRACT(YEAR FROM '$data[Start_Year]') AND EXTRACT(YEAR FROM '$data[Stop_Year]')
			AND ttl.Status_Pickup = 'DELIVERY'
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ttl.Pick != 'N'
			AND ($sqlWhere)
	),
	b AS (
	SELECT 
		*, 
		CASE
		WHEN a.difference IS NOT NULL AND a.difference BETWEEN -1800 AND 1800 THEN 'On Time'
		WHEN a.difference IS NOT NULL AND a.difference > 1800 THEN 'Delay'
		WHEN a.difference IS NOT NULL AND a.difference < -1800 THEN 'Early'
		ELSE 'Waiting'
		END AS Time_Status FROM a),
	c AS (
	SELECT 
		DATE_FORMAT(b.truckNo_Date, '%Y') AS 'year',
		count(*) AS total_del_year,
		sum(case when Time_Status = 'On Time' then 1 else 0 end) AS 'ontime_year',
		sum(case when Time_Status = 'Early' then 1 else 0 end) AS 'early_year',
		sum(case when Time_Status = 'Delay' then 1 else 0 end) AS 'delay_year',
		sum(case when Time_Status = 'Waiting' then 1 else 0 end) AS 'waiting_year'
	FROM b
	GROUP BY EXTRACT(YEAR FROM b.truckNo_Date))
	SELECT 
		DATE_FORMAT(truckNo_Date, '%Y') AS 'date',
		c.*,
		count(*) AS total_trip_year,
		sum(case when tran_status = 'COMPLETE' then 1 else 0 end) AS 'completed_year',
		sum(case when tran_status = 'IN-TRANSIT' then 1 else 0 end) AS 'in_transit_year',
		sum(case when tran_status = 'PLANNING' then 1 else 0 end) AS 'pending_year'
	FROM 
		tbl_transaction tts
			CROSS JOIN 
		c ON c.year = DATE_FORMAT(tts.truckNo_Date, '%Y')
	WHERE
		EXTRACT(YEAR FROM tts.truckNo_Date) between EXTRACT(YEAR FROM '$data[Start_Year]') AND EXTRACT(YEAR FROM '$data[Stop_Year]')
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ($sqlWhere)
	group by EXTRACT(YEAR FROM tts.truckNo_Date);";
	return $sql;
}


$mysqli->close();
exit();
