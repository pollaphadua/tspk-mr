<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT 
	BIN_TO_UUID(tts.transaction_ID,TRUE) as transaction_ID,
	tts.truck_Control_No,
	SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
	tts.truckNo_Date,
	-- tts.Route_Code,
	if(trm.route_special = 'Y' OR trm.route_special = 'N', tts.Route_Code, CONCAT(tts.Route_Code,' ', trm.route_special)) as Route_Code,
	tts.stop_Date,
	tts.trip_Number,
	tts.total_Stop,
	tts.mile_Start,
	tts.mile_End,
	tts.Truck_Number,
	tts.Truck_Type,
	tdm.Driver_Name,
	tts.tran_status,
	torder.Pickup_Date,
	ttl.pus_No,
	SUBSTRING(ttl.pus_No, 1, 13)as pus_No_show,
	DATE_FORMAT(ttl.planin_time, '%Y-%m-%d %H:%i') AS pus_Date,
	tranIn.planin_time AS Delivery_time,
	tsm.Supplier_Name_Short,
	tsm.Supplier_Name,
	ttl.sequence_Stop,
	ttl.status,
	CONVERT(ttl.line_CBM, CHAR) AS line_CBM,
	tdm.Driver_Name,
	tdm.Phone,
	tts.total_Stop,
	tpm.Project,
	trm.route_special
	FROM
		tbl_transaction tts
		INNER JOIN
		tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		INNER JOIN
		tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID AND tstop.order_status = 'plan'
		INNER JOIN
		tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
		INNER JOIN
		tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
		INNER JOIN
		tbl_order torder ON tstop.Order_ID = torder.Order_ID
		LEFT JOIN
		tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
		LEFT JOIN
	tbl_part_master tpm ON tstop.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tpm.Supplier_ID
	,
	LATERAL
	(
	SELECT 
	DATE_FORMAT(ttl2.planin_time, '%Y-%m-%d %H:%i') AS planin_time,
	BIN_TO_UUID(tts2.transaction_ID,TRUE) as transaction_ID
	FROM
	tbl_transaction tts2
		LEFT JOIN
	tbl_transaction_line ttl2 ON tts2.transaction_ID = ttl2.transaction_ID
	WHERE 
	tts2.truck_Control_No = tts.truck_Control_No
	AND ttl2.Status_Pickup = 'DELIVERY') 
	AS tranIn
WHERE
	ttl.pus_No = '$doc'
	AND tts.tran_status != 'PENDING'
GROUP BY ttl.pus_No
ORDER BY ttl.sequence_Stop, ttl.pus_No;";


$q1  .= "SELECT 
	tts.truck_Control_No,
	SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
	tts.truckNo_Date,
	-- tts.Route_Code,
	if(trm.route_special = 'Y' OR trm.route_special = 'N', tts.Route_Code, CONCAT(tts.Route_Code,' ', trm.route_special)) as Route_Code,
	DATE_FORMAT(tts.start_Date, '%Y-%m-%d %H:%i') AS start_Date,
	DATE_FORMAT(tts.start_Date, '%H:%i') AS start_time,
	DATE_FORMAT(tts.actual_start_Date, '%Y-%m-%d') AS actual_start_Date,
	DATE_FORMAT(tts.actual_start_Date, '%H:%i') AS actual_start_Time,
	DATE_FORMAT(ttl.planin_time, '%Y-%m-%d %H:%i') AS pus_Date,
	tranIn.planin_time AS Delivery_time,
	tts.stop_Date,
	tts.trip_Number,
	tts.total_Stop,
	tts.mile_Start,
	tts.mile_End,
	tts.Truck_Number,
	tts.Truck_Type,
	tdm.Driver_Name,
	tts.tran_status,
	ttl.pus_No,
	SUBSTRING(ttl.pus_No, 1, 13)as pus_No_show,
	torder.Pickup_Date,
	tsm.Supplier_Name_Short,
	tsm.Supplier_Name,
	ttl.sequence_Stop,
	ttl.status,
	CONVERT(ttl.line_CBM, CHAR) AS line_CBM,
	tstop.Refer_ID,
	torder.Part_No,
	tpm.Part_Name,
	tstop.Plan_Qty,
	tstop.Actual_Qty,
	if(tstop.Package_Qty = 0,
    CASE WHEN tpm.Group_pallet = 'Y' 
	THEN ROUND((CEILING(tstop.Actual_Qty) / tpm.SNP_Per_Pallet) / 2,2)
	ELSE FORMAT(ROUND(tstop.Actual_Qty / tpm.SNP_Per_Pallet,0), 0) END , FORMAT(tstop.Package_Qty,0)) Package_Qty,
	CONVERT(tstop.CBM, CHAR) AS CBM,
	tstop.WT,
	tstop.SNP_Per_Pallet,
	tpack.Packaging,
	tpack.Package_Type,
	tdm.Driver_Name,
	tdm.Phone,
	tpm.Project,
	trm.route_special
