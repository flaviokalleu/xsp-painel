<?php
// Arquivo: api/acoes_deletar.php
// Versão 1.1 - Corrigido o caminho para o arquivo de conexão do banco de dados

// ======================================================================
// CORREÇÃO APLICADA AQUI: Usando um caminho absoluto e mais seguro
// ======================================================================
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
// ======================================================================

header('Content-Type: application/json; charset=utf-8');

// Medida de segurança básica para garantir que uma ação foi enviada
if (!isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Nenhuma ação especificada.']);
    exit();
}

$action = $_POST['action'];
$conexao = conectar_bd();
$message = 'Ação não reconhecida.';
$status = 'error';

try {
    switch ($action) {
        case 'delete_streams':
            // Deleta todos os STREAMS (canais), mas não as categorias
            $conexao->exec("DELETE FROM `streams` WHERE `stream_type` = 'live'");
            $message = 'Todos os canais foram apagados com sucesso!';
            $status = 'success';
            break;

        case 'delete_movies':
            // Deleta todos os FILMES (VODs), mas não as categorias
            $conexao->exec("DELETE FROM `streams` WHERE `stream_type` = 'movie'");
            $message = 'Todos os filmes foram apagados com sucesso!';
            $status = 'success';
            break;

        case 'delete_series':
            // Deleta TODAS as séries, temporadas e episódios
            $conexao->exec("TRUNCATE TABLE `series`");
            $conexao->exec("TRUNCATE TABLE `series_seasons`");
            $conexao->exec("TRUNCATE TABLE `series_episodes`");
            $message = 'Todas as séries, temporadas e episódios foram apagados com sucesso!';
            $status = 'success';
            break;

        case 'delete_all':
            // Deleta TODO o conteúdo (canais, filmes, séries)
            // Usa TRUNCATE que é mais rápido e reseta os contadores
            $conexao->exec("TRUNCATE TABLE `streams`");
            $conexao->exec("TRUNCATE TABLE `series`");
            $conexao->exec("TRUNCATE TABLE `series_seasons`");
            $conexao->exec("TRUNCATE TABLE `series_episodes`");
            $message = 'Todo o conteúdo (canais, filmes e séries) foi apagado com sucesso!';
            $status = 'success';
            break;
    }
    echo json_encode(['status' => $status, 'message' => $message]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}

?>