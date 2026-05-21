<?php
session_start();
require_once './api/controles/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: codigos_p2p.php');
    exit();
}

$id_cliente = $_POST['id_cliente'] ?? null;
$plano_info = $_POST['plano_info'] ?? null;

if (empty($id_cliente) || empty($plano_info)) {
    $_SESSION['mensagem'] = "Erro: Informações de renovação inválidas.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: codigos_p2p.php');
    exit();
}

list($plano_id, $duracao_dias) = explode('|', $plano_info);

$conexao = conectar_bd();
if (!$conexao) {
    $_SESSION['mensagem'] = "Erro fatal: Não foi possível conectar ao banco de dados.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: codigos_p2p.php');
    exit();
}

try {
    // 1. Prepara a query de atualização com segurança
    $sql = "UPDATE clientes 
            SET Vencimento = DATE_ADD(GREATEST(Vencimento, NOW()), INTERVAL :duracao DAY),
                plano = :plano_id
            WHERE id = :id AND is_p2p = 1";
            
    $params = [
        ':duracao' => $duracao_dias,
        ':plano_id' => $plano_id,
        ':id' => $id_cliente
    ];
    
    // Se for revendedor, adiciona a verificação de segurança para garantir que ele só renove o próprio cliente
    if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0) {
        $sql .= " AND admin_id = :admin_id";
        $params[':admin_id'] = $_SESSION['admin_id'];
    }
            
    $stmt = $conexao->prepare($sql);
    
    // 2. Executa a query e verifica se alguma linha foi afetada
    if ($stmt->execute($params) && $stmt->rowCount() > 0) {
        // Busca os dados atualizados do cliente para montar a mensagem
        $stmt_cliente = $conexao->prepare("SELECT name, usuario, Vencimento FROM clientes WHERE id = :id");
        $stmt_cliente->bindParam(':id', $id_cliente);
        $stmt_cliente->execute();
        $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            // Busca o template de mensagem
            $stmt_template = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_message_template' LIMIT 1");
            $stmt_template->execute();
            $template = $stmt_template->fetchColumn();

            // Monta a mensagem final
            $vencimento_formatado = date('d/m/Y H:i', strtotime($cliente['Vencimento']));
            $placeholders = ['#cliente#', '#codigo#', '#vencimento#'];
            $values = [$cliente['name'], $cliente['usuario'], $vencimento_formatado];
            $mensagem_final = str_replace($placeholders, $values, $template);

            // Salva na sessão para exibir o modal
            $_SESSION['show_p2p_modal_message'] = $mensagem_final;
            $_SESSION['show_p2p_modal_title'] = '✅ Cliente Renovado!';
        }

    } else {
        $_SESSION['mensagem'] = "Erro: Cliente não renovado. Verifique se você tem permissão ou se os dados estão corretos.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: codigos_p2p.php');
exit();