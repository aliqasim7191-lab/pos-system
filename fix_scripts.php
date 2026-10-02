<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$script_block = <<<HTML
<script>
    const PRODUCTS_DATA = <?php echo json_encode(\$products); ?>;
    const PRI_CURR = '<?php echo addslashes(\$pri_curr); ?>';
    const SEC_CURR = '<?php echo addslashes(\$sec_curr); ?>';
    const EXCH_RATE = <?php echo floatval(\$exch_rate); ?>;
    const globalTaxRate = <?php echo floatval(\$global_tax_rate); ?>;
</script>
<script src="assets/js/app.js"></script>

<!-- Hold Sale Modal -->
HTML;

$f = str_replace("<!-- Hold Sale Modal -->", $script_block, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Restored PRODUCTS_DATA and app.js script block!";
?>
