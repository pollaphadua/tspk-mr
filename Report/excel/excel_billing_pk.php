<?php
include('../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Workshhet\wo;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$cbmArray = array();

$no = 1;
foreach ($dataArray as $row) {

    $formattedData = [
        $no,
        $row['Supplier'], $row['dash'], $row['Customer'], $row['Supplier_Name'],
        $row['CBM'], $row['Transport_Price'], $row['Planing_Price'], $row['Total_Price'], $row['Amount'], $row['Remark'],
    ];
    $cbmArray[] = $formattedData;
    $no++;
}


function setDefaultStyles($worksheet)
{
    $styles = [
        'A' => ['width' => 1.00], 'B' => ['width' => 10.00], 'C' => ['width' => 10.00],
        'D' => ['width' => 10.00], 'E' => ['width' => 10.00], 'F' => ['width' => 10.00],
        'G' => ['width' => 30.00], 'H' => ['width' => 20.00], 'I' => ['width' => 20.00],
        'J' => ['width' => 20.00], 'K' => ['width' => 20.00], 'L' => ['width' => 20.00],
        'M' => ['width' => 25.00], 'N' => ['width' => 25.00],
    ];
    $worksheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
    $worksheet->getParent()->getDefaultStyle()->getFont()->setSize(14);
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setVertical('Center');
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);

    $worksheet->getRowDimension('1')->setRowHeight(5, 'pt');

    foreach ($styles as $col => $style) {
        $worksheet->getColumnDimension($col)->setWidth($style['width']);
    }
}

function applyFont($worksheet, $range, $fontSize = 10, $color = 0)
{
    $styleArray = [
        'font' => [
            'bold' => true,
            'size' => $fontSize,
        ],
    ];

    if ($color === 1) {
        $styleArray['font']['color'] = ['rgb' => '0000FF'];
    }

    $worksheet->getStyle($range)->applyFromArray($styleArray);
}


function applyBorders($worksheet, $range, $borderCode, $inside = 1)
{
    $borderDefinitions = [
        'thin' => Border::BORDER_THIN,
        'thick' => Border::BORDER_THICK,
        'double' => Border::BORDER_DOUBLE,
    ];

    $borderStyle = [];
    for ($i = 0; $i < strlen($borderCode); $i += 2) {
        $char = $borderCode[$i];
        $styleCode = $borderCode[$i + 1];
        $borderStyleKey = '';
        if ($char === 'T') {
            $borderStyleKey = 'top';
        } elseif ($char === 'B') {
            $borderStyleKey = 'bottom';
        } elseif ($char === 'L') {
            $borderStyleKey = 'left';
        } elseif ($char === 'R') {
            $borderStyleKey = 'right';
        }

        if ($styleCode === '0') {
            $borderStyle['borders'][$borderStyleKey]['borderStyle'] = $borderDefinitions['thick'];
        } elseif ($styleCode === '2') {
            $borderStyle['borders'][$borderStyleKey]['borderStyle'] = $borderDefinitions['double'];
        } else {
            $borderStyle['borders'][$borderStyleKey]['borderStyle'] = $borderDefinitions['thin'];
        }
    }

    if ($inside === 1) {
        $borderStyle['borders']['inside'] = [
            'borderStyle' => Border::BORDER_THIN,
        ];
    }

    $worksheet->getStyle($range)->applyFromArray($borderStyle);
}

function addHeaderTable($worksheet, $data, $row, $col)
{
    $index = count($col) - 1;
    $worksheet->getStyle($col[0] . ($row))->getFont()->setBold(true);
    $worksheet->getStyle($col[0] . ($row))->getAlignment()->setWrapText(false);

    for ($i = 0; $i < count($data); $i++) {
        $worksheet->setCellValue($col[$i] . ($row), $data[$i]);
    }
    $range = $col[0] . ($row) . ':' . $col[$index] . ($row);
    $worksheet->getStyle($range)->getAlignment()->setHorizontal('center');
    return $range;
}

function addDetailTable($worksheet, $data, $row, $col, $border = 1)
{
    if (empty($data)) {
        return $row;
    }
    foreach ($data as $rowData) {
        for ($i = 0; $i < count($col); $i++) {
            $worksheet->setCellValue($col[$i] . $row, $rowData[$i]);
            $worksheet->getStyle("I$row")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED3);
            $worksheet->getStyle("J$row")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $worksheet->getStyle("K$row")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $worksheet->getStyle("L$row")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $worksheet->getStyle("M$row")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            //$worksheet->getStyle('M' . ($row))->getNumberFormat()->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

            $worksheet->getStyle("C$row:E$row")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyle("C$row:E$row")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->mergeCells("F$row:H$row");
        }
        $row++;
    }

    return $row;
}

