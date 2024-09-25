<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'TruckMonitor'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'TruckMonitor'}[0] == 0) {
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


include('../php/connection.php');
include('../common/common.php');


$entry_project = $_SESSION['xxxEntryProject'];


if ($type <= 10) //data
{
	if ($type == 1) {

		$dataParams = array(
			'obj',
			'obj=>Customer_Code:s:0:0'
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
		} else {
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
		}

		$sql = "SELECT 
			ROW_NUMBER() OVER (PARTITION BY tts.transaction_ID ORDER BY tts.truckNo_Date DESC, tts.truck_Control_No ASC, ttl.sequence_Stop, ttl.pus_No) AS Row_No,
			BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
			tts.truck_Control_No,
			SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
			tts.truckNo_Date,
			-- tts.Route_Code,
			if(trm.route_special = 'Y' OR trm.route_special = 'N', tts.Route_Code, CONCAT(tts.Route_Code,' ', trm.route_special)) as Route_Code,
			DATE_FORMAT(tts.start_Date, '%Y-%m-%d') AS start_Date,
			DATE_FORMAT(tts.start_Date, '%H:%i') AS start_time,
			DATE_FORMAT(tts.actual_start_Date, '%Y-%m-%d') AS actual_start_Date,
			DATE_FORMAT(tts.actual_start_Date, '%H:%i') AS actual_start_Time,
			tts.stop_Date,
			tts.trip_Number,
			tts.total_Stop,
			tts.mile_Start,
			tts.mile_End,
			tts.Truck_Number,
			CONCAT(tts.Truck_Number, ' | ', tts.Truck_Type) AS Truck,
			tts.Truck_Type,
			tdm.Driver_Name,
			BIN_TO_UUID(tts.truck_ID, TRUE) AS truck_ID,
			BIN_TO_UUID(tts.Driver_ID, TRUE) AS Driver_ID,
			tts.tran_status,
			BIN_TO_UUID(ttl.transaction_Line_ID, TRUE) AS transaction_Line_ID,
			BIN_TO_UUID(ttl.Route_ID, TRUE) AS Route_ID,
			ttl.pus_No,
			if(ttl.Status_Pickup = 'DELIVERY','',SUBSTRING(ttl.pus_No, 1, 13)) as pus_No_show,
			ttl.pus_Date,
			BIN_TO_UUID(ttl.Supplier_ID, TRUE) AS Supplier_ID,
			tsm.Supplier_Name_Short,
			tsm.Supplier_Name,
			ttl.sequence_Stop,
			DATE_FORMAT(ttl.planin_time, '%H:%i') AS planin_time,
			DATE_FORMAT(ttl.planout_time, '%H:%i') AS planout_time,
			DATE_FORMAT(ttl.return_planin_time, '%H:%i') AS return_planin_time,
			DATE_FORMAT(ttl.return_planout_time, '%H:%i') AS return_planout_time,
			DATE_FORMAT(ttl.load_unload_time, '%H:%i') AS load_unload_time,
			DATE_FORMAT(ttl.actual_in_time, '%H:%i') AS actual_in_time,
			DATE_FORMAT(ttl.actual_out_time, '%H:%i') AS actual_out_time,
			DATE_FORMAT(ttl.start_load_time, '%H:%i') AS start_load_time,
			DATE_FORMAT(ttl.end_load_time, '%H:%i') AS end_load_time,
			ttl.mile,
			ttl.Remark,
			ttl.seal1,
			ttl.seal2,
			ttl.seal3,
			ttl.status,
			(SELECT user_fName FROM tbl_user WHERE user_id = ttl.Created_By_ID) AS Created_By_ID,
			ttl.Creation_DateTime,
			(SELECT user_fName FROM tbl_user WHERE user_id = ttl.Updated_By_ID) AS Updated_By_ID,
			ttl.Last_Updated_DateTime,
			ttl.gps_updateDatetime,
			ttl.gps_connection,
			ttl.gps_datetime_connect,
            trm.route_special,
			amount_truck
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				INNER JOIN
			tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
				INNER JOIN
			tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
		WHERE
			tts.tran_status != 'PENDING'
				AND tts.tran_status != 'CANCEL'
				AND ttl.status != 'CANCEL'
				AND tts.tran_status != 'COMPLETE'
				AND ttl.Pick != 'N'
				AND tsm.Status = 'ACTIVE'
				AND ($sqlWhere)
		ORDER BY tts.truckNo_Date DESC, tts.truck_Control_No ASC, ttl.sequence_Stop, ttl.pus_No;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 2) {
		$dataParams = array(
			'obj',
			'obj=>truck_Control_No:s:0:0',
			'obj=>pus_No:s:0:0',
			'obj=>Customer_Code:s:0:0'
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$where = [];
		if ($pus_No == '' && $truck_Control_No == '') {
			$where[] = "AND tts.tran_status != 'COMPLETE' AND tts.tran_status != 'CANCEL' AND ttl.status != 'CANCEL'";
		} else if ($truck_Control_No != '' && $pus_No == '') {
			$truck_Control_No = $truck_Control_No;
			$where[] = "AND truck_Control_No LIKE '%$truck_Control_No%'";
		} else if ($truck_Control_No == '' && $pus_No != '') {
			$pus_No = $pus_No;
			$where[] = "AND pus_No LIKE '%$pus_No%'";
		} else if ($truck_Control_No != '' && $pus_No != '') {
			$truck_Control_No = $truck_Control_No;
			$pus_No = $pus_No;
			$where[] = "AND truck_Control_No LIKE '%$truck_Control_No%' AND pus_No LIKE '%$pus_No%'";
		}
		$sqlWhere = join(' AND ', $where);


		if ($Customer_Code != '') {
			$sql = "SELECT 
				BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
			FROM 
				tbl_customer_master 
			WHERE 
				Customer_Code = '$Customer_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];

			$sqlWhere1 = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";
		} else {
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
				$sqlWhere1 = join(' OR ', $where);
			}
		}

		$sql = "SELECT 
			ROW_NUMBER() OVER (PARTITION BY tts.transaction_ID ORDER BY tts.truckNo_Date DESC, tts.truck_Control_No ASC, ttl.sequence_Stop, ttl.pus_No) AS Row_No,
			BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
			tts.truck_Control_No,
			SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
			tts.truckNo_Date,
			-- tts.Route_Code,
			if(trm.route_special = 'Y' OR trm.route_special = 'N', tts.Route_Code, CONCAT(tts.Route_Code,' ', trm.route_special)) as Route_Code,
			DATE_FORMAT(tts.start_Date, '%Y-%m-%d') AS start_Date,
			DATE_FORMAT(tts.start_Date, '%H:%i') AS start_time,
			DATE_FORMAT(tts.actual_start_Date, '%Y-%m-%d') AS actual_start_Date,
			DATE_FORMAT(tts.actual_start_Date, '%H:%i') AS actual_start_Time,
			tts.stop_Date,
			tts.trip_Number,
			tts.total_Stop,
			tts.mile_Start,
			tts.mile_End,
			tts.Truck_Number,
			CONCAT(tts.Truck_Number, ' | ', tts.Truck_Type) AS Truck,
			tts.Truck_Type,
			tdm.Driver_Name,
			BIN_TO_UUID(tts.truck_ID, TRUE) AS truck_ID,
			BIN_TO_UUID(tts.Driver_ID, TRUE) AS Driver_ID,
			tts.tran_status,
			BIN_TO_UUID(ttl.transaction_Line_ID, TRUE) AS transaction_Line_ID,
			BIN_TO_UUID(ttl.Route_ID, TRUE) AS Route_ID,
			ttl.pus_No,
			if(ttl.Status_Pickup = 'DELIVERY','',SUBSTRING(ttl.pus_No, 1, 13)) as pus_No_show,
			ttl.pus_Date,
			BIN_TO_UUID(ttl.Supplier_ID, TRUE) AS Supplier_ID,
			tsm.Supplier_Name_Short,
			tsm.Supplier_Name,
			ttl.sequence_Stop,
			DATE_FORMAT(ttl.planin_time, '%H:%i') AS planin_time,
			DATE_FORMAT(ttl.planout_time, '%H:%i') AS planout_time,
			DATE_FORMAT(ttl.return_planin_time, '%H:%i') AS return_planin_time,
			DATE_FORMAT(ttl.return_planout_time, '%H:%i') AS return_planout_time,
			DATE_FORMAT(ttl.load_unload_time, '%H:%i') AS load_unload_time,
			DATE_FORMAT(ttl.actual_in_time, '%H:%i') AS actual_in_time,
			DATE_FORMAT(ttl.actual_out_time, '%H:%i') AS actual_out_time,
			DATE_FORMAT(ttl.start_load_time, '%H:%i') AS start_load_time,
			DATE_FORMAT(ttl.end_load_time, '%H:%i') AS end_load_time,
			ttl.mile,
			ttl.Remark,
			ttl.seal1,
			ttl.seal2,
			ttl.seal3,
			ttl.status,
			(SELECT user_fName FROM tbl_user WHERE user_id = ttl.Created_By_ID) AS Created_By_ID,
			ttl.Creation_DateTime,
			(SELECT user_fName FROM tbl_user WHERE user_id = ttl.Updated_By_ID) AS Updated_By_ID,
			ttl.Last_Updated_DateTime,
			ttl.gps_updateDatetime,
			ttl.gps_connection,
			ttl.gps_datetime_connect,
            trm.route_special,
			amount_truck
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				INNER JOIN
			tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
				INNER JOIN
			tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
		WHERE
			tts.tran_status != 'PENDING'
				AND ttl.Pick != 'N'
				$sqlWhere
				AND ($sqlWhere1)
			ORDER BY tts.truckNo_Date DESC, tts.truck_Control_No ASC, ttl.sequence_Stop, ttl.pus_No;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 6) {

		$val = checkTXT($mysqli, $_GET['filter']['value']);
		if (strlen(trim($val)) == 0) {
			echo "[]";
		}

		$sql = "SELECT 
			truck_Control_No AS value
		FROM
			tbl_transaction
		WHERE
			truck_Control_No LIKE '%$val%'
				AND tran_status = 'CANCEL'
		GROUP BY truck_Control_No
		ORDER BY truck_Control_No DESC
		LIMIT 5;";

		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else if ($type == 9) {

		$val = checkTXT($mysqli, $_GET['filter']['value']);
		if (strlen(trim($val)) == 0) {
			echo "[]";
		}

		$sql = "SELECT 
			truck_Control_No AS value
		FROM
			tbl_transaction
		WHERE
			truck_Control_No LIKE '%$val%'
				AND tran_status != 'PENDING'
		GROUP BY truck_Control_No
		LIMIT 5;";

		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else if ($type == 10) {

		$val = checkTXT($mysqli, $_GET['filter']['value']);
		if (strlen(trim($val)) == 0) {
			echo "[]";
		}

		$sql = "SELECT 
			pus_No AS value
		FROM
			tbl_transaction_line
		WHERE
			pus_No LIKE '%$val%'
				AND tran_status != 'PENDING'
		GROUP BY pus_No
		LIMIT 5;";

		if ($re1 = $mysqli->query($sql)) {
			echo json_encode(jsonRow($re1, false, 0));
		} else {
			echo "[{ID:0,value:'ERROR'}]";
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'TruckMonitor'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'TruckMonitor'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
			'obj=>pus_No:s:0:1',
			//'obj=>mile_End:i:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID

			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction 
			SET 
				actual_start_Date = null,
				mile_Start = 0,
				tran_status = 'PENDING',
				edit_status = 'Y',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				mile = 0,
				start_load_time = null,
				end_load_time = null,
				Remark = null,
				seal1 = null,
				seal2 = null,
				seal3 = null,
				status = 'PENDING',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
			FROM 
				tbl_truck_check 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$sql = "UPDATE tbl_truck_check 
				SET 
					Status = 'IN-TRANSIT',
					Last_Updated_DateTime = NOW(),
					Updated_By_ID = $cBy
				WHERE
					BIN_TO_UUID(transaction_ID, TRUE) = '$transaction_ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
				}
			}

			$mysqli->commit();


			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 22) {
		$dataParams = array(
			'obj',
			'obj=>truck_Control_No:s:0:1',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT 
				BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
			FROM 
				tbl_customer_master 
			WHERE 
				Customer_Code = '$Customer_Code';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];

			$sqlWhere = "Customer_ID = uuid_to_bin('$Customer_ID',true)";

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID

			FROM 
				tbl_transaction
			WHERE 
				truck_Control_No LIKE '%$truck_Control_No%'
					AND ($sqlWhere)
					AND tran_status = 'CANCEL';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล Truck Control No. นี้ <br> หรือไม่อยู่ในสถานะ Cancel');
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_ID = $row['transaction_ID'];
			}

			$sql = "UPDATE tbl_transaction_stop t1,
			(SELECT 
				t2.transaction_Line_ID
			FROM 
				tbl_transaction_stop t2 
					INNER JOIN
				tbl_transaction_line t3 on t2.transaction_Line_ID = t3.transaction_Line_ID
					INNER JOIN
				tbl_transaction t4 on t3.transaction_ID = t4. transaction_ID
			where 
				BIN_TO_UUID(t4.transaction_ID,TRUE) = '$transaction_ID') as order_stop
			set t1.status = 'COMPLETE',
				t1.Last_Updated_Date = curdate(),
				t1.Last_Updated_DateTime = now(),
				t1.Updated_By_ID = $cBy
			where t1.transaction_Line_ID = order_stop.transaction_Line_ID;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {

				$sql = "UPDATE tbl_transaction_line 
				SET 
					status = 'PLANNING',
					Last_Updated_Date = curdate(),
					Last_Updated_DateTime = now(),
					Updated_By_ID = $cBy
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
				}

				$sql = "UPDATE tbl_transaction
				SET 
					tran_status = 'PLANNING',
					Last_Updated_Date = curdate(),
					Last_Updated_DateTime = now(),
					Updated_By_ID = $cBy
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			} else {
				//throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				$sql = "UPDATE tbl_order t1,
				(SELECT 
					t2.Order_ID,
					t2.Actual_Qty 
				FROM 
					tbl_transaction_stop t2 
						INNER JOIN
					tbl_transaction_line t3 on t2.transaction_Line_ID = t3.transaction_Line_ID
						INNER JOIN
					tbl_transaction t4 on t3.transaction_ID = t4. transaction_ID
				where 
					BIN_TO_UUID(t4.transaction_ID,TRUE) = '$transaction_ID') as order_stop
				set t1.Actual_Qty = t1.Actual_Qty+order_stop.Actual_Qty,
					t1.Last_Updated_DateTime = now(),
					t1.Updated_By_ID = $cBy
				where t1.Order_ID = order_stop.Order_ID;";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}

				$sql = "UPDATE tbl_transaction_line t1,
				(
					SELECT 
						t3.transaction_Line_ID,
						t3.pus_No,
						t3.actual_in_time,
						t3.actual_out_time,
						CASE
						WHEN t3.actual_in_time is null AND actual_out_time is null THEN 'PLANNING'
						WHEN t3.actual_in_time is not null AND actual_out_time is null THEN 'IN-TRANSIT'
						WHEN t3.actual_in_time is not null AND actual_out_time is not null THEN 'COMPLETE'
						ELSE 'COMPLETE'
					END as return_status
					FROM 
						tbl_transaction_line t3
							INNER JOIN
						tbl_transaction t4 on t3.transaction_ID = t4. transaction_ID
					where 
						BIN_TO_UUID(t4.transaction_ID,TRUE) = '$transaction_ID'
				) AS ttl
				SET 
					t1.status = ttl.return_status,
					t1.Last_Updated_Date = curdate(),
					t1.Last_Updated_DateTime = now(),
					t1.Updated_By_ID = $cBy
				WHERE 
					t1.transaction_Line_ID = ttl.transaction_Line_ID;";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}

				$sql = "UPDATE tbl_transaction
				SET 
					tran_status = 'PLANNING',
					Last_Updated_Date = curdate(),
					Last_Updated_DateTime = now(),
					Updated_By_ID = $cBy
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
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
	if ($_SESSION['xxxRole']->{'TruckMonitor'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
			'obj=>pus_No:s:0:1',
			//'obj=>mile_End:i:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID

			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction 
			SET 
				tran_status = 'CANCEL',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				status = 'CANCEL',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "UPDATE tbl_transaction_stop tstop, (
			SELECT 
				transaction_stop_ID,
				Refer_ID
			FROM 
				tbl_transaction tts
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
					INNER JOIN
				tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
			WHERE 
			BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
				) AS tstop1
			SET 
				tstop.status = 'CANCEL',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				tstop.transaction_stop_ID = tstop1.transaction_stop_ID
					AND tstop.Refer_ID = tstop1.Refer_ID;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_order torder, (
			SELECT 
				Order_ID,
				Refer_ID,
				Actual_Qty
			FROM 
				tbl_transaction tts
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
					INNER JOIN
				tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
			WHERE 
				BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
				) AS tstop1
			SET 
				torder.Actual_Qty = torder.Actual_Qty-tstop1.Actual_Qty,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				torder.Order_ID = tstop1.Order_ID;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "UPDATE tbl_order torder,
			(
				SELECT 
					Order_ID
				FROM 
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
						INNER JOIN
					tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
				WHERE BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
			) AS tts1
			SET 
				torder.Pick = '',
				torder.Last_Updated_DateTime = NOW(),
				torder.Updated_By_ID = $cBy
			WHERE
				torder.Order_ID = tts1.Order_ID
					AND torder.Actual_Qty = 0;";



			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
			FROM 
				tbl_truck_check 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$sql = "DELETE FROM tbl_truck_check 
				WHERE
					BIN_TO_UUID(transaction_ID, TRUE) = '$transaction_ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถลบข้อมูลได้');
				}
			}

			$sp_trans = "CALL SP_Transaction_Save('CANCEL', '$truck_Control_No', '', '$cBy');";
			$re1 = sqlError($mysqli, __LINE__, $sp_trans, 1);
			if (!$re1) {
				throw new Exception('ERROR, SP');
			} else {
				$row = $re1->fetch_array(MYSQLI_NUM);
				$sp_status = $row[0];
				$sp_ms = $row[1];
				if ($sp_status == '0') {
					throw new Exception($sp_ms);
				} else {
				}
			}
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
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
	if ($_SESSION['xxxRole']->{'TruckMonitor'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>actual_start_Time:s:0:0',
			'obj=>mile_Start:i:0:0',
			'obj=>Driver_Name:s:0:1',
			'obj=>Truck:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
				truck_Control_No,
				tran_status
			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
				AND tran_status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Truck Control No. นี้<br>มีการบันทึกการเดินรถเรียบร้อยแล้ว');
			}

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
				BIN_TO_UUID(Truck_ID,TRUE) AS Old_Truck_ID,
				DATE_FORMAT(start_Date, '%Y-%m-%d') AS start_Date,
				DATE_FORMAT(start_Date, '%H:%i') AS start_time,
				truckNo_Date
			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$start_Date = $row['start_Date'];
				$start_time = $row['start_time'];
				$truckNo_Date = $row['truckNo_Date'];
				$Old_Truck_ID = $row['Old_Truck_ID'];
			}

			$Driver_ID = getDriverID($mysqli, $Driver_Name);

			$explode = explode(' | ', $Truck);
			$Truck_Number = $explode[0];
			$Truck_Type = $explode[1];

			$Truck_ID = getTruckID($mysqli, $Truck_Number, $Truck_Type);

			if ($actual_start_Time == null) {
				//exit('1');
				$actual_start_Time = '00:00';
			}


			$sql = "UPDATE tbl_transaction 
			SET 
				actual_start_Date = CONCAT(DATE('$truckNo_Date'),' ',TIME('$actual_start_Time')),
				mile_Start = $mile_Start,
				Driver_ID = UUID_TO_BIN('$Driver_ID',TRUE),
				Truck_ID = UUID_TO_BIN('$Truck_ID',TRUE),
				Truck_Number = '$Truck_Number',
				Truck_Type = '$Truck_Type',
				tran_status = 'IN-TRANSIT',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				status = 'IN-TRANSIT',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			if ($Old_Truck_ID != $Truck_ID) {
				$sql = "SELECT 
					BIN_TO_UUID(Truck_Check_ID,TRUE) AS Truck_Check_ID,
					BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
					BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
				FROM 
					tbl_truck_check
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
						AND BIN_TO_UUID(Truck_ID,TRUE) = '$Old_Truck_ID'
				LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$Truck_Check_ID = $row['Truck_Check_ID'];
					}

					$sql = "SELECT 
						BIN_TO_UUID(Truck_Check_ID,TRUE) AS Truck_Check_ID
					FROM 
						tbl_truck_check
					WHERE 
						BIN_TO_UUID(Truck_ID,TRUE) = '$Truck_ID'
							AND Status = 'IN-TRANSIT'
					LIMIT 1;";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						$sql = "SELECT 
							Truck_Number,
							ST_AsText(geo) AS TruckLocation
						FROM 
							tbl_truck_master
						WHERE 
							BIN_TO_UUID(Truck_ID,TRUE) = '$Old_Truck_ID';";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows == 0) {
							throw new Exception('ไม่พบข้อมูล ' . __LINE__);
						}
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$TruckLocation = $row['TruckLocation'];
						}

						$sql = "UPDATE tbl_truck_master 
						SET 
							geo = ST_GeomFromText('$TruckLocation'),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy
						WHERE
							BIN_TO_UUID(Truck_ID,TRUE) = '$Truck_ID';";
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}

						$sql = "UPDATE tbl_truck_master 
						SET 
							geo = ST_GeomFromText('POINT(13.030953 101.136099)'),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy
						WHERE
							BIN_TO_UUID(Truck_ID,TRUE) = '$Old_Truck_ID';";
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}


						$sql = "UPDATE tbl_truck_check 
						SET 
							Truck_ID = UUID_TO_BIN('$Truck_ID',TRUE),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy
						WHERE 
						BIN_TO_UUID(Truck_Check_ID,TRUE) = '$Truck_Check_ID';";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}
					} else {
						throw new Exception('ไม่สามารถเปลี่ยนได้<br>เนื่องจากขณะนี้<br>รถคันนี้วิ่งอยู่ใน Route อื่น');
					}
				}
			}

			$sp_trans = "CALL SP_Transaction_Save('IN-TRANSIT', '$truck_Control_No', '', '$cBy');";
			$re1 = sqlError($mysqli, __LINE__, $sp_trans, 1);
			if (!$re1) {
				throw new Exception('ERROR, SP');
			} else {
				$row = $re1->fetch_array(MYSQLI_NUM);
				$sp_status = $row[0];
				$sp_ms = $row[1];
				if ($sp_status == '0') {
					throw new Exception($sp_ms);
				} else {
				}
			}
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$mysqli->commit();


			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 42) {
		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>actual_start_Time:s:0:0',
			'obj=>mile_Start:i:0:0',
			'obj=>Driver_Name:s:0:1',
			'obj=>Truck:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
				BIN_TO_UUID(Truck_ID,TRUE) AS Old_Truck_ID,
				DATE_FORMAT(start_Date, '%Y-%m-%d') AS start_Date,
				DATE_FORMAT(start_Date, '%H:%i') AS start_time,
				truckNo_Date

			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$start_Date = $row['start_Date'];
				$start_time = $row['start_time'];
				$truckNo_Date = $row['truckNo_Date'];
				$Old_Truck_ID = $row['Old_Truck_ID'];
			}

			$Driver_ID = getDriverID($mysqli, $Driver_Name);

			$explode = explode(' | ', $Truck);
			$Truck_Number = $explode[0];
			$Truck_Type = $explode[1];

			$Truck_ID = getTruckID($mysqli, $Truck_Number, $Truck_Type);

			if ($actual_start_Time == null) {
				//exit('1');
				$actual_start_Time = '00:00';
			}

			$sql = "UPDATE tbl_transaction 
			SET 
				actual_start_Date = CONCAT(DATE('$truckNo_Date'),' ',TIME('$actual_start_Time')),
				mile_Start = $mile_Start,
				Driver_ID = UUID_TO_BIN('$Driver_ID',TRUE),
				Truck_ID = UUID_TO_BIN('$Truck_ID',TRUE),
				Truck_Number = '$Truck_Number',
				Truck_Type = '$Truck_Type',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			if ($Old_Truck_ID != $Truck_ID) {
				$sql = "SELECT 
					BIN_TO_UUID(Truck_Check_ID,TRUE) AS Truck_Check_ID,
					BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
					BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
				FROM 
					tbl_truck_check
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
						AND BIN_TO_UUID(Truck_ID,TRUE) = '$Old_Truck_ID'
				LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$Truck_Check_ID = $row['Truck_Check_ID'];
					}

					$sql = "SELECT 
						BIN_TO_UUID(Truck_Check_ID,TRUE) AS Truck_Check_ID
					FROM 
						tbl_truck_check
					WHERE 
						BIN_TO_UUID(Truck_ID,TRUE) = '$Truck_ID'
							AND Status = 'IN-TRANSIT'
					LIMIT 1;";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						$sql = "SELECT 
							Truck_Number,
							ST_AsText(geo) AS TruckLocation
						FROM 
							tbl_truck_master
						WHERE 
							BIN_TO_UUID(Truck_ID,TRUE) = '$Old_Truck_ID';";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows == 0) {
							throw new Exception('ไม่พบข้อมูล ' . __LINE__);
						}
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$TruckLocation = $row['TruckLocation'];
						}

						$sql = "UPDATE tbl_truck_master 
						SET 
							geo = ST_GeomFromText('$TruckLocation'),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy
						WHERE
							BIN_TO_UUID(Truck_ID,TRUE) = '$Truck_ID';";
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}

						$sql = "UPDATE tbl_truck_master 
						SET 
							geo = ST_GeomFromText('POINT(13.030953 101.136099)'),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = $cBy
						WHERE
							BIN_TO_UUID(Truck_ID,TRUE) = '$Old_Truck_ID';";
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}


						$sql = "UPDATE tbl_truck_check 
						SET 
							Truck_ID = UUID_TO_BIN('$Truck_ID',TRUE),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy
						WHERE 
						BIN_TO_UUID(Truck_Check_ID,TRUE) = '$Truck_Check_ID';";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}
					} else {
						throw new Exception('ไม่สามารถเปลี่ยนได้<br>เนื่องจากขณะนี้<br>รถคันนี้วิ่งอยู่ใน Route อื่น');
					}
				}
			}



			$sp_trans = "CALL SP_Transaction_Save('UPDATE_HEAD', '$truck_Control_No', '', '$cBy');";
			$re1 = sqlError($mysqli, __LINE__, $sp_trans, 1);
			if (!$re1) {
				throw new Exception('ERROR, SP');
			} else {
				$row = $re1->fetch_array(MYSQLI_NUM);
				$sp_status = $row[0];
				$sp_ms = $row[1];
				if ($sp_status == '0') {
					throw new Exception($sp_ms);
				} else {
				}
			}
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$mysqli->commit();


			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 43) {
		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
			'obj=>pus_No:s:0:0',
			'obj=>Supplier_Name_Short:s:0:1',
			'obj=>planin_time:s:0:1',
			'obj=>actual_in_time:s:0:1',
			'obj=>planout_time:s:0:1',
			'obj=>actual_out_time:s:0:1',
			'obj=>mile:i:0:0',

			'obj=>start_load_time:s:0:0',
			'obj=>end_load_time:s:0:0',
			'obj=>Remark:s:0:0',
			'obj=>seal1:s:0:0',
			'obj=>seal2:s:0:0',
			'obj=>seal3:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
				pus_No,
				status

			FROM 
				tbl_transaction_line
			WHERE 
			BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID'
				AND status = 'COMPLETE';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('PUS No. นี้<br>มีการบันทึกการเดินรถเรียบร้อยแล้ว');
			}

			$sql = "SELECT 
				Supplier_Name
			FROM
				tbl_supplier_master
			WHERE
				Supplier_Name_Short = '$Supplier_Name_Short'
					AND Status = 'ACTIVE'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}


			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}


			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
				sequence_Stop
			FROM 
				tbl_transaction_line 
			WHERE 
			BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			// while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			// 	$sequence_Stop = $row['sequence_Stop'];
			// }

			if ($start_load_time == null) {
				//exit('1');
				$start_load_time = '00:00';
			}
			if ($end_load_time == null) {
				//exit('1');
				$end_load_time = '00:00';
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				actual_in_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$actual_in_time')),
				actual_out_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$actual_out_time')),
				mile = $mile,
				start_load_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$start_load_time')),
				end_load_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$end_load_time')),
				Remark = '$Remark',
				seal1 = '$seal1',
				seal2 = '$seal2',
				seal3 = '$seal3',
				status = 'COMPLETE',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID, TRUE) AS transaction_Line_ID
			FROM
				tbl_transaction tts
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			WHERE
				BIN_TO_UUID(tts.transaction_ID, TRUE) = '$transaction_ID'
					AND ttl.status = 'IN-TRANSIT'
					AND ttl.Pick != 'N';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				$sql = "UPDATE tbl_transaction 
				SET 
					tran_status = 'COMPLETE',
					Last_Updated_Date = curdate(),
					Last_Updated_DateTime = now(),
					Updated_By_ID = $cBy
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
				}
			}

			$sp_trans = "CALL SP_Transaction_Save('COMPLETE', '$truck_Control_No', '$pus_No', '$cBy');";
			$re1 = sqlError($mysqli, __LINE__, $sp_trans, 1);
			if (!$re1) {
				throw new Exception('ERROR, SP');
			} else {
				$row = $re1->fetch_array(MYSQLI_NUM);
				$sp_status = $row[0];
				$sp_ms = $row[1];
				if ($sp_status == '0') {
					throw new Exception($sp_ms);
				} else {
				}
			}
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$mysqli->commit();


			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 44) {
		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
			'obj=>pus_No:s:0:0',
			'obj=>Supplier_Name_Short:s:0:1',
			'obj=>planin_time:s:0:1',
			'obj=>actual_in_time:s:0:1',
			'obj=>planout_time:s:0:1',
			'obj=>actual_out_time:s:0:1',
			'obj=>mile:i:0:0',

			'obj=>start_load_time:s:0:0',
			'obj=>end_load_time:s:0:0',
			'obj=>Remark:s:0:0',
			'obj=>seal1:s:0:0',
			'obj=>seal2:s:0:0',
			'obj=>seal3:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Supplier_Name
			FROM
				tbl_supplier_master
			WHERE
				Supplier_Name_Short = '$Supplier_Name_Short'
					AND Status = 'ACTIVE'";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}


			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
			FROM 
				tbl_transaction 
			WHERE 
			BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}


			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
				sequence_Stop
			FROM 
				tbl_transaction_line 
			WHERE 
			BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}

			if ($start_load_time == null) {
				//exit('1');
				$start_load_time = '00:00';
			}
			if ($end_load_time == null) {
				//exit('1');
				$end_load_time = '00:00';
			}
			//exit($start_load_time);
			$sql = "UPDATE tbl_transaction_line 
			SET 
				actual_in_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$actual_in_time')),
				actual_out_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$actual_out_time')),
				mile = $mile,
				start_load_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$start_load_time')),
				end_load_time = CONCAT(DATE('$truckNo_Date'),' ',TIME('$end_load_time')),
				Remark = '$Remark',
				seal1 = '$seal1',
				seal2 = '$seal2',
				seal3 = '$seal3',
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sp_trans = "CALL SP_Transaction_Save('UPDATE', '$truck_Control_No', '$pus_No', '$cBy');";
			//exit($sp_trans);
			$re1 = sqlError($mysqli, __LINE__, $sp_trans, 1);
			if (!$re1) {
				throw new Exception('ERROR, SP');
			} else {
				$row = $re1->fetch_array(MYSQLI_NUM);
				$sp_status = $row[0];
				$sp_ms = $row[1];
				if ($sp_status == '0') {
					throw new Exception($sp_ms);
				} else {
				}
			}
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล' . __LINE__);
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');




$mysqli->close();
exit();
