<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'SetLocation'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'SetLocation'}[0] == 0) {
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
		$re = select($mysqli, $sqlWhere);
		closeDBT($mysqli, 1, jsonRow($re, true, 0));
	} else if ($type == 2) {
		$re = getDataGeoSupplier($mysqli, $sqlWhere);
		closeDBT($mysqli, 1, jsonRow($re, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'SetLocation'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'SetLocation'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		if (!isset($_POST['obj'])) {
			echo json_encode(array('ch' => 2, 'data' => 'ข้อมูลไม่ถูกต้อง 1'));
			closeDB($mysqli);
		}
		$Supplier = !isset($_POST['obj']['Supplier']) ? '' : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['Supplier'])));
		$data = !isset($_POST['obj']['polygon']) ? 0 : $mysqli->real_escape_string(trim(strtoupper($_POST['obj']['polygon'])));

		//exit();

		if (strlen($Supplier) == 0 || strlen($data) == 0) {
			echo json_encode(array('ch' => 2, 'data' => 'ข้อมูลไม่ถูกต้อง 2'));
			closeDB($mysqli);
		}

		$explode = explode(' | ', $Supplier);
		//$Supplier_Code = $explode[0];
		$Supplier_Name_Short = $explode[0];
		//$Customer_Code = $explode[2];

		//$Customer_ID = getCustomerID($mysqli, $Customer_Code);

		$sql = "UPDATE tbl_supplier_master 
		SET 
			geo = ST_GeomFromText('$data'),
			Updated_By_ID = $cBy,
			Last_Updated_DateTime = now() 
		WHERE
			Supplier_Name_Short = '$Supplier_Name_Short';";
		// AND Supplier_Code = '$Supplier_Code'
		// AND Customer_ID = uuid_to_bin('$Customer_ID',true)
		$mysqli->autocommit(FALSE);

		try {
			if (!$mysqli->query($sql)) throw new Exception($mysqli->error);
			if ($mysqli->affected_rows == 0) throw new Exception('ไม่สามารถบันทึกข้อมูลได้โปรดลองอึกครั้ง');
			$mysqli->commit();

			closeDBT($mysqli, 1, '');
		} catch (Exception $e) {
			$mysqli->rollback();
			echo json_encode(array('ch' => 2, 'data' => $e->getMessage()));
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'SetLocation'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'SetLocation'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

function select($mysqli, $sqlWhere)
{
	$sql = "SELECT Supplier_Code, Supplier_Name_Short, if(st_AsText(geo)='POINT(0 0)','ยังไม่ตีกรอบ','OK')checkGeo,
	t2.Customer_Code,
	t1.Creation_DateTime,t1.Created_By_ID,t1.Last_Updated_DateTime,t1.Updated_By_ID
	from 
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
	WHERE t1.Status = 'ACTIVE' AND ($sqlWhere)
	;";
	//exit($sql);
	return sqlError($mysqli, __LINE__, $sql, 1);
}

function getDataGeoSupplier($mysqli, $sqlWhere)
{
	$sql = "SELECT 
    	t1.Supplier_Code,
		t1.Supplier_Name_Short,
		t1.Supplier_Name,
		ST_ASGEOJSON(t1.geo) AS supplier_geo,
		ST_ASGEOJSON(ST_CENTROID(t1.geo)) AS supplier_geoCenter,
		t2.Customer_Code
	FROM
		tbl_supplier_master t1
			INNER JOIN
		tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
	WHERE t1.Status = 'ACTIVE' AND ($sqlWhere)
	GROUP BY Supplier_Name_Short;";
	//exit($sql);
	return sqlError($mysqli, __LINE__, $sql);
}


$mysqli->close();
exit();
