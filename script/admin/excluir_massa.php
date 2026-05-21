<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';

// ==========================================================
// 1. CORREÇÃO: Lógica para obter a URL de redirecionamento segura
// ==========================================================
// Tenta obter a URL da página anterior. Se falhar, usa a raiz.
$redirect_url = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/index.php';
// Determina qual separador usar (? ou &)
$separator = strpos($redirect_url, '?') !== false ? '&' : '?';


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ids']) || !is_array($_POST['ids'])) {
    // Redireciona de volta se a requisição não for POST ou não houver IDs
    header('Location: ' . $redirect_url);
    exit;
}

$ids_sanitizados = array_map('intval', $_POST['ids']);
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
$placeholders = implode(',', array_fill(0, count($ids_sanitizados), '?'));

try {
    $conn = conectar_bd();

    if ($tipo === 'filme' && count($ids_sanitizados) > 0) {
        $stmt = $conn->prepare("DELETE FROM streams WHERE id IN ($placeholders) AND stream_type = 'movie'");
        $stmt->execute($ids_sanitizados);
    } elseif ($tipo === 'serie' && count($ids_sanitizados) > 0) {
        $conn->beginTransaction();
        
        // Apaga episódios
        $stmt_ep = $conn->prepare("DELETE FROM series_episodes WHERE series_id IN ($placeholders)");
        $stmt_ep->execute($ids_sanitizados);
        
        // CORREÇÃO: Apaga as temporadas (series_seasons)
        $stmt_ss = $conn->prepare("DELETE FROM series_seasons WHERE series_id IN ($placeholders)");
        $stmt_ss->execute($ids_sanitizados);
        
        // Apaga a série principal
        $stmt_serie = $conn->prepare("DELETE FROM series WHERE id IN ($placeholders)");
        $stmt_serie->execute($ids_sanitizados);
        
        $conn->commit();
    } elseif ($tipo === 'canal' && count($ids_sanitizados) > 0) {
        $stmt = $conn->prepare("DELETE FROM streams WHERE id IN ($placeholders) AND stream_type = 'live'");
        $stmt->execute($ids_sanitizados);
    }

    // ==========================================================
    // 2. CORREÇÃO: Redireciona de volta com a URL construída corretamente
    // ==========================================================
    header('Location: ' . $redirect_url . $separator . 'status=success_massa');
    exit;

} catch (PDOException $e) {
    // Em caso de erro, desfaz a transação
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // ==========================================================
    // 3. CORREÇÃO: Redireciona com erro e URL construída corretamente
    // ==========================================================
    $error_message = urlencode("Falha na exclusão em massa. Tente novamente.");
    header('Location: ' . $redirect_url . $separator . 'status=error_massa&message=' . $error_message);
    exit;
} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}