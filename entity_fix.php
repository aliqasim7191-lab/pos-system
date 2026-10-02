<?php
// Function to replace raw emojis with HTML entities in a file
function fix_emojis($file) {
    $f = file_get_contents($file);
    $map = [
        "\u{1F7E2}" => "&#x1F7E2;",
        "\u{1F534}" => "&#x1F534;",
        "\u{1F6D2}" => "&#x1F6D2;",
        "\u{1F4CA}" => "&#x1F4CA;",
        "\u{1F4C8}" => "&#x1F4C8;",
        "\u{1F381}" => "&#x1F381;",
        "\u{1F310}" => "&#x1F310;",
        "\u{1F465}" => "&#x1F465;",
        "\u{1F4BC}" => "&#x1F4BC;",
        "\u{2699}\u{FE0F}" => "&#x2699;&#xFE0F;",
        "\u{1F50C}" => "&#x1F50C;",
        "\u{1F4DC}" => "&#x1F4DC;",
        "\u{1F3E2}" => "&#x1F3E2;",
        "\u{1F319}" => "&#x1F319;"
    ];
    
    foreach ($map as $emoji => $entity) {
        $f = str_replace($emoji, $entity, $f);
    }
    
    file_put_contents($file, $f);
}

fix_emojis("C:/xampp/htdocs/point of sale/index.php");
fix_emojis("C:/xampp/htdocs/point of sale/includes/header.php");
echo "Replaced all emojis with HTML entities!";
?>
