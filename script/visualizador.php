<?php
// ARQUIVO: visualizador.php (VERSÃO FINAL com CSS FORÇADO)

// Pega a página que queremos exibir a partir da URL
$pagina = $_GET['pagina'] ?? 'filmes';
$caminho_base = 'gerenciador/';
$url_alvo = '';

switch ($pagina) {
    case 'series': $url_alvo = $caminho_base . 'series.php'; break;
    case 'canais': $url_alvo = $caminho_base . 'canais.php'; break;
    case 'filmes': default: $url_alvo = $caminho_base . 'filmes.php'; break;
}

// Vamos tentar pegar o conteúdo dos arquivos de header/footer do seu painel
// para extrair o HTML e injetar nossas correções.
$header_content = '';
$footer_content = '';

if (file_exists('template/header.php')) {
    ob_start();
    include 'template/header.php';
    $header_content = ob_get_clean();
}

if (file_exists('template/footer.php')) {
    ob_start();
    include 'template/footer.php';
    $footer_content = ob_get_clean();
}

// =========================================================================
// INJEÇÃO FORÇADA DA META TAG VIEWPORT E ESTILOS RESPONSIVOS
// Esta parte garante que a tag viewport exista, mesmo que o seu header.php não a tenha.
// =========================================================================
$viewport_tag = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
$force_responsive_css = '
<style>
    /* Força o container principal a usar a largura total */
    body, html {
        width: 100% !important;
        overflow-x: hidden !important;
    }
    /* Adapta containers comuns em painéis de IPTV */
    #wrapper, .main-content, .page-content, .container-fluid {
        width: 100% !important;
        min-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    iframe {
        width: 100%;
        height: 100vh; /* Ocupa 100% da altura da tela */
        border: none;
    }
</style>
';

// Adiciona a nossa tag e o CSS dentro do <head> do seu painel
if (strpos($header_content, '</head>') !== false) {
    $header_content = str_replace('</head>', $viewport_tag . $force_responsive_css . '</head>', $header_content);
} else {
    // Se não encontrar o </head>, adiciona no início (menos ideal)
    $header_content = $viewport_tag . $force_responsive_css . $header_content;
}

// Imprime o cabeçalho modificado
echo $header_content;

?>

<div style="text-align: right; padding: 10px; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
    <a href="/dashboard.php" style="
        display: inline-block;
        padding: 8px 15px;
        background-color: #FF0000;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
        border: none;
        cursor: pointer;
    ">Voltar ao Início</a>
</div>
<iframe src="<?= $url_alvo ?>">
    Seu navegador não suporta iframes.
</iframe>


<?php
// Imprime o rodapé original
echo $footer_content;
?>