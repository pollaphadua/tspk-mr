<?php

require_once('../php/connection.php');


getDataGeoTruck($mysqli);

function getDataGeoTruck($mysqli)
{
	$mysqli->autocommit(FALSE);
	try {

		$array_Truck_Number = [];
		$array_transaction_Line_ID = [];
		$array_Customer_ID = [];

		$sql = "WITH a AS (
			SELECT 
				tts.transaction_ID,
				ttl.Status_Pickup
			FROM
				tbl_truck_master ttm
					INNER JOIN
				tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			WHERE
				ttm.Status = 'Active'
					AND ttm.Truck_Number != 'N/A'
					AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT')
					AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT')
					AND Status_Pickup = 'PICKUP'
					AND actual_in_time IS NULL
                    AND ttl.Pick != 'N'
			ORDER BY ttm.Truck_ID, ttl.planin_time ASC ),
		b AS ( 
			SELECT 
				BIN_TO_UUID(tts.transaction_ID,TRUE) AS transaction_ID,
				BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) AS transaction_Line_ID,
				BIN_TO_UUID(tts.Customer_ID,TRUE) AS Customer_ID,
				ttm.Truck_Number,
				ttm.Truck_Type,
				ttm.gps_angle,
				ttm.gps_speed,
				ST_ASGEOJSON(ttm.geo) AS truck_geo,
				ST_ASGEOJSON(ST_CENTROID(ttm.geo)) AS truck_geoCenter,
				ttl.pus_No,
				ttl.planin_time,
				ttl.actual_in_time,
				ttl.planout_time,
				ttl.actual_out_time,
				DATE_SUB(ttl.planin_time, INTERVAL 3 HOUR) AS time_start,
				IF(ISNULL(ttl.actual_in_time),
					ttl.planin_time,
					ttl.planout_time) AS Plan,
				NOW() AS now,
				DATE_ADD(ttl.planout_time, INTERVAL 3 HOUR) AS time_end,
				ttl.Status_Pickup,
				if(isnull(a.Status_Pickup),'Entry','No Entry') As Entry
			FROM
				tbl_truck_master ttm
					INNER JOIN
				tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
					LEFT JOIN
				a ON tts.transaction_ID = a.transaction_ID
			WHERE
				ttm.Status = 'Active'
					AND ttm.Truck_Number != 'N/A'
					AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT')
					AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT')
                    AND ttl.Pick != 'N'
			GROUP BY ttl.transaction_Line_ID
			ORDER BY ttm.Truck_ID, ttl.planin_time ASC),
		c AS (
			SELECT b.*,
				CASE
				WHEN b.Status_Pickup = 'PICKUP' THEN 'Y'
				WHEN b.Status_Pickup = 'DELIVERY' AND b.Entry = 'Entry' THEN 'Y'
				WHEN b.Status_Pickup = 'DELIVERY' AND b.Entry = 'No Entry' THEN 'N'
			END AS Status_Entry
			FROM b  
			WHERE b.now between b.time_start and b.time_end
			),
            d AS (SELECT rank() OVER ( PARTITION BY c.Truck_Number ORDER BY c.planin_time ASC, c.Status_Entry ASC, c.planout_time ASC ) AS rank_num, c.* FROM c)
        SELECT * FROM d  
        WHERE d.Status_Entry = 'Y' 
		AND d.rank_num <= 1
		;";
		// , c.transaction_ID
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		if ($re1->num_rows == 0) {
			echo ('No plan <br>');
		} else {
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Truck_Number = $row['Truck_Number'];
				$array_Truck_Number[] = $Truck_Number;
				$transaction_Line_ID = $row['transaction_Line_ID'];
				$array_transaction_Line_ID[] = $transaction_Line_ID;
				$Customer_ID = $row['Customer_ID'];
				$array_Customer_ID[] = $Customer_ID;
			}

			$i = 0;

			foreach (array_combine($array_transaction_Line_ID, $array_Truck_Number) as $transaction_Line_ID => $Truck_Number) {
				$Customer_ID = $array_Customer_ID[$i];
				if ($Truck_Number == '74-7968') {

					$Truck_Number = '74-7967';
					$sql = "SELECT 
						BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID,
						Truck_Number
					FROM
						tbl_truck_master
					WHERE 
						Truck_Number = '$Truck_Number'
							AND Status = 'ACTIVE';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$Truck_ID = $row['Truck_ID'];
					}
				} else {
					$sql = "SELECT 
						BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID,
						Truck_Number
					FROM
						tbl_truck_master
					WHERE 
						Truck_Number = '$Truck_Number'
							AND Status = 'ACTIVE';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$Truck_ID = $row['Truck_ID'];
					}
				}

				$sql = "SELECT 
					truckLicense,
					gps_speed,
					gps_angle,
					ST_AsText(geo) AS truck_geo,
					gps_updateDatetime
				FROM
					aatmr_v2_test.tbl_truck_log
				WHERE 
					truckLicense = '$Truck_Number'
						AND gps_updateDatetime BETWEEN DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
				ORDER BY gps_updateDatetime DESC LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$gps_speed = $row['gps_speed'];
						$gps_angle = $row['gps_angle'];
						$truck_geo = $row['truck_geo'];
						$gps_updateDatetime = $row['gps_updateDatetime'];
						// $engine = $row['engine'];
						// $engine_updateDatetime = $row['engine_updateDatetime'];
						// $fuel = $row['fuel'];
					}


					$sql = "UPDATE tbl_truck_master 
					SET 
						geo = ST_GeomFromText('$truck_geo'),
						gps_speed = '$gps_speed',
						gps_angle = '$gps_angle',
						gps_updateDatetime = '$gps_updateDatetime'
					WHERE
						Truck_Number = '$Truck_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					echo ('Truck No : ' . $Truck_Number . ' Updated : ' . $gps_updateDatetime . '<br>');
					/* if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					} */
				}

				$mysqli->commit();
				$i++;
			}
		}

		echo ('<br>');

		$array_Truck_Number = [];
		$array_transaction_Line_ID = [];
		$array_Customer_ID = [];

		$sql = "WITH a AS (
			SELECT 
				tts.transaction_ID,
				ttl.Status_Pickup
			FROM
				tbl_truck_master ttm
					INNER JOIN
				tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
			WHERE
				ttm.Status = 'Active'
					AND ttm.Truck_Number != 'N/A'
					AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT')
					AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT')
					AND Status_Pickup = 'PICKUP'
					AND actual_in_time IS NULL
                    AND ttl.Pick != 'N'
			ORDER BY ttm.Truck_ID, ttl.planin_time ASC )
		,b AS ( 
			SELECT 
				BIN_TO_UUID(tts.transaction_ID,TRUE) AS transaction_ID,
				BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) AS transaction_Line_ID,
				BIN_TO_UUID(tts.Customer_ID,TRUE) AS Customer_ID,
				ttm.Truck_Number,
				ttm.Truck_Type,
				ttm.gps_angle,
				ttm.gps_speed,
				ST_ASGEOJSON(ttm.geo) AS truck_geo,
				ST_ASGEOJSON(ST_CENTROID(ttm.geo)) AS truck_geoCenter,
				ttl.pus_No,
				ttl.planin_time,
				ttl.actual_in_time,
				ttl.planout_time,
				ttl.actual_out_time,
				DATE_SUB(ttl.planin_time, INTERVAL 3 HOUR) AS time_start,
				IF(ISNULL(ttl.actual_in_time),
					ttl.planin_time,
					ttl.planout_time) AS Plan,
				NOW() AS now,
				DATE_ADD(ttl.planout_time, INTERVAL 3 HOUR) AS time_end,
                TIMESTAMPDIFF(SECOND, ttl.planin_time,ttl.actual_in_time) AS difference, if(ttl.actual_in_time IS NULL,TIMESTAMPDIFF(SECOND, ttl.planin_time,now()),null) AS difference2,
				ttl.Status_Pickup,
				if(isnull(a.Status_Pickup),'Entry','No Entry') As Entry
			FROM
				tbl_truck_master ttm
					INNER JOIN
				tbl_transaction tts ON ttm.Truck_ID = tts.Truck_ID
					INNER JOIN
				tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
					LEFT JOIN
				a ON tts.transaction_ID = a.transaction_ID
			WHERE
				ttm.Status = 'Active'
					AND ttm.Truck_Number != 'N/A'
					AND (tts.tran_status = 'PLANNING' OR tts.tran_status = 'IN-TRANSIT')
					AND (ttl.status = 'PLANNING' OR ttl.status = 'IN-TRANSIT')
                    AND ttl.Pick != 'N'
			GROUP BY ttl.transaction_Line_ID
			ORDER BY ttm.Truck_ID, ttl.planin_time ASC)
		,c AS (
			SELECT b.*,
				CASE
				WHEN b.Status_Pickup = 'PICKUP' THEN 'Y'
				WHEN b.Status_Pickup = 'DELIVERY' AND b.Entry = 'Entry' THEN 'Y'
				WHEN b.Status_Pickup = 'DELIVERY' AND b.Entry = 'No Entry' THEN 'N'
			END AS Status_Entry,
            CASE
			WHEN b.difference IS NULL AND difference2 > 3600 THEN 0
			ELSE 1
			END AS Time_Status
			FROM b  
			WHERE b.now between b.time_start and b.time_end)
            ,d AS (SELECT rank() OVER ( PARTITION BY c.Truck_Number ORDER BY  c.Time_Status DESC, c.planin_time ASC, c.Status_Entry ASC, c.planout_time ASC ) AS rank_num, c.* FROM c)
			SELECT * FROM d  
			WHERE 
			d.rank_num <= 1
		;";
		$re1 = sqlError($mysqli, __LINE__, $sql, 1);
		//,c.transaction_ID
		if ($re1->num_rows == 0) {
			echo ('No plan <br>');
		} else {
			while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
				$Truck_Number = $row['Truck_Number'];
				$array_Truck_Number[] = $Truck_Number;
				$transaction_Line_ID = $row['transaction_Line_ID'];
				$array_transaction_Line_ID[] = $transaction_Line_ID;
				$Customer_ID = $row['Customer_ID'];
				$array_Customer_ID[] = $Customer_ID;
			}

			$i = 0;

			foreach (array_combine($array_transaction_Line_ID, $array_Truck_Number) as $transaction_Line_ID => $Truck_Number) {
				$Customer_ID = $array_Customer_ID[$i];

				//echo ($Truck_Number . ' / ' . $transaction_Line_ID . '<br>');

				if ($Truck_Number == '74-7968') {

					$Truck_Number = '74-7967';
					$sql = "SELECT 
						BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID,
						Truck_Number
					FROM
						tbl_truck_master
					WHERE 
						Truck_Number = '$Truck_Number'
							AND Status = 'ACTIVE';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$Truck_ID = $row['Truck_ID'];
					}
				} else {
					$sql = "SELECT 
						BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID,
						Truck_Number
					FROM
						tbl_truck_master
					WHERE 
						Truck_Number = '$Truck_Number'
							AND Status = 'ACTIVE';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$Truck_ID = $row['Truck_ID'];
					}
				}


				$sql = "SELECT 
					truckLicense,
					gps_speed,
					gps_angle,
					ST_AsText(geo) AS truck_geo,
					gps_updateDatetime
				FROM
					aatmr_v2_test.tbl_truck_log
				WHERE 
					truckLicense = '$Truck_Number'
						AND gps_updateDatetime BETWEEN DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
				ORDER BY gps_updateDatetime DESC LIMIT 1;";
				$re1 = sqlError($mysqli, __LINE__, $sql, 1);
				if ($re1->num_rows > 0) {
					while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$gps_speed = $row['gps_speed'];
						$gps_angle = $row['gps_angle'];
						$truck_geo = $row['truck_geo'];
						$gps_updateDatetime = $row['gps_updateDatetime'];
						// $engine = $row['engine'];
						// $engine_updateDatetime = $row['engine_updateDatetime'];
						// $fuel = $row['fuel'];
					}


					$sql = "UPDATE tbl_truck_master 
					SET 
						geo = ST_GeomFromText('$truck_geo'),
						gps_speed = '$gps_speed',
						gps_angle = '$gps_angle',
						gps_updateDatetime = '$gps_updateDatetime'
					WHERE
						Truck_Number = '$Truck_Number';";
					sqlError($mysqli, __LINE__, $sql, 1);
					/* if ($mysqli->affected_rows == 0) {
						throw new Exception('ไม่สามารถบันทึกข้อมูลได้ ' . __LINE__);
					} */


					$sql = "UPDATE tbl_transaction_line 
					SET 
						gps_connection = 'CONNECT',
						gps_datetime_connect = '$gps_updateDatetime'
					WHERE
						BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
					sqlError($mysqli, __LINE__, $sql, 1);




					$sql = "SELECT 
						BIN_TO_UUID(Truck_ID,TRUE) AS Truck_ID,
						Truck_Number,
						ST_AsText(geo) AS truck_geo,
						gps_updateDatetime AS gps
					FROM
						tbl_truck_master
					WHERE 
						Truck_Number = '$Truck_Number'
							AND Status = 'ACTIVE';";
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows == 0) {
						throw new Exception('ไม่พบข้อมูล ' . __LINE__);
					}
					/* while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
						$gps = $row['gps'];
					} */


					$sql = "SELECT 
						BIN_TO_UUID(tsm.Supplier_ID,TRUE) AS Supplier_ID,
						tsm.Supplier_Code,
						tsm.Supplier_Name_Short,
						ST_AsText(tsm.geo) AS supplier_geo_Text,
						ST_Contains(tsm.geo,  ST_GEOMFROMTEXT('$truck_geo')) AS supplier_geo_Contains
					FROM
						tbl_supplier_master tsm
					WHERE
						ST_Contains(tsm.geo,  ST_GEOMFROMTEXT('$truck_geo')) = 1
							AND Customer_ID = uuid_to_bin('$Customer_ID',true);";
					//exit($sql);
					$re1 = sqlError($mysqli, __LINE__, $sql, 1);
					if ($re1->num_rows > 0) {
						while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
							$Supplier_ID = $row['Supplier_ID'];
						}

						//บันทึก actual_in_time เปลี่ยนสถานะเป้น IN-TRANSIT
						$sql = "SELECT 
							BIN_TO_UUID(ttl.Supplier_ID,TRUE) AS Supplier_ID,
							BIN_TO_UUID(ttl.transaction_ID, TRUE) AS transaction_ID,
							ttl.pus_No,
							ttl.planin_time,
							ttl.actual_in_time,
							ttl.actual_out_time
						FROM
							tbl_transaction_line ttl
						WHERE
							BIN_TO_UUID(ttl.transaction_Line_ID,TRUE) = '$transaction_Line_ID'
								AND BIN_TO_UUID(ttl.Supplier_ID,TRUE) = '$Supplier_ID'
								AND ttl.actual_in_time IS NULL
								AND ttl.actual_out_time IS NULL
								AND ttl.Pick != 'N';";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows > 0) {
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$transaction_ID = $row['transaction_ID'];
							}

							$sql = "UPDATE tbl_transaction 
								SET 
									tran_status = 'IN-TRANSIT',
									Last_Updated_Date = curdate(),
									Last_Updated_DateTime = now()
								WHERE 
									BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
							//exit($sql);
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
							}

							$sql = "UPDATE tbl_transaction_line 
							SET 
								actual_in_time = '$gps_updateDatetime',
								status = 'IN-TRANSIT',
								gps_updateDatetime = now(),
								gps_Updated_By_ID = 1
							WHERE
								BIN_TO_UUID(transaction_Line_ID, TRUE) = '$transaction_Line_ID';";
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
							}

							$sql = "INSERT INTO tbl_truck_check (
								Truck_ID,
								Supplier_ID,
								transaction_ID,
								transaction_Line_ID,
								Start_Time,
								Creation_DateTime, 
								Created_By_ID,
								Last_Updated_DateTime, 
								Updated_By_ID)
							VALUES(
								UUID_TO_BIN('$Truck_ID',TRUE),
								UUID_TO_BIN('$Supplier_ID',TRUE),
								UUID_TO_BIN('$transaction_ID',TRUE),
								UUID_TO_BIN('$transaction_Line_ID',TRUE),
								'$gps_updateDatetime',
								NOW(),
								1,
								NOW(),
								1
							)
							ON DUPLICATE KEY UPDATE
							Start_Time = now(),
							Last_Updated_DateTime = NOW(),
							Updated_By_ID = 1;";
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
							}
							echo ('Truck No : ' . $Truck_Number . ' Last Updated : ' . $gps_updateDatetime . ' IN-TIME <br>');
						} else {
							echo ('Truck No : ' . $Truck_Number . ' No Update IN-TIME <br>');
						}
					} else {

						//บันทึก actual_out_time เปลี่ยนสถานะเป็น COMPLETE
						$sql = "SELECT 
								BIN_TO_UUID(Truck_Check_ID,TRUE) AS Truck_Check_ID,
								BIN_TO_UUID(transaction_Line_ID,TRUE) AS transaction_Line_ID,
								BIN_TO_UUID(transaction_ID, TRUE) AS transaction_ID
							FROM 
								tbl_truck_check
							WHERE 
								BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID'
									AND Status = 'IN-TRANSIT'
									AND End_Time IS NULL;";
						$re1 = sqlError($mysqli, __LINE__, $sql, 1);
						if ($re1->num_rows > 0) {
							while ($row = $re1->fetch_array(MYSQLI_ASSOC)) {
								$Truck_Check_ID = $row['Truck_Check_ID'];
								$transaction_ID = $row['transaction_ID'];
							}

							$sql = "UPDATE tbl_transaction_line 
							SET 
								actual_out_time = '$gps_updateDatetime',
								status = 'COMPLETE',
								gps_updateDatetime = now(),
								gps_Updated_By_ID = 1
							WHERE
								BIN_TO_UUID(transaction_Line_ID, TRUE) = '$transaction_Line_ID';";
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
							}

							$sql = "UPDATE tbl_truck_check 
							SET 
								End_Time = '$gps_updateDatetime',
								Status = 'COMPLETE',
								Last_Updated_DateTime = NOW(),
								Updated_By_ID = 1
							WHERE
								BIN_TO_UUID(Truck_Check_ID, TRUE) = '$Truck_Check_ID';";
							sqlError($mysqli, __LINE__, $sql, 1);
							if ($mysqli->affected_rows == 0) {
								throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
							}

							$sql = "SELECT 
								BIN_TO_UUID(transaction_Line_ID, TRUE) AS transaction_Line_ID
							FROM
								tbl_transaction tts
									INNER JOIN
								tbl_transaction_line ttl ON tts.transaction_ID = ttl.transaction_ID
							WHERE
								BIN_TO_UUID(tts.transaction_ID, TRUE) = '$transaction_ID'
									AND (ttl.status = 'IN-TRANSIT' OR ttl.status = 'PLANNING')
									AND tts.tran_status != 'CANCEL'
									AND tts.tran_status != 'PENDING'
									AND ttl.Pick != 'N';";
							$re1 = sqlError($mysqli, __LINE__, $sql, 1);
							if ($re1->num_rows == 0) {
								$sql = "UPDATE tbl_transaction 
								SET 
									tran_status = 'COMPLETE',
									Last_Updated_Date = curdate(),
									Last_Updated_DateTime = now()
								WHERE 
									BIN_TO_UUID(transaction_ID,TRUE) = '$transaction_ID';";
								//exit($sql);
								sqlError($mysqli, __LINE__, $sql, 1);
								if ($mysqli->affected_rows == 0) {
									throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
								}
							}

							echo ('Truck No : ' . $Truck_Number . ' Last Updated : ' . $gps_updateDatetime . ' OUT-TIME <br>');
						} else {
							echo ('Truck No : ' . $Truck_Number . ' No Update OUT-TIME<br>');
						}
					}
				} else {
					$sql = "UPDATE tbl_transaction_line 
					SET 
						gps_connection = 'DISCONNECT',
						gps_datetime_connect = now()
					WHERE
						BIN_TO_UUID(transaction_Line_ID,TRUE) = '$transaction_Line_ID';";
					sqlError($mysqli, __LINE__, $sql, 1);
					echo ('ไม่พบข้อมูล Truck No : ' . $Truck_Number . '<br>');
				}

				$mysqli->commit();

				$i++;
			}
		}

		//$mysqli->commit();
		closeDBT($mysqli, 1, jsonRow($re1, true, 0));
	} catch (Exception $e) {
		$mysqli->rollback();
		closeDBT($mysqli, 2, $e->getMessage());
	}
}

$mysqli->close();
exit();