FROM
	tbl_transaction tts
		INNER JOIN
	tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		INNER JOIN
	tbl_transaction_stop tstop ON ttl.transaction_Line_ID = tstop.transaction_Line_ID AND tstop.order_status = 'plan'
		INNER JOIN
	tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
		INNER JOIN
	tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
		INNER JOIN
	tbl_package_master tpack ON tstop.Package_ID = tpack.Package_ID
		INNER JOIN
	tbl_order torder ON tstop.Order_ID = torder.Order_ID
		LEFT JOIN
	tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
		LEFT JOIN
		tbl_part_master tpm ON tstop.Part_ID = tpm.Part_ID AND torder.Supplier_ID = tsm.Supplier_ID,
	LATERAL
	(
	SELECT 
		DATE_FORMAT(ttl2.planin_time, '%Y-%m-%d %H:%i') AS planin_time,
		BIN_TO_UUID(tts2.transaction_ID,TRUE) as transaction_ID
		FROM
	tbl_transaction tts2
			LEFT JOIN
		tbl_transaction_line ttl2 ON tts2.transaction_ID = ttl2.transaction_ID
	WHERE 
		tts2.truck_Control_No = tts.truck_Control_No
		AND ttl2.Status_Pickup = 'DELIVERY') 
	AS tranIn
WHERE
ttl.pus_No = '$doc'
	AND tts.tran_status != 'PENDING'
ORDER BY ttl.sequence_Stop, ttl.pus_No, tpm.Product_Code, tstop.transaction_stop_ID;";

//exit($q1);


if (!$mysqli->multi_query($q1)) {
	echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
}
do {
	if ($res = $mysqli->store_result()) {
		array_push($dataset, $res->fetch_all(MYSQLI_ASSOC));
		$res->free();
	}
} while ($mysqli->more_results() && $mysqli->next_result());
$headerData = $dataset[0];
$detailData = $dataset[1];

