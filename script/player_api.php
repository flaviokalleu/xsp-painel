<?php
// player_api_unified_v5.0.php - Versão Sênior Unificada (FINAL - VOD CORRIGIDO)
// Correção: VOD (get_vod_streams) revertido para hardcode "mp4" e sem casting de ID para máxima compatibilidade Smarters/Clássicos.

// IMPORTANTE: Certifique-se de que o caminho para 'db.php' está correto.
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

// Cabeçalhos de Resposta para API: JSON e Controle de Conexão/Acesso
header('Content-Type: application/json; charset=utf-8');
header('Connection: close');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: *'); 
date_default_timezone_set('America/Sao_Paulo');

// ==========================================================
// 1. Variáveis de Entrada e Configuração
// ==========================================================
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$url         = $_SERVER['HTTP_HOST'] ?? 'localhost';
$username    = $_GET['username']    ?? null;
$password    = $_GET['password']    ?? null;
$action      = $_GET['action']      ?? null;
$series_id   = $_GET['series_id']   ?? null;
$vod_id      = $_GET['vod_id']      ?? null;
$category_id = $_GET['category_id'] ?? null;
$type        = $_GET['type']        ?? null;

if ($category_id === '*' || $category_id === '-1' || $category_id === '0') {
    $category_id = null;
}

// Suporte a POST (Mantido robusto)
if (isset($_POST['username']) && isset($_POST['password'])) {
    $qs = [
        'username' => $_POST['username'],
        'password' => $_POST['password']
    ];
    if (!empty($_POST['action']))    $qs['action']    = $_POST['action'];
    if (!empty($_POST['series_id'])) $qs['series_id'] = $_POST['series_id'];
    if (!empty($_POST['vod_id']))    $qs['vod_id']    = $_POST['vod_id'];
    if (!empty($_POST['category_id'])) $qs['category_id'] = $_POST['category_id'];
    if (!empty($_POST['type']))      $qs['type']      = $_POST['type'];

    header('Location: player_api.php?' . http_build_query($qs));
    exit;
}

// Inicialização de Variáveis de URL Base
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $url;

// ==========================================================
// 2. Funções Helper
// ==========================================================
function build_live_url($baseUrl, $u, $p, $id, $ext = 'm3u8') {
    return "{$baseUrl}/live/{$u}/{$p}/{$id}.{$ext}";
}
function build_movie_url($baseUrl, $u, $p, $id, $ext = 'mp4') {
    return "{$baseUrl}/movie/{$u}/{$p}/{$id}.{$ext}";
}
function build_series_url($baseUrl, $u, $p, $id, $ext = 'mp4') {
    return "{$baseUrl}/series/{$u}/{$p}/{$id}.{$ext}";
}
function timeToSeconds($timeStr) {
    $parts = explode(":", $timeStr);
    $durationSecs = 0;
    if (count($parts) === 3) {
        $durationSecs = ((int)$parts[0]*3600) + ((int)$parts[1]*60) + (int)$parts[2];
    } elseif (count($parts) === 2) {
        $durationSecs = ((int)$parts[0]*60) + (int)$parts[1]; 
    } elseif (count($parts) === 1) {
        $durationSecs = (int)$parts[0]; 
    }
    return $durationSecs;
}

// ==========================================================
// 3. Autenticação e Conexão com BD
// ==========================================================
if (!$username || !$password) {
    http_response_code(401);
    echo json_encode(["user_info" => ["auth" => 0, "msg" => "username e password necessario!"]]);
    exit;
}

$conexao = conectar_bd();
$query = "SELECT * FROM clientes WHERE usuario = :username AND senha = :password";
$statement = $conexao->prepare($query);
$statement->bindValue(':username', $username);
$statement->bindValue(':password', $password);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    http_response_code(401);
    echo json_encode(["user_info" => ["auth" => 0]]);
    exit;
}

// ==========================================================
// 3.5. Bouquet Filtering
// ==========================================================
$allowed_category_ids = null;
if (!empty($result['bouquet_id'])) {
    $stmt_bouquet = $conexao->prepare("SELECT category_id FROM bouquet_items WHERE bouquet_id = ?");
    $stmt_bouquet->execute([$result['bouquet_id']]);
    $allowed_category_ids = $stmt_bouquet->fetchAll(PDO::FETCH_COLUMN);
    if (empty($allowed_category_ids)) { $allowed_category_ids = [0]; }
}

