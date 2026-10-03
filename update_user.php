<?php
include 'includes/db.php';
$username = 'Qasim';
$password = '123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$conn->query("UPDATE users SET username='$username', password='$hash' WHERE role='admin'");
echo "Username and password updated successfully.";
?>
