<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/branches.php");

$f = str_replace(
    'SELECT * FROM branches ORDER BY id ASC',
    'SELECT * FROM branches WHERE tenant_id = ' . "\$_SESSION['tenant_id']" . ' ORDER BY id ASC',
    $f
);

$f = str_replace(
    'INSERT INTO branches (name, location, phone) VALUES (?, ?, ?)',
    'INSERT INTO branches (name, location, phone, tenant_id) VALUES (?, ?, ?, ' . "\$_SESSION['tenant_id']" . ')',
    $f
);

$f = str_replace(
    'UPDATE branches SET name=?, location=?, phone=? WHERE id=?',
    'UPDATE branches SET name=?, location=?, phone=? WHERE id=? AND tenant_id=' . "\$_SESSION['tenant_id']",
    $f
);

$f = str_replace(
    'DELETE FROM branches WHERE id = ?',
    'DELETE FROM branches WHERE id = ? AND tenant_id=' . "\$_SESSION['tenant_id']",
    $f
);

file_put_contents("C:/xampp/htdocs/point of sale/branches.php", $f);
echo "Branches.php isolated!";
?>