function sumTable($worksheet, $row, $dataStartRow, $col)
{
    //$index = count($col) - 1;
    //applyBorders($worksheet, $col[0] . ($row - 1) . ':' . $col[$index] . ($row - 1), 'B0');

    $lastDataRow = $row - 1;
    //$worksheet->getRowDimension(($row))->setRowHeight(5);
    //$row++;
    //applyBorders($worksheet, $col[0] . $row . ':' . $col[$index] . $row, 'T1', 0);

    $worksheet->setCellValue('H' . ($row), "Total:");
    $worksheet->setCellValue('I' . ($row), "=SUM(I$dataStartRow:I$lastDataRow)");
    $worksheet->setCellValue('J' . ($row), "CBM/M3");

    $worksheet->setCellValue('L' . ($row), "Total:");
    $worksheet->setCellValue('M' . ($row), "=SUM(M$dataStartRow:M$lastDataRow)");

    $worksheet->getStyle('H' . ($row) . ':M' . ($row))->getFont()->setBold(true);
    $worksheet->getStyle('H' . ($row))->getAlignment()->setHorizontal('right');
    $worksheet->getStyle('I' . ($row))->getAlignment()->setHorizontal('right');
    $worksheet->getStyle('J' . ($row))->getAlignment()->setHorizontal('left');

    $worksheet->getStyle('L' . ($row))->getAlignment()->setHorizontal('right');
    $worksheet->getStyle('M' . ($row))->getAlignment()->setHorizontal('right');

    $worksheet->getStyle('H' . ($row) . ':M' . ($row))->getFont()->setName('Calibri');
    $worksheet->getStyle('H' . ($row) . ':M' . ($row))->getFont()->setSize(14);
    $worksheet->getCell('I' . ($row))->getStyle()->getFont()->setUnderline(true);
    $worksheet->getCell('M' . ($row))->getStyle()->getFont()->setUnderline(true);
    //'###,###,##0.###0'
    //'"฿ "#,##0.000'
    $worksheet->getStyle('I' . ($row))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED3);
    $worksheet->getStyle('M' . ($row))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    return $row;
}



function addHeaderData($worksheet)
{
    global $start_date;
    global $stop_date;
    global $Customer_Code;
    global $Customer_Name;

    $worksheet->getDefaultRowDimension()->setRowHeight(14);

    $styles = [
        'A' => ['width' => 5.00], 'B' => ['width' => 10.00], 'C' => ['width' => 10.00],
        'D' => ['width' => 10.00], 'E' => ['width' => 10.00], 'F' => ['width' => 10.00],
        'G' => ['width' => 30.00], 'H' => ['width' => 20.00], 'I' => ['width' => 25.00],
        'J' => ['width' => 20.00], 'K' => ['width' => 20.00], 'L' => ['width' => 20.00],
        'M' => ['width' => 25.00], 'N' => ['width' => 25.00],
    ];
    foreach ($styles as $col => $style) {
        $worksheet->getColumnDimension($col)->setWidth($style['width']);
    }

    $worksheet->getRowDimension('1')->setRowHeight(10, 'pt');
    $worksheet->getRowDimension('2')->setRowHeight(24, 'pt');
    $worksheet->getRowDimension('3')->setRowHeight(10, 'pt');
    //applyBorders($worksheet, 'B6:N6', 'B1');

    $worksheet->getStyle('B6:N6')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

    $worksheet->getRowDimension('6')->setRowHeight(14, 'pt');
    $worksheet->getRowDimension('7')->setRowHeight(14, 'pt');
    $worksheet->getRowDimension('11')->setRowHeight(14, 'pt');

    $worksheet->getStyle("N8")->getNumberFormat()->setFormatCode("DD MMMM, YYYY");

    $cellData = [
        'B2' => ['value' => 'ALBATROSS LOGISTICS COMPANY LIMITED', 'alignment' => 'left'],
        'B4' => ['value' => '336/11 Bowin Subdistrict, Si Racha District, Chonburi 20230', 'alignment' => 'left'],
        'B5' => ['value' => 'TEL: 033-135020', 'alignment' => 'left'],

        'B8' => ['value' => 'Customer : ' . $Customer_Name, 'alignment' => 'left'],
        'M8' => ['value' => 'Date : ', 'alignment' => 'right'],
        'N8' => ['value' => '=NOW()', 'alignment' => 'left'],
        'B9' => ['value' => 'Project : ' . $Customer_Code . ' Milkrun', 'alignment' => 'left'],
        'B10' => ['value' => 'Period : ' . $start_date . '  -  ' . $stop_date, 'alignment' => 'left'],
        'B12' => ['value' => 'Normal Trip Delivery', 'alignment' => 'left'],
    ];

    $worksheet->mergeCells('B2:L2');
    $worksheet->mergeCells('B4:L4');
    $worksheet->mergeCells('B5:L5');
    $worksheet->mergeCells('B8:L8');
    $worksheet->mergeCells('B9:L9');
    $worksheet->mergeCells('B10:L10');
    $worksheet->mergeCells('B12:N12');


    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');

        $cellStyle->getFont()->setSize(14);
        $cellStyle->getFont()->setName('Calibri');
        $cellStyle->getFont()->setBold(true);
        $cellStyle->getAlignment()->setWrapText(true);
    }

    $worksheet->getStyle('B2')->getFont()->setSize(24);
    $worksheet->getStyle('N8')->getFont()->setBold(false);
}

