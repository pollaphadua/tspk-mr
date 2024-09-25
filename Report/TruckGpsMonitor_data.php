<?php
if (!ob_start("ob_gzhandler"))
	ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName']) || !isset($_SESSION['xxxRole']->{'TruckGpsMonitor'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'TruckGpsMonitor'}[0] == 0) {
	echo "{ch:9,data:'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้'}";
	exit();
}

if (!isset($_REQUEST['type'])) {
	echo json_encode(array('ch' => 2, 'data' => 'ข้อมูลไม่ถูกต้อง'));
	exit();
}
$cBy = $_SESSION['xxxID'];
$fName = $_SESSION['xxxFName'];
$type = intval($_REQUEST['type']);


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
		$re = getDataGeoTruckMaster($mysqli, $sqlWhere);
		closeDBT($mysqli, 1, jsonRow($re, true, 0));
	} else if ($type == 2) {
		$re = getDataGeoSupplier($mysqli, $entry_project);
		closeDBT($mysqli, 1, jsonRow($re, true, 0));
	} else if ($type == 3) {
		$re = getDataGeoTruck($mysqli, $sqlWhere);
		closeDBT($mysqli, 1, jsonRow($re, true, 0));
	} else if ($type == 4) {

		if (!isset($_POST['obj'])) {
			echo json_encode(array('ch' => 2, 'data' => 'ข้อมูลไม่ถูกต้อง 1'));
			closeDB($mysqli);
		}


		// print_r($_POST['obj']);
		// exit();

		$dateStart = !isset($_POST['obj']['dateStart']) ? '' : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['dateStart'])));
		$timeStart = !isset($_POST['obj']['timeStart']) ? 0 : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['timeStart'])));

		$dateEnd = !isset($_POST['obj']['dateEnd']) ? 0 : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['dateEnd'])));
		$timeEnd = !isset($_POST['obj']['timeEnd']) ? 0 : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['timeEnd'])));

		$Truck = !isset($_POST['obj']['Truck']) ? 0 : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['Truck'])));
		//exit($Truck);

		$explode = explode(' | ', $Truck);
		$Truck_Number = $explode[0];
		// $Truck_Type = $explode[1];

		$Supplier = !isset($_POST['obj']['Supplier']) ? 0 : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['Supplier'])));
		$explode = explode(' | ', $Supplier);
		//$Supplier_Code = $explode[0];
		$Supplier_Name_Short = $explode[0];

		$dateStart = (explode(' ', $dateStart))[0];
		$dateEnd = (explode(' ', $dateEnd))[0];

		if (!(validateDate($dateStart, 'Y-m-d') && validateDate($timeStart, 'H:i') && validateDate($dateEnd, 'Y-m-d') && validateDate($timeEnd, 'H:i'))) {
			closeDBT($mysqli, 2, 'ป้อนเวลาไม่ถูกต้อง');
		}

		if (strlen($Truck_Number) == 0) {
			closeDBT($mysqli, 2, 'ทะเบียนรถไม่ถูกต้อง');
		}

		if (strlen($Supplier_Name_Short) == 0) {
			closeDBT($mysqli, 2, 'ซัฟพลายเออร์ไม่ถูกต้อง');
		}

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

			$where[] = "tsm.Customer_ID = uuid_to_bin('$Customer_ID',true)";
			$sqlWhere = join(' OR ', $where);
		}

		// exit($sqlWhere);

		$sql = "SELECT 
		ttlog.gps_updateDatetime,
		ttlog.truckLicense,
		tsm.Supplier_Name_Short,
		ST_ASGEOJSON(ST_CENTROID(tsm.geo)) sup_geo,
		IF(ST_CONTAINS(tsm.geo, (ttlog.geo)) = 1,
			'YES',
			'NO') Contain,
		ST_ASGEOJSON(ttlog.Geo) pt
		FROM
		aatmr_v2_test.tbl_truck_log ttlog,
		`tspk-mr`.tbl_supplier_master tsm
		WHERE
		gps_updateDatetime BETWEEN '$dateStart $timeStart' AND '$dateEnd $timeEnd'
			AND truckLicense = '$Truck_Number'
			AND tsm.Supplier_Name_Short = '$Supplier_Name_Short'
			AND ($sqlWhere)
		-- GROUP BY gps_updateDatetime, truckLicense, Supplier_Name_Short
		;";
		//exit($sql);
		//GROUP BY tsm.Supplier_Name_Short
		// AND tsm.Supplier_Code = '$Supplier_Code'

		$re = sqlError($mysqli, __LINE__, $sql);
		closeDBT($mysqli, 1, jsonRow($re, true, 0));
	} else if ($type == 5) {
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

			$where[] = "ttm.Customer_ID = uuid_to_bin('$Customer_ID',true)";
			$sqlWhere = join(' OR ', $where);
		}

		$val = checkTXT($mysqli, $_GET['filter']['value']);
		if (strlen(trim($val)) == 0) {
			echo "[]";
		}
		$sql = "SELECT 
			DISTINCT concat(ttm.Truck_Number, ' | ', ttm.Truck_Type) AS value
		FROM
			`tspk-mr`.tbl_truck_master ttm
			-- 	INNER JOIN
			-- aatmr_v2_test.tbl_truck attv on ttm.Truck_Number = attv.truckLicense
		WHERE
			ttm.Truck_Number LIKE '%$val%'
				AND ttm.Status = 'ACTIVE'
				AND ttm.Truck_Number != 'N/A'
				AND ($sqlWhere);";
		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else if ($type == 6) {
		$sql = "SELECT 
			DISTINCT concat(ttm.Truck_Number, ' | ', ttm.Truck_Type) as Truck
		FROM
			`tspk-mr`.tbl_truck_master ttm
				INNER JOIN
			aatmr_v2_test.tbl_truck attv on ttm.Truck_Number = attv.truckLicense
		WHERE
			ttm.Status = 'ACTIVE'
				AND ttm.Truck_Number != 'N/A'
				AND ttm.Customer_ID = uuid_to_bin('$Customer_ID',true);";
		//exit($sql);
		toArrayStringOne($mysqli->query($sql), 1);
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'TruckGpsMonitor'}[1] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'TruckGpsMonitor'}[2] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'TruckGpsMonitor'}[3] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'TruckGpsMonitor'}[1] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else
	closeDBT($mysqli, 2, 'TYPE ERROR');


