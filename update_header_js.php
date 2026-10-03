<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

$bad = <<<PHP
<head>
    <meta charset="UTF-8">
PHP;

$good = <<<PHP
<head>
    <script>
        // Strict Tab Closure Security Check
        if (!sessionStorage.getItem('strict_session')) {
            window.location.href = 'logout.php';
        }
    </script>
    <meta charset="UTF-8">
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Updated header.php\n";
?>
