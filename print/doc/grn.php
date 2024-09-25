<?php
include '../fpdf.php';
include '../exfpdf.php';
include '../easyTable.php';
include 'PDF_Code128.php';
include('../../php/connection.php');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));
$dataset = array();
$q1  = "SELECT 
	BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
	BIN_TO_UUID(tts.Route_Header_ID, TRUE) AS Route_Header_ID,
	tts.truck_Control_No,
	tts.truckNo_Date,
	tts.Route_Code,
	tts.Plan_StartDate,
	tts.start_Date,
	tts.stop_Date,
	tts.work_Shift,
	tts.trip_Number,
	tts.total_Stop,
	tts.trip_Type,
	tts.mile_Start,
	tts.mile_End,
	BIN_TO_UUID(tts.truck_ID, TRUE) AS truck_ID,
	BIN_TO_UUID(tts.Driver_ID, TRUE) AS Driver_ID,
	tts.Truck_Number,
	tts.Truck_Type,
	tts.Driver_Name,
	tts.tran_status
FROM
	tbl_transaction tts
WHERE
	tts.truck_Control_No = '$doc'
ORDER BY tts.truck_Control_No;";

$q1  .= "SELECT 
		BIN_TO_UUID(tts.transaction_ID, TRUE) AS transaction_ID,
		BIN_TO_UUID(tts.Route_Header_ID, TRUE) AS Route_Header_ID,
		tts.truck_Control_No,
		tts.truckNo_Date,
		tts.Route_Code,
		tts.Plan_StartDate,
		tts.start_Date,
		tts.stop_Date,
		tts.work_Shift,
		tts.trip_Number,
		tts.total_Stop,
		tts.trip_Type,
		tts.mile_Start,
		tts.mile_End,
		BIN_TO_UUID(tts.truck_ID, TRUE) AS truck_ID,
		BIN_TO_UUID(tts.Driver_ID, TRUE) AS Driver_ID,
		tts.Truck_Number,
		tts.Driver_Name,
		tts.tran_status,
		BIN_TO_UUID(ttl.transaction_Line_ID, TRUE) AS transaction_Line_ID,
		ttl.pus_No,
		ttl.pus_Date,
		BIN_TO_UUID(ttl.Truck_Control_Route_ID, TRUE) AS Truck_Control_Route_ID,
		BIN_TO_UUID(ttl.Supplier_ID, TRUE) AS Supplier_ID,
		ttl.Supplier_Code,
		ttl.Supplier_Name,
		ttl.sequence_Stop,
		ttl.plan_Arrival_Date,
		ttl.actual_Arrival__Date,
		ttl.plan_Departure_Date,
		ttl.actual_Departure_Date,
		ttl.start_load_Date,
		ttl.end_load_Date,
		ttl.mile,
		ttl.Remark,
		ttl.seal1,
		ttl.seal2,
		ttl.seal3
	FROM
	tbl_transaction tts
		INNER JOIN
	tbl_truck_control_route tcr ON tts.transaction_ID = tcr.transaction_ID
		LEFT JOIN
	tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID AND tcr.Supplier_ID = ttl.Supplier_ID
	WHERE
		tts.truck_Control_No = '$doc'
	ORDER BY tts.truck_Control_No , ttl.sequence_Stop , ttl.pus_No;";
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
	function __construct($orientation='P', $unit='mm', $format='A4')
	{
		parent::__construct($orientation,$unit,$format);
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
	    $header = new easyTable($this->instance, '%{20, 50, 30,}', 'border:0;font-family:THSarabun;font-size:12; font-style:B;');
		$header->easyCell('', 'img:images/abt-logo.gif, w35;align:L', '');
      	$header->easyCell('ALBATROSS LOGISTICS CO., LTD.
	  336/7 MOO 7 BOWIN, SRIRACHA CHONBURI 20230
	  Phone +66 38 058 021, +66 38 058 081-2
	  Fax : +66 38 058 007
	  ', 'valign:M;align:L');
      $header->easyCell($v[0]['GRN_Number'], 'valign:B;align:C');
      $header->printRow();
      $header->endTable(2);
      	

      	$header=new easyTable($this->instance, '%{100}','border:0;font-family:THSarabun;font-size:20; font-style:B;');
      	$header->easyCell(utf8Th('GOODS RECEIPT NOTE'), 'valign:M;align:C;border:TB');
      	$header->printRow();
      	$header->endTable(1);

        $header=new easyTable($this->instance, '%{20,20,10,20,15,15}','border:0;font-family:THSarabun;font-size:13;');
        $header->easyCell("Receipt Date Time :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['Received_Date']), 'valign:T;align:L;');
        $header->easyCell("GRN No :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['GRN_Number']), 'valign:T;align:L;');
        $header->easyCell("Supplier Name : ", 'valign:T;align:L;font-style:B;');
        $header->easyCell($v[0]['Supplier'], 'valign:T;align:L;');
        $header->printRow();
		$header->easyCell("DN Number :", 'valign:T;align:L;font-style:B;');
        $header->easyCell(utf8Th($v[0]['DN_Number']), 'valign:T;align:L;');
        $header->printRow();
        $header->endTable(2);

	    $headdetail =new easyTable($this->instance, '{10,30,30,35,30,50,15,25}',
	    'width:300;border:1;font-family:THSarabun;font-size:12; font-style:B;bgcolor:#C8C8C8; valign:M;');
		$headdetail->easyCell(utf8Th('No.'), 'align:C');
		$headdetail->easyCell(utf8Th('Rack Number'), 'align:C');
        $headdetail->easyCell(utf8Th('Package Number'), 'align:C');
        //$headdetail->easyCell(utf8Th('Serial Number'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Number'), 'align:C');
        $headdetail->easyCell(utf8Th('MMTH Part No.'), 'align:C');
        $headdetail->easyCell(utf8Th('Part Name'), 'align:C');
        $headdetail->easyCell(utf8Th('Qty/
		PCS'), 'align:C');
        $headdetail->easyCell(utf8Th('Remark'), 'align:C');
		$headdetail->printRow(); 
		$headdetail->endTable(0);

		$this->instance->Code128(145,20,$v[0]['GRN_Number'],55,7);
  	}
  	function Footer()
  	{
  		$this->SetXY(-20,0);
	    $this->SetFont('THSarabun','I',8);
	    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
  	}
}

$pdf=new PDF('P');

$pdf->AddFont('THSarabun','','THSarabun.php');
$pdf->AddFont('THSarabun','I','THSarabun Italic.php');
$pdf->AddFont('THSarabun','B','THSarabun Bold.php');
$pdf->AddFont('THSarabun','BI','THSarabun Bold Italic.php');
$pdf->setInstance($pdf);
$pdf->setHeaderData($headerData);
$pdf->AddPage();
$docno = $headerData[0]['GRN_Number'];
$pdf->SetTitle($docno);
$detail =new easyTable($pdf, '{10,30,30,35,30,50,15,25}','width:300;border:1;font-family:THSarabun;font-size:10;valign:M;');
$data = sizeof($detailData);
// หน้าละ15row
$pagebreak = 15;
$i = 0;
$countrow = 1;
$nn = 1;
$sumqty=0;
$sumBoxes=0;
$sumCBM=0;
while ( $i <  $data)
{
if ($countrow > $pagebreak) 
{
  $pdf->AddPage();
  $countrow = 1;
}
$countrow++;
$x=$pdf->GetX();
$y=$pdf->GetY();
$detail->easyCell(utf8Th($nn), 'align:C');
$detail->easyCell(utf8Th($detailData[$i]["Rack_Number"]), 'align:C;font-style:B;font-size:12;');
$detail->easyCell(utf8Th($detailData[$i]["Package_Number"]), 'align:C;font-style:B;font-size:12;');
$detail->easyCell(utf8Th($detailData[$i]["Part_Number"]), 'align:C;font-style:B;font-size:12;');
$detail->easyCell(utf8Th($detailData[$i]["MMTH_Part_No"]), 'align:C;font-style:B;font-size:12;');
$detail->easyCell(utf8Th($detailData[$i]["Part_Name"]), 'align:C;font-size:10;');
$detail->easyCell(utf8Th($detailData[$i]["Qty"]), 'align:C;font-style:B;font-size:14;');
$detail->easyCell(utf8Th(''), 'align:C;font-style:B;font-size:14;');
$detail->printRow();
$sumqty += $detailData[$i]['Qty'];
$i++;$nn++;

}
$detail->easyCell(utf8Th('Total :'), 'align:R;font-style:B;;colspan:6;font-size:14;');
$detail->easyCell(utf8Th($sumqty), 'align:C;font-style:B;font-size:14;');
$detail->easyCell(utf8Th(''), 'align:C');
$detail->easyCell(utf8Th(''), 'align:C;font-size:14;');
$detail->easyCell(utf8Th(''), 'align:C;colspan:3');
$detail->printRow();
$detail->endTable(10);

$lastfooter =new easyTable($pdf, '%{20,25,20,35}','width:300;border:0;font-family:THSarabun;font-size:12;');
$lastfooter->easyCell(utf8Th('Data Entry By :'), 'align:C;font-size:14;');
$lastfooter->easyCell(utf8Th('____________________'), 'align:C;font-size:14;');
$lastfooter->easyCell(utf8Th('Check By :'), 'align:C;font-size:14;');
$lastfooter->easyCell(utf8Th('____________________  Suppervisor'), 'align:C;font-size:14;');
$lastfooter->printRow();
$lastfooter->endTable(3);

$pdf->Output();

function utf8Th($v)
{
	return iconv( 'UTF-8','TIS-620//TRANSLIT',$v);
}
