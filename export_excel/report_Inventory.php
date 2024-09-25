<?php
include('../common/common.php');
include('../php/connection.php');

if (
  !isset($_REQUEST['Group'])
)
  closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');
$Group = checkTXT($mysqli, $_REQUEST['Group']);

$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
$date = date("Ymd");
header("Content-Type: application/vnd.ms-excel");
//header('Content-Disposition: attachment; filename="myexcel.xls"');
header('Content-Disposition: attachment; filename="report_inventory' . $date . $randomString . '.xls"');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize('report_inventory' . $date . $randomString . '.xls'));
@readfile($filename);
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
  <?php
  if ($Group == 'PACKAGE_NUMBER') {
    echo '<table>
    <tr>
    <td>No</td>
    <td>Receive Date</td>
    <td>GRN Number</td>
    <td>DN Number</td>
    <td>Rack Number</td>
    <td>Package Number</td>
    <td>Part Number</td>
    <td>MMTH Part No.</td>
    <td>Serial Number</td>
    <td>Qty</td>
  </tr>';
    $sql = "SELECT
      ROW_NUMBER() OVER (ORDER BY tpm.Part_No ASC , Pick_Status ASC , trh.GRN_Number ASC , Receive_DateTime ASC) AS No,
      DATE_FORMAT(Receive_DateTime, '%d/%m/%y %H:%i') AS Receive_DateTime,
      trh.GRN_Number,
      trh.DN_Number,
      tiv.Rack_Number,
      tiv.Package_Number,
      tpm.Part_No,
      tpm.MMTH_Part_No,
      tiv.FG_Serial_Number,
      SUM(tiv.Qty) AS Qty,
      tlm.Location_Code,
      tiv.Area,
      Pick_Status
    FROM
      tbl_inventory tiv
        LEFT JOIN
      tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
        INNER JOIN
      tbl_receiving_header trh ON tiv.Receiving_Header_ID = trh.Receiving_Header_ID
        INNER JOIN
      tbl_part_master tpm ON tiv.Part_ID = tpm.Part_ID
    WHERE
      (tiv.Area = 'Storage'
        OR tiv.Area = 'TruckSIM'
        or tiv.Area = 'Received')
      GROUP BY tiv.Package_Number
      ORDER BY tpm.Part_No ASC, Pick_Status ASC, trh.GRN_Number ASC, Receive_DateTime ASC;";
    $re1 = sqlError($mysqli, __LINE__, $sql, 1);
    while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
      $No = $row['No'];
      $Receive_DateTime = $row['Receive_DateTime'];
      $GRN_Number = $row['GRN_Number'];
      $DN_Number = $row['DN_Number'];
      $Rack_Number = $row['Rack_Number'];
      $Package_Number = $row['Package_Number'];
      $Part_No = $row['Part_No'];
      $MMTH_Part_No = $row['MMTH_Part_No'];
      $FG_Serial_Number = $row['FG_Serial_Number'];
      $Qty = $row['Qty'];

      echo '<tr>
  <td>' . $No . '</td>
  <td>' . $Receive_DateTime . '</td>
  <td>' . $GRN_Number . '</td>
  <td>' . $DN_Number . '</td>
  <td>' . $Rack_Number . '</td>
  <td>' . $Package_Number . '</td>
  <td>' . $Part_No . '</td>
  <td>' . $MMTH_Part_No . '</td>
  <td>' . $FG_Serial_Number . '</td>
  <td>' . $Qty . '</td>
  </tr>';
    }
  } else if ($Group == 'PART_NO') {
    echo '<table>
    <tr>
    <td>No</td>
    <td>Part Number</td>
    <td>MMTH Part No.</td>
    <td>Qty</td>
  </tr>';
    $sql = "SELECT
      ROW_NUMBER() OVER (ORDER BY tpm.Part_No ASC) AS No,
      tpm.Part_No,
      tpm.MMTH_Part_No,
      SUM(tiv.Qty) AS Qty
    FROM
      tbl_inventory tiv
        LEFT JOIN
      tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
        INNER JOIN
      tbl_receiving_header trh ON tiv.Receiving_Header_ID = trh.Receiving_Header_ID
        INNER JOIN
      tbl_part_master tpm ON tiv.Part_ID = tpm.Part_ID
    WHERE
      (tiv.Area = 'Storage'
        OR tiv.Area = 'TruckSIM'
        or tiv.Area = 'Received')
      GROUP BY tpm.Part_No
      ORDER BY tpm.Part_No ASC;";
    $re1 = sqlError($mysqli, __LINE__, $sql, 1);
    while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
      $No = $row['No'];
      $Part_No = $row['Part_No'];
      $Qty = $row['Qty'];

      echo '<tr>
  <td>' . $No . '</td>
  <td>' . $Part_No . '</td>
  <td>' . $MMTH_Part_No . '</td>
  <td>' . $Qty . '</td>
  </tr>';
    }
  }

  ?>
</body>

</html>