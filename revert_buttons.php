<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/products.php");

$bad_buttons = <<<PHP
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-start;">
                        <a href="products.php?edit=<?php echo \$product['id']; ?>" class="btn action-btn btn-edit" style="text-decoration:none; padding: 0.4rem 0.8rem;" title="Edit Product"><i class="fa fa-pencil"></i></a>
                        <a href="product_variations.php?id=<?php echo \$product['id']; ?>" class="btn action-btn" style="text-decoration:none; background:#8b5cf6; color:white; padding: 0.4rem 0.8rem;" title="Manage Variations"><i class="fa fa-sitemap"></i> Vari..</a>
                        <a href="products.php?delete=<?php echo \$product['id']; ?>" class="btn action-btn btn-delete" style="text-decoration:none; padding: 0.4rem 0.8rem;" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete Product"><i class="fa fa-trash"></i></a>
                    </div>
PHP;

$good_buttons = <<<PHP
                    <div style="display: flex; gap: 0.5rem; justify-content: flex-start;">
                        <a href="products.php?edit=<?php echo \$product['id']; ?>" class="btn action-btn btn-edit" style="text-decoration:none;">Edit</a>
                        <a href="product_variations.php?id=<?php echo \$product['id']; ?>" class="btn action-btn" style="text-decoration:none; background:#8b5cf6; color:white;">Variations</a>
                        <a href="products.php?delete=<?php echo \$product['id']; ?>" class="btn action-btn btn-delete" style="text-decoration:none;" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                    </div>
PHP;

$f = str_replace($bad_buttons, $good_buttons, $f);

// Make sure the table column width for action is larger so it doesn't squish
$f = str_replace(
    '<th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>',
    '<th style="padding: 1rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; min-width: 250px;">Actions</th>',
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/products.php", $f);
echo "Buttons reverted!";
?>
