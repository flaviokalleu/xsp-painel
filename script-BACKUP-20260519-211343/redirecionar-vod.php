<?php
header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

require_once "./api/controles/db.php";
function processarUrl($url)
{
    $parsedUrl = parse_url($url);

    $path = $parsedUrl["path"];

    $pathParts = explode("/", trim($path, "/"));
    if (count($pathParts) >= 4) {
        $domain = $parsedUrl["host"];
        //$type = $pathParts[0];
        $usuario = $pathParts[1];
        $senha = $pathParts[2];
        $arquivo = pathinfo($pathParts[3], PATHINFO_FILENAME);
    } elseif (count($pathParts) <= 3) {
        $domain = $parsedUrl["host"];
        $usuario = $pathParts[0];
        $senha = $pathParts[1];
        $arquivo = pathinfo($pathParts[2], PATHINFO_FILENAME);
    } else {
        return false;
    }

    if (empty($arquivo) || !is_numeric($arquivo)) {
        return false;
    }

    return [
        "dominio" => $domain,
        "usuario" => $usuario,
        "senha" => $senha,
        "arquivo" => $arquivo,
    ];
}

function getHeadersAsJson($url)
{
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: XCIPTV\r\n",
        ],
    ];

    $context = stream_context_create($options);

    $headers = @get_headers($url, 1, $context);

    if ($headers === false) {
        return json_encode(
            ["error" => "Não foi possível obter os cabeçalhos"],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    if (isset($headers["Location"])) {
        $locations = is_array($headers["Location"])
            ? $headers["Location"]
            : [$headers["Location"]];

        $urlsComToken = array_filter($locations, function ($location) {
            return strpos($location, "token=") !== false;
        });

        if (!empty($urlsComToken)) {
            return json_encode(
                ["URLsComToken" => array_values($urlsComToken)],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );
        }

        return json_encode(
            ["Location" => $locations],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    return json_encode($headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

$userAgent = $_SERVER["HTTP_USER_AGENT"] ?? 'unknown'; // Define o User Agent
$type_url = $_GET["type_url"] ?? "live";
$username = $_GET["usuario"] ?? null;
$password = $_GET["senha"] ?? null;
$arquivo = $_GET["arquivo"] ?? null;
$tempo = $_GET["tempo"] ?? null;

if ($arquivo) {
    $arquivo_sem_extensao = pathinfo($arquivo, PATHINFO_FILENAME);
    $extensao = pathinfo($arquivo, PATHINFO_EXTENSION);
}

if (!$username || !$password) {
    http_response_code(401);
    $errorResponse["user_info"] = [];
    $errorResponse["user_info"]["auth"] = 0;
    $errorResponse["user_info"]["msg"] = "username e password necessario!";
    echo json_encode($errorResponse);
    exit();
}

$conexao = conectar_bd();
$query = "SELECT *
    FROM clientes
    WHERE usuario = :username AND senha = :password";
$statement = $conexao->prepare($query);
$statement->bindValue(":username", $username);
$statement->bindValue(":password", $password);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
if (!$result) {
    http_response_code(401);
    $errorResponse = json_encode(["user_info" => ["auth" => 0]]);
    echo $errorResponse;
    exit();
}

// ==========================================================
// >>> INÍCIO DO CÓDIGO DE SEGURANÇA <<<
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
    $vencido = $bloqueado_url; // Usa a variável já definida
    header("Location: $vencido");
    exit();
}

$limiteConexoes = (int) $result["conexoes"];
$tempo_limite = (new DateTime())->modify("-{$tempo_expiracao} seconds")->format("Y-m-d H:i:s");

// Limpa conexões antigas
$query_delete = "DELETE FROM conexoes WHERE ultima_atividade < :tempo_limite AND usuario = :usuario";
$stmt_delete = $conexao->prepare($query_delete);
$stmt_delete->bindValue(":tempo_limite", $tempo_limite);
$stmt_delete->bindValue(":usuario", $username);
$stmt_delete->execute();

// ==========================================================
// >>> REGRA DE CONEXÃO V17.0: OTIMIZAÇÃO PARA VOD/SÉRIES/DIFERENTES USER AGENTS <<<
// ==========================================================

$session_existe_ip_ua = false;
$id_sessao_existente = null;

// 1. Tenta encontrar uma sessão ativa que corresponda a este IP E ESTE USER_AGENT. (Melhor Match/Troca de Canal)
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
    // A. Sessão Ativa (IP + UA) Encontrada: Renovação. (Troca de Canal)
    $query_update = "UPDATE conexoes SET ultima_atividade = NOW() WHERE id = :id";
    $stmt_update = $conexao->prepare($query_update);
    $stmt_update->bindValue(":id", $id_sessao_existente);
    $stmt_update->execute();

} else {
    // B. NOVA SESSÃO (Novo IP OU IP existente com novo User Agent).
    
    // B.1. Verifica se já existe uma sessão para este IP, independente do User Agent.
    $query_check_ip = "SELECT id FROM conexoes WHERE usuario = :usuario AND ip = :ip LIMIT 1";
    $stmt_check_ip = $conexao->prepare($query_check_ip);
    $stmt_check_ip->bindValue(":usuario", $username);
    $stmt_check_ip->bindValue(":ip", $ip);
    $stmt_check_ip->execute();
    $session_ip_apenas = $stmt_check_ip->fetch(PDO::FETCH_ASSOC);

    if ($session_ip_apenas) {
        // B.1a. IP Encontrado, mas com User Agent diferente (Live -> VOD).
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


// Bloco de atualização do status
if ($conexao && $username && !empty($arquivo_sem_extensao)) {
    // Atualiza a linha mais recente com base no IP e User Agent que acabou de conectar/renovar.
    $query_update_live = "UPDATE conexoes SET
                             canal_atual = :id_stream,
                             ultima_atividade = NOW(),
                             serie_nome = NULL
                         WHERE usuario = :user AND ip = :ip AND user_agent = :ua
                         ORDER BY ultima_atividade DESC
                         LIMIT 1";

    try {
        $stmt_update_live = $conexao->prepare($query_update_live);
        $stmt_update_live->execute([
            ':id_stream'  => $arquivo_sem_extensao,
            ':ua'         => $userAgent,
            ':ip'         => $ip,
            ':user'       => $username
        ]);
    } catch (PDOException $e) { /* Falha silenciosa */ }
}


$query_streams = "SELECT * FROM streams WHERE id = :id";
$stmt_streams = $conexao->prepare($query_streams);
$stmt_streams->bindParam(":id", $arquivo_sem_extensao, PDO::PARAM_STR);
$stmt_streams->execute();
$resultado_streams = $stmt_streams->fetch(PDO::FETCH_ASSOC);

if (empty($resultado_streams)) {
    $vod_nao_encontrado =
        "http://" . $_SERVER["HTTP_HOST"] . "/video/block.mp4";
    header("Location: $vod_nao_encontrado");
    exit();
}

$location = $resultado_streams["link"];
$tipo_link = $resultado_streams["tipo_link"];

if ($tipo_link == "link_direto") {
    header("Location: " . $location);
    exit();
}

$dados = @processarUrl($location);

if ($location && $dados && $tipo_link !== "link_direto2") {
    $url = "http://{$dados["dominio"]}/{$type_url}/{$dados["usuario"]}/{$dados["senha"]}/{$dados["arquivo"]}.$extensao";

    if ($tipo_link == "padrao2") {
        header("Location: " . $location);
        exit();
    }

    $location2 = json_decode(getHeadersAsJson($url), true);
    if ($location2) {
        if (isset($location2["location"])) {
            header("url: 1 location");
            if (is_array($location2["location"])) {
                header("Location: " . $location2["location"][0]);
            } else {
                header("Location: " . $location2["location"]);
            }

            exit();
        }
        if (isset($location2["URLsComToken"])) {
            header("url: 2 URLsComToken");

            if (is_array($location2["URLsComToken"])) {
                header("Location: " . $location2["URLsComToken"][0]);
                exit();
            } else {
                header("Location: " . $location2["URLsComToken"]);
                exit();
            }
        }
    }

    header("url: 3 link original");
    header("Location: $url");
    exit();
}

$location3 = json_decode(getHeadersAsJson($location), true);

if ($location3) {
    if (isset($location3["location"])) {
        header("url: 1 link direto location");
        if (is_array($location3["location"])) {
            header("Location: " . $location3["location"][0]);
        } else {
            header("Location: " . $location3["location"]);
        }

        exit();
    }
    if (isset($location3["URLsComToken"])) {
        header("url: 2 link direto URLsComToken");

        if (is_array($location3["URLsComToken"])) {
            header("Location: " . $location3["URLsComToken"][0]);
            exit();
        } else {
            header("Location: " . $location3["URLsComToken"]);
        }
    }
}
header("url: 3 link direto original");
header("Location: $location");
exit();
?>