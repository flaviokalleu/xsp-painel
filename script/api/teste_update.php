<?php
/**
 * SCRIPT DE TESTE DEFINITIVO
 * Este script tenta executar uma única atualização no banco de dados
 * para diagnosticar problemas de conexão ou permissão.
 */

// Ativa a exibição de todos os erros para o diagnóstico.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Teste de Atualização</title>";
echo "<style>body { font-family: sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
echo "</head><body>";
echo "<h1>Teste de Atualização do Banco de Dados</h1>";

// --- 1. CONFIGURAÇÃO DO BANCO DE DADOS (a mesma que já usamos) ---
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'xsp_panel';
$db_user = getenv('DB_USER') ?: 'xsp';
$db_pass = getenv('DB_PASS') ?: '';

// --- 2. TENTATIVA DE CONEXÃO ---
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    echo "<p class='success'>SUCESSO: Conexão com o banco de dados '{$db_name}' estabelecida.</p>";
} catch (PDOException $e) {
    echo "<p class='error'>ERRO DE CONEXÃO: Não foi possível conectar ao banco de dados.</p>";
    echo "<p><strong>Detalhe do Erro:</strong> " . $e->getMessage() . "</p>";
    exit(); // Interrompe o script se a conexão falhar.
}

// --- 3. TENTATIVA DE ATUALIZAÇÃO ---
try {
    echo "<hr>";
    echo "<p>A tentar executar o seguinte comando SQL:</p>";
    echo "<pre>UPDATE `categoria` SET `position` = 999 WHERE `id` = 1</pre>";

    // Prepara o comando de atualização.
    $stmt = $pdo->prepare("UPDATE `categoria` SET `position` = :pos WHERE `id` = :id");

    // Executa o comando com valores fixos para o teste.
    $stmt->execute([
        ':pos' => 999,
        ':id'  => 1
    ]);

    // Verifica quantas linhas foram realmente alteradas.
    $affected_rows = $stmt->rowCount();

    echo "<p class='success'>SUCESSO: O comando foi executado.</p>";
    echo "<h2>Resultado: {$affected_rows} linha(s) foi/foram atualizada(s).</h2>";

    if ($affected_rows > 0) {
        echo "<p>Isto significa que o problema está na forma como os dados são enviados do JavaScript. Podemos corrigir isso!</p>";
    } else {
        echo "<p class='error'><strong>CONCLUSÃO:</strong> O comando foi executado mas não alterou nenhuma linha. Isto confirma que o problema está na configuração do seu servidor de banco de dados ou nas permissões do usuário '{$db_user}'. Por favor, verifique se este usuário tem permissão para executar comandos UPDATE na tabela 'categoria'.</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>ERRO DE ATUALIZAÇÃO: O comando SQL falhou.</p>";
    echo "<p><strong>Detalhe do Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "</body></html>";

?>
