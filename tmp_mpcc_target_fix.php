<?php
$path = 'storage/app/mpcc_target_sync.php';
$content = file_get_contents($path);
$content = str_replace("    $targetAmount = $branchTarget ? ((float) $branchTarget->target_revenue_branch_billion * 1000000000) : 0;", "    $targetAmount = $branchTarget ? (float) $branchTarget->target_revenue_branch_billion : 0;", $content);
file_put_contents($path, $content);
