<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'SummaryTransportation'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'SummaryTransportation'}[0] == 0) {
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
			'obj=>Start_Date:s:5',
			'obj=>Stop_Date:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sql = getdata_cbmDaily_Show($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		//$re1 = getdata_cbmDaily($mysqli, $data, __LINE__);
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>Period:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$explode = explode(" - ", $Period);

		//var_dump($explode);
		$start_date = gen_format_date2($explode[0]);
		$stop_date = gen_format_date2($explode[1]);

		$data = ['start_date' => $start_date, 'stop_date' => $stop_date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sql = getdata_cbmMonthly($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 3) {
		$dataParams = array(
			'obj',
			'obj=>Period:s:5',
			'obj=>Customer_Code:s:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$explode = explode(" - ", $Period);

		//var_dump($explode);
		$Start = gen_format_date2($explode[0]);
		$Stop = gen_format_date2($explode[1]);

		$data = ['Start' => $Start, 'Stop' => $Stop, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$sql = getdata_Trip($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 9) {
		$sql = "WITH a AS (
			SELECT 
				`Date` AS Start_Date,
				EXTRACT(YEAR_MONTH FROM `Date`) AS Start_Month
			FROM
				tbl_date
			WHERE
				DAY(Date) = '21'),
			b AS (
			SELECT 
				CONCAT((DATE_SUB(a.Start_Date, INTERVAL 1 MONTH)),' | ',tdate.`Date`) AS Period,
    			CONCAT(DATE_FORMAT((DATE_SUB(a.Start_Date, INTERVAL 1 MONTH)),'%d %b %Y'),' - ',DATE_FORMAT(tdate.`Date`,'%d %b %Y')) AS Period_Show,
				PERIOD_DIFF
				(
				EXTRACT(YEAR_MONTH FROM CURRENT_DATE), 
				EXTRACT(YEAR_MONTH FROM a.Start_Date)
				) 
				  AS months_diff
			FROM
				tbl_date tdate
					INNER JOIN 
				a ON EXTRACT(YEAR_MONTH FROM tdate.`Date`) = a.Start_Month
			WHERE
				DAY(Date) = '20')
			SELECT * FROM b WHERE months_diff between -3 AND 3;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		if ($re1 = $mysqli->query($sql)) {
			$row = array();
			while ($result = $re1->fetch_array(MYSQLI_ASSOC)) {
				$row[] = $result['Period_Show'];
			}
			echo json_encode($row);
		} else {
			echo "[]";
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'SummaryTransportation'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'SummaryTransportation'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'SummaryTransportation'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'SummaryTransportation'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 50 && $type <= 60) //export
{
	if ($_SESSION['xxxRole']->{'SummaryTrip'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 51) {

		if (
			!isset($_REQUEST['Start_Date'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Start_Date = checkTXT($mysqli, $_REQUEST['Start_Date']);
		$Stop_Date = checkTXT($mysqli, $_REQUEST['Stop_Date']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "SummaryReport_" . $Date . "_" . $randomString . ".xlsx";

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$getdata_cbmDaily = getdata_cbmDaily($mysqli, $data);

		if ($getdata_cbmDaily != '') {
			$data_cbmDaily = [];

			if ($re1 = $mysqli->query($getdata_cbmDaily)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$headerData = array(
						'Operation Date' => 'string',
						'Suppiler' => 'string',
						'CBM' => 'string',
					);

					// var_dump($headerData);
					// exit();

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData = array(
							$row['truckNo_Date'], $row['Supplier'], $row['CBM']
						);
						array_push($data_cbmDaily, $lineData);
					}

					$writer->writeSheetHeader('Summary CBM', $headerData);
					foreach ($data_cbmDaily as $row) {
						$writer->writeSheetRow('Summary CBM', $row);
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

		$dataParams = array(
			'obj',
			'obj=>Period:s:0:1',
			'obj=>Customer_Code:s:0',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT upper(Customer_Name) as Customer_Name FROM tbl_customer_master WHERE Customer_Code = '$Customer_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$Customer_Name = $re1->fetch_array(MYSQLI_ASSOC)['Customer_Name'];

			$explode = explode(" - ", $Period);

			//var_dump($explode);
			$start_date = gen_format_date2($explode[0]);
			$stop_date = gen_format_date2($explode[1]);

			$array = array(
				'start_date' => $start_date,
				'stop_date' => $stop_date,
				'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere
			);

			$sql = getdata_cbmMonthly($mysqli, $array);
			$re = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$dataArray = array();
			while ($row = $re->fetch_assoc()) {
				$dataArray[] = $row;
			}
			include('excel/excel_billing_pk.php');

			$mysqli->commit();
			closeDBT($mysqli, 1, $filename);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 53) {
		if (
			!isset($_REQUEST['Period'])
		)
			closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');

		$Period = checkTXT($mysqli, $_REQUEST['Period']);
		$Customer_Code = checkTXT($mysqli, $_REQUEST['Customer_Code']);

		$explode = explode(" - ", $Period);

		//var_dump($explode);
		$Start = gen_format_date2($explode[0]);
		$Stop = gen_format_date2($explode[1]);

		$Date = date("Ymd");
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$filename = "SummaryReport_" . $Date . "_" . $randomString . ".xlsx";

		$data = ['Start' => $Start, 'Stop' => $Stop, 'Customer_Code' => $Customer_Code, 'sqlWhere' => $sqlWhere];
		$getdata_Trip = getdata_Trip($mysqli, $data);

		if ($getdata_Trip != '') {
			$data_Trip = [];

			if ($re1 = $mysqli->query($getdata_Trip)) {
				if ($re1->num_rows > 0) {

					$writer = new XLSXWriter();

					$headerData = array(
						'Period' => 'string',
						'Suppiler' => 'string',
						'Trip' => 'integer',
					);

					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$lineData = array(
							$row['truckNo_Date'], $row['Supplier'], $row['trip']
						);
						array_push($data_Trip, $lineData);
					}

					$writer->writeSheetHeader('Summary Trip', $headerData);
					foreach ($data_Trip as $row) {
						$writer->writeSheetRow('Summary Trip', $row);
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

function gen_format_date($date)
{
	$explode = explode(" ", $date);
	$month = $explode[1];
	$date = $explode[2];
	$year = $explode[3];

	$date_new = date_create($date . '-' . $month . '-' . $year);
	$Start = date_format($date_new, "Y-m-d");

	return $Start;
}

function gen_format_date2($date)
{
	$explode = explode(" ", $date);
	$date = $explode[0];
	$month = $explode[1];
	$year = $explode[2];

	$date_new = date_create($date . '-' . $month . '-' . $year);

	$Start = date_format($date_new, "Y-m-d");

	return $Start;
}

function getdata_cbmDaily_Show($mysqli, $data)
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
		date_format(tts.truckNo_Date, '%d-%m-%Y') AS truckNo_Date,
		CAST(Total.CBM AS CHAR) AS Total_CBM,
		tsp.Supplier_Name_Short AS Supplier,
		CAST(sum(ttl.line_CBM) AS CHAR) AS CBM
	FROM
		tbl_transaction tts
			INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			INNER JOIN
		tbl_supplier_master tsp ON ttl.Supplier_ID = tsp.Supplier_ID,
			lateral (
				SELECT 
					sum(ttl.line_CBM) AS CBM
				FROM
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				WHERE
					DATE(tts.truckNo_Date) BETWEEN DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
						AND ttl.Status_Pickup = 'PICKUP'
						AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
						AND ttl.Pick != 'N'
						AND ($sqlWhere)
			) AS Total
	WHERE
		DATE(tts.truckNo_Date) BETWEEN DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
			AND ttl.Status_Pickup = 'PICKUP'
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ttl.Pick != 'N'
			AND ($sqlWhere)
	GROUP BY tsp.Supplier_ID
	ORDER BY tts.truckNo_Date, tsp.Supplier_Name_Short ASC;";
	//exit($sql);
	return $sql;
	//return sqlError($mysqli, $line, $sql, 1);
}


function getdata_cbmDaily($mysqli, $data)
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
		date_format(tts.truckNo_Date, '%d-%m-%Y') AS truckNo_Date,
		CAST(Total.CBM AS CHAR) AS Total_CBM,
		tsp.Supplier_Name_Short AS Supplier,
		CAST(sum(ttl.line_CBM) AS CHAR) AS CBM
	FROM
		tbl_transaction tts
			INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			INNER JOIN
		tbl_supplier_master tsp ON ttl.Supplier_ID = tsp.Supplier_ID,
			lateral (
				SELECT 
					sum(ttl.line_CBM) AS CBM
				FROM
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				WHERE
					DATE(tts.truckNo_Date) BETWEEN DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
						AND ttl.Status_Pickup = 'PICKUP'
						AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
						AND ttl.Pick != 'N'
						AND ($sqlWhere)
			) AS Total
	WHERE
		DATE(tts.truckNo_Date) BETWEEN DATE('$data[Start_Date]') AND DATE('$data[Stop_Date]')
			AND ttl.Status_Pickup = 'PICKUP'
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ttl.Pick != 'N'
			AND ($sqlWhere)
	GROUP BY tts.truckNo_Date, tsp.Supplier_ID
	ORDER BY tts.truckNo_Date, tsp.Supplier_Name_Short ASC;";
	//exit($sql);
	return $sql;
	//return sqlError($mysqli, $line, $sql, 1);
}

function getdata_cbmMonthly($mysqli, $data)
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
		CONCAT(DATE_FORMAT('$data[start_date]','%d %b %Y'),' - ',DATE_FORMAT('$data[stop_date]','%d %b %Y')) AS truckNo_Date,
		CAST(Total_CBM.CBM  AS decimal (20,3)) AS Total_CBM,
		CAST(Total_Amount.Total_Amount  AS decimal (20,2)) AS Total_Amount,
		ROW_NUMBER() OVER (ORDER BY tsp.Supplier_Name_Short) AS num_row,
		tsp.Supplier_Name_Short AS Supplier,
		'-' AS dash,
		CASE
			WHEN Customer_Code = 'TSPK-L' THEN 'TSPKK'
			WHEN Customer_Code = 'TSPK-BP' THEN 'TSPKBP'
			WHEN Customer_Code = 'TSPK-C' THEN 'TSPK'
			ELSE Customer_Code
		END as Customer,
		tsp.Supplier_Name,
		CAST(sum(ttl.line_CBM) AS decimal (20,3)) AS CBM,
		CAST(tpm.Transport_Price AS decimal (20,2)) AS Transport_Price,
		CAST(tpm.Planing_Price AS decimal (20,2)) AS Planing_Price,
		(tpm.Transport_Price+tpm.Planing_Price) AS Total_Price,
		CAST(ROUND(sum(ttl.line_CBM)*(tpm.Transport_Price+tpm.Planing_Price),2) AS decimal (20,2)) AS Amount,
		'' AS Remark
	FROM
		tbl_transaction tts
			INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			INNER JOIN
		tbl_supplier_master tsp ON ttl.Supplier_ID = tsp.Supplier_ID
			INNER JOIN
		tbl_customer_master t1 ON tts.Customer_ID = t1.Customer_ID
			INNER JOIN
		tbl_price_master tpm ON tsp.Supplier_ID = tpm.Supplier_ID AND tts.Customer_ID = tpm.Customer_ID,
			LATERAL (
			SELECT 
					sum(ttl.line_CBM) AS CBM
				FROM
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				WHERE
					Date(tts.truckNo_Date) between Date('$data[start_date]') AND Date('$data[stop_date]')
						AND ttl.Status_Pickup = 'PICKUP'
						AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
						AND ttl.Pick != 'N'
						AND ($sqlWhere)
			) AS Total_CBM,
			LATERAL (
			WITH a AS (
				SELECT 
					CAST(ROUND(sum(ttl.line_CBM)*(tpm.Transport_Price+tpm.Planing_Price),2) AS decimal (20,2)) AS Amount
				FROM
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
						INNER JOIN
					tbl_supplier_master tsp ON ttl.Supplier_ID = tsp.Supplier_ID
						INNER JOIN
					tbl_price_master tpm ON tsp.Supplier_ID = tpm.Supplier_ID AND tts.Customer_ID = tpm.Customer_ID
				WHERE
					Date(tts.truckNo_Date) between Date('$data[start_date]') AND Date('$data[stop_date]')
						AND ttl.Status_Pickup = 'PICKUP'
						AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
						AND ttl.Pick != 'N'
						AND ($sqlWhere)
				GROUP BY tsp.Supplier_ID
				ORDER BY tsp.Supplier_Name_Short ASC)
				SELECT CAST(SUM(a.Amount) AS decimal (20,2)) AS Total_Amount FROM a
			) AS Total_Amount
	WHERE
		Date(tts.truckNo_Date) between Date('$data[start_date]') AND Date('$data[stop_date]')
			AND ttl.Status_Pickup = 'PICKUP'
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ttl.Pick != 'N'
			AND ($sqlWhere)
	GROUP BY tsp.Supplier_ID
	ORDER BY tsp.Supplier_Name_Short ASC;";
	// exit($sql);
	return $sql;
	//return sqlError($mysqli, $line, $sql, 1);
}


function getdata_Trip($mysqli, $data)
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
		CONCAT(DATE_FORMAT('$data[Start]','%d %b %Y'),' - ',DATE_FORMAT('$data[Stop]','%d %b %Y')) AS truckNo_Date,
		Total.trip AS Total_Trip,
		tsp.Supplier_Name_Short AS Supplier,
		count(*) AS trip
	FROM
		tbl_transaction tts
			INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			INNER JOIN
		tbl_supplier_master tsp ON ttl.Supplier_ID = tsp.Supplier_ID,
		lateral (
			SELECT 
					count(*) AS trip
				FROM
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				WHERE
					Date(tts.truckNo_Date) between Date('$data[Start]') AND Date('$data[Stop]')
						AND ttl.Status_Pickup = 'PICKUP'
						AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
						AND ttl.Pick != 'N'
						AND ($sqlWhere)
				) AS Total
	WHERE
		Date(tts.truckNo_Date) between Date('$data[Start]') AND Date('$data[Stop]')
			AND ttl.Status_Pickup = 'PICKUP'
			AND (tts.tran_status != 'CANCEL' AND tts.tran_status != 'PENDING')
			AND ttl.Pick != 'N'
			AND ($sqlWhere)
	GROUP BY tsp.Supplier_ID
	ORDER BY tsp.Supplier_Name_Short ASC;";
	//exit($sql);
	return $sql;
	//return sqlError($mysqli, $line, $sql, 1);
}


$mysqli->close();
exit();
