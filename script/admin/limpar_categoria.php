<?php
// Usando um caminho absoluto para o arquivo de conexão
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';

// Ativar a exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Validação de Segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirmacao']) || $_POST['confirmacao'] !== 'EXCLUIR TUDO' || !isset($_POST['tipo'])) {
    die("Acesso negado ou confirmação inválida. Operação cancelada.");
}

$conn = conectar_bd();
if (!$conn) {
    die("Falha fatal: Não foi possível conectar ao banco de dados.");
}

$tipo = $_POST['tipo'];
$sql = '';

// 2. Determina qual comando SQL executar com base no tipo
switch ($tipo) {
    case 'filme':
        // Apaga todos os registros do tipo 'movie' da tabela streams
        $sql = "DELETE FROM streams WHERE stream_type = 'movie'";
        break;
    case 'serie':
        // ==========================================================
        // CORREÇÃO: Apaga TODAS as temporadas (series_seasons)
        // Isso é crucial para limpar a estrutura da série.
        // ==========================================================
        $conn->exec("TRUNCATE TABLE series_seasons"); 
        
        // Apaga TODOS os episódios
        $conn->exec("TRUNCATE TABLE series_episodes");
        
        // Apaga TODAS as séries
        $sql = "TRUNCATE TABLE series";
        break;
    case 'canal':
        // Apaga todos os registros do tipo 'live' da tabela streams
        $sql = "DELETE FROM streams WHERE stream_type = 'live'";
        break;
    default:
        die("Tipo de conteúdo inválido. Operação cancelada.");
}

// 3. Executa a exclusão
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    // Se chegou aqui, a operação foi bem-sucedida
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?status=limpeza_success');
    exit;

} catch (PDOException $e) {
    // Em caso de erro
    // Redireciona com uma mensagem de erro
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?status=limpeza_error&message=' . urlencode('Erro na limpeza. Verifique o log do servidor.'));
    exit;
}