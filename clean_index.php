<?php
\ = 'C:/xampp/htdocs/point of sale/index.php';
\ = file_get_contents(\);

// Remove the Quote button
\ = preg_replace('/<button[^>]*onclick="saveQuote\(\)"[^>]*>.*?<\/button>\s*/', '', \);

// Remove the Quote option from orderModeSelect
\ = preg_replace('/<option value="quote">Quote<\/option>\s*/', '', \);

// Remove the load_quote_js block
\ = preg_replace('/\ = "";.*?if \(\!empty\(\\)\) \{.*?\}\s*\}/s', '', \);
\ = str_replace('<?php echo ; ?>', '', \);

file_put_contents(\, \);
echo "Cleaned index.php";
?>
