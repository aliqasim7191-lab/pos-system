<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

$bad = <<<HTML
                        <div id="customer-points-display" style="display:none; font-size: 0.75rem; font-weight: bold; color: #10b981; background: #dcfce7; padding: 2px 6px; border-radius: 4px;">⭐ <span id="customer-points-val">0</span> Pts</div>
                </div>
            </div>
            <select id="customerSelect"
HTML;

$good = <<<HTML
                        <div id="customer-points-display" style="display:none; font-size: 0.75rem; font-weight: bold; color: #10b981; background: #dcfce7; padding: 2px 6px; border-radius: 4px;">⭐ <span id="customer-points-val">0</span> Pts</div>
                    </div>
                </div>
            <select id="customerSelect"
HTML;

$f = str_replace($bad, $good, $f);
file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
echo "Missing div restored!";
?>