// ==========================================================
// 4. Ação Padrão: user_info / server_info
// ==========================================================
if (!isset($action)) {
    $exp_date   = strtotime($result['Vencimento']);
    $created_at = strtotime($result['Criado_em'] ?? 'now');
    $status = "Active";
    $auth   = "1";

    if ($exp_date && $exp_date < time()) {
        $status = "Inactive";
        $auth   = "0";
    }

    $response = [
        'user_info' => [
            'username' => $result['usuario'],
            'password' => $result['senha'],
            'message'  => 'BEM-VINDOS AO PAINEL PB&CTV!',
            'auth'     => $auth,
            'status'   => $status,
            'exp_date' => (string)$exp_date,
            'is_trial' => (string)($result['is_trial'] ?? "0"),
            'active_cons' => 0,
            'created_at' => (string)$created_at,
            'max_connections' => (string)($result['conexoes'] ?? "1"),
            'allowed_output_formats' => ['m3u8','ts','rtmp','mp4'] 
        ],
        'server_info' => [
            'painel' => 'XTREAM UNIFIED API',
            'version' => '0.0.1',
            'revision' => 1,
            'url' => $url,
            'port' => '', 
            'https_port' => "443",
            'server_protocol' => $scheme, 
            'rtmp_port' => '8880',
            'timestamp_now' => time(),
            'time_now' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ]
    ];
    echo json_encode($response);
    exit;
}

// ==========================================================
// 5. Categorias (live/vod/series)
// ==========================================================
if (in_array($action, ['get_live_categories','get_vod_categories','get_series_categories'])) {
    $adulto = (int)($result['adulto'] ?? 0);
    $type = '';

    switch ($action) {
        case 'get_live_categories':   $type = 'live';   break;
        case 'get_vod_categories':    $type = 'movie';  break;
        case 'get_series_categories': $type = 'series'; break;
    }

    $query_str = "SELECT * FROM categoria WHERE type = ?";
    $params = [$type];
    if ($adulto === 0) {
        $query_str .= " AND is_adult = '0'";
    }
    if ($allowed_category_ids !== null) {
        $in_placeholders = implode(',', array_fill(0, count($allowed_category_ids), '?'));
        $query_str .= " AND id IN ($in_placeholders)";
        $params = array_merge($params, $allowed_category_ids);
    }
    $query_str .= " ORDER BY position ASC, nome ASC"; 

    $query = $conexao->prepare($query_str);
    $query->execute($params);

    $results = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            "category_id" => (string)$row["id"],
            "category_name" => $row["nome"],
            "parent_id" => $row["parent_id"] ?? 0,
        ];
    }

    if (empty($results)) {
        $results[] = ["category_id"=>"1","category_name"=>"Sem categorias","parent_id"=>0];
    }

    echo json_encode($results);
    exit;
}

// ==========================================================
// 6. Streams (live / vod / series list)
// ==========================================================
if (in_array($action, ['get_live_streams','get_vod_streams','get_series'])) {
    $adulto = (int)($result['adulto'] ?? 0);
    $results = [];
    $num = 0;
    $table = ($action === 'get_series') ? 'series' : 'streams';
    $stream_type = '';

    if ($action === 'get_live_streams') {
        $stream_type = 'live';
    } elseif ($action === 'get_vod_streams') {
        $stream_type = 'movie';
    }

    $query_str = "SELECT * FROM `{$table}` WHERE 1=1"; 
    $params = [];

    if ($table === 'streams') { 
        $query_str .= " AND stream_type = ?";
        array_unshift($params, $stream_type);
    } 

    if ($adulto === 0) {
        $query_str .= " AND is_adult = '0'";
    }

    if ($allowed_category_ids !== null) {
        $in_placeholders = implode(',', array_fill(0, count($allowed_category_ids), '?'));
        $query_str .= " AND category_id IN ($in_placeholders)";
        $params = array_merge($params, $allowed_category_ids);
    }

    if ($category_id !== null) { 
        $query_str .= " AND category_id = ?";
        $params[] = $category_id;
    }

    $query_str .= " ORDER BY name ASC";
    $stmt = $conexao->prepare($query_str);
    $stmt->execute($params);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $num++;
        if ($action === 'get_live_streams') {
            // LIVE: ID como string e URL completa (Mantido unificado)
            $direct = build_live_url($baseUrl, $username, $password, $row["id"], 'm3u8'); 
            $results[] = [
                "num" => $num, "name" => htmlspecialchars_decode($row["name"]), "stream_type" => $row["stream_type"],
                "stream_id" => (string)$row["id"], "stream_icon" => $row["stream_icon"] ?? "", "epg_channel_id" => $row["epg_channel_id"] ?? "",
                "added" => $row["added"] ?? "", "is_adult" => (string)($row["is_adult"] ?? "0"), "custom_sid" => "", "tv_archive" => 0,
                "direct_source" => $direct, "tv_archive_duration" => 0, "category_id" => (string)$row["category_id"],
                "category_ids" => [(string)$row["category_id"]], "thumbnail" => "",
            ];

        } elseif ($action === 'get_vod_streams') { 
            // VOD: ESTRUTURA E TIPAGEM IDÊNTICAS AO player_api.php 1.php (que funciona)
            $results[] = [
                "num" => $num, // Inteiro
                "name" => htmlspecialchars_decode($row["name"]),
                "title" => htmlspecialchars_decode($row["name"]),
                "year" => $row["year"],
                "stream_type" => $row["stream_type"],
                "stream_id" => $row["id"], // <--- SEM CAST (Match PHP 1)
                "stream_icon" => $row["stream_icon"],
                "rating" => $row["rating"],
                "rating_5based" => $row["rating_5based"],
                "added" => $row["added"],
                "is_adult" => $row["is_adult"],
                "category_id" => $row["category_id"], // <--- SEM CAST (Match PHP 1)
                "container_extension" => "mp4", // <--- HARDCODED (Match PHP 1)
                "custom_sid" => "",
                "direct_source" => "", // VAZIO (Match PHP 1)
            ];

        } elseif ($action === 'get_series') { 
            // SÉRIES: ID como string e URL completa (Mantido unificado)
            $results[] = [
                "num" => $num, "name" => htmlspecialchars_decode($row["name"] ?? ""), "title" => htmlspecialchars_decode($row["name"] ?? ""),
                "year" => $row["year"] ?? "", "stream_type" => "series", "series_id" => (string)$row["id"], 
                "cover" => $row["cover"] ?? "", "plot" => $row["plot"] ?? "", "cast" => !empty($row["cast"]) ? $row["cast"] : null,
                "director" => !empty($row["director"]) ? $row["director"] : null, "genre" => $row["genre"] ?? "",
                "release_date" => $row["release_date"] ?? "", "releaseDate" => $row["release_date"] ?? "",
                "last_modified" => $row["last_modified"] ?? "", "rating" => $row["rating"] ?? "0",
                "rating_5based" => floatval($row["rating_5based"] ?? 0),
                "backdrop_path" => !empty($row["backdrop_path"]) ? explode(",", $row["backdrop_path"]) : [],
                "youtube_trailer" => !empty($row["youtube_trailer"]) ? $row["youtube_trailer"] : null,
                "episode_run_time" => $row["episode_run_time"] ?? "0", "category_id" => (string)$row["category_id"],
                "category_ids" => [(string)$row["category_id"]],
            ];
        }
    }

    echo json_encode($results, JSON_UNESCAPED_SLASHES); 
    exit;
}

