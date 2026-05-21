<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Erro: ID inválido ou não fornecido.");
}

$id_para_excluir = intval($_GET['id']);
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// ==========================================================
// 1. Lógica para obter a URL de redirecionamento segura
// Garante que o separador seja ? ou &
// ==========================================================
$redirect_url = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/index.php';
$separator = strpos($redirect_url, '?') !== false ? '&' : '?';

try {
    $conn = conectar_bd();

    if ($tipo === 'serie') {
        // Bloco Série: Requer transação para múltiplos DELETEs
        $conn->beginTransaction();
        
        // 1. Apaga os episódios (series_episodes)
        $stmt_ep = $conn->prepare("DELETE FROM series_episodes WHERE series_id = :id");
        $stmt_ep->bindParam(':id', $id_para_excluir, PDO::PARAM_INT);
        $stmt_ep->execute();
        
        // 2. Apaga as temporadas (series_seasons)
        $stmt_ss = $conn->prepare("DELETE FROM series_seasons WHERE series_id = :id");
        $stmt_ss->bindParam(':id', $id_para_excluir, PDO::PARAM_INT);
        $stmt_ss->execute();
        
        // 3. Apaga a série principal
        $stmt_serie = $conn->prepare("DELETE FROM series WHERE id = :id");
        $stmt_serie->bindParam(':id', $id_para_excluir, PDO::PARAM_INT);
        $stmt_serie->execute();
        
        $conn->commit();
        
    } elseif ($tipo === 'filme') {
        // Bloco Filme: DELETE simples
        $stmt = $conn->prepare("DELETE FROM streams WHERE id = :id AND stream_type = 'movie'");
        $stmt->bindParam(':id', $id_para_excluir, PDO::PARAM_INT);
        $stmt->execute();
        
    } elseif ($tipo === 'canal') {
        // Bloco Canal: DELETE simples
        $stmt = $conn->prepare("DELETE FROM streams WHERE id = :id AND stream_type = 'live'");
        $stmt->bindParam(':id', $id_para_excluir, PDO::PARAM_INT);
        $stmt->execute();
    }

    // ==========================================================
    // 2. Redirecionamento de Sucesso (APÓS todas as operações)
    // ==========================================================
    header('Location: ' . $redirect_url . $separator . 'status=success');
    exit;

} catch (PDOException $e) {
    // Em caso de erro, desfaz a transação e exibe o erro
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // ==========================================================
    // 3. Redirecionamento de Erro (APÓS o Rollback)
    // ==========================================================
    $error_message = urlencode("Falha na exclusão. Tente novamente.");
    header('Location: ' . $redirect_url . $separator . 'status=error&message=' . $error_message);
    exit;
} catch (Exception $e) {
    die("Erro fatal: " . $e->getMessage());
}