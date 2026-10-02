<?php
include 'includes/db.php';

$keyword_map = [
    'soap' => 'soap,bar',
    'suger' => 'sugar',
    'shampo' => 'shampoo,bottle',
    'Sugar' => 'sugar',
    'Rice' => 'rice,grain',
    'Lays' => 'potato,chips',
    'Coca' => 'coca,cola,drink',
    'Nestl' => 'milk,carton',
    'Lipton' => 'tea,cup',
    'Tapal' => 'tea,leaves',
    'National' => 'ketchup,bottle',
    'Shan' => 'spices,masala',
    'Dettol' => 'soap,dettol',
    'Safeguard' => 'soap,bar',
    'Surf' => 'laundry,detergent',
    'Ariel' => 'laundry,detergent',
    'Colgate' => 'toothpaste',
    'Sensodyne' => 'toothpaste',
    'Dalda' => 'cooking,oil',
    'Habib' => 'cooking,oil',
    'Sunsilk' => 'shampoo',
    'Clear' => 'shampoo',
    'Pampers' => 'diapers',
    'Nestle' => 'juice,mango',
    'Olpers' => 'milk,glass',
    'Peak' => 'biscuits,cookies',
    'LU' => 'chocolate,biscuits',
    'Everyday' => 'milk,powder'
];

$stmt = $conn->prepare("SELECT id, name FROM products");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['name'];
    
    $search_keyword = 'grocery'; // default
    foreach ($keyword_map as $key => $kw) {
        if (stripos($name, $key) !== false) {
            $search_keyword = $kw;
            break;
        }
    }
    
    // Use loremflickr to get a real photo matching the keyword
    // Adding $id to the URL ensures the image doesn't get cached incorrectly for different products
    $imgPath = "https://loremflickr.com/300/300/" . $search_keyword . "?random=" . $id;
    
    $updateStmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
    $updateStmt->bind_param("si", $imgPath, $id);
    $updateStmt->execute();
    $updateStmt->close();
}
$stmt->close();

echo "All products updated with REAL photo URLs from LoremFlickr!";
?>
