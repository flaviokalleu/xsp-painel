<?php
/**
 * ARQUIVO: api/acoes_excluir.php
 * DESCRIÇÃO: Back-end seguro para exclusão em massa de clientes.
 * VERSÃO FINAL: Utiliza validação de token direto no banco para garantir a autenticação correta.
 */

@session_start();
header('Content-Type: application/json');

// Carrega os arquivos essenciais do painel
require_once 'controles/db.php';

// Conecta ao banco de dados
$conexao = conectar_bd();
if (!$conexao) {
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados.']);
    exit;
}

// ======================================================================
//  VERIFICAÇÃO DE SEGURANÇA (TOKEN DIRETO NO BANCO)
// ======================================================================
$token_enviado = $_POST['token'] ?? '';

if (empty($token_enviado)) {
    echo json_encode(['status' => 'error', 'message' => 'Erro de autenticação: Token não fornecido.']);
    exit;
}

// Valida o token no banco de dados e obtém o ID e o nível do admin
try {
    // **CORREÇÃO APLICADA AQUI:** Trocado 'nivel_admin' por 'admin' para corresponder à sua tabela.
    $sql_token = "SELECT id, admin FROM admin WHERE token = :token";
    $stmt_token = $conexao->prepare($sql_token);
    $stmt_token->bindParam(':token', $token_enviado, PDO::PARAM_STR);
    $stmt_token->execute();
    $admin_logado = $stmt_token->fetch(PDO::FETCH_ASSOC);

    if (!$admin_logado || empty($admin_logado['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Erro de autenticação: Token inválido ou expirado. Faça login novamente.']);
        exit;
    }
    $admin_id_logado = $admin_logado['id'];
    $nivel_admin_logado = $admin_logado['admin']; // Usa a coluna correta

} catch (PDOException $e) {
    // Mostra o erro exato do banco de dados para facilitar o debug.
    echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados durante a validação do token: ' . $e->getMessage()]);
    exit;
}

// Apenas o administrador principal (nível 1) pode executar esta ação
if ($nivel_admin_logado != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado. Apenas o administrador principal pode executar esta ação.']);
    exit;
}
// ======================================================================

$action = $_POST['action'] ?? '';

if ($action === 'excluir_listas') {
    excluirListas($conexao, $admin_id_logado);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida.']);
}

function excluirListas($conexao, $admin_id) {
    $daterange = $_POST['daterange'] ?? '';
    $tipos = $_POST['tipos'] ?? [];

    if (empty($daterange) || empty($tipos)) {
        echo json_encode(['status' => 'error', 'message' => 'Intervalo de data ou tipo de lista não foram especificados.']);
        return;
    }

    try {
        // Processa o intervalo de datas
        list($data_inicio_str, $data_fim_str) = explode(' - ', $daterange);
        $data_inicio = DateTime::createFromFormat('d/m/Y', trim($data_inicio_str))->format('Y-m-d 00:00:00');
        $data_fim = DateTime::createFromFormat('d/m/Y', trim($data_fim_str))->format('Y-m-d 23:59:59');

        $queries = [];
        $params = [];

        // Monta a query de exclusão com base nos tipos selecionados
        if (in_array('testes', $tipos)) {
            // Condição para testes: is_trial = 1 E a data de criação está no intervalo
            $queries[] = "(is_trial = 1 AND criado_em BETWEEN ? AND ?)";
            $params[] = $data_inicio;
            $params[] = $data_fim;
        }
        if (in_array('expiradas', $tipos)) {
            // Condição para expiradas: a data de vencimento está no passado E está no intervalo selecionado
            $queries[] = "(Vencimento < NOW() AND Vencimento BETWEEN ? AND ?)";
            $params[] = $data_inicio;
            $params[] = $data_fim;
        }

        if (empty($queries)) {
            echo json_encode(['status' => 'error', 'message' => 'Nenhum tipo de lista válido foi selecionado.']);
            return;
        }

        // Combina as condições com OR
        $where_clause = implode(' OR ', $queries);

        // Monta a query final. A exclusão só afeta os clientes do próprio admin.
        $sql = "DELETE FROM clientes WHERE admin_id = ? AND ($where_clause)";
        
        // Adiciona o admin_id no início do array de parâmetros
        array_unshift($params, $admin_id);

        $stmt = $conexao->prepare($sql);
        $stmt->execute($params);
        $total_excluidos = $stmt->rowCount();

        echo json_encode([
            'status' => 'success',
            'message' => "Operação concluída! Total de {$total_excluidos} listas foram excluídas com sucesso."
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao processar a solicitação: ' . $e->getMessage()]);
    }
}
?>
