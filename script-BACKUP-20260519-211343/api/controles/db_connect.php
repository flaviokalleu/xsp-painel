<?php
// ATENÇÃO: ESTE É UM CÓDIGO TEMPORÁRIO PARA DEBUG! REMOVA APÓS CORRIGIR.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function conectar_bd() {
    $endereco = getenv('DB_HOST') ?: 'localhost';
    $banco = getenv('DB_NAME') ?: 'nome_banco';
    $dbusuario = getenv('DB_USER') ?: 'nome_user';
    $dbsenha = getenv('DB_PASS') ?: 'senha_banco';

    try {
        $conexao = new PDO("mysql:host=$endereco;dbname=$banco", $dbusuario, $dbsenha);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conexao;
    } catch(PDOException $e) {
        // Esta linha irá mostrar o erro de conexão na tela
        die('Erro na conexão com o banco de dados: ' . $e->getMessage());
        return null;
    }
}
?>