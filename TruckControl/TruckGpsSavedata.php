<?php
//exit();
require_once('../php/connection.php');

getDataGeoTruck($mysqli);

function getDataGeoTruck($mysqli)
{
	$mysqli->autocommit(FALSE);
	try {

		$array_Truck_Number = [];
		$array_transaction_Line_ID = [];

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
				DATE_SUB(NOW(), INTERVAL 5 HOUR) AS time_start,
				IF(ISNULL(ttl.actual_in_time),
					ttl.planin_time,
					ttl.planout_time) AS Plan,
				DATE_ADD(NOW(), INTERVAL 5 HOUR) AS time_end,
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
					AND IF(ISNULL(ttl.actual_in_time),
					ttl.planin_time,
					ttl.planout_time) BETWEEN DATE_SUB(NOW(), INTERVAL 5 HOUR) AND DATE_ADD(NOW(), INTERVAL 5 HOUR)
			GROUP BY ttl.transaction_Line_ID
			ORDER BY ttm.Truck_ID, ttl.planin_time ASC),
		c AS (
			SELECT b.*,
				CASE
				WHEN b.Status_Pickup = 'PICKUP' THEN 'Y'
				WHEN b.Status_Pickup = 'DELIVERY' AND b.Entry = 'Entry' THEN 'Y'
				WHEN b.Status_Pickup = 'DELIVERY' AND b.Entry = 'No Entry' THEN 'N'
			END AS Status_Entry
			FROM b ),
            d AS (SELECT rank() OVER ( PARTITION BY c.Truck_Number, c.transaction_ID ORDER BY c.planin_time ASC, c.Status_Entry ASC, c.planout_time ASC ) AS rank_num, c.* FROM c)
        SELECT * FROM d  
        WHERE d.Status_Entry = 'Y' AND d.rank_num <= 1;";
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
			}

			foreach (array_combine($array_transaction_Line_ID, $array_Truck_Number) as $transaction_Line_ID => $Truck_Number) {
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
				} 

				$mysqli->commit();
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
