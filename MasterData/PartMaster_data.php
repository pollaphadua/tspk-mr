<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'PartMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'PartMaster'}[0] == 0) {
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
			BIN_TO_UUID(Part_ID,TRUE) AS Part_ID,
			Part_No, 
			Part_Name, 
			CONCAT(tsm.Supplier_Code, ' | ',tsm.Supplier_Name_Short) as Supplier,
			tsm.Supplier_Code,
			tsm.Supplier_Name_Short,
			Pallet_Type,
			BIN_TO_UUID(Package_ID,TRUE) AS Package_ID,
			Width_Pallet_Size,
			Length_Pallet_Size,
			Height_Pallet_Size,
			CONCAT(Width_Pallet_Size,
						'x',
						Length_Pallet_Size,
						'x',
						Height_Pallet_Size) AS Dimansion,
			SNP_Per_Pallet,
			Mass_Per_Pcs,
			Mass_Per_Pallet,
			Usage_Pcs_Per_Unit,
			CBM_Per_Pkg,
			Project,
			Product_Code,
			t1.Customer_Code,
			Active,
			DATE_FORMAT(tpm.Creation_DateTime, '%Y-%m-%d %H:%i') AS Creation_DateTime,
			DATE_FORMAT(tpm.Last_Updated_DateTime, '%Y-%m-%d %H:%i') AS Last_Updated_DateTime
		FROM 
			tbl_part_master tpm
				INNER JOIN 
			tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID
				INNER JOIN 
			tbl_customer_master t1 ON tpm.Customer_ID = t1.Customer_ID

		WHERE 
			($sqlWhere)
		Order by Active, t1.Customer_Code, tpm.Project, tsm.Supplier_Name_Short;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'PartMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		$dataParams = array(
			'obj',
			'obj=>Part_No:s:0:1',
			'obj=>Part_Name:s:0:1',
			'obj=>Project:s:0:0',
			'obj=>Product_Code:s:0:0',
			'obj=>Supplier:s:0:1',
			'obj=>Pallet_Type:s:0:1',
			'obj=>Width_Pallet_Size:i:0:0',
			'obj=>Length_Pallet_Size:i:0:0',
			'obj=>Height_Pallet_Size:i:0:0',
			'obj=>Mass_Per_Pcs:f:0:0',
			'obj=>SNP_Per_Pallet:i:0:0',
			'obj=>Mass_Per_Pallet:f:0:0',
			'obj=>CBM_Per_Pkg:f:0:0',
			'obj=>Usage_Pcs_Per_Unit:i:0:0',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {
			$sql = "SELECT Part_No FROM tbl_part_master 
			where Part_No = '$Part_No'";
			if ((sqlError($mysqli, __LINE__, $sql, 1))->num_rows > 0) {
				throw new Exception('มี Part_No นี้แล้ว');
			}

			$explode = explode(' | ', $Supplier);
			$Supplier_Code = $explode[0];
			$Supplier_Name_Short = $explode[1];


			$sql = "SELECT 
				BIN_TO_UUID(Package_ID,true) as Package_ID,
				Package_Code
			FROM
				tbl_package_master
			WHERE 
				Width_Pallet_Size = $Width_Pallet_Size
					AND Length_Pallet_Size = $Length_Pallet_Size
					AND Height_Pallet_Size = $Height_Pallet_Size
					AND Package_Type = '$Pallet_Type';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Package_ID = $row['Package_ID'];
					$Package_Code = $row['Package_Code'];
				}
			} else {
				//throw new Exception('ไม่พบ Package' . '<br>' . 'Weight : ' . $Width_Pallet_Size . '<br>' . "Length : " . $Length_Pallet_Size . '<br>' . "Height : " . $Height_Pallet_Size . "<br>" . $Part_No);

				if ($Pallet_Type == 'Box') {
					$Packaging = 'PTB';
				} else if ($Pallet_Type == 'Pallet') {
					$Packaging = 'STP';
				} else if ($Pallet_Type == 'Rack') {
					$Packaging = 'STD';
				}

				$sql = "INSERT INTO tbl_package_master (
					Package_Code,
				Package_Type,
				Packaging,
				Width_Pallet_Size,
				Length_Pallet_Size,
				Height_Pallet_Size,
				Creation_Date,
				Creation_DateTime,
				Created_By_ID)
				values (
					concat(SUBSTRING('$Pallet_Type', 1,1),$Width_Pallet_Size,$Length_Pallet_Size,$Height_Pallet_Size),
					'$Pallet_Type',
					'$Packaging',
					$Width_Pallet_Size,
					$Length_Pallet_Size,
					$Height_Pallet_Size,
				curdate(),
				now(),
				$cBy)
				ON DUPLICATE KEY UPDATE
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy;";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					//exit($sql);
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$sql = "SELECT 
					BIN_TO_UUID(Package_ID,true) as Package_ID,
					Package_Code
				FROM
					tbl_package_master
				WHERE 
					Width_Pallet_Size = $Width_Pallet_Size
						AND Length_Pallet_Size = $Length_Pallet_Size
						AND Height_Pallet_Size = $Height_Pallet_Size
						AND Package_Type = '$Pallet_Type';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					//exit($sql);
					throw new Exception('ไม่พบข้อมูล ' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Package_ID = $row['Package_ID'];
					$Package_Code = $row['Package_Code'];
				}
			}


			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

			$sql = "INSERT INTO tbl_part_master (
				Part_No, 
			Part_Name,
			Project,
			Product_Code,
			Supplier_ID,
			Pallet_Type,
			Package_ID,
			Width_Pallet_Size,
			Length_Pallet_Size,
			Height_Pallet_Size,
			Mass_Per_Pcs,
			SNP_Per_Pallet,
			Mass_Per_Pallet,
			CBM_Per_Pkg,
			Usage_Pcs_Per_Unit,
			Customer_ID,
			Creation_Date,
			Creation_DateTime,
			Created_By_ID
			)
			values (
				'$Part_No', 
			'$Part_Name',
			'$Project',
			'$Product_Code',
			UUID_TO_BIN('$Supplier_ID',TRUE),
			'$Pallet_Type',
			UUID_TO_BIN('$Package_ID',TRUE),
			$Width_Pallet_Size,
			$Length_Pallet_Size,
			$Height_Pallet_Size,
			$Mass_Per_Pcs,
			$SNP_Per_Pallet,
			$Mass_Per_Pallet,
			$CBM_Per_Pkg,
			$Usage_Pcs_Per_Unit,
			UUID_TO_BIN('$Customer_ID',TRUE),
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
	if ($_SESSION['xxxRole']->{'PartMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>Part_ID:s:0:1',
			'obj=>Part_No:s:0:1',
			'obj=>Part_Name:s:0:1',
			'obj=>Project:s:0:0',
			'obj=>Product_Code:s:0:0',
			'obj=>Supplier:s:0:1',
			'obj=>Pallet_Type:s:0:1',
			'obj=>Width_Pallet_Size:i:0:0',
			'obj=>Length_Pallet_Size:i:0:0',
			'obj=>Height_Pallet_Size:i:0:0',
			'obj=>Mass_Per_Pcs:f:0:0',
			'obj=>SNP_Per_Pallet:i:0:0',
			'obj=>Mass_Per_Pallet:f:0:0',
			'obj=>CBM_Per_Pkg:f:0:0',
			'obj=>Usage_Pcs_Per_Unit:i:0:0',
			'obj=>Active:s:0:1',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$explode = explode(' | ', $Supplier);
			$Supplier_Code = $explode[0];
			$Supplier_Name_Short = $explode[1];

			$sql = "SELECT 
				BIN_TO_UUID(Package_ID,true) as Package_ID,
				Package_Code
			FROM
				tbl_package_master
			WHERE 
				Width_Pallet_Size = $Width_Pallet_Size
					AND Length_Pallet_Size = $Length_Pallet_Size
					AND Height_Pallet_Size = $Height_Pallet_Size
					AND Package_Type = '$Pallet_Type';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Package_ID = $row['Package_ID'];
					$Package_Code = $row['Package_Code'];
				}
			} else {
				//throw new Exception('ไม่พบ Package' . '<br>' . 'Weight : ' . $Width_Pallet_Size . '<br>' . "Length : " . $Length_Pallet_Size . '<br>' . "Height : " . $Height_Pallet_Size . "<br>" . $Part_No);

				if ($Pallet_Type == 'Box') {
					$Packaging = 'PTB';
				} else if ($Pallet_Type == 'Pallet') {
					$Packaging = 'STP';
				} else if ($Pallet_Type == 'Rack') {
					$Packaging = 'STD';
				}

				$sql = "INSERT INTO tbl_package_master (
					Package_Code,
				Package_Type,
				Packaging,
				Width_Pallet_Size,
				Length_Pallet_Size,
				Height_Pallet_Size,
				Creation_Date,
				Creation_DateTime,
				Created_By_ID)
				values (
					concat(SUBSTRING('$Pallet_Type', 1,1),$Width_Pallet_Size,$Length_Pallet_Size,$Height_Pallet_Size),
					'$Pallet_Type',
					'$Packaging',
					$Width_Pallet_Size,
					$Length_Pallet_Size,
					$Height_Pallet_Size,
				curdate(),
				now(),
				$cBy)
				ON DUPLICATE KEY UPDATE
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy;";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					//exit($sql);
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
				}

				$sql = "SELECT 
					BIN_TO_UUID(Package_ID,true) as Package_ID,
					Package_Code
				FROM
					tbl_package_master
				WHERE 
					Width_Pallet_Size = $Width_Pallet_Size
						AND Length_Pallet_Size = $Length_Pallet_Size
						AND Height_Pallet_Size = $Height_Pallet_Size
						AND Package_Type = '$Pallet_Type';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					//exit($sql);
					throw new Exception('ไม่พบข้อมูล ' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Package_ID = $row['Package_ID'];
					$Package_Code = $row['Package_Code'];
				}
			}

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

			$sql = "UPDATE tbl_part_master 
			SET 
				Part_Name = '$Part_Name',
				Project = '$Project',
				Product_Code = '$Product_Code',
				Supplier_ID = UUID_TO_BIN('$Supplier_ID',TRUE),
				Pallet_Type = '$Pallet_Type',
				Package_ID = UUID_TO_BIN('$Package_ID',TRUE),
				Width_Pallet_Size = $Width_Pallet_Size,
				Length_Pallet_Size = $Length_Pallet_Size,
				Height_Pallet_Size = $Height_Pallet_Size,
				Mass_Per_Pcs = $Mass_Per_Pcs,
				SNP_Per_Pallet = $SNP_Per_Pallet,
				Mass_Per_Pallet = $Mass_Per_Pallet,
				CBM_Per_Pkg = $CBM_Per_Pkg,
				Usage_Pcs_Per_Unit = $Usage_Pcs_Per_Unit,
				Customer_ID = UUID_TO_BIN('$Customer_ID',TRUE),
				Active = '$Active',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(Part_ID,TRUE) = '$Part_ID'";
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
	if ($_SESSION['xxxRole']->{'PartMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'PartMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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

							$Project = $row[1];
							$Product_Code = $row[2];
							$Part_No = $row[3];
							$Part_Name = $row[4];
							$Supplier_Name_Short = $row[5];
							$Pallet_Type = $row[6];
							$Width_Pallet_Size = $row[7];
							$Length_Pallet_Size = $row[8];
							$Height_Pallet_Size = $row[9];
							$CBM_Per_Pkg = $row[10];
							$SNP_Per_Pallet = $row[11];
							$Mass_Per_Pcs = $row[12];
							$Mass_Per_Pallet = $row[13];
							$Usage_Pcs_Per_Unit = $row[14];
							$Customer_Code = $row[15];


							$sql = "SELECT 
								BIN_TO_UUID(Package_ID,true) as Package_ID,
								Package_Code
							FROM
								tbl_package_master
							WHERE 
								Width_Pallet_Size = $Width_Pallet_Size
									AND Length_Pallet_Size = $Length_Pallet_Size
									AND Height_Pallet_Size = $Height_Pallet_Size
									AND Package_Type = '$Pallet_Type';";
							//exit($sql);
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows > 0) {
								while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
									$Package_ID = $row['Package_ID'];
									$Package_Code = $row['Package_Code'];
								}
							} else {
								//throw new Exception('ไม่พบ Package' . '<br>' . 'Weight : ' . $Width_Pallet_Size . '<br>' . "Length : " . $Length_Pallet_Size . '<br>' . "Height : " . $Height_Pallet_Size . "<br>" . $Part_No);

								if ($Pallet_Type == 'Box') {
									$Packaging = 'PTB';
								} else if ($Pallet_Type == 'Pallet') {
									$Packaging = 'STP';
								} else if ($Pallet_Type == 'Rack') {
									$Packaging = 'STD';
								}

								$sql = "INSERT INTO tbl_package_master (
									Package_Code,
								Package_Type,
								Packaging,
								Width_Pallet_Size,
								Length_Pallet_Size,
								Height_Pallet_Size,
								Creation_Date,
								Creation_DateTime,
								Created_By_ID)
								values (
									concat(SUBSTRING('$Pallet_Type', 1,1),$Width_Pallet_Size,$Length_Pallet_Size,$Height_Pallet_Size),
									'$Pallet_Type',
									'$Packaging',
									$Width_Pallet_Size,
									$Length_Pallet_Size,
									$Height_Pallet_Size,
								curdate(),
								now(),
								$cBy)
								ON DUPLICATE KEY UPDATE
								Last_Updated_DateTime = NOW(),
								Updated_By_ID = $cBy;";
								sqlError($mysqli, __LINE__, $sql, 1);
								if ($mysqli->affected_rows == 0) {
									//exit($sql);
									throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
								}

								$sql = "SELECT 
									BIN_TO_UUID(Package_ID,true) as Package_ID,
									Package_Code
								FROM
									tbl_package_master
								WHERE 
									Width_Pallet_Size = $Width_Pallet_Size
										AND Length_Pallet_Size = $Length_Pallet_Size
										AND Height_Pallet_Size = $Height_Pallet_Size
										AND Package_Type = '$Pallet_Type';";
								$re1 = sqlError($mysqli, __LINE__, $sql, 1);
								if ($re1->num_rows == 0) {
									//exit($sql);
									throw new Exception('ไม่พบข้อมูล ' . __LINE__);
								}
								while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
									$Package_ID = $row['Package_ID'];
									$Package_Code = $row['Package_Code'];
								}
							}

							$Customer_ID = getCustomerID($mysqli, $Customer_Code);
							$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

							$sqlArray[] = array(
								'Project' => stringConvert($Project),
								'Product_Code' => stringConvert($Product_Code),
								'Part_No' => stringConvert($Part_No),
								'Part_Name' => stringConvert($Part_Name),
								'Supplier_ID' => 'uuid_to_bin("' . $Supplier_ID . '",true)',
								'Package_ID' => 'uuid_to_bin("' . $Package_ID . '",true)',
								'Pallet_Type' => stringConvert($Pallet_Type),
								'Width_Pallet_Size' => $Width_Pallet_Size,
								'Length_Pallet_Size' => $Length_Pallet_Size,
								'Height_Pallet_Size' => $Height_Pallet_Size,
								'CBM_Per_Pkg' => stringConvert($CBM_Per_Pkg),
								'SNP_Per_Pallet' => stringConvert($SNP_Per_Pallet),
								'Mass_Per_Pcs' => stringConvert($Mass_Per_Pcs),
								'Mass_Per_Pallet' => stringConvert($Mass_Per_Pallet),
								'Usage_Pcs_Per_Unit' => stringConvert($Usage_Pcs_Per_Unit),
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

							$Project = $sqlArray[$i]['Project'];
							$Product_Code = $sqlArray[$i]['Product_Code'];
							$Part_No = $sqlArray[$i]['Part_No'];
							$Part_Name = $sqlArray[$i]['Part_Name'];
							$Supplier_ID = $sqlArray[$i]['Supplier_ID'];
							$Package_ID = $sqlArray[$i]['Package_ID'];
							$Pallet_Type = $sqlArray[$i]['Pallet_Type'];
							$Width_Pallet_Size = $sqlArray[$i]['Width_Pallet_Size'];
							$Length_Pallet_Size = $sqlArray[$i]['Length_Pallet_Size'];
							$Height_Pallet_Size = $sqlArray[$i]['Height_Pallet_Size'];
							$CBM_Per_Pkg = $sqlArray[$i]['CBM_Per_Pkg'];
							$SNP_Per_Pallet = $sqlArray[$i]['SNP_Per_Pallet'];
							$Mass_Per_Pcs = $sqlArray[$i]['Mass_Per_Pcs'];
							$Mass_Per_Pallet = $sqlArray[$i]['Mass_Per_Pallet'];
							$Usage_Pcs_Per_Unit = $sqlArray[$i]['Usage_Pcs_Per_Unit'];
							$Customer_ID = $sqlArray[$i]['Customer_ID'];
							$Created_By_ID = $sqlArray[$i]['Created_By_ID'];
							$Creation_Date = $sqlArray[$i]['Creation_Date'];
							$Creation_DateTime = $sqlArray[$i]['Creation_DateTime'];

							//exit();
							$sql = "INSERT IGNORE INTO tbl_part_master
							$sqlName
							VALUES (
							$Project,
							$Product_Code,
							$Part_No,
							$Part_Name,
							$Supplier_ID,
							$Package_ID,
							$Pallet_Type,
							$Width_Pallet_Size,
							$Length_Pallet_Size,
							$Height_Pallet_Size,
							$CBM_Per_Pkg,
							$SNP_Per_Pallet,
							$Mass_Per_Pcs,
							$Mass_Per_Pallet,
							$Usage_Pcs_Per_Unit,
							$Customer_ID,
							$Created_By_ID,
							$Creation_Date,
							$Creation_DateTime )
							ON DUPLICATE KEY UPDATE 
							Project = $Project,
							Product_Code = $Product_Code,
							Part_Name = $Part_Name,
							Package_ID = $Package_ID,
							Pallet_Type = $Pallet_Type,
							Width_Pallet_Size = $Width_Pallet_Size,
							Length_Pallet_Size = $Length_Pallet_Size,
							Height_Pallet_Size = $Height_Pallet_Size,
							CBM_Per_Pkg = $CBM_Per_Pkg,
							SNP_Per_Pallet = $SNP_Per_Pallet,
							Mass_Per_Pcs = $Mass_Per_Pcs,
							Mass_Per_Pallet = $Mass_Per_Pallet,
							Usage_Pcs_Per_Unit = $Usage_Pcs_Per_Unit,
							Customer_ID = $Customer_ID,
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
