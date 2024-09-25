<?php
include('../common/common.php');
include('../php/connection.php');

// if (
//   !isset($_REQUEST['Group'])
// )
//   closeDBT($mysqli, 2, 'ข้อมูลไม่ถูกต้อง 1');
// $Group = checkTXT($mysqli, $_REQUEST['Group']);

$randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
$date = date("Ymd");
header("Content-Type: application/vnd.ms-excel");
//header('Content-Disposition: attachment; filename="myexcel.xls"');
header('Content-Disposition: attachment; filename="report_GRN' . $date . $randomString . '.xls"');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize('report_GRN' . $date . $randomString . '.xls'));
@readfile($filename);
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
  <?php
  echo '<table>
    <tr>
    <td>No</td>
    <td>Receive Date</td>
    <td>Receive Time</td>
    <td>GRN Number</td>
    <td>DN Number</td>
    <td>Part Number</td>
    <td>MMTH Part No.</td>
    <td>Part Name</td>
    <td>Package Number</td>
    <td>Qty</td>
    <td>Created By</td>
  </tr>';

  $sql = "SELECT 
        ROW_NUMBER() OVER (ORDER BY Receive_DateTime ASC , GRN_Number ASC , tpm.Part_No ASC , trp.FG_Serial_Number ASC) AS No,
        DATE_FORMAT(Receive_DateTime, '%d/%m/%y') AS Receive_Date,
        DATE_FORMAT(Receive_DateTime, '%H:%i:%s') AS Receive_Time,
        GRN_Number AS GRN_Number,
        trh.DN_Number AS DN_Number,
        tpm.Part_No AS Part_Number,
        tpm.MMTH_Part_No,
        Part_Name AS Part_Name,
        trp.Package_Number AS Package_Number,
        trp.Qty AS Qty,
        CONCAT(user_fName,
                ' ',
                SUBSTRING(user_lname, 1, 1),
                '.') AS Created_By
    FROM
        tbl_receiving_header trh
            INNER JOIN
        tbl_receiving_pre trp ON trp.Receiving_Header_ID = trh.Receiving_Header_ID
            LEFT JOIN
        tbl_inventory tiv ON trp.FG_Serial_Number = tiv.FG_Serial_Number
            LEFT JOIN
        tbl_location_master tlm ON tiv.Location_ID = tlm.Location_ID
            LEFT JOIN
        tbl_part_master tpm ON trp.Part_ID = tpm.Part_ID
            INNER JOIN
        tbl_user tuser ON tuser.user_id = trp.Created_By_ID
    WHERE
        Receive_DateTime IS NOT NULL
        AND Ship_Status != 'Y'
    GROUP BY GRN_Number, trp.Package_Number
    ORDER BY Receive_DateTime ASC , GRN_Number ASC , tpm.Part_No ASC , trp.FG_Serial_Number ASC;";
  $re1 = sqlError($mysqli, __LINE__, $sql, 1);
  while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
    $No = $row['No'];
    $Receive_Date = $row['Receive_Date'];
    $Receive_Time = $row['Receive_Time'];
    $GRN_Number = $row['GRN_Number'];
    $DN_Number = $row['DN_Number'];
    $Part_Number = $row['Part_Number'];
    $MMTH_Part_No = $row['MMTH_Part_No'];
    $Part_Name = $row['Part_Name'];
    $Package_Number = $row['Package_Number'];
    $Qty = $row['Qty'];
    $Created_By = $row['Created_By'];

    echo '<tr>
  <td>' . $No . '</td>
  <td>' . $Receive_Date . '</td>
  <td>' . $Receive_Time . '</td>
  <td>' . $GRN_Number . '</td>
  <td>' . $DN_Number . '</td>
  <td>' . $Part_Number . '</td>
  <td>' . $MMTH_Part_No . '</td>
  <td>' . $Part_Name . '</td>
  <td>' . $Package_Number . '</td>
  <td>' . $Qty . '</td>
  <td>' . $Created_By . '</td>
  </tr>';
  }
  ?>
</body>

</html>