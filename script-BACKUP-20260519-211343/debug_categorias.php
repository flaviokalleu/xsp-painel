<?php
// Inclua a conexão com o banco de dados
require_once('./api/controles/db_connect.php');
$conexao = conectar_bd();

if (!$conexao) {
    die("Erro ao conectar com o banco de dados.");
}

$stmt = $conexao->prepare("SELECT id, nome, type FROM categoria ORDER BY position ASC");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Categorias da Tabela:</h1>";
echo "<pre>";
print_r($categorias);
echo "</pre>";
?>