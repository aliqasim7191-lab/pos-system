<?php
\ = ['branches.php', 'damaged_stock.php', 'returns.php', 'staff.php'];
foreach (\ as \) {
    \ = file_get_contents(\);
    \ = strpos(\, '$code');
    if (\ !== false) {
        // find the original while loop or if it's damaged_stock etc
        \ = strpos(\, '<?php while', \);
        if (\ === false) {
            \ = strpos(\, '<?php foreach', \);
        }
        if (\ === false) {
            \ = strpos(\, '<?php if', \);
        }
        
        if (\ !== false) {
            // we want to cut out from  up to the <tbody> before the 
            // Actually, $code is already inside <tbody>. The next real thing is <?php while inside <tbody>.
            // The corrupted text includes a duplicate of the header, the form, the table header, and <tbody>.
            // So if we just find the last <tbody> before $end, we want to remove everything from $code up to that <tbody> (including it).
            \ = strrpos(substr(\, 0, \), '<tbody>');
            if (\ !== false && \ > \) {
                // Remove from  to  + strlen('<tbody>')
                \ = substr(\, 0, \) . substr(\, \ + 7);
                file_put_contents(\, \);
                echo "Cleaned \\n";
            }
        }
    }
}
