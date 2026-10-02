<?php $data = json_decode(file_get_contents('first_card.txt'), true); echo substr($data['content'], 0, 3000); ?>
