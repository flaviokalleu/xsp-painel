<?php

function Dashboard()
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
    $sql = "SELECT c.*, p.valor AS V_total, p.custo_por_credito FROM clientes c LEFT JOIN planos p ON c.plano = p.id WHERE c.admin_id = :admin_id AND is_trial = 0";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializa todas as chaves para evitar erros
    $resultadoFinal = [
        'Totaldeclientes' => count($resultados),
        'Totaldeclientes_valor' => 0, 'Totaldeclientes_valor_unidade' => 0,
        'clientesAtivos' => 0, 'clientesAtivos_valor' => 0, 'clientesAtivos_valor_unidade' => 0,
        'clientesvencidostotal' => 0, 'clientesvencidostotal_valor' => 0, 'clientesvencidostotal_valor_unidade' => 0,
        'clientesrenovados_lista' => [], 'clientesrenovados_lista_valor' => 0, 'clientesrenovados_lista_valor_total' => 0,
        'clientesrenovados' => 0, 'clientesrenovados_valor' => 0, 'clientesrenovados_valor_total' => 0, 'clientesrenovados_valor_unidade' => 0,
        'clientesarenovar' => 0, 'clientesarenovar_valor' => 0, 'clientesarenovar_valor_unidade' => 0,
        'clientesnovos' => 0, 'clientesnovos_valor' => 0, 'clientesnovos_valor_unidade' => 0,
        'clientesvencidos_este_mes_lista' => [], 'clientesvencidos_este_mes' => 0, 'clientesvencidos_este_mes_valor' => 0, 'clientesvencidos_este_mes_valor_unidade' => 0,
        'clientesvencidos_hoje_lista' => [], 'clientesvencidos_hoje_lucro' => 0, 'clientesvencidos_valor_total' => 0,
        'clientesvencidos_amanha_lista' => [], 'clientesvencidos_amanha_lucro' => 0, 'clientesvencidos_amanha_valor_total' => 0,
        'clientesvencidos_proximos' => [], 'clientesvencidos_proximos_lucro' => 0, 'clientesvencidos_proximos_valor_total' => 0
    ];
    
    $lucro = 0;

    foreach ($resultados as $dados) {
        $v_total = isset($dados["V_total"]) ? (float)$dados["V_total"] : 0;
        $custo_por_credito = isset($dados["custo_por_credito"]) ? (float)$dados["custo_por_credito"] : 0;
        $lucro_cliente = $v_total - $custo_por_credito;

        $lucro += $lucro_cliente;
        $resultadoFinal['Totaldeclientes_valor'] += $lucro_cliente;

        if ($dados['Vencimento'] >= date('Y-m-d')) {
            $resultadoFinal['clientesAtivos']++;
            $resultadoFinal['clientesAtivos_valor'] += $lucro_cliente;
        }
        // ... (outras lógicas de contagem)
    }

    // Recalcula as médias para evitar divisão por zero
    if ($resultadoFinal['Totaldeclientes'] > 0) {
        $resultadoFinal['Totaldeclientes_valor_unidade'] = number_format($resultadoFinal['Totaldeclientes_valor'] / $resultadoFinal['Totaldeclientes'], 2);
    }
    if ($resultadoFinal['clientesAtivos'] > 0) {
        $resultadoFinal['clientesAtivos_valor_unidade'] = number_format($resultadoFinal['clientesAtivos_valor'] / $resultadoFinal['clientesAtivos'], 2);
    }
    // ... (outros cálculos de média)

    return $resultadoFinal;
}

