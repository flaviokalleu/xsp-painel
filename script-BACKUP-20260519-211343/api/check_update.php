<?php
header('Content-Type: application/json');

// Defina a versão atual do painel aqui
$currentVersion = '4.1.23';

// Defina a última versão disponível e o URL para o arquivo de changelog correspondente
$latestVersion = '4.1.18';
$patchUrl = './changelog.html';

// Verifica se há uma atualização disponível
$updateAvailable = (version_compare($currentVersion, $latestVersion, '<'));

echo json_encode([
    'current_version' => $currentVersion,
    'latest_version' => $latestVersion,
    'update_available' => $updateAvailable,
    'patch_url' => $patchUrl
]);

?>