<?php
\ = ['branches.php', 'damaged_stock.php', 'returns.php', 'staff.php'];
\ = \"<?php\nsession_start();\nif (!isset(\\['user_id'])) {\n    header('Location: login.php');\n    exit();\n}\n\\ = \\['branch_id'] ?? 1;\ninclude 'includes/db.php';\n\";

foreach (\ as \) {
    \ = file_get_contents(\);
    // Find the position of includes/header.php or other includes
    // We basically want to replace everything before include 'includes/db.php';
    \ = strpos(\, \"include 'includes/header.php';\");
    if (\ !== false) {
        // Find the last include 'includes/db.php'; before this
        \ = strpos(\, \"include 'includes/db.php';\");
        if (\ !== false && \ < \) {
            // Cut everything before dbPos
            \ = \ . substr(\, \ + strlen(\"include 'includes/db.php';\"));
            file_put_contents(\, \);
            echo \"Fixed \\n\";
        }
    }
}
