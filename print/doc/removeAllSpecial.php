<?php
include('../../php/connection.php');

$doc = $mysqli->real_escape_string(trim(strtoupper($_REQUEST['data'])));

/* -------- truck control form -------- */
unlink('truckfrom/TRUCKCONTROL_' . $doc . '.pdf'); // delete file
unlink('truckfrom/TRUCKCONTROL_B' . $doc . '.pdf'); // delete file
unlink('truckfrom/TRUCKCONTROL_CUS' . $doc . '.pdf'); // delete file
unlink('truckfrom/TRUCKCONTROL_CUS_B' . $doc . '.pdf'); // delete file

/* -------- pickup sheet -------- */
unlink('pickupsheet/merge_pus/PICKUPSHEET_' . $doc . '.pdf'); // delete file