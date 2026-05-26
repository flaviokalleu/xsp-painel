<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once './api/controles/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: codigos_p2p.php');
    exit();
}

// 1. Validar e obter dados do formulário
$name = trim($_POST['name'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$plano_info = explode('|', $_POST['plano_info'] ?? '');
$plano_id = $plano_info[0] ?? null;
$plano_duracao = $plano_info[1] ?? 30;

if (empty($name) || empty($plano_id)) {
    $_SESSION['mensagem'] = "Erro: Nome e Plano são obrigatórios.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: p2p_criar.php');
    exit();
}

$conexao = conectar_bd();
if (!$conexao) {
    $_SESSION['mensagem'] = "Erro fatal: Não foi possível conectar ao banco de dados.";
    $_SESSION['msg_type'] = "alert-error";
    header('Location: codigos_p2p.php');
    exit();
}

try {
    // 2. Busca a senha P2P do local correto: tabela `settings`
    $stmt_senha = $conexao->prepare("SELECT setting_value FROM settings WHERE setting_name = 'p2p_default_password' LIMIT 1");
    $stmt_senha->execute();
    $senha_p2p = $stmt_senha->fetchColumn();

    if (!$senha_p2p) {
        $_SESSION['mensagem'] = "Erro Crítico: A senha padrão P2P não foi encontrada na tabela 'settings'.";
        $_SESSION['msg_type'] = "alert-error";
        header('Location: codigos_p2p.php');
        exit();
    }

    // 3. Gerar um código de usuário único
    do {
        $codigo_usuario = mt_rand(10000000, 99999999);
        $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = :usuario");
        $stmt_check->bindParam(':usuario', $codigo_usuario);
        $stmt_check->execute();
    } while ($stmt_check->fetchColumn());

    // 4. Calcular data de vencimento
    $vencimento = date('Y-m-d H:i:s', strtotime("+$plano_duracao days"));
    
    // 5. Pega o ID do criador da sessão (admin ou revendedor)
    $creator_id = $_SESSION['admin_id'];

    // 6. Prepara e executa a inserção no banco
    $sql = "INSERT INTO clientes (usuario, senha, name, whatsapp, plano, is_p2p, Criado_em, Vencimento, conexoes, admin_id) 
            VALUES (:usuario, :senha, :name, :whatsapp, :plano, 1, NOW(), :vencimento, 1, :admin_id)"; 
            
    $stmt_insert = $conexao->prepare($sql);
    
    $stmt_insert->bindParam(':usuario', $codigo_usuario);
    $stmt_insert->bindParam(':senha', $senha_p2p);
    $stmt_insert->bindParam(':name', $name);
    $stmt_insert->bindParam(':whatsapp', $whatsapp);
    $stmt_insert->bindParam(':plano', $plano_id);
    $stmt_insert->bindParam(':vencimento', $vencimento);
    $stmt_insert->bindParam(':admin_id', $creator_id);

    if ($stmt_insert->execute()) {
        // Busca o template de mensagem no banco
        $stmt_template = $conexao->prepare("SELECT valor FROM configuracoes WHERE chave = 'p2p_message_template' LIMIT 1");
        $stmt_template->execute();
        $template = $stmt_template->fetchColumn();

        // Prepara as variáveis para substituição
        $vencimento_formatado = date('d/m/Y H:i', strtotime($vencimento));
        $placeholders = ['#cliente#', '#codigo#', '#vencimento#'];
        $values = [$name, $codigo_usuario, $vencimento_formatado];

        // Substitui as variáveis e cria a mensagem final
        $mensagem_final = str_replace($placeholders, $values, $template);

        // Salva a mensagem na sessão para exibir o modal na próxima página
        $_SESSION['show_p2p_modal_message'] = $mensagem_final;
        $_SESSION['show_p2p_modal_title'] = '✅ Cliente P2P Criado!';

    } else {
        $_SESSION['mensagem'] = "Erro ao criar usuário.";
        $_SESSION['msg_type'] = "alert-error";
    }

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro de banco de dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "alert-error";
}

header('Location: codigos_p2p.php');
exit();