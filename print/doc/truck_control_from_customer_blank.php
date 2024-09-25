<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');
require '../../vendor/autoload.php';
//include '../fpdf2file.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));

$dataset = array();
$q1  = "SELECT 
	BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
	tts.truck_Control_No,
	SUBSTRING(tts.truck_Control_No, 1, 13)as truck_Control_No_show,
	if(trm.route_special = 'Y' OR trm.route_special = 'N', SUBSTRING(tts.truck_Control_No, 1, 13), CONCAT(SUBSTRING(tts.truck_Control_No, 1, 13),'-', '2/',amount_truck)) truck_Control_No_special,
	DATE(tts.truckNo_Date) AS truckNo_Date,
	-- tts.Route_Code,
	if(trm.route_special = 'Y' OR trm.route_special = 'N', tts.Route_Code, CONCAT(tts.Route_Code,' ', trm.route_special)) as Route_Code,
	DATE_FORMAT(tts.start_Date, '%H:%i') AS start_Date,
	DATE_FORMAT(tts.actual_start_Date, '%H:%i') AS actual_start_Date,
	tts.stop_Date,
	tts.trip_Number,
	tts.total_Stop,
	tts.mile_Start,
	tts.mile_End,
	BIN_TO_UUID(tts.truck_ID, TRUE) AS truck_ID,
	BIN_TO_UUID(tts.Driver_ID, TRUE) AS Driver_ID,
	tts.Truck_Number,
	tts.Truck_Type,
	tdm.Driver_Name,
	tdm.Phone,
	tts.tran_status,
	trm.route_special,
	t1.user_signature
	FROM
	tbl_transaction tts
		INNER JOIN
	tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
		INNER JOIN
	tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
		INNER JOIN
	tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
		LEFT JOIN
	tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
		LEFT JOIN
	tbl_user t1 ON tts.Created_By_ID = t1.user_id
	WHERE
	tts.truck_Control_No = '$doc'
	ORDER BY tts.truck_Control_No;";


$q1  .= "SELECT
tts.truck_Control_No,
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
trm.route_special
FROM
tbl_transaction tts
	INNER JOIN
tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
	INNER JOIN
tbl_driver_master tdm ON tts.Driver_ID = tdm.Driver_ID
	INNER JOIN
tbl_supplier_master tsm ON ttl.Supplier_ID = tsm.Supplier_ID
LEFT JOIN
tbl_route_master trm ON ttl.Route_ID = trm.Route_ID
WHERE
tts.truck_Control_No = '$doc'
	AND tts.tran_status != 'PENDING'
	AND ttl.Pick != 'N'
