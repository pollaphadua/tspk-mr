<?php
if (!ob_start("ob_gzhandler"))
	ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName']) || !isset($_SESSION['xxxRole']->{'EditBilling'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'EditBilling'}[0] == 0) {
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

if ($type <= 10)//data
{
	if ($type == 1) {
		$dataParams = array(
			'obj',
			'obj=>truck_Control_No:s:0:0',
			'obj=>pus_No:s:0:0',
			'obj=>Customer_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0)
			closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['truck_Control_No' => $truck_Control_No, 'pus_No' => $pus_No, 'Customer_Code' => $Customer_Code];
		$sql = getData($mysqli, $data);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20)//insert
{
	if ($_SESSION['xxxRole']->{'EditBilling'}[1] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {

	} else if ($type == 12) {

	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30)//update
{
	if ($_SESSION['xxxRole']->{'EditBilling'}[2] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$dataParams = array(
			'obj',
			'obj=>transaction_stop_ID:s:0:1',
			'obj=>qty_actual:i:0:1',
			'obj=>SNP_Per_Pallet:i:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0)
			closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT
			BIN_TO_UUID(Order_ID,TRUE) AS Order_ID
			FROM
				tbl_transaction_stop t1 
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Order_ID = $row['Order_ID'];
			}

			$sql = "SELECT
			Actual_Qty,
			qty_actual,
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN
					ROUND((CEILING($qty_actual / $SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg,3)
				ELSE ROUND(CEILING($qty_actual / $SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
			END AS cbm_actual,
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN ROUND(((tpm.Mass_Per_Pallet*($qty_actual / $SNP_Per_Pallet))/2),3)
				ELSE ROUND((tpm.Mass_Per_Pallet*($qty_actual / $SNP_Per_Pallet)),3)
			END AS WT
			FROM
				tbl_transaction_stop t1 
				INNER JOIN tbl_part_master tpm ON t1.Part_ID = tpm.Part_ID
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$cbm_actual = $row['cbm_actual'];
				$Actual_Qty = $row['Actual_Qty'];
				$qty_actual1 = $row['qty_actual'];
			}

			if ($qty_actual > $Actual_Qty) {
				throw new Exception('ใส่ Qty เกินยอด Plan ของออเดอร์นี้');
			}

			$sql = "SELECT
				Qty
			FROM
				tbl_order
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Qty = $row['Qty'];
			}

			if ($qty_actual > $Qty) {
				throw new Exception('ใส่ Qty เกินยอดตั้งต้นของออเดอร์นี้');
			}

			if ($qty_actual1 == 0) {
				$qty_actual1 = $Actual_Qty;
			}

			if ($qty_actual > 0) {
				$Actual_Qty = $qty_actual;
			}

			

			/* $sql = "UPDATE tbl_order 
			SET 
				Actual_Qty = Actual_Qty-$qty_actual1,
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//echo($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "SELECT
				Qty
			FROM
				tbl_order
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID'
					AND Actual_Qty+$qty_actual > Qty;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ยอดคงเหลือไม่เพียงพอ');
			}

			$sql = "UPDATE tbl_order 
			SET 
				Actual_Qty = Actual_Qty+$qty_actual,
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			} */


			$sql = "UPDATE tbl_transaction_stop
			SET
			qty_actual = $qty_actual,
			cbm_actual = '$cbm_actual',
			Last_Updated_Date = curdate(),
			Last_Updated_DateTime = NOW(), 
			Updated_By_ID = $cBy
			WHERE transaction_stop_ID = uuid_to_bin('$transaction_stop_ID',true);";
			// exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . $sql);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'แก้ไขสำเร็จ');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 22) {

		$dataParams = array(
			'obj',
			'obj=>transaction_stop_ID:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0)
			closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT 
				BIN_TO_UUID(Order_ID,TRUE) AS Order_ID,
				Actual_Qty,
				qty_actual,
				CBM,
				WT
			FROM
				tbl_transaction_stop
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Order_ID = $row['Order_ID'];
				$Actual_Qty = $row['Actual_Qty'];
				$qty_actual = $row['qty_actual'];
			}


			// if ($qty_actual > 0) {
			// 	$Actual_Qty = $qty_actual;
			// }

			$sql = "UPDATE tbl_order 
			SET 
				Actual_Qty = Actual_Qty-$Actual_Qty,
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction_stop
			SET order_status = 'no plan'
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'ยกเลิกสำเร็จ');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 23) {

		$dataParams = array(
			'obj',
			'obj=>transaction_stop_ID:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0)
			closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				BIN_TO_UUID(Order_ID,TRUE) AS Order_ID,
				Actual_Qty,
				qty_actual,
				CBM,
				WT
			FROM
				tbl_transaction_stop
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Order_ID = $row['Order_ID'];
				$Actual_Qty = $row['Actual_Qty'];
				$qty_actual = $row['qty_actual'];
			}


			// if ($qty_actual > 0) {
			// 	$Actual_Qty = $qty_actual;
			// }

			$sql = "SELECT
				Actual_Qty, Qty
			FROM
				tbl_order
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID'
					AND Actual_Qty+$Actual_Qty > Qty;";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ยอดคงเหลือไม่เพียงพอ');
			}

			$sql = "UPDATE tbl_order 
			SET 
				Actual_Qty = Actual_Qty+$Actual_Qty,
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "UPDATE tbl_transaction_stop
			SET order_status = 'plan'
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถยกเลิกได้' . __LINE__);
			}

			$mysqli->commit();
			closeDBT($mysqli, 1, 'ยกเลิกสำเร็จ');
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40)//delete
{
	if ($_SESSION['xxxRole']->{'EditBilling'}[3] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50)//save
{
	if ($_SESSION['xxxRole']->{'EditBilling'}[1] == 0)
		closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

	} else
		closeDBT($mysqli, 2, 'TYPE ERROR');
} else
	closeDBT($mysqli, 2, 'TYPE ERROR');


function getData($mysqli, $data)
{
	$where = [];
	if ($data['truck_Control_No'] != '') {
		$where[0] = "tts.truck_Control_No LIKE '%$data[truck_Control_No]%'";
	}

	if ($data['pus_No'] != '') {
		$where[1] = "ttl.pus_No LIKE '%$data[pus_No]%'";
	}

	$sqlWhere = join(' and ', $where);
	// echo($sqlWhere);
	// exit();

	$Customer_Code = $data['Customer_Code'];

	$sql = "SELECT 
		BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID
	FROM 
		tbl_customer_master 
	WHERE 
		Customer_Code = '$Customer_Code';";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	$Customer_ID = $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];

	$sqlWhere1 = "tts.Customer_ID = uuid_to_bin('$Customer_ID',true)";

	$sql = "WITH a AS (
    SELECT
        bin_to_uuid(tstop.transaction_stop_ID,true) transaction_stop_ID, 
		0 as `change`,
        tts.tran_status,
        tts.truck_Control_No,
        SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
        DATE_FORMAT(tts.truckNo_Date, '%d-%m-%Y') AS truckNo_Date,
        if(trm.route_special = 'Y' OR trm.route_special = 'N', tts.Route_Code, CONCAT(tts.Route_Code,' ', trm.route_special)) as Route_Code,
        DATE_FORMAT(tts.start_Date, '%Y-%m-%d %H:%i') AS start_Date,
        DATE_FORMAT(tts.start_Date, '%H:%i') AS start_time,
        DATE_FORMAT(tts.actual_start_Date, '%Y-%m-%d') AS actual_start_Date,
        DATE_FORMAT(tts.actual_start_Date, '%H:%i') AS actual_start_Time,
        CONCAT(DATE(ttl.pus_Date),
                ' ',
                DATE_FORMAT(ttl.planin_time, '%H:%i')) AS Delivery_time,
        tts.stop_Date,
        tts.trip_Number,
        tts.total_Stop,
        tts.mile_Start,
        tts.mile_End,
        DATE_FORMAT(CONVERT(ttl.planin_time,DATE), '%d-%m-%Y') AS planin_date,
        DATE_FORMAT(ttl.planin_time, '%H:%i') AS planin_time,
        ttl.planout_time,
        tts.Truck_Number,
        tts.Truck_Type,
        tdm.Driver_Name,
        ttl.status,
        ttl.pus_No,
        if(ttl.Status_Pickup = 'DELIVERY','',SUBSTRING(ttl.pus_No, 1, 13)) as pus_No_show,
        DATE_FORMAT(ttl.pus_Date, '%Y-%m-%d') AS pus_Date,
        tsm.Supplier_Name_Short,
        tsm.Supplier_Name,
        ttl.sequence_Stop,
        ttl.Status_Pickup,
        tstop.Refer_ID,
        torder.Part_No,
        torder.Part_Name,
        torder.PO_No,
        tstop.Plan_Qty,
        CASE WHEN ttl.Status_Pickup = 'PICKUP' THEN tstop.CBM
        WHEN ttl.Status_Pickup = 'DELIVERY' THEN 0
        END pre_cbm_plan,
        CASE WHEN ttl.Status_Pickup = 'PICKUP'AND tstop.cbm_actual = 0 AND tstop.order_status = 'plan' THEN tstop.CBM
        WHEN ttl.Status_Pickup = 'PICKUP' AND tstop.cbm_actual > 0 AND tstop.order_status = 'plan' THEN tstop.cbm_actual
        WHEN ttl.Status_Pickup = 'PICKUP' AND tstop.order_status = 'no plan' THEN 0
        WHEN ttl.Status_Pickup = 'DELIVERY' THEN 0
        END pre_cbm_actual,
        CASE WHEN ttl.Status_Pickup = 'PICKUP' THEN tstop.Actual_Qty
        WHEN ttl.Status_Pickup = 'DELIVERY' THEN 0
        END Pre_Actual_Qty,
        CASE WHEN ttl.Status_Pickup = 'PICKUP' AND tstop.qty_actual = 0 AND tstop.order_status = 'plan' THEN tstop.Actual_Qty
        WHEN ttl.Status_Pickup = 'PICKUP' AND tstop.qty_actual > 0 AND tstop.order_status = 'plan' THEN tstop.qty_actual
        WHEN ttl.Status_Pickup = 'PICKUP' AND tstop.order_status = 'no plan' THEN 0
        WHEN ttl.Status_Pickup = 'DELIVERY' THEN 0
        END pre_qty_actual,
        CASE
        WHEN tpm.Group_pallet = 'Y' AND ttl.Status_Pickup = 'PICKUP' AND tstop.order_status = 'plan' THEN ROUND((CEILING(tstop.Actual_Qty) / tpm.SNP_Per_Pallet) / 2,2)
        WHEN ttl.Status_Pickup = 'PICKUP' AND tstop.order_status = 'plan' THEN FORMAT(CEILING(tstop.Actual_Qty / tpm.SNP_Per_Pallet), 0)
        WHEN ttl.Status_Pickup = 'DELIVERY' THEN 0
        ELSE 0
        END AS Package_Qty,
        tstop.order_status,
        tstop.SNP_Per_Pallet,
        tpm.Project,
        tpack.Packaging,
        tpack.Package_Type,
        tdm.Phone,
        (SELECT 
                user_fName
            FROM
                tbl_user
            WHERE
                user_id = ttl.Created_By_ID) AS Created_By_ID,
        DATE_FORMAT(ttl.Creation_DateTime, '%d-%m-%Y %H:%i:%s') AS Creation_DateTime,
        DATE_FORMAT(ttl.Creation_DateTime, '%d-%m-%Y') AS Creation_Date,
        DATE_FORMAT(ttl.Creation_DateTime, '%H:%i:%s') AS Creation_Time,
        (SELECT 
                user_fName
            FROM
                tbl_user
            WHERE
                user_id = ttl.Updated_By_ID) AS Updated_By_ID,
        DATE_FORMAT(ttl.Last_Updated_DateTime, '%d-%m-%Y %H:%i:%s') AS Last_Updated_DateTime,
        DATE_FORMAT(ttl.Last_Updated_DateTime, '%d-%m-%Y') AS Last_Updated_Date,
        DATE_FORMAT(ttl.Last_Updated_DateTime, '%H:%i:%s') AS Last_Updated_Time,
        t1.Customer_Code,
        trm.route_special
    FROM
        tbl_transaction tts
            INNER JOIN
        tbl_customer_master t1 ON tts.Customer_ID = t1.Customer_ID
            LEFT JOIN
        tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
            LEFT JOIN
        tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
            LEFT JOIN
        tbl_order torder ON tstop.Order_ID = torder.Order_ID
            LEFT JOIN
        tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
            LEFT JOIN
        tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
            LEFT JOIN
        tbl_package_master tpack ON tstop.Package_ID = tpack.Package_ID
            LEFT JOIN
        tbl_part_master tpm ON tstop.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
            LEFT JOIN
        tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
    WHERE
        ttl.status != 'PENDING'
        AND ttl.status != 'CANCEL'
        AND ttl.Pick != 'N'
        AND ($sqlWhere1)
		AND $sqlWhere
    ORDER BY tts.truckNo_Date ASC, t1.Customer_Code, tts.truck_Control_No ASC, ttl.sequence_Stop ASC, ttl.pus_No, torder.PO_No, tpm.Part_No)
    SELECT *, 
    if(Status_Pickup = 'PICKUP', convert(pre_cbm_plan, CHAR), convert(SUM(pre_cbm_plan) OVER (PARTITION BY truck_Control_No ORDER BY truckNo_Date ASC, Customer_Code, truck_Control_No ASC, sequence_Stop ASC, pus_No, PO_No, Part_No), CHAR) ) CBM,
    if(Status_Pickup = 'PICKUP', convert(pre_cbm_actual, CHAR), convert(SUM(pre_cbm_actual) OVER (PARTITION BY truck_Control_No ORDER BY truckNo_Date ASC, Customer_Code, truck_Control_No ASC, sequence_Stop ASC, pus_No, PO_No, Part_No), CHAR) ) cbm_actual, 
    convert(pre_cbm_plan-pre_cbm_actual, CHAR) diff_cbm, 
    if(Status_Pickup = 'PICKUP', Pre_Actual_Qty, SUM(Pre_Actual_Qty) OVER (PARTITION BY truck_Control_No ORDER BY truckNo_Date ASC, Customer_Code, truck_Control_No ASC, sequence_Stop ASC, pus_No, PO_No, Part_No)) Actual_Qty, 
    if(Status_Pickup = 'PICKUP', pre_qty_actual, SUM(pre_qty_actual) OVER (PARTITION BY truck_Control_No ORDER BY truckNo_Date ASC, Customer_Code, truck_Control_No ASC, sequence_Stop ASC, pus_No, PO_No, Part_No)) qty_actual,
    if(Status_Pickup = 'PICKUP', Pre_Actual_Qty-pre_qty_actual, SUM(Pre_Actual_Qty-pre_qty_actual) OVER (PARTITION BY truck_Control_No ORDER BY truckNo_Date ASC, Customer_Code, truck_Control_No ASC, sequence_Stop ASC, pus_No, PO_No, Part_No)) diff_qty
    FROM a;";
	// exit($sql);
	return $sql;
}

$mysqli->close();
exit();
?>