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
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ViewOrder'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ViewOrder'}[0] == 0) {
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

require('../vendor/autoload.php');
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
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'sqlWhere' => $sqlWhere];
		$re1 = getData($mysqli, $data, __LINE__);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ViewOrder'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
		$dataParams = array(
			'obj',
			'obj=>Truck_Number:s:0:1',
			'obj=>Truck_Type:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Truck_Number 
			FROM 
				tbl_truck_master 
			WHERE 
				Truck_Number = '$Truck_Number'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีรถเลขทะเบียนนี้อยู่แล้ว');
			}

			$sql = "INSERT INTO tbl_truck_master (
				Truck_Number,
			Truck_Type,
			Creation_Date,
			Creation_DateTime,
			Created_By_ID )
			VALUES (
				'$Truck_Number',
				'$Truck_Type',
				curdate(),
				now(),
				$cBy )";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}
			$mysqli->commit();

			$sql = "SELECT 
			BIN_TO_UUID(Order_ID,TRUE) AS Order_ID,
				Refer_ID,
				Pickup_Date,
				DueTime,
				Destination,
				BIN_TO_UUID(Part_ID,TRUE) AS Part_ID,
				Part_No,
				Part_Name,
				BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID,
				Supplier_Name_Short,
				Supplier_Name,
				Qty,
				Actual_Qty,
				PO_No,
				Invoice_No,
				Command,
				Creation_Date
			FROM
				tbl_order
			WHERE 
				Command != 'DELETE'
				AND (Pick = '' OR Actual_Qty < Qty);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ViewOrder'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>Order_ID:s:0:1',
			'obj=>Qty:i:0:0',
			'obj=>Actual_Qty:i:0:0',
			'obj=>Pickup_Date:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			// echo $Order_ID;
			// exit();

			$sql = "SELECT Refer_ID FROM tbl_order 
			where BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}

			$sql = "UPDATE tbl_order
			SET
				Pickup_Date = '$Pickup_Date',
				Qty = $Qty,
				Actual_Qty = $Actual_Qty,
				Updated_By_ID = $cBy,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = NOW()
			WHERE
				Order_ID = UUID_TO_BIN('$Order_ID',TRUE);";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			//exit();
			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ViewOrder'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ViewOrder'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		if (!isset($_FILES["upload"])) {
			echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
			closeDB($mysqli);
		}
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$fileName = $randomString . '_' . $_FILES["upload"]["name"];
		$tempName = $_FILES["upload"]["tmp_name"];
		if (move_uploaded_file($tempName, "../order_file/" . $fileName)) {
			$file_info = pathinfo("../order_file/" . $fileName);
			$myfile = fopen("../order_file/" . $file_info['basename'], "r") or die("Unable to open file!");
			$data_file = fread($myfile, filesize("../order_file/" . $file_info['basename']));
			$file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$allowed_ext = ['xls', 'csv', 'xlsx'];
			fclose($myfile);

			$mysqli->autocommit(FALSE);
			try {
				if (in_array($file_ext, $allowed_ext)) {
					$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('../order_file/' . $fileName);
					$data = $spreadsheet->getActiveSheet()->toArray();
					$count = 0;
					foreach ($data as $row) {
						if ($count > 0) {

							$Refer_ID = $row[0];
							$Pickup_Date = $row[1];
							$Part_No = $row[2];
							$Part_Name = $row[3];
							$Supplier_Code = $row[4];
							$Supplier_Name = $row[5];
							$Qty = $row[6];
							$UM = $row[7];
							$PO_No = $row[8];
							$PO_Line = $row[9];
							$PO_Release = $row[10];
							$Command = $row[11];


							$sql = "SELECT 
								BIN_TO_UUID(Part_ID,true) as Part_ID,
								Part_No
							FROM 
								tbl_part_master 
							WHERE 
								Part_No = '$Part_No';";
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows == 0) {
								throw new Exception('ไม่พบข้อมูล Part ' . $Part_No);
							}
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$Part_ID = $row['Part_ID'];
							}

							$sql = "SELECT 
								BIN_TO_UUID(Supplier_ID,true) as Supplier_ID,
								Supplier_Name_Short
							FROM 
								tbl_supplier_master 
							WHERE 
								Supplier_Code = '$Supplier_Code';";
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows == 0) {
								throw new Exception('ไม่พบข้อมูล Supplier' . $Supplier_Code);
							}
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$Supplier_ID = $row['Supplier_ID'];
								$Supplier_Name_Short = $row['Supplier_Name_Short'];
							}

							$sqlArray[] = array(
								'Refer_ID' => stringConvert($Refer_ID),
								'Pickup_Date' => stringConvert($Pickup_Date),
								'Part_ID' => 'uuid_to_bin("' . $Part_ID . '",true)',
								'Part_No' => stringConvert($Part_No),
								'Part_Name' => stringConvert($Part_Name),
								'Supplier_ID' => 'uuid_to_bin("' . $Supplier_ID . '",true)',
								'Supplier_Code' => stringConvert($Supplier_Code),
								'Supplier_Name_Short' => stringConvert($Supplier_Name_Short),
								'Supplier_Name' => stringConvert($Supplier_Name),
								'Qty' => $Qty,
								'UM' => stringConvert($UM),
								'PO_No' => stringConvert($PO_No),
								'PO_Line' => $PO_Line,
								'PO_Release' => $PO_Release,
								'Command' => stringConvert($Command),
								'Creation_DateTime' => 'now()',
								'Created_By_ID' => $cBy,
								'File_Name' => stringConvert($fileName),
							);
						} else {
							$count = 1;
						}
					}

					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);
						$sqlChunk = array_chunk($sqlArray, 500);

						for ($i = 0, $len = count($sqlChunk); $i < $len; $i++) {
							$sqlValues = prepareValueInsert($sqlChunk[$i]);
							$sql = "INSERT INTO tbl_order $sqlName 
							VALUES $sqlValues
							ON DUPLICATE KEY UPDATE
								Command = 'UPDATE',
								File_Name = '$fileName',
								Last_Updated_Date = curdate(),
								Last_Updated_DateTime = now(),
								Updated_By_ID = $cBy;";
							sqlError($mysqli, __LINE__, $sql, 1, 0);
							$total += $mysqli->affected_rows;
						}
						$mysqli->commit();

						if ($total == 0) throw new Exception('ไม่มีรายการอัพเดท' . $mysqli->error);
						echo '{"status":"server","mms":"Upload สำเร็จ ' . $total . '","data":[]}';
						closeDB($mysqli);
					} else {
						echo '{"status":"server","mms":"ไม่พบข้อมูลในไฟล์ ' . count($sqlArray) . '","data":[]}';
						closeDB($mysqli);
					}
				}
			} catch (Exception $e) {
				$mysqli->rollback();
				echo '{"status":"server","mms":"' . $e->getMessage() . '","sname":[]}';
				closeDB($mysqli);
			}
		} else echo json_encode(array('status' => 'server', 'mms' => 'ข้อมูลในไฟล์ไม่ถูกต้อง', 'sname' => array()));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');


