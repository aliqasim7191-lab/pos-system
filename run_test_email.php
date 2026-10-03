<?php
include 'includes/db.php';
include 'includes/mailer.php';

$res = send_email_smtp("aliqasim7191@gmail.com", "Test from POS System", "This is a test message to see if emails are working.", $conn);

echo $res ? "Email sent function returned true." : "Email sent function returned false.";
?>
