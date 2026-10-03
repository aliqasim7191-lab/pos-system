<?php
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$database = getenv('DB_NAME') ?: "pos_system";
$port = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function to log audit events
function log_audit($conn, $user_id, $branch_id, $action_type, $entity_type, $entity_id, $old_value, $new_value, $description) {
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, branch_id, action_type, entity_type, entity_id, old_value, new_value, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssss", $user_id, $branch_id, $action_type, $entity_type, $entity_id, $old_value, $new_value, $description);
    $stmt->execute();
    $stmt->close();
}

// License Check Interceptor
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'super_admin') {
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage !== 'license.php' && $currentPage !== 'login.php' && $currentPage !== 'logout.php') {
        $tid = intval($_SESSION['tenant_id'] ?? 1);
        $licQ = $conn->query("SELECT id FROM license_keys WHERE tenant_id = $tid AND status = 'active'");
        if (!$licQ || $licQ->num_rows === 0) {
            header("Location: license.php");
            exit;
        }
    }
}
?>
