<?php
include 'includes/db.php';
$conn->query("UPDATE products SET image='perfume.jpg' WHERE name='Perfume'");
$conn->query("UPDATE products SET image='face_serum.jpg' WHERE name='Face Serum'");
echo "Updated two images.";
?>