function getDataGeoSupplier($mysqli, $entry_project)
{
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

		$where[] = "tsm.Customer_ID = uuid_to_bin('$Customer_ID',true)";
		$sqlWhere = join(' OR ', $where);
	}

	$sql = "SELECT DISTINCT
		tsm.Supplier_Code,
		tsm.Supplier_Name_Short,
		tsm.Supplier_Name,
		-- t1.Customer_Code,
		ST_ASGEOJSON(tsm.geo) AS supplier_geo,
		ST_ASGEOJSON(ST_CENTROID(tsm.geo)) AS supplier_geoCenter
	FROM
		tbl_supplier_master tsm
	WHERE
		tsm.Status = 'ACTIVE'
			AND ($sqlWhere)
	GROUP BY tsm.Supplier_Name_Short
	ORDER BY tsm.Supplier_Name_Short;";
	return sqlError($mysqli, __LINE__, $sql);
}


function getDataGeoTruck($mysqli, $sqlWhere)
{
	$sql = "WITH rank_truck AS (
		SELECT 
			rank() OVER (
				PARTITION BY ttm.Truck_Number ORDER BY ttl.planin_time ASC
				) AS rank_num,
			ttm.Truck_Number,
			ttm.Truck_Type,
			ttm.gps_angle,
            ttm.gps_speed,
			ST_ASGEOJSON(ttm.geo) AS truck_geo,
			ST_ASGEOJSON(ST_CENTROID(ttm.geo)) AS truck_geoCenter,
			ttl.pus_No,
			planin_time,
			actual_in_time,
			planout_time,
			actual_out_time,
			DATE_SUB(NOW(), INTERVAL 2 HOUR) AS time_start,
			IF(ISNULL(actual_in_time),
				planin_time,
				planout_time) AS Plan,
			DATE_ADD(NOW(), INTERVAL 2 HOUR) AS time_end
		FROM
			tbl_truck_master ttm
				INNER JOIN
			tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		WHERE
			ttm.Status = 'ACTIVE'
				AND ttm.Truck_Number != 'N/A'
				AND ($sqlWhere)
				AND tts.truckNo_Date = CURDATE()
                AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT')
				AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT')
				AND IF(ISNULL(actual_in_time),
				planin_time,
				planout_time) BETWEEN DATE_SUB(NOW(), INTERVAL 2 HOUR) AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
		ORDER BY ttm.Truck_ID, ttl.planin_time ASC
		)
		SELECT *
		FROM rank_truck
		WHERE rank_num <= 1;";
	//exit($sql);
	return sqlError($mysqli, __LINE__, $sql);
}

function getDataGeoTruckMaster($mysqli, $sqlWhere)
{
	$sql = "SELECT 
	ttm.Truck_Number,
	ttm.Truck_Type,
	ttm.gps_speed,
	ttm.gps_angle,
	ttm.gps_updateDatetime,
	ST_ASGEOJSON(ttm.geo) AS truck_geo
FROM
	tbl_truck_master ttm
		INNER JOIN
	tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
		INNER JOIN
	tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
WHERE
	ttm.Status = 'ACTIVE'
		AND ttm.Truck_Number != 'N/A'
		AND ($sqlWhere)
		AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT')
		AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT')
		AND ttl.Pick != 'N'
		-- AND IF(ISNULL(ttl.actual_in_time),
		-- ttl.planin_time,
		-- ttl.planout_time) BETWEEN DATE_SUB(NOW(), INTERVAL 7 HOUR) AND DATE_ADD(NOW(), INTERVAL 7 HOUR)
GROUP BY ttm.Truck_Number
ORDER BY ttm.Truck_Number;";
	//exit($sql);
	return sqlError($mysqli, __LINE__, $sql);
}

$mysqli->close();
exit();
