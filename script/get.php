<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

// ============================================================
// GET — geração de playlist M3U (Xtream Codes compatible)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $username = $_GET['username'] ?? '';
    $password = $_GET['password'] ?? '';
    $type     = $_GET['type']     ?? 'm3u_plus';
    $output   = $_GET['output']   ?? 'ts';

    if (empty($username) || empty($password)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'username e password são obrigatórios.']);
        exit;
    }

    $conexao = conectar_bd();
    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE usuario = ? AND senha = ?");
    $stmt->execute([$username, $password]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Credenciais inválidas.']);
        exit;
    }

    // Verifica vencimento
    if (!empty($cliente['Vencimento']) && strtotime($cliente['Vencimento']) < time()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Conta expirada.']);
        exit;
    }

    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base    = $scheme . '://' . $host;
    $adulto  = (int)($cliente['adulto'] ?? 0);

    // Filtro de bouquet
    $allowed_cats = null;
    if (!empty($cliente['bouquet_id'])) {
        $sb = $conexao->prepare("SELECT category_id FROM bouquet_items WHERE bouquet_id = ?");
        $sb->execute([$cliente['bouquet_id']]);
        $allowed_cats = $sb->fetchAll(PDO::FETCH_COLUMN);
        if (empty($allowed_cats)) $allowed_cats = [0];
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="playlist.m3u"');

    echo "#EXTM3U\n";

    // ---- CANAIS AO VIVO ----
    $sql = "SELECT s.*, c.nome AS cat_nome
            FROM streams s
            LEFT JOIN categoria c ON c.id = s.category_id AND c.type = 'live'
            WHERE s.stream_type = 'live'";
    $params = [];
    if ($adulto === 0) { $sql .= " AND s.is_adult = '0'"; }
    if ($allowed_cats !== null) {
        $ph = implode(',', array_fill(0, count($allowed_cats), '?'));
        $sql .= " AND s.category_id IN ($ph)";
        $params = array_merge($params, $allowed_cats);
    }
    $sql .= " ORDER BY c.nome ASC, s.name ASC";
    $st = $conexao->prepare($sql);
    $st->execute($params);

    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $tvg_id   = htmlspecialchars($row['epg_channel_id'] ?? '', ENT_QUOTES);
        $tvg_name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES);
        $logo     = htmlspecialchars($row['stream_icon'] ?? '', ENT_QUOTES);
        $group    = htmlspecialchars($row['cat_nome'] ?? 'Sem Categoria', ENT_QUOTES);
        $url      = "{$base}/live/{$username}/{$password}/{$row['id']}.{$output}";
        echo "#EXTINF:-1 tvg-id=\"{$tvg_id}\" tvg-name=\"{$tvg_name}\" tvg-logo=\"{$logo}\" group-title=\"{$group}\",{$tvg_name}\n";
        echo $url . "\n";
    }

    // ---- FILMES (somente m3u_plus) ----
    if ($type === 'm3u_plus') {
        $sql2 = "SELECT s.*, c.nome AS cat_nome
                 FROM streams s
                 LEFT JOIN categoria c ON c.id = s.category_id AND c.type = 'movie'
                 WHERE s.stream_type = 'movie'";
        $params2 = [];
        if ($adulto === 0) { $sql2 .= " AND s.is_adult = '0'"; }
        if ($allowed_cats !== null) {
            $ph = implode(',', array_fill(0, count($allowed_cats), '?'));
            $sql2 .= " AND s.category_id IN ($ph)";
            $params2 = array_merge($params2, $allowed_cats);
        }
        $sql2 .= " ORDER BY c.nome ASC, s.name ASC";
        $st2 = $conexao->prepare($sql2);
        $st2->execute($params2);

        while ($row = $st2->fetch(PDO::FETCH_ASSOC)) {
            $tvg_name = htmlspecialchars($row['name'] ?? '', ENT_QUOTES);
            $logo     = htmlspecialchars($row['stream_icon'] ?? '', ENT_QUOTES);
            $group    = htmlspecialchars($row['cat_nome'] ?? 'Filmes', ENT_QUOTES);
            $ext      = $row['container_extension'] ?? 'mp4';
            $url      = "{$base}/movie/{$username}/{$password}/{$row['id']}.{$ext}";
            echo "#EXTINF:-1 tvg-name=\"{$tvg_name}\" tvg-logo=\"{$logo}\" group-title=\"{$group}\",{$tvg_name}\n";
            echo $url . "\n";
        }

        // ---- SÉRIES ----
        $sql3 = "SELECT se.*, s.name AS serie_nome, c.nome AS cat_nome
                 FROM series_episodes se
                 INNER JOIN series s ON s.id = se.series_id
                 LEFT JOIN categoria c ON c.id = s.category_id AND c.type = 'series'";
        $params3 = [];
        if ($adulto === 0) { $sql3 .= " WHERE s.is_adult = '0'"; }
        else               { $sql3 .= " WHERE 1=1"; }
        if ($allowed_cats !== null) {
            $ph = implode(',', array_fill(0, count($allowed_cats), '?'));
            $sql3 .= " AND s.category_id IN ($ph)";
            $params3 = array_merge($params3, $allowed_cats);
        }
        $sql3 .= " ORDER BY s.name ASC, se.season ASC, se.episode_num ASC";
        $st3 = $conexao->prepare($sql3);
        $st3->execute($params3);

        while ($row = $st3->fetch(PDO::FETCH_ASSOC)) {
            $serie = htmlspecialchars($row['serie_nome'] ?? '', ENT_QUOTES);
            $title = htmlspecialchars($row['title'] ?? '', ENT_QUOTES);
            $logo  = htmlspecialchars($row['cover_big'] ?? $row['movie_image'] ?? '', ENT_QUOTES);
            $group = htmlspecialchars($row['cat_nome'] ?? 'Séries', ENT_QUOTES);
            $ext   = $row['container_extension'] ?? 'mp4';
            $ep    = 'S' . str_pad($row['season'] ?? 1, 2, '0', STR_PAD_LEFT)
                   . 'E' . str_pad($row['episode_num'] ?? 1, 2, '0', STR_PAD_LEFT);
            $name  = "{$serie} {$ep} - {$title}";
            $url   = "{$base}/series/{$username}/{$password}/{$row['id']}.{$ext}";
            echo "#EXTINF:-1 tvg-name=\"{$name}\" tvg-logo=\"{$logo}\" group-title=\"{$group}\",{$name}\n";
            echo $url . "\n";
        }
    }

    exit;
}

// ============================================================
// POST — atualizar links em massa
// ============================================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conexao = conectar_bd();
if (!$conexao) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro fatal: não foi possível conectar ao banco de dados."]);
    exit();
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $texto_procurar   = $data['link_m3u']  ?? '';
    $texto_substituir = $data['nova_url']  ?? '';

    if (empty($texto_procurar)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "O campo 'Link da M3U' não pode estar vazio."]);
        exit();
    }

    $conexao->beginTransaction();
    $total = 0;

    foreach (['streams' => 'stream_source', 'movies' => 'stream_source', 'series_episodes' => 'stream_source'] as $table => $col) {
        try {
            $st = $conexao->prepare("UPDATE `{$table}` SET `{$col}` = REPLACE(`{$col}`, ?, ?)");
            $st->execute([$texto_procurar, $texto_substituir]);
            $total += $st->rowCount();
        } catch (PDOException $e) { /* tabela pode não existir */ }
    }

    $conexao->commit();
    echo json_encode(["status" => "success", "message" => "Atualização concluída! {$total} links modificados."]);

} catch (PDOException $e) {
    $conexao->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro: " . $e->getMessage()]);
}
