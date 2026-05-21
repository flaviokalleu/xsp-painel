<?php
// Este script vai listar os arquivos na pasta pai (um nível acima)
$parent_dir = realpath(__DIR__ . '/..');

if ($parent_dir !== false) {
    echo "<h1>Conteúdo da pasta pai:</h1>";
    $files = scandir($parent_dir);
    echo "<pre>";
    print_r($files);
    echo "</pre>";
} else {
    echo "Não foi possível encontrar a pasta pai.";
}
?>