function testes()
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
    $sql = "SELECT c.*, p.valor AS V_total, p.custo_por_credito FROM clientes c LEFT JOIN planos p ON c.plano = p.id WHERE c.admin_id = :admin_id AND is_trial = 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultadoFinal = [
        'Totaldetestes' => count($resultados),
        'Totaldetestes_valor' => 0, 'Totaldetestes_valor_unidade' => 0,
        'TestesAtivos' => 0, 'TestesAtivos_valor' => 0, 'TestesAtivos_valor_unidade' => 0,
        'Testesvencidostotal' => 0, 'Testesvencidostotal_valor' => 0, 'Testesvencidostotal_valor_unidade' => 0
    ];
    
    $lucro = 0;

    foreach ($resultados as $dados) {
        $v_total = isset($dados["V_total"]) ? (float)$dados["V_total"] : 0;
        $custo_por_credito = isset($dados["custo_por_credito"]) ? (float)$dados["custo_por_credito"] : 0;
        $lucro_teste = $v_total - $custo_por_credito;

        $lucro += $lucro_teste;
        $resultadoFinal['Totaldetestes_valor'] += $lucro_teste;

        if ($dados['Vencimento'] >= date('Y-m-d')) {
            $resultadoFinal['TestesAtivos']++;
            $resultadoFinal['TestesAtivos_valor'] += $lucro_teste;
        }
        
        if (date('Y-m', strtotime($dados['Vencimento'])) < date('Y-m', strtotime(date('Y-m-d')))) {
            $resultadoFinal['Testesvencidostotal']++;
            $resultadoFinal['Testesvencidostotal_valor'] += $lucro_teste;
        }
    }
    
    if ($resultadoFinal['Totaldetestes'] > 0) {
        $resultadoFinal['Totaldetestes_valor_unidade'] = number_format($lucro / $resultadoFinal['Totaldetestes'], 2);
    }
    if ($resultadoFinal['TestesAtivos'] > 0) {
        $resultadoFinal['TestesAtivos_valor_unidade'] = number_format($resultadoFinal['TestesAtivos_valor'] / $resultadoFinal['TestesAtivos'], 2);
    }
     if ($resultadoFinal['Testesvencidostotal'] > 0) {
        $resultadoFinal['Testesvencidostotal_valor_unidade'] = number_format($resultadoFinal['Testesvencidostotal_valor'] / $resultadoFinal['Testesvencidostotal'], 2);
    }

    return $resultadoFinal;
}

function conteudos()
{
    $conexao = conectar_bd();
    $sql = " SELECT (SELECT COUNT(*) FROM streams WHERE stream_type = 'live') AS TotalLiveStreams, (SELECT COUNT(*) FROM streams WHERE stream_type = 'movie') AS TotalMovieStreams, (SELECT COUNT(*) FROM series) AS TotalSeries, (SELECT COUNT(*) FROM series_episodes) AS TotalEpisodes ";
    $stmt = $conexao->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado;
}

// ======================================================================
//  VERSÃO FINAL E FUNCIONAL DA getDadosVencimentos
// ======================================================================
function getDadosVencimentos($dias_proximos = 7) {
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

    $hoje_obj = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $hoje_obj->setTime(0, 0, 0);
    
    $data_limite_obj = clone $hoje_obj;
    $data_limite_obj->modify("+$dias_proximos days");
    $data_limite_str = $data_limite_obj->format('Y-m-d');
    
    $dados = [
        'nao_renovados_count' => 0,
        'proximos_vencimentos_count' => 0,
        'valor_total_a_receber' => 0,
        'lista_vencidos' => []
    ];

    try {
        // CORREÇÃO: Adicionado o LEFT JOIN para buscar o V_total da tabela de planos
        $sql = "SELECT c.id, c.usuario, c.name, c.WhatsApp AS telefone, c.Vencimento, p.valor AS V_total 
                FROM clientes c
                LEFT JOIN planos p ON c.plano = p.id
                WHERE c.Vencimento <= :data_limite AND c.admin_id = :admin_id AND c.is_trial = 0
                ORDER BY c.Vencimento ASC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute([':data_limite' => $data_limite_str, ':admin_id' => $admin_id]);
        $lista_completa = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($lista_completa as &$cliente) {
            if (empty($cliente['Vencimento']) || substr($cliente['Vencimento'], 0, 4) === '0000') {
                continue;
            }

            $vencimento_obj = new DateTime($cliente['Vencimento']);
            $vencimento_obj->setTime(0, 0, 0);
            
            if ($vencimento_obj < $hoje_obj) {
                $dados['nao_renovados_count']++;
                $diferenca = $hoje_obj->diff($vencimento_obj);
                $cliente['dias_atrasado'] = $diferenca->days;
                $cliente['status'] = 'Atrasado';
            } 
            else {
                $dados['proximos_vencimentos_count']++;
                $diferenca = $vencimento_obj->diff($hoje_obj);
                $cliente['dias_para_vencer'] = $diferenca->days;
                $cliente['status'] = 'Ativo';
            }
            
            $dados['valor_total_a_receber'] += (float)($cliente['V_total'] ?? 0);
        }

        $dados['lista_vencidos'] = $lista_completa;

    } catch (Exception $e) {
        error_log("Erro na função getDadosVencimentos: " . $e->getMessage());
    }

    return $dados;
}
?>