function addHeadTable($worksheet, $row)
{
    $rowheader = $row;
    $rowheadmerge = $row + 1;
    $cellData = [
        "B$rowheader" => ['value' => 'Item', 'alignment' => 'center'],
        "C$rowheader" => ['value' => 'Route', 'alignment' => 'center'],
        "F$rowheader" => ['value' => 'Supplier', 'alignment' => 'center'],
        "I$rowheader" => ['value' => 'Summary Part delivery (M3)', 'alignment' => 'center'],
        "J$rowheader" => ['value' => 'Unit Price', 'alignment' => 'center'],
        "L$rowheader" => ['value' => 'Total (THB/M3)', 'alignment' => 'center'],
        "M$rowheader" => ['value' => 'Amount (THB/M3)', 'alignment' => 'center'],
        "N$rowheader" => ['value' => 'Remark', 'alignment' => 'center'],


        "J$rowheadmerge" => ['value' => 'Transport Price (THB/M3)', 'alignment' => 'center'],
        "K$rowheadmerge" => ['value' => 'Planing Price (THB/M3)', 'alignment' => 'center'],
    ];

    $worksheet->mergeCells("B$rowheader:B$rowheadmerge");
    $worksheet->mergeCells("C$rowheader:E$rowheadmerge");
    $worksheet->mergeCells("F$rowheader:H$rowheadmerge");
    $worksheet->mergeCells("I$rowheader:I$rowheadmerge");
    $worksheet->mergeCells("J$rowheader:K$rowheader");
    $worksheet->mergeCells("L$rowheader:L$rowheadmerge");
    $worksheet->mergeCells("M$rowheader:M$rowheadmerge");
    $worksheet->mergeCells("N$rowheader:N$rowheadmerge");

    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');

        $cellStyle->getFont()->setSize(14);
        $cellStyle->getFont()->setName('Calibri');
        $cellStyle->getFont()->setBold(true);
        $cellStyle->getAlignment()->setWrapText(true);
    }

    applyBorders($worksheet, "B$rowheader:N$rowheadmerge", 'T1B1R1L1');
    $worksheet->getStyle("B$rowheader:N$rowheadmerge")
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('CCECFF');

    $row = $row + 1;
    return $row;
}


function Normal_Trip_Delivery($worksheet, $row, $cbmData)
{
    $row = $row + 1;
    $col = ['B', 'C', 'D', 'E', 'F', 'I', 'J', 'K', 'L', 'M', 'N'];
    $dataStartRow = $row;
    $lastRow = addDetailTable($worksheet, $cbmData, $row, $col);

    $cellStyle = $worksheet->getStyle("B$row:N" . $lastRow);
    $cellStyle->getFont()->setName('Cordia New');
    $cellStyle->getFont()->setSize(16);

    $beforelastRow = $lastRow - 1;
    applyBorders($worksheet, "B$row:B" . $beforelastRow, 'T1B1R1L1');
    applyBorders($worksheet, "F$row:N" . $beforelastRow, 'T1B1R1L1');

    $worksheet->getStyle("B$row:E" . $beforelastRow)->getAlignment()->setHorizontal('center');
    $worksheet->getStyle("I$row:N" . $beforelastRow)->getAlignment()->setHorizontal('right');

    $row = $lastRow;
    $row = sumTable($worksheet, $row, $dataStartRow, $col);

    return $row;
}

