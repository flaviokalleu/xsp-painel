<?php
require_once('./api/controles/db.php');
set_time_limit(30);

function processarUrl($url) {
    $parsedUrl = parse_url($url);
    
    $path = $parsedUrl['path'];
    
    $pathParts = explode('/', trim($path, '/'));
    
    if (count($pathParts) >= 4) {
        $domain = $parsedUrl['host'];
        //$type = $pathParts[0]; // Tipo fixo "live" conforme o que foi solicitado
        $usuario = $pathParts[1];
        $senha = $pathParts[2];
        $arquivo = pathinfo($pathParts[3], PATHINFO_FILENAME);
    }elseif (count($pathParts) <= 3) {
        $domain = $parsedUrl['host'];
        $usuario = $pathParts[0];
        $senha = $pathParts[1];
        $arquivo = pathinfo($pathParts[2], PATHINFO_FILENAME);
    } else {
        return false; // Caso a URL não tenha a estrutura esperada
    }

    // Se $arquivo for vazio ou não for um número, retorna false
    if (empty($arquivo) || !is_numeric($arquivo)) {
        return false;
    }

    return [
        'dominio' => $domain,
        'usuario' => $usuario,
        'senha' => $senha,
        'arquivo' => $arquivo,
    ];
}
function getHeadersAsJson($url) {
    // Configuração do cabeçalho User-Agent
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: XCIPTV\r\n"
        ]
    ];

    // Criação do contexto de transmissão
    $context = stream_context_create($options);

    // Obtendo os cabeçalhos da URL usando o contexto de transmissão
    $headers = @get_headers($url, 1, $context);

    // Verifica se foi possível obter os cabeçalhos
    if ($headers === false) {
        return json_encode(["error" => "Não foi possível obter os cabeçalhos"], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // Verifica se existe o cabeçalho Location
    if (isset($headers['Location'])) {
        $locations = is_array($headers['Location']) ? $headers['Location'] : [$headers['Location']];

        // Filtra URLs que contêm "token="
        $urlsComToken = array_filter($locations, function($location) {
            return strpos($location, 'token=') !== false;
        });

        // Se encontrou URLs com "token=", retorna essas URLs
        if (!empty($urlsComToken)) {
            return json_encode(["URLsComToken" => array_values($urlsComToken)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        
        // Caso contrário, retorna o valor de Location original
        return json_encode(["Location" => $locations], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // Caso não tenha o cabeçalho Location, retorna todos os cabeçalhos
    return json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
function getLocationFromURL($url) {
    $visitedUrls = []; // Array para evitar loops de redirecionamento
    $maxRedirects = 10; // Limite de redirecionamentos para evitar loops infinitos
    $redirectCount = 0;

    while ($redirectCount < $maxRedirects) {
        // Configuração do cabeçalho User-Agent
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: XCIPTV\r\n"
            ]
        ];

        // Criação do contexto de transmissão
        $context = stream_context_create($options);

        // Obtendo os cabeçalhos da URL usando o contexto de transmissão
        $headers = @get_headers($url, 1, $context);

        // Se não conseguiu obter os cabeçalhos, retorna erro
        if (!$headers) {
            return false;
        }

        // Verificar status HTTP para erros comuns
        if (isset($headers[0])) {
            if (strpos($headers[0], "401 Unauthorized") !== false || 
                strpos($headers[0], "404 Not Found") !== false) {
                return false;
            }
        }

        // Verifica se há um cabeçalho de redirecionamento (Location)
        if (isset($headers["Location"])) {
            $location = is_array($headers["Location"]) ? end($headers["Location"]) : $headers["Location"];

            // Evitar redirecionamento infinito
            if (in_array($location, $visitedUrls)) {
                return false;
            }

            // Salvar URL visitada
            $visitedUrls[] = $location;

            // Se a URL contém "token", retornamos essa URL
            if (strpos($location, "token") !== false) {
                return trim($location);
            }

            // Atualiza a URL para seguir o redirecionamento
            $url = $location;
            $redirectCount++;
        } else {
            // Se não houver mais redirecionamentos e ainda não encontramos um token, retorna falso
            return false;
        }
    }

    // Se atingir o limite de redirecionamentos sem encontrar "token", retorna falso
    return false;
}
// Obter o agente do usuário
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

$type_url = $_GET['type_url'] ?? 'series';
$username = $_GET['usuario'] ?? null;
$password = $_GET['senha'] ?? null;
$arquivo = $_GET['arquivo'] ?? null;


$arquivo_sem_extensao = pathinfo($arquivo, PATHINFO_FILENAME);
$extensao = pathinfo($arquivo, PATHINFO_EXTENSION);

if (!$username || !$password) {
    http_response_code(401); 
    $errorResponse['user_info'] = array();
    $errorResponse['user_info']['auth'] = 0;
    $errorResponse['user_info']['msg'] = "username e password necessario!";
    echo json_encode($errorResponse);
    exit();
}

$conexao = conectar_bd();
$query = "SELECT *
                    FROM clientes
                    WHERE usuario = :username AND senha = :password";
$statement = $conexao->prepare($query);
$statement->bindValue(':username', $username);
$statement->bindValue(':password', $password);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
if (!$result) {
    http_response_code(401); 
    $errorResponse = json_encode(["user_info" => ["auth" => 0]]);
    echo $errorResponse;
    exit();
}

// ==========================================================
// >>> INÍCIO DO CÓDIGO DE SEGURANÇA (BLOQUEIO DE IP) <<<
// ==========================================================
$ip = $_SERVER['REMOTE_ADDR']; // Obtém o IP do cliente
$bloqueado_url = "http://" . $_SERVER["HTTP_HOST"] . "/video/block.mp4"; // URL de vídeo de bloqueio/vencido

// --- VERIFICAÇÃO DE IP BLOQUEADO ---
$query_check_ban = "SELECT 1 FROM banned_ips WHERE ip_address = :ip AND ban_expires > NOW() LIMIT 1";
$stmt_check_ban = $conexao->prepare($query_check_ban);
$stmt_check_ban->bindValue(":ip", $ip);
$stmt_check_ban->execute();

if ($stmt_check_ban->rowCount() > 0) {
    // IP ENCONTRADO NA LISTA DE BLOQUEADOS
    http_response_code(403); // Acesso Proibido
    header("Location: $bloqueado_url");
    exit();
}
// ==========================================================
// >>> FIM DO CÓDIGO DE SEGURANÇA <<<
// ==========================================================

$tempo_expiracao = 3600; // 1 hora de sessão

$dataAtual = new DateTime();
if (new DateTime($result["Vencimento"]) < $dataAtual) {
    // USANDO A NOVA VARIÁVEL
    $vencido = $bloqueado_url;
    header("Location: $vencido");
    exit();
}

// A linha $ip = $_SERVER['REMOTE_ADDR']; foi movida para cima e não é mais necessária aqui
$limiteConexoes = (int) $result["conexoes"]; 
$tempo_limite = (new DateTime())->modify("-{$tempo_expiracao} seconds")->format("Y-m-d H:i:s");

// Limpa conexões antigas
$query_delete = "DELETE FROM conexoes WHERE ultima_atividade < :tempo_limite AND usuario = :usuario";
$stmt_delete = $conexao->prepare($query_delete);
$stmt_delete->bindValue(":tempo_limite", $tempo_limite);
$stmt_delete->bindValue(":usuario", $username);
$stmt_delete->execute();

// ==========================================================
// >>> INÍCIO DA REGRA DE CONEXÃO V17.0: OTIMIZAÇÃO PARA VOD/SÉRIES/DIFERENTES USER AGENTS <<<
// ==========================================================

$session_existe_ip_ua = false;
$id_sessao_existente = null;

// 1. Tenta encontrar uma sessão ativa que corresponda a este IP E ESTE USER_AGENT. (Melhor Match/Troca de Conteúdo)
$query_check_session = "SELECT id FROM conexoes WHERE usuario = :usuario AND ip = :ip AND user_agent = :ua LIMIT 1";
$stmt_check_session = $conexao->prepare($query_check_session);
$stmt_check_session->bindValue(":usuario", $username);
$stmt_check_session->bindValue(":ip", $ip);
$stmt_check_session->bindValue(":ua", $userAgent);
$stmt_check_session->execute();
$session_data = $stmt_check_session->fetch(PDO::FETCH_ASSOC);

if ($session_data) {
    $session_existe_ip_ua = true;
    $id_sessao_existente = $session_data['id'];
}

// 2. Contamos o número TOTAL de sessões ativas (COUNT(id)).
$query_count = "SELECT COUNT(id) FROM conexoes WHERE usuario = :usuario";
$stmt_count = $conexao->prepare($query_count);
$stmt_count->bindValue(":usuario", $username);
$stmt_count->execute();
$conexoes_ativas = (int) $stmt_count->fetchColumn();


if ($session_existe_ip_ua) {
    // A. Sessão Ativa (IP + UA) Encontrada: Renovação. (Troca de Conteúdo/Episódio no mesmo Player)
    $query_update = "UPDATE conexoes SET ultima_atividade = NOW() WHERE id = :id";
    $stmt_update = $conexao->prepare($query_update);
    $stmt_update->bindValue(":id", $id_sessao_existente);
    $stmt_update->execute();

} else {
    // B. NOVA SESSÃO (Novo IP OU IP existente com novo User Agent).
    
    // B.1. Verifica se já existe uma sessão para este IP, independente do User Agent.
    // **CHAVE: Consolidação da Sessão para o mesmo IP, evitando bloqueio por User Agent diferente.**
    $query_check_ip = "SELECT id FROM conexoes WHERE usuario = :usuario AND ip = :ip LIMIT 1";
    $stmt_check_ip = $conexao->prepare($query_check_ip);
    $stmt_check_ip->bindValue(":usuario", $username);
    $stmt_check_ip->bindValue(":ip", $ip);
    $stmt_check_ip->execute();
    $session_ip_apenas = $stmt_check_ip->fetch(PDO::FETCH_ASSOC);

    if ($session_ip_apenas) {
        // B.1a. IP Encontrado, mas com User Agent diferente (Ex: Live -> Séries).
        // ATUALIZA a sessão existente com o novo User Agent. Não insere nova linha.
        $query_update_ip = "UPDATE conexoes SET ultima_atividade = NOW(), user_agent = :ua WHERE id = :id_sessao";
        $stmt_update_ip = $conexao->prepare($query_update_ip);
        $stmt_update_ip->bindValue(":id_sessao", $session_ip_apenas['id']);
        $stmt_update_ip->bindValue(":ua", $userAgent);
        $stmt_update_ip->execute();
        
    } else {
        // B.2. Novo IP. Checa se o limite já foi atingido.
        if ($conexoes_ativas >= $limiteConexoes) {
            
            // B.2a. [BLOQUEIO ESTRITO] Bloqueia rigorosamente a NOVA SESSÃO.
            http_response_code(429);
            $vencido = $bloqueado_url;
            header("Location: $vencido");
            exit();
        }
        
        // B.3. Insere a nova conexão (Novo IP/Sessão).
        $query_insert = "INSERT INTO conexoes (usuario, ip, ultima_atividade, user_agent) VALUES (:usuario, :ip, NOW(), :ua)";
        $stmt_insert = $conexao->prepare($query_insert);
        $stmt_insert->bindValue(":usuario", $username);
        $stmt_insert->bindValue(":ip", $ip);
        $stmt_insert->bindValue(":ua", $userAgent);
        $stmt_insert->execute();
    }
}
// ==========================================================
// >>> FIM DA REGRA DE CONEXÃO V17.0 <<<
// ==========================================================

// ==========================================================
// >>> BLOCO V17.1: GRAVAÇÃO DO NOME COMPLETO DA SÉRIE E EPISÓDIO <<<
// ==========================================================
$nome_para_salvar = NULL; 
$series_id_from_episode = NULL;
$episode_info = NULL;

if ($conexao && $username && $arquivo_sem_extensao) {
    // 1. Tenta obter as informações do episódio (Series ID, Season, Episode Num, Title)
    try {
        // Seleciona todas as informações necessárias do episódio
        $sql_episode_info = "SELECT series_id, season, episode_num, title FROM series_episodes WHERE id = :episode_id LIMIT 1";
        $stmt_episode_info = $conexao->prepare($sql_episode_info);
        $stmt_episode_info->execute([':episode_id' => $arquivo_sem_extensao]);
        $episode_info = $stmt_episode_info->fetch(PDO::FETCH_ASSOC);

        if ($episode_info) {
            $series_id_from_episode = $episode_info['series_id'];
        }
    } catch (PDOException $e) { $episode_info = NULL; }

    // 2. Se for um episódio, busca o nome da série e formata o nome completo.
    if ($series_id_from_episode) {
        $series_name = 'Série Desconhecida';
        
        // Descodifica o título do episódio para garantir que caracteres especiais apareçam corretamente
        $episode_name = htmlspecialchars_decode($episode_info['title'] ?? 'Episódio Sem Nome');
        $season = $episode_info['season'] ?? '??';
        $episode_num = $episode_info['episode_num'] ?? '??';

        try {
            $sql_series_name = "SELECT name FROM series WHERE id = :series_id LIMIT 1";
            $stmt_series_name = $conexao->prepare($sql_series_name);
            $stmt_series_name->execute([':series_id' => $series_id_from_episode]);
            $row_series_name = $stmt_series_name->fetch(PDO::FETCH_ASSOC);

            if ($row_series_name && $row_series_name['name']) {
                $series_name = $row_series_name['name'];
            }
        } catch (PDOException $e) { /* Falha silenciosa */ }
        
        // Formato final: SÉRIE: [Nome da Série] - T[Temporada] E[Episódio]: [Título do Episódio]
        $nome_para_salvar = "SÉRIE: {$series_name} - T{$season} E{$episode_num}: {$episode_name}";
    }
    
    // Fallback para Filmes (VODs) caso o ID não seja de um episódio de série
    if (!$nome_para_salvar) {
          try {
            $sql_vod_name = "SELECT name FROM streams WHERE id = :vod_id LIMIT 1";
            $stmt_vod_name = $conexao->prepare($sql_vod_name);
            $stmt_vod_name->execute([':vod_id' => $arquivo_sem_extensao]);
            $row_vod_name = $stmt_vod_name->fetch(PDO::FETCH_ASSOC);

            if ($row_vod_name && $row_vod_name['name']) {
                $nome_para_salvar = "FILME: " . $row_vod_name['name'];
            }
        } catch (PDOException $e) { /* Falha silenciosa */ }
    }
    
    // Fallback Final
    if (!$nome_para_salvar) {
        $nome_para_salvar = 'CONTEÚDO: ' . $arquivo_sem_extensao;
    }


    // 3. Atualiza a tabela conexoes (agora usando a identificação da sessão IP/UA)
    $query_update_final = "UPDATE conexoes SET 
                          canal_atual = :id_stream, 
                          ultima_atividade = NOW(), 
                          serie_nome = :nome_serie
                         WHERE usuario = :user AND ip = :ip AND user_agent = :ua
                         ORDER BY ultima_atividade DESC 
                         LIMIT 1";

    try {
        $stmt_update_final = $conexao->prepare($query_update_final);
        $stmt_update_final->execute([
            ':id_stream'  => $arquivo_sem_extensao,
            ':nome_serie' => $nome_para_salvar, 
            ':ua'         => $userAgent,
            ':ip'         => $ip,
            ':user'       => $username
        ]);
    } catch (PDOException $e) { /* Falha silenciosa */ }
}
// ==========================================================


// Se chegou até aqui, a conexão foi registrada ou atualizada, sem necessidade de bloqueio adicional.

// Bloco original do seu arquivo para buscar o link do stream
$query_streams = "SELECT * FROM series_episodes WHERE id = :id";
$stmt_streams = $conexao->prepare($query_streams);
$stmt_streams->bindParam(':id', $arquivo_sem_extensao, PDO::PARAM_STR);
$stmt_streams->execute();
$resultado_streams = $stmt_streams->fetch(PDO::FETCH_ASSOC);


if (empty($resultado_streams)) {
    // Tenta buscar na streams se não for episódio
    $query_streams_vod = "SELECT * FROM streams WHERE id = :id";
    $stmt_streams_vod = $conexao->prepare($query_streams_vod);
    $stmt_streams_vod->bindParam(':id', $arquivo_sem_extensao, PDO::PARAM_STR);
    $stmt_streams_vod->execute();
    $resultado_streams_vod = $stmt_streams_vod->fetch(PDO::FETCH_ASSOC);
    
    if (empty($resultado_streams_vod)) {
        $vod_nao_encontrado = "http://" . $_SERVER['HTTP_HOST'] . "/video/vod_nao_encontrado.mp4";
        header("Location: $vod_nao_encontrado");
        exit();
    } else {
        $resultado_streams = $resultado_streams_vod;
    }
}

$location = $resultado_streams['link'];
$tipo_link = $resultado_streams['tipo_link'];

if ($tipo_link == 'link_direto') {
    header("Location: ".$location);
    exit;
}

$dados = @processarUrl($location);

if ($location && $dados && $tipo_link !== 'link_direto2') {
    $url = "http://{$dados['dominio']}/{$type_url}/{$dados['usuario']}/{$dados['senha']}/{$dados['arquivo']}.$extensao";
    if ($tipo_link == 'padrao2') {
        header("Location: ".$location);
        exit;
    }

    $location2 = json_decode(getHeadersAsJson($url), true);
    if ($location2) {
        if (isset($location2['Location'])) {
            header("url: 1 location");
            if (is_array($location2['Location'])) {
                header("Location: ".$location2['Location'][0]);
            }else{
                header("Location: ".$location2['Location']);
            }
            exit;
        }
        if (isset($location2['URLsComToken'])) {
            header("url: 2 URLsComToken");
            if (is_array($location2['URLsComToken'])) {
                header("Location: ".$location2['URLsComToken'][0]);
                exit;
            }else{
                header("Location: ".$location2['URLsComToken']);
                exit;
            }
        }
    }
    header("url: 3 link original");
    header("Location: $url");
    exit;
}

// Bloco para tipo_link = 'link_direto2' ou fallback

$location3 = json_decode(getHeadersAsJson($location), true);
    
if ($location3) {
    if (isset($location3['Location'])) {
        header("url: 1 link direto location");
        if (is_array($location3['Location'])) {
            header("Location: ".$location3['Location'][0]);
        }else{
            header("Location: ".$location3['Location']);
        }
        exit;
    }
    if (isset($location3['URLsComToken'])) {
        header("url: 2 link direto URLsComToken");
        if (is_array($location3['URLsComToken'])) {
            header("Location: ".$location3['URLsComToken'][0]);
            exit;
        }else{
            header("Location: ".$location3['URLsComToken']);
            exit;
        }
    }
}
header("url: 3 link direto original");
header("Location: $location");
exit;

?>