class PDF extends PDF_Code128
{
	function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
	{
		parent::__construct($orientation, $unit, $format);
		$this->AliasNbPages();
	}
	public function setHeaderData($v)
	{
		$this->headerData = $v;
	}
	public function setInstance($v)
	{
		$this->instance = $v;
	}
	function Header()
	{
		$v = $this->headerData;
		$this->SetY(13);
		$header = new easyTable($this->instance, '%{25, 55, 10, 10,}', 'border:0;font-family:THSarabun;');
		$header->easyCell('ALBATROSS LOGISTICS CO., LTD.', 'valign:B;align:L;font-size:16; font-style:B;');
		$header->easyCell('', 'valign:M;align:L;font-size:18; font-style:B;');
		$header->easyCell('', 'img:images/tspk.jpg, w20;align:C;rowspan:2;border:B;', '');
		$header->easyCell('', 'img:images/abt-logo.gif, w20;align:C;rowspan:2;border:B;', '');


		$date = date_create($v[0]['truckNo_Date']);
		$truckNo_Date = date_format($date, "d/m/Y");

		$suppiler1 = $v[0]['Supplier_Name_Short'];
		$suppiler2 = str_replace('-', '', $suppiler1);
		$suppiler = str_replace(' ', '', $suppiler2);

		$Customer_Name = substr($v[0]['truck_Control_No'], 13);

		if ($Customer_Name == '') {
			$Customer_Name = 'TSPK';
		}elseif ($Customer_Name == 'TSPK-C'){
			$Customer_Name = 'TSPK';
		}elseif ($Customer_Name == 'TSPK-L'){
			$Customer_Name = 'TSPKK';
		}elseif ($Customer_Name == 'TSPK-BP'){
			$Customer_Name = 'TSPKBP';
		}

		$truck = $v[0]['Truck_Number'];
		if (preg_match('/\p{Thai}/u', $truck) === 1) {
			$truck = 'N/A';
		}

		$qr_code =  $truckNo_Date . " " . $v[0]['truck_Control_No_show'] . " " . $v[0]['pus_No_show'] . " " . $suppiler . "-" . $Customer_Name . " " . $truck . " 1 " . $v[0]['line_CBM'];

		//echo ($qr_code);


		$this->instance->Code128(55, 5, $qr_code, 200, 8);


		/* //gen qr_code
		$ip_server = $_SERVER['SERVER_NAME'];
		$code = 'ABT001';
		$this->instance->Image('http://' . $ip_server . "/tspk-mr/print/qr_generator.php?code=" . $code, 145, 10, 20, 20, "png");
 */


		$header->printRow();
		//$header = new easyTable($this->instance, '%{25,55,20}', 'border:0;font-family:THSarabun;');
		$header->easyCell('336/7 MOO 7 BOWIN, SRIRACHA CHONBURI 20230', 'valign:T;align:L;font-size:10;border:B;');
		$header->easyCell('', 'valign:T;align:C;font-size:12;border:B;');
		$header->easyCell('', 'valign:M;align:L;font-size:18; font-style:B;border:0;');
		$header->easyCell('', 'valign:M;align:L;font-size:18; font-style:B;border:0;');
		$header->printRow();
		//$header->endTable(2);


		if ($v[0]['Truck_Number'] == 'N/A') {
			$Truck_Number = '';
		} else {
			$Truck_Number = $v[0]['Truck_Number'];
		}
		if ($v[0]['Driver_Name'] == 'N/A') {
			$Driver_Name = '';
		} else {
			$Driver_Name = $v[0]['Driver_Name'];
		}

		$Customer = substr($v[0]['pus_No'], 13);

		if ($Customer == '') {
			$Customer = 'TSPK-C';
		}

		$route_special = $v[0]['route_special'];

		if ($Customer == 'TSPK-L' && $route_special == 'Y') {
			$Truck_Type = '';
			$Delivery_time = '';
			$pus_Date = '';
		} else {
			$Truck_Type = $v[0]['Truck_Type'];
			$Delivery_time = $v[0]['Delivery_time'];
			$pus_Date = $v[0]['pus_Date'];
		}

		$headerdetail = new easyTable($this->instance, '%{40,20,10,10,20}', 'border:0;font-family:THSarabun;');
		$headerdetail->easyCell(utf8Th(''), 'valign:M;align:L;font-size:12; font-style:B;');
		$headerdetail->easyCell(utf8Th('PICKUP SHEET'), 'valign:M;align:C;font-size:24; font-style:B;');
		$headerdetail->easyCell(utf8Th('Pus No. : '), 'valign:M;align:R;font-size:12; font-style:B;');
		$headerdetail->easyCell(utf8Th($v[0]['pus_No_show']), 'valign:M;align:R;font-size:12; font-style:B;');
		$headerdetail->easyCell(utf8Th(''), 'valign:M;align:L;font-size:12; font-style:B;');
		$this->instance->Code128(234, 31, $v[0]['pus_No_show'], 50, 7);
		$headerdetail->printRow();

		$headerdetail = new easyTable($this->instance, '%{60,10,10,20}', 'border:0;font-family:THSarabun;');
		$headerdetail->easyCell(utf8Th(''), 'valign:M;align:L;font-size:12; font-style:B;');
		$headerdetail->easyCell(utf8Th('Truck Control : '), 'valign:M;align:R;font-size:12; font-style:B;');
		$headerdetail->easyCell(utf8Th($v[0]['truck_Control_No_show']), 'valign:M;align:R;font-size:12; font-style:B;');
		//$headerdetail->rowStyle('paddingY:6;');
		$headerdetail->easyCell(utf8Th(''), 'valign:M;align:L;font-size:12; font-style:B;paddingY:5;');
		$this->instance->Code128(234, 42, $v[0]['truck_Control_No_show'], 50, 7);
		$headerdetail->printRow();
		$headerdetail->endTable(2);

		if ($Customer == 'TSPK-C') {
			$plan_name = 'THAI SUMMIT PK CORPORATION LTD.';
		} else if ($Customer == 'TSPK-L') {
			$plan_name = 'THAI SUMMIT PKK CO.,LTD';
		}
		if ($Customer == 'TSPK-BP') {
			$plan_name = 'THAI SUMMIT PKK BANGPAKONG CO.,LTD';
		}

		$headerdetail = new easyTable($this->instance, '%{11,25,8,9,9,17,5,11,5}', 'border:0;font-family:THSarabun;font-size:10;');
		$headerdetail->easyCell(utf8Th('PICKUP TIME : '), 'valign:M;align:R;font-style:B;');
		$headerdetail->easyCell(utf8Th($pus_Date), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('ROUTE NO : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($v[0]['Route_Code']), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('DELIVERY TIME : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($Delivery_time), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('DRIVER : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($Driver_Name), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->printRow();
		$headerdetail->easyCell(utf8Th('SUPPLIER NAME : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($v[0]['Supplier_Name']), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('TRUCK NO : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($Truck_Number), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('PLANT NAME : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($plan_name), 'valign:M;align:L; font-style:B;border:B;font-size:9;');
		$headerdetail->easyCell(utf8Th('TEL: '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($v[0]['Phone']), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->printRow();
		$headerdetail->easyCell(utf8Th('SUPPLIER CODE : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($v[0]['Supplier_Name_Short']), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('TRUCK TYPE : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($Truck_Type), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->easyCell(utf8Th('PLANT CODE : '), 'valign:M;align:R; font-style:B;');
		$headerdetail->easyCell(utf8Th($Customer), 'valign:M;align:L; font-style:B;border:B;');
		$headerdetail->printRow();
		$headerdetail = new easyTable($this->instance, '%{100}', 'border:0;font-family:THSarabun;');
		$headerdetail->easyCell(utf8Th(''), 'valign:M;align:R;font-size:16; font-style:B;border:B');
		$headerdetail->printRow();
		$headerdetail->endTable(2);

		$headdetail = new easyTable(
			$this->instance,
			'%{3,7,16,16,4,7,4,4,4,4,5,5,5,4,5,7}',
			'width:300;border:1;font-family:THSarabun;font-size:10; bgcolor:#C8C8C8; valign:M;border:0'
		);
		$headdetail->easyCell(utf8Th('No.'), 'align:C;border:LRBT;rowspan:2;');
		$headdetail->easyCell(utf8Th('Refer ID'), 'align:C;font-style:B;border:RT;');
		$headdetail->easyCell(utf8Th('Part No.'), 'align:C;font-style:B;border:RT;');
		$headdetail->easyCell(utf8Th('Part Name'), 'align:C;font-style:B;border:RT;');
		$headdetail->easyCell(utf8Th('Project'), 'align:C;font-style:B;border:RT;');
		$headdetail->easyCell(utf8Th('Packaging'), 'align:C;font-style:B;border:RT;');
		$headdetail->easyCell(utf8Th('Qty'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('SNP'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('Box'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('Rack'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('Pallet'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('CBM'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('WT'), 'align:C;font-style:B;border:RTB;rowspan:2;');
		$headdetail->easyCell(utf8Th('สถานะการรับสินค้า'), 'align:C;font-size:8;border:RTB;colspan:2;');
		$headdetail->easyCell(utf8Th('Remark'), 'align:C;font-size:8;border:RT;');
		$headdetail->printRow();

		$headdetail->easyCell(utf8Th('เลขออเดอร์'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('รหัสชิ้นงาน'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('ชื่อชิ้นงาน'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('โปรเจกต์'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('ประเภทบรรจุภัณฑ์'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('ไม่ได้รับ'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('ได้รับจำนวน'), 'align:C;font-size:8;border:RB;');
		$headdetail->easyCell(utf8Th('หมายเหตุ'), 'align:C;font-size:8;border:RB;');
		$headdetail->printRow();
	}
	function Footer()
	{
		$this->SetXY(-20, 0);
		$this->SetFont('THSarabun', 'I', 8);
		$this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
	}
}

$pdf = new PDF('L');

$pdf->AddFont('THSarabun', '', 'THSarabun.php');
$pdf->AddFont('THSarabun', 'I', 'THSarabun Italic.php');
$pdf->AddFont('THSarabun', 'B', 'THSarabun Bold.php');
$pdf->AddFont('THSarabun', 'BI', 'THSarabun Bold Italic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($detailData);
$pdf->AddPage();
$docno = $headerData[0]['pus_No'];
$detail = new easyTable($pdf, '%{3,7,16,16,4,7,4,4,4,4,5,5,5,4,5,7}', 'width:300;border:1;font-family:THSarabun;font-size:10;valign:M;');
$pdf->SetTitle($docno);
$data = sizeof($detailData);

//echo($data);
//หน้าละ15row
$pagebreak = 10;
$i = 0;
$countrow = 1;
$nn = 1;
$sumqty = 0;
$sumSNP = 0;
$sumBox = 0;
$sumRack = 0;
$sumPallet = 0;
$sumCBM = 0;
$sumWT = 0;

while ($i <  $data) {
	if ($countrow > $pagebreak) {
		$pdf->AddPage();
		$countrow = 1;
	}
	$countrow++;
	$x = $pdf->GetX();
	$y = $pdf->GetY();

	if ($detailData[$i]["Package_Type"] == 'Box') {
		$Box = $detailData[$i]["Package_Qty"];
	} else {
		$Box = 0;
	}
	if ($detailData[$i]["Package_Type"] == 'Rack') {
		$Rack = $detailData[$i]["Package_Qty"];
	} else {
		$Rack = 0;
	}
	if ($detailData[$i]["Package_Type"] == 'Pallet') {
		$Pallet = $detailData[$i]["Package_Qty"];
	} else {
		$Pallet = 0;
	}
	$Customer = substr($detailData[$i]['pus_No'], 13);

	$detail->easyCell(utf8Th($nn), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["Refer_ID"]), 'align:C;font-size:9;');
	$detail->easyCell(utf8Th($detailData[$i]["Part_No"]), 'align:C;font-size:9;');
	$detail->easyCell(utf8Th($detailData[$i]["Part_Name"]), 'align:C;font-size:9;');
	$detail->easyCell(utf8Th($detailData[$i]["Project"]), 'align:C;font-size:9;');
	$detail->easyCell(utf8Th($detailData[$i]["Package_Type"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["Actual_Qty"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["SNP_Per_Pallet"]), 'align:C;');
	$detail->easyCell(utf8Th($Box), 'align:C;');
	$detail->easyCell(utf8Th($Rack), 'align:C;');
	$detail->easyCell(utf8Th($Pallet), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["CBM"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["WT"]), 'align:C;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$detail->easyCell(utf8Th(''), 'align:C;');
	$detail->printRow();
	$nn++;
	$sumqty += $detailData[$i]['Actual_Qty'];
	$sumSNP += $detailData[$i]['SNP_Per_Pallet'];
	$sumCBM += $detailData[$i]['CBM'];
	$sumWT += $detailData[$i]['WT'];
	$sumBox += $Box;
	$sumRack += $Rack;
	$sumPallet += $Pallet;
	$i++;
}

$detail->easyCell(utf8Th('Grand Totals :'), 'align:R;font-style:B;colspan:6;font-size:12;');
$detail->easyCell(utf8Th($sumqty), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->easyCell(utf8Th(''), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->easyCell(utf8Th($sumBox), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->easyCell(utf8Th($sumRack), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->easyCell(utf8Th($sumPallet), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->easyCell(utf8Th($sumCBM), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->easyCell(utf8Th($sumWT), 'align:C;font-style:B;font-size:10;bgcolor:#C8C8C8;');
$detail->printRow();
$detail->endTable(2);


$lastfooter = new easyTable($pdf, '%{25,5,45,5,10,10}', 'width:300;border:1;font-family:THSarabun;font-size:10;');
$lastfooter->easyCell(utf8Th('Remark : '), 'align:L;font-style:B;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('รับ Packaging'), 'align:C;font-style:B;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('ลายมือชื่อพนักงานขับรถ'), 'align:C;font-style:B;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('ลายมือชื่อ ' . $Customer), 'align:C;font-style:B;bgcolor:#C8C8C8;');
$lastfooter->printRow();

$lastfooter = new easyTable($pdf, '%{25,5,10,7,7,7,7,7,5,10,10}', 'width:300;border:1;font-family:THSarabun;font-size:10;');
$lastfooter->easyCell(utf8Th(''), 'align:L;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('ชนิด'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('กล่องพลาสติก'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('กล่องลูกฟูก'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('พาเลทพลาสติก'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('แร็คเหล็ก(ไม่มีล้อ)'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('แร็คเหล็ก(มีล้อ)'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('ตัวบรรจง'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th('ตัวบรรจง'), 'align:C;valign:M;bgcolor:#C8C8C8;');
$lastfooter->printRow();

$lastfooter->easyCell(utf8Th(''), 'align:L;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('จำนวนรับจริง'), 'align:C;bgcolor:#C8C8C8;rowspan:2;paddingY:4;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;rowspan:2;');
$lastfooter->printRow();

$lastfooter->easyCell(utf8Th(''), 'align:L;font-style:B;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->printRow();
$lastfooter->endTable(2);

$nn = 1;
$sumqty = 0;
$sumSNP = 0;
$sumBox = 0;
$sumRack = 0;
$sumPallet = 0;
$sumCBM = 0;
$sumWT = 0;

$pdf->Output('I', 'pickupsheet/PICKUPSHEET_' . $docno . '.pdf');
$pdf->Output('F', 'pickupsheet/pus_pre/PICKUPSHEET_' . $docno . '.pdf');

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
	// return iconv("cp1252", "utf-8//TRANSLIT", $v);
}
