<?php
include('../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$dataRouteArray = array();

$no = 1;
foreach ($dataArray as $row) {
    $formattedData = [
        $no,
        $row['truck_Control_No_show'],
        $row['truckNo_Date'],
        $row['status'],
        $row['Route_Code'],
        $row['pus_No_show'],
        $row['planin_date'],
        $row['planin_time'],
        $row['Project'],
        $row['Part_No'],
        $row['Part_Name'],
        $row['Actual_Qty'],
        $row['qty_actual'],
        $row['diff_qty'],
        $row['Package_Qty'],
        $row['CBM'],
        $row['cbm_actual'],
        $row['diff_cbm'],
        $row['SNP_Per_Pallet'],
        $row['Status_Pickup'],
        $row['Supplier_Name_Short'],
        $row['Supplier_Name'],
        '',
        $row['PO_No'],
        $row['Truck_Number'],
        $row['Truck_Type'],
        $row['Created_By_ID'],
        $row['Creation_Date'],
        $row['Creation_Time'],
        $row['Updated_By_ID'],
        $row['Last_Updated_Date'],
        $row['Last_Updated_Time'],
    ];
    $no++;
    $dataRouteArray[] = $formattedData;
}

// var_dump($dataRouteArray);
// exit();


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

function setDefaultStylesSheet($worksheet)
{
    $styles = [
        'A' => ['width' => 10.00], 'B' => ['width' => 20.00], 'C' => ['width' => 20.00], 'D' => ['width' => 20.00], 'E' => ['width' => 20.00],
        'F' => ['width' => 20.00], 'G' => ['width' => 20.00], 'H' => ['width' => 20.00], 'I' => ['width' => 20.00], 'J' => ['width' => 25.00],
        'K' => ['width' => 40.00], 'L' => ['width' => 15.00], 'M' => ['width' => 15.00], 'N' => ['width' => 15.00], 'O' => ['width' => 15.00],
        'P' => ['width' => 15.00], 'Q' => ['width' => 15.00], 'R' => ['width' => 15.00], 'S' => ['width' => 15.00], 'T' => ['width' => 20.00],
        'U' => ['width' => 20.00], 'V' => ['width' => 50.00], 'W' => ['width' => 20.00], 'X' => ['width' => 20.00], 'Y' => ['width' => 20.00],
        'Z' => ['width' => 20.00], 'AA' => ['width' => 20.00], 'AB' => ['width' => 20.00], 'AC' => ['width' => 20.00], 'AD' => ['width' => 20.00],
        'AE' => ['width' => 20.00], 'AF' => ['width' => 20.00],
    ];
    $worksheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri');
    $worksheet->getParent()->getDefaultStyle()->getFont()->setSize(9);
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setVertical('Center');
    $worksheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);
    //$worksheet->getParent()->getDefaultStyle()->getAlignment()->setHorizontal('Center');

    //$worksheet->getRowDimension('2')->setRowHeight(5, 'pt');
    foreach ($styles as $col => $style) {
        $worksheet->getColumnDimension($col)->setWidth($style['width']);
    }
}

function addDetailTableSheet($worksheet, $data, $row, $col, $border = 1)
{
    $row += 1;
    if (empty($data)) {
        return $row;
    }

    //var_dump($data);

    foreach ($data as $rowData) {
        for ($i = 0; $i < count($col); $i++) {
            $worksheet->getStyle("P" . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED3);
            $worksheet->getStyle("Q" . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED3);
            $worksheet->getStyle("R" . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED3);
            $worksheet->setCellValue($col[$i] . $row, $rowData[$i]);
        }
        $row++;
    }
    return $row;
}

