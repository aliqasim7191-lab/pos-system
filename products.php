<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

$conn = new mysqli('localhost', 'root', '', 'pos_system');

// 1. Authenticate Request
$headers = apache_request_headers();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Missing or Invalid Authorization Header"]);
    exit();
}

$token = $conn->real_escape_string($matches[1]);
$userQ = $conn->query("SELECT id, branch_id FROM users WHERE api_token = '$token' LIMIT 1");

if (!$userQ || $userQ->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid API Token"]);
    exit();
}

$user = $userQ->fetch_assoc();
$branch_id = $user['branch_id'] ?? 1;

// 2. Handle Request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch products for this branch
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $res = $conn->query("SELECT id, name, price, stock, barcode, category FROM products WHERE branch_id = $branch_id LIMIT $limit OFFSET $offset");
    
    $products = [];
    while($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "branch_id" => $branch_id,
        "count" => count($products),
        "data" => $products
    ]);
} 
elseif ($method === 'POST') {
    // e.g. Sync a product from E-commerce
    $input = json_decode(file_get_contents('php://input'), true);
    
    if(!isset($input['name']) || !isset($input['price'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Name and Price are required"]);
        exit();
    }
    
    $name = $conn->real_escape_string($input['name']);
    $price = floatval($input['price']);
    $stock = isset($input['stock']) ? floatval($input['stock']) : 0;
    $barcode = isset($input['barcode']) ? $conn->real_escape_string($input['barcode']) : '';
    
    $stmt = $conn->prepare("INSERT INTO products (name, price, stock, barcode, branch_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sddsi", $name, $price, $stock, $barcode, $branch_id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Product created", "product_id" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
