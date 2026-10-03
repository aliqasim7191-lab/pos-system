<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$f = preg_replace('/<a href="dashboard\.php" class="(.*?) btn" style=".*?">/', '<a href="dashboard.php" class="$1">', $f);
$f = preg_replace('/<a href="analytics\.php" class="(.*?) btn" style=".*?">/', '<a href="analytics.php" class="$1">', $f);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Fixed!";
?>