ORDER BY ttl.sequence_Stop, ttl.pus_No;";
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
	function __construct($orientation = 'L', $unit = 'mm', $format = 'A4')
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
	function mmToPercentWidth($mm)
	{
		return ($mm / $this->GetPageWidth()) * 100;
	}

	function mmToPercentHeight($mm)
	{
		return ($mm / $this->GetPageHeight()) * 100;
	}
	function Header()
	{
		$v = $this->headerData;
		$header = new easyTable($this->instance, '%{40, 40, 10, 10,}', 'border:0;font-family:THSarabun;font-size:24; font-style:B;');
		$header->easyCell(utf8Th('แบบฟอร์มควบคุมการเดินรถขนส่งสินค้า
	  (Truck Control Form)'), 'valign:M;align:L');
		$header->easyCell('', 'valign:C;align:C;font-size:24; font-style:B;');
		$header->easyCell('', 'img:images/tspk.jpg, w30;align:L', '');
		$header->easyCell('', 'img:images/abt-logo.gif, w30;align:L', '');
		$header->printRow();
		$header->endTable(2);


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

		$Customer = substr($v[0]['truck_Control_No'], 13);

		if ($Customer == '') {
			$Customer = 'TSPK-C';
		}

		$route_special = $v[0]['route_special'];

		if ($Customer == 'TSPK-L' && $route_special == 'Y') {
			$Truck_Type = '';
			$Truck_Number = '';
			$Driver_Name = '';
			$Phone = '';
			$truck_Control_No_show_text = $v[0]['truck_Control_No_show'];
		} else if ($Customer == 'TSPK-L' && $route_special == 'UT') {
			$Truck_Type = '';
			$Truck_Number = '';
			$Driver_Name = '';
			$Phone = '';
			$truck_Control_No_show_text = $v[0]['truck_Control_No_special'];
		} else {
			$Truck_Type = $v[0]['Truck_Type'];
			$Truck_Number = $Truck_Number;
			$Driver_Name = $Driver_Name;
			$Phone = $v[0]['Phone'];
			$truck_Control_No_show_text = $v[0]['truck_Control_No_show'];
		}


		if ($v[0]["mile_Start"] == '0') {
			$mile_Start = '';
		} else {
			$mile_Start = $v[0]["mile_Start"];
		}
		if ($v[0]["mile_Start"] == '0') {
			$mile_Start = '';
		} else {
			$mile_Start = $v[0]["mile_Start"];
		}

		if ($v[0]["start_Date"] == '00:00') {
			$start_Date = '';
		} else {
			$start_Date = $v[0]["start_Date"];
		}


		$header = new easyTable($this->instance, '%{5,8,8,10,10,8,10,12,8,21}', 'border:1;font-family:THSarabun;font-size:14;');
		$header->easyCell(utf8Th('วันที่
		รับงาน'), 'valign:M;align:C;font-size:14; rowspan:2;bgcolor:#C8C8C8;');
		$header->easyCell(utf8Th($v[0]['truckNo_Date']), 'valign:M;align:C;rowspan:2;font-style:B;');
		$header->easyCell(utf8Th('Route No.'), 'valign:M;align:C;bgcolor:#C8C8C8;font-size:14; ');
		$header->easyCell(utf8Th($v[0]['Route_Code']), 'valign:M;align:C;font-style:B;font-size:12;');
		$header->easyCell(utf8Th('ทะเบียนรถ'), 'valign:M;align:C;bgcolor:#C8C8C8;font-size:14;');
		$header->easyCell(utf8Th($Truck_Number), 'valign:M;align:C;font-style:B;font-size:12;');
		$header->easyCell(utf8Th('พนักงานขับรถ'), 'valign:M;align:C;bgcolor:#C8C8C8;font-size:14;');
		$header->easyCell(utf8Th($Driver_Name), 'valign:M;align:C;font-style:B;font-size:12;');
		$header->easyCell(utf8Th('เลขที่เอกสาร'), 'valign:M;align:C;rowspan:2;bgcolor:#C8C8C8;font-size:14;');
		$header->easyCell(utf8Th($truck_Control_No_show_text), 'valign:B;align:C;font-size:12;rowspan:2;font-style:B;');
		$this->instance->Code128(230.5, 35, $v[0]['truck_Control_No_show'], 55, 8);
		$header->printRow();

		$header->easyCell(utf8Th('Revision No.'), 'valign:M;align:C;bgcolor:#C8C8C8;font-size:14;');
		$header->easyCell(utf8Th(''), 'valign:M;align:C;font-style:B;');
		$header->easyCell(utf8Th('ประเภทรถ'), 'valign:M;align:C;bgcolor:#C8C8C8;font-size:14;');
		$header->easyCell(utf8Th($Truck_Type), 'valign:M;align:C;font-style:B;font-size:12;');
		$header->easyCell(utf8Th('เบอร์โทรติดต่อ'), 'valign:M;align:C;bgcolor:#C8C8C8;font-size:14;');
		$header->easyCell(utf8Th($Phone), 'valign:M;align:C;font-style:B;font-size:12;');
		$header->printRow();

		$header = new easyTable($this->instance, '%{56.5,23.5,20}', 'border:1;font-family:THSarabun;font-size:14; font-style:B;');
		$header->easyCell(utf8Th('*ข้อกำหนด : พนักงานขับรถต้องทำการบันทึกข้อมูลในแบบฟอร์มให้ถูกต้อง และครบถ้วน*'), 'valign:M;align:L;border:LTB;');
		$header->easyCell('', 'img:images/truck.png, w50;align:C;border:LTBR;', '');
		$header->easyCell(utf8Th('ส่วนของลูกค้า/เจ้าหน้าที่หน้างาน'), 'valign:M;align:C;bgcolor:#C8C8C8;');
		$header->printRow();
		//$header->endTable(3);

		$headdetail = new easyTable(
			$this->instance,
			'%{23,33.5,23.5,10,10}',
			'width:300;border:1;font-family:THSarabun;font-size:12; font-style:B;bgcolor:#C8C8C8; valign:M;'
		);
		$headdetail->easyCell(utf8Th('หัวหน้างาน กำหนดการเดินรถ'), 'align:C');
		$headdetail->easyCell(utf8Th('1. พนักงานขับรถ บันทึกเวลาเดินรถและเลขไมล์จริง'), 'align:C');
		$headdetail->easyCell(utf8Th('2. การตรวจสอบ seal ล๊อคตู้'), 'align:C');
		$headdetail->easyCell(utf8Th('สภาพงาน'), 'align:C');
		$headdetail->easyCell(utf8Th('ลายเซ็น'), 'align:C');
		$headdetail->printRow();



		$mmWidth = 18; // Width in mm
		$percentWidth = $this->instance->mmToPercentWidth($mmWidth);

		//echo $percentWidth;
		
		$headdetail = new easyTable(
			$this->instance,
			"%{3,15,5,5,5,5,5,5,8.5,4,6.5,6.5,6.5,5,5,10}",
			'width:300;border:1;font-family:THSarabun;font-size:10;bgcolor:#C8C8C8; valign:M;'
		);
		$headdetail->easyCell(utf8Th('ลำดับ'), 'align:C');
		$headdetail->easyCell(utf8Th('จุดรับ-ส่ง (สินค้า)'), 'align:C');
		$headdetail->easyCell(utf8Th('กำหนดการ'), 'align:C');
		$headdetail->easyCell(utf8Th('เวลาเข้า'), 'align:C');
		$headdetail->easyCell(utf8Th('เวลาเริ่มขึ้น
		หรือลงของ'), 'align:C');
		$headdetail->easyCell(utf8Th('เวลาขึ้นหรือ
		ลงเสร็จ'), 'align:C');
		$headdetail->easyCell(utf8Th('เวลาออก'), 'align:C');
		$headdetail->easyCell(utf8Th('เลขไมล์'), 'align:C');
		$headdetail->easyCell(utf8Th('สาเหตุที่ล่าช้า
		(ใส่หมายเลขตาม
		รายละเอียดด้านล่าง)'), 'align:C');
		$headdetail->easyCell(utf8Th('เวลา'), 'align:C');
		$headdetail->easyCell(utf8Th('เลขซีล 1'), 'align:C');
		$headdetail->easyCell(utf8Th('เลขซีล 2'), 'align:C');
		$headdetail->easyCell(utf8Th('เลขซีล 3'), 'align:C');
		$headdetail->easyCell(utf8Th('สมบูรณ์'), 'align:C');
		$headdetail->easyCell(utf8Th('ไม่สมบูรณ์'), 'align:C');
		$headdetail->easyCell(utf8Th('เจ้าหน้าที่/ลูกค้า'), 'align:C');
		$headdetail->printRow();
	}
	function Footer()
	{
		$this->SetXY(-20, 0);
		$this->SetFont('THSarabun', 'I', 8);
		$this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
	}
}

$pdf = new PDF('L', 'mm', 'A4');

$pdf->AddFont('THSarabun', '', 'THSarabun.php');
$pdf->AddFont('THSarabun', 'I', 'THSarabun Italic.php');
$pdf->AddFont('THSarabun', 'B', 'THSarabun Bold.php');
$pdf->AddFont('THSarabun', 'BI', 'THSarabun Bold Italic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($headerData);
$pdf->AddPage();
$docno = $headerData[0]['truck_Control_No'];
$pdf->SetTitle($docno);
$detail = new easyTable($pdf, 
"%{3,15,5,5,5,5,5,5,8.5,4,6.5,6.5,6.5,5,5,10}",
'width:300;border:1;font-family:THSarabun;font-size:12;valign:M;');
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 4;
$i = 0;
$countrow = 1;
$nn = 1;
$sumqty = 0;
$sumBoxes = 0;
$sumCBM = 0;

while ($i <  $data) {
	if ($countrow > $pagebreak) {
		$pdf->AddPage();
		$countrow = 1;
	}
	$countrow++;
	$x = $pdf->GetX();
	$y = $pdf->GetY();

	if ($detailData[$i]["start_load_time"] == '00:00') {
		$start_load_time = '';
	} else {
		$start_load_time = $detailData[$i]["start_load_time"];
	}
	if ($detailData[$i]["end_load_time"] == '00:00') {
		$end_load_time = '';
	} else {
		$end_load_time = $detailData[$i]["end_load_time"];
	}

	if ($detailData[$i]["mile"] == '0') {
		$mile = '';
	} else {
		$mile = $detailData[$i]["mile"];
	}
	if ($detailData[$i]["mile"] == '0') {
		$mile = '';
	} else {
		$mile = $detailData[$i]["mile"];
	}


	$Customer = substr($detailData[$i]['truck_Control_No'], 13);

	if ($Customer == '') {
		$Customer = 'TSPK-C';
	}

	$route_special = $detailData[$i]['route_special'];

	if ($Customer == 'TSPK-L' && $route_special == 'Y') {
		$planin_time = '';
	} else {
		$planin_time = $detailData[$i]["planin_time"];
	}

	$detail->easyCell(utf8Th($nn), 'align:C;rowspan:2');
	$detail->easyCell(utf8Th($detailData[$i]["Supplier_Name_Short"]), 'align:C;font-size:14;font-style:B;border:TLR;');
	$detail->easyCell(utf8Th($planin_time), 'align:C; font-size:12;rowspan:2;bgcolor:#C8C8C8;');
	$detail->easyCell(utf8Th($detailData[$i]["actual_in_time"]), 'align:C;rowspan:2');
	$detail->easyCell('', 'img:images/slash.png, w30;align:L;rowspan:2', '');
	$detail->easyCell('', 'img:images/slash.png, w30;align:L;rowspan:2', '');
	//$detail->easyCell(utf8Th($start_load_time), 'align:C;rowspan:2');
	//$detail->easyCell(utf8Th($end_load_time), 'align:C;rowspan:2');
	$detail->easyCell(utf8Th($detailData[$i]["actual_out_time"]), 'align:C;rowspan:2');
	$detail->easyCell(utf8Th($mile), 'align:C;rowspan:2');
	$detail->easyCell(utf8Th($detailData[$i]["Remark"]), 'align:C;rowspan:2;font-size:8;');
	$detail->easyCell(utf8Th('เข้า'), 'align:C;bgcolor:#C8C8C8;');
	$detail->easyCell(utf8Th(''), 'align:C;bgcolor:#e5e5e5;');
	$detail->easyCell(utf8Th(''), 'align:C;bgcolor:#e5e5e5;');
	$detail->easyCell(utf8Th(''), 'align:C;bgcolor:#e5e5e5;');
	$detail->easyCell('', 'img:images/cube.png, w4;align:C;valign:M;', '');
	$detail->easyCell('', 'img:images/cube.png, w4;align:C;valign:M;', '');
	$detail->easyCell(utf8Th(''), 'align:C;rowspan:2');
	$detail->printRow();

	$detail->easyCell(utf8Th($detailData[$i]["Supplier_Name"]), 'align:C;font-size:8;font-style:B;border:LRB;');
	$detail->easyCell(utf8Th('ออก'), 'align:C;font-style:B;');
	$detail->easyCell(utf8Th($detailData[$i]["seal1"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["seal2"]), 'align:C;');
	$detail->easyCell(utf8Th($detailData[$i]["seal3"]), 'align:C;');
	$detail->easyCell('', 'img:images/cube.png, w4;align:C', '');
	$detail->easyCell('', 'img:images/cube.png, w4;align:C', '');
	$detail->printRow();


	$cs_tel = '';
	$ts_tel = '';
	if ($Customer == 'TSPK-C') {
		$cs_tel = '061-4236410';
		$ts_tel = '062-5702210';
	} else if ($Customer == 'TSPK-L') {
		$cs_tel = '098-6186989';
		$ts_tel = '065-9825524';
	} else if ($Customer == 'TSPK-BP') {
		$cs_tel = '098-6186989';
		$ts_tel = '065-9825524';
	}

	$i++;
	$nn++;
}
$detail->endTable(3);


$user_signature = $headerData[0]['user_signature'];
$truckNo_Date = $headerData[0]['truckNo_Date'];

$lastfooter = new easyTable($pdf, '%{20,6,44,6,12,12}', 'width:300;border:1;font-family:THSarabun;font-size:10;');
$lastfooter->easyCell(utf8Th('เบอร์ติดต่อหัวหน้างาน'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('เหตุผลที่เกิดการล่าช้า ในการ รับ-ส่ง สินค้า (ให้ใส่หมายเลขในช่อง สาเหตุที่ล่าช้า)'), 'align:L;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('ผู้ปล่อยรถ(ABT)'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->easyCell(utf8Th('ผู้ตรวจสอบขากลับ(ABT)'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->printRow();


$lastfooter = new easyTable($pdf, '%{10,10,6,5,20,4,15,6,12,12}', 'width:300;border:1;font-family:THSarabun;font-size:10;line-height:1.15;');
$lastfooter->easyCell(utf8Th('CS Controller'), 'align:C;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th($cs_tel), 'align:C;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('1'), 'align:C;');
$lastfooter->easyCell(utf8Th('ออกจากลานจอดรถช้า หรือได้รับรถช้า'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th('7'), 'align:C;');
$lastfooter->easyCell(utf8Th('ไม่มีช่องจอดรถ'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:LR;rowspan:2;');
//$lastfooter->easyCell('', 'img:images/user_signature/' . $user_signature . ', w15;border:LR;rowspan:2;', '');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:LR;rowspan:2;');
$lastfooter->printRow();

$lastfooter->easyCell(utf8Th('Transport Controller'), 'align:C;bgcolor:#C8C8C8;');
$lastfooter->easyCell(utf8Th($ts_tel), 'align:C;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('2'), 'align:C;');
$lastfooter->easyCell(utf8Th('ช้ามาจากจุดก่อนหน้า (จุดรับ หรือ ส่งสินค้า)'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th('8'), 'align:C;');
$lastfooter->easyCell(utf8Th('รอเจ้าหน้าที่ตรวจรับสินค้า'), 'align:L;border:LR');
$lastfooter->printRow();


$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('3'), 'align:C;');
$lastfooter->easyCell(utf8Th('ฝนตก, รถติด'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th('9'), 'align:C;');
$lastfooter->easyCell(utf8Th('ภาชนะเปล่าไม่ได้ถูกจัดเตรียม'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('หัวหน้างาน'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->easyCell(utf8Th('หัวหน้างาน'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->printRow();


$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('4'), 'align:C;');
$lastfooter->easyCell(utf8Th('รอคิวรถ เพื่อเรียกเข้ารับ-ส่งสินค้า'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th('10'), 'align:C;');
$lastfooter->easyCell(utf8Th('รอเอกสาร'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('วันที่ปล่อยรถ'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->easyCell(utf8Th('วันที่ตรวจสอบขากลับ'), 'align:C;bgcolor:#C8C8C8;font-style:B;');
$lastfooter->printRow();


$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('5'), 'align:C;');
$lastfooter->easyCell(utf8Th('สินค้าไม่พร้อมจัดส่ง หรือ รอขึ้นสินค้า'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th('11'), 'align:C;');
$lastfooter->easyCell(utf8Th('รถเสียระหว่างทาง'), 'align:L;border:LR');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;rowspan:2;');

$date = date_create($truckNo_Date);
$truckNo_Date = date_format($date,"d/m/Y");
$lastfooter->easyCell(utf8Th(''), 'align:C;border:LRB;rowspan:2;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:LRB;rowspan:2;');
$lastfooter->printRow();


$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th(''), 'align:C;border:0;');
$lastfooter->easyCell(utf8Th('6'), 'align:C;');
$lastfooter->easyCell(utf8Th('ภาชนะบรรจุไม่เพียงพอ'), 'align:L;border:BLR');
$lastfooter->easyCell(utf8Th('12'), 'align:C;');
$lastfooter->easyCell(utf8Th('รถเกิดอุบัติเหตุ'), 'align:L;border:BLR');
$lastfooter->printRow();
$lastfooter->endTable(3);

$pdf->SetFont('THSarabun', 'B', 24);
$pdf->Text(142, 18, $Customer);


$pdf->Output('I', 'truckfrom/TRUCKCONTROL_CUS_B' . $docno . '.pdf');
$pdf->Output('F', 'truckfrom/TRUCKCONTROL_CUS_B' . $docno . '.pdf');

function utf8Th($v)
{
	return iconv('UTF-8', 'TIS-620//TRANSLIT', $v);
}


// $ip_server = $_SERVER['SERVER_NAME'];
// $pus_file = file_get_contents('http://' . $ip_server . '/tspk-mr/print/doc/loop_createPUS.php?data=' . $doc);