function Extra_Trip_Delivery($worksheet, $row, $cbmData)
{
    $start_row = $row + 1;
    $row = $row + 1;
    $col = ['B', 'C', 'D', 'E', 'F', 'I', 'J', 'K', 'L', 'M', 'N'];
    $dataStartRow = $start_row;

    if (empty($cbmData)) {
        return $row;
    }
    // var_dump($data);
    // exit();
    foreach ($cbmData as $rowData) {
        for ($i = 0; $i < count($col); $i++) {
            //echo $col[$i] . ' ';
            $worksheet->setCellValue($col[$i] . $row, '');
            $worksheet->getStyle("C$row:E$row")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->getStyle("C$row:E$row")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $worksheet->mergeCells("F$row:H$row");
        }
        //echo '<br>';
        $row++;
    }

    //exit();

    $lastRow = $row;
    //$lastRow = addDetailTable($worksheet, $cbmData, $row, $col);

    $cellStyle = $worksheet->getStyle("B$start_row:N" . $lastRow);
    $cellStyle->getFont()->setName('Cordia New');
    $cellStyle->getFont()->setSize(16);

    $beforelastRow = $lastRow - 1;
    applyBorders($worksheet, "B$start_row:B" . $beforelastRow, 'T1B1R1L1');
    applyBorders($worksheet, "F$start_row:N" . $beforelastRow, 'T1B1R1L1');

    $worksheet->getStyle("B$start_row:E" . $beforelastRow)->getAlignment()->setHorizontal('center');
    $worksheet->getStyle("I$start_row:N" . $beforelastRow)->getAlignment()->setHorizontal('right');

    $row = $lastRow;
    $row = sumTable($worksheet, $row, $dataStartRow, $col);
    return $row;
}



function Summary_Service_Charge($worksheet, $row, $Summary_Service_Charge)
{
    $row += 2;
    $col = ['B', 'C', 'I'];
    $data = ['Item', 'Summary', 'Total amount (THB)'];

    $index = count($col) - 1;
    $worksheet->getStyle($col[0] . ($row))->getFont()->setBold(true);
    $worksheet->getStyle($col[0] . ($row))->getAlignment()->setWrapText(false);

    for ($i = 0; $i < count($data); $i++) {
        $worksheet->setCellValue($col[$i] . ($row), $data[$i]);
    }
    $range = $col[0] . ($row) . ':' . $col[$index] . ($row);
    $worksheet->getStyle($range)->getAlignment()->setHorizontal('center');
    $worksheet->mergeCells('C' . ($row) . ':' . 'H' . ($row));

    $styleArray = array(
        'font'  => array(
            'bold'  => true,
            'color' => array('rgb' => '0000FF'),
            'size'  => 14,
            'name'  => 'Calibri'
        )
    );
    $worksheet->getStyle($range)->applyFromArray($styleArray);

    applyBorders($worksheet, $range, 'T1B1R1L1');

    $worksheet->getStyle($range)
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('CCFFFF');

    $StartRow = $row + 1;
    $dataStartRow = $row + 1;
    foreach ($Summary_Service_Charge as $rowData) {
        for ($i = 0; $i < count($col); $i++) {
            $worksheet->setCellValue($col[$i] . $dataStartRow, $rowData[$i]);
            $worksheet->getStyle("I$dataStartRow")->getAlignment()->setHorizontal('center');
            $worksheet->getStyle("I$dataStartRow")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $worksheet->mergeCells("C$dataStartRow:H$dataStartRow");
        }
        $dataStartRow++;
    }
    $lastRow = $dataStartRow - 1;
    applyBorders($worksheet, "B$StartRow:I$lastRow", 'T1B1R1L1');

    $row = $lastRow + 2;
    $worksheet->setCellValue('G' . ($row), "Grand total amount   :");
    $worksheet->mergeCells("G$row:H$row");
    $worksheet->setCellValue("I$row", "=SUM(I$StartRow:I$lastRow)");

    $worksheet->getStyle("B$StartRow:B$lastRow")->getAlignment()->setVertical('center');
    $cellStyle = $worksheet->getStyle("B$StartRow:I$lastRow");
    $cellStyle->getFont()->setSize(14);
    $cellStyle->getFont()->setName('Calibri');
    $cellStyle->getAlignment()->setWrapText(true);

    $worksheet->getStyle("G$row:I$row")->getFont()->setSize(14);
    $worksheet->getStyle("G$row:I$row")->getFont()->setBold(true);
    $worksheet->getStyle("G$row:H$row")->getAlignment()->setHorizontal('right');
    $worksheet->getStyle("I$row")->getAlignment()->setHorizontal('center');
    $worksheet->getStyle("I$row")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    return $row;
}

