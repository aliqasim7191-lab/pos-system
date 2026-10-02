<?php
error_reporting(0); // Prevent PHP raw warning/error output breaking JSON
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'includes/db.php';

$tenantId = intval($_SESSION['tenant_id'] ?? 1);
$currentBranchId = intval($_SESSION['branch_id'] ?? 1);
$isAdmin = ($_SESSION['role'] ?? '') === 'super_admin' || ($_SESSION['role'] ?? '') === 'admin';
$userRole = $_SESSION['role'] ?? 'staff';

$input = json_decode(file_get_contents('php://input'), true);
$question = trim($input['question'] ?? '');

if (empty($question)) {
    echo json_encode(['success' => false, 'message' => 'Sawal khali nahi ho sakta. Kripya apna sawal poochen.']);
    exit();
}

$qLower = strtolower($question);

// -------------------------------------------------------------
// 1. SECURITY GUARD: Block password, username, pin, hash requests
// -------------------------------------------------------------
if (
    strpos($qLower, 'password') !== false ||
    strpos($qLower, 'passcode') !== false ||
    strpos($qLower, 'pin') !== false ||
    strpos($qLower, 'secret') !== false ||
    strpos($qLower, 'hash') !== false ||
    strpos($qLower, 'username') !== false ||
    strpos($qLower, 'user name') !== false ||
    strpos($qLower, 'login credentials') !== false
) {
    echo json_encode([
        'success' => true,
        'reply' => "🔒 **Security & Privacy Policy Guard:**\n\nUsernames, Passwords, PINs aur confidential login credentials strict security policy ke tehat hidden hain aur display nahi kiye jate."
    ]);
    exit();
}

// -------------------------------------------------------------
// DYNAMIC DATE PARSER ENGINE
// -------------------------------------------------------------
// DYNAMIC DATE & MONTH PARSER ENGINE
// -------------------------------------------------------------
$targetDateStr = '';
$targetMonthStr = '';
$dateLabelStr = '';
$isMonthQuery = false;

// 1. Month-Based Match (e.g. "august sale", "august month", "this month", "last month", "pichle mahine", "is mahine", "september ki payment")
if (strpos($qLower, 'last month') !== false || strpos($qLower, 'pichle mahine') !== false || strpos($qLower, 'pechly mahine') !== false || strpos($qLower, 'prev month') !== false) {
    $targetMonthStr = date('Y-m', strtotime('first day of last month'));
    $dateLabelStr = "Last Month (" . date('F Y', strtotime('first day of last month')) . ")";
    $isMonthQuery = true;
} elseif (strpos($qLower, 'this month') !== false || strpos($qLower, 'is month') !== false || strpos($qLower, 'is mahine') !== false || strpos($qLower, 'iss mahine') !== false) {
    $targetMonthStr = date('Y-m');
    $dateLabelStr = "This Month (" . date('F Y') . ")";
    $isMonthQuery = true;
} elseif (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|sept|oct|nov|dec)\s*(\d{4})?/i', $qLower, $mMonth) && !preg_match('/\d{1,2}\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)/i', $qLower)) {
    $mName = $mMonth[1];
    $mYear = !empty($mMonth[2]) ? intval($mMonth[2]) : date('Y');
    $parsedMT = strtotime("1 $mName $mYear");
    if ($parsedMT) {
        $targetMonthStr = date('Y-m', $parsedMT);
        $dateLabelStr = date('F Y', $parsedMT);
        $isMonthQuery = true;
    }
}

// 2. Specific Single Date Match (e.g. "15 aug", "12 september", "2026-08-15", "15-08-2026", "15/08/2026", "yesterday", "today", "kal", "aj")
if (!$isMonthQuery) {
    if (strpos($qLower, 'kal') !== false || strpos($qLower, 'yesterday') !== false) {
        $targetDateStr = date('Y-m-d', strtotime('-1 day'));
        $dateLabelStr = "Yesterday (" . date('d M Y', strtotime('-1 day')) . ")";
    } elseif (strpos($qLower, 'aaj') !== false || strpos($qLower, 'aj') !== false || strpos($qLower, 'today') !== false) {
        $targetDateStr = date('Y-m-d');
        $dateLabelStr = "Today (" . date('d M Y') . ")";
    } elseif (preg_match('/(\d{1,2})\s*(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|sept|oct|nov|dec)\s*(\d{4})?/i', $qLower, $m)) {
        $day = intval($m[1]);
        $monthStr = $m[2];
        $yearStr = !empty($m[3]) ? intval($m[3]) : date('Y');
        $parsedT = strtotime("$day $monthStr $yearStr");
        if ($parsedT) {
            $targetDateStr = date('Y-m-d', $parsedT);
            $dateLabelStr = date('d M Y', $parsedT);
        }
    } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $qLower, $m)) {
        $targetDateStr = $m[0];
        $dateLabelStr = date('d M Y', strtotime($m[0]));
    } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $qLower, $m)) {
        $day = intval($m[1]);
        $month = intval($m[2]);
        $year = intval($m[3]);
        $parsedT = strtotime("$year-$month-$day");
        if ($parsedT) {
            $targetDateStr = date('Y-m-d', $parsedT);
            $dateLabelStr = date('d M Y', $parsedT);
        }
    }
}

// -------------------------------------------------------------
// SAFE & ISOLATED LIVE DATABASE METRICS FETCHING
// -------------------------------------------------------------

// Branch Details
$currentBranchName = 'Main Branch';
try {
    $branchQ = $conn->query("SELECT name FROM branches WHERE id = $currentBranchId");
    if ($branchQ && $branchQ->num_rows > 0) {
        $currentBranchName = $branchQ->fetch_assoc()['name'];
    }
} catch (\Throwable $e) {}

// 1. OPENING CASH & SHIFT DETAILS (ISOLATED QUERY BLOCK)
$openCashVal = 0;
$shiftStatusStr = 'CLOSED';
$shiftOpenedAt = '';
try {
    $sq1 = $conn->query("SELECT opening_cash, status, opened_at FROM shifts WHERE (status = 'open' OR is_cleared = 0 OR is_cleared IS NULL) ORDER BY id DESC LIMIT 1");
    if ($sq1 && $sq1->num_rows > 0) {
        $r = $sq1->fetch_assoc();
        $openCashVal = floatval($r['opening_cash']);
        $shiftStatusStr = !empty($r['status']) ? strtoupper($r['status']) : 'OPEN';
        $shiftOpenedAt = $r['opened_at'] ?? '';
    }

    if ($openCashVal <= 0) {
        $sq2 = $conn->query("SELECT opening_cash, status, opened_at FROM shifts WHERE opening_cash > 0 ORDER BY id DESC LIMIT 1");
        if ($sq2 && $sq2->num_rows > 0) {
            $r = $sq2->fetch_assoc();
            $openCashVal = floatval($r['opening_cash']);
            $shiftStatusStr = !empty($r['status']) ? strtoupper($r['status']) : 'OPEN';
            $shiftOpenedAt = $r['opened_at'] ?? '';
        }
    }

    if ($openCashVal <= 0) {
        $sq3 = $conn->query("SELECT MAX(opening_cash) as max_cash FROM shifts");
        if ($sq3 && $sq3->num_rows > 0) {
            $mC = floatval($sq3->fetch_assoc()['max_cash'] ?? 0);
            if ($mC > 0) {
                $openCashVal = $mC;
                $shiftStatusStr = 'OPEN';
            }
        }
    }

    if ($openCashVal <= 0) {
        $sq4 = $conn->query("SELECT opening_cash, status, opened_at FROM shifts ORDER BY id DESC LIMIT 1");
        if ($sq4 && $sq4->num_rows > 0) {
            $r = $sq4->fetch_assoc();
            $openCashVal = floatval($r['opening_cash']);
            $shiftStatusStr = !empty($r['status']) ? strtoupper($r['status']) : 'CLOSED';
            $shiftOpenedAt = $r['opened_at'] ?? '';
        }
    }
} catch (\Throwable $e) {}

// 2. Active Session / Specific Date Sales & Revenue
$todayOrders = 0;
$todaySales = 0;
try {
    if ($isMonthQuery && !empty($targetMonthStr)) {
        $salesWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
    } elseif (!empty($targetDateStr)) {
        $salesWhere = "WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
    } else {
        $salesWhere = "WHERE DATE(created_at) = CURDATE() AND tenant_id = $tenantId";
    }
    
    $salesQ = $conn->query("SELECT COUNT(id) as cnt, SUM(total_amount) as total FROM sales $salesWhere");
    if ($salesQ && $salesQ->num_rows > 0) {
        $sd = $salesQ->fetch_assoc();
        $todayOrders = intval($sd['cnt'] ?? 0);
        $todaySales = floatval($sd['total'] ?? 0);
    }
    
    // Fallback if empty targetDateStr/targetMonthStr and todayOrders is 0: check CURDATE()
    if ($todayOrders == 0 && empty($targetDateStr) && empty($targetMonthStr)) {
        $curDateQ = $conn->query("SELECT COUNT(id) as cnt, SUM(total_amount) as total FROM sales WHERE DATE(created_at) = CURDATE() AND tenant_id = $tenantId");
        if ($curDateQ && $curDateQ->num_rows > 0) {
            $cd = $curDateQ->fetch_assoc();
            $cCnt = intval($cd['cnt'] ?? 0);
            if ($cCnt > 0) {
                $todayOrders = $cCnt;
                $todaySales = floatval($cd['total'] ?? 0);
            }
        }
    }
} catch (\Throwable $e) {}

// COGS & Profit
$todayCogs = 0;
try {
    if ($isMonthQuery && !empty($targetMonthStr)) {
        $cogsWhere = "WHERE DATE_FORMAT(s.created_at, '%Y-%m') = '$targetMonthStr' AND s.tenant_id = $tenantId";
    } elseif (!empty($targetDateStr)) {
        $cogsWhere = "WHERE DATE(s.created_at) = '$targetDateStr' AND s.tenant_id = $tenantId";
    } else {
        $cogsWhere = "WHERE DATE(s.created_at) = CURDATE() AND s.tenant_id = $tenantId";
    }
    
    $cogsQ = $conn->query("SELECT SUM(si.quantity * LEAST(IF(si.cost_price > 0, si.cost_price, IFNULL(p.purchase_price, 0)), si.price)) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id LEFT JOIN products p ON si.product_id = p.id $cogsWhere");
    if ($cogsQ && $cogsQ->num_rows > 0) {
        $todayCogs = floatval($cogsQ->fetch_assoc()['cogs'] ?? 0);
    }
} catch (\Throwable $e) {}
$todayProfit = $todaySales - $todayCogs;

