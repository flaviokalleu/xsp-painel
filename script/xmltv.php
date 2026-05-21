<?php
// Define o caminho para o arquivo de banco de dados
require_once(__DIR__ . '/api/controles/db.php');

// Tenta aumentar o limite de memória para gerar o arquivo, caso seja muito grande
@ini_set('memory_limit', '512M');

$conn = conectar_bd();
if (!$conn) { 
    // Se não conectar ao BD, retorna um erro HTTP para o aplicativo
    http_response_code(500);
    die("Erro de conexão com o banco de dados."); 
}

// --- Início da Geração do XML ---
header("Content-type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<!DOCTYPE tv SYSTEM "xmltv.dtd">' . PHP_EOL;
echo '<tv generator-info-name="SeuPainelIPTV">' . PHP_EOL;

try {
    // 1. Gerar a lista de canais que têm um EPG ID associado
    $stmt_channels = $conn->query("SELECT id, name, stream_icon, epg_channel_id FROM streams WHERE stream_type = 'live' AND epg_channel_id IS NOT NULL AND epg_channel_id != ''");
    while ($channel = $stmt_channels->fetch(PDO::FETCH_ASSOC)) {
        echo '  <channel id="' . htmlspecialchars($channel['epg_channel_id']) . '">' . PHP_EOL;
        echo '    <display-name>' . htmlspecialchars($channel['name']) . '</display-name>' . PHP_EOL;
        if (!empty($channel['stream_icon'])) {
            echo '    <icon src="' . htmlspecialchars($channel['stream_icon']) . '" />' . PHP_EOL;
        }
        echo '  </channel>' . PHP_EOL;
    }

    // 2. Gerar a lista de programas dos últimos 2 dias e próximos 7 dias
    $stmt_programmes = $conn->query("SELECT * FROM epg_data WHERE start_time BETWEEN NOW() - INTERVAL 2 DAY AND NOW() + INTERVAL 7 DAY ORDER BY start_time ASC");
    while ($prog = $stmt_programmes->fetch(PDO::FETCH_ASSOC)) {
        // Formata as datas para o padrão XMLTV (ex: 20240115140000 +0000)
        $start_formatted = date('YmdHis O', strtotime($prog['start_time']));
        $end_formatted = date('YmdHis O', strtotime($prog['end_time']));

        echo '  <programme start="' . $start_formatted . '" stop="' . $end_formatted . '" channel="' . htmlspecialchars($prog['channel_id']) . '">' . PHP_EOL;
        echo '    <title lang="pt">' . htmlspecialchars($prog['title']) . '</title>' . PHP_EOL;
        if (!empty($prog['description'])) {
            echo '    <desc lang="pt">' . htmlspecialchars($prog['description']) . '</desc>' . PHP_EOL;
        }
        echo '  </programme>' . PHP_EOL;
    }

} catch (PDOException $e) {
    // Em caso de erro, apenas loga e encerra o XML de forma válida
    error_log("Erro ao gerar XMLTV: " . $e->getMessage());
}

echo '</tv>';
?>