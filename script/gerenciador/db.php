<?php
// Tente encontrar e incluir o arquivo de configuração do seu painel.
// O caminho pode variar. Exemplo:
// require_once('../includes/config.php');

// Se não for possível, defina as variáveis manualmente (MENOS SEGURO):
$db_host = getenv('DB_HOST') ?: 'localhost'; // Ou o host do seu DB
$db_name = getenv('DB_NAME') ?: 'xsp_panel'; // O nome do seu banco de dados
$db_user = getenv('DB_USER') ?: 'xsp'; // Seu usuário
$db_pass = getenv('DB_PASS') ?: ''; // Sua senha

// Usando PDO para uma conexão segura
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    // Configura o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>