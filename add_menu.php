<?php
\ = 'C:/xampp/htdocs/point of sale/includes/header.php';
\ = file_get_contents(\);

\ = '
<style>
.show-dropdown { display: flex !important; flex-direction: column; }
#kebabDropdown a {
    color: #334155 !important;
    background: transparent !important;
    padding: 0.75rem 1.2rem;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 600;
    border-bottom: 1px solid #f1f5f9;
    display: block;
    transition: all 0.2s;
}
#kebabDropdown a:hover {
    background: #f8fafc !important;
    color: #0ea5e9 !important;
    padding-left: 1.5rem;
}
#kebabDropdown a.active {
    background: #e0f2fe !important;
    color: #0369a1 !important;
    border-left: 4px solid #0ea5e9;
}
</style>
<div style="display: flex; align-items: center; gap: 0.8rem;">
    <!-- Kebab Menu -->
    <div style="position: relative;" id="kebabMenuContainer">
        <button onclick="document.getElementById(\'kebabDropdown\').classList.toggle(\'show-dropdown\'); event.stopPropagation();" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); color: white; cursor: pointer; padding: 0.4rem; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: 0.2s;" onmouseover="this.style.background=\'rgba(255,255,255,0.25)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.15)\'">
            <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2.5"/><circle cx="12" cy="12" r="2.5"/><circle cx="12" cy="19" r="2.5"/></svg>
        </button>
        <div id="kebabDropdown" style="display: none; position: absolute; top: 120%; left: 0; background: white; min-width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-radius: 8px; z-index: 9999; padding: 0.5rem 0; max-height: 80vh; overflow-y: auto; overflow-x: hidden;">
            <!-- Menu items cloned via JS -->
        </div>
    </div>
    
    <div class="logo">
';

// Replace <div class="logo"> with our new structure
\ = str_replace('<div class="logo">', \, \);

// Also we need to close the wrapper div around logo and menu.
// The logo block ends with </div> right before <nav class="main-nav">
\ = str_replace('</div>
        <nav class="main-nav">', '</div>
    </div>
        <nav class="main-nav">', \);

// Add JS to clone nav items into dropdown, and close dropdown on outside click
\ = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const navLinks = document.querySelectorAll(".main-nav ul li a");
    const dropdown = document.getElementById("kebabDropdown");
    navLinks.forEach(link => {
        const clone = link.cloneNode(true);
        // Remove inline styles that clash
        clone.style.background = "";
        clone.style.color = "";
        clone.style.borderRadius = "";
        dropdown.appendChild(clone);
    });
});
document.addEventListener("click", function(e) {
    const dropdown = document.getElementById("kebabDropdown");
    const container = document.getElementById("kebabMenuContainer");
    if(dropdown && container && !container.contains(e.target)) {
        dropdown.classList.remove("show-dropdown");
    }
});
</script>
';
// insert JS right before closing </header>
\ = str_replace('</header>', \ . "\n</header>", \);

file_put_contents(\, \);
echo "Added kebab menu to header.php\n";
?>
