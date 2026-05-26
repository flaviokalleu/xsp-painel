<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// --- CAMINHO CORRIGIDO AQUI ---
// O correto é apenas um '../' para subir da pasta 'clientes_p2p' para a raiz.
require_once '../api/controles/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: ../codigos_p2p.php'); // Volta para a lista principal
    exit();
}

$conexao = conectar_bd();
if (!$conexao) {
    // Adiciona uma mensagem de erro se a conexão falhar
    $_SESSION['mensagem'] = "Erro fatal: Não foi possível conectar ao banco de dados.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: ../codigos_p2p.php');
    exit();
}

try {
    $sql = "DELETE FROM clientes WHERE id = :id AND is_p2p = 1";
    $params = [':id' => $id];

    // Se for revendedor, adiciona a verificação de segurança para garantir que ele só apague o próprio cliente
    if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $_SESSION['admin_id']; // Usando a chave correta da sessão
    }
    
    $stmt = $conexao->prepare($sql);
    
    if ($stmt->execute($params) && $stmt->rowCount() > 0) {
        $_SESSION['mensagem'] = "Usuário excluído com sucesso.";
        $_SESSION['msg_type'] = "alert-success";
    } else {
        $_SESSION['mensagem'] = "Erro: Usuário não encontrado ou você não tem permissão para excluí-lo.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: ../codigos_p2p.php');
exit();