// ==========================================================
// 7. Info de VOD (Mantido unificado e robusto)
// ==========================================================
if ($action === 'get_vod_info' && isset($_GET['vod_id'])) {
    $vod_id = (int)($_GET['vod_id'] ?? 0);

    $query = $conexao->prepare("SELECT * FROM streams WHERE id = :vod_id");
    $query->execute([":vod_id" => $vod_id]);

    $info = [];
    $movie_data = [];

    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $durationSecs = timeToSeconds($row["duration"] ?? "00:00:00"); 

        $ext = $row["container_extension"] ?? "mp4";
        // A URL COMPLETA DEVE ESTAR AQUI para o Smarters conseguir reproduzir.
        $direct = build_movie_url($baseUrl, $username, $password, $row["id"], $ext);

        $info = [
            "kinopoisk_url" => $row["kinopoisk_url"] ?? "", "tmdb_id" => strval($row["tmdb_id"] ?? ""),
            "name" => htmlspecialchars_decode($row["name"] ?? ""), "o_name" => htmlspecialchars_decode($row["name"] ?? ""),
            "cover_big" => $row["stream_icon"] ?? "", "movie_image" => $row["stream_icon"] ?? "",
            "release_date" => $row["releasedate"] ?? "", "releasedate" => $row["releasedate"] ?? "",
            "episode_run_time" => $row["episode_run_time"] ?? "", "youtube_trailer" => $row["youtube_trailer"] ?? "",
            "director" => $row["director"] ?? "", "actors" => $row["actors"] ?? "", "cast" => $row["actors"] ?? "",
            "description" => $row["plot"] ?? "", "plot" => $row["plot"] ?? "", "age" => $row["age"] ?? "", "mpaa_rating" => "",
            "rating_count_kinopoisk" => intval($row["rating_count_kinopoisk"] ?? 0), "country" => $row["country"] ?? "",
            "genre" => $row["genre"] ?? "", "backdrop_path" => array_filter(explode(",", $row["backdrop_path"] ?? "")),
            "duration_secs" => $durationSecs, "duration" => $row["duration"] ?? "00:00:00", "runtime" => intval($durationSecs/60), 
            "bitrate" => intval($row["bitrate"] ?? 0), "rating" => $row["rating"] ?? "",
            "subtitles" => array_filter(explode(",", $row["subtitles"] ?? "")), "video" => [], "audio" => []
        ];

        $movie_data = [
            "name" => htmlspecialchars_decode($row["name"] ?? ""), "title" => htmlspecialchars_decode($row["name"] ?? ""),
            "year" => $row["year"] ?? "", "added" => $row["added"] ?? null, "stream_id" => (string)$row["id"], 
            "category_id" => (string)$row["category_id"], "category_ids" => [(string)$row["category_id"]],
            "container_extension" => $ext, "custom_sid" => "", "direct_source" => $direct 
        ];
    }

    echo json_encode(["info" => $info, "movie_data" => $movie_data], JSON_UNESCAPED_SLASHES);
    exit;
}

