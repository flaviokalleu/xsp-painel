<?php
header('Content-Type: application/json');
session_start();

// VERIFICAÇÃO CORRIGIDA: Usando a variável de sessão correta do seu sistema
if (!isset($_SESSION['logged_in_fxtream']) || $_SESSION['logged_in_fxtream'] !== true) {
    http_response_code(403); // Proibido
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit;
}

require_once('./controles/db.php');
require_once('./controles/dashboard.php');

// Tenta buscar os dados
try {
    // Busca dados para os próximos 7 dias
    $dadosVencimentos = getDadosVencimentos(7);

    // Lógica de data robusta para calcular dias de atraso
    if (!empty($dadosVencimentos['lista_vencidos'])) {
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $hoje = new DateTime('now', $timezone);
        $hoje->setTime(0, 0, 0); // Zera o tempo para comparar apenas o dia

        foreach ($dadosVencimentos['lista_vencidos'] as &$cliente) {
            if (empty($cliente['Vencimento'])) {
                $cliente['dias_atrasado'] = 0;
                continue;
            }

            $vencimento = new DateTime($cliente['Vencimento'], $timezone);
            $vencimento->setTime(0, 0, 0); // Zera o tempo da data de vencimento
            
            if ($hoje > $vencimento) {
                $diferenca = $hoje->diff($vencimento);
                $cliente['dias_atrasado'] = $diferenca->days;
            } else {
                $cliente['dias_atrasado'] = 0;
            }
        }
    }

    echo json_encode($dadosVencimentos);

} catch (Exception $e) {
    http_response_code(500); // Erro interno do servidor
    echo json_encode(['error' => 'Falha ao buscar dados de vencimentos.', 'details' => $e->getMessage()]);
}
?>