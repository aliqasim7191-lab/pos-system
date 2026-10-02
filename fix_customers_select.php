<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/customers.php");

$bad_query = <<<PHP
    SELECT c.*, 
           COALESCE(SUM(s.total_amount), 0) as total_spent,
           COUNT(s.id) as total_orders,
           MAX(s.created_at) as last_purchase
    FROM customers c 
    LEFT JOIN sales s ON c.name = s.customer_name 
    GROUP BY c.id 
    ORDER BY c.name ASC
PHP;

$good_query = <<<PHP
    SELECT c.*, 
           COALESCE(SUM(s.total_amount), 0) as total_spent,
           COUNT(s.id) as total_orders,
           MAX(s.created_at) as last_purchase
    FROM customers c 
    LEFT JOIN sales s ON c.name = s.customer_name AND s.tenant_id = {\$_SESSION['tenant_id']}
    WHERE c.tenant_id = {\$_SESSION['tenant_id']}
    GROUP BY c.id 
    ORDER BY c.name ASC
PHP;

$f = str_replace($bad_query, $good_query, $f);
file_put_contents("C:/xampp/htdocs/point of sale/customers.php", $f);
echo "SELECT query fixed!\n";
?>
