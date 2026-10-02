<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$current_branch_id = $_SESSION['branch_id'] ?? 1;
include 'includes/db.php';
// Image Processing Helper (High Quality, Resolution Setting)
function processAndSaveImage($tmpName, $targetPath, $maxWidth = 800, $maxHeight = 800, $quality = 90) {
    // Fallback if GD library is not enabled
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
        if (move_uploaded_file($tmpName, $targetPath)) return $targetPath;
        return false;
    }

    $info = getimagesize($tmpName);
    if (!$info) return false;
    
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($tmpName); break;
        case 'image/png': $img = imagecreatefrompng($tmpName); break;
        case 'image/webp': $img = imagecreatefromwebp($tmpName); break;
        case 'image/gif': $img = imagecreatefromgif($tmpName); break;
        default: return move_uploaded_file($tmpName, $targetPath); // Fallback
    }
    
    $width = imagesx($img);
    $height = imagesy($img);
    
    // Calculate new dimensions (maintain aspect ratio)
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    if ($ratio < 1) {
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }
    
    // Create new image with high quality
    $newImg = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG/WEBP, otherwise white background
    if ($mime == 'image/png' || $mime == 'image/webp') {
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        imagefilledrectangle($newImg, 0, 0, $newWidth, $newHeight, $transparent);
    } else {
        $white = imagecolorallocate($newImg, 255, 255, 255);
        imagefill($newImg, 0, 0, $white);
    }
    
    imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save as JPEG for better compatibility and quality (unless it's PNG)
    if ($mime == 'image/png') {
        imagepng($newImg, $targetPath, 2); // 0-9 compression
    } else {
        // Enforce .jpg extension if saving as JPEG
        $targetPath = preg_replace('/\.(png|webp|gif)$/i', '.jpg', $targetPath);
        imagejpeg($newImg, $targetPath, $quality);
    }
    
    imagedestroy($img);
    imagedestroy($newImg);
    return $targetPath;
}

// Handle adding a new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'];
    $purchase_price = $_POST['purchase_price'] ?? 0;
    $price = $_POST['price'];
    $wholesale_price = !empty($_POST['wholesale_price']) ? $_POST['wholesale_price'] : $price;
    $stock = $_POST['stock'];
    $unit = $_POST['unit'];
    $barcode = $_POST['barcode'];
    $category = $_POST['category'] ?? '';
    $imagePath = null;

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        $finalPath = processAndSaveImage($_FILES['image']['tmp_name'], $targetFilePath);
        if ($finalPath) {
            $imagePath = $finalPath;
        }
    }

    $tax_class_id = !empty($_POST['tax_class_id']) ? intval($_POST['tax_class_id']) : null;
    $stmt = $conn->prepare("INSERT INTO products (name, purchase_price, price, wholesale_price, stock, image, unit, barcode, category, tax_class_id, branch_id, tenant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, {$_SESSION['tenant_id']})");
    $stmt->bind_param("sdddissssii", $name, $purchase_price, $price, $wholesale_price, $stock, $imagePath, $unit, $barcode, $category, $tax_class_id, $current_branch_id);
    $stmt->execute();
    $stmt->close();
    header("Location: products.php");
    exit();
}

