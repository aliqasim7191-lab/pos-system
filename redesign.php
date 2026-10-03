<?php
$f = file_get_contents("C:/xampp/htdocs/point of sale/index.php");

// We need to replace the entire style and HTML block for the overlay
$start = strpos($f, "<style>\n@keyframes pulseGlow");
$end = strpos($f, "<?php endif; ?>", $start);

if ($start !== false && $end !== false) {
    $length = $end - $start;
    
    $new_overlay = <<<HTML
<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes pulseLock {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(245, 158, 11, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
.shift-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(3px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.shift-modal {
    background: rgba(255, 255, 255, 0.98);
    padding: 3rem;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
    text-align: center;
    width: 440px;
    animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    position: relative;
    overflow: hidden;
}
.shift-modal::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 6px;
    background: linear-gradient(90deg, #f59e0b, #ef4444);
}
.lock-icon-container {
    width: 90px;
    height: 90px;
    background: linear-gradient(135deg, #fef3c7, #fef08a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem auto;
    border: 4px solid #fff;
    animation: pulseLock 2s infinite;
}
.lock-icon-container i {
    font-size: 2.8rem;
    color: #d97706;
}
.modal-title {
    font-size: 2rem;
    color: #0f172a;
    font-weight: 800;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
}
.modal-subtitle {
    color: #64748b;
    font-size: 1.05rem;
    line-height: 1.5;
    margin-bottom: 2rem;
    padding: 0 1rem;
}
.shift-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 1.2rem;
    font-size: 1.2rem;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
}
.shift-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px -5px rgba(16, 185, 129, 0.6);
}
</style>
<div class="shift-overlay">
    <div class="shift-modal">
        <div class="lock-icon-container">
            <i class="fa fa-lock"></i>
        </div>
        <h2 class="modal-title">Register is Locked</h2>
        <p class="modal-subtitle">For security reasons, your shift must be open to process transactions.</p>
        
        <a href="shift.php" class="shift-btn">
            <span class="btn-text">Open Register Now</span>
            <i class="fa fa-arrow-right"></i>
        </a>
    </div>
</div>
HTML;
    
    $f = substr_replace($f, $new_overlay, $start, $length);
    file_put_contents("C:/xampp/htdocs/point of sale/index.php", $f);
    echo "Modern modal applied successfully!\n";
} else {
    echo "Could not find target block.\n";
}
?>
