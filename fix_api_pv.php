<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/api_place_order.php");

$bad = <<<PHP
    // Insert Items
    \$i_stmt = \$conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, tenant_id) VALUES (?, ?, ?, ?, {$_SESSION['tenant_id']})");
    foreach(\$cart as \$item) {
        \$p_id = (int)\$item['id'];
        \$qty = (int)\$item['qty'];
        \$price = (float)\$item['price'];
        \$i_stmt->bind_param("iiid", \$sale_id, \$p_id, \$qty, \$price);
        \$i_stmt->execute();
        
        // Deduct Stock
        \$conn->query("UPDATE products SET stock_quantity = stock_quantity - \$qty WHERE id = \$p_id");
    }
PHP;

$good = <<<PHP
    // Insert Items
    \$i_stmt = \$conn->prepare("INSERT INTO sale_items (sale_id, product_id, variation_id, quantity, price, tenant_id) VALUES (?, ?, ?, ?, ?, ?)");
    \$tenant_id = \$_SESSION['tenant_id'];
    foreach(\$cart as \$item) {
        // App.js sends id as "product_id-variation_id" if variation is used, but it also sends product_id and variation_id fields!
        // Let's use item['product_id'] and item['variation_id'] if available.
        \$p_id = isset(\$item['product_id']) ? (int)\$item['product_id'] : (int)\$item['id'];
        \$v_id = !empty(\$item['variation_id']) ? (int)\$item['variation_id'] : null;
        \$qty = (int)\$item['qty'];
        \$price = (float)\$item['price'];
        
        \$i_stmt->bind_param("iiidid", \$sale_id, \$p_id, \$v_id, \$qty, \$price, \$tenant_id);
        \$i_stmt->execute();
        
        // Deduct Stock
        if (\$v_id) {
            \$conn->query("UPDATE product_variations SET stock = stock - \$qty WHERE id = \$v_id");
            // Also deduct main stock to keep them in sync if desired, but typically variations track their own stock.
        } else {
            \$conn->query("UPDATE products SET stock_quantity = stock_quantity - \$qty WHERE id = \$p_id");
        }
    }
PHP;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/api_place_order.php", $f);
echo "api_place_order.php variations fixed!";
?>
