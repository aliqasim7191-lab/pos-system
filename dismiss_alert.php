<?php
session_start();
if(isset($_POST['alert_id'])) {
    if(!isset($_SESSION['dismissed_alerts'])) {
        $_SESSION['dismissed_alerts'] = [];
    }
    $_SESSION['dismissed_alerts'][] = $_POST['alert_id'];
    echo "OK";
}
?>
