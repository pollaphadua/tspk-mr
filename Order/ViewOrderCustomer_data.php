<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'ViewOrderCustomer'})) {
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
} else if ($_SESSION['xxxRole']->{'ViewOrderCustomer'}[0] == 0) {
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
	} else if ($type == 2) {

		$dataParams = array(
			'obj',
			'obj=>Start_Date:s:5',
			'obj=>Stop_Date:s:5',
		);
		$chkPOST = checkParamsAndDelare($_POST, $dataParams, $mysqli);
		if (count($chkPOST) > 0) closeDBT($mysqli, 2, join('<br>', $chkPOST));

		$data = ['Start_Date' => $Start_Date, 'Stop_Date' => $Stop_Date, 'sqlWhere' => $sqlWhere];

		$mysqli->autocommit(FALSE);
		try {

			$sql = getdata_excel($mysqli, $data);
			$re = sqlError($mysqli, __LINE__, $sql, 1);
			if ($re->num_rows === 0) {
				throw new Exception('ไม่พบข้อมูล ' . __LINE__);
			}
			$dataArray = array();
			while ($row = $re->fetch_assoc()) {
				$dataArray[] = $row;
			}
			include('excel/excel_order.php');

			$mysqli->commit();

			closeDBT($mysqli, 1, $filename);
		} catch (Exception $e) {
			$mysqli->rollback();
			closeDBT($mysqli, 2, $e->getMessage());
		}
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
	if ($_SESSION['xxxRole']->{'ViewOrderCustomer'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 11) {
	} else if ($type == 12) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
	if ($_SESSION['xxxRole']->{'ViewOrderCustomer'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 21) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
	if ($_SESSION['xxxRole']->{'ViewOrderCustomer'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 31) {
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
	if ($_SESSION['xxxRole']->{'ViewOrderCustomer'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if ($type == 41) {
		$sql = "SELECT 
			BIN_TO_UUID(torder.Order_ID,TRUE) AS Order_ID
			torder.Refer_ID,
			torder.Pickup_Date,
			BIN_TO_UUID(tpm.Part_ID.TRUE) AS Part_ID,
			tpm.Part_No,
			tpm.Part_Name,
			BIN_TO_UUID(tsm.Suppiler_ID,TRUE) AS Supplier_ID,
			tsm.Suppiler_Code,
			tsm.Supplier_Name_Short,
			tsm.Suppiler_Name,
			torder.Qty,
			torder.UM,
			torder.PO_No,
			torder.PO_Line,
			torder.PO_Release,
			torder.Command,
			tpm.SNP_Per_Pallet,
			tpm.CBM_Per_Pkg,
			tpm.Mass_per_Pallet
		FROM
			tbl_order torder
				INNER JOIN 
			tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID - tpm.Suppiler_ID
				INNER JOIN
			tbl_suppiler_master tsm ON torder.Suppiler_ID = tsm.Suppiler_ID
		WHERE
			torder.Command != 'DELETE';";
	} else closeDBT($mysqli, 2, 'TYPE ERROR');
} else closeDBT($mysqli, 2, 'TYPE ERROR');

function getData($mysqli, $data, $line)
{
	$where = [];
	$where[] = "DATE_FORMAT(torder.Pickup_Date, '%Y-%m-%d') between DATE_FORMAT('$data[Start_Date]', '%Y-%m-%d') and DATE_FORMAT('$data[Stop_Date]', '%Y-%m-%d')";

	$sqlWhere = join(' and ', $where);
	$sql = "WITH a AS(
	SELECT 
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
		torder.Actual_Qty,
		torder.UM,
		torder.PO_No,
		torder.PO_Line,
		torder.PO_Release,
		torder.Command,
		tpm.Project,
		tpm.SNP_Per_Pallet,
		CONVERT(ROUND(tpm.CBM_Per_Pkg,3),CHAR) AS CBM_Per_Pkg,
		CONVERT(tpm.Mass_Per_Pallet,CHAR) AS Mass_Per_Pallet,
		
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN CONVERT(ROUND((((torder.Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3),CHAR)
			ELSE CONVERT(ROUND(((torder.Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg),3),CHAR)
		END AS CBM_All,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
				THEN ROUND(((tpm.Mass_Per_Pallet*(torder.Qty / tpm.SNP_Per_Pallet))/2),2)
				ELSE ROUND((tpm.Mass_Per_Pallet*(torder.Qty / tpm.SNP_Per_Pallet)),2)
		END AS WT_All,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN
				ROUND(((torder.Qty / tpm.SNP_Per_Pallet) / 2),2)
			ELSE CEILING((torder.Qty / tpm.SNP_Per_Pallet))
		END AS Package_Qty_All,
		
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN CONVERT(ROUND((((torder.Actual_Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3),CHAR)
			ELSE CONVERT(ROUND(((torder.Actual_Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg),3),CHAR)
		END AS CBM_Actual_Plan,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
				THEN ROUND(((tpm.Mass_Per_Pallet*(torder.Actual_Qty / tpm.SNP_Per_Pallet))/2),2)
				ELSE ROUND((tpm.Mass_Per_Pallet*(torder.Actual_Qty / tpm.SNP_Per_Pallet)),2)
		END AS WT_Actual_Plan,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN
				ROUND(((torder.Actual_Qty / tpm.SNP_Per_Pallet) / 2),2)
			ELSE CEILING((torder.Actual_Qty / tpm.SNP_Per_Pallet))
		END AS Package_Qty_Actual_Plan,
		torder.Creation_DateTime,
        t1.Customer_Code
	FROM
		tbl_order torder
			INNER JOIN
		tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tpm.Supplier_ID
			INNER JOIN
		tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID
			INNER JOIN
		tbl_customer_master t1 ON tpm.Customer_ID = t1.Customer_ID
		WHERE 
			($sqlWhere)
			AND torder.Command != 'DELETE'
			AND torder.Qty != 0
			AND ($data[sqlWhere])
			AND tpm.Active = 'Y'
			AND tsm.Status = 'ACTIVE'
		ORDER BY torder.Pickup_Date ASC, torder.Refer_ID ASC)
	SELECT 
		a.*, 
		CASE
			WHEN
				length(substring_index(CBM_Per_Pkg,'.',-1)) = 2 AND substring_index(CBM_Per_Pkg,'.',-1) != 0
				AND if(CBM_Per_Pkg LIKE '%.%',1,0) = 1 AND CBM_Per_Pkg != 0
			THEN
				concat(CBM_Per_Pkg,'0')
			WHEN
				length(substring_index(CBM_Per_Pkg,'.',-1)) = 1 AND substring_index(CBM_Per_Pkg,'.',-1) != 0
				AND if(CBM_Per_Pkg LIKE '%.%',1,0) = 1 AND CBM_Per_Pkg != 0
			THEN
				concat(CBM_Per_Pkg,'00')
			WHEN
				if(CBM_Per_Pkg LIKE '%.%',1,0) = 0
				AND CBM_Per_Pkg != 0
			THEN
				concat(CBM_Per_Pkg,'.000')
			WHEN
				substring_index(CBM_Per_Pkg,'.',-1) = 0
				AND if(CBM_Per_Pkg LIKE '%.%',1,0) = 1
				AND CBM_Per_Pkg != 0
			THEN
				CBM_Per_Pkg
			WHEN
				if(CBM_Per_Pkg LIKE '%.%',1,0) = 1
				AND CBM_Per_Pkg != 0
			THEN
				CBM_Per_Pkg
			WHEN
				CBM_Per_Pkg = 0
			THEN
				CBM_Per_Pkg
			ELSE 
				CBM_Per_Pkg
		END AS CBM_Per_Pkg1,

		CASE
			WHEN
				length(substring_index(CBM_Actual_Plan,'.',-1)) = 2 AND substring_index(CBM_Actual_Plan,'.',-1) != 0
				AND if(CBM_Actual_Plan LIKE '%.%',1,0) = 1 AND CBM_Actual_Plan != 0
			THEN
				concat(CBM_Actual_Plan,'0')
			WHEN
				length(substring_index(CBM_Actual_Plan,'.',-1)) = 1 AND substring_index(CBM_Actual_Plan,'.',-1) != 0
				AND if(CBM_Actual_Plan LIKE '%.%',1,0) = 1 AND CBM_Actual_Plan != 0
			THEN
				concat(CBM_Actual_Plan,'00')
			WHEN
				if(CBM_Actual_Plan LIKE '%.%',1,0) = 0
				AND CBM_Actual_Plan != 0
			THEN
				concat(CBM_Actual_Plan,'.000')
			WHEN
				substring_index(CBM_Actual_Plan,'.',-1) = 0
				AND if(CBM_Actual_Plan LIKE '%.%',1,0) = 1
				AND CBM_Actual_Plan != 0
			THEN
				CBM_Actual_Plan
			WHEN
				if(CBM_Actual_Plan LIKE '%.%',1,0) = 1
				AND CBM_Actual_Plan != 0
			THEN
				CBM_Actual_Plan
			WHEN
				CBM_Actual_Plan = 0
			THEN
				CBM_Actual_Plan
			ELSE 
				CBM_Actual_Plan
		END AS CBM_Actual_Plan1,
		CASE
			WHEN
				length(substring_index(CBM_All,'.',-1)) = 2 AND substring_index(CBM_All,'.',-1) != 0
				AND if(CBM_All LIKE '%.%',1,0) = 1 AND CBM_All != 0
			THEN
				concat(CBM_All,'0')
			WHEN
				length(substring_index(CBM_All,'.',-1)) = 1 AND substring_index(CBM_All,'.',-1) != 0
				AND if(CBM_All LIKE '%.%',1,0) = 1 AND CBM_All != 0
			THEN
				concat(CBM_All,'00')
			WHEN
				if(CBM_All LIKE '%.%',1,0) = 0
				AND CBM_All != 0
			THEN
				concat(CBM_All,'.000')
			WHEN
				substring_index(CBM_All,'.',-1) = 0
				AND if(CBM_All LIKE '%.%',1,0) = 1
				AND CBM_All != 0
			THEN
				CBM_All
			WHEN
				if(CBM_All LIKE '%.%',1,0) = 1
				AND CBM_All != 0
			THEN
				CBM_All
			WHEN
				CBM_All = 0
			THEN
				CBM_All
			ELSE 
				CBM_All
		END AS CBM_All1
		FROM a;";
	// exit($sql);
	return sqlError($mysqli, $line, $sql, 1);
}

function getdata_excel($mysqli, $data)
{
	$where = [];
	$where[] = "DATE_FORMAT(torder.Pickup_Date, '%Y-%m-%d') between DATE_FORMAT('$data[Start_Date]', '%Y-%m-%d') and DATE_FORMAT('$data[Stop_Date]', '%Y-%m-%d')";

	$sqlWhere = join(' and ', $where);
	$sql = "WITH a AS(
	SELECT 
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
		torder.Actual_Qty,
		torder.UM,
		torder.PO_No,
		torder.PO_Line,
		torder.PO_Release,
		torder.Command,
		tpm.Project,
		tpm.SNP_Per_Pallet,
		CONVERT(ROUND(tpm.CBM_Per_Pkg,3),CHAR) AS CBM_Per_Pkg,
		CONVERT(tpm.Mass_Per_Pallet,CHAR) AS Mass_Per_Pallet,
		
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN CONVERT(ROUND((((torder.Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3),CHAR)
			ELSE CONVERT(ROUND(((torder.Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg),3),CHAR)
		END AS CBM_All,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
				THEN ROUND(((tpm.Mass_Per_Pallet*(torder.Qty / tpm.SNP_Per_Pallet))/2),2)
				ELSE ROUND((tpm.Mass_Per_Pallet*(torder.Qty / tpm.SNP_Per_Pallet)),2)
		END AS WT_All,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN
				ROUND(((torder.Qty / tpm.SNP_Per_Pallet) / 2),2)
			ELSE CEILING((torder.Qty / tpm.SNP_Per_Pallet))
		END AS Package_Qty_All,
		
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN CONVERT(ROUND((((torder.Actual_Qty / tpm.SNP_Per_Pallet) / 2) * tpm.CBM_Per_Pkg),3),CHAR)
			ELSE CONVERT(ROUND(((torder.Actual_Qty / tpm.SNP_Per_Pallet) * tpm.CBM_Per_Pkg),3),CHAR)
		END AS CBM_Actual_Plan,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
				THEN ROUND(((tpm.Mass_Per_Pallet*(torder.Actual_Qty / tpm.SNP_Per_Pallet))/2),2)
				ELSE ROUND((tpm.Mass_Per_Pallet*(torder.Actual_Qty / tpm.SNP_Per_Pallet)),2)
		END AS WT_Actual_Plan,
		CASE
			WHEN
				tpm.Group_pallet = 'Y'
			THEN
				ROUND(((torder.Actual_Qty / tpm.SNP_Per_Pallet) / 2),2)
			ELSE CEILING((torder.Actual_Qty / tpm.SNP_Per_Pallet))
		END AS Package_Qty_Actual_Plan,
		torder.Creation_DateTime,
        t1.Customer_Code
	FROM
		tbl_order torder
			INNER JOIN
		tbl_part_master tpm ON torder.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tpm.Supplier_ID
			INNER JOIN
		tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID
			INNER JOIN
		tbl_customer_master t1 ON tpm.Customer_ID = t1.Customer_ID
		WHERE 
			($sqlWhere)
			AND torder.Command != 'DELETE'
			AND torder.Qty != 0
			AND ($data[sqlWhere])
			AND tpm.Active = 'Y'
			AND tsm.Status = 'ACTIVE'
		ORDER BY torder.Pickup_Date ASC, torder.Refer_ID ASC)
	SELECT 
		a.*, 
		CASE
			WHEN
				length(substring_index(CBM_Per_Pkg,'.',-1)) = 2 AND substring_index(CBM_Per_Pkg,'.',-1) != 0
				AND if(CBM_Per_Pkg LIKE '%.%',1,0) = 1 AND CBM_Per_Pkg != 0
			THEN
				concat(CBM_Per_Pkg,'0')
			WHEN
				length(substring_index(CBM_Per_Pkg,'.',-1)) = 1 AND substring_index(CBM_Per_Pkg,'.',-1) != 0
				AND if(CBM_Per_Pkg LIKE '%.%',1,0) = 1 AND CBM_Per_Pkg != 0
			THEN
				concat(CBM_Per_Pkg,'00')
			WHEN
				if(CBM_Per_Pkg LIKE '%.%',1,0) = 0
				AND CBM_Per_Pkg != 0
			THEN
				concat(CBM_Per_Pkg,'.000')
			WHEN
				substring_index(CBM_Per_Pkg,'.',-1) = 0
				AND if(CBM_Per_Pkg LIKE '%.%',1,0) = 1
				AND CBM_Per_Pkg != 0
			THEN
				CBM_Per_Pkg
			WHEN
				if(CBM_Per_Pkg LIKE '%.%',1,0) = 1
				AND CBM_Per_Pkg != 0
			THEN
				CBM_Per_Pkg
			WHEN
				CBM_Per_Pkg = 0
			THEN
				CBM_Per_Pkg
			ELSE 
				CBM_Per_Pkg
		END AS CBM_Per_Pkg1,

		CASE
			WHEN
				length(substring_index(CBM_Actual_Plan,'.',-1)) = 2 AND substring_index(CBM_Actual_Plan,'.',-1) != 0
				AND if(CBM_Actual_Plan LIKE '%.%',1,0) = 1 AND CBM_Actual_Plan != 0
			THEN
				concat(CBM_Actual_Plan,'0')
			WHEN
				length(substring_index(CBM_Actual_Plan,'.',-1)) = 1 AND substring_index(CBM_Actual_Plan,'.',-1) != 0
				AND if(CBM_Actual_Plan LIKE '%.%',1,0) = 1 AND CBM_Actual_Plan != 0
			THEN
				concat(CBM_Actual_Plan,'00')
			WHEN
				if(CBM_Actual_Plan LIKE '%.%',1,0) = 0
				AND CBM_Actual_Plan != 0
			THEN
				concat(CBM_Actual_Plan,'.000')
			WHEN
				substring_index(CBM_Actual_Plan,'.',-1) = 0
				AND if(CBM_Actual_Plan LIKE '%.%',1,0) = 1
				AND CBM_Actual_Plan != 0
			THEN
				CBM_Actual_Plan
			WHEN
				if(CBM_Actual_Plan LIKE '%.%',1,0) = 1
				AND CBM_Actual_Plan != 0
			THEN
				CBM_Actual_Plan
			WHEN
				CBM_Actual_Plan = 0
			THEN
				CBM_Actual_Plan
			ELSE 
				CBM_Actual_Plan
		END AS CBM_Actual_Plan1,
		CASE
			WHEN
				length(substring_index(CBM_All,'.',-1)) = 2 AND substring_index(CBM_All,'.',-1) != 0
				AND if(CBM_All LIKE '%.%',1,0) = 1 AND CBM_All != 0
			THEN
				concat(CBM_All,'0')
			WHEN
				length(substring_index(CBM_All,'.',-1)) = 1 AND substring_index(CBM_All,'.',-1) != 0
				AND if(CBM_All LIKE '%.%',1,0) = 1 AND CBM_All != 0
			THEN
				concat(CBM_All,'00')
			WHEN
				if(CBM_All LIKE '%.%',1,0) = 0
				AND CBM_All != 0
			THEN
				concat(CBM_All,'.000')
			WHEN
				substring_index(CBM_All,'.',-1) = 0
				AND if(CBM_All LIKE '%.%',1,0) = 1
				AND CBM_All != 0
			THEN
				CBM_All
			WHEN
				if(CBM_All LIKE '%.%',1,0) = 1
				AND CBM_All != 0
			THEN
				CBM_All
			WHEN
				CBM_All = 0
			THEN
				CBM_All
			ELSE 
				CBM_All
		END AS CBM_All1
		FROM a;";
	// exit($sql);
	return $sql;
}

$mysqli->close();
exit();
