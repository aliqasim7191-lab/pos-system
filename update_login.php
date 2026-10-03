<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/login.php");

$bad = <<<PHP
        \$_SESSION['tenant_id'] = \$user['tenant_id'];
        header("Location: index.php");
        exit();
PHP;

$good = <<<PHP
        \$_SESSION['tenant_id'] = \$user['tenant_id'];
        echo "<script>sessionStorage.setItem('strict_session', 'active'); window.location.href = 'index.php';</script>";
        exit();
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/login.php", $f);
echo "Updated login.php redirect\n";
?>