// ==========================================================
// 8. Info de Séries (Mantido unificado)
// ==========================================================
if ($action === 'get_series_info' && isset($_GET['series_id'])) {
    $series_id = filter_input(INPUT_GET, 'series_id', FILTER_VALIDATE_INT);
    if (!$series_id) { http_response_code(400); echo json_encode(["error" => "ID da série inválido."]); exit; }

    $query = $conexao->prepare("SELECT * FROM series WHERE id = :series_id");
    $query->execute([":series_id" => $series_id]);
    $row = $query->fetch(PDO::FETCH_ASSOC);

    if (!$row) { http_response_code(404); echo json_encode(["error" => "Série não encontrada."]); exit; }

    $series = [
        "name" => htmlspecialchars_decode($row["name"] ?? ""), "title" => htmlspecialchars_decode($row["name"] ?? ""),
        "cover" => $row["cover"] ?? "", "year" => $row["year"] ?? "", "plot" => $row["plot"] ?? "", "cast" => $row["cast"] ?? "",
        "director" => $row["director"] ?? "", "genre" => $row["genre"] ?? "", "release_date" => $row["release_date"] ?? "",
        "releaseDate" => $row["release_date"] ?? "", "last_modified" => $row["last_modified"] ?? "", 
        "rating" => $row["rating"] ?? "", "rating_5based" => $row["rating_5based"] ?? "",
        "backdrop_path" => !empty($row["backdrop_path"]) ? explode(",", $row["backdrop_path"]) : [],
        "youtube_trailer" => $row["youtube_trailer"] ?? "", "episode_run_time" => $row["episode_run_time"] ?? "",
        "category_id" => (string)($row["category_id"] ?? ""), "category_ids" => [(string)($row["category_id"] ?? "")]
    ];

    $stmt = $conexao->prepare("SELECT * FROM series_seasons WHERE series_id = :series_id ORDER BY season_number");
    $stmt->execute([":series_id" => $series_id]);
    $seasons = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $seasons[] = [
            "air_date" => $r["air_date"] ?? "", "episode_count" => $r["episode_count"] ?? "", "id" => (string)($r["id"] ?? ""), 
            "name" => htmlspecialchars_decode($r["name"] ?? ""), "overview" => $r["overview"] ?? "",
            "season_number" => (string)($r["season_number"] ?? ""), "cover" => $r["cover"] ?? "", "cover_big" => $r["cover_big"] ?? ""
        ];
    }

    $stmt = $conexao->prepare("SELECT * FROM series_episodes WHERE series_id = :series_id ORDER BY season, episode_num");
    $stmt->execute([":series_id" => $series_id]);
    $episodes = [];

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $season = $r['season'] ?? 0;
        if (!isset($episodes[$season])) { $episodes[$season] = []; }

        $ext = $r["container_extension"] ?? "mp4";
        $direct = build_series_url($baseUrl, $username, $password, $r["id"], $ext);

        $episodes[$season][] = [
            "id" => (string)($r["id"] ?? ""), "episode_num" => (string)($r["episode_num"] ?? ""), "title" => htmlspecialchars_decode($r["title"] ?? ""),
            "container_extension" => $ext,
            "info" => [
                "tmdb_id" => $r["tmdb_id"] ?? "", "duration_secs" => $r["duration_secs"] ?? "", "duration" => $r["duration"] ?? "", 
                "bitrate" => $r["bitrate"] ?? "", "cover_big" => $r["cover_big"] ?? "", "movie_image" => $r["movie_image"] ?? "", 
                "plot" => $r["plot"] ?? ""
            ],
            "subtitles" => !empty($r["subtitles"]) ? explode(",", $r["subtitles"]) : [], "custom_sid" => $r["custom_sid"] ?? "", 
            "added" => $r["added"] ?? "", "season" => (string)$season, "direct_source" => $direct 
        ];
    }

    echo json_encode(["seasons" => $seasons, "info" => $series, "episodes" => $episodes], JSON_UNESCAPED_SLASHES);
    exit;
}

// ==========================================================
// 9. Default: Não Autorizado / Ação Inválida
// ==========================================================
http_response_code(401);
echo json_encode(["user_info" => ["auth" => 0, "msg" => "Ação inválida ou não autorizada."]]);
exit;

?>
