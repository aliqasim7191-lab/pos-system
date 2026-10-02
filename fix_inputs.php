<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<HTML
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input type="text" id="barcodeInput" placeholder="Scan Barcode (F4)" style="flex: 1; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 8px; font-size: 1.1rem; outline: none;">
            <input type="text" id="searchInput" placeholder="Search product... (F2)" style="flex: 2; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1.1rem;">
        </div>
HTML;

$good = <<<HTML
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input type="text" id="barcodeScanner" placeholder="🔍 Scan Barcode (F4)" style="flex: 1; padding: 1rem; border: 2px solid var(--primary-color); border-radius: 8px; font-size: 1.1rem; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.1);" autofocus autocomplete="off">
            <input type="text" id="productSearch" placeholder="Search product... (F2)" style="flex: 1; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1.1rem;">
        </div>
HTML;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Scanner inputs fixed!";
?>
