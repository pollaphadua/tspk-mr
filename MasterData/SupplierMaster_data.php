<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'SupplierMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'SupplierMaster'}[0] == 0) {
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
			BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID,
			Supplier_Code,
			Supplier_Name,
			Supplier_Name_Short,
			Address,
			GPS,
			Province,
			Sub_Zone,
			t1.Status,
			t2.Customer_Code,
			DATE_FORMAT(t1.Creation_DateTime, '%Y-%m-%d %H:%i') AS Creation_DateTime,
			DATE_FORMAT(t1.Last_Updated_DateTime, '%Y-%m-%d %H:%i') AS Last_Updated_DateTime
		FROM 
			tbl_supplier_master t1
				INNER JOIN 
			tbl_customer_master t2 ON t1.Customer_ID = t2.Customer_ID
		WHERE 
			($sqlWhere)
			ORDER BY t1.Status, Customer_Code, Supplier_Name_Short;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>Supplier_Code:s:0:1',
			'obj=>Supplier_Name:s:0:1',
			'obj=>Supplier_Name_Short:s:0:1',
			'obj=>Province:s:0:1',
			'obj=>Sub_Zone:s:0:1',
			'obj=>Address:s:0:1',
			'obj=>GPS:s:0:1',
			'obj=>Customer_Code:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT 
				Supplier_Code 
			FROM 
				tbl_supplier_master 
			WHERE 
				Supplier_Code = '$Supplier_Code'
					AND Supplier_Name_Short = '$Supplier_Name_Short';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี Supplier นี้แล้ว');
			}

			$sql = "SELECT 
				Supplier_Name_Short 
			FROM 
				tbl_supplier_master 
			WHERE 
				Supplier_Name_Short = '$Supplier_Name_Short';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มี Supplier นี้แล้ว');
			}

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);

			$sql = "INSERT INTO tbl_supplier_master (
				Supplier_Code,
			Supplier_Name,
			Supplier_Name_Short,
			Province,
			Sub_Zone,
			Address,
			GPS,
			geo,
			Customer_ID,
			Creation_Date,
			Creation_DateTime,
			Created_By_ID )
			VALUES (
				'$Supplier_Code',
				'$Supplier_Name',
				'$Supplier_Name_Short',
				'$Province',
				'$Sub_Zone',
				'$Address',
				'$GPS',
				ST_GeomFromText('point(0 0)'),
				uuid_to_bin('$Customer_ID',true),
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
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>Supplier_ID:s:0:0',
			'obj=>Supplier_Code:s:0:1',
			'obj=>Supplier_Name:s:0:1',
			'obj=>Supplier_Name_Short:s:0:1',
			'obj=>Province:s:0:1',
			'obj=>Sub_Zone:s:0:1',
			'obj=>Address:s:0:1',
			'obj=>GPS:s:0:1',
			'obj=>Status:s:0:1',
			'obj=>Customer_Code:s:0:1'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			// $sql = "SELECT 
			// 	Supplier_Name 
			// FROM 
			// 	tbl_supplier_master 
			// WHERE 
			// 	Supplier_Code = '$Supplier_Code'
			// 		AND Supplier_Name_Short = '$Supplier_Name_Short';";
			// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
			// if ($re1->num_rows == 0) {
			// 	throw new Exception('ไม่พบข้อมูล');
			// }

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);

			$sql = "UPDATE tbl_supplier_master 
			SET 
				Supplier_Code = '$Supplier_Code',
				Supplier_Name_Short = '$Supplier_Name_Short',
				Supplier_Name = '$Supplier_Name',
				Province = '$Province',
				Sub_Zone = '$Sub_Zone',
				Address = '$Address',
				GPS = '$GPS',
				Status = '$Status',
				Customer_ID = uuid_to_bin('$Customer_ID',true),
				Last_Updated_Date = CURDATE(),
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Supplier_ID,TRUE) = '$Supplier_ID';";
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
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'SupplierMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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

							$Province = $row[1];
							$Sub_Zone = $row[2];
							$Supplier_Code = $row[3];
							$Supplier_Name_Short = $row[4];
							$Supplier_Name = $row[5];
							$Address = $row[6];
							$GPS = $row[7];
							$Customer_Code = $row[8];

							$Customer_ID = getCustomerID($mysqli, $Customer_Code);

							$sql = "SELECT 
								Supplier_Name_Short,
								ST_AsText(geo) AS supplier_geo
							FROM 
								tbl_supplier_master 
							WHERE 
								Supplier_Name_Short = '$Supplier_Name_Short'
									AND ST_AsText(geo) != 'point(0 0)'
							LIMIT 1;";
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows > 0) {
								$supplier_geo = $re1->fetch_array(MYSQLI_ASSOC)['supplier_geo'];
							} else {
								$supplier_geo = 'point(0 0)';
							}

							//exit($supplier_geo);

							$sqlArray[] = array(
								'Province' => stringConvert($Province),
								'Sub_Zone' => stringConvert($Sub_Zone),
								'Supplier_Code' => stringConvert($Supplier_Code),
								'Supplier_Name_Short' => stringConvert($Supplier_Name_Short),
								'Supplier_Name' => stringConvert($Supplier_Name),
								'Address' => stringConvert($Address),
								'GPS' => stringConvert($GPS),
								'geo' => $supplier_geo,
								'Customer_ID' => 'uuid_to_bin("' . $Customer_ID . '",true)',
								'Created_By_ID' => $cBy,
								'Creation_DateTime' => 'now()',
								'Creation_Date' => 'curdate()'
							);
						} else {
							$count = 1;
						}
					}

					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);

						for ($i = 0, $len = count($sqlArray); $i < $len; $i++) {

							$Province = $sqlArray[$i]['Province'];
							$Sub_Zone = $sqlArray[$i]['Sub_Zone'];
							$Supplier_Code = $sqlArray[$i]['Supplier_Code'];
							$Supplier_Name_Short = $sqlArray[$i]['Supplier_Name_Short'];
							$Supplier_Name = $sqlArray[$i]['Supplier_Name'];
							$Address = $sqlArray[$i]['Address'];
							$GPS = $sqlArray[$i]['GPS'];
							$geo = $sqlArray[$i]['geo'];
							$Customer_ID = $sqlArray[$i]['Customer_ID'];
							$Created_By_ID = $sqlArray[$i]['Created_By_ID'];
							$Creation_DateTime = $sqlArray[$i]['Creation_DateTime'];
							$Creation_Date = $sqlArray[$i]['Creation_Date'];

							//exit();
							$sql = "INSERT IGNORE INTO tbl_supplier_master
							$sqlName
							VALUES (
							$Province,
							$Sub_Zone,
							$Supplier_Code,
							$Supplier_Name_Short,
							$Supplier_Name,
							$Address,
							$GPS,
							ST_GeomFromText('$geo'),
							$Customer_ID,
							$Created_By_ID,
							$Creation_DateTime,
							$Creation_Date)
							ON DUPLICATE KEY UPDATE 
							Province = $Province,
							Sub_Zone = $Sub_Zone,
							Supplier_Name = $Supplier_Name,
							Customer_ID = $Customer_ID,
							Address = $Address,
							GPS = $GPS,
							geo = ST_GeomFromText('$geo'),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy";
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

					// $total = 0;
					// if (count($sqlArray) > 0) {
					// 	$sqlName = prepareNameInsert($sqlArray[0]);
					// 	$sqlChunk = array_chunk($sqlArray, 500);

					// 	for ($i = 0, $len = count($sqlChunk); $i < $len; $i++) {
					// 		$sqlValues = prepareValueInsert($sqlChunk[$i]);
					// 		$sql = "INSERT IGNORE INTO tbl_supplier_master $sqlName VALUES $sqlValues";
					// 		sqlError($mysqli, __LINE__, $sql, 1, 0);
					// 		$total += $mysqli->affected_rows;
					// 	}
					// 	//exit();
					// 	$mysqli->commit();

					// 	if ($total == 0) throw new Exception('ไม่มีรายการอัพเดท' . $mysqli->error);
					// 	echo '{"status":"server","mms":"Upload สำเร็จ ' . $total . '","data":[]}';
					// 	closeDB($mysqli);
					// } else {
					// 	echo '{"status":"server","mms":"ไม่พบข้อมูลในไฟล์ ' . count($sqlArray) . '","data":[]}';
					// 	closeDB($mysqli);
					// }
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
