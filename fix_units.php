<?php
include 'includes/db.php';

$stmt = $conn->prepare("SELECT id, name FROM products");
$stmt->execute();
$result = $stmt->get_result();

$kg_keywords = ['meat', 'beef', 'chicken', 'mutton', 'apple', 'sugar', 'rice', 'fish', 'tomato', 'potato', 'onion', 'flour'];
$l_keywords = ['milk', 'oil', 'juice', 'water', 'shampoo'];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = strtolower($row['name']);
    
    $unit = 'pcs'; // default
    
    foreach ($kg_keywords as $kw) {
        if (strpos($name, $kw) !== false) {
            $unit = 'kg';
            break;
        }
    }
    
    if ($unit === 'pcs') {
        foreach ($l_keywords as $kw) {
            if (strpos($name, $kw) !== false) {
                $unit = 'L';
                break;
            }
        }
    }
    
    $updateStmt = $conn->prepare("UPDATE products SET unit = ? WHERE id = ?");
    $updateStmt->bind_param("si", $unit, $id);
    $updateStmt->execute();
    $updateStmt->close();
}
$stmt->close();

echo "Units updated successfully based on product names!";
?>
