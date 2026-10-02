<?php
$idx = file_get_contents("C:/xampp/htdocs/point of sale/index.php");
// Let's replace the whole stock line
$idx = preg_replace('/<\?php echo \$product\[\'stock\'\] < 20 \? \'.*?\' : \'.*?\'; \?>/', '<?php echo $product[\'stock\'] < 20 ? \'<i class="fa fa-exclamation-circle"></i>\' : \'<i class="fa fa-cube"></i>\'; ?>', $idx);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $idx);
?>
