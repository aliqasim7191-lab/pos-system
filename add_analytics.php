<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/super_admin.php");

// 1. Add MRR and other calculations to the top of the file
$php_logic_old = '$total_sales_res = $conn->query("SELECT SUM(total_amount) as t FROM sales");
$total_sales = $total_sales_res->fetch_assoc()[\'t\'] ?? 0;';

$php_logic_new = <<<'PHP'
$total_sales_res = $conn->query("SELECT SUM(total_amount) as t FROM sales");
$total_sales = $total_sales_res->fetch_assoc()['t'] ?? 0;

// SaaS Analytics
$mrr_query = $conn->query("SELECT subscription_plan, COUNT(id) as count FROM tenants WHERE subscription_status = 'active' GROUP BY subscription_plan");
$mrr = 0;
while($row = $mrr_query->fetch_assoc()) {
    if($row['subscription_plan'] == 'basic') $mrr += ($row['count'] * 29);
    elseif($row['subscription_plan'] == 'pro') $mrr += ($row['count'] * 49);
    elseif($row['subscription_plan'] == 'enterprise') $mrr += ($row['count'] * 99);
}

$susp_query = $conn->query("SELECT COUNT(id) as count FROM tenants WHERE subscription_status = 'suspended'");
$suspended_count = $susp_query->fetch_assoc()['count'] ?? 0;

$exp_query = $conn->query("SELECT COUNT(id) as count FROM tenants WHERE subscription_status = 'active' AND subscription_ends_at BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)");
$expiring_soon = $exp_query->fetch_assoc()['count'] ?? 0;
PHP;

$f = str_replace($php_logic_old, $php_logic_new, $f);

// 2. Replace the HTML grid
$grid_old = <<<HTML
      <div class="grid-3">
          <div class="stat-box"><h3>Active Businesses</h3><div class="num"><?php echo \$total_tenants; ?></div></div>
          <div class="stat-box" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);"><h3>Global SaaS Volume</h3><div class="num">$<?php echo number_format(\$total_sales, 2); ?></div></div>
          <div class="stat-box" style="background: linear-gradient(135deg, #10b981, #059669);"><h3>System Health</h3><div class="num">100% Online</div></div>
      </div>
HTML;

$grid_new = <<<HTML
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
          <div class="stat-box" style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">
              <h3>Active Clients</h3>
              <div class="num"><?php echo \$total_tenants; ?></div>
              <div style="font-size: 0.8rem; margin-top: 0.5rem; opacity: 0.9;">Total Businesses Using POS</div>
          </div>
          <div class="stat-box" style="background: linear-gradient(135deg, #10b981, #059669);">
              <h3>Monthly Recurring Rev</h3>
              <div class="num">$<?php echo number_format(\$mrr, 2); ?></div>
              <div style="font-size: 0.8rem; margin-top: 0.5rem; opacity: 0.9;">Estimated MRR from Active Plans</div>
          </div>
          <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
              <h3>Expiring (7 Days)</h3>
              <div class="num"><?php echo \$expiring_soon; ?></div>
              <div style="font-size: 0.8rem; margin-top: 0.5rem; opacity: 0.9;">Subscriptions needing renewal</div>
          </div>
          <div class="stat-box" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
              <h3>Suspended Accounts</h3>
              <div class="num"><?php echo \$suspended_count; ?></div>
              <div style="font-size: 0.8rem; margin-top: 0.5rem; opacity: 0.9;">Clients with blocked access</div>
          </div>
      </div>
HTML;

$f = str_replace($grid_old, $grid_new, $f);

file_put_contents("C:/xampp/htdocs/point of sale/super_admin.php", $f);
echo "SaaS Analytics Added!";
?>
