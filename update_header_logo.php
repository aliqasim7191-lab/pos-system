<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/includes/header.php");

// 1. Add settings query at the top
$php_inject = <<<'PHP'
  $isManagerOrAdmin = in_array($user_role, ['admin', 'manager', 'super_admin']);
  
  // Fetch Tenant Settings (Logo & Name)
  $tSettings = [];
  $tSetQ = $conn->query("SELECT setting_key, setting_value FROM settings WHERE tenant_id = " . ($_SESSION['tenant_id'] ?? 1));
  if($tSetQ) { while($row = $tSetQ->fetch_assoc()) { $tSettings[$row['setting_key']] = $row['setting_value']; } }
  $headerStoreName = $tSettings['store_name'] ?? 'SuperStore';
  $headerLogo = !empty($tSettings['store_logo']) ? $tSettings['store_logo'] : 'assets/images/logo.jpg';

PHP;

$f = str_replace('$isManagerOrAdmin = in_array($user_role, [\'admin\', \'manager\', \'super_admin\']);', $php_inject, $f);

// 2. Replace hardcoded logo and name
$old_logo = '<img src="assets/images/logo.jpg" alt="Logo" style="height: 32px; width: 32px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
$new_logo = '<img src="<?php echo htmlspecialchars($headerLogo); ?>" alt="Logo" style="height: 32px; width: auto; max-width: 80px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background: white;">';
$f = str_replace($old_logo, $new_logo, $f);

$old_name = '<span style="font-weight: 800; letter-spacing: -0.5px; color: #ffffff;">SuperStore</span>';
$new_name = '<span style="font-weight: 800; letter-spacing: -0.5px; color: #ffffff;"><?php echo htmlspecialchars($headerStoreName); ?></span>';
$f = str_replace($old_name, $new_name, $f);

file_put_contents("C:/xampp/htdocs/point of sale/includes/header.php", $f);
echo "Header Logo Updated!";
?>