//if($Customer_Code == 'TSPK-C'){
$name_abt = 'Khun Worapong Chamnanpana';
$name_tspk = 'Khun Paiwan Pimpakorn';
//}
$Management_Fees = [
    ['Issued Draft Invoice', 'Approve Draft Invoice'], ['', ''],
    ['Sign__________________________', 'Sign__________________________'], ['', ''],
    ['Date__________________________', 'Date__________________________'], ['', ''],
    [$name_abt, $name_tspk],
    ['ALBATROSS LOGISTICS COMPANY LIMITED', 'THAI SUMMIT PK CORPORATION LTD.'],
];


function lastfooter($worksheet, $row, $Management_Fees)
{
    $row += 3;
    $dataStartRow = $row;
    $col = ['F', 'J'];
    $worksheet->getStyle('F' . ($dataStartRow) . ':L' . ($dataStartRow))->getFont()->setBold(true);
    $worksheet->getStyle('F' . ($dataStartRow + 6) . ':L' . ($dataStartRow + 6))->getFont()->setBold(true);
    foreach ($Management_Fees as $rowData) {
        for ($i = 0; $i < count($col); $i++) {
            $worksheet->setCellValue($col[$i] . $row, $rowData[$i]);
            $worksheet->getStyle($col[$i] . $row)->getAlignment()->setHorizontal('center');
        }
        $row++;
    }
    $lastRow = $row;
    for ($i = $dataStartRow; $i < $lastRow; $i++) {
        $worksheet->mergeCells('F' . ($i) . ':H' . ($i));
        $worksheet->mergeCells('J' . ($i) . ':L' . ($i));
    }
    $worksheet->getStyle('F' . ($dataStartRow) . ':L' . ($lastRow))->getFont()->setSize(14);
    return $row;
}


function box($worksheet)
{
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Logo');
    $drawing->setPath('../images/abt.png');
    $drawing->setCoordinates('M2');
    $drawing->setOffsetX(10);
    $drawing->setOffsetY(5);
    $drawing->setWidth(300);
    $drawing->setHeight(90);
    $drawing->setWorksheet($worksheet);
}



$spreadsheet = new Spreadsheet();
$worksheet = $spreadsheet->getActiveSheet();
$worksheet->setShowGridlines(false);
$worksheet->setTitle('Customer');
//setDefaultStyles($worksheet);
addHeaderData($worksheet);
box($worksheet);
$row = 13;
$row = addHeadTable($worksheet, $row);
$row = Normal_Trip_Delivery($worksheet, $row, $cbmArray);
$normal_trip_row = $row;

$row = $row + 2;
$worksheet->setCellValue("B$row", 'Extra Trip Delivery');
$worksheet->getStyle("B$row")->getFont()->setSize(14);
$worksheet->getStyle("B$row")->getFont()->setName('Calibri');
$worksheet->getStyle("B$row")->getFont()->setBold(true);
$row = $row + 1;
$row = addHeadTable($worksheet, $row);
$extraArray = array(1, 2, 3, 4, 5, 6, 7);
$row = Extra_Trip_Delivery($worksheet, $row, $extraArray);
$extra_trip_row = $row;

$Summary_Service_Charge = [
    [1, ' Normal Trip Delivery', '=M' . $normal_trip_row],
    [2, ' Extra Trip Delivery', '=M' . $extra_trip_row],
];
$row = Summary_Service_Charge($worksheet, $row, $Summary_Service_Charge);
$row = lastfooter($worksheet, $row, $Management_Fees);

$date = date_create($start_date);
$start_date = date_format($date, "d M y");

$date1 = date_create($stop_date);
$stop_date = date_format($date1, "d M y");

$filename = 'excel/fileoutput/Billing PK ' . $start_date . ' - ' . $stop_date . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);
