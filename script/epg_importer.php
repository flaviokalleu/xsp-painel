<?php
// ##################################################################
// ### ESTILO NEON COM CAIXA CENTRALIZADA (BORDAS GROSSAS E TÍTULO) #
// ##################################################################
echo '<!DOCTYPE html>
<html>
<head>
    <title>Importador de EPG - Neon Ultra</title>
    <style>
        /* Estilos de Fundo */
        body {
            background-color: #0d0d0d; /* Fundo muito escuro */
            display: flex;
            flex-direction: column; /* Permite empilhar o título e a caixa */
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
            font-family: "Consolas", "Courier New", monospace;
            color: #00FFFF; /* Azul Ciano Neon para texto principal */
            font-size: 14px;
        }

        /* Estilo do Título Neon */
        .neon-title {
            color: #FF00FF; /* Roxo/Magenta Neon para o título */
            font-size: 32px;
            font-weight: bold;
            text-shadow: 
                0 0 7px #FF00FF,
                0 0 10px #FF00FF,
                0 0 20px #00FFFF; /* Brilho com toque azul para contraste */
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* Estilo da Caixa Centralizada (Card) */
        .neon-box {
            background-color: #1a1a1a; 
            padding: 30px;
            border-radius: 10px;
            /* Bordas mais grossas e efeito de brilho mais intenso */
            box-shadow: 
                0 0 15px 3px #00FFFF, /* Sombra interna azul, mais espessa */
                0 0 30px 5px #00FFFF, /* Sombra externa azul, mais espessa */
                0 0 60px rgba(255, 0, 255, 0.6); /* Sombra roxa forte */
            width: 90%;
            max-width: 600px; 
            border: 3px solid #00FFFF; /* Bordas visíveis mais grossas */
        }

        /* Mantém a formatação de console dentro da caixa */
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            line-height: 1.6;
        }

        /* Cores Neon */
        strong {
            color: #FF00FF; 
            font-size: 18px;
            display: block;
            margin-top: 20px;
            text-shadow: 0 0 5px #FF00FF;
        }
        .message-success {
            color: #00FF00; 
            font-weight: bold;
        }
        .highlight {
            color: #00BFFF; 
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="neon-title">Importador de EPG</div>
<div class="neon-box">
<pre>';
// ##################################################################
// ### FIM DA SEÇÃO DE ESTILO E INÍCIO DO CONTEÚDO PHP ##############
// ##################################################################

// Aumenta os limites de execução para arquivos grandes
@ini_set('memory_limit', '512M');
@set_time_limit(300); // 5 minutos

// Define o caminho para o arquivo de banco de dados
require_once(__DIR__ . '/api/controles/db.php');

// ########################################################
// ### SEU LINK DE EPG JÁ ESTÁ AQUI ###
// ########################################################
$xmltv_url = 'http://seu_painel/epg.xml';
// ########################################################

echo "Iniciando importação do EPG... (Isso pode demorar alguns minutos)\n<br>";
echo "URL do EPG: <span class=\"highlight\">" . htmlspecialchars($xmltv_url) . "</span>\n<br>";
flush(); // Envia a primeira mensagem para o navegador

$conn = conectar_bd();
if (!$conn) {
    die("Falha ao conectar ao banco de dados.\n");
}

// Limpa dados antigos para manter a tabela otimizada
try {
    echo "Limpando dados antigos... ";
    $conn->exec("DELETE FROM epg_data WHERE end_time < NOW() - INTERVAL 1 DAY");
    echo "<span class=\"message-success\">OK.</span>\n<br>";
    flush();
} catch (PDOException $e) {
    if ($e->getCode() != '42S02') { // Ignora erro se a tabela não existir ainda
        // Usando o roxo/magenta neon para erros
        die("<strong style=\"color: #FF00FF;\">ERRO:</strong> Erro ao limpar dados antigos: " . $e->getMessage() . "\n");
    }
}

// Baixa o novo arquivo XMLTV
echo "Baixando o arquivo EPG do fornecedor... ";
$xml_content = @file_get_contents($xmltv_url);
if ($xml_content === FALSE) {
    // Usando o roxo/magenta neon para erros
    die("<strong style=\"color: #FF00FF;\">ERRO:</strong> Falha ao baixar o arquivo XMLTV. Verifique se o link está correto e acessível.\n");
}
echo "<span class=\"message-success\">OK.</span>\n<br>";
flush();

// Processa o XML
echo "Processando o XML e salvando no banco de dados... ";
try {
    $xml = new SimpleXMLElement($xml_content);
} catch (Exception $e) {
    // Usando o roxo/magenta neon para erros
    die("<strong style=\"color: #FF00FF;\">ERRO:</strong> Erro ao processar o arquivo XML. O conteúdo pode estar mal formatado: " . $e->getMessage() . "\n");
}

$stmt = $conn->prepare(
    "INSERT INTO epg_data (channel_id, start_time, end_time, title, description) 
     VALUES (:channel_id, :start_time, :end_time, :title, :description)"
);

$count = 0;
// Começa a transação para uma inserção em massa muito mais rápida
$conn->beginTransaction(); 
try {
    foreach ($xml->programme as $programme) {
        $channel_id = (string)$programme['channel'];
        
        $start_raw = (string)$programme['start'];
        $stop_raw = (string)$programme['stop'];

        $start_dt = DateTime::createFromFormat('YmdHis O', $start_raw);
        $end_dt = DateTime::createFromFormat('YmdHis O', $stop_raw);
        
        $start = $start_dt ? $start_dt->format('Y-m-d H:i:s') : null;
        $end = $end_dt ? $end_dt->format('Y-m-d H:i:s') : null;

        if (!$start || !$end) {
            continue; 
        }

        $title = (string)$programme->title;
        $desc = isset($programme->desc) ? (string)$programme->desc : null;

        $stmt->execute([
            ':channel_id' => $channel_id,
            ':start_time' => $start,
            ':end_time' => $end,
            ':title' => $title,
            ':description' => $desc
        ]);
        $count++;
    }
    $conn->commit(); 
} catch (Exception $e) {
    $conn->rollBack();
    // Usando o roxo/magenta neon para erros
    die("<strong style=\"color: #FF00FF;\">ERRO:</strong> Erro durante a inserção dos dados: " . $e->getMessage() . "\n");
}

echo "<span class=\"message-success\">OK.</span>\n<br>";
echo "<strong>Importação concluída! <span class=\"highlight\">$count</span> programas foram adicionados.</strong>\n";

// ##################################################################
// ### FECHANDO AS TAGS HTML ########################################
// ##################################################################
echo '</pre>
</div>
</body>
</html>';
// ##################################################################
?>