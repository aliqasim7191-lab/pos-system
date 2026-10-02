<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/customers.php");

$bad = <<<PHP
    } elseif (\$_POST['action'] == 'delete') {
        \$c_id = (int)\$_POST['id'];
        
        // Delete their khata (ledger) history so it doesn't leave orphan records
        \$conn->query("DELETE FROM customer_ledger WHERE customer_id = \$c_id AND tenant_id = {\$_SESSION['tenant_id']}");
        
        // Remove customer ID from sales so sales are kept but anonymized
        \$conn->query("UPDATE sales SET customer_id = NULL WHERE customer_id = \$c_id AND tenant_id = {\$_SESSION['tenant_id']}");
        
        // Finally delete the customer
        \$stmt = \$conn->prepare("DELETE FROM customers WHERE id=? AND tenant_id=?");
        \$stmt->bind_param("ii", \$c_id, \$_SESSION['tenant_id']);
        \$stmt->execute();
        \$stmt->close();
    }
    echo "<script>window.location.href='customers.php';</script>";
PHP;

$good = <<<PHP
    } elseif (\$_POST['action'] == 'delete') {
        \$c_id = (int)\$_POST['id'];
        
        try {
            \$conn->query("DELETE FROM customer_ledger WHERE customer_id = \$c_id AND tenant_id = {\$_SESSION['tenant_id']}");
            \$conn->query("UPDATE sales SET customer_id = NULL WHERE customer_id = \$c_id AND tenant_id = {\$_SESSION['tenant_id']}");
            
            \$stmt = \$conn->prepare("DELETE FROM customers WHERE id=? AND tenant_id=?");
            \$stmt->bind_param("ii", \$c_id, \$_SESSION['tenant_id']);
            \$stmt->execute();
            \$stmt->close();
        } catch (Exception \$e) {
            die("Delete failed: " . \$e->getMessage());
        }
    }
    echo "<script>window.location.href='customers.php';</script>";
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/customers.php", $f);
echo "Try catch added!\n";
?>
