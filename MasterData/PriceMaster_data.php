<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PriceMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PriceMaster'}[0] == 0) {
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
		$sql = "SELECT 
		BIN_TO_UUID(Price_ID,TRUE) AS Price_ID,
		tsm.Supplier_Name_Short,
		tsm.Supplier_Name,
		CAST(Transport_Price AS CHAR) AS Transport_Price, 
		CAST(Planing_Price AS CHAR) AS Planing_Price,
		tpm.Status,
		DATE_FORMAT(tpm.Creation_DateTime, '%Y-%m-%d %H:%i') AS Creation_DateTime,
		DATE_FORMAT(tpm.Last_Updated_DateTime, '%Y-%m-%d %H:%i') AS Last_Updated_DateTime,
		t1.Customer_Code
	FROM 
		tbl_price_master tpm
			INNER JOIN 
		tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID
			INNER JOIN
		tbl_customer_master t1 ON tpm.Customer_ID = t1.Customer_ID
	WHERE 
		tpm.Status = 'Active'
		AND ($sqlWhere)
	Order by t1.Customer_Code, tsm.Supplier_Name_Short ASC;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {
		toArrayStringOne($mysqli->query("SELECT Supplier_Name_Short FROM tbl_supplier_master GROUP BY Supplier_Name_Short"), 1);
	} else if ($type == 3) {
		$val = checkTXT($mysqli, $_GET['filter']['value']);
		if (strlen(trim($val)) == 0) {
			echo "[]";
		}

		$sql = "SELECT 
			Supplier_Name_Short AS value
		FROM
			tbl_Supplier_master
		WHERE
			Supplier_Name_Short LIKE '%$val%';";

		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'PriceMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>Supplier_Name_Short:s:0:1',
			'obj=>Transport_Price:f:0:0',
			'obj=>Planing_Price:f:0:0',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);


			$sql = "SELECT 
				BIN_TO_UUID(Price_ID,true) as Price_ID 
			FROM 
				tbl_price_master 
			where 
				BIN_TO_UUID(Supplier_ID,true) = '$Supplier_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี supplier นี้แล้ว ' . $Supplier_Name_Short);
			}

			$sql = "INSERT INTO tbl_price_master (
			Supplier_ID,
			Transport_Price,
			Planing_Price,
			Customer_ID,
			Creation_Date,
			Creation_DateTime,
			Created_By_ID
			)
			values (
			UUID_TO_BIN('$Supplier_ID',TRUE),
			$Transport_Price,
			$Planing_Price,
			uuid_to_bin('$Customer_ID',true),
			curdate(),
			now(),
			$cBy
			)";
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
	if ($_SESSION['xxxRole']->{'PriceMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>Price_ID:s:0:1',
			'obj=>Supplier_Name_Short:s:0:1',
			'obj=>Transport_Price:f:0:0',
			'obj=>Planing_Price:f:0:0',
			'obj=>Status:s:0:1',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

			$sql = "UPDATE tbl_price_master 
			SET 
				Transport_Price = $Transport_Price,
				Planing_Price = $Planing_Price,
				Status = '$Status',
				Customer_ID = uuid_to_bin('$Customer_ID',true),
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(Price_ID,TRUE) = '$Price_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'PriceMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'PriceMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		if (!isset($_FILES["upload"])) {
			echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
			closeDB($mysqli);
		}
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$fileName = $randomString . '_' . $_FILES["upload"]["name"];
		$tempName = $_FILES["upload"]["tmp_name"];
		if (move_uploaded_file($tempName, "../temp_fileupload/" . $fileName)) {
			$file_info = pathinfo("../temp_fileupload/" . $fileName);
			$myfile = fopen("../temp_fileupload/" . $file_info['basename'], "r") or die("Unable to open file!");
			$data_file = fread($myfile, filesize("../temp_fileupload/" . $file_info['basename']));
			$file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$allowed_ext = ['xls', 'csv', 'xlsx'];
			fclose($myfile);

			$mysqli->autocommit(FALSE);
			try {
				if (in_array($file_ext, $allowed_ext)) {
					$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('../temp_fileupload/' . $fileName);
					$data = $spreadsheet->getActiveSheet()->toArray();
					$count = 0;
					foreach ($data as $row) {
						if ($count > 0) {

							$Supplier_Name_Short = $row[1];
							$Transport_Price = $row[2];
							$Planing_Price = $row[3];
							$Customer_Code = $row[4];

							$Customer_ID = getCustomerID($mysqli, $Customer_Code);
							$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

							$sqlArray[] = array(
								'Supplier_ID' => 'uuid_to_bin("' . $Supplier_ID . '",true)',
								'Transport_Price' => $Transport_Price,
								'Planing_Price' => $Planing_Price,
								'Customer_ID' => 'uuid_to_bin("' . $Customer_ID . '",true)',
								'Created_By_ID' => $cBy,
								'Creation_Date' => 'curdate()',
								'Creation_DateTime' => 'now()'
							);
						} else {
							$count = 1;
						}
					}

					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);

						for ($i = 0, $len = count($sqlArray); $i < $len; $i++) {

							$Supplier_ID = $sqlArray[$i]['Supplier_ID'];
							$Transport_Price = $sqlArray[$i]['Transport_Price'];
							$Planing_Price = $sqlArray[$i]['Planing_Price'];
							$Customer_ID = $sqlArray[$i]['Customer_ID'];
							$Created_By_ID = $sqlArray[$i]['Created_By_ID'];
							$Creation_Date = $sqlArray[$i]['Creation_Date'];
							$Creation_DateTime = $sqlArray[$i]['Creation_DateTime'];

							//exit();
							$sql = "INSERT IGNORE INTO tbl_price_master
							$sqlName
							VALUES (
							$Supplier_ID,
							$Transport_Price,
							$Planing_Price,
							$Customer_ID,
							$Created_By_ID,
							$Creation_Date,
							$Creation_DateTime )
							ON DUPLICATE KEY UPDATE 
							Transport_Price = $Transport_Price,
							Planing_Price = $Planing_Price,
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy";
							//exit($sql);
							sqlError($mysqli, __LINE__, $sql, 1, 0);
							$total += $mysqli->affected_rows;
							$mysqli->commit();
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
				closeDBT($mysqli, 1, jsonRow($re1, true, 0));
			} catch (Exception $e) {
				$mysqli->rollback();
				echo '{"status":"server","mms":"' . $e->getMessage() . '","sname":[]}';
				closeDB($mysqli);
			}
		} else echo json_encode(array('status' => 'server', 'mms' => 'ข้อมูลในไฟล์ไม่ถูกต้อง', 'sname' => array()));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

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

// function prepareValueInsertOnUpdate($header, $data)
// {
// 	$dataReturn = array();
// 	foreach ($data as $valueAr) {
// 		$typeV;
// 		$keyV;
// 		$valueV;
// 		$dataAr = array();
// 		foreach ($valueAr as $key => $value) {
// 			$keyV = $key;
// 			$valueV = $value;
// 			$dataAr[] = $valueV;
// 		}
// 	}
// 	foreach ($header as $valueArheader) {
// 		$typeVheader;
// 		$keyVheader;
// 		$valueVheader;
// 		$dataArheader = array();
// 		foreach ($valueArheader as $keyheader => $valueheader) {
// 			$keyVheader = $keyheader;
// 			$valueVheader = $valueheader;
// 			$dataArheader[] = $valueVheader;
// 		}
// 	}
// 	$dataReturn[] = join(',', $dataAr);
// 	print_r($dataReturn);
// 	exit();
// 	return join(',', $dataReturn);
// }

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

$mysqli->close();
exit();
