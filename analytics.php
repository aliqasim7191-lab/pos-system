<?php
include 'includes/db.php';
include 'includes/header.php';

if (!$isManagerOrAdmin) {
    echo "<div class='container' style='padding:2rem;text-align:center;'><h2>Access Denied</h2><p>Only Managers and Admins can view analytics.</p></div>";
    include 'includes/footer.php';
    exit();
}

$bF = $isAdmin ? "" : "WHERE branch_id = $current_branch_id";
$bF_AND = $isAdmin ? "" : "AND branch_id = $current_branch_id";

// Data for Charts
// 1. Last 7 Days Sales
$last7Days = [];
for ($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $q = $conn->query("SELECT SUM(total_amount) as total FROM sales WHERE DATE(created_at) = '$date' $bF_AND");
    $row = $q->fetch_assoc();
    $last7Days[$date] = $row['total'] ?: 0;
}

// 2. Top 5 Selling Products
$topProducts = [];
$tQ = $conn->query("
    SELECT p.name, SUM(si.quantity) as qty
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.branch_id = $current_branch_id OR " . ($isAdmin ? "1" : "0") . "
    GROUP BY p.id
    ORDER BY qty DESC
    LIMIT 5
");
if($tQ) {
    while($r = $tQ->fetch_assoc()){
        $topProducts[] = $r;
    }
}

// 3. AI Insights Logic (Basic)
$insights = [];
// Check low stock
$lsQ = $conn->query("SELECT COUNT(*) as c FROM products WHERE stock < 10 $bF_AND");
if($lsQ && $lsQ->fetch_assoc()['c'] > 0) {
    $insights[] = "You have products running low on stock. Consider reordering soon to prevent lost sales.";
}
// Check high udhaar
$uhQ = $conn->query("SELECT SUM(outstanding_balance) as tot FROM customers");
$uh = $uhQ ? $uhQ->fetch_assoc()['tot'] : 0;
if($uh > 5000) {
    $insights[] = "High outstanding credit detected ($".number_format($uh, 2)."). Focus on debt collection this week.";
}
// Check best day
$bestDay = max($last7Days);
if($bestDay > 0) {
    $bestDate = array_search($bestDay, $last7Days);
    $insights[] = "Your best sales day this week was ".date('l', strtotime($bestDate))." ($".number_format($bestDay, 2).").";
}

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h2>Advanced Analytics & BI</h2>
    <p>AI-driven insights and deep analytics for your business.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    
    <!-- Sales Trend -->
    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1rem; color: #334155;">7-Day Sales Trend</h3>
        <canvas id="salesChart"></canvas>
    </div>

    <!-- Top Products -->
    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1rem; color: #334155;">Top Selling Products</h3>
        <canvas id="productsChart"></canvas>
    </div>

</div>

<!-- AI Business Assistant Section -->
<div style="background: linear-gradient(135deg, #4f46e5, #3b82f6); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); color: white;">
    <h3 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
        <span style="font-size: 1.5rem;">✨</span> AI Business Insights
    </h3>
    <ul style="list-style: none; padding: 0; margin: 0;">
        <?php foreach($insights as $insight): ?>
            <li style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem; font-size: 1.05rem;">
                <?php echo $insight; ?>
            </li>
        <?php endforeach; ?>
        <?php if(empty($insights)): ?>
            <li style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px;">No major insights generated yet. Keep selling!</li>
        <?php endif; ?>
    </ul>
</div>

<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($last7Days)); ?>,
        datasets: [{
            label: 'Sales Revenue ($)',
            data: <?php echo json_encode(array_values($last7Days)); ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Products Chart
const prodCtx = document.getElementById('productsChart').getContext('2d');
new Chart(prodCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($topProducts, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($topProducts, 'qty')); ?>,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