// Handle editing a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $purchase_price = $_POST['purchase_price'] ?? 0;
    $price = $_POST['price'];
    $wholesale_price = !empty($_POST['wholesale_price']) ? $_POST['wholesale_price'] : $price;
    $stock = $_POST['stock'];
    $unit = $_POST['unit'];
    $barcode = $_POST['barcode'];
    $category = $_POST['category'] ?? '';
    
    $tax_class_id = !empty($_POST['tax_class_id']) ? intval($_POST['tax_class_id']) : null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        $finalPath = processAndSaveImage($_FILES['image']['tmp_name'], $targetFilePath);
        if ($finalPath) {
            $stmt = $conn->prepare("UPDATE products SET name=?, purchase_price=?, price=?, wholesale_price=?, stock=?, image=?, unit=?, barcode=?, category=?, tax_class_id=? WHERE id=?");
            $stmt->bind_param("sdddissssii", $name, $purchase_price, $price, $wholesale_price, $stock, $finalPath, $unit, $barcode, $category, $tax_class_id, $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        
            // Fetch old data
            $id = intval($_POST["id"]);
            $oldQ = $conn->query("SELECT price, stock FROM products WHERE id = $id");
            if ($oldQ && $oldQ->num_rows > 0) {
                $oldD = $oldQ->fetch_assoc();
                if ($oldD["price"] != $price || $oldD["stock"] != $stock) {
                    if(function_exists("log_audit")) {
                        log_audit($conn, $_SESSION["user_id"], $current_branch_id, "UPDATE_PRODUCT", "product", $id, "Price: {$oldD['price']}, Stock: {$oldD['stock']}", "Price: $price, Stock: $stock", "Updated product: $name");
                    }
                }
            }
            $stmt = $conn->prepare("UPDATE products SET name=?, purchase_price=?, price=?, wholesale_price=?, stock=?, unit=?, barcode=?, category=?, tax_class_id=? WHERE id=?");
        $stmt->bind_param("sdddisssii", $name, $purchase_price, $price, $wholesale_price, $stock, $unit, $barcode, $category, $tax_class_id, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: products.php");
    exit();
}

// Handle deleting a product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get image path to delete the physical file
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imagePath);
    if ($stmt->fetch()) {
        if ($imagePath && file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    $stmt->close();

    // Delete from database
    
            // Fetch before delete
            $oldQ = $conn->query("SELECT name FROM products WHERE id = " . intval($_POST["id"]));
            $oldName = ($oldQ && $oldQ->num_rows > 0) ? $oldQ->fetch_assoc()["name"] : "Unknown";
            
            if(function_exists("log_audit")) {
                log_audit($conn, $_SESSION["user_id"], $current_branch_id, "DELETE_PRODUCT", "product", $_POST["id"], $oldName, null, "Deleted product: " . $oldName);
            }
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: products.php");
    exit();
}

// Handle GET: Download Sample Excel CSV Template
if (isset($_GET['action']) && $_GET['action'] === 'download_sample') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SuperStore_Inventory_Sample.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Product Name', 'Barcode', 'Category', 'Selling Price', 'Purchase Price', 'Wholesale Price', 'Stock Level', 'Unit', 'Expiry Date']);
    fputcsv($output, ['Fresh Milk 1L', '890123456701', 'Dairy & Milk', '3.50', '2.50', '3.20', '50', 'pcs', '2026-12-31']);
    fputcsv($output, ['Whole Wheat Bread', '890123456702', 'Bakery', '2.20', '1.50', '2.00', '30', 'pcs', '2026-10-15']);
    fputcsv($output, ['Organic Red Apples', '890123456703', 'Fruits', '4.00', '2.80', '3.80', '100', 'kg', '']);
    
    fclose($output);
    exit();
}

// Handle GET: Export All Products to Excel CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $filename = "Inventory_Export_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Product Name', 'Barcode', 'Category', 'Selling Price', 'Purchase Price', 'Wholesale Price', 'Stock Level', 'Unit', 'Expiry Date']);
    
    $tenant_id = intval($_SESSION['tenant_id']);
    $query = "SELECT * FROM products WHERE tenant_id = $tenant_id ORDER BY id DESC";
    $res = $conn->query($query);
    
    if ($res && $res->num_rows > 0) {
        while ($p = $res->fetch_assoc()) {
            fputcsv($output, [
                $p['name'],
                $p['barcode'] ?? '',
                $p['category'] ?? '',
                $p['price'],
                $p['purchase_price'] ?? '0',
                $p['wholesale_price'] ?? $p['price'],
                $p['stock'],
                $p['unit'] ?? 'pcs',
                $p['expiry_date'] ?? ''
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Handle POST: Import Products from Excel / CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_excel') {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === 0) {
        $tmpFile = $_FILES['excel_file']['tmp_name'];
        $handle = fopen($tmpFile, "r");
        
        if ($handle !== false) {
            $addedCount = 0;
            $updatedCount = 0;
            
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            
            $header = fgetcsv($handle, 1000, ",");
            if (!$header) {
                rewind($handle);
                $header = fgetcsv($handle, 1000, ";");
            }
            
            $colMap = [
                'name' => 0,
                'barcode' => 1,
                'category' => 2,
                'price' => 3,
                'purchase_price' => 4,
                'wholesale_price' => 5,
                'stock' => 6,
                'unit' => 7,
                'expiry_date' => 8
            ];
            
            if ($header) {
                foreach ($header as $idx => $colName) {
                    $c = strtolower(trim($colName));
                    if (strpos($c, 'name') !== false || strpos($c, 'product') !== false) $colMap['name'] = $idx;
                    elseif (strpos($c, 'barcode') !== false || strpos($c, 'sku') !== false) $colMap['barcode'] = $idx;
                    elseif (strpos($c, 'category') !== false || strpos($c, 'cat') !== false) $colMap['category'] = $idx;
                    elseif (strpos($c, 'selling') !== false || strpos($c, 'sale price') !== false || $c === 'price') $colMap['price'] = $idx;
                    elseif (strpos($c, 'purchase') !== false || strpos($c, 'cost') !== false) $colMap['purchase_price'] = $idx;
                    elseif (strpos($c, 'wholesale') !== false) $colMap['wholesale_price'] = $idx;
                    elseif (strpos($c, 'stock') !== false || strpos($c, 'qty') !== false || strpos($c, 'quantity') !== false) $colMap['stock'] = $idx;
                    elseif (strpos($c, 'unit') !== false) $colMap['unit'] = $idx;
                    elseif (strpos($c, 'expiry') !== false || strpos($c, 'exp') !== false) $colMap['expiry_date'] = $idx;
                }
            }
            
            $tenant_id = intval($_SESSION['tenant_id']);
            $branch_id = intval($current_branch_id);
            
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) < 2) continue;
                
                $name = trim($data[$colMap['name']] ?? '');
                if (empty($name) || strtolower($name) === 'product name' || strtolower($name) === 'name') continue;
                
                $barcode = trim($data[$colMap['barcode']] ?? '');
                $category = trim($data[$colMap['category']] ?? 'General');
                $price = floatval($data[$colMap['price']] ?? 0);
                $purchase_price = floatval($data[$colMap['purchase_price']] ?? 0);
                $wholesale_price = floatval($data[$colMap['wholesale_price']] ?? $price);
                $stock = floatval($data[$colMap['stock']] ?? 0);
                $unit = trim($data[$colMap['unit']] ?? 'pcs');
                if (empty($unit)) $unit = 'pcs';
                $expiry_date = trim($data[$colMap['expiry_date']] ?? '');
                $expiry_val = !empty($expiry_date) ? "'" . $conn->real_escape_string($expiry_date) . "'" : "NULL";
                
                $checkQ = false;
                if (!empty($barcode)) {
                    $checkQ = $conn->query("SELECT id, stock FROM products WHERE barcode = '" . $conn->real_escape_string($barcode) . "' AND tenant_id = $tenant_id LIMIT 1");
                }
                if (!$checkQ || $checkQ->num_rows === 0) {
                    $checkQ = $conn->query("SELECT id, stock FROM products WHERE name = '" . $conn->real_escape_string($name) . "' AND tenant_id = $tenant_id LIMIT 1");
                }
                
                if ($checkQ && $checkQ->num_rows > 0) {
                    $existing = $checkQ->fetch_assoc();
                    $existId = $existing['id'];
                    
                    $updateSql = "UPDATE products SET 
                        name = '" . $conn->real_escape_string($name) . "',
                        price = $price,
                        purchase_price = $purchase_price,
                        wholesale_price = $wholesale_price,
                        stock = stock + $stock,
                        unit = '" . $conn->real_escape_string($unit) . "',
                        category = '" . $conn->real_escape_string($category) . "'";
                    
                    if (!empty($barcode)) {
                        $updateSql .= ", barcode = '" . $conn->real_escape_string($barcode) . "'";
                    }
                    if (!empty($expiry_date)) {
                        $updateSql .= ", expiry_date = $expiry_val";
                    }
                    $updateSql .= " WHERE id = $existId AND tenant_id = $tenant_id";
                    
                    $conn->query($updateSql);
                    $updatedCount++;
                } else {
                    $insSql = "INSERT INTO products (name, barcode, category, price, purchase_price, wholesale_price, stock, unit, expiry_date, branch_id, tenant_id) 
                        VALUES (
                            '" . $conn->real_escape_string($name) . "',
                            '" . $conn->real_escape_string($barcode) . "',
                            '" . $conn->real_escape_string($category) . "',
                            $price,
                            $purchase_price,
                            $wholesale_price,
                            $stock,
                            '" . $conn->real_escape_string($unit) . "',
                            $expiry_val,
                            $branch_id,
                            $tenant_id
                        )";
                    $conn->query($insSql);
                    $addedCount++;
                }
            }
            fclose($handle);
            
            $_SESSION['import_status'] = [
                'type' => 'success',
                'msg' => "Excel Import Successful! 📥 <b>$addedCount</b> Naye Products Add Huey | <b>$updatedCount</b> Existing Products Ka Stock Update Hua!"
            ];
        } else {
            $_SESSION['import_status'] = ['type' => 'error', 'msg' => 'Excel file ko read karne me masla hua. Please check file format.'];
        }
    } else {
        $_SESSION['import_status'] = ['type' => 'error', 'msg' => 'File upload me error aya.'];
    }
    header("Location: products.php");
    exit();
}

