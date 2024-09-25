<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function getSupplierID($mysqli, $Supplier_Name_Short, $Customer_ID)
{
    $sql = "SELECT BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID from tbl_supplier_master where Supplier_Name_Short='$Supplier_Name_Short' AND Customer_ID = uuid_to_bin('$Customer_ID',true) limit 1;";
    // exit($sql);
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) {
        throw new Exception("ไม่พบข้อมูล Supplier " . $Supplier_Name_Short);
        closeDBT($mysqli, 1, jsonRow($re1, true, 0));
    }
    return  $re1->fetch_array(MYSQLI_ASSOC)['Supplier_ID'];
}

// function getSupplierID($mysqli, $Supplier_Name_Short, $Customer)
// {
//     $sql = "SELECT BIN_TO_UUID(Supplier_ID,TRUE) AS Supplier_ID from tbl_supplier_master where Supplier_Name_Short='$Supplier_Name_Short' AND Customer = '$Customer' limit 1;";
//     // exit($sql);
//     $re1 = sqlError($mysqli, __LINE__, $sql);
//     if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Supplier " . $Supplier_Name_Short);
//     return  $re1->fetch_array(MYSQLI_ASSOC)['Supplier_ID'];
// }

function getCustomerID($mysqli, $Customer_Code)
{
    $sql = "SELECT BIN_TO_UUID(Customer_ID,TRUE) AS Customer_ID from tbl_customer_master where Customer_Code = '$Customer_Code' limit 1;";
    //exit($sql);
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) {
        throw new Exception("ไม่พบข้อมูล Customer " . $Customer_Code);
        closeDBT($mysqli, 1, jsonRow($re1, true, 0));
    }
    return  $re1->fetch_array(MYSQLI_ASSOC)['Customer_ID'];
}

function getRouteID($mysqli, $Route_Code, $Customer_ID)
{
    $sql = "SELECT BIN_TO_UUID(Route_ID,TRUE) AS Route_ID from tbl_route_master where 
    Route_Code='$Route_Code'
     AND Customer_ID = uuid_to_bin('$Customer_ID',true)
     AND Status = 'ACTIVE'
     limit 1;";
    //exit($sql);
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Route " . $Route_Code);
    return  $re1->fetch_array(MYSQLI_ASSOC)['Route_ID'];
}

function getTruckID($mysqli, $Truck_Number, $Truck_Type)
{
    $sql = "SELECT BIN_TO_UUID(Truck_ID,TRUE) as Truck_ID
    FROM 
        tbl_truck_master
    WHERE
        Truck_Number = '$Truck_Number'
            AND Truck_Type = '$Truck_Type'
            AND Status = 'ACTIVE' limit 1;";
    // exit($sql);
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Truck " . $Truck_Number);
    return  $re1->fetch_array(MYSQLI_ASSOC)['Truck_ID'];
}

function getDriverID($mysqli, $Driver_Name)
{
    $sql = "SELECT BIN_TO_UUID(Driver_ID,TRUE) AS Driver_ID from tbl_driver_master 
    where Driver_Name = '$Driver_Name'
        AND Status = 'ACTIVE'
    limit 1;";
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล Driver " . $Driver_Name);
    return  $re1->fetch_array(MYSQLI_ASSOC)['Driver_ID'];
}

function getTransactionID($mysqli, $truck_Control_No)
{
    $sql = "SELECT 
        BIN_TO_UUID(transaction_ID,TRUE) AS transaction_ID
    FROM 
        tbl_transaction 
    WHERE 
        truck_Control_No = '$truck_Control_No';";
    $re1 = sqlError($mysqli, __LINE__, $sql);
    if ($re1->num_rows == 0) closeDBT($mysqli, 2, 'ERROR LINE ' . __LINE__ . "<br>ไม่พบข้อมูล truck Control No " . $truck_Control_No);
    return  $re1->fetch_array(MYSQLI_ASSOC)['transaction_ID'];
}


function selectColumnFromArray($dataAr, $columnAr)
{
    $returnData = array();
    for ($i = 0, $len = count($dataAr); $i < $len; $i++) {
        $ar = array();
        for ($i2 = 0, $len2 = count($columnAr); $i2 < $len2; $i2++) {
            $ar[$columnAr[$i2]] = $dataAr[$i][$columnAr[$i2]];
        }
        $returnData[] = $ar;
    }
    return $returnData;
}

function group_by($key, $data)
{
    $result = array();

    foreach ($data as $val) {
        if (array_key_exists($key, $val)) {
            $result[$val[$key]][] = $val;
        } else {
        }
    }

    return $result;
}
