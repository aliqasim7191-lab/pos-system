<?php
session_id('test');
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['branch_id'] = 1;
include 'index.php';