include 'includes/header.php';

// Check if we are in edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editRes = $conn->query("SELECT * FROM products WHERE id = $editId");
    if ($editRes && $editRes->num_rows > 0) {
        $editProduct = $editRes->fetch_assoc();
    }
}

if ($isAdmin) {
    $result = $conn->query("SELECT p.*, b.name as branch_name FROM products p LEFT JOIN branches b ON p.branch_id = b.id WHERE p.tenant_id = {$_SESSION['tenant_id']} ORDER BY p.id DESC");
} else {
    $result = $conn->query("SELECT p.*, b.name as branch_name FROM products p LEFT JOIN branches b ON p.branch_id = b.id WHERE p.tenant_id = {$_SESSION['tenant_id']} AND p.branch_id = $current_branch_id ORDER BY p.id DESC");
}

$products = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<?php if (isset($_SESSION['import_status'])): ?>
    <div style="padding: 1rem 1.25rem; margin-bottom: 1.5rem; border-radius: 10px; font-weight: 500; display: flex; align-items: center; justify-content: space-between; background: <?php echo $_SESSION['import_status']['type'] === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $_SESSION['import_status']['type'] === 'success' ? '#166534' : '#991b1b'; ?>; border: 1px solid <?php echo $_SESSION['import_status']['type'] === 'success' ? '#86efac' : '#fca5a5'; ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
        <div><?php echo $_SESSION['import_status']['msg']; ?></div>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; font-size: 1.2rem; cursor:pointer; color: inherit; padding: 0 0.5rem;">&times;</button>
    </div>
    <?php unset($_SESSION['import_status']); ?>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.8rem; color: #0f172a; margin-bottom: 0.2rem;">Products Inventory</h2>
        <p style="color: #64748b; font-size: 0.95rem;">Manage store inventory, import/export Excel, and pricing.</p>
    </div>
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
        <button type="button" onclick="openImportModal()" class="btn" style="background:#2563eb; color:#ffffff; border:none; box-shadow: 0 2px 4px rgba(37,99,235,0.25); font-weight: 600; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; transition: all 0.2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">📥 Import Excel / CSV</button>
        <a href="products.php?action=export_excel" class="btn" style="background:#ffffff; color:#059669; border:1px solid #a7f3d0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-weight: 600; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; transition: all 0.2s; text-decoration: none;" onmouseover="this.style.background='#ecfdf5'" onmouseout="this.style.background='#ffffff'">📤 Export to Excel</a>
        <a href="print_barcodes.php" target="_blank" class="btn" style="background:#ffffff; color:#0369a1; border:1px solid #7dd3fc; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-weight: 600; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; transition: all 0.2s; text-decoration: none;" onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#ffffff'">🏷️ Print Barcodes</a>
        <button onclick="exportPDF()" class="btn" style="background:#ffffff; color:#475569; border:1px solid #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-weight: 500; width: auto !important; border-radius: 6px !important; padding: 0.6rem 1.2rem; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">📄 Export PDF</button>
    </div>
