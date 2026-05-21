<?php
// === LIGA O OUTPUT BUFFER NO INÍCIO E GARANTE A LIMPEZA ===
ob_start();

header('Content-Type: application/json');
header('Connection: close');

// Desativa a exibição de erros no navegador para segurança
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ==========================================================
// 1. DEFINE O FUSO HORÁRIO PARA BRASÍLIA (UTC-3) - APENAS PARA FORMATAR
// ==========================================================
date_default_timezone_set('America/Sao_Paulo'); 

// ==========================================================
// FUNÇÃO PARA ENVIAR RESPOSTA E PARAR O SCRIPT
// ==========================================================
function sendFinalResponse($conn, $response) {
    if ($conn) {
        @$conn->close();
    }
    ob_end_clean();
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// ==========================================================
// 2. CONFIGURAÇÕES E CONEXÃO mysqli (MAIS ESTÁVEL)
// ==========================================================
$servername = getenv('DB_HOST') ?: 'localhost';
$username_db = getenv('DB_USER') ?: 'xsp'; 
$password_db = getenv('DB_PASS') ?: '';     
$dbname = getenv('DB_NAME') ?: 'xsp_panel'; 

$response = [
    'online_count' => 0,
    'multi_connection_count' => 0,
    'activity' => []
];
$conn = null;

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    $response['error'] = 'Falha na conexão com o DB. Verifique as credenciais: ' . $conn->connect_error;
    sendFinalResponse(null, $response);
}
$conn->set_charset("utf8mb4");

// ==========================================================
// 3. CONSULTA SQL ÚNICA
// ==========================================================
$sql = "SELECT 
            c.usuario, 
            c.ip, 
            c.ultima_atividade, 
            c.user_agent, 
            c.id,
            c.canal_atual AS id_conteudo_ativo,
            COALESCE(
                c.serie_nome,       
                s.name,             
                CONCAT('ID: ', c.canal_atual) 
            ) AS canal_atual_display, 
            (SELECT COUNT(id) FROM conexoes WHERE usuario = c.usuario) AS conexoes_total
        FROM conexoes AS c
        LEFT JOIN streams AS s ON c.canal_atual = s.id
        ORDER BY c.ultima_atividade DESC";

$result = $conn->query($sql);

if ($result === false) {
    $response['error'] = 'Erro ao executar a consulta SQL. Mensagem do DB: ' . $conn->error;
    sendFinalResponse($conn, $response);
}

// ==========================================================
// 4. PROCESSAMENTO E RESPOSTA DOS DADOS
// ==========================================================
$activity = [];
$counted_multi = [];
$multi_connection_users = 0;

while($row = $result->fetch_assoc()) {
    
    $ultima_atividade_formatada = 'N/A';
    if (!empty($row['ultima_atividade'])) {
        try {
            // ==========================================================
            // CORREÇÃO FINAL: SUBTRAI EXATAMENTE 3 HORAS DO VALOR FORNECIDO PELO DB.
            // O DateTime é criado sem fuso, e a subtração garante a correção do offset.
            // ==========================================================
            $db_datetime = new DateTime($row['ultima_atividade']); 
            
            // Subtrai 3 horas (P3H = Period 3 Hours)
            $db_datetime->sub(new DateInterval('PT3H')); 
            
            $ultima_atividade_formatada = $db_datetime->format('d/m/Y H:i:s'); 

        } catch (Exception $e) {
            $ultima_atividade_formatada = 'Erro de Data';
        }
    }
    
    $activity[] = [
        'id' => $row['id'],
        'usuario' => $row['usuario'],
        'ip' => $row['ip'],
        'canal_atual' => $row['canal_atual_display'], 
        'ultima_atividade' => $ultima_atividade_formatada, // O valor corrigido
        'user_agent' => $row['user_agent'],
        'conexoes_total' => $row['conexoes_total']
    ];
    
    if ($row['conexoes_total'] > 1 && !isset($counted_multi[$row['usuario']])) {
        $multi_connection_users++;
        $counted_multi[$row['usuario']] = true;
    }
}

$response['online_count'] = count($activity);
$response['multi_connection_count'] = $multi_connection_users;
$response['activity'] = $activity;

// === ENVIA A RESPOSTA FINAL E ENCERRA ===
sendFinalResponse($conn, $response);