function clean($string)
{
	$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

	return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}

function prepareNameInsert($data)
{
	$dataReturn = array();
	foreach ($data as $key => $value) {
		$dataReturn[] = $key;
	}
	return '(' . join(',', $dataReturn) . ')';
}
function prepareValueInsert($data)
{
	$dataReturn = array();
	foreach ($data as $valueAr) {
		$typeV;
		$keyV;
		$valueV;
		$dataAr = array();
		foreach ($valueAr as $key => $value) {
			$keyV = $key;
			$valueV = $value;
			$dataAr[] = $valueV;
		}
		$dataReturn[] = '(' . join(',', $dataAr) . ')';
	}
	return join(',', $dataReturn);
}
function stringConvert($data)
{
	if (strlen($data) > 0) {
		return "'$data'";
	} else {
		return 'null';
	}
}
function insert($mysqli, $tableName, $data, $error)
{
	$sql = "INSERT into $tableName" . prepareInsert($data);
	sqlError($mysqli, __LINE__, $sql, 1);
	if ($mysqli->affected_rows == 0) {
		throw new Exception($error);
	}
}
function convertDate($valueV)
{
	if (strlen($valueV) > 0) {
		if (is_a($valueV, 'DateTime')) {
			$v = "'" . $valueV->format('Y-m-d') . "'";
		} else {
			$valueV1 = explode('-', $valueV);
			$valueV2 = explode('/', $valueV);
			$valueV3 = explode('.', $valueV);
			$valueV4 = strlen($valueV);
			if (count($valueV1) == 3) {
				$v = switchDate($valueV1);
			} else if (count($valueV2) == 3) {
				$v = switchDate($valueV2);
			} else if (count($valueV3) == 3) {
				$v = switchDate($valueV3);
			} else if ($valueV4 == 8) {
				$v = "'" . substr($valueV, 0, 4) . '-' . substr($valueV, 4, 2) . '-' . substr($valueV, 6, 2) . "'";
			} else {
				$UNIX_DATE = ($valueV - 25569) * 86400;
				$v = "'" . gmdate("Y-m-d", $UNIX_DATE) . "'";
			}
		}
	} else {
		return 'null';
	}


	return $v;
}
function switchDate($d)
{
	if (strlen($d[0]) == 4) {
		return "'" . "$d[0]-$d[1]-$d[2]" . "'";
	} else {
		return "'" . "$d[2]-$d[1]-$d[0]" . "'";
	}
}

