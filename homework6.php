<?php
$filename = "counter_of_visits.txt";
$visitsQuantity = file_get_contents($filename);

if ($visitsQuantity == "") {
    echo "0";
} else {
    echo $visitsQuantity;
}
file_put_contents($filename, $visitsQuantity + 1);