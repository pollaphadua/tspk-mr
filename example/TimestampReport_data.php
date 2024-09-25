<?php
if(!ob_start("ob_gzhandler")) ob_start();
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
include('../start.php');
session_start();
if(!isset($_SESSION['xxxID']) || !isset($_SESSION['xxxRole']) || !isset($_SESSION['xxxID']) || !isset($_SESSION['xxxFName'])  || !isset($_SESSION['xxxRole']->{'TimestampReport'}) )
{
	echo "{ch:10,data:'เวลาการเชื่อมต่อหมด<br>คุณจำเป็นต้อง login ใหม่'}";
	exit();
}
else if($_SESSION['xxxRole']->{'TimestampReport'}[0] == 0)
{
	echo "{ch:9,data:'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้'}";
	exit();
}

if(!isset($_REQUEST['type'])) {echo json_encode(array('ch'=>2,'data'=>'ข้อมูลไม่ถูกต้อง'));exit();}
$cBy = $_SESSION['xxxID'];
$fName = $_SESSION['xxxFName'];
$type  = intval($_REQUEST['type']);


include('../php/connection.php');
if($type<=10)//data
{
	if($type == 1)
	{
		$dataParams = array(
			'obj',
			'obj=>start:s:0:4',
			'obj=>stop:s:0:4',
			'obj=>gps:s:0:2',
		);
		
		$chkPOST = checkParamsAndDelare($_POST,$dataParams,$mysqli);
		if(count($chkPOST) > 0) closeDBT($mysqli,2,join('<br>',$chkPOST));

		if($gps === 'ALL')
		{
			$gps = "";			
		}
		else
		{
			$gps = "and t3.GPS_Status='$gps'";		
		}
		
		$sql = 
		"WITH getDataTotal as 
		(
			select 'Total' `type`,t2.operration_date,t1.Load_ID,t3.Update_ActualIN_Datetime_By,t3.Update_ActualOut_Datetime_By,t2.CurrentLoadOperationalStatusEnumVal,t2.Status,
			sum(if(Update_ActualIN_Datetime_By>1,1,0)) as UserKeyIn,sum(if(Update_ActualIN_Datetime_By=1,1,0)) as SystemKeyIn,
			sum(if(Update_ActualOut_Datetime_By>1,1,0)) as UserKeyOut,sum(if(Update_ActualOut_Datetime_By=1,1,0)) as SystemKeyOut,t5.projectName
			from tbl_transaction t1
			inner join tbl_204header_api t2 using(Load_ID)
			inner join tbl_204body_api t3 using(Load_ID)
			inner join tbl_route_master_header t4 on t2.Route=t4.routeName
            inner join tbl_project_master t5 on t4.projectID=t5.ID
			where t2.operration_date between '$start' and '$stop' and t2.CurrentLoadOperationalStatusEnumVal <>'S_TENDER_REJECTED' and t5.projectName in('FTM MR','AAT MR')
			$gps
			group by t2.operration_date
		),getDataPick as 
		(
			select 'Pick' `type`,t2.operration_date,t1.Load_ID,t3.Update_ActualIN_Datetime_By,t3.Update_ActualOut_Datetime_By,t2.CurrentLoadOperationalStatusEnumVal,t2.Status,
			sum(if(Update_ActualIN_Datetime_By>1,1,0)) as UserKeyIn,sum(if(Update_ActualIN_Datetime_By=1,1,0)) as SystemKeyIn,
			sum(if(Update_ActualOut_Datetime_By>1,1,0)) as UserKeyOut,sum(if(Update_ActualOut_Datetime_By=1,1,0)) as SystemKeyOut,t5.projectName
			from tbl_transaction t1
			inner join tbl_204header_api t2 using(Load_ID)
			inner join tbl_204body_api t3 using(Load_ID)
			inner join tbl_route_master_header t4 on t2.Route=t4.routeName
            inner join tbl_project_master t5 on t4.projectID=t5.ID
			where t2.operration_date between '$start' and '$stop' and t2.CurrentLoadOperationalStatusEnumVal <>'S_TENDER_REJECTED'  and t3.StopTypeEnumVal='ST_PICKONLY' and t5.projectName in('FTM MR','AAT MR')
			$gps
			group by t2.operration_date
		),getDataDrop as 
		(
			select 'Drop' `type`,t2.operration_date,t1.Load_ID,t3.Update_ActualIN_Datetime_By,t3.Update_ActualOut_Datetime_By,t2.CurrentLoadOperationalStatusEnumVal,t2.Status,
			sum(if(Update_ActualIN_Datetime_By>1,1,0)) as UserKeyIn,sum(if(Update_ActualIN_Datetime_By=1,1,0)) as SystemKeyIn,
			sum(if(Update_ActualOut_Datetime_By>1,1,0)) as UserKeyOut,sum(if(Update_ActualOut_Datetime_By=1,1,0)) as SystemKeyOut,t5.projectName
			from tbl_transaction t1
			inner join tbl_204header_api t2 using(Load_ID)
			inner join tbl_204body_api t3 using(Load_ID)
			inner join tbl_route_master_header t4 on t2.Route=t4.routeName
            inner join tbl_project_master t5 on t4.projectID=t5.ID
			where t2.operration_date between '$start' and '$stop' and t2.CurrentLoadOperationalStatusEnumVal <>'S_TENDER_REJECTED'  and t3.StopTypeEnumVal='ST_DROPONLY' and t5.projectName in('FTM MR','AAT MR')
			$gps
			group by t2.operration_date
		), calData as 
		(
			select `type`,operration_date,UserKeyIn+UserKeyOut as UserKey,SystemKeyIn+SystemKeyOut as SystemKey,
			UserKeyIn+UserKeyOut+SystemKeyIn+SystemKeyOut totalStop
			from getDataTotal
			union all 
			select `type`,operration_date,UserKeyIn+UserKeyOut as UserKey,SystemKeyIn+SystemKeyOut as SystemKey,
			UserKeyIn+UserKeyOut+SystemKeyIn+SystemKeyOut totalStop
			from getDataPick
			union all 
			select `type`,operration_date,UserKeyIn+UserKeyOut as UserKey,SystemKeyIn+SystemKeyOut as SystemKey,
			UserKeyIn+UserKeyOut+SystemKeyIn+SystemKeyOut totalStop
			from getDataDrop
		), calPercentage as 
		(
			select *,round(UserKey*100/totalStop) UserKeyPercentage,round(SystemKey*100/totalStop) SystemKeyPercentage
			from calData
		), dataFormat as 
		(
			select  * from
			(
				select HolidayDate `Date`,DATE_FORMAT(HolidayDate, '%d') as `Day` from tbl_holiday where HolidayDate between '$start' and '$stop'
			) s1
			cross join 
			(
				select 'Total' `type`,0 UserKey,0 SystemKey, 0 totalStop,0 UserKeyPercentage,0 SystemKeyPercentage
				union all
				select 'Pick' `type`,0 UserKey,0 SystemKey, 0 totalStop,0 UserKeyPercentage,0 SystemKeyPercentage
				union all
				select 'Drop'`type`,0 UserKey,0 SystemKey, 0 totalStop,0 UserKeyPercentage,0 SystemKeyPercentage
			) s2
		), compareData1 as
		( 
				select t1.Date,t1.`Day`,t1.`type`,
				ifnull(t2.UserKey,t1.UserKey) UserKey,
				ifnull(t2.SystemKey,t1.SystemKey) SystemKey,
				ifnull(t2.totalStop,t1.totalStop) totalStop,
				ifnull(t2.UserKeyPercentage,t1.UserKeyPercentage) UserKeyPercentage,
				ifnull(t2.SystemKeyPercentage,t1.SystemKeyPercentage) SystemKeyPercentage
				from dataFormat t1 
				left join calPercentage t2 on t1.Date=t2.operration_date and  t1.`type`=t2.`type`
		), compareData2 as
		( 
				select *,
				SUM(UserKey) OVER (PARTITION BY `type`) TypeUserKey,
				SUM(SystemKey) OVER (PARTITION BY `type`) TypeSystemKey,
				SUM(totalStop) OVER (PARTITION BY `type`) TypeTotalStop
				from compareData1
		)
		select *,
		round(TypeUserKey*100/TypeTotalStop) TypeUserKeyPercentage,
		round(TypeSystemKey*100/TypeTotalStop) TypeSystemKeyPercentage
		from compareData2 order by `type`,`Date`
		";
		// exit($sql);
		$re1 = sqlError($mysqli,__LINE__,$sql,1);
		closeDBT($mysqli,1,jsonRow($re1,true,0));
	}
	else closeDBT($mysqli,2,'TYPE ERROR');
}
else if($type>10 && $type<=20)//insert
{
	if($_SESSION['xxxRole']->{'TimestampReport'}[1] == 0) closeDBT($mysqli,9,'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if($type == 11)
	{

	}
	else if($type == 12)
	{

	}
	else closeDBT($mysqli,2,'TYPE ERROR');
}
else if($type>20 && $type<=30)//update
{
	if($_SESSION['xxxRole']->{'TimestampReport'}[2] == 0) closeDBT($mysqli,9,'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if($type == 21)
	{
		
	}
	else closeDBT($mysqli,2,'TYPE ERROR');
}
else if($type>30 && $type<=40)//delete
{
	if($_SESSION['xxxRole']->{'TimestampReport'}[3] == 0) closeDBT($mysqli,9,'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if($type == 31)
	{

	}
	else closeDBT($mysqli,2,'TYPE ERROR');
}
else if($type>40 && $type<=50)//save
{
	if($_SESSION['xxxRole']->{'TimestampReport'}[1] == 0) closeDBT($mysqli,9,'คุณไม่ได้รับอุญาติให้ทำกิจกรรมนี้');
	if($type == 41)
	{
		
	}
	else closeDBT($mysqli,2,'TYPE ERROR');
}
else closeDBT($mysqli,2,'TYPE ERROR');

$mysqli->close();
exit();

?>
