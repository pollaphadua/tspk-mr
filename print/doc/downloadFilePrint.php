<?php
include('../../php/connection.php');

$sql  = "SELECT
    file_id,
    file_name
FROM 
    tbl_label_file
LIMIT 1;";
$re1 = sqlError($mysqli, __LINE__, $sql, 1);
if ($re1->num_rows == 0) {
    // throw new Exception('ไม่พบข้อมูล' . __LINE__);
    echo ('no path found');
    exit();
} else {
    while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
        $file_id = $row['file_id'];
        $file_name = $row['file_name'];
    }

    $data = array("file_id" => $file_id, "file_name" => $file_name);
    $data_string = json_encode($data);
    echo $file_name;
}
