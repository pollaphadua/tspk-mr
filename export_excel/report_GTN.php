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
header('Content-Disposition: attachment; filename="report_GTN' . $date . $randomString . '.xls"');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize('report_GTN' . $date . $randomString . '.xls'));
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
    <td>Ship Date</td>
    <td>Ship Time</td>
    <td>GTN Number</td>
    <td>PS Number</td>
    <td>PDS_No</td>
    <td>Order_No</td>
    <td>Order_Box_No</td>
    <td>Part Number</td>
    <td>MMTH Part No.</td>
    <td>Part Name</td>
    <td>Package Number</td>
    <td>SNP</td>
    <td>Truck_ID</td>
    <td>Truck_Driver</td>
    <td>Truck_Type</td>
    <td>Status Shipping</td>
    <td>Created By</td>
  </tr>';

  $sql = "SELECT 
      ROW_NUMBER() OVER (ORDER BY tsh.GTN_Number DESC , odn.PS_Number DESC) AS No,
      DATE_FORMAT(tsh.Ship_Date, '%d/%m/%y') AS Ship_Date,
      DATE_FORMAT(tsh.Ship_Time, '%H:%i:%s') AS Ship_Time,
      tsh.GTN_Number,
      odn.PS_Number,
      odn.PDS_No,
      odn.Order_No,
      odn.Order_Box_No,
      tpp.Part_No,
      tpm.MMTH_Part_No,
      tpm.Part_Name,
      odn.Package_Number,
      odn.SNP,
      tsh.Truck_ID,
      tsh.Truck_Driver,
      tsh.Truck_Type,
      tsh.Status_Shipping,
      CONCAT(user_fName,
                ' ',
                SUBSTRING(user_lname, 1, 1),
                '.') AS Created_By
  FROM
      tbl_shipping_header tsh
          INNER JOIN
      tbl_shipping_pre tsp ON tsh.Shipping_Header_ID = tsp.Shipping_Header_ID
          INNER JOIN
      tbl_order_box_no odn ON tsp.PS_Number = odn.PS_Number
          AND tsp.Order_Box_No = odn.Order_Box_No
          INNER JOIN
      tbl_picking_pre tpp ON odn.Order_Box_No = tpp.Order_Box_No
          INNER JOIN
        tbl_part_master tpm ON tpp.Part_ID = tpm.Part_ID
            INNER JOIN
        tbl_user tuser ON tuser.user_id = tsp.Created_By_ID
  WHERE
      tsh.Status_Shipping = 'COMPLETE'
  GROUP BY odn.Order_Box_No , tpp.Package_Number
  ORDER BY tsh.GTN_Number DESC , odn.PS_Number DESC;";
$re1 = sqlError($mysqli, __LINE__, $sql, 1);
  while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
    $No = $row['No'];
    $Ship_Date = $row['Ship_Date'];
    $Ship_Time = $row['Ship_Time'];
    $GTN_Number = $row['GTN_Number'];
    $PS_Number = $row['PS_Number'];
    $PDS_No = $row['PDS_No'];
    $Order_No = $row['Order_No'];
    $Order_Box_No = $row['Order_Box_No'];
    $Part_No = $row['Part_No'];
    $MMTH_Part_No = $row['MMTH_Part_No'];
    $Part_Name = $row['Part_Name'];
    $Package_Number = $row['Package_Number'];
    $SNP = $row['SNP'];
    $Truck_ID = $row['Truck_ID'];
    $Truck_Driver = $row['Truck_Driver'];
    $Truck_Type = $row['Truck_Type'];
    $Status_Shipping = $row['Status_Shipping'];
    $Created_By = $row['Created_By'];

    echo '<tr>
    <td>' . $No . '</td>
    <td>' . $Ship_Date . '</td>
    <td>' . $Ship_Time . '</td>
    <td>' . $GTN_Number . '</td>
    <td>' . $PS_Number . '</td>
    <td>' . $PDS_No . '</td>
    <td>' . $Order_No . '</td>
    <td>' . $Order_Box_No . '</td>
    <td>' . $Part_No . '</td>
    <td>' . $MMTH_Part_No . '</td>
    <td>' . $Part_Name . '</td>
    <td>' . $Package_Number . '</td>
    <td>' . $SNP . '</td>
    <td>' . $Truck_ID . '</td>
    <td>' . $Truck_Driver . '</td>
    <td>' . $Truck_Type . '</td>
    <td>' . $Status_Shipping . '</td>
    <td>' . $Created_By . '</td>
  </tr>';
  }
  ?>
</body>

</html>