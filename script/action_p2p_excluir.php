<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once './api/controles/db.php';

$id = $_GET['id'] ?? null;
$redirect_page = $_GET['from'] ?? 'codigos_p2p.php'; // Página para onde voltar

if (!$id) {
    header("Location: $redirect_page");
    exit();
}

$conexao = conectar_bd();
if (!$conexao) {
    // ... erro de conexão
    header("Location: $redirect_page");
    exit();
}

try {
    $sql = "DELETE FROM clientes WHERE id = :id AND is_p2p = 1";
    $params = [':id' => $id];

    // Se for revendedor, garante que ele só apague o próprio cliente/teste
    if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $_SESSION['admin_id'];
    }
    
    $stmt = $conexao->prepare($sql);
    
    if ($stmt->execute($params) && $stmt->rowCount() > 0) {
        $_SESSION['mensagem'] = "Item excluído com sucesso.";
        $_SESSION['msg_type'] = "alert-success";
    } else {
        $_SESSION['mensagem'] = "Erro: Item não encontrado ou você não tem permissão para excluí-lo.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

// Redireciona de volta para a página de origem
header("Location: $redirect_page");
exit();