<?php
session_start();
require_once '../api/controles/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$id_cliente = $_POST['id_cliente'] ?? null;
$plano_info = $_POST['plano_info'] ?? null;

if (empty($id_cliente) || empty($plano_info)) {
    $_SESSION['mensagem'] = "Erro: Informações de renovação inválidas.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: index.php');
    exit();
}

list($plano_id, $duracao_dias) = explode('|', $plano_info);

$conexao = conectar_bd();
if (!$conexao) {
    // ... erro de conexão
    header('Location: index.php');
    exit();
}

try {
    $sql = "UPDATE clientes 
            SET Vencimento = DATE_ADD(GREATEST(Vencimento, NOW()), INTERVAL :duracao DAY),
                plano = :plano_id
            WHERE id = :id";
            
    $stmt = $conexao->prepare($sql);
    
    $stmt->bindParam(':duracao', $duracao_dias, PDO::PARAM_INT);
    $stmt->bindParam(':plano_id', $plano_id);
    $stmt->bindParam(':id', $id_cliente, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // --- NOVA LÓGICA PARA GERAR A MENSAGEM ---
        
        // 1. Buscar os dados atualizados do cliente
        $stmt_cliente = $conexao->prepare("SELECT name, usuario, Vencimento FROM clientes WHERE id = :id");
        $stmt_cliente->bindParam(':id', $id_cliente);
        $stmt_cliente->execute();
        $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            // 2. Formatar a data de vencimento
            $vencimento_formatado = date('d/m/Y H:i', strtotime($cliente['Vencimento']));
            
            // 3. Montar a mensagem completa
            // Usamos \n para quebras de linha que funcionarão no WhatsApp e na cópia.
            $mensagem_completa = "Plano P2P renovado com sucesso!\n\n";
            $mensagem_completa .= "👤 Cliente: " . $cliente['name'] . "\n";
            $mensagem_completa .= "🔑 Código: " . $cliente['usuario'] . "\n";
            $mensagem_completa .= "📅 Próximo vencimento: " . $vencimento_formatado;

            // 4. Salvar a mensagem na sessão para ser exibida na página principal
            $_SESSION['mensagem_renovacao'] = $mensagem_completa;
        } else {
            $_SESSION['mensagem'] = "Cliente renovado, mas não foi possível gerar a mensagem.";
            $_SESSION['msg_type'] = "alert-warning";
        }

    } else {
        $_SESSION['mensagem'] = "Erro ao renovar o cliente.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: index.php');
exit();