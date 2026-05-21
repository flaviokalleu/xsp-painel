<?php
/**
 * ARQUIVO: api/api_migracao.php
 * DESCRIÇÃO: Script de back-end para analisar e importar clientes de listas M3U.
 * VERSÃO FINAL: Utiliza validação de token direto no banco de dados para contornar problemas de sessão.
 */

// Inicia a sessão para compatibilidade, mas não confiaremos nela para autenticação.
@session_start();

// Define que a resposta será sempre em formato JSON
header('Content-Type: application/json');

// Tenta carregar o arquivo de conexão com o banco de dados
try {
    // Como este arquivo está na pasta /api/, o caminho para /api/controles/db.php é 'controles/db.php'
    require_once 'controles/db.php';
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro crítico: Não foi possível carregar o arquivo do banco de dados.']);
    exit;
}

// Conecta ao banco de dados
$conexao = conectar_bd();
if (!$conexao) {
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados.']);
    exit;
}

// ======================================================================
//  NOVA VERIFICAÇÃO DE SEGURANÇA (TOKEN DIRETO NO BANCO)
// ======================================================================
$token_enviado = $_POST['token'] ?? '';

if (empty($token_enviado)) {
    echo json_encode(['status' => 'error', 'message' => 'Erro de autenticação: Token não fornecido.']);
    exit;
}

// Valida o token no banco de dados e obtém o ID do admin
try {
    $sql_token = "SELECT id FROM admin WHERE token = :token";
    $stmt_token = $conexao->prepare($sql_token);
    $stmt_token->bindParam(':token', $token_enviado, PDO::PARAM_STR);
    $stmt_token->execute();
    $admin_logado = $stmt_token->fetch(PDO::FETCH_ASSOC);

    if (!$admin_logado || empty($admin_logado['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Erro de autenticação: Token inválido ou expirado. Faça login novamente.']);
        exit;
    }
    $admin_id_logado = $admin_logado['id'];
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados durante a validação do token.']);
    exit;
}
// ======================================================================

// Roteador de ações: decide se vai analisar ou importar
$action = $_POST['action'] ?? '';

if ($action === 'analisar') {
    analisarLink();
} elseif ($action === 'importar') {
    // Passa o ID do admin validado para a função de importação
    importarCliente($conexao, $admin_id_logado);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ação inválida solicitada.']);
}

/**
 * Analisa o link M3U, conecta-se ao servidor de origem e retorna os dados.
 */
function analisarLink() {
    $m3u_url = $_POST['url'] ?? '';

    if (empty($m3u_url) || !filter_var($m3u_url, FILTER_VALIDATE_URL)) {
        echo json_encode(['status' => 'error', 'message' => 'URL M3U inválida ou em branco.']);
        return;
    }

    $parts = parse_url($m3u_url);
    if (!isset($parts['host']) || !isset($parts['query'])) {
        echo json_encode(['status' => 'error', 'message' => 'O formato da URL não parece ser de uma lista M3U válida.']);
        return;
    }

    $servidor = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    parse_str($parts['query'], $query);
    $usuario = $query['username'] ?? '';
    $senha = $query['password'] ?? '';

    if (empty($usuario) || empty($senha)) {
        echo json_encode(['status' => 'error', 'message' => 'Não foi possível encontrar usuário e senha na URL.']);
        return;
    }

    $api_url = "{$servidor}/player_api.php?username={$usuario}&password={$senha}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpcode != 200){
        echo json_encode(['status' => 'error', 'message' => 'O servidor de origem não respondeu (Código: '.$httpcode.'). Pode estar offline ou bloqueando a conexão.']);
        return;
    }

    $data = json_decode($response, true);

    if (isset($data['user_info']['auth']) && $data['user_info']['auth'] == 1) {
        $info = $data['user_info'];
        
        $dias_restantes = 0;
        $exp_date_timestamp = 0;

        if (!empty($info['exp_date'])) {
            $exp_date_timestamp = (int)$info['exp_date'];
            $hoje_timestamp = time();
            if ($exp_date_timestamp > $hoje_timestamp) {
                // Dias restantes
                $dias_restantes = floor(($exp_date_timestamp - $hoje_timestamp) / (60 * 60 * 24));
            }
        }
        
        // =================================================================
        // NOVA LÓGICA DE COBRANÇA (SIMPLIFICADA e CORRIGIDA)
        // Regra:
        // 1. Linhas com <= 30 dias: 0 Créditos (Tolerância/Carência de 1 mês)
        // 2. Linhas com >= 31 dias: 1 Crédito para cada ciclo de 30 dias APÓS a carência.
        // =================================================================
        $custo_final_em_creditos = 0;
        
        // CORRIGIDO: A cobrança começa se houver 31 dias ou mais restantes.
        if ($dias_restantes >= 31) { 
            // Subtrai os 30 dias de carência (o primeiro mês grátis)
            $dias_cobranca = $dias_restantes - 30;
            
            // Calcula o custo sobre os dias APÓS a carência, arredondando para cima.
            // Ex: 61 dias -> 61 - 30 = 31 dias cobráveis. ceil(31/30) = 2 Créditos.
            $meses_restantes = $dias_cobranca / 30;
            $custo_final_em_creditos = ceil($meses_restantes);
            
        } else {
            // Se $dias_restantes for 30 ou menos, o custo permanece 0.
            $custo_final_em_creditos = 0;
        }

        // Garante que o custo não seja negativo (apenas para segurança)
        $custo_final_em_creditos = max(0, $custo_final_em_creditos);
        // =================================================================
        
        $resultado = [
            'servidor' => $servidor,
            'usuario' => $usuario,
            'senha' => $senha,
            'conexoes' => $info['max_connections'] ?? '1',
            'dias_restantes' => $dias_restantes,
            // Adiciona o custo final em créditos ao payload para ser usado no 'importar' (se necessário)
            'custo_creditos_final' => $custo_final_em_creditos, 
            // Exibe o custo calculado (0, 1, 2, etc.)
            'custo_importacao' => number_format($custo_final_em_creditos, 0) . ' Crédito(s)', 
            'exp_date_timestamp' => $info['exp_date']
        ];

        echo json_encode(['status' => 'success', 'data' => $resultado]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Credenciais inválidas ou usuário inativo no servidor de origem.']);
    }
}