// Lifetime Sales Metrics
$lifetimeOrders = 0;
$lifetimeSales = 0;
try {
    $lifeSalesQ = $conn->query("SELECT COUNT(id) as cnt, SUM(total_amount) as total FROM sales WHERE tenant_id = $tenantId");
    if ($lifeSalesQ && $lifeSalesQ->num_rows > 0) {
        $ld = $lifeSalesQ->fetch_assoc();
        $lifetimeOrders = intval($ld['cnt'] ?? 0);
        $lifetimeSales = floatval($ld['total'] ?? 0);
    }
} catch (\Throwable $e) {}

// Payment Method Breakdown
$paymentBreakdown = [];
try {
    if ($isMonthQuery && !empty($targetMonthStr)) {
        $payWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
    } elseif (!empty($targetDateStr)) {
        $payWhere = "WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
    } else {
        $payWhere = "WHERE DATE(created_at) = CURDATE() AND tenant_id = $tenantId";
    }
    
    $payMethodQ = $conn->query("SELECT payment_method, SUM(total_amount) as total, COUNT(id) as cnt FROM sales $payWhere GROUP BY payment_method");
    if ($payMethodQ && $payMethodQ->num_rows > 0) {
        while ($pr = $payMethodQ->fetch_assoc()) {
            $paymentBreakdown[] = $pr;
        }
    }
} catch (\Throwable $e) {}

// Expenses & Operating Spending
$todayExpenses = 0;
$todayExpenseCount = 0;
try {
    if ($isMonthQuery && !empty($targetMonthStr)) {
        $expWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
    } elseif (!empty($targetDateStr)) {
        $expWhere = "WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
    } else {
        $expWhere = "WHERE DATE(created_at) = CURDATE() AND tenant_id = $tenantId";
    }
    
    $expQ = $conn->query("SELECT SUM(amount) as total, COUNT(id) as cnt FROM expenses $expWhere");
    if ($expQ && $expQ->num_rows > 0) {
        $ed = $expQ->fetch_assoc();
        $todayExpenses = floatval($ed['total'] ?? 0);
        $todayExpenseCount = intval($ed['cnt'] ?? 0);
    }
} catch (\Throwable $e) {}