</div>

<div class="table-wrapper" style="margin-bottom: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; background: #ffffff;">
    <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; color: #0f172a;"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
            <?php if($editProduct): ?>
                <a href="products.php" class="btn" style="background:#f1f5f9; color:#475569; padding: 0.6rem 1.2rem; border-radius: 6px !important; text-decoration:none; font-weight: 600; width: auto !important; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel Edit</a>
            <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $editProduct ? 'edit' : 'add'; ?>">
            <?php if($editProduct): ?>
                <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Left Column -->
                <div>
                    <h4 style="color: #64748b; margin-bottom: 1rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Product Information</h4>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Product Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Barcode / SKU</label>
                            <input type="text" name="barcode" value="<?php echo htmlspecialchars($editProduct['barcode'] ?? ''); ?>" placeholder="Scan or type..." style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </div>
                        <div>
                            
    <div style="flex: 1;">
        <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Expiry Date</label>
        <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($editProduct['expiry_date'] ?? ''); ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
    </div>

<label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Category</label>
                            <input type="text" name="category" value="<?php echo htmlspecialchars($editProduct['category'] ?? ''); ?>" placeholder="e.g. Grocery" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; margin-bottom:1rem;">
                            
                            <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Tax Class (Optional)</label>
                            <select name="tax_class_id" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                                <option value="">Global Default Tax</option>
                                <?php
                                $taxQ = $conn->query("SELECT * FROM tax_classes");
                                if ($taxQ) {
                                    while($t = $taxQ->fetch_assoc()) {
                                        $sel = (isset($editProduct['tax_class_id']) && $editProduct['tax_class_id'] == $t['id']) ? 'selected' : '';
                                        echo "<option value='{$t['id']}' $sel>{$t['name']} ({$t['rate']}%)</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Product Image <?php echo $editProduct && $editProduct['image'] ? '<span style="color:#64748b; font-weight:normal; font-size:0.8rem;">(Leave empty to keep current)</span>' : ''; ?></label>
                        <input type="file" name="image" accept="image/*" style="width: 100%; padding: 0.5rem; border: 1px dashed #cbd5e1; border-radius: 6px; background: #f8fafc; cursor: pointer;">
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <h4 style="color: #64748b; margin-bottom: 1rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">Pricing & Inventory</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Purchase Price ($)</label>
                            <input type="number" step="0.01" name="purchase_price" value="<?php echo htmlspecialchars($editProduct['purchase_price'] ?? ''); ?>" required placeholder="0.00" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Selling Price ($) *</label>
                            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required placeholder="0.00" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Stock Level</label>
                            <input type="number" step="0.01" name="stock" value="<?php echo htmlspecialchars($editProduct['stock'] ?? '0'); ?>" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #0f172a;">Unit</label>
                            <input list="unit-options" name="unit" value="<?php echo htmlspecialchars($editProduct['unit'] ?? ''); ?>" required placeholder="e.g. pcs, kg" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none;">
                            <datalist id="unit-options">
                                <option value="pcs">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="L">Liters</option>
                                <option value="Box">Box</option>
                                <option value="Packet">Packet</option>
                            </datalist>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem; font-weight: 600; border-radius: 8px !important; box-shadow: 0 2px 4px rgba(37,99,235,0.2); width: auto !important; display: inline-flex; align-items: center; justify-content: center;"><?php echo $editProduct ? 'Save Changes' : '+ Add Product'; ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div style="margin-bottom: 1rem; display: flex; justify-content: flex-end;">
    <input type="text" id="inventorySearch" placeholder="🔍 Search products by name or barcode..." style="padding: 0.8rem 1.2rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 300px; outline: none; font-size: 0.95rem;">
</div>

<div class="table-wrapper" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: visible; background: #ffffff;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
            <tr>
                <th style="padding: 0.65rem 0.5rem; text-align: left; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Product</th>
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">SKU / Barcode</th>
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Category</th>
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Expiry</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Cost</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Selling Price</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Profit</th>
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Stock</th>
                <th style="padding: 0.65rem 0.4rem; text-align: center; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Status</th>
                <th style="padding: 0.65rem 0.5rem; text-align: right; color: #475569; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; white-space: nowrap;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($products as $product): ?>
            <?php
                $profit = $product['price'] - ($product['purchase_price'] ?? 0);
                $margin = $product['price'] > 0 ? ($profit / $product['price']) * 100 : 0;
            ?>
            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                <td style="padding: 0.6rem 0.5rem; white-space: nowrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php if($product['image']): ?>
                            <?php 
                                $img_src = htmlspecialchars($product['image']);
                                if (!preg_match('/^http/', $img_src) && !preg_match('/^uploads\//', $img_src)) {
                                    $img_src = 'uploads/' . $img_src;
                                }
                            ?>
                            <img src="<?php echo $img_src; ?>" alt="Product" style="width: 38px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; flex-shrink: 0;">
                        <?php else: ?>
                            <div style="width: 38px; height: 38px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #94a3b8; border: 1px solid #e2e8f0; flex-shrink: 0;">N/A</div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight: 600; color: #0f172a; font-size: 0.85rem;"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div style="font-size: 0.75rem; color: #64748b;">ID: #<?php echo $product['id']; ?></div>
                        </div>
                    </div>
                </td>
                <td style="padding: 0.6rem 0.4rem; text-align: center; color: #475569; font-family: monospace; font-size: 0.82rem; white-space: nowrap;"><?php echo htmlspecialchars($product['barcode'] ?? '-'); ?></td>
                <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap;">
                    <span style="background: #eff6ff; color: var(--primary-color); padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600;">
                        <?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?>
                    </span>
                </td>
                <td style="padding: 0.6rem 0.4rem; text-align: center; color: #475569; font-size: 0.8rem; white-space: nowrap;">
                    <?php 
                    if(!empty($product['expiry_date'])) {
                        $days = (strtotime($product['expiry_date']) - time()) / 86400;
                        if($days < 0) echo '<span style="color:#ef4444; font-weight:600;">'.date("M d, Y", strtotime($product['expiry_date'])).' (EXP)</span>';
                        elseif($days <= 30) echo '<span style="color:#d97706; font-weight:600;">'.date("M d, Y", strtotime($product['expiry_date'])).' (Soon)</span>';
                        else echo date("M d, Y", strtotime($product['expiry_date']));
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
                <td style="padding: 0.6rem 0.5rem; text-align: right; color: #64748b; font-size: 0.85rem; white-space: nowrap;">$<?php echo number_format($product['purchase_price'] ?? 0, 2); ?></td>
                <td style="padding: 0.6rem 0.5rem; text-align: right; color: #0f172a; font-weight: 600; font-size: 0.85rem; white-space: nowrap;">$<?php echo number_format($product['price'], 2); ?></td>
                <td style="padding: 0.6rem 0.5rem; text-align: right; white-space: nowrap;">
                    <div style="color: #10b981; font-weight: 600; font-size: 0.85rem;">$<?php echo number_format($profit, 2); ?></div>
                    <div style="font-size: 0.72rem; color: #64748b;"><?php echo number_format($margin, 1); ?>% margin</div>
                </td>
                <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap;">
                    <div style="font-weight: 600; font-size: 0.85rem; color: <?php echo $product['stock'] <= 10 ? '#ef4444' : '#0f172a'; ?>;"><?php echo floatval($product['stock']); ?></div>
                    <div style="font-size: 0.72rem; color: #64748b;"><?php echo htmlspecialchars($product['unit'] ?? 'pcs'); ?></div>
                </td>
                <td style="padding: 0.6rem 0.4rem; text-align: center; white-space: nowrap;">
                    <?php if($product['stock'] <= 0): ?>
                        <span style="background: #fee2e2; color: #ef4444; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.72rem; font-weight: 600;">Out of Stock</span>
                    <?php elseif($product['stock'] <= 10): ?>
                        <span style="background: #fef3c7; color: #d97706; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.72rem; font-weight: 600;">Low Stock</span>
                    <?php else: ?>
                        <span style="background: #dcfce7; color: #10b981; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.72rem; font-weight: 600;">In Stock</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.6rem 0.5rem; text-align: right; white-space: nowrap;">
                    <div style="position: relative; display: inline-block;">
                        <button type="button" onclick="toggleActionMenu(event, 'actionMenu_<?php echo $product['id']; ?>')" style="background: #e2e8f0; color: #1e293b; border: 1px solid #cbd5e1; padding: 0.35rem 0.75rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; transition: all 0.15s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" onmouseover="this.style.background='#cbd5e1';" onmouseout="this.style.background='#e2e8f0';">
                            Actions <span style="font-size: 0.65rem; color: #475569;">▼</span>
                        </button>
                        <div id="actionMenu_<?php echo $product['id']; ?>" class="action-dropdown-menu" style="display: none; position: absolute; right: 0; top: calc(100% + 4px); background: #ffffff; min-width: 160px; box-shadow: 0 10px 25px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.08); border-radius: 10px; z-index: 9999; padding: 0.4rem 0; text-align: left;">
                            <a href="products.php?edit=<?php echo $product['id']; ?>" style="display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.9rem; color: #2563eb; text-decoration: none; font-size: 0.83rem; font-weight: 600; transition: background 0.15s;" onmouseover="this.style.background='#eff6ff';" onmouseout="this.style.background='transparent';">
                                <span style="font-size: 0.95rem;">✏️</span> <span style="color: #2563eb;">Edit Details</span>
                            </a>
                            <a href="product_variations.php?id=<?php echo $product['id']; ?>" style="display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.9rem; color: #8b5cf6; text-decoration: none; font-size: 0.83rem; font-weight: 600; transition: background 0.15s;" onmouseover="this.style.background='#f5f3ff';" onmouseout="this.style.background='transparent';">
                                <span style="font-size: 0.95rem;">🔀</span> <span style="color: #8b5cf6;">Variations</span>
                            </a>
                            <div style="border-top: 1px solid #f1f5f9; margin: 0.3rem 0;"></div>
                            <a href="products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');" style="display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.9rem; color: #ef4444; text-decoration: none; font-size: 0.83rem; font-weight: 600; transition: background 0.15s;" onmouseover="this.style.background='#fef2f2';" onmouseout="this.style.background='transparent';">
                                <span style="font-size: 0.95rem;">🗑️</span> <span style="color: #ef4444;">Delete Product</span>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($products)): ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 4rem; color: #64748b;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h3 style="margin: 0; color: #0f172a; font-size: 1.2rem;">No Products Yet</h3>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem;">You haven't added any products to your inventory.</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script>

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape'); // Landscape is better for tables
    
    doc.setFontSize(18);
    doc.text("Inventory Management", 14, 20);
    doc.setFontSize(11);
    doc.text("Generated on: " + new Date().toLocaleDateString(), 14, 28);
    
    let rows = [];
    let headers = ['ID', 'Barcode', 'Name', 'Purchase Price', 'Retail Price', 'Wholesale Price', 'Stock'];
    
    const tableBodyRows = document.querySelectorAll('.table-wrapper:last-of-type tbody tr');
    tableBodyRows.forEach(tr => {
        let tds = tr.querySelectorAll('td');
        if (tds.length > 1) { // Skip empty state row
            rows.push([
                tds[0].innerText,
                tds[1].innerText,
                tds[3].innerText,
                tds[4].innerText,
                tds[5].innerText,
                tds[6].innerText,
                tds[7].innerText
            ]);
        }
    });
    
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 35,
        theme: 'grid',
        headStyles: { fillColor: [59, 130, 246] } // Blue header matching UI
    });
    
    doc.save('inventory_export.pdf');
}

