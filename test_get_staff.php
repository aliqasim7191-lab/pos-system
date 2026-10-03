<?php
$_SERVER['REQUEST_METHOD']='GET';
session_start();
$_SESSION['user_id']=1;
$_SESSION['role']='admin';
include 'staff.php';

