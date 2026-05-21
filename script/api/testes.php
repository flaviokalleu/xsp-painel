<?php
session_start();
require_once('./controles/db.php');
require_once('./controles/clientes.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['gerar_teste_rapido'])) {
        $conexao = conectar_bd();
        $admin_id = $_SESSION['admin_id'] ?? null;
        if (empty($admin_id)) { exit(json_encode(['title' => 'Erro!', 'msg' => 'Sessão inválida.', 'icon' => 'error'])); }
        $custo_credito_teste = 0; // Configurado para não cobrar créditos pelo Teste Rápido
        // Duração do Teste Rápido: Mantida fixa em 4 horas.
        $duracao_teste_horas = 4; 
        $plano_id_teste = 64; // ID fixo para o plano de teste
        try {
            $stmt_admin = $conexao->prepare("SELECT admin, creditos FROM admin WHERE id = ?");
            $stmt_admin->execute([$admin_id]);
            $admin_info = $stmt_admin->fetch();
            if ($admin_info['admin'] != 1 && $admin_info['creditos'] < $custo_credito_teste) {
                exit(json_encode(['title' => 'Erro!', 'msg' => 'Créditos insuficientes.', 'icon' => 'error']));
            }
            $usuario_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8));
            $senha_teste = strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6)); 
            $data_vencimento = date('Y-m-d H:i:s', strtotime("+$duracao_teste_horas hours"));
            
            $sql = "INSERT INTO clientes (admin_id, name, usuario, senha, plano, conexoes, Vencimento, is_trial, Criado_em) 
                    VALUES (:admin_id, :name, :usuario, :senha, :plano, 1, :vencimento, 1, NOW())";
            $stmt_insert = $conexao->prepare($sql);
            $params = [ ':admin_id' => $admin_id, ':name' => "Teste Rápido", ':usuario' => $usuario_teste, ':senha' => $senha_teste, ':plano' => $plano_id_teste, ':vencimento' => $data_vencimento ];
            
            $conexao->beginTransaction();
            $stmt_insert->execute($params);
            
            if ($admin_info['admin'] != 1) { // Verifica se NÃO é o admin principal (ID 1)
                if ($custo_credito_teste > 0) { // Garante que só desconte se o custo for > 0
                    $conexao->prepare("UPDATE admin SET creditos = creditos - ? WHERE id = ?")->execute([$custo_credito_teste, $admin_id]);
                    $sql_log = "INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (?, ?, ?, ?, ?)";
                    $conexao->prepare($sql_log)->execute([$admin_id, $admin_id, -$custo_credito_teste, time(), "Criação de Teste Rápido: " . $usuario_teste]);
                }
            }
            $conexao->commit();
            
            $template_file = $_SERVER['DOCUMENT_ROOT'] . '/template_mensagem.txt';
            $template = file_exists($template_file) ? file_get_contents($template_file) : "Usuário: #username#\nSenha: #password#";
            $portal_url = 'http://' . $_SERVER['HTTP_HOST'];
            $exp_date_formatted = date('d/m/Y H:i', strtotime($data_vencimento));
            $replacements = [ 
                '#username#' => $usuario_teste, 
                '#password#' => $senha_teste, 
                '#url#' => $portal_url, 
                '#exp_date#' => $exp_date_formatted, 
                '#m3u_link#' => $portal_url.'/get.php?username='.$usuario_teste.'&password='.$senha_teste.'&type=m3u_plus&output=ts' 
            ];
            $mensagem_final = str_replace(array_keys($replacements), array_values($replacements), $template);
            $modal_body_html = '<div style="white-space: pre-wrap; font-family: monospace;">' . nl2br(htmlspecialchars($mensagem_final)) . '</div>';
            
            $texto_para_copiar = htmlspecialchars($mensagem_final, ENT_QUOTES, 'UTF-8');
            
            $modal_footer = "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Fechar</button>" . "<button type='button' class='btn btn-success' onclick='navigator.clipboard.writeText(`{$texto_para_copiar}`).then(() => SweetAlert3(\"Dados copiados!\", \"success\"))'><i class='fab fa-whatsapp'></i> Copiar</button>";
            
            echo json_encode([ 
                'modal_titulo' => 'Teste Rápido Criado (' . $usuario_teste . ')', 
                'modal_body' => $modal_body_html, 
                'modal_footer' => $modal_footer 
            ]);
        } catch (Exception $e) {
            $conexao->rollBack();
            error_log("Erro no Teste Rápido: " . $e->getMessage());
            echo json_encode(['title' => 'Erro!', 'msg' => 'Ocorreu um erro no servidor.', 'icon' => 'error']);
        }
        exit();
    }
    
    // Supondo que 'add_cliente' é o endpoint de criação de teste/cliente via modal
    if (isset($_POST['add_cliente'])) {
        $data = $_POST['add_cliente'];
        $nome = $data['nome'] ?? ''; $usuario = $data['usuario'] ?? ''; $senha = $data['senha'] ?? '';
        
        // --- REGRA DE FIXAÇÃO: A DURAÇÃO É FIXADA EM 4 HORAS, IGNORANDO O INPUT DO USUÁRIO ---
        $duracao = 4;
        // As linhas de limite de 12 horas foram removidas.
        // --- FIM DA REGRA DE FIXAÇÃO ---

        // Pega o limite de conexões, que deve ser fixo em 1 para um teste
        $limite = $data['limite'] ?? 1;

        // --- REGRA DE PLANO: FORÇA O PLANO A SER O ID DE TESTE (64) PARA SEGURANÇA ---
        $plano_id = 64; 
        // --- FIM DA REGRA DE PLANO ---
        
        if (function_exists('add_cliente')) { echo json_encode(add_cliente($nome, $usuario, $senha, $plano_id, $duracao, $limite)); exit(); }
    }

    if (isset($_POST['adicionar_clientes'])) {
        if (function_exists('adicionar_clientes')) { echo json_encode(adicionar_clientes()); exit(); }
    }
    if (isset($_POST['confirme_adicionar_clientes'])) {
        if (function_exists('confirme_adicionar_clientes')) { echo json_encode(confirme_adicionar_clientes($_POST)); exit(); }
    }
    if (isset($_POST['adicionar_testes'])) {
        if (function_exists('adicionar_testes')) { echo json_encode(adicionar_testes()); exit(); }
    }
    if (isset($_POST['confirme_adicionar_testes'])) {
        if (function_exists('confirme_adicionar_testes')) { echo json_encode(confirme_adicionar_testes($_POST)); exit(); }
    }
    if (isset($_POST['info_cliente'])) {
        $id = $_POST['info_cliente'];
        if (function_exists('info_cliente')) { echo json_encode(info_cliente($id)); exit(); }
    }
    if (isset($_POST['edite_cliente'])) {
        $id = $_POST['edite_cliente'];
        if (function_exists('edite_cliente')) { echo json_encode(edite_cliente($id)); exit(); }
    }
    if (isset($_POST['confirme_edite_cliente'])) {
        if (function_exists('confirme_edite_cliente')) { echo json_encode(confirme_edite_cliente($_POST)); exit(); }
    }
    if (isset($_POST['renovar_cliente'])) {
        $id = $_POST['renovar_cliente']; $usuario = $_POST['usuario'] ?? '';
        if (function_exists('renovar_cliente')) { echo json_encode(renovar_cliente($id, $usuario)); exit(); }
    }
    if (isset($_POST['confirme_renovar_cliente'])) {
        $id = $_POST['confirme_renovar_cliente']; $meses = $_POST['meses'] ?? 1;
        if (function_exists('confirme_renovar_cliente')) { echo json_encode(confirme_renovar_cliente($id, $meses)); exit(); }
    }
    if (isset($_POST['delete_cliente'])) {
        $id = $_POST['delete_cliente']; $usuario = $_POST['usuario'] ?? '';
        if (function_exists('delete_cliente')) { echo json_encode(delete_cliente($id, $usuario)); exit(); }
    }
    if (isset($_POST['confirme_delete_cliente'])) {
        $id = $_POST['confirme_delete_cliente'];
        if (function_exists('confirme_delete_cliente')) { echo json_encode(confirme_delete_cliente($id, '')); exit(); }
    }
    if (isset($_POST['converter_teste'])) {
        $id = $_POST['converter_teste']; $usuario = $_POST['usuario'] ?? '';
        if (function_exists('converter_teste')) { echo json_encode(converter_teste($id, $usuario)); exit(); }
    }
    if (isset($_POST['confirme_converter_teste'])) {
        $id = $_POST['confirme_converter_teste'];
        if (function_exists('confirme_converter_teste')) { echo json_encode(confirme_converter_teste($id)); exit(); }
    }

    echo json_encode(['title' => 'Erro!', 'msg' => 'Ação POST não reconhecida.', 'icon' => 'error']);
    exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Lógica GET, se houver, continua aqui.
}

?>
