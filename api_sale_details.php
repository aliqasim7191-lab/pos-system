<?php
include 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No sale ID provided']);
    exit;
}

$sale_id = intval($_GET['id']);

// Fetch sale info
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale_result = $stmt->get_result();
if ($sale_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}
$sale = $sale_result->fetch_assoc();
$stmt->close();

// Fetch items
$stmt = $conn->prepare("SELECT si.*, p.name, p.unit FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'sale' => $sale,
    'items' => $items
]);
?>
