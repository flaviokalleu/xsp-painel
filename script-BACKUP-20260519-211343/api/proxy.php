<?php
// Define um tempo limite maior para o download, caso a lista seja grande
set_time_limit(300);

// Pega a URL da lista M3U que o javascript enviou
$m3u_url = $_GET['url'] ?? '';

// Validação básica da URL
if (empty($m3u_url) || !filter_var($m3u_url, FILTER_VALIDATE_URL)) {
    http_response_code(400); // Bad Request
    die("URL inválida ou não fornecida.");
}

// Inicia o cURL para baixar o conteúdo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $m3u_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Importante para seguir redirecionamentos
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Timeout de 2 minutos

// ===== AQUI ESTÁ A CORREÇÃO DO USER-AGENT =====
// O script vai se "fingir" de um aplicativo VLC para não ser bloqueado
curl_setopt($ch, CURLOPT_USERAGENT, 'VLC/3.0.0');

$content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Verifica se houve erro no download
if ($content === false || $http_code >= 400) {
    http_response_code($http_code > 0 ? $http_code : 500);
    die("Erro ao baixar a lista do fornecedor. Código de Status: $http_code. Erro do cURL: $error");
}

// Se tudo deu certo, envia o conteúdo da lista M3U de volta para o javascript
header('Content-Type: application/x-mpegURL');
echo $content;
?>
