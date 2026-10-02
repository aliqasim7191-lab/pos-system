<?php
$files = [
    'Wireless Earbuds' => ['wireless_earbuds.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\dfda466b-e79f-4ca5-86d5-1739bdb48ffd\wireless_earbuds_1786964454537.jpg'],
    'Power Bank' => ['power_bank.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\dfda466b-e79f-4ca5-86d5-1739bdb48ffd\power_bank_1786964469457.jpg'],
    'Bluetooth Speaker' => ['bluetooth_speaker.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\dfda466b-e79f-4ca5-86d5-1739bdb48ffd\bluetooth_speaker_1786964482326.jpg'],
    'Vitamin C' => ['vitamin_c.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\d4eaff32-3ea0-4788-a322-39c8bbe2970d\vitamin_c_1786964461658.jpg'],
    'Pain Reliever' => ['pain_reliever.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\d4eaff32-3ea0-4788-a322-39c8bbe2970d\pain_reliever_1786964479704.jpg'],
    'Bread Pack' => ['bread_pack.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\9579d1e9-e6d2-40e0-b114-36218ac96e9c\bread_pack_1786964441611.jpg'],
    'Organic Apples' => ['organic_apples.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\9579d1e9-e6d2-40e0-b114-36218ac96e9c\organic_apples_1786964462036.jpg'],
    'Eggs Dozen' => ['eggs_dozen.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\9579d1e9-e6d2-40e0-b114-36218ac96e9c\eggs_dozen_1786964476428.jpg'],
    'Orange Juice' => ['orange_juice.jpg', 'C:\Users\aliq3\.gemini\antigravity\brain\9579d1e9-e6d2-40e0-b114-36218ac96e9c\orange_juice_1786964490073.jpg']
];

include 'includes/db.php';
foreach ($files as $name => $info) {
    $target = 'uploads/' . $info[0];
    if (copy($info[1], $target)) {
        $stmt = $conn->prepare("UPDATE products SET image=? WHERE name=?");
        $stmt->bind_param("ss", $info[0], $name);
        $stmt->execute();
        echo "Successfully updated $name\n";
    } else {
        echo "Failed to copy image for $name\n";
    }
}
?>
