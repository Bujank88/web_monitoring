<?php
$path = 'app/Http/Controllers/BackController.php';
$content = file_get_contents($path);
$content = str_replace("                $target = (float) ($targetByUser[$mpccUser->id]->target_amount ?? 0);", "                $target = (float) ($targetByUser[$mpccUser->id]->target_amount ?? 0);", $content);
file_put_contents($path, $content);
