<?php

$myArray = array(
  array("Volvo", 22, 1),
  array("Volvo", 22, 2),
  array("BMW", 15, 1),
  array("BMW", 15, 2),
  array("BMW", 15, 3),
  array("Saab", 30, 1),
  array("Land Rover", 17, 1),
  array("Land Rover", 17, 2)
);



$temp = $myArray;
for ($i = 0; $i <= sizeof($myArray); $i++) {
  for ($j = $i + 1; $j <= sizeof($myArray); $j++) {
    $ans1 = $temp[$i][0];
    $ans2 = $temp[$i][1];
    $ans3 = $temp[$i][2];
    // echo $j . '<br>';
    if ($j != sizeof($myArray)) {
      //echo $temp[$i][0] . ' = ' . $temp[$j][0] . '<br>';
      if ($temp[$i][0] == $temp[$j][0]) {
        echo $ans1 . ' : ' . $ans2 . ' : ' . $ans3 . '<br>';
        $ans1 = $temp[$j][0];
        $ans2 = $temp[$j][1];
        $ans3 = $temp[$j][2];
      } else {
        echo $ans1 . ' : ' . $ans2 . ' : ' . $ans3 . '<br>';
        echo '______________<br>';
      }
    } else if ($j == sizeof($myArray)) {
      //echo $temp[$j - 1][0] . ' = ' . $temp[$j - 1][0] . '<br>';
      if ($temp[$i - 1][0] == $temp[$j - 1][0]) {
        echo $ans1 . ' : ' . $ans2 . ' : ' . $ans3 . '<br>';
      } else {
        echo $ans1 . ' : ' . $ans2 . ' : ' . $ans3 . '<br>';
      }
    }
    $i++;
  }
}
