<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'RouteMaster'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'RouteMaster'}[0] == 0) {
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
			BIN_TO_UUID(trm.Route_ID,TRUE) AS Route_ID,
			-- trm.Route_Code,
			if(trm.route_special = 'Y' OR trm.route_special = 'N', trm.Route_Code, CONCAT(trm.Route_Code,' ', trm.route_special)) as Route_Code,
			BIN_TO_UUID(trm.Truck_ID,TRUE) AS Truck_ID,
			ttm.Truck_Number,
			trm.Truck_Type,
			CONCAT(ttm.Truck_Number, ' | ', ttm.Truck_Type) AS Truck,
			trm.Delivery_Method,
			BIN_TO_UUID(trm.Supplier_ID,TRUE) AS Supplier_ID,
			CONCAT(tsm.Supplier_Name_Short, ' | ', tsm.Supplier_Name_Short) AS Supplier,
			tsm.Supplier_Name_Short,
			tsm.Supplier_Name,
			tsm.Province,
			tsm.Sub_Zone,
			trm.Delivery_Plant,
			trm.Vol,
			trm.Weight,
			DATE_FORMAT(trm.start_time, '%H:%i') AS start_time,
			DATE_FORMAT(trm.planin_time, '%H:%i') AS planin_time,
			DATE_FORMAT(trm.planout_time, '%H:%i') AS planout_time,
			DATE_FORMAT(trm.return_planin_time, '%H:%i') AS return_planin_time,
			DATE_FORMAT(trm.return_planout_time, '%H:%i') AS return_planout_time,
			DATE_FORMAT(trm.load_unload_time, '%H:%i') AS load_unload_time,
			trm.Status_Pickup,
			trm.Distance,
			trm.Add_Day,
			trm.Status,
			trm.Model,
			DATE_FORMAT(trm.Creation_DateTime, '%Y-%m-%d %H:%i') AS Creation_DateTime,
			DATE_FORMAT(trm.Last_Updated_DateTime, '%Y-%m-%d %H:%i') AS Last_Updated_DateTime,
			trm.Created_By_ID,
			t1.Customer_Code
		FROM 
			tbl_route_master trm
				INNER JOIN
			tbl_truck_master ttm ON trm.Truck_ID = ttm.Truck_ID
				INNER JOIN
			tbl_supplier_master tsm ON trm.Supplier_ID = tsm.Supplier_ID
				INNER JOIN 
			tbl_customer_master t1 ON trm.Customer_ID = t1.Customer_ID
			WHERE trm.Status = 'ACTIVE'
				AND ($sqlWhere)
		ORDER BY t1.Customer_Code, trm.Route_Code, trm.Status_Pickup, TIMESTAMP(if(Add_Day = 0, curdate(), DATE_ADD(curdate(), INTERVAL 1 DAY)), planin_time) ASC;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'RouteMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

		//$counter  = $_POST['obj'];
		$dataParams = array(
			'obj',
			'obj=>Route_Code:s:0:1',
			'obj=>Truck:s:0:1',
			'obj=>Delivery_Method:s:0:1',
			'obj=>Supplier:s:0:1',
			'obj=>Delivery_Plant:s:0:1',
			'obj=>Vol:f:0:0',
			'obj=>Weight:f:0:1',
			'obj=>start_time:s:0:0',
			'obj=>planin_time:s:0:1',
			'obj=>planout_time:s:0:1',
			'obj=>return_planin_time:s:0:0',
			'obj=>return_planout_time:s:0:0',
			'obj=>load_unload_time:s:0:1',
			'obj=>Status_Pickup:s:0:1',
			'obj=>Distance:i:0:0',
			'obj=>Add_Day:i:0:0',
			'obj=>Customer_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));


		$mysqli->autocommit(FALSE);
		try {

			$explode = explode(' | ', $Supplier);
			$Supplier_Code = $explode[0];
			$Supplier_Name_Short = $explode[1];

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

			$explode = explode(' | ', $Truck);
			$Truck_Number = $explode[0];
			$Truck_Type = $explode[1];

			$sql = "SELECT 
				Truck_Number,
				BIN_TO_UUID(Truck_ID,TRUE) as Truck_ID,
				Truck_Type
			FROM 
				tbl_truck_master 
			WHERE 
				Truck_Number = '$Truck_Number'
					AND Truck_Type = '$Truck_Type'
					AND Customer_ID = uuid_to_bin('$Customer_ID',TRUE);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Truck_ID = $row['Truck_ID'];
				$Truck_Type = $row['Truck_Type'];
			}


			$sql = "INSERT INTO tbl_route_master (
				Route_Code,
				Truck_ID,
				Truck_Type,
				Delivery_Method,
				Supplier_ID,
				Delivery_Plant,
				Customer_ID,
				Vol,
				Weight,
				start_time,
				planin_time,
				planout_time,
				return_planin_time,
				return_planout_time,
				load_unload_time,
				Status_Pickup,
				Distance,
				Add_Day,
				Creation_Date,
				Creation_DateTime,
				Created_By_ID)
			VALUES(
				'$Route_Code',
				UUID_TO_BIN('$Truck_ID',TRUE),
				'$Truck_Type',
				'$Delivery_Method',
				UUID_TO_BIN('$Supplier_ID',TRUE),
				'$Delivery_Plant',
				UUID_TO_BIN('$Customer_ID',TRUE),
				$Vol,
				$Weight,
				if('$start_time' = '',null, '$start_time'),
				'$planin_time',
				'$planout_time',
				if('$return_planin_time' = '',null, '$return_planin_time'),
				if('$return_planout_time' = '',null, '$return_planout_time'),
				'$load_unload_time',
				'$Status_Pickup',
				$Distance,
				$Add_Day,
				curdate(),
				now(),
				$cBy);";
			//exit($sql);
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
	if ($_SESSION['xxxRole']->{'RouteMaster'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>Route_ID:s:0:1',
			'obj=>Route_Code:s:0:1',
			'obj=>Truck:s:0:1',
			'obj=>Delivery_Method:s:0:1',
			'obj=>Supplier:s:0:1',
			'obj=>Delivery_Plant:s:0:1',
			'obj=>Vol:f:0:0',
			'obj=>Weight:f:0:1',
			'obj=>start_time:s:0:0',
			'obj=>planin_time:s:0:1',
			'obj=>planout_time:s:0:1',
			'obj=>return_planin_time:s:0:0',
			'obj=>return_planout_time:s:0:0',
			'obj=>load_unload_time:s:0:1',
			'obj=>Status_Pickup:s:0:1',
			'obj=>Distance:i:0:0',
			'obj=>Add_Day:i:0:0',
			'obj=>Status:s:0:0',
			'obj=>Customer_Code:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$explode = explode(' | ', $Supplier);
			$Supplier_Code = $explode[0];
			$Supplier_Name_Short = $explode[1];

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

			$explode = explode(' | ', $Truck);
			$Truck_Number = $explode[0];
			$Truck_Type = $explode[1];

			$sql = "SELECT 
				Truck_Number,
				BIN_TO_UUID(Truck_ID,TRUE) as Truck_ID,
				Truck_Type
			FROM 
				tbl_truck_master 
			WHERE 
				Truck_Number = '$Truck_Number'
					AND Truck_Type = '$Truck_Type'
					AND Customer_ID = uuid_to_bin('$Customer_ID',TRUE);";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Truck_ID = $row['Truck_ID'];
				$Truck_Type = $row['Truck_Type'];
			}

			$sql = "UPDATE tbl_route_master 
			SET 
			Route_Code = '$Route_Code',
			Truck_ID = UUID_TO_BIN('$Truck_ID',TRUE),
			Truck_Type = '$Truck_Type',
			Delivery_Method = '$Delivery_Method',
			Supplier_ID = UUID_TO_BIN('$Supplier_ID',TRUE),
			Delivery_Plant = '$Delivery_Plant',
			Customer_ID = UUID_TO_BIN('$Customer_ID',TRUE),
			Vol = $Vol,
			Weight = $Weight,
			start_time = if('$start_time' = '',null, '$start_time'),
			planin_time = '$planin_time',
			planout_time = '$planout_time',
			return_planin_time = if('$return_planin_time' = '',null, '$return_planin_time'),
			return_planout_time = if('$return_planout_time' = '',null, '$return_planout_time'),
			load_unload_time = '$load_unload_time',
			Status_Pickup = '$Status_Pickup',
			Distance = '$Distance',
			Add_Day = '$Add_Day',
			Status = '$Status',
			Last_Updated_Date = curdate(),
			Last_Updated_DateTime = now(),
			Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(Route_ID,TRUE) = '$Route_ID';";
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
	if ($_SESSION['xxxRole']->{'RouteMaster'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
		$obj  = $_POST['obj'];
		$explode = explode("/", $obj);
		$Route_Pre_ID  = $explode[0];


		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Supplier_Name_Short,
				BIN_TO_UUID(Route_Pre_ID,TRUE) as Route_Pre_ID,
				BIN_TO_UUID(Route_Header_ID,TRUE) as Route_Header_ID
			FROM 
				tbl_route_master_pre 
			WHERE 
				BIN_TO_UUID(Route_Pre_ID,TRUE) = '$Route_Pre_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Route_Header_ID = $row['Route_Header_ID'];
			}


			$sql = "DELETE FROM tbl_route_master_pre
			WHERE 
				BIN_TO_UUID(Route_Header_ID,TRUE) = '$Route_Header_ID'
					AND BIN_TO_UUID(Route_Pre_ID,TRUE) = '$Route_Pre_ID';";
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
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'RouteMaster'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
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

							$Route_Code = $row[1];
							$Truck_Number = $row[2];
							$Truck_Type = $row[3];
							$Delivery_Method = $row[4];
							$Supplier_Name_Short = $row[5];
							$Model = $row[6];
							$Delivery_Plant = $row[9];
							$Vol = RemoveSpecialChar($row[10]);
							$Weight = RemoveSpecialChar($row[11]);
							$start_time = $row[12];
							$planin_time = $row[13];
							$planout_time = $row[14];
							$return_planin_time = $row[15];
							$return_planout_time = $row[16];
							$load_unload_time = $row[17];
							$Status_Pickup = $row[18];
							$Distance = $row[19];
							$Add_Day = $row[20];
							$Customer_Code = $row[21];
							$route_special = $row[22];

							$Customer_ID = getCustomerID($mysqli, $Customer_Code);
							$Supplier_ID = getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID);

							if($Customer_Code != 'TSPK-L'){
								$route_special = 'N';
							}

							$Status_Pickup = str_replace(' ', '', $Status_Pickup);


							$sql = "SELECT 
								BIN_TO_UUID(Truck_ID,true) as Truck_ID,
								Truck_Type
							FROM
								tbl_truck_master
							WHERE 
								Truck_Number = '$Truck_Number'
									AND Truck_Type = '$Truck_Type'
									AND Customer_ID = uuid_to_bin('$Customer_ID',TRUE);";
							//exit($sql);
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows == 0) {
								throw new Exception('ไม่พบ Truck No. ' . $Truck_Number . " " . $Truck_Type);
							}
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$Truck_ID = $row['Truck_ID'];
								$Truck_Type = $row['Truck_Type'];
							}


							$sql = "SELECT 
								BIN_TO_UUID(Supplier_ID,true) as Supplier_ID
							FROM
								tbl_supplier_master
							WHERE 
								Supplier_Name_Short = '$Delivery_Plant';";
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows == 0) {
								throw new Exception('ไม่พบ Delivery Plant');
							}

							$sqlArray[] = array(
								'Route_Code' => stringConvert($Route_Code),
								'Truck_ID' => 'uuid_to_bin("' . $Truck_ID . '",true)',
								'Truck_Type' => stringConvert($Truck_Type),
								'Delivery_Method' => stringConvert($Delivery_Method),
								'Delivery_Plant' => stringConvert($Delivery_Plant),
								'Supplier_ID' => 'uuid_to_bin("' . $Supplier_ID . '",true)',
								'Model' => stringConvert($Model),
								'Customer_ID' => 'uuid_to_bin("' . $Customer_ID . '",true)',
								'Vol' => $Vol,
								'Weight' => $Weight,
								'start_time' => stringConvert($start_time),
								'planin_time' => stringConvert($planin_time),
								'planout_time' => stringConvert($planout_time),
								'return_planin_time' => stringConvert($return_planin_time),
								'return_planout_time' => stringConvert($return_planout_time),
								'load_unload_time' => stringConvert($load_unload_time),
								'Status_Pickup' => stringConvert($Status_Pickup),
								'Distance' => $Distance,
								'Add_Day' => $Add_Day,
								'Created_By_ID' => $cBy,
								'Creation_DateTime' => 'now()',
								'Creation_Date' => 'curdate()',
								'route_special' => stringConvert($route_special),

							);
						} else {
							$count = 1;
						}
					}

					$sql = "SELECT * FROM tbl_route_master WHERE Customer_ID = uuid_to_bin('$Customer_ID',TRUE)";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows > 0) {
						$sql = "UPDATE tbl_route_master 
						SET
							Status = 'INACTIVE',
							Last_Updated_Date = curdate(),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy
						WHERE 
							Customer_ID = uuid_to_bin('$Customer_ID',TRUE);";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถแก้ไขข้อมูลได้');
						}
					}




					$total = 0;
					if (count($sqlArray) > 0) {
						$sqlName = prepareNameInsert($sqlArray[0]);

						for ($i = 0, $len = count($sqlArray); $i < $len; $i++) {

							$Route_Code = $sqlArray[$i]['Route_Code'];
							$Truck_ID = $sqlArray[$i]['Truck_ID'];
							$Truck_Type = $sqlArray[$i]['Truck_Type'];
							$Delivery_Method = $sqlArray[$i]['Delivery_Method'];
							$Delivery_Plant = $sqlArray[$i]['Delivery_Plant'];
							$Supplier_ID = $sqlArray[$i]['Supplier_ID'];
							$Model = $sqlArray[$i]['Model'];
							$Customer_ID = $sqlArray[$i]['Customer_ID'];
							$Vol = $sqlArray[$i]['Vol'];
							$Weight = $sqlArray[$i]['Weight'];
							$start_time = $sqlArray[$i]['start_time'];
							$planin_time = $sqlArray[$i]['planin_time'];
							$planout_time = $sqlArray[$i]['planout_time'];
							$return_planin_time = $sqlArray[$i]['return_planin_time'];
							$return_planout_time = $sqlArray[$i]['return_planout_time'];
							$load_unload_time = $sqlArray[$i]['load_unload_time'];
							$Status_Pickup = $sqlArray[$i]['Status_Pickup'];
							$Distance = $sqlArray[$i]['Distance'];
							$Add_Day = $sqlArray[$i]['Add_Day'];
							$Created_By_ID = $sqlArray[$i]['Created_By_ID'];
							$Creation_Date = $sqlArray[$i]['Creation_Date'];
							$Creation_DateTime = $sqlArray[$i]['Creation_DateTime'];
							$route_special = $sqlArray[$i]['route_special'];

							//exit();
							$sql = "INSERT IGNORE INTO tbl_route_master
							$sqlName
							VALUES (
							$Route_Code,
							$Truck_ID,
							$Truck_Type,
							$Delivery_Method,
							$Delivery_Plant,
							$Supplier_ID,
							$Model,
							$Customer_ID,
							$Vol,
							$Weight,
							$start_time,
							$planin_time,
							$planout_time,
							$return_planin_time,
							$return_planout_time,
							$load_unload_time,
							$Status_Pickup,
							$Distance,
							$Add_Day,
							$Created_By_ID,
							now(),
							now(),
							$route_special )
							ON DUPLICATE KEY UPDATE 
							Truck_ID = $Truck_ID,
							Truck_Type = $Truck_Type,
							Delivery_Method = $Delivery_Method,
							Delivery_Plant = $Delivery_Plant,
							Supplier_ID = $Supplier_ID,
							Model = $Model,
							Customer_ID = $Customer_ID,
							Vol = $Vol,
							Weight = $Weight,
							start_time = $start_time,
							planin_time = $planin_time,
							planout_time = $planout_time,
							return_planin_time = $return_planin_time,
							return_planout_time = $return_planout_time,
							load_unload_time = $load_unload_time,
							Status_Pickup = $Status_Pickup,
							Distance = $Distance,
							Add_Day = $Add_Day,
							Status = 'ACTIVE',
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy,
							route_special = $route_special;";
							// exit($sql);
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

function RemoveSpecialChar($str)
{

	// Using str_replace() function
	// to replace the word
	$res = str_replace(array(
		'\'', '"',
		',', ';', '<', '>'
	), '', $str);

	// Returning the result
	return $res;
}

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

$mysqli->close();
exit();
