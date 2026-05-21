<?php
session_start();
require_once("menu.php");
require_once("./api/controles/db.php");
$conexao = conectar_bd();

$apps = $conexao->query("SELECT * FROM loja_apps ORDER BY app_nome ASC")->fetchAll();
?>
<style>
    /* Estilo geral da página */
    .page-content {
        /* Remove o padding do container principal para o header ocupar a largura toda */
        padding: 0 !important;
    }
    .loja-wrapper {
        background-color: #2a2e33; /* Fundo escuro para a área dos apps */
        padding: 40px 20px;
    }
    /* Estilo do Cabeçalho (Header) */
    .loja-header {
        position: relative;
        text-align: center;
        padding: 100px 20px;
        color: white;
        background-size: cover;
        background-position: center center;
        /* --- IMPORTANTE: TROQUE A URL DA IMAGEM ABAIXO --- */
        /* Coloque o link para a sua imagem de fundo aqui */
        background-image: url('http://topiptv.app.br/img/marvel.jpeg'); /* Exemplo com imagem genérica */
    }
    .loja-header::after { /* Efeito de escurecimento para o texto ficar legível */
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 1;
    }
    .loja-header-content {
        position: relative;
        z-index: 2;
    }
    .loja-header h1 {
        font-size: 3rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
    }
    .loja-header img {
        max-width: 100px;
        margin-bottom: 15px;
    }

    /* Estilo da Grade de Aplicativos */
    .app-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 25px; 
        max-width: 1400px;
        margin: auto;
    }
    .app-card {
        border: 1px solid #444;
        border-radius: 8px;
        background-color: #383c42;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        overflow: hidden; /* Garante que a imagem não saia do card */
        display: flex;
        flex-direction: column;
    }
    .app-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }
    .app-card .app-thumbnail {
        width: 100%;
        height: 160px; /* Altura fixa para a imagem */
        object-fit: cover; /* Garante que a imagem preencha o espaço sem distorcer */
    }
    .app-card-body {
        padding: 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    .app-card h5 { 
        margin-top: 0;
        margin-bottom: 10px; 
        font-weight: bold; 
        color: #fff; 
        flex-grow: 1;
    }
    
    /* --- ESTILO DO CÓDIGO DOWNLOADER (QUADRADO LARANJA) --- */
    .downloader-code { 
        background-color: #ff8c00; /* Laranja escuro */
        color: white; 
        padding: 8px 12px; 
        border-radius: 5px; 
        font-weight: bold; 
        font-size: 1.1rem;
        display: inline-block; 
        margin: 10px 0 15px 0; 
        user-select: all; /* Facilita copiar o código */
        border: 2px solid #e07b00;
    }

    .btn-download {
        background-color: #0d6efd;
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        padding: 10px;
        border-radius: 5px;
        text-decoration: none;
        display: block;
        margin-top: auto; /* Empurra o botão para o final do card */
    }
    .btn-download:hover {
        background-color: #0b5ed7;
        color: #fff;
    }
</style>

<div class="loja-header">
    <div class="loja-header-content">
        <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="Logo">
        <h1>Central de Aplicativos</h1>
    </div>
</div>

<div class="loja-wrapper">
    <div class="app-grid">
        <?php if (empty($apps)): ?>
            <p class="text-white">Nenhum aplicativo disponível no momento.</p>
        <?php else: ?>
            <?php foreach ($apps as $app): ?>
            <div class="app-card">
                <img src="<?php echo htmlspecialchars($app['app_icone']); ?>" class="app-thumbnail" alt="Ícone do <?php echo htmlspecialchars($app['app_nome']); ?>">
                <div class="app-card-body">
                    <h5><?php echo htmlspecialchars($app['app_nome']); ?></h5>
                    
                    <?php if (!empty($app['app_codigo_downloader'])): ?>
                        <div class="downloader-code" title="Código para o App Downloader"><?php echo htmlspecialchars($app['app_codigo_downloader']); ?></div>
                    <?php endif; ?>
                    
                    <a href="<?php echo htmlspecialchars($app['app_link_download']); ?>" class="btn-download" target="_blank" download>Baixar APK</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>