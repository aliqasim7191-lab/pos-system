<?php
session_start();
include 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if(isset($data['action']) && $data['action'] === 'delete') {
        $id = intval($data['id']);
        $conn->query("DELETE FROM held_carts WHERE id = $id");
        echo json_encode(['success' => true]);
        exit;
    }

    $cart = json_encode($data['cart'] ?? []);
    $ref = $conn->real_escape_string($data['reference_note'] ?? '');
    $walkin = $conn->real_escape_string($data['walkin_name'] ?? '');
    $takenby = $conn->real_escape_string($data['taken_by'] ?? '');
    $discount = floatval($data['discount'] ?? 0);
    $customerId = !empty($data['customer_id']) ? intval($data['customer_id']) : 'NULL';
    $userId = intval($_SESSION['user_id']);

    $query = "INSERT INTO held_carts (user_id, customer_id, cart_data, discount, reference_note, walkin_name, taken_by, tenant_id) VALUES ($userId, $customerId, '$cart', $discount, '$ref', '$walkin', '$takenby', {$_SESSION['tenant_id']})";
              
    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }

} else if ($method === 'GET') {
    $tenant_id = $_SESSION['tenant_id'];
    $res = $conn->query("SELECT h.*, c.name as customer_name FROM held_carts h LEFT JOIN customers c ON h.customer_id = c.id WHERE h.tenant_id = $tenant_id ORDER BY h.created_at DESC");
    $sales = [];
    if ($res) {
        while($r = $res->fetch_assoc()) {
            $r['items'] = json_decode($r['cart_data'], true);
            $sales[] = $r;
        }
    }
    echo json_encode(['success' => true, 'held_sales' => $sales]);
}
?>
