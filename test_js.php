<?php
$_SESSION['username'] = "admin";
$_SESSION['user_id'] = 1;
include "C:/xampp/htdocs/point of sale/includes/db.php";

$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$start = strpos($f, "<script>");
$end = strpos($f, "</script>", $start);

if ($start !== false) {
    // Find the second script tag where the sendWhatsApp is
    $start2 = strpos($f, "<script>", $end + 9);
    $end2 = strpos($f, "</script>", $start2);
    
    echo substr($f, $start2, $end2 - $start2 + 9);
}
?>
