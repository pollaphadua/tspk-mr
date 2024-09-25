<?php
if (!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
/*  if(!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName']) )
    {
        echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
        exit();
    }
 */

include('../common/common.php');
include('../php/connection.php');

if ($_REQUEST['type'] == 1) {

	$sql = "WITH a AS (
		SELECT 
			`Date` AS Start_Date,
			EXTRACT(YEAR_MONTH FROM `Date`) AS Start_Month
		FROM
			tbl_date
		WHERE
			DAY(Date) = '21'),
		b AS (
		SELECT
			CONCAT(DATE_SUB(a.Start_Date, INTERVAL 1 MONTH),' | ',tdate.`Date`) AS Period,
			CONCAT(DATE_FORMAT(DATE_SUB(a.Start_Date, INTERVAL 1 MONTH),'%d %b %Y'),' - ',DATE_FORMAT(tdate.`Date`,'%d %b %Y')) AS Period_Show,
			PERIOD_DIFF
			(
			EXTRACT(YEAR_MONTH FROM CURRENT_DATE), 
			EXTRACT(YEAR_MONTH FROM a.Start_Date)
			) 
			  AS months_diff
		FROM
			tbl_date tdate
				INNER JOIN 
			a ON EXTRACT(YEAR_MONTH FROM tdate.`Date`) = a.Start_Month
		WHERE
			DAY(Date) = '20')
		SELECT * FROM b WHERE months_diff = 0;";
	$re1 = sqlError($mysqli, __LINE__, $sql, 1);
	closeDBT($mysqli, 1, jsonRow($re1, true, 0));
}

$mysqli->close();
exit();
