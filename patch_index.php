<?php
\ = 'C:/xampp/htdocs/point of sale/index.php';
\ = file_get_contents(\);

\ = <<<EOD
              <?php foreach(\ as \): ?>
                  <?php \ = isset(\['wholesale_price']) ? \['wholesale_price'] : \['price']; ?>
                  <div class="product-card" data-category="<?php echo htmlspecialchars(\['category'] ?? ''); ?>" onclick="openProductSelectionModal(<?php echo \['id']; ?>, '<?php echo addslashes(htmlspecialchars(\['name'])); ?>', <?php echo \['price']; ?>, <?php echo \; ?>, <?php echo floatval(\['stock']); ?>, '<?php echo \['unit']; ?>')">
EOD;

\ = <<<EOD
              <?php foreach(\ as \): ?>
                  <?php 
                    \ = isset(\['wholesale_price']) ? \['wholesale_price'] : \['price']; 
                    \ = floatval(\['stock']) >= 10;
                  ?>
                  <div class="product-card" data-category="<?php echo htmlspecialchars(\['category'] ?? ''); ?>" <?php if(\): ?>onclick="openProductSelectionModal(<?php echo \['id']; ?>, '<?php echo addslashes(htmlspecialchars(\['name'])); ?>', <?php echo \['price']; ?>, <?php echo \; ?>, <?php echo floatval(\['stock']); ?>, '<?php echo \['unit']; ?>')"<?php else: ?>style="opacity: 0.6; cursor: not-allowed;"<?php endif; ?>>
EOD;

\ = str_replace(\, \, \);

\ = <<<EOD
                              <button class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.3rem 0.6rem; font-size: 0.85rem; border-radius: 4px; font-weight: 600;" onclick="event.stopPropagation(); openProductSelectionModal(<?php echo \['id']; ?>, '<?php echo addslashes(htmlspecialchars(\['name'])); ?>', <?php echo \['price']; ?>, <?php echo \; ?>, <?php echo floatval(\['stock']); ?>, '<?php echo \['unit']; ?>')">+ Add</button>
EOD;

\ = <<<EOD
                              <?php if(\): ?>
                              <button class="btn" style="background: #eff6ff; color: #2563eb; padding: 0.3rem 0.6rem; font-size: 0.85rem; border-radius: 4px; font-weight: 600;" onclick="event.stopPropagation(); openProductSelectionModal(<?php echo \['id']; ?>, '<?php echo addslashes(htmlspecialchars(\['name'])); ?>', <?php echo \['price']; ?>, <?php echo \; ?>, <?php echo floatval(\['stock']); ?>, '<?php echo \['unit']; ?>')">+ Add</button>
                              <?php else: ?>
                              <button class="btn" style="background: #fee2e2; color: #ef4444; padding: 0.3rem 0.6rem; font-size: 0.85rem; border-radius: 4px; font-weight: 600; cursor: not-allowed;" onclick="event.stopPropagation();">Low Stock</button>
                              <?php endif; ?>
EOD;

\ = str_replace(\, \, \);
file_put_contents(\, \);
echo "Done";
?>
