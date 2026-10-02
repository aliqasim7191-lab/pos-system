<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/staff.php");

// 1. UPDATE SELECT QUERY
$f = preg_replace(
    '/FROM users u \s*LEFT JOIN branches b ON u.branch_id = b.id/',
    'FROM users u LEFT JOIN branches b ON u.branch_id = b.id WHERE u.tenant_id = ' . "\$_SESSION['tenant_id']" . ' AND u.role != \'super_admin\'',
    $f
);

// 2. UPDATE INSERT QUERY
$f = str_replace(
    'INSERT INTO users (username, password, role, branch_id) VALUES (?, ?, ?, ?)',
    'INSERT INTO users (username, password, role, branch_id, tenant_id) VALUES (?, ?, ?, ?, ' . "\$_SESSION['tenant_id']" . ')',
    $f
);

// 3. UPDATE UPDATE QUERY 1 (with password)
$f = str_replace(
    'UPDATE users SET username=?, password=?, role=?, branch_id=? WHERE id=?',
    'UPDATE users SET username=?, password=?, role=?, branch_id=? WHERE id=? AND tenant_id=' . "\$_SESSION['tenant_id']",
    $f
);

// 4. UPDATE UPDATE QUERY 2 (without password)
$f = str_replace(
    'UPDATE users SET username=?, role=?, branch_id=? WHERE id=?',
    'UPDATE users SET username=?, role=?, branch_id=? WHERE id=? AND tenant_id=' . "\$_SESSION['tenant_id']",
    $f
);

// 5. UPDATE DELETE QUERY
$f = str_replace(
    'DELETE FROM users WHERE id = ?',
    'DELETE FROM users WHERE id = ? AND tenant_id=' . "\$_SESSION['tenant_id']",
    $f
);

// 6. Fix Branch List dropdown to only show tenant's branches
$f = str_replace(
    'SELECT id, name FROM branches',
    'SELECT id, name FROM branches WHERE tenant_id = ' . "\$_SESSION['tenant_id']",
    $f
);


file_put_contents("C:/xampp/htdocs/point of sale/staff.php", $f);
echo "Staff.php completely isolated!";
?>
