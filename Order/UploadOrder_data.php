<?php
ini_set('post_max_size', '2024M');
ini_set('upload_max_filesize', '2024M');
ini_set('memory_limit', '2024M');
ini_set('max_execution_time', 300);

if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if (!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'UploadOrder'})) {
    echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
    exit();
} else if ($_SESSION['xxxRole']->{'UploadOrder'}[0] == 0) {
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



    } else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 10 && $type <= 20) //insert
{
    if ($_SESSION['xxxRole']->{'UploadOrder'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
    if ($type == 11) {
    } else if ($type == 12) {
    } else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 20 && $type <= 30) //update
{
    if ($_SESSION['xxxRole']->{'UploadOrder'}[2] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
    if ($type == 21) {
    } else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 30 && $type <= 40) //delete
{
    if ($_SESSION['xxxRole']->{'UploadOrder'}[3] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
    if ($type == 31) {
    } else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 40 && $type <= 50) //save
{
    if ($_SESSION['xxxRole']->{'UploadOrder'}[1] == 0) closeDBT($mysqli, 9, 'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
    if ($type == 41) {
    } else closeDBT($mysqli, 2, 'TYPE ERROR');
} else if ($type > 50 && $type <= 60) //upload
{
    if ($type == 54) {
        if (!isset($_FILES["upload"])) {
            echo json_encode(array('status' => 'server', 'mms' => 'ไม่พบไฟล์ UPLOAD'));
            closeDB($mysqli);
        }
        // $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
        // $fileName = $randomString . '_' . $_FILES["upload"]["name"];
        // $tempName = $_FILES["upload"]["tmp_name"];
        $fileName = $_FILES["upload"]["name"];
        $tempName = $_FILES["upload"]["tmp_name"];
        $dir = "order_file";
        $pathDir = "../order_file";

        if (!is_dir($pathDir)) {
            if (!mkdir($pathDir, 0770, true)) {
                die('Failed to create directories...');
            }
        }

        if (move_uploaded_file($tempName, "../order_file/" . $fileName)) {
            $file_info = pathinfo("../order_file/" . $fileName);
            $myfile = fopen("../order_file/" . $file_info['basename'], "r") or die("Unable to open file!");
            $data_file = fread($myfile, filesize("../order_file/" . $file_info['basename']));
            $dataAR_line = explode('<br />', nl2br($data_file));
            //print_r($dataAR_line);
            //exit();
            fclose($myfile);
            $mysqli->autocommit(FALSE);
            try {
                $full_filename = $fileName;
                $temp = explode("_", $fileName);
                $site_customer = $temp[0];
                $fileName = $temp[1];
                $explode = explode("-", $fileName);
                $date = date_create($explode[0]);
                $filename_date = date_format($date, "Y-m-d");
                $explode1 = explode(".", $explode[1]);
                $str = substr($explode[1], 0, 6);
                $time = date_create($str);
                $filename_time = date_format($time, "H:i:s");
                $File_Name_DateTime = $filename_date . " " . $filename_time;
                $path = 'C:/node/tspk-c/saveFIle/';
                $File_Name = $path . $full_filename;

                $sql = "SELECT
                    BIN_TO_UUID(Order_From_Email_Header_ID,TRUE) as Order_From_Email_Header_ID,
                    File_Name,
                    File_Name_DateTime,
                    Process_Status
                FROM
                    tbl_order_from_email_header
                WHERE 
                    File_Name = '$File_Name';";
                $re1 = sqlError($mysqli, __LINE__, $sql, 1);
                if ($re1->num_rows > 0) {
                    throw new Exception('ไฟล์นี้มีการอัปโหลดไปแล้ว ' . __LINE__);
                }

                if ($site_customer == 'TSPK') {
                    $site_customer = 'TSPK-C';
                    $prefix = 'C';
                } else if ($site_customer == 'TSPKL') {
                    $site_customer = 'TSPK-L';
                    $prefix = 'L';
                } else if ($site_customer == 'TSPKBP') {
                    $site_customer = 'TSPK-BP';
                    $prefix = 'B';
                }

                //$Customer_ID = getCustomerID($mysqli, $site_customer);

                $sql = "INSERT IGNORE INTO tbl_order_from_email_header (
                    File_Name,
                    File_Name_DateTime,
                    site_customer,
                    Received_Date,
                    Send_DateTime,
                    Creation_DateTime,
                    Created_By_ID)
                VALUES (
                    '$File_Name',
                    '$File_Name_DateTime',
                    '$site_customer',
                    now(),
                    now(),
                    now(),
                    $cBy);";
                sqlError($mysqli, __LINE__, $sql, 1, 0);
                if ($mysqli->affected_rows == 0) {
                    throw new Exception('ไม่มีการอัพเดท');
                }

                $sql = "SELECT
                    BIN_TO_UUID(Order_From_Email_Header_ID,TRUE) as Order_From_Email_Header_ID,
                    File_Name,
                    File_Name_DateTime,
                    Process_Status
                FROM
                    tbl_order_from_email_header
                WHERE 
                    File_Name = '$File_Name';";
                //exit($sql);
                $re1 = sqlError($mysqli, __LINE__, $sql, 1);
                if ($re1->num_rows == 0) {
                    throw new Exception('ไม่พบข้อมูล ' . __LINE__);
                }
                while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
                    $Order_From_Email_Header_ID = $row['Order_From_Email_Header_ID'];
                }

                for ($i = 0; $i < sizeof($dataAR_line); $i++) {
                    if ($i >= 1) {
                        $exp_data = explode("|", trim($dataAR_line[$i]));
                        if (sizeof($exp_data) == 1) {
                            break;
                        }

                        $Refer_ID = $exp_data[0];
                        $Pickup_Date = $exp_data[1];
                        $Part_No = $exp_data[2];
                        $Part_Name = $exp_data[3];
                        $Supplier_Code = $exp_data[4];
                        $Supplier_Name = $exp_data[5];
                        $Qty = $exp_data[6];
                        $UM = $exp_data[7];
                        $PO_No = $exp_data[8];
                        $PO_Line = $exp_data[9];
                        $PO_Release = $exp_data[10];
                        $Command = $exp_data[11];

                        $refer_date = date_create($Pickup_Date);
                        $ReferDate = date_format($refer_date, "Ymd");
                        $Refer_ID_unique = $prefix . $ReferDate . $Refer_ID;
                        //$Refer_ID_unique = (sqlError($mysqli, __LINE__, "SELECT func_GenRuningNumber('refer',0) Refer_ID_unique", 1))->fetch_array(MYSQLI_ASSOC)['Refer_ID_unique'];

                        $sqlArray[] = array(
                            'Order_From_Email_Header_ID' => 'uuid_to_bin("' . $Order_From_Email_Header_ID . '",true)',
                            'Refer_ID' => stringConvert($Refer_ID),
                            'Refer_ID_unique' => stringConvert($Refer_ID_unique),
                            'Pickup_Date' => stringConvert($Pickup_Date),
                            'Part_No' => stringConvert($Part_No),
                            'Part_Name' => stringConvert($Part_Name),
                            'Supplier_Code' => stringConvert($Supplier_Code),
                            'Supplier_Name' => stringConvert($Supplier_Name),
                            'Qty' => $Qty,
                            'UM' => stringConvert($UM),
                            'PO_No' => stringConvert($PO_No),
                            'PO_Line' => $PO_Line,
                            'PO_Release' => $PO_Release,
                            'Command' => stringConvert($Command),
                            'Creation_DateTime' => 'now()',
                            'Created_By_ID' => $cBy
                        );
                    }
                }

                $total = 0;
                if (count($sqlArray) > 0) {
                    $sqlName = prepareNameInsert($sqlArray[0]);
                    $sqlChunk = array_chunk($sqlArray, 500);

                    for ($i = 0, $len = count($sqlChunk); $i < $len; $i++) {
                        $sqlValues = prepareValueInsert($sqlChunk[$i]);
                        $sql = "INSERT INTO tbl_order_from_email_body $sqlName VALUES $sqlValues";
                        sqlError($mysqli, __LINE__, $sql, 1, 0);
                        $total += $mysqli->affected_rows;
                    }

                    //$mysqli->commit();

                    if ($total == 0) {
                        throw new Exception('ไม่มีรายการอัพเดท' . $mysqli->error);
                    } else {
                        $sql = "UPDATE tbl_order_from_email_body 
                        SET 
                            Process_Status = 'Processing',
                            Last_Updated_DateTime = now(),
                            Updated_By_ID = $cBy
                        WHERE 
                            BIN_TO_UUID(Order_From_Email_Header_ID,TRUE) = '$Order_From_Email_Header_ID';";
                        sqlError($mysqli, __LINE__, $sql, 1);
                        if ($mysqli->affected_rows == 0) {
                            throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                        }


                        $sql = "WITH a AS (
                            SELECT 
                                BIN_TO_UUID(t2.Order_From_Email_Body_ID, TRUE) AS Order_From_Email_Body_ID,
                                t2.Refer_ID,
                                t2.Refer_ID_unique,
                                t2.Pickup_Date,
                                t2.Part_No,
                                t2.Supplier_Code,
                                t2.Qty,
                                t2.Command,
                                IFNULL(t4.Supplier_Code, '') AS Check_Supplier_Master,
                                IFNULL(torder.Refer_ID_unique, '') AS Check_Refer_ID
                            FROM
                                tbl_order_from_email_header t1
                                    INNER JOIN
                                tbl_order_from_email_body t2 ON t1.Order_From_Email_Header_ID = t2.Order_From_Email_Header_ID
                                    INNER JOIN
                                tbl_customer_master tcus ON t1.site_customer = tcus.Customer_Code
                                    LEFT JOIN
                                tbl_supplier_master t4 ON t2.Supplier_Code = t4.Supplier_Code AND tcus.Customer_ID = t4.Customer_ID
                                    LEFT JOIN
                                tbl_part_master t3 ON t4.Supplier_ID = t3.Supplier_ID AND t2.Part_No = t3.Part_No
                                    LEFT JOIN
                                tbl_order torder ON torder.Part_No = t2.Part_No
                                    AND torder.PO_No = t2.PO_No
                                    AND torder.PO_Line = t2.PO_Line
                            WHERE
                                BIN_TO_UUID(t1.Order_From_Email_Header_ID, TRUE) = '$Order_From_Email_Header_ID'
                                    AND t2.Process_Status = 'Processing')
                            SELECT a.*,
                            IFNULL(a1.Part_No, '') AS Check_Part_Master
                            FROM a
                                LEFT JOIN
                            tbl_part_master a1 ON a.Part_No = a1.Part_No
                            GROUP BY a.Refer_ID;";
                        //exit($sql);
                        $re1 = sqlError($mysqli, __LINE__, $sql, 1);
                        if ($re1->num_rows == 0) {
                            throw new Exception('ไม่พบข้อมูล ' . __LINE__);
                        }
                        while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
                            $Order_From_Email_Body_ID = $row['Order_From_Email_Body_ID'];
                            $Refer_ID = $row['Refer_ID'];
                            $Refer_ID_unique = $row['Refer_ID_unique'];
                            $Command = $row['Command'];
                            $Check_Part_Master = $row['Check_Part_Master'];
                            $Check_Supplier_Master = $row['Check_Supplier_Master'];
                            $Check_Refer_ID = $row['Check_Refer_ID'];

                            if ($Command == 'NEW') {
                                $sql = "INSERT IGNORE INTO tbl_order (
                                    Refer_ID,
                                    Refer_ID_unique,
                                    Pickup_Date,
                                    Part_ID,
                                    Part_No,
                                    Part_Name,
                                    Supplier_ID,
                                    Supplier_Code,
                                    Supplier_Name_Short,
                                    Supplier_Name,
                                    Qty,
                                    UM,
                                    PO_No,
                                    PO_Line,
                                    PO_Release,
                                    Command,
                                    Creation_DateTime,
                                    Created_By_ID,
                                    File_Name
                                )
                                SELECT
                                    tob.Refer_ID,
                                    tob.Refer_ID_unique,
                                    tob.Pickup_Date,
                                    tpm.Part_ID,
                                    tob.Part_No,
                                    tob.Part_Name,
                                    tsm.Supplier_ID,
                                    tob.Supplier_Code,
                                    tsm.Supplier_Name_Short,
                                    tob.Supplier_Name,
                                    tob.Qty,
                                    tob.UM,
                                    tob.PO_No,
                                    tob.PO_Line,
                                    tob.PO_Release,
                                    tob.Command,
                                    now(),
                                    $cBy,
                                    '$fileName'
                                FROM
                                    tbl_order_from_email_header t1
                                        INNER JOIN
                                    tbl_order_from_email_body tob ON t1.Order_From_Email_Header_ID = tob.Order_From_Email_Header_ID
                                        INNER JOIN
                                    tbl_customer_master tcus ON t1.site_customer = tcus.Customer_Code
										INNER JOIN
									tbl_part_master tpm ON tob.Part_No = tpm.Part_No AND tcus.Customer_ID = tpm.Customer_ID
                                        INNER JOIN
									tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID AND tcus.Customer_ID = tsm.Customer_ID
                                    AND tsm.Supplier_Code = tob.Supplier_Code
                                WHERE 
                                    BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID'
                                        AND tob.Process_Status = 'Processing';";
                                sqlError($mysqli, __LINE__, $sql, 1);
                                // if ($mysqli->affected_rows == 0) {
                                //     //exit($sql);
                                //     throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                // }

                                if ($Check_Refer_ID != '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Duplicate',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else if ($Check_Part_Master == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Part_Master_Problem',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else if ($Check_Supplier_Master == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Supplier_Master_Problem',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Process_Complete',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                }
                            } else if ($Command == 'UPDATE') {
                                $sql = "UPDATE tbl_order torder,
                                (
                                SELECT
                                    tob.Refer_ID,
                                    tob.Refer_ID_unique,
                                    tob.Pickup_Date,
                                    tpm.Part_ID,
                                    tob.Part_No,
                                    tob.Part_Name,
                                    tsm.Supplier_ID,
                                    tob.Supplier_Code,
                                    tsm.Supplier_Name_Short,
                                    tob.Supplier_Name,
                                    tob.Qty,
                                    tob.UM,
                                    tob.PO_No,
                                    tob.PO_Line,
                                    tob.PO_Release,
                                    tob.Command
                                FROM
                                    tbl_order_from_email_header t1
                                        INNER JOIN
                                    tbl_order_from_email_body tob ON t1.Order_From_Email_Header_ID = tob.Order_From_Email_Header_ID
                                        INNER JOIN
                                    tbl_customer_master tcus ON t1.site_customer = tcus.Customer_Code
										INNER JOIN
									tbl_part_master tpm ON tob.Part_No = tpm.Part_No AND tcus.Customer_ID = tpm.Customer_ID
                                        INNER JOIN
									tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID AND tcus.Customer_ID = tsm.Customer_ID
                                    AND tsm.Supplier_Code = tob.Supplier_Code
                                WHERE 
                                    BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID'
                                        AND tob.Process_Status = 'Processing'
                                ) AS torder1
                                SET
                                    torder.Pickup_Date = torder1.Pickup_Date,
                                    torder.Part_ID = torder1.Part_ID,
                                    torder.Part_No = torder1.Part_No,
                                    torder.Part_Name = torder1.Part_Name,
                                    torder.Supplier_ID = torder1.Supplier_ID,
                                    torder.Supplier_Code = torder1.Supplier_Code,
                                    torder.Supplier_Name_Short = torder1.Supplier_Name_Short,
                                    torder.Supplier_Name = torder1.Supplier_Name,
                                    torder.Qty = torder1.Qty,
                                    torder.UM = torder1.UM,
                                    torder.PO_No = torder1.PO_No,
                                    torder.PO_Line = torder1.PO_Line,
                                    torder.PO_Release = torder1.PO_Release,
                                    torder.Command = torder1.Command,
                                    torder.File_Name = '$fileName',
                                    torder.Last_Updated_Date = curdate(),
                                    torder.Last_Updated_DateTime = now(),
                                    torder.Updated_By_ID = $cBy
                                WHERE 
                                    torder.Part_No = torder1.Part_No
                                        AND torder.PO_No = torder1.PO_No
                                        AND torder.PO_Line = torder1.PO_Line;";
                                //exit($sql);
                                sqlError($mysqli, __LINE__, $sql, 1, 0);

                                if ($Check_Refer_ID == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Not_Found',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__ . __LINE__);
                                    }
                                } else if ($Check_Part_Master == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Part_Master_Problem',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else if ($Check_Supplier_Master == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Supplier_Master_Problem',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else {
                                    $sql = "UPDATE tbl_order_from_email_body
                                    SET 
                                        Process_Status = 'Process_Complete',
                                        Last_Updated_DateTime = now(),
                                        Updated_By_ID = $cBy
                                    WHERE 
                                        BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                }

                                //exit('s');
                            } else if ($Command == 'DELETE') {
                                $sql = "UPDATE tbl_order torder,
                                (
                                    SELECT
                                    tob.Refer_ID,
                                    tob.Refer_ID_unique,
                                    tob.Command,
                                    tob.Part_No,
                                    tob.PO_No,
                                    tob.PO_Line
                                FROM
                                    tbl_order_from_email_header t1
                                        INNER JOIN
                                    tbl_order_from_email_body tob ON t1.Order_From_Email_Header_ID = tob.Order_From_Email_Header_ID
                                        INNER JOIN
                                    tbl_customer_master tcus ON t1.site_customer = tcus.Customer_Code
										INNER JOIN
									tbl_part_master tpm ON tob.Part_No = tpm.Part_No AND tcus.Customer_ID = tpm.Customer_ID
                                        INNER JOIN
									tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID AND tcus.Customer_ID = tsm.Customer_ID
                                    AND tsm.Supplier_Code = tob.Supplier_Code
                                WHERE 
                                    BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID'
                                        AND tob.Process_Status = 'Processing'
                                ) AS torder1
                                SET
                                    torder.Command = torder1.Command,
                                    torder.File_Name = '$fileName',
                                    torder.Last_Updated_Date = curdate(),
                                    torder.Last_Updated_DateTime = now(),
                                    torder.Updated_By_ID = $cBy
                                WHERE 
                                    torder.Part_No = torder1.Part_No
                                        AND torder.PO_No = torder1.PO_No
                                        AND torder.PO_Line = torder1.PO_Line;";
                                sqlError($mysqli, __LINE__, $sql, 1, 0);

                                if ($Check_Refer_ID == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                        SET 
                                            Process_Status = 'Not_Found',
                                            Last_Updated_DateTime = now(),
                                            Updated_By_ID = $cBy
                                        WHERE 
                                            BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__ . __LINE__);
                                    }
                                } else if ($Check_Part_Master == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                        SET 
                                            Process_Status = 'Part_Master_Problem',
                                            Last_Updated_DateTime = now(),
                                            Updated_By_ID = $cBy
                                        WHERE 
                                            BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else if ($Check_Supplier_Master == '') {
                                    $sql = "UPDATE tbl_order_from_email_body
                                        SET 
                                            Process_Status = 'Supplier_Master_Problem',
                                            Last_Updated_DateTime = now(),
                                            Updated_By_ID = $cBy
                                        WHERE 
                                            BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                } else {
                                    $sql = "UPDATE tbl_order_from_email_body
                                        SET 
                                            Process_Status = 'Process_Complete',
                                            Last_Updated_DateTime = now(),
                                            Updated_By_ID = $cBy
                                        WHERE 
                                            BIN_TO_UUID(Order_From_Email_Body_ID,TRUE) = '$Order_From_Email_Body_ID';";
                                    sqlError($mysqli, __LINE__, $sql, 1);
                                    if ($mysqli->affected_rows == 0) {
                                        throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                                    }
                                }
                            }
                        }

                        $sql = "UPDATE tbl_order_from_email_header
                        SET 
                            Process_Status = 'Process_Complete',
                            Last_Updated_DateTime = now(),
                            Updated_By_ID = $cBy
                        WHERE 
                            BIN_TO_UUID(Order_From_Email_Header_ID,TRUE) = '$Order_From_Email_Header_ID';";
                        sqlError($mysqli, __LINE__, $sql, 1);
                        if ($mysqli->affected_rows == 0) {
                            throw new Exception('ไม่สามารถบันทึกข้อมูลได้' . __LINE__);
                        }

                        echo '{"status":"server","mms":"Upload สำเร็จ ' . $total . '","data":[]}';
                        $mysqli->commit();
                    }
                    closeDB($mysqli);
                } else {
                    echo '{"status":"server","mms":"ไม่พบข้อมูลในไฟล์ ' . count($sqlArray) . '","data":[]}';
                    closeDB($mysqli);
                }
            } catch (Exception $e) {
                $mysqli->rollback();
                echo '{"status":"server","mms":"' . $e->getMessage() . '","sname":[]}';
                closeDB($mysqli);
            }
        } else echo json_encode(array('status' => 'server', 'mms' => 'ข้อมูลในไฟล์ไม่ถูกต้อง', 'sname' => array()));
    }
} else closeDBT($mysqli, 2, 'TYPE ERROR');

function Check_Delivery_Date()
{
    $dateTime = new DateTime("now", new DateTimeZone('Asia/Bangkok'));

    $Delivery_Time = '';
    $Time1 = '09:00';
    $Time2 = '12:00';
    $Time3 = '18:00';

    $New_Date = $dateTime->format('Y-m-d');
    $TimeNow = $dateTime->format('H:i');
    if ($TimeNow < $Time1) {
        $Delivery_Time = $New_Date . ' ' . '14:00:00';
    } else if ($TimeNow > $Time1 && $TimeNow < $Time2) {
        $Delivery_Time = $New_Date . ' ' . '15:00:00';
    } else if ($TimeNow > $Time1 && $TimeNow > $Time2 && $TimeNow < $Time3) {
        $Tomorrow = date('Y-m-d', strtotime($New_Date . "+1 days"));
        $Delivery_Time = $Tomorrow . ' ' . '09:00:00';
    }

    return ($Delivery_Time);
}

function clean($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

    return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}

function prepareNameInsert($data)
{
    $dataReturn = array();
    foreach ($data as $key => $value) {
        $dataReturn[] = $key;
    }
    return '(' . join(',', $dataReturn) . ')';
}
function prepareValueInsert($data)
{
    $dataReturn = array();
    foreach ($data as $valueAr) {
        $typeV;
        $keyV;
        $valueV;
        $dataAr = array();
        foreach ($valueAr as $key => $value) {
            $keyV = $key;
            $valueV = $value;
            $dataAr[] = $valueV;
        }
        $dataReturn[] = '(' . join(',', $dataAr) . ')';
    }
    return join(',', $dataReturn);
}
function stringConvert($data)
{
    if (strlen($data) > 0) {
        return "'$data'";
    } else {
        return 'null';
    }
}
function insert($mysqli, $tableName, $data, $error)
{
    $sql = "INSERT into $tableName" . prepareInsert($data);
    sqlError($mysqli, __LINE__, $sql, 1);
    if ($mysqli->affected_rows == 0) {
        throw new Exception($error);
    }
}
function convertDate($valueV)
{
    if (strlen($valueV) > 0) {
        if (is_a($valueV, 'DateTime')) {
            $v = "'" . $valueV->format('Y-m-d') . "'";
        } else {
            $valueV1 = explode('-', $valueV);
            $valueV2 = explode('/', $valueV);
            $valueV3 = explode('.', $valueV);
            $valueV4 = strlen($valueV);
            if (count($valueV1) == 3) {
                $v = switchDate($valueV1);
            } else if (count($valueV2) == 3) {
                $v = switchDate($valueV2);
            } else if (count($valueV3) == 3) {
                $v = switchDate($valueV3);
            } else if ($valueV4 == 8) {
                $v = "'" . substr($valueV, 0, 4) . '-' . substr($valueV, 4, 2) . '-' . substr($valueV, 6, 2) . "'";
            } else {
                $UNIX_DATE = ($valueV - 25569) * 86400;
                $v = "'" . gmdate("Y-m-d", $UNIX_DATE) . "'";
            }
        }
    } else {
        return 'null';
    }


    return $v;
}
function switchDate($d)
{
    if (strlen($d[0]) == 4) {
        return "'" . "$d[0]-$d[1]-$d[2]" . "'";
    } else {
        return "'" . "$d[2]-$d[1]-$d[0]" . "'";
    }
}

function getData($mysqli, $data, $line)
{
    $where = [];
    $where[] = "DATE(torderH.Creation_DateTime) between DATE('$data[Start_Date]') and DATE('$data[Stop_Date]')";

    $sqlWhere = join(' and ', $where);

    $sql = "SELECT 
    torderH.Received_Date,
    torderB.Refer_ID,
    torderB.Pickup_Date,
    torderB.Part_No,
    torderB.Part_Name,
    torderB.Supplier_Code,
    torderB.Supplier_Name,
    tsm.Supplier_Name_Short,
    torderB.Qty,
    torderB.UM,
    torderB.PO_No,
    torderB.PO_Line,
    torderB.PO_Release,
    torderB.Command,
    torderB.Process_Status,
    torderB.Creation_DateTime,
    torderB.Last_Updated_DateTime,
    tpm.Project,
    tpm.Product_Code,
    t1.Customer_Code
    FROM
    tbl_order_from_email_header torderH
        INNER JOIN
    tbl_order_from_email_body torderB ON torderH.Order_From_Email_Header_ID = torderB.Order_From_Email_Header_ID
        LEFT JOIN
    tbl_customer_master t1 ON torderH.site_customer = t1.Customer_Code
        LEFT JOIN
    tbl_part_master tpm ON torderB.Part_No = tpm.Part_No
        AND t1.Customer_ID = tpm.Customer_ID AND tpm.Active = 'Y'
        LEFT JOIN
    tbl_supplier_master tsm ON tpm.Supplier_ID = tsm.Supplier_ID
        AND t1.Customer_ID = tsm.Customer_ID AND torderB.Supplier_code = tsm.Supplier_code
    WHERE
        $sqlWhere
            AND ($data[sqlWhere])
    ORDER BY torderB.Process_Status ASC , torderB.Pickup_Date ASC, t1.Customer_Code, torderB.Last_Updated_DateTime DESC, torderB.Refer_ID DESC";
    // $sql = "SELECT
    //     torderH.Received_Date,
    //     torderB.Refer_ID,
    // 	torderB.Pickup_Date,
    // 	torderB.Part_No,
    // 	torderB.Part_Name,
    // 	torderB.Supplier_Code,
    // 	torderB.Supplier_Name,
    //     tsm.Supplier_Name_Short,
    // 	torderB.Qty,
    // 	torderB.UM,
    // 	torderB.PO_No,
    // 	torderB.PO_Line,
    // 	torderB.PO_Release,
    // 	torderB.Command,
    //     torderB.Process_Status,
    // 	torderB.Creation_DateTime,
    //     tpm.Project
    // FROM
    //     tbl_order_from_email_body torderB
    //         INNER JOIN
    //     tbl_order_from_email_header torderH ON torderB.Order_From_Email_Header_ID = torderH.Order_From_Email_Header_ID
    //         INNER JOIN
    //     tbl_supplier_master tsm ON torderB.Supplier_Code = tsm.Supplier_Code
    //         LEFT JOIN
    //     tbl_part_master tpm ON torderB.Part_No = tpm.Part_No
    //         AND tsm.Supplier_ID = tpm.Supplier_ID
    // WHERE 
    // 	$sqlWhere
    //         -- AND tpm.Customer_ID = uuid_to_bin('$data[Customer_ID]',true)
    // GROUP BY torderB.Refer_ID
    // ORDER BY torderH.Received_Date DESC, torderB.Order_From_Email_Body_ID ASC;";
    //exit($sql);
    return sqlError($mysqli, $line, $sql, 1);
}


$mysqli->close();
exit();