function toggleActionMenu(event, menuId) {
    event.stopPropagation();
    document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
        if (menu.id !== menuId) {
            menu.style.display = 'none';
        }
    });
    const targetMenu = document.getElementById(menuId);
    if (targetMenu) {
        targetMenu.style.display = targetMenu.style.display === 'block' ? 'none' : 'block';
    }
}

document.addEventListener('click', function() {
    document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('inventorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const nameCol = row.querySelector('td:nth-child(1)');
                const skuCol = row.querySelector('td:nth-child(2)');
                
                if (!nameCol || !skuCol) return;
                
                const name = nameCol.innerText.toLowerCase();
                const sku = skuCol.innerText.toLowerCase();
                
                if (name.includes(query) || sku.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

function openImportModal() {
    document.getElementById('excelImportModal').style.display = 'flex';
}
function closeImportModal() {
    document.getElementById('excelImportModal').style.display = 'none';
}
</script>

<!-- Excel Import Modal -->
<div id="excelImportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 92%; max-width: 520px; border-radius: 16px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); position: relative;">
        <button type="button" onclick="closeImportModal()" style="position: absolute; top: 1.25rem; right: 1.25rem; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 1.2rem; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center;">&times;</button>
        
        <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1.25rem;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">📥</div>
            <div>
                <h3 style="margin: 0; color: #0f172a; font-size: 1.25rem; font-weight: 700;">Import Inventory from Excel</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.85rem;">Upload your products CSV / Excel spreadsheet</p>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: #334155; line-height: 1.5;">
            <div style="font-weight: 600; color: #0f172a; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <span>💡 Excel File Columns Format</span>
                <a href="products.php?action=download_sample" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 0.2rem;">📄 Download Sample (.csv)</a>
            </div>
            <div style="color: #64748b; font-size: 0.8rem;">
                <b>Required Columns:</b> Product Name, Barcode, Category, Selling Price, Purchase Price, Wholesale Price, Stock Level, Unit
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_excel">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #0f172a; font-size: 0.9rem;">Select CSV / Excel File *</label>
                <input type="file" name="excel_file" accept=".csv, .txt, .xls, .xlsx" required style="width: 100%; padding: 0.75rem; border: 2px dashed #cbd5e1; border-radius: 10px; background: #f8fafc; cursor: pointer;">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" onclick="closeImportModal()" class="btn" style="background: #f1f5f9; color: #475569; padding: 0.7rem 1.4rem; border-radius: 8px !important; font-weight: 600;">Cancel</button>
                <button type="submit" class="btn" style="background: #2563eb; color: white; padding: 0.7rem 1.6rem; border-radius: 8px !important; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.3);">Upload & Import Products</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
