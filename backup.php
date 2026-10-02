<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied. Only Super Administrators can download backups.");
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pos_system';

// Backup file name
$backup_file = $db_name . '_' . date("Y-m-d-H-i-s") . '.sql';

// Try to use mysqldump via exec
$dump_command = "C:\\xampp\\mysql\\bin\\mysqldump.exe --user={$db_user} --host={$db_host} {$db_name} > {$backup_file}";

exec($dump_command, $output, $return_var);

if (file_exists($backup_file) && filesize($backup_file) > 0) {
    // Force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($backup_file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($backup_file));
    readfile($backup_file);
    
    // Delete local file after download
    unlink($backup_file);
    exit;
} else {
    echo "<script>alert('Failed to generate backup. Make sure mysqldump is configured properly.'); window.location.href='settings.php';</script>";
}
?>
