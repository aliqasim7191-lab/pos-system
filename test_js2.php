<?php
$_SESSION['username'] = "admin";
$_SESSION['user_id'] = 1;
include "C:/xampp/htdocs/point of sale/includes/db.php";

$f = file_get_contents("C:/xampp/htdocs/point of sale/receipt.php");

$start = strpos($f, "<script>", strpos($f, "<script>") + 10);
$end = strpos($f, "</script>", $start);

if ($start !== false) {
    // We execute the PHP file and capture output instead.
    ob_start();
    $_GET['id'] = 152; // dummy id
    include "C:/xampp/htdocs/point of sale/receipt.php";
    $html = ob_get_clean();
    
    $s1 = strpos($html, "function sendWhatsApp()");
    $e1 = strpos($html, "</script>", $s1);
    echo substr($html, $s1, $e1 - $s1);
}
?>
