<?php
// gerar_teste.php (VERSÃO FINAL COM ID DO ADMIN)

header('Content-Type: application/json');
require_once 'api/controles/db.php';

$seu_dominio = "http://drikcdn.online:80";
$duracao_teste_horas = 4;

// **INÍCIO DA MUDANÇA**
// Pega o ID do admin enviado pela API do chatbot
$admin_id_do_teste = $_GET['admin_id'] ?? null;

if (empty($admin_id_do_teste)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ID do administrador nao foi fornecido.']);
    exit;
}
// **FIM DA MUDANÇA**

$conexao = conectar_bd();
if (!$conexao) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro fatal: Nao foi possivel conectar ao banco de dados.']);
    exit;
}

try {
    $usuario_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8));
    $senha_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6));
    $data_criacao = date('Y-m-d H:i:s');
    $data_vencimento = date('Y-m-d H:i:s', strtotime("+$duracao_teste_horas hours"));

    // **INÍCIO DA MUDANÇA**
    // Adicionamos a coluna `admin_id` na instrução SQL
    $sql = "INSERT INTO clientes (admin_id, usuario, senha, criado_em, vencimento, is_trial, conexoes, bloqueio_conexao) VALUES (?, ?, ?, ?, ?, 1, 1, 'nao')";
    $stmt = $conexao->prepare($sql);

    // E passamos o ID do admin ao executar
    if ($stmt->execute([$admin_id_do_teste, $usuario_teste, $senha_teste, $data_criacao, $data_vencimento])) {
    // **FIM DA MUDANÇA**
        
        $link_m3u_encurtado = "{$seu_dominio}/t/" . base64_encode($usuario_teste);
        $link_ssiptv_encurtado = "{$seu_dominio}/e/" . base64_encode($senha_teste);

        $dados_teste = [
            'sucesso'       => true,
            'usuario'       => $usuario_teste,
            'senha'         => $senha_teste,
            'vencimento'    => date('d/m/Y H:i', strtotime($data_vencimento)),
            'url_servidor'  => $seu_dominio,
            'link_m3u_encurtado' => $link_m3u_encurtado,
            'link_ssiptv_encurtado' => $link_ssiptv_encurtado
        ];
        
        echo json_encode($dados_teste);

    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao inserir o usuário de teste no banco de dados.']);
    }

} catch (PDOException $e) {
    error_log('Erro na query de teste rapido: ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Ocorreu um erro no servidor ao gerar o teste.']);
}

$conexao = null;
?>