// PURCHASES & SUPPLIER PAYMENTS
$dateFilteredPurchasesPaid = 0;
$dateFilteredPurchasesInvoiceTotal = 0;
$lifetimePurchasesPaid = 0;
$recentPurchasesList = [];
try {
    if ($isMonthQuery && !empty($targetMonthStr)) {
        $purWhere = " WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId ";
        $purJoinWhere = " WHERE DATE_FORMAT(p.created_at, '%Y-%m') = '$targetMonthStr' AND p.tenant_id = $tenantId ";
    } elseif (!empty($targetDateStr)) {
        $purWhere = " WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId ";
        $purJoinWhere = " WHERE DATE(p.created_at) = '$targetDateStr' AND p.tenant_id = $tenantId ";
    } else {
        $purWhere = " WHERE DATE(created_at) = CURDATE() AND tenant_id = $tenantId ";
        $purJoinWhere = " WHERE DATE(p.created_at) = CURDATE() AND p.tenant_id = $tenantId ";
    }

    $purQ = $conn->query("SELECT SUM(total_amount) as tot, SUM(amount_paid) as paid FROM purchases $purWhere");
    if ($purQ && $purQ->num_rows > 0) {
        $pData = $purQ->fetch_assoc();
        $dateFilteredPurchasesInvoiceTotal = floatval($pData['tot'] ?? 0);
        $dateFilteredPurchasesPaid = floatval($pData['paid'] ?? 0);
    }

    $lpurQ = $conn->query("SELECT SUM(amount_paid) as paid, SUM(total_amount) as tot FROM purchases");
    if ($lpurQ && $lpurQ->num_rows > 0) {
        $lpData = $lpurQ->fetch_assoc();
        $lifetimePurchasesPaid = floatval($lpData['paid'] ?? 0);
    }

    $rpQ = $conn->query("SELECT p.id, p.total_amount, p.amount_paid, p.status, p.created_at, s.name as supplier_name,
                                (SELECT GROUP_CONCAT(CONCAT(pi.quantity, ' ', IFNULL(pi.unit,''), ' ', pr.name) SEPARATOR ', ') 
                                 FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = p.id) as items_summary
                         FROM purchases p 
                         LEFT JOIN suppliers s ON p.supplier_id = s.id 
                         $purJoinWhere
                         ORDER BY p.id DESC LIMIT 15");
    if ($rpQ && $rpQ->num_rows > 0) {
        while ($rRow = $rpQ->fetch_assoc()) {
            $recentPurchasesList[] = $rRow;
        }
    }
} catch (\Throwable $e) {}

$todayPurchases = $dateFilteredPurchasesPaid;

// -------------------------------------------------------------
// DYNAMIC RECEIPT ORDER # & CUSTOMER SALE LOOKUP ENGINE
// -------------------------------------------------------------
$searchedCustomerSales = [];
$searchedSaleById = null;
$searchedCustomerNameKeyword = "";

try {
    // 1. Check if user typed a Receipt Order # or Sale ID (e.g. "000255", "Order # 000255", "255", "Sale 255")
    if (preg_match('/(?:order\s*#?|sale\s*#?|bill\s*#?|receipt\s*#?|id\s*#?|^|\s)0*(\d{1,7})(?:\s|$)/i', $qLower, $mId)) {
        $saleIdInput = intval($mId[1]);
        if ($saleIdInput > 0) {
            $sIdQ = $conn->query("
                SELECT s.id, s.total_amount, s.discount, s.tax_amount, s.payment_method, s.amount_received, s.change_returned, s.created_at, s.taken_by,
                       COALESCE(c.name, s.customer_name, 'Walk-in Customer') as customer_name,
                       (SELECT GROUP_CONCAT(CONCAT(si.quantity, ' ', IFNULL(p.unit,''), ' ', p.name, ' @ $', FORMAT(si.price, 2)) SEPARATOR '\n  • ') 
                        FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = s.id) as items_detailed
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                WHERE s.id = $saleIdInput LIMIT 1
            ");
            if ($sIdQ && $sIdQ->num_rows > 0) {
                $searchedSaleById = $sIdQ->fetch_assoc();
            }
        }
    }

    // 2. Check if user asked about a customer name or walk-in sale
    $isGeneralTotalOrMetricsQuery = (
        (strpos($qLower, 'total sale') !== false || strpos($qLower, 'total sales') !== false || strpos($qLower, 'total order') !== false || strpos($qLower, 'total orders') !== false || strpos($qLower, 'today sale') !== false || strpos($qLower, 'aaj ki sale') !== false || strpos($qLower, 'sales kitni') !== false || strpos($qLower, 'order kitne') !== false || strpos($qLower, 'opening cash') !== false || strpos($qLower, 'inventory') !== false) &&
        (strpos($qLower, 'customer') === false && strpos($qLower, 'custmer') === false && strpos($qLower, 'khata') === false && strpos($qLower, 'client') === false && strpos($qLower, 'naam') === false && strpos($qLower, 'name') === false)
    );

    if (empty($searchedSaleById) && !$isGeneralTotalOrMetricsQuery) {
        $custStopWords = [
            'aj', 'aaj', 'today', 'kal', 'yesterday', 'koi', 'name', 'naam', 'ka', 'ki', 'ke', 'kay', 
            'customer', 'custmer', 'products', 'product', 'buy', 'kar', 'gaya', 'gayi', 'gaye', 
            'hai', 'hain', 'ho', 'walk', 'in', 'out', 'khatma', 'khata', 'sale', 'sales', 'main', 
            'mein', 'say', 'se', 'hui', 'huwi', 'hu', 'nahi', 'aur', 'bhi', 'correct', 'answer', 
            'de', 'pocho', 'check', 'batao', 'bataye', 'bataen', 'batoo', 'bato', 'bataoo', 'batayen', 
            'is', 'did', 'bill', 'receipt', 'order', 'orders', 'total', 'kitni', 'kitne', 'kitna', 
            'show', 'tell', 'me', 'give', 'detail', 'details', 'summary', 'report',
            'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december',
            'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec'
        ];
        
        // Remove numbers if they are date digits (e.g., "22", "15")
        $qCleanForCust = preg_replace('/\b\d{1,4}\b/', ' ', $qLower);
        $qCustClean = str_replace($custStopWords, ' ', $qCleanForCust);
        $custTokens = array_filter(explode(' ', trim($qCustClean)));

        if ($isMonthQuery && !empty($targetMonthStr)) {
            $dateCustWhere = " AND DATE_FORMAT(s.created_at, '%Y-%m') = '$targetMonthStr' ";
        } elseif (!empty($targetDateStr)) {
            $dateCustWhere = " AND DATE(s.created_at) = '$targetDateStr' ";
        } else {
            $dateCustWhere = " ";
        }

        foreach ($custTokens as $cTk) {
            $cTk = trim($cTk);
            if (strlen($cTk) >= 3 && strpos($qLower, 'supplier') === false && strpos($qLower, 'product') === false) {
                $likeCust = "%" . $cTk . "%";

                // Search sales table by customer_name
                $cSq = $conn->query("SELECT s.id, s.customer_name, s.total_amount, s.payment_method, s.created_at,
                                            (SELECT GROUP_CONCAT(CONCAT(si.quantity, ' ', pr.name, ' @ $', FORMAT(si.price, 2)) SEPARATOR '\n      • ') 
                                             FROM sale_items si JOIN products pr ON si.product_id = pr.id WHERE si.sale_id = s.id) as items_summary 
                                     FROM sales s 
                                     WHERE (LOWER(s.customer_name) LIKE '$likeCust' OR REPLACE(LOWER(s.customer_name), ' ', '') LIKE '%$cTk%') AND s.tenant_id = $tenantId $dateCustWhere 
                                     ORDER BY s.id DESC LIMIT 10");
                if ($cSq && $cSq->num_rows > 0) {
                    $searchedCustomerNameKeyword = $cTk;
                    while ($cRow = $cSq->fetch_assoc()) {
                        $searchedCustomerSales[$cRow['id']] = $cRow;
                    }
                }

                // Also check customers table for registered customer ledger sales
                if (empty($searchedCustomerSales)) {
                    $cTableQ = $conn->query("SELECT id, name FROM customers WHERE (LOWER(name) LIKE '$likeCust' OR REPLACE(LOWER(name), ' ', '') LIKE '%$cTk%') AND tenant_id = $tenantId");
                    if ($cTableQ && $cTableQ->num_rows > 0) {
                        while ($custRow = $cTableQ->fetch_assoc()) {
                            $cId = intval($custRow['id']);
                            $cSq2 = $conn->query("SELECT s.id, s.customer_name, s.total_amount, s.payment_method, s.created_at,
                                                        (SELECT GROUP_CONCAT(CONCAT(si.quantity, ' ', pr.name, ' @ $', FORMAT(si.price, 2)) SEPARATOR '\n      • ') 
                                                         FROM sale_items si JOIN products pr ON si.product_id = pr.id WHERE si.sale_id = s.id) as items_summary 
                                                 FROM sales s 
                                                 WHERE s.customer_id = $cId AND s.tenant_id = $tenantId $dateCustWhere 
                                                 ORDER BY s.id DESC LIMIT 10");
                            if ($cSq2 && $cSq2->num_rows > 0) {
                                $searchedCustomerNameKeyword = $cTk;
                                while ($cRow2 = $cSq2->fetch_assoc()) {
                                    $searchedCustomerSales[$cRow2['id']] = $cRow2;
                                }
                            }
                        }
                    }
                }

                // If user explicitly mentioned customer / name / client / khata, but 0 matches were found in DB
                if (empty($searchedCustomerSales) && (strpos($qLower, 'customer') !== false || strpos($qLower, 'custmer') !== false || strpos($qLower, 'khata') !== false || strpos($qLower, 'client') !== false || strpos($qLower, 'naam') !== false || strpos($qLower, 'name') !== false)) {
                    $searchedCustomerNameKeyword = $cTk;
                }
            }
        }
    }
} catch (\Throwable $e) {}


// 3. Products & Full Inventory Catalog
$totalProducts = 0;
$inventoryCostValue = 0;
$inventorySellValue = 0;
$allProductCatalog = [];

try {
    $prodCountQ = $conn->query("SELECT COUNT(id) as total FROM products");
    if ($prodCountQ && $prodCountQ->num_rows > 0) {
        $totalProducts = intval($prodCountQ->fetch_assoc()['total'] ?? 0);
    }

    $sellValQ = $conn->query("SELECT SUM(stock * price) as sell_val FROM products");
    if ($sellValQ && $sellValQ->num_rows > 0) {
        $inventorySellValue = floatval($sellValQ->fetch_assoc()['sell_val'] ?? 0);
    }

    $costValQ = $conn->query("SELECT SUM(stock * purchase_price) as cost_val FROM products");
    if ($costValQ && $costValQ->num_rows > 0) {
        $inventoryCostValue = floatval($costValQ->fetch_assoc()['cost_val'] ?? 0);
    } else {
        $costValQ2 = $conn->query("SELECT SUM(stock * cost_price) as cost_val FROM products");
        if ($costValQ2 && $costValQ2->num_rows > 0) {
            $inventoryCostValue = floatval($costValQ2->fetch_assoc()['cost_val'] ?? 0);
        }
    }

    $catQ = $conn->query("SELECT name, price, stock, unit, category FROM products ORDER BY stock DESC LIMIT 30");
    if ($catQ && $catQ->num_rows > 0) {
        while ($cRow = $catQ->fetch_assoc()) {
            $allProductCatalog[] = $cRow;
        }
    }
} catch (\Throwable $e) {}

// Low Stock & Out of Stock
$lowStockItems = [];
$lowStockCount = 0;
$outOfStockCount = 0;
try {
    $lowStockQ = $conn->query("SELECT id, name, stock, price FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 10");
    if ($lowStockQ) {
        while ($r = $lowStockQ->fetch_assoc()) $lowStockItems[] = $r;
    }
    $lowStockCount = count($lowStockItems);

    $outOfStockQ = $conn->query("SELECT COUNT(id) as c FROM products WHERE stock <= 0");
    if ($outOfStockQ && $outOfStockQ->num_rows > 0) {
        $outOfStockCount = intval($outOfStockQ->fetch_assoc()['c']);
    }
} catch (\Throwable $e) {}

// SPECIFIC PRODUCT SEARCH LOGIC
$searchedProducts = [];
try {
    $allProdQ = $conn->query("SELECT name, price, stock, unit, category, barcode FROM products");
    if ($allProdQ && $allProdQ->num_rows > 0) {
        while ($pRow = $allProdQ->fetch_assoc()) {
            $pNameLower = strtolower($pRow['name']);
            if (!empty($pNameLower) && strlen($pNameLower) >= 3 && strpos($qLower, $pNameLower) !== false) {
                $searchedProducts[$pRow['name']] = $pRow;
            }
        }
    }

    if (empty($searchedProducts)) {
        $stopWords = ['ki', 'ka', 'ke', 'kay', 'ko', 'se', 'bary', 'bare', 'bari', 'kya', 'hai', 'hain', 'ho', 'kitna', 'kitni', 'kitne', 'bato', 'batao', 'bataen', 'bataye', 'batayein', 'price', 'rate', 'cost', 'stock', 'stok', 'parah', 'para', 'detail', 'details', 'baare', 'main', 'mein', 'show', 'tell', 'me', 'the', 'of', 'in', 'is', 'what', 'how', 'much', 'inverty', 'inventery', 'inventry', 'inventy', 'inventory', 'sari', 'saari', 'poori', 'puri', 'all', 'full', 'complete', 'list', 'sab', 'har', 'cheez', 'per', 'par', 'total', 'product', 'products', 'supplier', 'suppliers', 'vendor', 'purchase', 'payment', 'kal', 'aaj', 'aj', 'today', 'yesterday'];
        $qClean = str_replace($stopWords, '', $qLower);
        $tokens = array_filter(explode(' ', trim($qClean)));
        foreach ($tokens as $tk) {
            $tk = trim($tk);
            if (strlen($tk) >= 3) {
                $stmtP = $conn->prepare("SELECT name, price, stock, unit, category, barcode FROM products WHERE LOWER(name) LIKE ? OR barcode LIKE ? LIMIT 5");
                $likeT = "%" . $tk . "%";
                $stmtP->bind_param("ss", $likeT, $likeT);
                $stmtP->execute();
                $resP = $stmtP->get_result();
                if ($resP) {
                    while ($prRow = $resP->fetch_assoc()) {
                        $searchedProducts[$prRow['name']] = $prRow;
                    }
                }
                $stmtP->close();
            }
        }
    }
} catch (\Throwable $e) {}

// Damaged Stock & Financial Loss
$totalDamageCount = 0;
$totalDamageLoss = 0;
$totalDamageQty = 0;
try {
    $damageQ = $conn->query("SELECT COUNT(id) as cnt, SUM(quantity) as qty, SUM(loss_amount) as loss FROM damaged_stock");
    if ($damageQ && $damageQ->num_rows > 0) {
        $dd = $damageQ->fetch_assoc();
        $totalDamageCount = intval($dd['cnt'] ?? 0);
        $totalDamageQty = floatval($dd['qty'] ?? 0);
        $totalDamageLoss = floatval($dd['loss'] ?? 0);
    }
} catch (\Throwable $e) {}

// Top 5 Selling Products
$topSellers = [];
try {
    $topSellerQ = $conn->query("SELECT p.name, SUM(si.quantity) as total_qty, SUM(si.quantity * si.price) as total_rev FROM sale_items si JOIN products p ON si.product_id = p.id GROUP BY p.id ORDER BY total_qty DESC LIMIT 5");
    if ($topSellerQ && $topSellerQ->num_rows > 0) {
        while ($tr = $topSellerQ->fetch_assoc()) {
            $topSellers[] = $tr;
        }
    }
} catch (\Throwable $e) {}

// Customer & Udhaar (Khata) Metrics
$udhaarCount = 0;
$udhaarTotal = 0;
$topUdhaarCust = null;
$totalCustomers = 0;

$todayUdhaarTotal = 0;
$todayUdhaarCount = 0;
$todayUdhaarCustomersList = [];

try {
    // 1. Overall customer outstanding ledger balance
    $udhaarQ = $conn->query("SELECT COUNT(id) as cnt, SUM(outstanding_balance) as total FROM customers WHERE outstanding_balance > 0");
    if ($udhaarQ && $udhaarQ->num_rows > 0) {
        $ud = $udhaarQ->fetch_assoc();
        $udhaarCount = intval($ud['cnt'] ?? 0);
        $udhaarTotal = floatval($ud['total'] ?? 0);
    }

    $topUdhaarQ = $conn->query("SELECT name, outstanding_balance FROM customers WHERE outstanding_balance > 0 ORDER BY outstanding_balance DESC LIMIT 1");
    if ($topUdhaarQ && $topUdhaarQ->num_rows > 0) {
        $topUdhaarCust = $topUdhaarQ->fetch_assoc();
    }

    $totalCustQ = $conn->query("SELECT COUNT(id) as c FROM customers");
    if ($totalCustQ && $totalCustQ->num_rows > 0) {
        $totalCustomers = intval($totalCustQ->fetch_assoc()['c']);
    }

    // 2. Today's / Date-Filtered New Udhaar (Credit Sales) per customer
    $udWhereDate = !empty($targetDateStr) ? "DATE(s.created_at) = '$targetDateStr'" : "(s.is_cleared = 0 OR DATE(s.created_at) = CURDATE())";

    $tUdQ = $conn->query("SELECT COUNT(s.id) as cnt, SUM(s.total_amount) as total 
                           FROM sales s 
                           WHERE (LOWER(s.payment_method) LIKE '%credit%' OR LOWER(s.payment_method) LIKE '%khata%' OR LOWER(s.payment_method) LIKE '%account%' OR LOWER(s.payment_method) LIKE '%udhaar%') 
                             AND $udWhereDate");
    if ($tUdQ && $tUdQ->num_rows > 0) {
        $tUdData = $tUdQ->fetch_assoc();
        $todayUdhaarCount = intval($tUdData['cnt'] ?? 0);
        $todayUdhaarTotal = floatval($tUdData['total'] ?? 0);
    }

    $cUdQ = $conn->query("
        SELECT s.id, s.total_amount, s.created_at, s.payment_method,
               COALESCE(c.name, s.customer_name, 'Walk-in Customer') as customer_name,
               (SELECT GROUP_CONCAT(CONCAT(si.quantity, ' ', pr.name) SEPARATOR ', ') 
                FROM sale_items si JOIN products pr ON si.product_id = pr.id WHERE si.sale_id = s.id) as items_summary
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE (LOWER(s.payment_method) LIKE '%credit%' OR LOWER(s.payment_method) LIKE '%khata%' OR LOWER(s.payment_method) LIKE '%account%' OR LOWER(s.payment_method) LIKE '%udhaar%')
          AND $udWhereDate
        ORDER BY s.id DESC
    ");
    if ($cUdQ && $cUdQ->num_rows > 0) {
        while ($cRow = $cUdQ->fetch_assoc()) {
            $todayUdhaarCustomersList[] = $cRow;
        }
    }
} catch (\Throwable $e) {}

// Supplier & Payables Detailed Metrics
$suppPayableTotal = 0;
$totalSuppliers = 0;
$detailedSuppliersList = [];
try {
    $suppQ = $conn->query("SELECT COUNT(id) as cnt, SUM(outstanding_payable) as total FROM suppliers WHERE outstanding_payable > 0");
    if ($suppQ && $suppQ->num_rows > 0) {
        $sData = $suppQ->fetch_assoc();
        $suppPayableTotal = floatval($sData['total'] ?? 0);
    }

    $totalSuppliersQ = $conn->query("SELECT COUNT(id) as c FROM suppliers");
    if ($totalSuppliersQ && $totalSuppliersQ->num_rows > 0) {
        $totalSuppliers = intval($totalSuppliersQ->fetch_assoc()['c']);
    }

    $dSuppQ = $conn->query("SELECT name, contact_person, phone, outstanding_payable FROM suppliers ORDER BY outstanding_payable DESC LIMIT 10");
    if ($dSuppQ && $dSuppQ->num_rows > 0) {
        while ($sRow = $dSuppQ->fetch_assoc()) {
            $detailedSuppliersList[] = $sRow;
        }
    }
} catch (\Throwable $e) {}

// Cash Drawer Calculation
$netCashSales = 0;
try {
    $cashSalesQ = $conn->query("SELECT SUM(amount_received - change_returned) as c FROM sales WHERE payment_method = 'cash' AND is_cleared = 0");
    if ($cashSalesQ && $cashSalesQ->num_rows > 0) {
        $netCashSales = floatval($cashSalesQ->fetch_assoc()['c'] ?? 0);
    }
} catch (\Throwable $e) {}

$expectedDrawerCash = $openCashVal + $netCashSales - $todayExpenses;

// Staff Users Count
$totalStaff = 0;
try {
    $staffQ = $conn->query("SELECT COUNT(id) as c FROM users");
    if ($staffQ && $staffQ->num_rows > 0) {
        $totalStaff = intval($staffQ->fetch_assoc()['c']);
    }
} catch (\Throwable $e) {}


// -------------------------------------------------------------
// FUZZY INTENT MATCHING MATRIX
// -------------------------------------------------------------
$reply = "";

// 1. OPENING CASH / SHIFT START CASH / REGISTER CASH (FUZZY MATCHING)
$isOpeningCashQuery = (
    strpos($qLower, 'open') !== false ||
    strpos($qLower, 'opei') !== false ||
    strpos($qLower, 'opn') !== false ||
    strpos($qLower, 'start cash') !== false ||
    strpos($qLower, 'starting cash') !== false ||
    strpos($qLower, 'register') !== false ||
    strpos($qLower, 'drawer') !== false ||
    strpos($qLower, '1000') !== false ||
    strpos($qLower, '500') !== false ||
    (strpos($qLower, 'cash') !== false && (
        strpos($qLower, 'shift') !== false ||
        strpos($qLower, 'kitna') !== false ||
        strpos($qLower, 'pehle') !== false ||
        strpos($qLower, 'aaj') !== false ||
        strpos($qLower, 'start') !== false ||
        strpos($qLower, 'paisa') !== false
    ))
);

// END OF DAY / Z-REPORT / FINANCIAL STATEMENT INTENT
$isEodReportQuery = (
    strpos($qLower, 'end of day') !== false ||
    strpos($qLower, 'eod') !== false ||
    strpos($qLower, 'z report') !== false ||
    strpos($qLower, 'z-report') !== false ||
    strpos($qLower, 'zreport') !== false ||
    strpos($qLower, 'statement') !== false ||
    strpos($qLower, 'daily report') !== false ||
    strpos($qLower, 'day report') !== false ||
    strpos($qLower, 'daily summary') !== false ||
    strpos($qLower, 'closing report') !== false ||
    strpos($qLower, 'din ki report') !== false ||
    (strpos($qLower, 'report') !== false && (strpos($qLower, 'day') !== false || strpos($qLower, 'aaj') !== false || strpos($qLower, 'aj') !== false || strpos($qLower, 'date') !== false || strpos($qLower, 'month') !== false))
);

// 2. TOP SELLING / BESTSELLER PRODUCTS
$isTopSellingQuery = (
    strpos($qLower, 'top') !== false ||
    strpos($qLower, 'best') !== false ||
    strpos($qLower, 'popular') !== false ||
    strpos($qLower, 'bestseller') !== false ||
    strpos($qLower, 'zyada bik') !== false ||
    strpos($qLower, 'ziada bik') !== false ||
    strpos($qLower, 'sab se zyada') !== false ||
    strpos($qLower, 'sab se ziada') !== false ||
    strpos($qLower, 'most sold') !== false ||
    strpos($qLower, 'bikne wal') !== false ||
    strpos($qLower, 'biknay wal') !== false
);

// 3. GENERAL INVENTORY OVERVIEW INTENT
$isGeneralInventoryOverview = (
    strpos($qLower, 'inverty') !== false ||
    strpos($qLower, 'inventery') !== false ||
    strpos($qLower, 'inventry') !== false ||
    strpos($qLower, 'inventy') !== false ||
    strpos($qLower, 'invetory') !== false ||
    strpos($qLower, 'invintory') !== false ||
    strpos($qLower, 'inventory') !== false ||
    strpos($qLower, 'sari detail') !== false ||
    strpos($qLower, 'saari detail') !== false ||
    strpos($qLower, 'all products') !== false ||
    strpos($qLower, 'full stock') !== false ||
    strpos($qLower, 'total product') !== false ||
    strpos($qLower, 'total products') !== false ||
    strpos($qLower, 'products bato') !== false ||
    strpos($qLower, 'product bato') !== false ||
    strpos($qLower, 'catalog') !== false
);

// 4. SUPPLIER & PURCHASE PAYMENTS INTENT
$isSupplierQuery = (
    strpos($qLower, 'supplier') !== false ||
    strpos($qLower, 'suppliers') !== false ||
    strpos($qLower, 'suppler') !== false ||
    strpos($qLower, 'suplier') !== false ||
    strpos($qLower, 'vendor') !== false ||
    strpos($qLower, 'vendors') !== false ||
    strpos($qLower, 'payable') !== false ||
    strpos($qLower, 'payables') !== false ||
    strpos($qLower, 'purchase') !== false ||
    strpos($qLower, 'purchases') !== false ||
    strpos($qLower, 'purches') !== false ||
    strpos($qLower, 'khareedi') !== false ||
    (strpos($qLower, 'stock') !== false && strpos($qLower, 'payment') !== false) ||
    (strpos($qLower, 'stock') !== false && strpos($qLower, 'name') !== false) ||
    (strpos($qLower, 'kon sa') !== false && strpos($qLower, 'stock') !== false)
);

// EXECUTION ROUTING

// A. SALE BY ID / ORDER # / RECEIPT NUMBER SEARCH
if ($searchedSaleById) {
    $sItem = $searchedSaleById;
    $orderCodeFormatted = sprintf('%06d', $sItem['id']);
    $cName = !empty($sItem['customer_name']) ? $sItem['customer_name'] : 'Walk-in Customer';
    $totAmt = floatval($sItem['total_amount']);
    $payM = ucfirst($sItem['payment_method'] ?? 'cash');
    $itemsStr = !empty($sItem['items_detailed']) ? $sItem['items_detailed'] : 'Store inventory item checkout';
    $dtStr = !empty($sItem['created_at']) ? date('d-m-Y \a\t h:i A (H:i)', strtotime($sItem['created_at'])) : '';
    $cashierStr = !empty($sItem['taken_by']) ? $sItem['taken_by'] : 'Admin / POS';

    $reply = "🧾 **Bill Receipt & Order Details (Order # " . $orderCodeFormatted . "):**\n\n";
    $reply .= "• **Order / Sale ID:** **Order # " . $orderCodeFormatted . "** (ID: " . $sItem['id'] . ")\n";
    $reply .= "• **Customer Name:** **" . htmlspecialchars($cName) . "**\n";
    $reply .= "• **Cashier / Staff:** **" . htmlspecialchars($cashierStr) . "**\n";
    $reply .= "• 🛍️ **Purchased Items & Prices:**\n  • " . htmlspecialchars($itemsStr) . "\n\n";
    $reply .= "• **Total Amount Due:** **$" . number_format($totAmt, 2) . "**\n";
    $reply .= "• **Payment Method:** " . $payM . "\n";
    if ($dtStr) {
        $reply .= "• **Date & Time:** " . $dtStr . "\n";
    }
}
// B. SEARCH BY CUSTOMER NAME INTENT
elseif (!empty($searchedCustomerSales)) {
    $dateH = !empty($dateLabelStr) ? $dateLabelStr : "Today / Active Session";
    $reply = "✅ **Customer Sale Record Found:**\n📍 **Date Filter:** **" . $dateH . "**\n\n";
    $sCount = 1;
    foreach ($searchedCustomerSales as $sItem) {
        $cName = !empty($sItem['customer_name']) ? $sItem['customer_name'] : 'Walk-in Customer';
        $totAmt = floatval($sItem['total_amount']);
        $payM = ucfirst($sItem['payment_method'] ?? 'cash');
        $itemsStr = !empty($sItem['items_summary']) ? $sItem['items_summary'] : 'Store items checkout';
        $dtStr = !empty($sItem['created_at']) ? date('d M Y, h:i A', strtotime($sItem['created_at'])) : '';
        $orderCode = sprintf('%06d', $sItem['id']);

        $reply .= "**" . $sCount . ". Order # " . $orderCode . "** — Customer: **" . htmlspecialchars($cName) . "**\n";
        $reply .= "   - 🛍️ **Purchased Items:** `" . htmlspecialchars($itemsStr) . "`\n";
        $reply .= "   - **Total Bill:** **$" . number_format($totAmt, 2) . "**\n";
        $reply .= "   - **Payment Method:** " . $payM . "\n";
        if ($dtStr) {
            $reply .= "   - **Time:** " . $dtStr . "\n";
        }
        $reply .= "\n";
        $sCount++;
    }
}
// C. SEARCH BY CUSTOMER NAME WITH NO SALE RECORD FOUND
elseif (!empty($searchedCustomerNameKeyword) && strpos($qLower, 'sale') !== false) {
    $dateH = !empty($dateLabelStr) ? $dateLabelStr : "Today / Active Session";
    $reply = "ℹ️ **Customer Sale Status Check:**\n📍 **Date Filter:** **" . $dateH . "**\n\n";
    $reply .= "⚠️ **" . $dateH . "** ke record mein **\"" . htmlspecialchars($searchedCustomerNameKeyword) . "\"** ke name se koi sale entry record nahi hui.\n\n";
    $reply .= "💡 *Aap Receipt Code (e.g. \"000255\") ya Sale ID (e.g. \"Sale #255\") pooch sakte hain.*";
}
// D. SUPPLIER QUERIES
elseif ($isSupplierQuery) {
    $filterHeader = !empty($dateLabelStr) ? "📍 **Filter Date:** **" . $dateLabelStr . "**\n\n" : "";
    $reply = "🚚 **Supplier Payables & Stock Purchase Payment Analytics:**\n" . $filterHeader;
    
    if (!empty($targetDateStr)) {
        $reply .= "• **Purchases Paid on " . $dateLabelStr . ":** **$" . number_format($dateFilteredPurchasesPaid, 2) . "**\n";
        $reply .= "• **Total Purchase Invoices Value:** **$" . number_format($dateFilteredPurchasesInvoiceTotal, 2) . "**\n";
    } else {
        $reply .= "• **Active Shift Cash Paid to Suppliers:** **$" . number_format($dateFilteredPurchasesPaid, 2) . "**\n";
        $reply .= "• Active Shift Purchase Invoices Total: **$" . number_format($dateFilteredPurchasesInvoiceTotal, 2) . "**\n";
    }
    
    $reply .= "• **Total Outstanding Supplier Payables:** **$" . number_format($suppPayableTotal, 2) . "**\n";
    $reply .= "• Lifetime Total Purchases Paid: **$" . number_format($lifetimePurchasesPaid, 2) . "**\n\n";

    if (!empty($recentPurchasesList)) {
        $reply .= "📦 **Stock Purchase Invoices & Items (" . (!empty($dateLabelStr) ? $dateLabelStr : "Recent") . "):**\n";
        $pCount = 1;
        foreach ($recentPurchasesList as $pItem) {
            $sName = !empty($pItem['supplier_name']) ? $pItem['supplier_name'] : 'General Supplier';
            $totAmt = floatval($pItem['total_amount']);
            $paidAmt = floatval($pItem['amount_paid']);
            $pendingBal = $totAmt - $paidAmt;
            $dtStr = !empty($pItem['created_at']) ? date('d M Y, h:i A', strtotime($pItem['created_at'])) : '';
            $itemsSummary = !empty($pItem['items_summary']) ? $pItem['items_summary'] : 'Stock items catalog entry';
            
            $reply .= "**" . $pCount . ". Invoice #" . $pItem['id'] . "** — Supplier: **" . htmlspecialchars($sName) . "**\n";
            $reply .= "   - 🏷️ **Purchased Stock:** `" . htmlspecialchars($itemsSummary) . "`\n";
            $reply .= "   - Total Invoice: **$" . number_format($totAmt, 2) . "** | Amount Paid: **$" . number_format($paidAmt, 2) . "**\n";
            if ($pendingBal > 0) {
                $reply .= "   - Pending Balance: **$" . number_format($pendingBal, 2) . "**\n";
            } else {
                $reply .= "   - Status: **Fully Paid ✅**\n";
            }
            if ($dtStr) {
                $reply .= "   - Date: " . $dtStr . "\n";
            }
            $reply .= "\n";
            $pCount++;
        }
    } else {
        if (!empty($targetDateStr)) {
            $reply .= "ℹ️ **" . $dateLabelStr . "** ko koi supplier payment ya stock purchase invoice record nahi hua.\n\n";
        } else {
            $reply .= "ℹ️ Database mein filhal koi purchase invoice record nahi hua.\n\n";
        }
    }

    if (!empty($detailedSuppliersList)) {
        $reply .= "🏬 **Supplier Accounts & Pending Payables:**\n";
        foreach ($detailedSuppliersList as $sItem) {
            $payVal = floatval($sItem['outstanding_payable']);
            $contactStr = !empty($sItem['phone']) ? " (Phone: " . htmlspecialchars($sItem['phone']) . ")" : "";
            $reply .= "• **" . htmlspecialchars($sItem['name']) . "**" . $contactStr . " — Pending Payable: **$" . number_format($payVal, 2) . "**\n";
        }
    }
}
elseif ($isGeneralInventoryOverview && !$isOpeningCashQuery && !$isTopSellingQuery) {
    $potentialProfit = $inventorySellValue - $inventoryCostValue;
    $reply = "📦 **Complete Store Inventory & Product Catalog Report:**\n\n";
    $reply .= "• **Total Registered Products:** **" . $totalProducts . " Products**\n";
    $reply .= "• **Total Inventory Retail Value:** **$" . number_format($inventorySellValue, 2) . "**\n";
    if ($inventoryCostValue > 0) {
        $reply .= "• **Total Inventory Cost Value:** **$" . number_format($inventoryCostValue, 2) . "**\n";
    }
    if ($potentialProfit > 0) {
        $reply .= "• **Potential Inventory Profit:** **$" . number_format($potentialProfit, 2) . "**\n";
    }
    $reply .= "• Out of Stock (0 Stock): **" . $outOfStockCount . "** items\n";
    $reply .= "• Low Stock Warning (<=10): **" . $lowStockCount . "** items\n\n";

    if ($totalProducts > 0 && !empty($allProductCatalog)) {
        $reply .= "📋 **Product Stock & Price Catalog List:**\n";
        $idx = 1;
        foreach ($allProductCatalog as $pItem) {
            $u = !empty($pItem['unit']) ? $pItem['unit'] : 'units';
            $c = !empty($pItem['category']) ? " (" . htmlspecialchars($pItem['category']) . ")" : "";
            $reply .= "**" . $idx . ". " . htmlspecialchars($pItem['name']) . "**" . $c . " — Price: **$" . number_format($pItem['price'], 2) . "** | Stock: **" . floatval($pItem['stock']) . " " . $u . "**\n";
            $idx++;
        }
    } else {
        $reply .= "⚠️ Store database mein filhal koi product added nahi hai.";
    }
}
elseif (!empty($searchedProducts) && !$isOpeningCashQuery && !$isTopSellingQuery) {
    $reply = "🔍 **Product Inventory Search Results:**\n\n";
    foreach ($searchedProducts as $item) {
        $unitStr = !empty($item['unit']) ? $item['unit'] : 'units';
        $catStr = !empty($item['category']) ? " (" . htmlspecialchars($item['category']) . ")" : "";
        $costStr = !empty($item['purchase_price']) ? floatval($item['purchase_price']) : 0;
        
        $reply .= "🏷️ **" . htmlspecialchars($item['name']) . "**" . $catStr . "\n";
        $reply .= "  • **Retail Selling Price:** **$" . number_format($item['price'], 2) . "**\n";
        if ($costStr > 0) {
            $reply .= "  • **Purchase Cost Price:** $" . number_format($costStr, 2) . "\n";
        }
        $reply .= "  • **Stock Available:** **" . floatval($item['stock']) . " " . $unitStr . "**\n";
        if (!empty($item['barcode'])) {
            $reply .= "  • **Barcode:** `" . htmlspecialchars($item['barcode']) . "`\n";
        }
        $reply .= "\n";
    }
}
// END OF DAY (Z-REPORT) FINANCIAL STATEMENT ROUTE
elseif ($isEodReportQuery) {
    $filterLabel = !empty($dateLabelStr) ? $dateLabelStr : ($targetDateStr ? date('d M Y', strtotime($targetDateStr)) : ($targetMonthStr ? $targetMonthStr : "Today (" . date('d M Y') . ")"));
    
    $eodOrdersCount = 0;
    $eodSalesTotal = 0;
    $eodCashSalesTotal = 0;
    $eodCreditSalesTotal = 0;
    $eodCogsTotal = 0;
    $eodExpensesTotal = 0;
    $eodPurchasesTotal = 0;
    $eodReturnsRefundTotal = 0;
    $eodUdhaarRecoveredTotal = 0;
    $eodOpeningCash = 0;
    
    try {
        if ($isMonthQuery && !empty($targetMonthStr)) {
            $eodSalesWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
            $eodCogsWhere = "WHERE DATE_FORMAT(s.created_at, '%Y-%m') = '$targetMonthStr' AND s.tenant_id = $tenantId";
            $eodExpWhere = "WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$targetMonthStr' AND category != 'Return Refund' AND tenant_id = $tenantId";
            $eodPurWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND amount_paid > 0 AND tenant_id = $tenantId";
            $eodRetWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
            $eodClWhere = "WHERE type = 'payment' AND DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
            $eodShWhere = "WHERE DATE_FORMAT(opened_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
        } elseif (!empty($targetDateStr)) {
            $eodSalesWhere = "WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
            $eodCogsWhere = "WHERE DATE(s.created_at) = '$targetDateStr' AND s.tenant_id = $tenantId";
            $eodExpWhere = "WHERE DATE(expense_date) = '$targetDateStr' AND category != 'Return Refund' AND tenant_id = $tenantId";
            $eodPurWhere = "WHERE DATE(created_at) = '$targetDateStr' AND amount_paid > 0 AND tenant_id = $tenantId";
            $eodRetWhere = "WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
            $eodClWhere = "WHERE type = 'payment' AND DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
            $eodShWhere = "WHERE DATE(opened_at) = '$targetDateStr' AND tenant_id = $tenantId";
        } else {
            $eodSalesWhere = "WHERE is_cleared = 0 AND tenant_id = $tenantId";
            $eodCogsWhere = "WHERE s.is_cleared = 0 AND s.tenant_id = $tenantId";
            $eodExpWhere = "WHERE is_cleared = 0 AND category != 'Return Refund' AND tenant_id = $tenantId";
            $eodPurWhere = "WHERE is_cleared = 0 AND amount_paid > 0 AND tenant_id = $tenantId";
            $eodRetWhere = "WHERE (is_cleared = 0 OR is_cleared IS NULL) AND tenant_id = $tenantId";
            $eodClWhere = "WHERE type = 'payment' AND is_cleared = 0 AND tenant_id = $tenantId";
            $eodShWhere = "WHERE is_cleared = 0 AND tenant_id = $tenantId";
        }

        // 1. Sales & Cash/Credit Split
        $sQ = $conn->query("SELECT COUNT(id) as cnt, SUM(total_amount) as total FROM sales $eodSalesWhere");
        if ($sQ && $sQ->num_rows > 0) {
            $sD = $sQ->fetch_assoc();
            $eodOrdersCount = intval($sD['cnt'] ?? 0);
            $eodSalesTotal = floatval($sD['total'] ?? 0);
        }
        
        $csQ = $conn->query("SELECT SUM(total_amount) as c FROM sales " . ($eodSalesWhere ? $eodSalesWhere . " AND payment_method = 'cash'" : "WHERE payment_method = 'cash'"));
        if ($csQ && $csQ->num_rows > 0) {
            $eodCashSalesTotal = floatval($csQ->fetch_assoc()['c'] ?? 0);
        }
        $eodCreditSalesTotal = $eodSalesTotal - $eodCashSalesTotal;

        // 2. COGS
        $cgQ = $conn->query("SELECT SUM(si.quantity * si.cost_price) as cogs FROM sales s JOIN sale_items si ON s.id = si.sale_id $eodCogsWhere");
        if ($cgQ && $cgQ->num_rows > 0) {
            $eodCogsTotal = floatval($cgQ->fetch_assoc()['cogs'] ?? 0);
        }

        // 3. Expenses
        $exQ = $conn->query("SELECT SUM(amount) as total FROM expenses $eodExpWhere");
        if ($exQ && $exQ->num_rows > 0) {
            $eodExpensesTotal = floatval($exQ->fetch_assoc()['total'] ?? 0);
        }

        // 4. Supplier Payments
        $puQ = $conn->query("SELECT SUM(amount_paid) as total FROM purchases $eodPurWhere");
        if ($puQ && $puQ->num_rows > 0) {
            $eodPurchasesTotal = floatval($puQ->fetch_assoc()['total'] ?? 0);
        }

        // 5. Returns Refund
        $rtQ = $conn->query("SELECT SUM(total_refund) as total FROM returns $eodRetWhere");
        if ($rtQ && $rtQ->num_rows > 0) {
            $eodReturnsRefundTotal = floatval($rtQ->fetch_assoc()['total'] ?? 0);
        }

        // 6. Customer Udhaar Recovered
        $clQ = $conn->query("SELECT SUM(amount) as total FROM customer_ledger $eodClWhere");
        if ($clQ && $clQ->num_rows > 0) {
            $eodUdhaarRecoveredTotal = floatval($clQ->fetch_assoc()['total'] ?? 0);
        }

        // 7. Opening Cash
        $shQ = $conn->query("SELECT opening_cash FROM shifts $eodShWhere ORDER BY id DESC LIMIT 1");
        if ($shQ && $shQ->num_rows > 0) {
            $eodOpeningCash = floatval($shQ->fetch_assoc()['opening_cash'] ?? 0);
        } elseif (!empty($targetDateStr)) {
            $zQ = $conn->query("SELECT opening_cash FROM z_reports_history WHERE DATE(report_date) = '$targetDateStr' AND tenant_id = $tenantId LIMIT 1");
            if ($zQ && $zQ->num_rows > 0) {
                $eodOpeningCash = floatval($zQ->fetch_assoc()['opening_cash'] ?? 0);
            }
        }
    } catch (\Throwable $e) {}

    $eodGrossProfit = $eodSalesTotal - $eodCogsTotal;
    $eodNetIncome = $eodSalesTotal - $eodExpensesTotal - $eodPurchasesTotal - $eodReturnsRefundTotal;
    $eodExpectedCashInDrawer = $eodOpeningCash + $eodCashSalesTotal + $eodUdhaarRecoveredTotal - $eodExpensesTotal - $eodPurchasesTotal - $eodReturnsRefundTotal;

    $reply = "📋 **End of Day (Z-Report) Financial Statement:**\n";
    $reply .= "📍 **Report Date:** **" . $filterLabel . "**\n\n";

    $reply .= "💵 **Sales & Revenue Performance:**\n";
    $reply .= "• **Total Orders Placed:** **" . $eodOrdersCount . " Orders**\n";
    $reply .= "• **Gross Revenue (Total Sales):** **$" . number_format($eodSalesTotal, 2) . "**\n";
    $reply .= "  - Cash Sales Collection: **$" . number_format($eodCashSalesTotal, 2) . "**\n";
    $reply .= "  - Card & Credit Sales: **$" . number_format($eodCreditSalesTotal, 2) . "**\n";
    $reply .= "• Cost of Goods Sold (COGS): $" . number_format($eodCogsTotal, 2) . "\n";
    $reply .= "• 📈 **Gross Profit Margin:** **$" . number_format($eodGrossProfit, 2) . "**\n\n";

    $reply .= "💸 **Outflows & Payments Made:**\n";
    $reply .= "• Operating Expenses: **$" . number_format($eodExpensesTotal, 2) . "**\n";
    $reply .= "• Supplier Stock Payments: **$" . number_format($eodPurchasesTotal, 2) . "**\n";
    $reply .= "• Customer Returns & Refunds: **$" . number_format($eodReturnsRefundTotal, 2) . "**\n\n";

    $reply .= "💰 **Net Income & Settlement:**\n";
    $reply .= "• 👤 Customer Udhaar Cash Recovered: **$" . number_format($eodUdhaarRecoveredTotal, 2) . "**\n";
    $reply .= "• 📊 **Net Income (Net Profit):** **$" . number_format($eodNetIncome, 2) . "**\n\n";

    $reply .= "🗄️ **Cash Register Drawer Settlement (" . $filterLabel . "):**\n";
    $reply .= "• Opening Cash: **$" . number_format($eodOpeningCash, 2) . "**\n";
    $reply .= "• Net Cash Sales Received: **$" . number_format($eodCashSalesTotal, 2) . "**\n";
    $reply .= "• Customer Udhaar Cash Recovered: **$" . number_format($eodUdhaarRecoveredTotal, 2) . "**\n";
    $reply .= "• Cash Outflows (Expenses + Suppliers + Refunds): **-$" . number_format(($eodExpensesTotal + $eodPurchasesTotal + $eodReturnsRefundTotal), 2) . "**\n";
    $reply .= "• 💵 **Expected Cash in Drawer:** **$" . number_format($eodExpectedCashInDrawer, 2) . "**";
}
elseif ($isOpeningCashQuery && !$isTopSellingQuery) {
    if (!empty($targetDateStr) || (!empty($targetMonthStr) && $isMonthQuery)) {
        $filterLabel = !empty($dateLabelStr) ? $dateLabelStr : ($targetDateStr ? date('d M Y', strtotime($targetDateStr)) : $targetMonthStr);
        $reply = "💵 **Cash Register & Opening Cash Details:**\n📍 **Date Filter:** **" . $filterLabel . "**\n\n";
        
        $histOpenCash = 0;
        $histShiftStatus = 'CLOSED';
        $histOpenedAt = '';
        $histClosedAt = '';
        $histCashSales = 0;
        $histExpenses = 0;
        $foundShiftRecord = false;
        
        try {
            if ($isMonthQuery && !empty($targetMonthStr)) {
                $shiftWhere = "WHERE DATE_FORMAT(opened_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
                $cashSalesWhere = "WHERE payment_method = 'cash' AND DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
                $expWhere = "WHERE DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId";
            } else {
                $shiftWhere = "WHERE DATE(opened_at) = '$targetDateStr' AND tenant_id = $tenantId";
                $cashSalesWhere = "WHERE payment_method = 'cash' AND DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
                $expWhere = "WHERE DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId";
            }
            
            // Query shifts table for historical date
            $shQ = $conn->query("SELECT opening_cash, status, opened_at, closed_at, expected_cash, closing_cash FROM shifts $shiftWhere ORDER BY id DESC LIMIT 1");
            if ($shQ && $shQ->num_rows > 0) {
                $shData = $shQ->fetch_assoc();
                $histOpenCash = floatval($shData['opening_cash'] ?? 0);
                $histShiftStatus = !empty($shData['status']) ? strtoupper($shData['status']) : 'CLOSED';
                $histOpenedAt = $shData['opened_at'] ?? '';
                $histClosedAt = $shData['closed_at'] ?? '';
                $foundShiftRecord = true;
            }
            
            // Query z_reports_history if not found in shifts
            if (!$foundShiftRecord && !empty($targetDateStr)) {
                $zQ = $conn->query("SELECT opening_cash, expected_cash, closing_cash FROM z_reports_history WHERE DATE(report_date) = '$targetDateStr' AND tenant_id = $tenantId LIMIT 1");
                if ($zQ && $zQ->num_rows > 0) {
                    $zData = $zQ->fetch_assoc();
                    $histOpenCash = floatval($zData['opening_cash'] ?? 0);
                    $foundShiftRecord = true;
                }
            }
            
            // Query cash sales on date/month
            $csQ = $conn->query("SELECT SUM(amount_received - change_returned) as c FROM sales $cashSalesWhere");
            if ($csQ && $csQ->num_rows > 0) {
                $histCashSales = floatval($csQ->fetch_assoc()['c'] ?? 0);
            }
            
            // Query cash expenses on date/month
            $exQ = $conn->query("SELECT SUM(amount) as e FROM expenses $expWhere");
            if ($exQ && $exQ->num_rows > 0) {
                $histExpenses = floatval($exQ->fetch_assoc()['e'] ?? 0);
            }
        } catch (\Throwable $e) {}
        
        $histExpectedCash = $histOpenCash + $histCashSales - $histExpenses;
        
        $reply .= "• **Opening Cash (" . $filterLabel . "):** **$" . number_format($histOpenCash, 2) . "**\n";
        $reply .= "• Shift Status: **" . $histShiftStatus . "**" . ($histOpenedAt ? " (Opened: " . date('h:i A', strtotime($histOpenedAt)) . ")" : "") . "\n";
        $reply .= "• Net Cash Received Sales: **$" . number_format($histCashSales, 2) . "**\n";
        $reply .= "• Cash Expenses Paid: **$" . number_format($histExpenses, 2) . "**\n";
        $reply .= "• **Expected Cash in Register (" . $filterLabel . "):** **$" . number_format($histExpectedCash, 2) . "**";
    } else {
        $reply = "💵 **Cash Register & Opening Cash Details:**\n📍 **Date Filter:** **Today (" . date('d M Y') . ")**\n\n";
        $reply .= "• **Today Opening Cash:** **$" . number_format($openCashVal, 2) . "**\n";
        $reply .= "• Shift Status: **" . $shiftStatusStr . "**" . ($shiftOpenedAt ? " (Opened: " . date('h:i A', strtotime($shiftOpenedAt)) . ")" : "") . "\n";
        $reply .= "• Net Cash Received Sales: **$" . number_format($netCashSales, 2) . "**\n";
        $reply .= "• Cash Expenses Paid: **$" . number_format($todayExpenses, 2) . "**\n";
        $reply .= "• **Expected Cash in Drawer:** **$" . number_format($expectedDrawerCash, 2) . "**";
    }
}
elseif ($isTopSellingQuery) {
    if (!empty($topSellers)) {
        $reply = "🔥 **Top Selling Products (Best Sellers):**\n\n";
        $rank = 1;
        foreach ($topSellers as $item) {
            $reply .= "**" . $rank . ". " . htmlspecialchars($item['name']) . "**\n";
            $reply .= "   - Total Sold: **" . $item['total_qty'] . " units**\n";
            $reply .= "   - Total Revenue: **$" . number_format($item['total_rev'], 2) . "**\n\n";
            $rank++;
        }
    } else {
        $reply = "📦 Store database mein abhi tak sales entry record nahi hui. POS Terminal se items sell hone par top sellers yahan update honge.";
    }
}
// 4. LOW STOCK / OUT OF STOCK REPORT
elseif (
    strpos($qLower, 'low stock') !== false ||
    strpos($qLower, 'out of stock') !== false ||
    strpos($qLower, 'khatam stock') !== false ||
    strpos($qLower, 'kam stock') !== false ||
    strpos($qLower, 'thoda stock') !== false
) {
    $reply = "📦 **Low & Out of Stock Report:**\n\n";
    $reply .= "• Total Products in Store: **" . $totalProducts . "**\n";
    $reply .= "• Out of Stock (0 Stock): **" . $outOfStockCount . "** items\n";
    $reply .= "• Low Stock (<= 10 Stock): **" . $lowStockCount . "** items\n\n";

    if ($lowStockCount > 0) {
        $reply .= "⚠️ **Low Stock Product Details:**\n";
        foreach ($lowStockItems as $item) {
            $reply .= "  - **" . htmlspecialchars($item['name']) . "** | Stock Left: **" . $item['stock'] . "** | Price: $" . number_format($item['price'], 2) . "\n";
        }
    } else {
        $reply .= "✅ Sabhi products ka stock safety level par hai!";
    }
}
// 5. DAMAGED STOCK / EXPIRY / FINANCIAL LOSS
elseif (
    strpos($qLower, 'damage') !== false ||
    strpos($qLower, 'nuksan') !== false ||
    strpos($qLower, 'loss') !== false ||
    strpos($qLower, 'kharab') !== false ||
    strpos($qLower, 'zaya') !== false
) {
    $reply = "🚨 **Damaged Stock & Financial Loss Analytics:**\n\n";
    $reply .= "• Total Damaged Records: **" . $totalDamageCount . "**\n";
    $reply .= "• Total Damaged Quantity: **" . $totalDamageQty . " units**\n";
    $reply .= "• **Total Financial Loss:** **$" . number_format($totalDamageLoss, 2) . "**";
}
// 6. PAYMENTS & RECOVERY DETAILS (Customer Udhaar Collection & Supplier Payments)
elseif (
    strpos($qLower, 'payment') !== false ||
    strpos($qLower, 'payments') !== false ||
    strpos($qLower, 'paymnt') !== false ||
    strpos($qLower, 'pemnt') !== false ||
    strpos($qLower, 'card sale') !== false ||
    strpos($qLower, 'cash sale') !== false ||
    strpos($qLower, 'wasool') !== false ||
    strpos($qLower, 'vasool') !== false ||
    strpos($qLower, 'paisa') !== false ||
    strpos($qLower, 'paise') !== false ||
    strpos($qLower, 'raqam') !== false ||
    (strpos($qLower, 'aj') !== false && (strpos($qLower, 'ai') !== false || strpos($qLower, 'aayi') !== false || strpos($qLower, 'aaye') !== false)) ||
    (strpos($qLower, 'aaj') !== false && (strpos($qLower, 'ai') !== false || strpos($qLower, 'aayi') !== false || strpos($qLower, 'aaye') !== false))
) {
    if ($isMonthQuery && !empty($targetMonthStr)) {
        $cpWhereSql = "cl.type = 'payment' AND DATE_FORMAT(cl.created_at, '%Y-%m') = '$targetMonthStr' AND cl.tenant_id = $tenantId";
        $spWhereSql = "p.amount_paid > 0 AND DATE_FORMAT(p.created_at, '%Y-%m') = '$targetMonthStr' AND p.tenant_id = $tenantId";
        $dsWhereSql = "DATE_FORMAT(created_at, '%Y-%m') = '$targetMonthStr' AND tenant_id = $tenantId AND payment_method != 'unpaid'";
        $dateH = !empty($dateLabelStr) ? $dateLabelStr : "Month (" . $targetMonthStr . ")";
    } elseif (!empty($targetDateStr)) {
        $cpWhereSql = "cl.type = 'payment' AND DATE(cl.created_at) = '$targetDateStr' AND cl.tenant_id = $tenantId";
        $spWhereSql = "p.amount_paid > 0 AND DATE(p.created_at) = '$targetDateStr' AND p.tenant_id = $tenantId";
        $dsWhereSql = "DATE(created_at) = '$targetDateStr' AND tenant_id = $tenantId AND payment_method != 'unpaid'";
        $dateH = !empty($dateLabelStr) ? $dateLabelStr : "Date (" . $targetDateStr . ")";
    } else {
        $cpWhereSql = "cl.type = 'payment' AND DATE(cl.created_at) = CURDATE() AND cl.tenant_id = $tenantId";
        $spWhereSql = "p.amount_paid > 0 AND DATE(p.created_at) = CURDATE() AND p.tenant_id = $tenantId";
        $dsWhereSql = "DATE(created_at) = CURDATE() AND tenant_id = $tenantId AND payment_method != 'unpaid'";
        $dateH = "Today (" . date('d M Y') . ")";
    }
    
    // Fetch Customer Udhaar Payments Received
    $cPaymentsList = [];
    $totalCustomerPaymentsVal = 0;
    try {
        $cpQ = $conn->query("
            SELECT cl.id, cl.amount, cl.balance_after, cl.description, cl.created_at,
                   c.name as customer_name, c.phone as customer_phone, c.outstanding_balance
            FROM customer_ledger cl
            JOIN customers c ON cl.customer_id = c.id
            WHERE $cpWhereSql
            ORDER BY cl.id DESC
        ");
        if ($cpQ && $cpQ->num_rows > 0) {
            while ($cp = $cpQ->fetch_assoc()) {
                $cPaymentsList[] = $cp;
                $totalCustomerPaymentsVal += floatval($cp['amount']);
            }
        }
    } catch (\Throwable $e) {}

    // Fetch Supplier Stock Payments Made
    $sPaymentsList = [];
    $totalSupplierPaymentsVal = 0;
    try {
        $spQ = $conn->query("
            SELECT p.id, p.total_amount, p.amount_paid, p.status, p.created_at,
                   s.name as supplier_name, s.phone as supplier_phone, s.outstanding_payable
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE $spWhereSql
            ORDER BY p.id DESC
        ");
        if ($spQ && $spQ->num_rows > 0) {
            while ($sp = $spQ->fetch_assoc()) {
                $sPaymentsList[] = $sp;
                $totalSupplierPaymentsVal += floatval($sp['amount_paid']);
            }
        }
    } catch (\Throwable $e) {}

    // Direct Sales Collections Breakdown
    $dsList = [];
    $totalSalesCollectedVal = 0;
    try {
        $dsQ = $conn->query("
            SELECT payment_method, COUNT(id) as cnt, SUM(amount_received - change_returned) as collected, SUM(total_amount) as total_val
            FROM sales
            WHERE $dsWhereSql
            GROUP BY payment_method
        ");
        if ($dsQ && $dsQ->num_rows > 0) {
            while ($ds = $dsQ->fetch_assoc()) {
                $amt = floatval($ds['collected'] > 0 ? $ds['collected'] : $ds['total_val']);
                $dsList[$ds['payment_method']] = ['amt' => $amt, 'cnt' => $ds['cnt']];
                $totalSalesCollectedVal += $amt;
            }
        }
    } catch (\Throwable $e) {}

    $reply = "💳 **Payment & Recovery Report:**\n📍 **Date Filter:** **" . $dateH . "**\n\n";
    $reply .= "• 👤 **Customer Udhaar Recoveries:** **$" . number_format($totalCustomerPaymentsVal, 2) . "**\n";
    $reply .= "• 🏭 **Supplier Payments Made:** **$" . number_format($totalSupplierPaymentsVal, 2) . "**\n";
    $reply .= "• 🛍️ **Direct Sales Collection:** **$" . number_format($totalSalesCollectedVal, 2) . "**\n\n";

    // 1. Customer Udhaar Payments
    if (!empty($cPaymentsList)) {
        $reply .= "👤 **Customer Credit / Udhaar Received Details (" . count($cPaymentsList) . "):**\n";
        $cCount = 1;
        foreach ($cPaymentsList as $cp) {
            $cName = !empty($cp['customer_name']) ? $cp['customer_name'] : 'Customer';
            $cPhone = !empty($cp['customer_phone']) ? " (" . $cp['customer_phone'] . ")" : "";
            $pAmt = floatval($cp['amount']);
            $remBal = floatval($cp['balance_after']);
            $timeStr = date('h:i A', strtotime($cp['created_at']));
            $descStr = !empty($cp['description']) ? " — *" . htmlspecialchars($cp['description']) . "*" : "";
            
            $reply .= "**" . $cCount . ". " . htmlspecialchars($cName) . "**" . $cPhone . "\n";
            $reply .= "   - 💰 **Amount Received:** **$" . number_format($pAmt, 2) . "** at " . $timeStr . "\n";
            if ($remBal <= 0) {
                $reply .= "   - 📌 **Remaining Udhaar Balance:** **$0.00 (Fully Cleared ✅)**\n";
            } else {
                $reply .= "   - 📌 **Remaining Udhaar Balance:** **$" . number_format($remBal, 2) . "**\n";
            }
            if ($descStr) {
                $reply .= "   - 📝 Note:" . $descStr . "\n";
            }
            $reply .= "\n";
            $cCount++;
        }
    } else {
        $reply .= "👤 **Customer Udhaar Payments:** *Is date ko purana udhaar/credit recovery record nahi hua.* \n\n";
    }

    // 2. Supplier Payments
    if (!empty($sPaymentsList)) {
        $reply .= "🏭 **Supplier Payments Paid Details (" . count($sPaymentsList) . "):**\n";
        $sCount = 1;
        foreach ($sPaymentsList as $sp) {
            $sName = !empty($sp['supplier_name']) ? $sp['supplier_name'] : 'Supplier';
            $sPhone = !empty($sp['supplier_phone']) ? " (" . $sp['supplier_phone'] . ")" : "";
            $pPaid = floatval($sp['amount_paid']);
            $totInv = floatval($sp['total_amount']);
            $remPayable = floatval($sp['outstanding_payable'] ?? 0);
            $timeStr = date('h:i A', strtotime($sp['created_at']));
            
            $reply .= "**" . $sCount . ". " . htmlspecialchars($sName) . "**" . $sPhone . "\n";
            $reply .= "   - 💸 **Amount Paid to Supplier:** **$" . number_format($pPaid, 2) . "** at " . $timeStr . "\n";
            $reply .= "   - 📦 **Invoice Total:** **$" . number_format($totInv, 2) . "**\n";
            if ($remPayable > 0) {
                $reply .= "   - 📌 **Remaining Supplier Payable:** **$" . number_format($remPayable, 2) . "**\n";
            } else {
                $reply .= "   - 📌 **Status:** **Fully Settled ✅**\n";
            }
            $reply .= "\n";
            $sCount++;
        }
    } else {
        $reply .= "🏭 **Supplier Payments:** *Is date ko supplier ko koi payment nahi di gayi.* \n\n";
    }

    // 3. Sales Breakdown
    if (!empty($dsList)) {
        $reply .= "💳 **Direct Sales Payment Breakdown:**\n";
        foreach ($dsList as $m => $info) {
            $reply .= "• **" . ucfirst($m) . ":** $" . number_format($info['amt'], 2) . " (" . $info['cnt'] . " transactions)\n";
        }
    }
}
// 7. PROFIT / MARGINS / NET EARNINGS
elseif (
    strpos($qLower, 'profit') !== false ||
    strpos($qLower, 'munafa') !== false ||
    strpos($qLower, 'nafa') !== false ||
    strpos($qLower, 'margin') !== false ||
    strpos($qLower, 'kamai') !== false
) {
    $netProfit = $todayProfit - $todayExpenses;
    $dateH = !empty($dateLabelStr) ? $dateLabelStr : "Today (" . date('d M Y') . ")";
    $reply = "💰 **Profit & Financial Summary (" . $dateH . "):**\n\n";
    $reply .= "• 💵 **Total Sales Revenue:** **$" . number_format($todaySales, 2) . "**\n";
    $reply .= "• 📦 **Cost of Goods Sold (COGS):** **$" . number_format($todayCogs, 2) . "**\n";
    $reply .= "• 📈 **Gross Profit:** **$" . number_format($todayProfit, 2) . "**\n";
    if ($todayExpenses > 0) {
        $reply .= "• 💸 **Operating Expenses:** **$" . number_format($todayExpenses, 2) . "**\n";
    }
    $reply .= "• 🎯 **Total Profit:** **$" . number_format($netProfit, 2) . "**";
}
// 8. EXPENSES & SPENDING
elseif (
    strpos($qLower, 'expense') !== false ||
    strpos($qLower, 'expenses') !== false ||
    strpos($qLower, 'kharcha') !== false ||
    strpos($qLower, 'kharche') !== false
) {
    $dateH = !empty($dateLabelStr) ? " (" . $dateLabelStr . ")" : "";
    $reply = "💸 **Expense Report" . $dateH . ":**\n\n";
    $reply .= "• Total Expense Transactions: **" . $todayExpenseCount . "**\n";
    $reply .= "• **Total Operating Expenses Paid:** **$" . number_format($todayExpenses, 2) . "**";
}
// 9. ORDERS COUNT
elseif (
    strpos($qLower, 'order') !== false ||
    strpos($qLower, 'orders') !== false ||
    strpos($qLower, 'kitne order') !== false ||
    strpos($qLower, 'kitnay order') !== false
) {
    $dateH = !empty($dateLabelStr) ? " (" . $dateLabelStr . ")" : " (Active Shift / Today)";
    $reply = "📦 **Total Orders & Sales Analytics" . $dateH . ":**\n\n";
    $reply .= "• 🛍️ **Total Orders:** **" . $todayOrders . " Orders**\n";
    $reply .= "• 💵 **Total Revenue:** **$" . number_format($todaySales, 2) . "**\n";
    $reply .= "• 📈 Gross Profit: **$" . number_format($todayProfit, 2) . "**\n";
    $reply .= "• 🌐 **Lifetime System Orders:** **" . $lifetimeOrders . " Orders** (Lifetime Revenue: **$" . number_format($lifetimeSales, 2) . "**)";
}
// 10. TOTAL SALES & REVENUE
elseif (
    strpos($qLower, 'total sale') !== false ||
    strpos($qLower, 'today sale') !== false ||
    strpos($qLower, 'sales kitni') !== false ||
    strpos($qLower, 'kitni sale') !== false ||
    strpos($qLower, 'sale kitni') !== false ||
    strpos($qLower, 'sale') !== false ||
    strpos($qLower, 'sales') !== false ||
    strpos($qLower, 'revenue') !== false ||
    strpos($qLower, 'turnover') !== false
) {
    $dateH = !empty($dateLabelStr) ? " (" . $dateLabelStr . ")" : " (Active Shift / Today)";
    $reply = "📊 **Total Sales & Revenue Summary" . $dateH . ":**\n\n";
    $reply .= "• 💵 **Total Sales Amount:** **$" . number_format($todaySales, 2) . "**\n";
    $reply .= "• 🛍️ **Total Orders Placed:** **" . $todayOrders . " Orders**\n";
    $reply .= "• 📈 Gross Profit: **$" . number_format($todayProfit, 2) . "**\n";
    $reply .= "• 💳 Cash Received Sales: **$" . number_format($netCashSales, 2) . "**\n";
    $reply .= "• 🌐 **Lifetime Total Sales:** **$" . number_format($lifetimeSales, 2) . "** (" . $lifetimeOrders . " Orders)";
}
// 11. CUSTOMERS & UDHAAR (KHATA)
elseif (
    strpos($qLower, 'udhaar') !== false ||
    strpos($qLower, 'udher') !== false ||
    strpos($qLower, 'khata') !== false ||
    strpos($qLower, 'credit') !== false ||
    strpos($qLower, 'debt') !== false
) {
    $dateH = !empty($dateLabelStr) ? $dateLabelStr : "Today (" . date('d M Y') . ")";

    $reply = "💳 **Customer Udhaar & Credit Sales Report (" . $dateH . "):**\n\n";
    $reply .= "• **" . $dateH . " Total New Udhaar:** **$" . number_format($todayUdhaarTotal, 2) . "** (" . $todayUdhaarCount . " Credit Sales)\n\n";

    if (!empty($todayUdhaarCustomersList)) {
        $reply .= "👥 **" . $dateH . " Kis Customer Se Kitna Udhaar Lena Hai:**\n";
        $uIdx = 1;
        foreach ($todayUdhaarCustomersList as $uItem) {
            $cName = !empty($uItem['customer_name']) ? $uItem['customer_name'] : 'Walk-in Customer';
            $amt = floatval($uItem['total_amount']);
            $orderCode = sprintf('%06d', $uItem['id']);
            $timeStr = !empty($uItem['created_at']) ? date('h:i A', strtotime($uItem['created_at'])) : '';
            $itemsStr = !empty($uItem['items_summary']) ? " (`" . $uItem['items_summary'] . "`)" : "";

            $reply .= "**" . $uIdx . ". Customer: " . htmlspecialchars($cName) . "**\n";
            $reply .= "   - 💵 **Udhaar Amount:** **$" . number_format($amt, 2) . "**\n";
            $reply .= "   - 🧾 **Order ID:** Order # " . $orderCode . $itemsStr . "\n";
            if ($timeStr) {
                $reply .= "   - 🕒 **Time:** " . $timeStr . "\n";
            }
            $reply .= "\n";
            $uIdx++;
        }
    } else {
        $reply .= "✅ **" . $dateH . "** ko koi nayi Udhaar (Credit) sale record nahi hui. Aaj ke tamam orders Cash/Card paid hain!\n\n";
    }

    if (strpos($qLower, 'total') !== false || strpos($qLower, 'peechla') !== false || strpos($qLower, 'all') !== false || strpos($qLower, 'overall') !== false || strpos($qLower, 'ledger') !== false) {
        $reply .= "📌 **Historical Total Customer Balances (Overall Ledger):**\n";
        $reply .= "• Total Overall Udhaar Balance (All Time): **$" . number_format($udhaarTotal, 2) . "** (" . $udhaarCount . " Customers)\n";
        if ($topUdhaarCust) {
            $reply .= "• Top Outstanding Ledger: **" . htmlspecialchars($topUdhaarCust['name']) . "** ($" . number_format($topUdhaarCust['outstanding_balance'], 2) . ")\n";
        }
    }
}
// 12. STAFF / EMPLOYEES / USERS
elseif (
    strpos($qLower, 'staff') !== false ||
    strpos($qLower, 'employee') !== false ||
    strpos($qLower, 'user') !== false ||
    strpos($qLower, 'users') !== false ||
    strpos($qLower, 'bande') !== false
) {
    $reply = "👨‍💼 **Staff & Team Overview:**\n\n";
    $reply .= "• Total System Users / Staff: **" . $totalStaff . "** members\n";
    $reply .= "• Current Active User Role: **" . ucfirst($userRole) . "**\n";
    $reply .= "• Current Branch: **" . htmlspecialchars($currentBranchName) . "**\n\n";
    $reply .= "ℹ️ *Usernames, passwords & PINs privacy protection ke tehat hidden hain.*";
}
// 13. SYSTEM FEATURES & MODULES GUIDE
elseif (
    strpos($qLower, 'feature') !== false ||
    strpos($qLower, 'software') !== false ||
    strpos($qLower, 'kya kya') !== false ||
    strpos($qLower, 'module') !== false ||
    strpos($qLower, 'help') !== false
) {
    $reply = "🖥️ **System Features & Software Modules Breakdown:**\n\n";
    $reply .= "• **1. POS Terminal:** Barcode search, discount & quick retail checkout.\n";
    $reply .= "• **2. Shift & Register:** Opening cash drawer, closing shift & variance tracking.\n";
    $reply .= "• **3. Executive Dashboard:** Real-time metrics, profit/loss graphs & branch stats.\n";
    $reply .= "• **4. Inventory Management:** Products catalog, stock alerts, categories & cost pricing.\n";
    $reply .= "• **5. Customers & Udhaar (Khata):** Customer ledgers, deposits & payment tracking.\n";
    $reply .= "• **6. Suppliers & Payables:** Vendor balance tracking & purchase invoices.\n";
    $reply .= "• **7. Expenses & Financials:** Expense recording, categorization & profit reports.\n";
    $reply .= "• **8. HR & Payroll:** Employee attendance, salaries & staff directory.\n";
    $reply .= "• **9. Damaged Stock:** Record broken/expired inventory & track financial loss.\n";
    $reply .= "• **10. E-Commerce Online Store:** Synchronized web shop link & digital order sync.\n";
    $reply .= "• **11. End of Day (Z-Report):** 1-click day settlement & historical archiving.";
}
// 14. DEFAULT OVERVIEW
else {
    $reply = "🤖 **POS Smart AI Agent:**\n\n";
    $reply .= "📍 **Branch:** " . htmlspecialchars($currentBranchName) . "\n";
    $reply .= "• Opening Cash: **$" . number_format($openCashVal, 2) . "**\n";
    $reply .= "• Today Sales: **$" . number_format($todaySales, 2) . "** (" . $todayOrders . " Orders)\n";
    $reply .= "• Gross Profit: **$" . number_format($todayProfit, 2) . "**\n";
    $reply .= "• Inventory Value: **$" . number_format($inventorySellValue, 2) . "** (" . $totalProducts . " Products)\n";
    $reply .= "• Customer Udhaar Total: **$" . number_format($udhaarTotal, 2) . "**\n\n";
    $reply .= "💡 **Aap mujh se ye exact questions pooch sakte hain:**\n";
    $reply .= "- \"000255 ki detail batao\"\n";
    $reply .= "- \"Order # 000255 bill detail\"\n";
    $reply .= "- \"aj ali qasim name ke customer ki sale hui hai?\"\n";
    $reply .= "- \"aaj ki supplier payment kitni hai?\"\n";
    $reply .= "- \"kon sa stock purchase kiya hai?\"\n";
    $reply .= "- \"opening cash kitna hai?\"";
}

echo json_encode([
    'success' => true,
    'reply' => $reply
]);
