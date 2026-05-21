<?php
// ARQUIVO: /gerenciador/teste_db.php

// Força a exibição de todos os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Iniciando teste de conexão...<br>";

// Tenta incluir o arquivo de conexão
require_once('../api/controles/db.php');

echo "Arquivo de conexão incluído com sucesso.<br>";

// Tenta usar a função de conexão
$pdo = conectar_bd();

if ($pdo) {
    echo "<b>Conexão com o banco de dados bem-sucedida!</b>";
} else {
    echo "<b>Falha ao conectar com o banco de dados.</b>";
}

?>