/**
 * Importa o cliente para o banco de dados local, atribuindo ao admin logado.
 */
function importarCliente($conn, $admin_id_autenticado) {
    $cliente = $_POST['cliente'] ?? null;
    if (!$cliente) {
        echo json_encode(['status' => 'error', 'message' => 'Dados do cliente não foram recebidos para importação.']);
        return;
    }

    $conn->beginTransaction();
    try {
        $usuario = $cliente['usuario'];
        $senha = $cliente['senha'];
        $conexoes = (int)$cliente['conexoes'];
        $exp_date = (int)$cliente['exp_date_timestamp'];
        $vencimento_formatado = date('Y-m-d H:i:s', $exp_date);
        
        // ** NOVO: Obtém o custo final calculado para a cobrança **
        $custo_creditos = (int)($cliente['custo_creditos_final'] ?? 0); 
        
        // =================================================================
        // LÓGICA DE COBRANÇA DE CRÉDITO DO REVENDEDOR
        // =================================================================
        if ($custo_creditos > 0) {
            
            // 1. Verificar se o revendedor tem créditos suficientes (OPCIONAL, mas recomendado)
            $stmt_credito = $conn->prepare("SELECT creditos FROM admin WHERE id = ?");
            $stmt_credito->execute([$admin_id_autenticado]);
            $admin_info = $stmt_credito->fetch(PDO::FETCH_ASSOC);

            if (!$admin_info || $admin_info['creditos'] < $custo_creditos) {
                // Se não houver crédito, a transação não é realizada
                $conn->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Erro: Créditos insuficientes para realizar a importação de ' . $custo_creditos . ' crédito(s).']);
                return;
            }
            
            // 2. Subtrair os créditos
            $sql_subtracao = "UPDATE admin SET creditos = creditos - ? WHERE id = ?";
            $stmt_subtracao = $conn->prepare($sql_subtracao);
            $stmt_subtracao->execute([$custo_creditos, $admin_id_autenticado]);
        }
        // =================================================================
        
        // 3. Inserir o Cliente no Banco de Dados
        $sql = "INSERT INTO clientes 
                    (name, usuario, senha, criado_em, Vencimento, is_trial, adulto, conexoes, bloqueio_conexao, admin_id) 
                VALUES 
                    (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            $usuario,
            $usuario,
            $senha,
            $vencimento_formatado,
            0,
            0,
            $conexoes,
            'nao',
            $admin_id_autenticado // Usa o ID validado pelo token
        ]);

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Cliente importado com sucesso! ' . ($custo_creditos > 0 ? $custo_creditos . ' crédito(s) cobrado(s).' : 'Nenhuma cobrança realizada (carência).')]);
    } catch (PDOException $e) {
        $conn->rollBack();
        if ($e->getCode() == 23000) {
              echo json_encode(['status' => 'error', 'message' => 'Erro: Este nome de usuário já existe no seu sistema.']);
        } else {
            // Em caso de erro, adicione um log real para depuração.
              echo json_encode(['status' => 'error', 'message' => 'Erro de banco de dados: ' . $e->getMessage()]);
        }
    }
}
?>
