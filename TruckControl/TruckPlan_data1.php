<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'TruckPlan'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'TruckPlan'}[0] == 0) {
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

if ($type <= 10) //data
{
	if ($type == 1) {

		$dataParams = array(
			'obj',
			'obj=>Route_Code:s:0:1',
			'obj=>truckNo_Date:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		//echo $Route_Code;

		$explode = explode(" | ", $Route_Code);
		$Route_Code1 = $explode[0];

		$explode1 = explode(" ", $Route_Code1);
		$Route_Code = $explode1[0];

		//exit($Route_Code);

		$supplier = explode(', ', $explode[1]);
		$Customer_Code = end($supplier);

		if ($Customer_Code == 'TSPKK') {
			$Customer_Code = 'TSPK-L';
		}

		$Customer_ID = getCustomerID($mysqli, $Customer_Code);
		$Route_ID = getRouteID($mysqli, $Route_Code, $Customer_ID);

		$sql = "SELECT 
			BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
			Refer_ID,
			Pickup_Date,
			BIN_TO_UUID(torder.Part_ID, TRUE) AS Part_ID,
			torder.Part_No,
			torder.Part_Name,
			tpm.Product_Code,
			Qty,
			Actual_Qty,
			Qty - Actual_Qty AS Sum_Qty,
			tpm.SNP_Per_Pallet,
			tpm.CBM_Per_Pkg,
			tpm.Mass_Per_Pallet,
			(((Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2),
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN
					ROUND((((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3)
				ELSE ROUND((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
			END AS CBM,
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
					THEN ROUND(((tpm.Mass_Per_Pallet*((Qty - Actual_Qty) / tpm.SNP_Per_Pallet))/2),3)
					ELSE ROUND((tpm.Mass_Per_Pallet*((Qty - Actual_Qty)/ tpm.SNP_Per_Pallet)),3)
			END AS WT,
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN
					(CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2
				ELSE CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet
			END AS Package_Qty,
			UM,
			PO_Line,
			PO_No,
			PO_Release,
			tpm.Pallet_Type,
			BIN_TO_UUID(tpm.Package_ID, TRUE) AS Package_ID,
			tpm.Width_Pallet_Size,
			tpm.Length_Pallet_Size,
			tpm.Height_Pallet_Size,
			tpm.Project,
			torder.Supplier_Name_Short,
			BIN_TO_UUID(torder.Supplier_ID, TRUE) AS Supplier_ID,
			torder.Pick,
			DATE_FORMAT(trm.start_time, '%H:%i') AS start_time,
			trm.route_special,
			CONCAT(Width_Pallet_Size,
				'x',
				Length_Pallet_Size,
				'x',
				Height_Pallet_Size) AS Dimansion
		FROM
			tbl_order torder
				INNER JOIN
			tbl_route_master trm ON torder.Supplier_ID = trm.Supplier_ID
				INNER JOIN
			tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
		WHERE
			trm.Route_Code  = '$Route_Code'
				AND trm.Customer_ID = uuid_to_bin('$Customer_ID',true)
				AND Pickup_Date = DATE('$truckNo_Date')
				AND Command != 'DELETE'
				AND (Actual_Qty < Qty)
				AND torder.Qty != 0
				AND trm.Status = 'ACTIVE'
				AND tpm.Active = 'Y'
		ORDER BY Pickup_Date, Width_Pallet_Size,Length_Pallet_Size,Height_Pallet_Size, PO_No, Order_ID;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$header = jsonRow($re1, true, 0);

		$sql = "SELECT 
			tsm.Supplier_Name_Short,
			tsm.Supplier_Name,
			DATE_FORMAT(trm.planin_time, '%H:%i') AS planin_time,
			DATE_FORMAT(trm.planout_time, '%H:%i') AS planout_time,
			DATE_FORMAT(trm.return_planin_time, '%H:%i') AS return_planin_time,
			DATE_FORMAT(trm.return_planout_time, '%H:%i') AS return_planout_time,
			DATE_FORMAT(trm.load_unload_time, '%H:%i') AS load_unload_time,
			trm.Add_Day,
			trm.Status_Pickup
		FROM
			tbl_route_master trm
				INNER JOIN
			tbl_supplier_master tsm ON trm.Supplier_ID = tsm.Supplier_ID
		WHERE
			trm.Route_Code = '$Route_Code'
			AND trm.Customer_ID = uuid_to_bin('$Customer_ID',true)
			AND trm.Status = 'ACTIVE'
		ORDER BY trm.Status_Pickup, TIMESTAMP(if(trm.Add_Day = 0, curdate(), DATE_ADD(curdate(), INTERVAL 1 DAY)), trm.planin_time) ASC;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$body = jsonRow($re1, true, 0);

		$returnData = ['header' => $header, 'body' => $body];
		closeDBT($mysqli, 1, $returnData);
	} else if ($type == 2) {
		$sql = "WITH a AS (
			SELECT 
				BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
				BIN_TO_UUID(tts.truck_ID, TRUE) AS truck_ID,
				BIN_TO_UUID(tts.Driver_ID, TRUE) AS Driver_ID,
				tdm.Driver_Name,
				ttm.Truck_Number,
				tts.Truck_Type,
				CONCAT(ttm.Truck_Number, ' | ', tts.Truck_Type) AS Truck,
				tts.truck_Control_No,
				SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
				tts.truckNo_Date,
				tts.Route_Code AS Route_Code1,
				TIME(tts.start_Date) AS start_time,
				tts.Customer_ID,
                t1.Customer_Code,
				amount_truck
			FROM
				tbl_transaction tts
					LEFT JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
					INNER JOIN
				tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
					INNER JOIN
				tbl_truck_master ttm ON tts.truck_ID = ttm.truck_ID
					INNER JOIN
				tbl_customer_master t1 ON tts.Customer_ID = t1.Customer_ID
			WHERE
				tts.Updated_By_ID = $cBy
					AND tts.tran_status = 'PENDING'
					AND (ttl.transaction_Line_ID IS NULL
					OR ttl.status = 'PENDING')
			GROUP BY tts.truck_Control_No)
			SELECT 
            a.Customer_Code, a.transaction_ID, a.truck_ID, a.Driver_ID, a.Driver_Name, a.Truck_Number, a.Truck_Type, a.Truck, 
            a.truck_Control_No, a.truck_Control_No_show, a.truckNo_Date, a.Route_Code1, a.start_time,
            CONCAT(if(route_special = 'Y' OR route_special = 'N', a.Route_Code1, CONCAT(a.Route_Code1,' ', route_special)),
			 ' | ', GROUP_CONCAT(DISTINCT Supplier_Name_Short ORDER BY t1.Route_Code, t1.Status_Pickup, t1.Route_ID, t1.planin_time SEPARATOR ', ')) AS Route_Code,
			route_special, amount_truck
            FROM a 
			LEFT JOIN tbl_route_master t1 ON  a.Route_Code1 = t1.Route_Code AND a.Customer_ID = t1.Customer_ID
			LEFT JOIN tbl_supplier_master t2 ON t1.Supplier_ID = t2.Supplier_ID
			GROUP BY t1.Route_Code;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		$header = jsonRow($re1, true, 0);

		$body = [];

		if (count($header) > 0) {
			$Route_Code = $header[0]['Route_Code'];

			$explode = explode(" | ", $Route_Code);
			$Route_Code1 = $explode[0];

			$explode1 = explode(" ", $Route_Code1);
			$Route_Code = $explode1[0];

			$transaction_ID = $header[0]['transaction_ID'];
			$truckNo_Date = $header[0]['truckNo_Date'];

			$sql = "SELECT
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Pickup_Date,
				BIN_TO_UUID(torder.Part_ID, TRUE) AS Part_ID,
				torder.Part_No,
				torder.Part_Name,
				tpm.Product_Code,
				Qty,
				Actual_Qty,
				Qty - Actual_Qty AS Sum_Qty,
				tpm.CBM_Per_Pkg,
				tpm.Mass_Per_Pallet,
				tpm.SNP_Per_Pallet,
				(((Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2),
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN
						ROUND((((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3)
					ELSE ROUND((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
				END AS CBM,
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN ROUND(((tpm.Mass_Per_Pallet*((Qty - Actual_Qty) / tpm.SNP_Per_Pallet))/2),3)
					ELSE ROUND((tpm.Mass_Per_Pallet*((Qty - Actual_Qty)/ tpm.SNP_Per_Pallet)),3)
				END AS WT,
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN
						(CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2
					ELSE CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet
				END AS Package_Qty,
				UM,
				PO_Line,
				PO_No,
				PO_Release,
				tpm.Pallet_Type,
				BIN_TO_UUID(tpm.Package_ID, TRUE) AS Package_ID,
				tpm.Width_Pallet_Size,
				tpm.Length_Pallet_Size,
				tpm.Height_Pallet_Size,
				tpm.Project,
				torder.Supplier_Name_Short,
				BIN_TO_UUID(torder.Supplier_ID, TRUE) AS Supplier_ID,
				torder.Pick,
				CONCAT(Width_Pallet_Size,
				'x',
				Length_Pallet_Size,
				'x',
				Height_Pallet_Size) AS Dimansion
			FROM
				tbl_order torder
					INNER JOIN
				tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
					INNER JOIN
				tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
			WHERE
				BIN_TO_UUID(tts.transaction_ID, TRUE) = '$transaction_ID'
					AND Pickup_Date = DATE('$truckNo_Date')
					AND Command != 'DELETE'
					AND torder.Qty != 0
					AND (Actual_Qty < Qty)
					AND tpm.Active = 'Y'
			ORDER BY Pickup_Date, Width_Pallet_Size,Length_Pallet_Size,Height_Pallet_Size, PO_No, Order_ID;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);

			$body = jsonRow($re1, true, 0);
		}

		$returnData = ['header' => $header, 'body' => $body];

		closeDBT($mysqli, 1, $returnData);
	} else if ($type == 3) {

		$dataParams = array(
			'obj',
			'obj=>Route_Code:s:0:0',
			'obj=>transaction_ID:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$explode = explode(" | ", $Route_Code);
		$Route_Code1 = $explode[0];

		$explode1 = explode(" ", $Route_Code1);
		$Route_Code = $explode1[0];

		$sql = "SELECT 
			BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
			BIN_TO_UUID(ttl.transaction_Line_ID, TRUE) AS transaction_Line_ID,
			BIN_TO_UUID(ttl.Route_ID, TRUE) AS Route_ID,
			ttl.pus_No,
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
			ttl.Add_Day,
			ttl.line_CBM,
			IF(ttl.Status_Pickup = 'PICKUP','-',ttm.Weight) AS Weight,
			ttl.line_Weight,
			IF(ttl.Status_Pickup = 'DELIVERY',ttm.Weight-ttl.line_Weight,ttl.line_Weight) AS Sum_Weight,
			ttl.Status_Pickup,
			ttl.Pick,
            sum(tstop.Actual_Qty) as sum_qty
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
				INNER JOIN
			tbl_route_master trm ON ttl.Route_ID = trm.Route_ID AND tts.Customer_ID = trm.Customer_ID
				INNER JOIN
			tbl_supplier_master tsm ON trm.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_truck_master ttm ON tts.Truck_ID = ttm.Truck_ID
				LEFT JOIN
			tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
		WHERE
			tts.Route_Code = '$Route_Code'
				AND BIN_TO_UUID(tts.transaction_ID, TRUE) = '$transaction_ID'
				AND tsm.Status = 'ACTIVE'
				-- AND trm.Status = 'ACTIVE'
		GROUP BY ttl.transaction_Line_ID
		ORDER BY sequence_Stop;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	}
	if ($type == 4) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT 
			BIN_TO_UUID(tstop.transaction_stop_ID, TRUE) AS transaction_stop_ID,
			BIN_TO_UUID(ttl.transaction_Line_ID, TRUE) AS transaction_Line_ID,
			ttl.pus_No,
			ttl.pus_Date,
			tstop.Refer_ID,
			tstop.CBM,
			torder.PO_No,
			tpm.Part_No,
			tpm.Part_Name,
			tpm.Project,
			tpm.Product_Code,
			tstop.Plan_Qty,
			tstop.Actual_Qty AS Qty,
			tstop.Package_Qty,
			tsm.Supplier_Name_Short,
			ttl.sequence_Stop,
			ttl.status,
			ttl.Creation_DateTime,
			CONCAT(Width_Pallet_Size,
				'x',
				Length_Pallet_Size,
				'x',
				Height_Pallet_Size) AS Dimansion
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON ttl.transaction_ID = tts.transaction_ID
				INNER JOIN
			tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
				INNER JOIN
			tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_order torder ON tstop.Order_ID = torder.Order_ID
				INNER JOIN
			tbl_part_master tpm ON tstop.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
		WHERE
			BIN_TO_UUID(ttl.transaction_ID, TRUE) = '$transaction_ID'
			AND tpm.Active = 'Y'
				-- AND ttl.status = 'PENDING'
				-- AND tsm.Status = 'ACTIVE'
		ORDER BY ttl.Supplier_ID, tpm.Product_Code, tstop.transaction_stop_ID;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 5) {

		$dataParams = array(
			'obj',
			'obj=>Pickup_Date:s:0:1',
			'obj=>transaction_ID:s:0:1',
			'obj=>Route_Code:s:0:1',

		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$explode = explode(" | ", $Route_Code);
		$Route_Code1 = $explode[0];

		$explode1 = explode(" ", $Route_Code1);
		$Route_Code = $explode1[0];

		$supplier = explode(', ', $explode[1]);
		$Customer_Code = end($supplier);

		if ($Customer_Code == 'TSPKK') {
			$Customer_Code = 'TSPK-L';
		}

		$Customer_ID = getCustomerID($mysqli, $Customer_Code);

		// $sql = "SELECT 
		// 	bin_to_uuid(Supplier_ID,true) AS Supplier_ID 
		// FROM 
		// 	tbl_route_master 
		// WHERE 
		// 	Route_Code = '$Route_Code'
		// 	AND Customer_ID = uuid_to_bin('$Customer_ID',true);";
		// $re1 = sqlError($mysqli, __LINE__, $sql, 1);
		// if ($re1->num_rows == 0) {
		// 	throw new Exception('ไม่พบข้อมูล ' . __LINE__);
		// }
		// while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
		// 	$Supplier_ID = $row['Supplier_ID'];
		// }

		$sql = "SELECT 
			BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
			Refer_ID,
			Pickup_Date,
			BIN_TO_UUID(torder.Part_ID, TRUE) AS Part_ID,
			torder.Part_No,
			torder.Part_Name,
			tpm.Product_Code,
			Qty,
			Actual_Qty,
			Qty - Actual_Qty AS Sum_Qty,
			tpm.CBM_Per_Pkg,
			tpm.Mass_Per_Pallet,
			tpm.SNP_Per_Pallet,
			(((Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2),
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN
					ROUND((((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3)
				ELSE ROUND((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
			END AS CBM,
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN ROUND(((tpm.Mass_Per_Pallet*((Qty - Actual_Qty) / tpm.SNP_Per_Pallet))/2),3)
				ELSE ROUND((tpm.Mass_Per_Pallet*((Qty - Actual_Qty)/ tpm.SNP_Per_Pallet)),3)
			END AS WT,
			CASE
				WHEN
					tpm.Group_pallet = 'Y'
				THEN
					(CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2
				ELSE CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet
			END AS Package_Qty,
			UM,
			PO_Line,
			PO_No,
			PO_Release,
			tpm.Pallet_Type,
			BIN_TO_UUID(tpm.Package_ID, TRUE) AS Package_ID,
			tpm.Width_Pallet_Size,
			tpm.Length_Pallet_Size,
			tpm.Height_Pallet_Size,
			tpm.Project,
			torder.Supplier_Name_Short,
			BIN_TO_UUID(torder.Supplier_ID, TRUE) AS Supplier_ID,
			torder.Pick,
			CONCAT(Width_Pallet_Size,
				'x',
				Length_Pallet_Size,
				'x',
				Height_Pallet_Size) AS Dimansion
		FROM
			tbl_order torder
				INNER JOIN
			tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_route_master t1 ON tsm.Supplier_ID = t1.Supplier_ID
		WHERE
				Pickup_Date = '$Pickup_Date'
				AND t1.Route_Code = '$Route_Code'
				AND t1.Customer_ID = uuid_to_bin('$Customer_ID',true)
				AND Command != 'DELETE'
				AND torder.Qty != 0
				AND (Actual_Qty < Qty)
				AND tpm.Active = 'Y'
			ORDER BY Pickup_Date, Width_Pallet_Size,Length_Pallet_Size,Height_Pallet_Size, PO_No, Order_ID;";
		//exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 6) {

		$dataParams = array(
			'obj',
			'obj=>truckNo_Date:s:0:1',
			'obj=>Pickup_Date:s:0:1',
			'obj=>transaction_ID:s:0:1',
		);

		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Pickup_Date,
				BIN_TO_UUID(torder.Part_ID, TRUE) AS Part_ID,
				torder.Part_No,
				torder.Part_Name,
				tpm.Product_Code,
				Qty,
				Actual_Qty,
				Qty - Actual_Qty AS Sum_Qty,
				tpm.CBM_Per_Pkg,
				tpm.Mass_Per_Pallet,
				tpm.SNP_Per_Pallet,
				(((Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2),
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN
						ROUND((((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3)
					ELSE ROUND((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
				END AS CBM,
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN ROUND(((tpm.Mass_Per_Pallet*((Qty - Actual_Qty) / tpm.SNP_Per_Pallet))/2),3)
					ELSE ROUND((tpm.Mass_Per_Pallet*((Qty - Actual_Qty)/ tpm.SNP_Per_Pallet)),3)
				END AS WT,
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN
						(CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2
					ELSE CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet
				END AS Package_Qty,
				UM,
				PO_Line,
				PO_No,
				PO_Release,
				tpm.Pallet_Type,
				BIN_TO_UUID(tpm.Package_ID, TRUE) AS Package_ID,
				tpm.Width_Pallet_Size,
				tpm.Length_Pallet_Size,
				tpm.Height_Pallet_Size,
				tpm.Project,
				torder.Supplier_Name_Short,
				BIN_TO_UUID(torder.Supplier_ID, TRUE) AS Supplier_ID,
				torder.Pick,
				CONCAT(Width_Pallet_Size,
				'x',
				Length_Pallet_Size,
				'x',
				Height_Pallet_Size) AS Dimansion
			FROM
				tbl_order torder
					INNER JOIN
				tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
					INNER JOIN
				tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
			WHERE
				BIN_TO_UUID(tts.transaction_ID, TRUE) = '$transaction_ID'
					AND Pickup_Date = DATE('$Pickup_Date')
					AND Command != 'DELETE'
					AND torder.Qty != 0
					AND (Actual_Qty < Qty)
					AND tpm.Active = 'Y'
			ORDER BY Pickup_Date, Width_Pallet_Size,Length_Pallet_Size,Height_Pallet_Size, PO_No, Order_ID;";
		// exit($sql);
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 7) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
			'obj=>Pickup_Date:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT 
			BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
			Route_Code,
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
			$Route_Code = $row['Route_Code'];
			$truckNo_Date = $row['truckNo_Date'];
		}

		$sql = "SELECT 
			BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
			BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID
		FROM 
			tbl_transaction_line
		WHERE 
		BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		if ($re1->num_rows == 0) {
			throw new Exception('ไม่พบข้อมูล ' . __LINE__);
		}
		while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			$Supplier_ID = $row['Supplier_ID'];
		}

		$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Pickup_Date,
				BIN_TO_UUID(torder.Part_ID, TRUE) AS Part_ID,
				torder.Part_No,
				torder.Part_Name,
				tpm.Product_Code,
				Qty,
				Actual_Qty,
				Qty - Actual_Qty AS Sum_Qty,
				tpm.Mass_Per_Pallet,
				tpm.SNP_Per_Pallet,
				(((Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2),
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN
						ROUND((((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3)
					ELSE ROUND((CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
				END AS CBM,
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN ROUND(((tpm.Mass_Per_Pallet*((Qty - Actual_Qty) / tpm.SNP_Per_Pallet))/2),3)
					ELSE ROUND((tpm.Mass_Per_Pallet*((Qty - Actual_Qty)/ tpm.SNP_Per_Pallet)),3)
				END AS WT,
				CASE
					WHEN
					tpm.Group_pallet = 'Y'
					THEN
						(CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet) / 2
					ELSE CEILING(Qty - Actual_Qty) / tpm.SNP_Per_Pallet
				END AS Package_Qty,
				UM,
				PO_Line,
				PO_No,
				PO_Release,
				tpm.Pallet_Type,
				BIN_TO_UUID(tpm.Package_ID, TRUE) AS Package_ID,
				tpm.Width_Pallet_Size,
				tpm.Length_Pallet_Size,
				tpm.Height_Pallet_Size,
				torder.Supplier_Name_Short,
				tpm.Project,
				BIN_TO_UUID(torder.Supplier_ID, TRUE) AS Supplier_ID,
				torder.Pick,
				CONCAT(Width_Pallet_Size,
				'x',
				Length_Pallet_Size,
				'x',
				Height_Pallet_Size) AS Dimansion
			FROM
				tbl_order torder
					INNER JOIN
				tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
					INNER JOIN
				tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
			WHERE
				BIN_TO_UUID(tts.transaction_ID, TRUE) = '$transaction_ID'
					AND Route_Code = '$Route_Code'
					AND BIN_TO_UUID(torder.Supplier_ID, TRUE) = '$Supplier_ID'
					AND (Pickup_Date = DATE('$truckNo_Date') OR Pickup_Date = DATE('$Pickup_Date'))
					AND Command != 'DELETE'
					AND torder.Qty != 0
					AND (Actual_Qty < Qty)
					AND tpm.Active = 'Y'
			ORDER BY Pickup_Date, Width_Pallet_Size,Length_Pallet_Size,Height_Pallet_Size, PO_No, Order_ID;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));


		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 10) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$sql = "SELECT 
			BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
			Route_Code,
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
			$Route_Code = $row['Route_Code'];
			$truckNo_Date = $row['truckNo_Date'];
		}

		$sql = "SELECT 
			BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
			BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID
		FROM 
			tbl_transaction_line
		WHERE 
		BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		if ($re1->num_rows == 0) {
			throw new Exception('ไม่พบข้อมูล ' . __LINE__);
		}
		while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
			$Supplier_ID = $row['Supplier_ID'];
		}

		$sql = "SELECT 
			BIN_TO_UUID(tstop.transaction_stop_ID, TRUE) AS transaction_stop_ID,
			BIN_TO_UUID(ttl.transaction_Line_ID, TRUE) AS transaction_Line_ID,
			ttl.pus_No,
			ttl.pus_Date,
			tstop.Refer_ID,
			tstop.CBM,
			tpm.Part_No,
			tpm.Part_Name,
			tpm.Product_Code,
			tstop.Plan_Qty,
			tstop.Actual_Qty AS Qty,
			tsm.Supplier_Name_Short,
			ttl.sequence_Stop,
			ttl.status,
			ttl.Creation_DateTime,
			torder.PO_No
		FROM
			tbl_transaction tts
				INNER JOIN
			tbl_transaction_line ttl ON ttl.transaction_ID = tts.transaction_ID
				INNER JOIN
			tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
				INNER JOIN
			tbl_order torder ON tstop.Order_ID = torder.Order_ID
				INNER JOIN
			tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
				INNER JOIN
			tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
		WHERE
			BIN_TO_UUID(ttl.transaction_ID, TRUE) = '$transaction_ID'
				AND BIN_TO_UUID(ttl.Supplier_ID, TRUE) = '$Supplier_ID'
				AND ttl.status = 'PENDING'
				AND tsm.Status = 'ACTIVE'
				AND tpm.Active = 'Y'
		ORDER BY ttl.sequence_Stop , ttl.transaction_Line_ID;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'TruckPlan'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
		$dataParams = array(
			'obj',
			'obj=>Route_Code:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>Driver_Name:s:0:1',
			'obj=>Truck:s:0:1',
			'obj=>start_time:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$explode = explode(" | ", $Route_Code);
			$Route_Code1 = $explode[0];

			$explode1 = explode(" ", $Route_Code1);
			$Route_Code = $explode1[0];

			$supplier = explode(', ', $explode[1]);
			$Customer_Code = end($supplier);

			if ($Customer_Code == 'TSPKK') {
				$Customer_Code = 'TSPK-L';
			}


			if ($Customer_Code == 'TSPK-C') {
				$truck_Control_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('tcn_pkc',0) truck_Control_No", 1))->fetch_array(MYSQLI_ASSOC)['truck_Control_No'];
			} else if ($Customer_Code == 'TSPK-L') {
				$truck_Control_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('tcn_pkl-bp',0) truck_Control_No", 1))->fetch_array(MYSQLI_ASSOC)['truck_Control_No'];
			} else if ($Customer_Code == 'TSPK-BP') {
				$truck_Control_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('tcn_pkl-bp',0) truck_Control_No", 1))->fetch_array(MYSQLI_ASSOC)['truck_Control_No'];
			}

			$truck_Control_No = $truck_Control_No . $Customer_Code;

			$sql = "SELECT 
				truck_Control_No
			FROM 
				tbl_transaction 
			WHERE 
				truck_Control_No = '$truck_Control_No';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('Truck Control No.นี้มีการใช้งานแล้ว ' . __LINE__);
			}

			$Customer_ID = getCustomerID($mysqli, $Customer_Code);
			$Route_ID = getRouteID($mysqli, $Route_Code, $Customer_ID);

			$sql = "SELECT 
				Route_Code,
				BIN_TO_UUID(Route_ID,TRUE) as Route_ID,
				start_time,
				Add_Day
			FROM 
				tbl_route_master 
			WHERE 
				Route_ID = uuid_to_bin('$Route_ID',true)
			ORDER BY Route_Code, TIMESTAMP(if(Add_Day = 0, curdate(), DATE_ADD(curdate(), INTERVAL 1 DAY)), planin_time) ASC  LIMIT 1;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$start_time = $row['start_time'];
				$Add_Day = $row['Add_Day'];
			}

			$Driver_ID = getDriverID($mysqli, $Driver_Name);

			$explode = explode(' | ', $Truck);
			$Truck_Number = $explode[0];
			$Truck_Type = $explode[1];

			$Truck_ID = getTruckID($mysqli, $Truck_Number, $Truck_Type);

			$sql = "INSERT INTO tbl_transaction (
				truck_Control_No,
				truckNo_Date,
				Route_Code,
				Truck_ID,
				Driver_ID,
				Truck_Number,
				Truck_Type,
				start_Date,
				Customer_ID,
				Creation_Date,
				Creation_DateTime,
				Created_By_ID,
				Last_Updated_Date,
				Last_Updated_DateTime,
				Updated_By_ID )
			VALUES(
				'$truck_Control_No',
				'$truckNo_Date',
				'$Route_Code',
				UUID_TO_BIN('$Truck_ID',TRUE),
				UUID_TO_BIN('$Driver_ID',TRUE),
				'$Truck_Number',
				'$Truck_Type',
				CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL $Add_Day DAY),' ', '$start_time'),
				UUID_TO_BIN('$Customer_ID',TRUE),
				curdate(),
				now(),
				$cBy,
				curdate(),
				now(),
				$cBy);";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$transaction_ID = getTransactionID($mysqli, $truck_Control_No);

			$sql = "SELECT 
					BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
					count(sequence_Stop) AS sequence_Stop
				FROM 
					tbl_transaction_line
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				$sequence_Stop = 0;
			} else {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$sequence_Stop = $row['sequence_Stop'];
				}
			}

			$sql = "SELECT 
				trm.Route_Code,
				BIN_TO_UUID(trm.Route_ID,TRUE) as Route_ID,
				BIN_TO_UUID(trm.Supplier_ID,TRUE) as Supplier_ID,
				Status_Pickup
			FROM 
				tbl_route_master trm
			WHERE 
				trm.Route_Code = '$Route_Code'
					AND trm.Customer_ID = uuid_to_bin('$Customer_ID',true)
					AND trm.Status = 'ACTIVE'
			ORDER BY trm.Route_Code, trm.Status_Pickup, TIMESTAMP(if(trm.Add_Day = 0, curdate(), DATE_ADD(curdate(), INTERVAL 1 DAY)), trm.planin_time) ASC;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Route_ID = $row['Route_ID'];
				$Supplier_ID = $row['Supplier_ID'];
				$Status_Pickup = $row['Status_Pickup'];

				if ($Customer_Code == 'TSPK-C') {
					if ($Status_Pickup == 'PICKUP') {
						$pus_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('pus_pkc',0) pus_No", 1))->fetch_array(MYSQLI_ASSOC)['pus_No'];
					} else if ($Status_Pickup == 'DELIVERY') {
						$pus_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('d_pus_pkc',0) pus_No", 1))->fetch_array(MYSQLI_ASSOC)['pus_No'];
					}
				} else if ($Customer_Code == 'TSPK-L') {
					if ($Status_Pickup == 'PICKUP') {
						$pus_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('pus_pkl-bp',0) pus_No", 1))->fetch_array(MYSQLI_ASSOC)['pus_No'];
					} else if ($Status_Pickup == 'DELIVERY') {
						$pus_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('d_pus_pkl-bp',0) pus_No", 1))->fetch_array(MYSQLI_ASSOC)['pus_No'];
					}
				} else if ($Customer_Code == 'TSPK-BP') {
					if ($Status_Pickup == 'PICKUP') {
						$pus_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('pus_pkl-bp',0) pus_No", 1))->fetch_array(MYSQLI_ASSOC)['pus_No'];
					} else if ($Status_Pickup == 'DELIVERY') {
						$pus_No = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('d_pus_pkl-bp',0) pus_No", 1))->fetch_array(MYSQLI_ASSOC)['pus_No'];
					}
				}
				$pus_No = $pus_No . $Customer_Code;

				$sequence_Stop = $sequence_Stop + 1;

				$sql = "INSERT INTO tbl_transaction_line (
					transaction_ID,
					Route_ID,
					pus_No,
					pus_Date,
					Supplier_ID,
					sequence_Stop,
					planin_time,
					planout_time,
					return_planin_time,
					return_planout_time,
					load_unload_time,
					Status_Pickup,
					Add_Day,
					Creation_Date,
					Creation_DateTime,
					Created_By_ID,
					Last_Updated_Date,
					Last_Updated_DateTime,
					Updated_By_ID)
				SELECT
					UUID_TO_BIN('$transaction_ID',TRUE),
					trm.Route_ID,
					'$pus_No',
					'$truckNo_Date',
					UUID_TO_BIN('$Supplier_ID',TRUE),
					$sequence_Stop,
					CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL trm.Add_Day DAY),' ', trm.planin_time),
					CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL trm.Add_Day DAY),' ', trm.planout_time),
					CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL trm.Add_Day DAY),' ', trm.return_planin_time),
					CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL trm.Add_Day DAY),' ', trm.return_planout_time),
					trm.load_unload_time,
					trm.Status_Pickup,
					trm.Add_Day,
					curdate(),
					now(),
					$cBy,
					curdate(),
					now(),
					$cBy
				FROM
					tbl_route_master trm
				WHERE 
					BIN_TO_UUID(trm.Route_ID,TRUE) = '$Route_ID'
						AND BIN_TO_UUID(trm.Supplier_ID,TRUE) = '$Supplier_ID';";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
				}
				//exit($sql);
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 12) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>Route_Code:s:0:1',
			'obj=>Order_ID:s:0:1',
			'obj=>Refer_ID:s:0:1',
			'obj=>Part_ID:s:0:1',
			'obj=>Qty:i:0:1',
			'obj=>Actual_Qty:i:0:0',
			'obj=>Sum_Qty:i:0:1',
			'obj=>Supplier_ID:s:0:1',
			'obj=>truck_ID:s:0:1',
			'obj=>Driver_ID:s:0:1',
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


			$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Qty - Actual_Qty AS Qty,
				tpm.SNP_Per_Pallet,
				CBM_Per_Pkg,
    			tpm.Mass_Per_Pallet,
				CASE
					WHEN
						tpm.Group_pallet = 'Y'
					THEN
						ROUND((CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg,3)
					ELSE ROUND(CEILING($Sum_Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
				END AS CBM,
				CASE
					WHEN
						tpm.Group_pallet = 'Y'
					THEN ROUND(((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet))/2),3)
					ELSE ROUND((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet)),3)
				END AS WT,
				CASE
					WHEN
						tpm.Group_pallet = 'Y'
					THEN
						CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2
					ELSE CEILING($Sum_Qty / tpm.SNP_Per_Pallet)
				END AS Package_Qty
			FROM
				tbl_order torder
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
			WHERE
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
					AND Command != 'DELETE'
					AND tpm.Active = 'Y'
					AND (Actual_Qty < Qty);";
			//AND (Pick = '' OR Actual_Qty < Qty)
			////exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$CBM = $row['CBM'];
				$Package_Qty = $row['Package_Qty'];
				$WT = $row['WT'];
			}
			//exit($CBM);
			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
				BIN_TO_UUID(Route_ID,TRUE) AS Route_ID
			FROM 
				tbl_transaction_line
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(Supplier_ID,TRUE) = '$Supplier_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_Line_ID = $row['transaction_Line_ID'];
				$Route_ID = $row['Route_ID'];
			}


			$sql = "SELECT 
				BIN_TO_UUID(Order_ID,TRUE) AS Order_ID
			FROM 
				tbl_transaction_stop t1
			WHERE
			BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID'
				AND BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('มีรายการนี้อยู่แล้ว<br>ไม่สามารถบวกเพิ่มได้<br> กรุณาลบออเดอร์ฝั่ง Pickup Sheet ออกก่อน');
			}

			$sql = "SELECT 
				BIN_TO_UUID(Route_ID,TRUE) AS Route_ID,
				Weight
			FROM 
				tbl_route_master 
			WHERE 
			BIN_TO_UUID(Route_ID,TRUE) = '$Route_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Weight = $row['Weight'];
			}

			$sql = "INSERT INTO tbl_transaction_stop (
				transaction_Line_ID,
				Order_ID,
				Refer_ID,
				Package_ID,
				SNP_Per_Pallet,
				Package_Qty,
				CBM,
				WT,
				Part_ID,
				Plan_Qty,
				Actual_Qty,
				Creation_Date,
				Creation_DateTime,
				Created_By_ID,
				Last_Updated_Date,
				Last_Updated_DateTime,
				Updated_By_ID)
			SELECT
				UUID_TO_BIN('$transaction_Line_ID',TRUE),
				torder.Order_ID,
				torder.Refer_ID,
				tpm.Package_ID,
				tpm.SNP_Per_Pallet,
				$Package_Qty,
				$CBM,
				$WT,
				torder.Part_ID,
				torder.Qty,
				$Sum_Qty,
				curdate(),
				now(),
				$cBy,
				curdate(),
				now(),
				$cBy
			FROM
				tbl_order torder
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
					INNER JOIN
				tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
			WHERE 
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
					AND BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID'
					AND tpm.Active = 'Y'
			ON DUPLICATE KEY UPDATE
				Package_Qty = $Package_Qty,
				CBM = $CBM,
				WT = $WT,
				tbl_transaction_stop.Actual_Qty = $Sum_Qty,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				line_CBM = line_CBM+$CBM,
				line_Weight = line_Weight+$WT,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT 
				pus_No,
				line_Weight
			FROM 
				tbl_transaction_line
			WHERE 
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$line_Weight = $row['line_Weight'];
			}



			// if($line_Weight > $Weight){
			// 	echo '{"status":"server","mms":"เกิน","data":[]}';
			// }

			$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Actual_Qty
			FROM
				tbl_order
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			// exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Actual_Qty = $row['Actual_Qty'];
			}

			$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Actual_Qty
			FROM
				tbl_order torder
			WHERE
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
				AND ($Sum_Qty > (Qty-Actual_Qty) OR $Sum_Qty > Qty);";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ป้อนจำนวนเกิน ');
			}

			// echo($Actual_Qty);
			// exit();

			$sql = "UPDATE tbl_order 
			SET 
				Pick = 'Y',
				Actual_Qty = (Actual_Qty-$Actual_Qty)+($Actual_Qty+$Sum_Qty),
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			//exit($line_CBM);

			$sql = "UPDATE tbl_transaction_line 
			SET 
				line_CBM = line_CBM+$CBM,
				line_Weight = line_Weight+$WT,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND Status_Pickup = 'DELIVERY';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Actual_Qty,
				Qty
			FROM
				tbl_order torder
			WHERE
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Actual_Qty = $row['Actual_Qty'];
					$Qty = $row['Qty'];
				}
			}

			$left = $Qty - $Actual_Qty;

			$mysqli->commit();
			closeDBT($mysqli, 1, $left);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 13) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>Route_Code:s:0:1',
			'obj=>Order_ID:s:0:1',
			'obj=>Refer_ID:s:0:1',
			'obj=>Part_ID:s:0:1',
			'obj=>Qty:i:0:1',
			'obj=>Actual_Qty:i:0:0',
			'obj=>Package_Qty:f:0:0',
			'obj=>Supplier_ID:s:0:1',
			'obj=>truck_ID:s:0:1',
			'obj=>Driver_ID:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$explode = explode(" | ", $Route_Code);
			$Route_Code1 = $explode[0];

			$explode1 = explode(" ", $Route_Code1);
			$Route_Code = $explode1[0];

			//echo $Sum_Qty;
			//exit();

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
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Qty - Actual_Qty AS Qty,
				tpm.SNP_Per_Pallet,
				CBM_Per_Pkg,
    			($Package_Qty * tpm.SNP_Per_Pallet) AS Sum_Qty,
    			(($Package_Qty * tpm.SNP_Per_Pallet) / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg AS CBM,
				ROUND((tpm.Mass_Per_Pallet*(($Package_Qty * tpm.SNP_Per_Pallet) / tpm.SNP_Per_Pallet)),3) AS WT
			FROM
				tbl_order torder
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
			WHERE
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
					AND Command != 'DELETE'
					AND tpm.Active = 'Y'
					AND (Actual_Qty < Qty);";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$CBM = $row['CBM'];
				$Sum_Qty = $row['Sum_Qty'];
				$WT = $row['WT'];
			}

			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID
			FROM 
				tbl_transaction_line
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(Supplier_ID,TRUE) = '$Supplier_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_Line_ID = $row['transaction_Line_ID'];
			}


			$sql = "INSERT INTO tbl_transaction_stop (
				transaction_Line_ID,
				Order_ID,
				Refer_ID,
				Package_ID,
				SNP_Per_Pallet,
				Package_Qty,
				CBM,
				WT,
				Part_ID,
				Plan_Qty,
				Actual_Qty,
				Creation_Date,
				Creation_DateTime,
				Created_By_ID,
				Last_Updated_Date,
				Last_Updated_DateTime,
				Updated_By_ID)
			SELECT
				UUID_TO_BIN('$transaction_Line_ID',TRUE),
				torder.Order_ID,
				torder.Refer_ID,
				tpm.Package_ID,
				tpm.SNP_Per_Pallet,
				$Package_Qty,
				$CBM,
				$WT,
				torder.Part_ID,
				torder.Qty,
				$Sum_Qty,
				curdate(),
				now(),
				$cBy,
				curdate(),
				now(),
				$cBy
			FROM
				tbl_order torder
					INNER JOIN
				tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
					INNER JOIN
				tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
					INNER JOIN
				tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
			WHERE 
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
					AND BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID'
					AND tpm.Active = 'Y'
			ON DUPLICATE KEY UPDATE
				Package_Qty = $Package_Qty,
				CBM = $CBM,
				WT = $WT,
				tbl_transaction_stop.Actual_Qty = $Sum_Qty,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy;";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				line_CBM = line_CBM+$CBM,
				line_Weight = line_Weight+$WT,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}


			$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Actual_Qty
			FROM
				tbl_order
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Actual_Qty = $row['Actual_Qty'];
			}

			$sql = "SELECT 
				BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
				Refer_ID,
				Actual_Qty
			FROM
				tbl_order torder
			WHERE
				BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
				AND ($Sum_Qty > (Qty-Actual_Qty) OR $Sum_Qty > Qty);";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ป้อนจำนวนเกิน ');
			}


			$sql = "UPDATE tbl_order 
			SET 
				Pick = 'Y',
				Actual_Qty = (Actual_Qty-$Actual_Qty)+($Actual_Qty+$Sum_Qty),
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
			//exit($sql);
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				line_CBM = line_CBM+$CBM,
				line_Weight = line_Weight+$WT,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND Status_Pickup = 'DELIVERY';";
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
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'TruckPlan'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {

		$obj  = $_POST['obj'];
		$transaction_Line_ID  = $obj;


		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
    			pus_No
			FROM 
				tbl_transaction_line ttl
					LEFT JOIN
				tbl_transaction_stop tstop ON tstop.transaction_Line_ID = ttl.transaction_Line_ID
			WHERE 
			BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล');
			}


			$sql = "UPDATE tbl_transaction_line 
			SET 
				Pick = '',
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}


			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 22) {
		$dataParams = array(
			'obj',
			'obj=>Route_ID:s:0:1',
			'obj=>transaction_ID:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>transaction_Line_ID:s:0:1',
			'obj=>planin_time:s:0:0',
			'obj=>planout_time:s:0:0',
			'obj=>return_planin_time:s:0:0',
			'obj=>return_planout_time:s:0:0',
			'obj=>sequence_Stop:s:0:0',
			'obj=>Status_Pickup:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
				Route_Code,
				BIN_TO_UUID(Route_ID,TRUE) as Route_ID,
				Add_Day
			FROM 
				tbl_route_master 
			WHERE 
				BIN_TO_UUID(Route_ID,TRUE) = '$Route_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Add_Day = $row['Add_Day'];
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
				BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID
			FROM
				tbl_transaction_line
			WHERE
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}

			//exit($start_Time_sup);

			$sql = "UPDATE tbl_transaction_line 
			SET 
				sequence_Stop = '$sequence_Stop',
				Status_Pickup = '$Status_Pickup',
				planin_time = CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL $Add_Day DAY),' ', '$planin_time'),
				planout_time = CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL $Add_Day DAY),' ', '$planout_time'),
				return_planin_time = CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL $Add_Day DAY),' ', '$return_planin_time'),
				return_planout_time = CONCAT(DATE_ADD(DATE('$truckNo_Date'), INTERVAL $Add_Day DAY),' ', '$return_planout_time'),
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
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
	if ($_SESSION['xxxRole']->{'TruckPlan'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {

		$obj  = $_POST['obj'];
		$transaction_Line_ID  = $obj;

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
    			pus_No
			FROM 
				tbl_transaction_line ttl
					INNER JOIN
				tbl_transaction_stop tstop ON tstop.transaction_Line_ID = ttl.transaction_Line_ID
			WHERE 
			BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				throw new Exception('ไม่สามารถปิดการใช้งานได้<br>Supplier นี้มีการเพิ่มออเดอร์แล้ว<br>กรุณาลบข้อมูลใน ตาราง Pickup Sheet ก่อน');
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				Pick = 'N',
				Last_Updated_DateTime = NOW(),
				Updated_By_ID = $cBy
			WHERE
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}

		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 32) {

		$obj  = $_POST['obj'];
		// var_dump($obj);
		// exit();
		$explode = explode("/", $obj);
		$transaction_stop_ID  = $explode[0];
		//exit($Truck_Control_Route_ID);

		$mysqli->autocommit(FALSE);
		try {

			$sql = "SELECT 
			BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
				BIN_TO_UUID(Order_ID,TRUE) AS Order_ID,
				Actual_Qty,
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
				$transaction_Line_ID = $row['transaction_Line_ID'];
				$Order_ID = $row['Order_ID'];
				$Actual_Qty = $row['Actual_Qty'];
				$CBM = $row['CBM'];
				$WT = $row['WT'];
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				line_CBM = line_CBM-$CBM,
				line_Weight = line_Weight-$WT,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
			}

			$sql = "SELECT 
			BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
			FROM
				tbl_transaction_line
			WHERE
				BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_ID = $row['transaction_ID'];
			}

			$sql = "UPDATE tbl_transaction_line 
			SET 
				line_CBM = line_CBM-$CBM,
				line_Weight = line_Weight-$WT,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND Status_Pickup = 'DELIVERY';";
			sqlError($mysqli, __LINE__, $sql, 1);


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

			$sql = "SELECT 
				BIN_TO_UUID(Order_ID,TRUE) AS Order_ID
			FROM
				tbl_order
			WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID'
					AND Actual_Qty = 0;";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$sql = "UPDATE tbl_order 
				SET 
					Pick = '',
					Last_Updated_DateTime = NOW(),
					Updated_By_ID = $cBy
				WHERE
				BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
				// sqlError($mysqli, __LINE__, $sql, 1);
				// if ($mysqli->affected_rows == 0) {
				// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				// }
			}

			$sql = "DELETE FROM tbl_transaction_stop
			WHERE
				BIN_TO_UUID(transaction_stop_ID,TRUE) = '$transaction_stop_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถลบได้' . __LINE__);
			}



			$mysqli->commit();
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} else if ($type == 33) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) as transaction_ID,
				truck_Control_No,
				edit_status
			FROM 
				tbl_transaction 
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$edit_status = $row['edit_status'];
				$truck_Control_No = $row['truck_Control_No'];
			}


			$sql = "SELECT 
				BIN_TO_UUID(tstop.transaction_Line_ID,TRUE) as transaction_Line_ID
			FROM 
				tbl_transaction_stop tstop
					INNER JOIN
				tbl_transaction_line ttl ON tstop.transaction_Line_ID = ttl.transaction_Line_ID
			WHERE 
				BIN_TO_UUID(ttl.transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$sql = "UPDATE tbl_order torder,
			(
				SELECT 
					Order_ID,
					Actual_Qty
				FROM 
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
						INNER JOIN
					tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
				WHERE BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
			) AS tts1
			SET 
				torder.Actual_Qty = torder.Actual_Qty-tts1.Actual_Qty,
				torder.Last_Updated_DateTime = NOW(),
				torder.Updated_By_ID = $cBy
			WHERE
				torder.Order_ID = tts1.Order_ID;";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
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
				//exit($sql);
				// sqlError($mysqli, __LINE__, $sql, 1);
				// if ($mysqli->affected_rows == 0) {
				// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				// }
			}

			if ($edit_status == 'Y') {
				//exit('1');
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

				$sql = "SELECT 
					BIN_TO_UUID(transaction_Line_ID,TRUE) as transaction_Line_ID
				FROM 
					tbl_transaction_line
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					//exit('2');
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

					$sql = "SELECT 
						BIN_TO_UUID(transaction_stop_ID,TRUE) as transaction_stop_ID
					FROM 
						tbl_transaction tts
							INNER JOIN
						tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
							INNER JOIN
						tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
					WHERE 
						BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows > 0) {

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
			} else {
				//exit('3');
				$sql = "DELETE FROM tbl_transaction
			WHERE
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถลบได้' . __LINE__);
				}
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 34) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {


			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) as transaction_ID,
				truck_Control_No,
				edit_status
			FROM 
				tbl_transaction 
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$edit_status = $row['edit_status'];
				$truck_Control_No = $row['truck_Control_No'];
			}


			$sql = "SELECT 
				BIN_TO_UUID(tstop.transaction_Line_ID,TRUE) as transaction_Line_ID
			FROM 
				tbl_transaction_stop tstop
					INNER JOIN
				tbl_transaction_line ttl ON tstop.transaction_Line_ID = ttl.transaction_Line_ID
			WHERE 
				BIN_TO_UUID(ttl.transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				$sql = "UPDATE tbl_order torder,
			(
				SELECT 
					Order_ID,
					Actual_Qty
				FROM 
					tbl_transaction tts
						INNER JOIN
					tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
						INNER JOIN
					tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID
				WHERE BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
			) AS tts1
			SET 
				torder.Actual_Qty = torder.Actual_Qty-tts1.Actual_Qty,
				torder.Last_Updated_DateTime = NOW(),
				torder.Updated_By_ID = $cBy
			WHERE
				torder.Order_ID = tts1.Order_ID;";
				//exit($sql);
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
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
				//exit($sql);
				// sqlError($mysqli, __LINE__, $sql, 1);
				// if ($mysqli->affected_rows == 0) {
				// 	throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				// }
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
	if ($_SESSION['xxxRole']->{'TruckPlan'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>Route_Code:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$explode = explode(" | ", $Route_Code);
			$Route_Code1 = $explode[0];

			$explode1 = explode(" ", $Route_Code1);
			$Route_Code = $explode1[0];

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) as transaction_ID,
				edit_status
			FROM 
				tbl_transaction 
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$edit_status = $row['edit_status'];
			}

			$sql = "SELECT 
				BIN_TO_UUID(transaction_Line_ID,TRUE) as transaction_Line_ID,
				count(sequence_Stop) AS total_Stop
			FROM 
				tbl_transaction_line 
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND Pick != 'N';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$total_Stop = $row['total_Stop'];
			}

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
				tsm.Supplier_Name_Short
			FROM 
				tbl_transaction_line ttl
					INNER JOIN
				tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND line_CBM = 0
					AND Pick = ''
					AND Status_Pickup = 'PICKUP'
					AND tsm.Status = 'ACTIVE';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Supplier_Name_Short = $row['Supplier_Name_Short'];
				}
				throw new Exception('Supplier : ' . $Supplier_Name_Short . ' ยังไม่มีออเดอร์');
			}


			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID,
				tsm.Supplier_Name_Short
			FROM 
				tbl_transaction_line ttl
					INNER JOIN
				tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND line_CBM = 0
					AND Status_Pickup = 'DELIVERY'
					AND tsm.Status = 'ACTIVE';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows > 0) {
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Supplier_Name_Short = $row['Supplier_Name_Short'];
				}
				throw new Exception('[' . $Supplier_Name_Short . '] Supplier นี้ยังไม่มี Order');
			}

			$sql = "UPDATE tbl_transaction 
			SET 
				tran_status = 'PLANNING',
				total_Stop = $total_Stop,
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$sql = "SELECT
				bin_to_uuid(ttl.transaction_Line_ID,true) as transaction_Line_ID,
				sum(tstop.CBM) line_CBM
			FROM 
				tbl_transaction_stop tstop
					INNER JOIN
				tbl_transaction_line ttl on tstop.transaction_Line_ID = ttl.transaction_Line_ID
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND Status_Pickup = 'PICKUP'
					AND tstop.status = 'COMPLETE'
			GROUP BY ttl.transaction_Line_ID;";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$transaction_Line_ID = $row['transaction_Line_ID'];
				$line_CBM = $row['line_CBM'];

				$sql = "UPDATE tbl_transaction_line 
				SET 
					line_CBM = $line_CBM,
					status = 'PLANNING',
					Last_Updated_Date = curdate(),
					Last_Updated_DateTime = now(),
					Updated_By_ID = $cBy
				WHERE 
					BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			}


			$sql = "SELECT
				sum(tstop.CBM) All_CBM
			FROM 
				tbl_transaction_stop tstop
					INNER JOIN
				tbl_transaction_line ttl on tstop.transaction_Line_ID = ttl.transaction_Line_ID
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
					AND Status_Pickup = 'PICKUP'
					AND tstop.status = 'COMPLETE';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$All_CBM = $row['All_CBM'];

				$sql = "UPDATE tbl_transaction_line 
				SET 
					line_CBM = $All_CBM,
					status = 'PLANNING',
					Last_Updated_Date = curdate(),
					Last_Updated_DateTime = now(),
					Updated_By_ID = $cBy
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
						AND Status_Pickup = 'DELIVERY';";
				sqlError($mysqli, __LINE__, $sql, 1);
				if ($mysqli->affected_rows == 0) {
					throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
				}
			}

			if ($edit_status == 'Y') {
				$sp_trans = "CALL SP_Transaction_Save('EDIT', '$truck_Control_No', '', '$cBy');";
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
			} else {
				$sp_trans = "CALL SP_Transaction_Save('PLANNING', '$truck_Control_No', '', '$cBy');";
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
			}


			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 42) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 50 && $type <= 60) //save
{
	if ($_SESSION['xxxRole']->{'TruckPlan'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 51) {

		$obj = json_decode($_POST['obj'], true);

		$part_no_plan = array();

		if (!isset($_FILES["upload"])) {
			echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
			closeDB($mysqli);
		}
		$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
		$fileName = $randomString . '_' . $_FILES["upload"]["name"];
		$tempName = $_FILES["upload"]["tmp_name"];
		if (move_uploaded_file($tempName, "../plan_file/" . $fileName)) {
			$file_info = pathinfo("../plan_file/" . $fileName);
			$myfile = fopen("../plan_file/" . $file_info['basename'], "r") or die("Unable to open file!");
			$data_file = fread($myfile, filesize("../plan_file/" . $file_info['basename']));
			$file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$allowed_ext = ['xls', 'csv', 'xlsx'];
			fclose($myfile);

			$mysqli->autocommit(FALSE);
			try {
				if (in_array($file_ext, $allowed_ext)) {
					$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('../plan_file/' . $fileName);
					$data = $spreadsheet->getActiveSheet()->toArray();
					$count = 0;

					$Route_Code = $obj['Route_Code1'];
					$transaction_ID = $obj['transaction_ID'];
					$Customer_Code = $obj['Customer_Code'];
					$truck_Control_No = $obj['truck_Control_No'];
					$truckNo_Date = $obj['truckNo_Date'];


					foreach ($data as $row) {

						if ($count > 0) {

							if ($row[0] != '') {

								$Part_No = $row[0];
								$SNP_Per_Pallet = $row[2];
								$Sum_Qty  = $row[3];

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
									BIN_TO_UUID(Part_ID,TRUE) AS Part_ID,
									BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID
								FROM 
									tbl_part_master 
								WHERE 
									Part_No = '$Part_No';";
								$re1 = sqlError($mysqli, __LINE__, $sql, 1);
								if ($re1->num_rows == 0) {
									throw new Exception('ไม่พบข้อมูล Part' . $Part_No . ' ' . __LINE__);
								}
								while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
									$Part_ID = $row['Part_ID'];
									$Supplier_ID = $row['Supplier_ID'];
								}

								$sql = "SELECT 
									BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
									Refer_ID,
									Qty - Actual_Qty AS Qty,
									tpm.SNP_Per_Pallet,
									CBM_Per_Pkg,
									tpm.Mass_Per_Pallet,
									CASE
										WHEN
											tpm.Group_pallet = 'Y'
										THEN
											ROUND((CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg,3)
										ELSE ROUND(CEILING($Sum_Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
									END AS CBM,
									CASE
										WHEN
											tpm.Group_pallet = 'Y'
										THEN ROUND(((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet))/2),3)
										ELSE ROUND((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet)),3)
									END AS WT,
									CASE
										WHEN
											tpm.Group_pallet = 'Y'
										THEN
											CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2
										ELSE CEILING($Sum_Qty / tpm.SNP_Per_Pallet)
									END AS Package_Qty
								FROM
									tbl_order torder
										INNER JOIN
									tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
										INNER JOIN
									tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
								WHERE
									BIN_TO_UUID(torder.Part_ID,TRUE) = '$Part_ID'
												AND DATE(torder.Pickup_Date) = DATE('$truckNo_Date')
										AND Command != 'DELETE'
										AND tpm.Active = 'Y'
										AND (Actual_Qty < Qty);";
								//exit($sql);
								$re1 = sqlError($mysqli, __LINE__, $sql, 1);
								if ($re1->num_rows > 0) {
									while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
										$Order_ID = $row['Order_ID'];
										$CBM = $row['CBM'];
										$Package_Qty = $row['Package_Qty'];
										$WT = $row['WT'];
									}


									$sql = "SELECT 
										BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
										BIN_TO_UUID(Route_ID,TRUE) AS Route_ID
									FROM 
										tbl_transaction_line
									WHERE 
										BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
											AND BIN_TO_UUID(Supplier_ID,TRUE) = '$Supplier_ID';";
									$re1 = sqlError($mysqli, __LINE__, $sql, 1);
									if ($re1->num_rows == 0) {
										throw new Exception('ไม่พบข้อมูล ' . __LINE__);
									}
									while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
										$transaction_Line_ID = $row['transaction_Line_ID'];
										$Route_ID = $row['Route_ID'];
									}

									$sql = "SELECT 
										BIN_TO_UUID(Order_ID,TRUE) AS Order_ID
									FROM 
										tbl_transaction_stop t1
									WHERE
									BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID'
										AND BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
									//exit($sql);
									$re1 = sqlError($mysqli, __LINE__, $sql, 1);
									if ($re1->num_rows > 0) {
										throw new Exception('มีรายการนี้อยู่แล้ว<br>ไม่สามารถบวกเพิ่มได้<br> กรุณาลบออเดอร์ฝั่ง Pickup Sheet ออกก่อน');
									}

									$sql = "SELECT 
										BIN_TO_UUID(Route_ID,TRUE) AS Route_ID,
										Weight
									FROM 
										tbl_route_master 
									WHERE 
									BIN_TO_UUID(Route_ID,TRUE) = '$Route_ID';";
									$re1 = sqlError($mysqli, __LINE__, $sql, 1);
									if ($re1->num_rows == 0) {
										throw new Exception('ไม่พบข้อมูล ' . __LINE__);
									}
									while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
										$Weight = $row['Weight'];
									}

									$sql = "INSERT INTO tbl_transaction_stop (
										transaction_Line_ID,
										Order_ID,
										Refer_ID,
										Package_ID,
										SNP_Per_Pallet,
										Package_Qty,
										CBM,
										WT,
										Part_ID,
										Plan_Qty,
										Actual_Qty,
										Creation_Date,
										Creation_DateTime,
										Created_By_ID,
										Last_Updated_Date,
										Last_Updated_DateTime,
										Updated_By_ID)
									SELECT
										UUID_TO_BIN('$transaction_Line_ID',TRUE),
										torder.Order_ID,
										torder.Refer_ID,
										tpm.Package_ID,
										tpm.SNP_Per_Pallet,
										$Package_Qty,
										$CBM,
										$WT,
										torder.Part_ID,
										torder.Qty,
										$Sum_Qty,
										curdate(),
										now(),
										$cBy,
										curdate(),
										now(),
										$cBy
									FROM
										tbl_order torder
											INNER JOIN
										tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
											INNER JOIN
										tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
											INNER JOIN
										tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
											INNER JOIN
										tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
									WHERE 
										BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
											AND BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
											AND BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID'
											AND tpm.Active = 'Y'
									ON DUPLICATE KEY UPDATE
										Package_Qty = $Package_Qty,
										CBM = $CBM,
										WT = $WT,
										tbl_transaction_stop.Actual_Qty = $Sum_Qty,
										Last_Updated_Date = curdate(),
										Last_Updated_DateTime = now(),
										Updated_By_ID = $cBy;";
									//exit($sql);
									sqlError($mysqli, __LINE__, $sql, 1);
									if ($mysqli->affected_rows == 0) {
										throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
									}

									$sql = "UPDATE tbl_transaction_line 
									SET 
										line_CBM = line_CBM+$CBM,
										line_Weight = line_Weight+$WT,
										Last_Updated_Date = curdate(),
										Last_Updated_DateTime = now(),
										Updated_By_ID = $cBy
									WHERE 
										BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
										AND BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
									//exit($sql);
									sqlError($mysqli, __LINE__, $sql, 1);
									if ($mysqli->affected_rows == 0) {
										throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
									}

									$sql = "SELECT 
										pus_No,
										line_Weight
									FROM 
										tbl_transaction_line
									WHERE 
										BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
									$re1 = sqlError($mysqli, __LINE__, $sql, 1);
									if ($re1->num_rows == 0) {
										throw new Exception('ไม่พบข้อมูล ' . __LINE__);
									}
									while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
										$line_Weight = $row['line_Weight'];
									}

									// if($line_Weight > $Weight){
									// 	echo '{"status":"server","mms":"เกิน","data":[]}';
									// }

									$sql = "SELECT 
										BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
										Actual_Qty
									FROM
										tbl_order
									WHERE
										BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
									$re1 = sqlError($mysqli, __LINE__, $sql, 1);
									if ($re1->num_rows == 0) {
										throw new Exception('ไม่พบข้อมูล ' . __LINE__);
									}
									while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
										$Actual_Qty = $row['Actual_Qty'];
									}

									$sql = "SELECT 
										BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
										Refer_ID,
										Actual_Qty
									FROM
										tbl_order torder
									WHERE
										BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
										AND ($Sum_Qty > (Qty-Actual_Qty) OR $Sum_Qty > Qty);";
									//exit($sql);
									$re1 = sqlError($mysqli, __LINE__, $sql, 1);
									if ($re1->num_rows > 0) {
										throw new Exception('ป้อนจำนวนเกิน ');
									}

									// echo($Actual_Qty);
									// exit();

									$sql = "UPDATE tbl_order 
									SET 
										Pick = 'Y',
										Actual_Qty = (Actual_Qty-$Actual_Qty)+($Actual_Qty+$Sum_Qty),
										Last_Updated_Date = curdate(),
										Last_Updated_DateTime = now(),
										Updated_By_ID = $cBy
									WHERE 
										BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
									//exit($sql);
									sqlError($mysqli, __LINE__, $sql, 1);
									if ($mysqli->affected_rows == 0) {
										throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
									}

									//exit($line_CBM);

									$sql = "UPDATE tbl_transaction_line 
									SET 
										line_CBM = line_CBM+$CBM,
										line_Weight = line_Weight+$WT,
										Last_Updated_Date = curdate(),
										Last_Updated_DateTime = now(),
										Updated_By_ID = $cBy
									WHERE 
										BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
											AND Status_Pickup = 'DELIVERY';";
									sqlError($mysqli, __LINE__, $sql, 1);
									if ($mysqli->affected_rows == 0) {
										throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
									}
								}
							}
						}
						$count++;
					}
				}

				// $all_part = join(", ", $part_no_plan) . "<br>";
				// echo $all_part;
				// exit();

				$mysqli->commit();
				//closeDBT($mysqli, 1, jsonRow($re1, true, 0));
				echo '{"status":"server","mms":"Upload สำเร็จ","data":[]}';
				closeDB($mysqli);
			} catch (Exception $e) {
				$mysqli->rollback();
				echo '{"status":"server","mms":"' . $e->getMessage() . '","sname":[]}';
				closeDB($mysqli);
			}
		} else echo json_encode(array('status' => 'server', 'mms' => 'ข้อมูลในไฟล์ไม่ถูกต้อง', 'sname' => array()));
	} else if ($type == 52) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>truckNo_Date:s:0:1',
			'obj=>Route_Code:s:0:1',
			'obj=>Route_Code1:s:0:1',
			'obj=>plan:s:0:1',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$error_txt = array();
			$num_order = 0;

			$text_plan = explode('\n', $plan);

			foreach ($text_plan as $row) {
				$list = explode('	', $row);
				// var_dump($list);
				$Part_No = $list[0];
				$Sum_Qty  = $list[1];

				$Sum_Qty = str_replace(' ', '', $Sum_Qty);

				if ($Sum_Qty == '' or $Sum_Qty == '-' or $Sum_Qty == 0) {
					continue;
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
					BIN_TO_UUID(Part_ID,TRUE) AS Part_ID,
					BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID
				FROM 
					tbl_part_master 
				WHERE 
					Part_No = '$Part_No';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล Part' . $Part_No . ' ' . __LINE__);
					// $txt = 'ไม่พบข้อมูล Part ' . $Part_No;
					// array_push($error_txt, $txt);
					// $num_order++;
					// continue;
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$Part_ID = $row['Part_ID'];
					$Supplier_ID = $row['Supplier_ID'];
				}


				$sql = "SELECT 
					BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
					BIN_TO_UUID(Route_ID,TRUE) AS Route_ID
				FROM 
					tbl_transaction_line
				WHERE 
					BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
						AND BIN_TO_UUID(Supplier_ID,TRUE) = '$Supplier_ID';";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					throw new Exception('ไม่พบข้อมูล ' . __LINE__);
				}
				while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
					$transaction_Line_ID = $row['transaction_Line_ID'];
					$Route_ID = $row['Route_ID'];
				}




				$sql = "WITH a AS (
					SELECT 
						BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
						Refer_ID,
						Qty - Actual_Qty AS Qty,
						tpm.SNP_Per_Pallet,
						CBM_Per_Pkg,
						tpm.Mass_Per_Pallet,
						CASE
							WHEN
								tpm.Group_pallet = 'Y'
							THEN
								ROUND((CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg,3)
							ELSE ROUND(CEILING($Sum_Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
						END AS CBM,
						CASE
							WHEN
								tpm.Group_pallet = 'Y'
							THEN ROUND(((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet))/2),3)
							ELSE ROUND((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet)),3)
						END AS WT,
						CASE
							WHEN
								tpm.Group_pallet = 'Y'
							THEN
								CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2
							ELSE CEILING($Sum_Qty / tpm.SNP_Per_Pallet)
						END AS Package_Qty
					FROM
						tbl_order torder
							INNER JOIN
						tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
							INNER JOIN
						tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
					WHERE
						BIN_TO_UUID(torder.Part_ID,TRUE) = '$Part_ID'
									AND DATE(torder.Pickup_Date) = DATE('$truckNo_Date')
									AND tpm.Active = 'Y'
							AND Command != 'DELETE'
							AND (Actual_Qty < Qty)
					) SELECT * FROM a ORDER BY Qty ASC;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows == 0) {
					$txt = 'ไม่พบออเดอร์ Part ' . $Part_No;
					array_push($error_txt, $txt);
					$num_order++;
				} else if ($re1->num_rows > 0) {

					$order_array = [];

					$sql = "WITH a AS (
					SELECT 
						BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
						Refer_ID,
						Qty - Actual_Qty AS Qty,
						tpm.SNP_Per_Pallet,
						CBM_Per_Pkg,
						tpm.Mass_Per_Pallet,
						CASE
							WHEN
								tpm.Group_pallet = 'Y'
							THEN
								ROUND((CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg,3)
							ELSE ROUND(CEILING($Sum_Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg,3)
						END AS CBM,
						CASE
							WHEN
								tpm.Group_pallet = 'Y'
							THEN ROUND(((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet))/2),3)
							ELSE ROUND((tpm.Mass_Per_Pallet*($Sum_Qty / tpm.SNP_Per_Pallet)),3)
						END AS WT,
						CASE
							WHEN
								tpm.Group_pallet = 'Y'
							THEN
								CEILING($Sum_Qty / tpm.SNP_Per_Pallet) / 2
							ELSE CEILING($Sum_Qty / tpm.SNP_Per_Pallet)
						END AS Package_Qty
					FROM
						tbl_order torder
							INNER JOIN
						tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
							INNER JOIN
						tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
					WHERE
						BIN_TO_UUID(torder.Part_ID,TRUE) = '$Part_ID'
									AND DATE(torder.Pickup_Date) = DATE('$truckNo_Date')
									AND tpm.Active = 'Y'
							AND Command != 'DELETE'
							AND (Actual_Qty < Qty)
					) SELECT * FROM a ORDER BY Qty DESC;";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows > 0) {
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$Order_ID = $row['Order_ID'];
							array_push($order_array, ['Order_ID' => $Order_ID]);
						}
					}
				}

				$old_qty = $Sum_Qty;
				$i = 0;
				$len_order = count($order_array);
				while ($i < $len_order) {
					$Order_ID = $order_array[$i]['Order_ID'];

					if ($old_qty > 0) {


						$sql = "WITH a AS (
						SELECT 
						BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
						Refer_ID,
						if((Qty - Actual_Qty) >= $Sum_Qty, $Sum_Qty, (Qty - Actual_Qty)) AS Qty,
						if((Qty - Actual_Qty) >= $Sum_Qty,((Qty - Actual_Qty)-$Sum_Qty),($Sum_Qty-(Qty - Actual_Qty))) as left_qty,
						tpm.SNP_Per_Pallet,
						CBM_Per_Pkg,
						tpm.Mass_Per_Pallet,
						tpm.Group_pallet
						FROM
						tbl_order torder
							INNER JOIN
						tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
							INNER JOIN
						tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
						WHERE
						BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
							AND Command != 'DELETE'
							AND tpm.Active = 'Y'
							AND (Actual_Qty < Qty)
						) SELECT *,
						CASE
							WHEN
								a.Group_pallet = 'Y'
							THEN
								ROUND((CEILING( a.Qty / a.SNP_Per_Pallet) / 2) * a.CBM_Per_Pkg,3)
							ELSE ROUND(CEILING( a.Qty / a.SNP_Per_Pallet) * a.CBM_Per_Pkg,3)
						END AS CBM,
						CASE
							WHEN
								a.Group_pallet = 'Y'
							THEN ROUND(((a.Mass_Per_Pallet*( a.Qty / a.SNP_Per_Pallet))/2),3)
							ELSE ROUND((a.Mass_Per_Pallet*( a.Qty / a.SNP_Per_Pallet)),3)
						END AS WT,
						CASE
							WHEN
								a.Group_pallet = 'Y'
							THEN
								CEILING( a.Qty / a.SNP_Per_Pallet) / 2
							ELSE CEILING( a.Qty / a.SNP_Per_Pallet)
						END AS Package_Qty
						FROM a;";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows > 0) {
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$Sum_Qty = $row['Qty'];
								$CBM = $row['CBM'];
								$Package_Qty = $row['Package_Qty'];
								$WT = $row['WT'];
								$left_qty = $row['left_qty'];
							}
						}


						/* $sql = "SELECT 
							BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
							Refer_ID,
							Actual_Qty
						FROM
							tbl_order torder
						WHERE
							BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
							AND ($left_qty > (Qty-Actual_Qty) OR $left_qty > Qty);";
						exit($sql);
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows > 0) {
							throw new Exception($Part_No . ' ป้อนจำนวนเกิน ');
						} */

						$sql = "SELECT 
							BIN_TO_UUID(Order_ID,TRUE) AS Order_ID
						FROM 
							tbl_transaction_stop t1
						WHERE
						BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID'
							AND BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
						//exit($sql);
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows > 0) {
							//throw new Exception('มีรายการนี้อยู่แล้ว<br>ไม่สามารถบวกเพิ่มได้<br> กรุณาลบออเดอร์ฝั่ง Pickup Sheet ออกก่อน');
							throw new Exception('มีรายการออเดอร์ซ้ำ');
							$txt = 'มีรายการออเดอร์ซ้ำ Part ' . $Part_No;
							// array_push($error_txt, $txt);
							// $num_order++;
							// continue;
						}

						$sql = "SELECT 
							BIN_TO_UUID(Route_ID,TRUE) AS Route_ID,
							Weight
						FROM 
							tbl_route_master 
						WHERE 
						BIN_TO_UUID(Route_ID,TRUE) = '$Route_ID';";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows == 0) {
							throw new Exception('ไม่พบข้อมูล ' . __LINE__);
						}
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$Weight = $row['Weight'];
						}

						$sql = "INSERT INTO tbl_transaction_stop (
							transaction_Line_ID,
							Order_ID,
							Refer_ID,
							Package_ID,
							SNP_Per_Pallet,
							Package_Qty,
							CBM,
							WT,
							Part_ID,
							Plan_Qty,
							Actual_Qty,
							Creation_Date,
							Creation_DateTime,
							Created_By_ID,
							Last_Updated_Date,
							Last_Updated_DateTime,
							Updated_By_ID)
						SELECT
							UUID_TO_BIN('$transaction_Line_ID',TRUE),
							torder.Order_ID,
							torder.Refer_ID,
							tpm.Package_ID,
							tpm.SNP_Per_Pallet,
							$Package_Qty,
							$CBM,
							$WT,
							torder.Part_ID,
							torder.Qty,
							$Sum_Qty,
							curdate(),
							now(),
							$cBy,
							curdate(),
							now(),
							$cBy
						FROM
							tbl_order torder
								INNER JOIN
							tbl_supplier_master tsm ON torder.Supplier_ID = tsm.Supplier_ID
								INNER JOIN
							tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID
								INNER JOIN
							tbl_transaction_line ttl ON torder.Supplier_ID = ttl.Supplier_ID
								INNER JOIN
							tbl_transaction tts ON ttl.transaction_ID = tts.transaction_ID
						WHERE 
							BIN_TO_UUID(torder.Order_ID,TRUE) = '$Order_ID'
								AND BIN_TO_UUID(tts.transaction_ID,TRUE) = '$transaction_ID'
								AND BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID'
								AND tpm.Active = 'Y'
						ON DUPLICATE KEY UPDATE
							Package_Qty = $Package_Qty,
							CBM = $CBM,
							WT = $WT,
							tbl_transaction_stop.Actual_Qty = $Sum_Qty,
							Last_Updated_Date = curdate(),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy;";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}

						$sql = "UPDATE tbl_transaction_line 
						SET 
							line_CBM = line_CBM+$CBM,
							line_Weight = line_Weight+$WT,
							Last_Updated_Date = curdate(),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy
						WHERE 
							BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
							AND BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}


						$sql = "SELECT 
							pus_No,
							line_Weight
						FROM 
							tbl_transaction_line
						WHERE 
							BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows == 0) {
							throw new Exception('ไม่พบข้อมูล ' . __LINE__);
						}
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$line_Weight = $row['line_Weight'];
						}

						$sql = "SELECT 
							BIN_TO_UUID(Order_ID, TRUE) AS Order_ID,
							Actual_Qty
						FROM
							tbl_order
						WHERE
							BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows == 0) {
							throw new Exception('ไม่พบข้อมูล ' . __LINE__);
						}
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$Actual_Qty = $row['Actual_Qty'];
						}

						$sql = "UPDATE tbl_order 
						SET 
							Pick = 'Y',
							Actual_Qty = (Actual_Qty-$Actual_Qty)+($Actual_Qty+$Sum_Qty),
							Last_Updated_Date = curdate(),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy
						WHERE 
							BIN_TO_UUID(Order_ID,TRUE) = '$Order_ID';";
						//exit($sql);
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}

						//exit($line_CBM);

						$sql = "UPDATE tbl_transaction_line 
						SET 
							line_CBM = line_CBM+$CBM,
							line_Weight = line_Weight+$WT,
							Last_Updated_Date = curdate(),
							Last_Updated_DateTime = now(),
							Updated_By_ID = $cBy
						WHERE 
							BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID'
								AND Status_Pickup = 'DELIVERY';";
						sqlError($mysqli, __LINE__, $sql, 1);
						if ($mysqli->affected_rows == 0) {
							throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
						}

						$old_qty = $old_qty - $Sum_Qty;
						$Sum_Qty = $old_qty;
					}
					$i++;
				}
			}

			$mysqli->commit();

			if ($num_order > 0) {
				$text = join("<br>", $error_txt);
				closeDBT($mysqli, 1, $text);
			} else {
				closeDBT($mysqli, 1, 'เพิ่มสำเร็จ');
				//closeDBT($mysqli, 1, jsonRow($re1, true, 0));
			}
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else if ($type == 53) {

		$dataParams = array(
			'obj',
			'obj=>transaction_ID:s:0:1',
			'obj=>truck_Control_No:s:0:1',
			'obj=>Driver_Name:s:0:1',
			'obj=>Truck:s:0:1',
			'obj=>Route_Code:s:0:1',
			'obj=>amount_truck:s:0:0',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$mysqli->autocommit(FALSE);
		try {

			$explode = explode(" | ", $Route_Code);
			$Route_Code1 = $explode[0];

			$explode1 = explode(" ", $Route_Code1);
			$Route_Code = $explode1[0];

			$sql = "SELECT 
				BIN_TO_UUID(transaction_ID,TRUE) as transaction_ID
			FROM 
				tbl_transaction 
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			//exit($sql);
			$re1 = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re1->num_rows == 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}

			$explode = explode(' | ', $Truck);
			$Truck_Number = $explode[0];
			$Truck_Type = $explode[1];

			$Truck_ID = getTruckID($mysqli, $Truck_Number, $Truck_Type);
			$Driver_ID = getDriverID($mysqli, $Driver_Name);

			$sql = "UPDATE tbl_transaction 
			SET 
				Truck_ID = UUID_TO_BIN('$Truck_ID',TRUE),
				Truck_Number = '$Truck_Number',
				Truck_Type = '$Truck_Type',
				Driver_ID = UUID_TO_BIN('$Driver_ID',TRUE),
				Last_Updated_Date = curdate(),
				Last_Updated_DateTime = now(),
				Updated_By_ID = $cBy,
				amount_truck = $amount_truck
			WHERE 
				BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
			sqlError($mysqli, __LINE__, $sql, 1);
			if ($mysqli->affected_rows == 0) {
				throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
			}

			$mysqli->commit();

			closeDBT($mysqli, 1, jsonRow($re1, true, 0));
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
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
			$valueV2 = explode('|', $valueV);
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
