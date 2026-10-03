<?php
$_SERVER['REQUEST_METHOD']='POST';
$_POST['action']='add';
$_POST['username']='test_staff';
$_POST['password']='123';
$_POST['role']='cashier';
$_POST['branch_id']=1;
session_start();
$_SESSION['user_id']=1;
$_SESSION['role']='admin';
include 'staff.php';