function getData($mysqli, $data, $line)
{
	$where = [];
	$where[] = "DATE_FORMAT(torder.Pickup_Date, '%Y-%m-%d') between DATE_FORMAT('$data[Start_Date]', '%Y-%m-%d') and DATE_FORMAT('$data[Stop_Date]', '%Y-%m-%d')";

	$sqlWhere = join(' and ', $where);
	$sql = "SELECT 
		BIN_TO_UUID(torder.Order_ID,TRUE) AS Order_ID,
		torder.Refer_ID,
		torder.Pickup_Date,
		BIN_TO_UUID(tpm.Part_ID,TRUE) AS Part_ID,
		tpm.Part_No,
		tpm.Part_Name,
		BIN_TO_UUID(tsm.Supplier_ID,TRUE) AS Supplier_ID,
		tsm.Supplier_Code,
		tsm.Supplier_Name_Short,
		tsm.Supplier_Name,
		torder.Qty,
		torder.UM,
		torder.Actual_Qty,
		torder.PO_No,
		torder.PO_Line,
		torder.PO_Release,
		torder.Command,
		torder.Creation_DateTime,
		torder.Last_Updated_DateTime,
        tpm.Project,
    	tpm.Product_Code,
        t1.Customer_Code
	FROM
		tbl_order torder
			INNER JOIN
		tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
			INNER JOIN
		tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
			INNER JOIN
		tbl_customer_master t1 ON tpm.Customer_ID = t1.Customer_ID
	WHERE 
		($sqlWhere)
		AND torder.Command != 'DELETE'
		AND tpm.Active = 'Y'
		AND tsm.Status = 'ACTIVE'
		-- AND torder.Actual_Qty < torder.Qty
		AND ((torder.Actual_Qty < torder.Qty) OR (torder.Qty < torder.Actual_Qty))
		AND ($data[sqlWhere])
	ORDER BY torder.Pickup_Date ASC, torder.Last_Updated_DateTime DESC, torder.Refer_ID ASC";
	//exit($sql);
	return sqlError($mysqli, $line, $sql, 1);
}


$mysqli->close();
exit();