function addHeaderData($worksheet)
{
    $cellData = [
        //header table
        'A1' => ['value' => 'No.', 'alignment' => 'center',],
        'B1' => ['value' => 'Truck Control No.', 'alignment' => 'center',],
        'C1' => ['value' => 'Truck Control Date', 'alignment' => 'center',],
        'D1' => ['value' => 'Status', 'alignment' => 'center',],
        'E1' => ['value' => 'Route Code', 'alignment' => 'center',],
        'F1' => ['value' => 'Pus No.', 'alignment' => 'center',],
        'G1' => ['value' => 'Operation Date', 'alignment' => 'center',],
        'H1' => ['value' => 'Operation Time', 'alignment' => 'center',],
        'I1' => ['value' => 'Project', 'alignment' => 'center',],
        'J1' => ['value' => 'Part No.', 'alignment' => 'center',],
        'K1' => ['value' => 'Part Name', 'alignment' => 'center',],
        'L1' => ['value' => 'Plan Qty', 'alignment' => 'center',],
        'M1' => ['value' => 'Actual Qty', 'alignment' => 'center',],
        'N1' => ['value' => 'Diff Qty', 'alignment' => 'center',],
        'O1' => ['value' => 'Package Qty', 'alignment' => 'center',],
        'P1' => ['value' => 'Plan CBM', 'alignment' => 'center',],
        'Q1' => ['value' => 'Actual CBM', 'alignment' => 'center',],
        'R1' => ['value' => 'Diff CBM', 'alignment' => 'center',],
        'S1' => ['value' => 'SNP/Pallet', 'alignment' => 'center',],
        'T1' => ['value' => 'Activity', 'alignment' => 'center',],
        'U1' => ['value' => 'Supplier', 'alignment' => 'center',],
        'V1' => ['value' => 'Supplier Name', 'alignment' => 'center',],
        'W1' => ['value' => 'Service Rate', 'alignment' => 'center',],
        'X1' => ['value' => 'PO No.', 'alignment' => 'center',],
        'Y1' => ['value' => 'Truck No.', 'alignment' => 'center',],
        'Z1' => ['value' => 'Truck Type', 'alignment' => 'center',],
        'AA1' => ['value' => 'Creation By', 'alignment' => 'center',],
        'AB1' => ['value' => 'Creation Date', 'alignment' => 'center',],
        'AC1' => ['value' => 'Creation Time', 'alignment' => 'center',],
        'AD1' => ['value' => 'Updated By', 'alignment' => 'center',],
        'AE1' => ['value' => 'Last Updated Date', 'alignment' => 'center',],
        'AF1' => ['value' => 'Last Updated Time', 'alignment' => 'center',],

    ];


    $worksheet->getStyle('A1:AF1')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('def0fc');


    //$worksheet->getStyle("O")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED3);


    foreach ($cellData as $cell => $data) {
        $worksheet->setCellValue($cell, $data['value']);
        $cellStyle = $worksheet->getStyle($cell);
        $cellStyle->getAlignment()->setHorizontal($data['alignment']);
        $cellStyle->getAlignment()->setVertical('center');
    }

    //applyBorders($worksheet, 'A3:H3', 'T1B1R1L1');
}


function summary_data($worksheet, $row, $data)
{
    $col = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
        'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD',
        'AE', 'AF'
    ];
    $dataStartRow = $row + 1;
    $lastRow = addDetailTableSheet($worksheet, $data, $row, $col);
    $row = $lastRow;
    $borderCode = 'L1R1T1B1';
    $row_border = $row - 1;
    applyBorders($worksheet, 'A1:AF' . $row_border, $borderCode);
    $worksheet->getStyle('A1:AF' . $row_border, $borderCode)->getAlignment()->setVertical('center');
    $worksheet->getStyle('A1:AF' . $row_border, $borderCode)->getAlignment()->setHorizontal('center');

    return $row;
}


$spreadsheet = new Spreadsheet();
$worksheet = $spreadsheet->getActiveSheet();
$worksheet->setShowGridlines(false);
$worksheet->setTitle('Sheet 1');
setDefaultStylesSheet($worksheet);
addHeaderData($worksheet);
$row = 1;
summary_data($worksheet, $row, $dataRouteArray);

$date = date_create($Start_Date);
$Start_Date = date_format($date, "d-m-Y");
//$date = date('Y-m-d');

if($Customer_Code == ''){
    $Customer_Code = 'TSPK';
}
$filename = 'excel/fileoutput/Transaction Billing ' . $Customer_Code . ' MR ' . $Start_Date . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);
