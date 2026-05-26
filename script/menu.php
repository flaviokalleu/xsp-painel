<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$config_file = 'config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
} else {
    $config = [
        'title' => 'PB&CTV',
        'logo_path' => './img/logo_tranparente2.png'
    ];
}

require_once('./api/controles/checkLogout.php');

checkLogout();

if (isset($_GET['sair'])) {
    $_SESSION = array();
    session_unset();
    session_destroy();

    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    header('Location: ./index.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config['title']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($config['logo_path']); ?>">

    <link rel="stylesheet" type="text/css" href="/css/menu.css">

    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/2.0.7/css/dataTables.dataTables.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="//cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        
    <style>
        :root {
            --bg-main: #ffffff; --bg-sidebar: #cdfdfb; --bg-header: #ffffff; --bg-card: #d9e0ff;
            --text-primary: #212529; --text-secondary: #6c757d; --border-color: #d8d8d8;
            --sidebar-text: #313e47; --sidebar-active-bg: #114f65; --sidebar-active-text: #fff;
            --header-shadow: rgba(0,0,0,0.1);
        }
        [data-theme="dark"] {
            --bg-main: #041f31; --bg-sidebar: #212529; --bg-header: #212529; --bg-card: #2a2e33;
            --text-primary: #e7e7e7; --text-secondary: #b0b3b8; --border-color: #00284f;
            --sidebar-text: #e7e7e7; --header-shadow: rgba(0,0,0,0.4);
        }
        body, .page-content { background-color: var(--bg-main) !important; color: var(--text-primary); transition: background-color 0.3s ease, color 0.3s ease; }
        .navigation { background: var(--bg-sidebar); border-right: 1px solid var(--border-color); }
        header.navbar { background: var(--bg-header) !important; box-shadow: 0 2px 2px var(--header-shadow) !important; }
        .card, .table-card, .modal-content { background-color: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border-color); }
        h4, h5, .text-secondary, .card-label, .text-muted, .section-title { color: var(--text-secondary) !important; }
        .card-value, .card-text, .text-end, .nav-link, .dropdown-item, .modal-title { color: var(--text-primary) !important; }
        .navigation .list a .text.color, .navigation .list a .icon { color: var(--sidebar-text); }
        .dropdown-menu { background-color: var(--bg-card); border: 1px solid var(--border-color); }
        .dropdown-item:hover { background-color: var(--sidebar-active-bg); color: var(--sidebar-active-text) !important; }
        .navigation .list.active { background: var(--sidebar-active-bg) !important; }
        .navigation .list.active a .icon, .navigation .list.active a .text.color { color: var(--sidebar-active-text) !important; }
        #theme-toggle .fa-sun { display: none; } #theme-toggle .fa-moon { display: block; }
        [data-theme="dark"] #theme-toggle .fa-sun { display: block; } [data-theme="dark"] #theme-toggle .fa-moon { display: none; }
        [data-theme="dark"] #theme-toggle { color: var(--sidebar-active-bg) !important; }
        [data-theme="light"] header.navbar, [data-theme="light"] .dropdown-toggle { color: #333 !important; }
        [data-theme="dark"] header.navbar, [data-theme="dark"] .dropdown-toggle { color: #FFF !important; }
        @media (max-width: 767px) {
            .navigation:not(.active) { width: 0; overflow: hidden; border-right: none; }
            .page-content:not(.active) { margin-left: 0 !important; padding-left: 15px !important; }
        }
        .color-card { border-radius: 5px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border: none; transition: all 0.3s ease; color: #FFFFFF; display: flex; flex-direction: column; height: 100%; }
        .color-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12); }
        .color-card .card-body { display: flex; flex-direction: row; align-items: center; padding: 10px; flex-grow: 1; }
        .color-card .icon-container { font-size: 2.5rem; margin-right: 10px; opacity: 0.8; }
        .color-card .card-text-content { flex-grow: 1; text-align: right; }
        .color-card .card-label { font-size: 0.9rem; font-weight: 400; margin-bottom: 5px; color: rgba(255, 255, 255, 0.9); text-transform: uppercase; }
        .color-card .card-value { font-size: 2rem; font-weight: 700; color: #ffffff; margin: 0; line-height: 1; }

        @media (max-width: 767px) {
            .right-side-content {
                flex-grow: 1;
                justify-content: flex-end;
            }
            .right-side-content .btn,
            .right-side-content .badge {
                padding: 0.375rem 0.6rem;
                margin: 0 2px !important;
                font-size: 0.75rem;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
<header class="fixed-top navbar navbar-expand-lg" style="transition: 0.5s; padding: 0;">
    <div class="container-fluid" style="height: 3em;display: flex;align-items: center;">
        <div class="align-items-center d-flex left-side-content">
            <div class="pl-2 pr-2">
                <div class="m-0 navbar-brand w-100" style="display: flex; align-items: flex-end;">
                    <img alt="lightning logo" src="<?php echo htmlspecialchars($config['logo_path']); ?>" width="35px" height="35px" class="logo">
                    <span class="ml-1 text-logo"><?php echo htmlspecialchars($config['title']); ?></span>
                </div>
            </div>
            <div class="col-md-auto menuToggle btn1" style="width: 40px;height: 40px;">
                <i class="fa fa-bars"></i>
            </div>
        </div>
        <div class="align-items-center d-flex right-side-content">
            <button id="theme-toggle" class="btn btn-sm" style="font-size: 1.2rem;">
                <i class="fas fa-moon"></i>
                <i class="fas fa-sun"></i>
            </button>
            
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownTesteRapido" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bolt"></i> Teste Rápido
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownTesteRapido">
                    <li>
                        <a class="dropdown-item" href="#" onclick='modal_master("api/testes.php", "gerar_teste_rapido", "1")'>
                            <i class="fas fa-tv me-2"></i> Teste IPTV
                        </a>
                    </li>
                    <li>
                        <form action="action_p2p_gerar_teste.php" method="POST" class="d-inline">
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-list-ol me-2"></i> Teste Código P2P
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <div class="badge bg-success d-none d-md-flex align-items-center">
                <span class="j_credits" style="margin-right: 5px; opacity: 1;" id="creditos"> </span> Creditos
            </div>

            <div class="dropdown ms-2">
                <button class="btn header-item waves-effect dropdown-toggle" type="button" id="dropdownUser" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="https://i.postimg.cc/h422Lz69/1005064161.png" alt="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" style="width: 25px; height: 23px;">
                    <span class="d-none d-xl-inline-block ms-1"> <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?> </span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownUser">
                    <li class="d-md-none">
                        <div class="dropdown-item-text">
                            <strong>Creditos: <span id="creditos-mobile"></span></strong>
                        </div>
                    </li>
                    <li class="d-md-none"><hr class="dropdown-divider"></li>
                    <li>
                        <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
                            <a class="dropdown-item" onclick='modal_master("api/revendedores.php", "edite_admin", "edite")'>Editar Admin</a>
                        <?php endif ?>
                        <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 0): ?>
                            <a class="dropdown-item" onclick='modal_master("api/revendedores.php", "edite_admin_revenda", "edite")'>Editar Senha</a>
                        <?php endif ?>
                        <a class="dropdown-item" href="?sair">Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
<div class="navigation">
    <div class="container-fluid mb-3 menu-perfil p-0">
        <div class="align-items-center d-flex justify-content-center mb-1 menu-perfil1 p-2 pb-3 pt-4">
            <div class="justify-content-center align-items-center menu-perfil1">
                <div class="mb-4 perfil-foto">
                    <div class="m-auto mb-1 rounded-circle overflow-hidden">
                        <img class="img-fluid" src="https://i.postimg.cc/h422Lz69/1005064161.png" alt="">
                    </div>
                </div>
                <div class="text-center perfil-info" style="border-bottom: 1px solid var(--border-color);">
                    <p class="mb-0 text-uppercase" style="font-size: .9rem; font-weight: 800;"> <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?> </p>
                    <small class="text-uppercase role" style="font-size: .7rem; letter-spacing: 1px; font-weight: 500;" id="tipo_admin"> </small>
                    <br/>
                </div>
                <div class="perfil-info text-center">
                    <div class="mb-0 text-uppercase d-flex justify-content-between align-items-center" style="font-size: .9rem;font-weight: bolder;font-family: monospace;"> Creditos <span class="badge bg-success">
                            <i class="fa-solid fa-cent-sign pr-1">:</i> <span id="creditos2"> </span> </span>
                    </div>
                    <div class="mb-0 text-uppercase d-flex justify-content-between align-items-center" style="font-size: .9rem;font-weight: bolder;font-family: monospace;" id="vencimento">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="menuToggle2"></div>
    <ul class="p-0">
        <li class="list" id="dashboard">
            <a href="dashboard.php" class="clr">
                <i class="fa-solid fa-chart-line" style="color: rgb(99, 230, 190);"></i>
                <span class="text color">Dashboard</span>
            </a>
        </li>
        <li class="list" id="online">
            <a href="clientes_online.php" class="clr" target="_top">
                <i class="fa-solid fa-users" style="color: rgb(17, 122, 133);"></i>
                <span class="text color">Usuarios Online</span>
            </a>
        </li>
        <li class="list" id="dashboard_custom">
            <a href="dashboard_custom.php" class="clr">
                <i class="fa-solid fa-square-plus" style="color: rgb(2, 128, 73);"></i>
                <span class="text color">Conteudos Novos</span>
            </a>
        </li>
        <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
        <li class="list sub" id="Conteudos">
            <a class="clr">
                <i class="fa-solid fa-gear" style="color: rgb(255, 0, 0);"></i>
                <span class="text color">Conteudos</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class="sidebar-submenu">
                <ul class="ps-2">
                    <li><a href="gerenciar_categorias.php"><i class="fas fa-table-list"></i><span class="text color">Categorias</span></a></li>
                    <li><a href="gerenciar_bouquets.php"><i class="fas fa-layer-group"></i><span class="text color">Bouquets</span></a></li>
                    <li><a href="visualizador.php?pagina=canais"><i class="fas fa-tv"></i><span class="text color">Canais</span></a></li>
                    <li><a href="visualizador.php?pagina=filmes"><i class="fas fa-clapperboard"></i><span class="text color">Filmes</span></a></li>
                    <li><a href="visualizador.php?pagina=series"><i class="fas fa-film"></i><span class="text color">Series</span></a></li>
                    <li><a href="uploud.php"><i class="fa-solid fa-arrow-up-from-bracket"></i><span class="text color">Uploud</span></a></li>
                    <li><a href="importador_m3u"><i class="fa-solid fa-arrow-up-from-bracket"></i><span class="text color">Importar m3u</span></a></li>
                </ul>
            </div>
        </li>
        <li class="list sub" id="ferramentas">
            <a class="clr">
                <i class="fa-solid fa-screwdriver-wrench fa-beat" style="color: rgb(0, 125, 138);"></i>
                <span class="text color">Ferramentas</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class="sidebar-submenu">
                <ul class="ps-2 text-nowrap">
                    <li><a href="gerenciamento.php"><i class="fa-solid fa-eraser text-warning"></i><span class="text color">Gerenciar Conteúdo em Massa</span></a></li>
                    <li><a href="excluir_listas.php"><i class="fa-solid fa-calendar-times text-danger"></i><span class="text color">Excluir Listas</span></a></li>
                    <li>
                        <a href="atualizador" style="color: white !important; font-size: 14px !important;">
                            <i class="fas fa-sync-alt"></i> <span>Atualizar TMDB</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <?php endif; ?>
        <li class="list sub" id="clientes">
            <a class="clr">
                <i class="fa-solid fa-users" style="color: rgb(17, 122, 133);"></i>
                <span class="text color">Clientes</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            <div class="sidebar-submenu">
                <ul class="ps-2">
                    <li><a href="clientes.php"><i class="fa-solid fa-user"></i><span class="text color">Clientes</span></a></li>
                    <li><a href="testes.php"><i class="fa-solid fa-user"></i><span class="text color">Testes</span></a></li>
                    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] <= 1): ?>
                    <li><a href="migrar_clientes.php"><i class="fa-solid fa-people-arrows text-dark"></i><span class="text color">Migrar Clientes</span></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <li class="list sub" id="codigos_p2p_menu">
            <a class="clr">
                <i class="fas fa-spray-can" style="color: rgb(0, 125, 138);"></i>
                <span class="text color">Códigos P2P</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class="sidebar-submenu">
                <ul class="ps-2">
                    <li><a href="codigos_p2p.php"><i class="fa-solid fa-user-check"></i><span class="text color">Gerenciar Clientes</span></a></li>
                    <li><a href="testes_p2p.php"><i class="fa-solid fa-user-clock"></i><span class="text color">Testes P2P</span></a></li>
                </ul>
            </div>
        </li>
        <li class="list sub" id="revenda_menu">
            <a class="clr">
                <i class="fa-solid fa-id-card-clip" style="color: rgb(0, 125, 138);"></i>
                <span class="text color">Revenda</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class="sidebar-submenu">
                <ul class="ps-2">
                    <?php if (isset($_SESSION['plano_admin']) && $_SESSION['plano_admin'] != 1): ?>
                    <li><a href="revendedores.php"><i class="fa-solid fa-users"></i><span class="text color">Revendedores</span></a></li>
                    <?php endif; ?>
                    <li><a href="gerenciar_revendedores.php"><i class="fa-solid fa-users-cog"></i><span class="text color">Gestão de Revenda</span></a></li>
                </ul>
            </div>
        </li>
        <li class="list sub" id="relatorios">
            <a class="clr">
                <i class="fa-solid fa-money-bill-wave" style="color: rgb(0, 125, 138);"></i>
                <span class="text color">Relatorios</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class="sidebar-submenu">
                <ul class="ps-2">
                    <li><a href="log_creditos.php"><i class="fa-solid fa-history"></i><span class="text color">Log de Creditos</span></a></li>
                </ul>
            </div>
        </li>
        <li class="list sub" id="aplicacoes">
            <a class="clr">
                <i class="fa-solid fa-shuttle-space" style="color: rgb(0, 106, 117);"></i>
                <span class="text color">Aplicações</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class="sidebar-submenu">
                <ul class="ps-2">
                    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
                    <li><a href="admin_loja.php"><i class="fas fa-store-alt"></i><span class="text color">Gerenciar Loja</span></a></li>
                    <?php else: ?>
                    <li><a href="loja.php"><i class="fas fa-store"></i><span class="text color">Loja de Aplicativos</span></a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] != 1): ?>
                    <li><a href="pedidos.php"><i class="fas fa-plus-square"></i><span class="text color">Pedido de VODs</span></a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
                    <li><a href="admin_pedidos.php"><i class="fas fa-tasks"></i><span class="text color">Gerenciar Pedidos</span></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <li class="list" id="chatbot_regras">
            <a href="chatbot_integrado_painel.php" class="clr">
                <i class="fa-solid fa-robot" style="color: rgb(0, 106, 117);"></i>
                <span class="text color">ChatBot</span>
            </a>
        </li>
        <li class="list" id="pagamentos">
            <a href="pagamentos_config.php" class="clr">
                <i class="fa-solid fa-circle-dollar-to-slot" style="color: rgb(0, 106, 117);"></i>
                <span class="text color">Pagamento</span>
            </a>
        </li>
        <li class='list sub' id='configuracoes'>
            <a class='clr'>
                <i class="fa-brands fa-whmcs" style="color: rgb(0, 106, 117);"></i>
                <span class='text color'>Settings</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class='sidebar-submenu'>
                <ul class="ps-2 text-nowrap">
                    <li><a href='planos.php'><i class='fa-solid fa-server'></i><span class='text color'>Planos</span></a></li>
                    <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
                    <li><a href="ip_manager.php"><i class="fa-solid fa-user-shield text-danger"></i><span class="text color">Gerenciar IPs Bloqueados</span></a></li>
                    <li><a href="atualizar_epg.php"><i class="fa-solid fa-calendar-days text-info"></i><span class="text color">Atualizar EPG</span></a></li>
                    <li><a href="alterar_links.php"><i class="fa-solid fa-link text-warning"></i><span class="text color">Alterar Links em Massa</span></a></li>
                    <li><a href="gerenciador_db.php"> <i class="fa-solid fa-database text-success"></i><span class="text color">Backup do Sistema</span></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <?php if (isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1): ?>
        <li class='list sub' id='painel'>
            <a class='clr'>
                <i class="fa-solid fa-outdent" style="color: rgb(0, 106, 117);"></i>
                <span class='text color'>Painel</span>
                <i class="fa-solid fa-angle-right" style="color: rgb(103, 145, 150);"></i>
            </a>
            <div class='sidebar-submenu'>
                <ul class="ps-2">
                    <li><a href="personalizar.php"><i class="fa-solid fa-paint-roller"></i><span class="text color">Personalizar</span></a></li>
                    <li><a href="editar_template.php"><i class="fas fa-file-alt text-dark"></i><span class="text color">Editar Template</span></a></li>
                </ul>
            </div>
        </li>
        <li class="list" id="configuracoes_p2p">
            <a href="p2p_config.php" class="clr">
                <i class="fa-solid fa-sliders" style="color: rgb(0, 106, 117);"></i>
                <span class="text color">Configurações P2P</span>
            </a>
        </li>
        <?php endif; ?>
        <div class="indicator"></div>
    </ul>
</div>

<script>
    (function() {
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        function applyTheme(theme) {
            htmlElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);
    })();

    $(document).ready(function () {
        var currentURL = window.location.pathname.split('/').pop();
        currentURL = currentURL.replace('.php', '').replace('.html', '');
        
        if (currentURL === 'pedidos' || currentURL === 'admin_pedidos' || currentURL === 'loja' || currentURL === 'admin_loja') {
            $('#aplicacoes').addClass('active');
        } else if (currentURL === 'planos' || currentURL === 'atualizar_epg' || currentURL === 'alterar_links' || currentURL.startsWith('gerenciador_db') || currentURL === 'gerenciar_ips') { 
            $('#configuracoes').addClass('active');
        } else if (currentURL === 'personalizar') {
            $('#painel').addClass('active');
        } else if (currentURL === 'categorias' || currentURL === 'canais' || currentURL === 'filmes' || currentURL === 'serie' || currentURL === 'uploud' || currentURL === 'divisor-m3u' || currentURL === 'alterar_links' || currentURL === 'gerenciar_categorias' || currentURL === 'gerenciar_bouquets') { // Adicionado gerenciar_bouquets
            $('#Conteudos').addClass('active');
        } else if (currentURL === 'live' || currentURL === 'vod' || currentURL === 'series') {
            $('#vereditarpagar').addClass('active');
        } else if (currentURL === 'importar-live' || currentURL === 'importar-vod' || currentURL === 'importar-series') {
            $('#importar').addClass('active');
        } else if (currentURL === 'clientes' || currentURL === 'testes' || currentURL === 'sub-revenda' || currentURL === 'migrar_clientes') { // REMOVIDO 'revendedores' e 'gerenciar_revendedores'
            $('#clientes').addClass('active');
        } else if (currentURL === 'revendedores' || currentURL === 'gerenciar_revendedores') { // ADICIONADA NOVA CONDIÇÃO PARA REVENDA
            $('#revenda_menu').addClass('active');
        } else if (currentURL === 'log_creditos') {
            $('#relatorios').addClass('active');
        } else if (currentURL === 'add_serie') {
            $('#Adicionar').addClass('active');
        } 
        else if (currentURL === 'conteudo_novo') {
            $('#conteudo_novo').addClass('active');
        } 
        else if (currentURL === 'chatbot_regras' || currentURL === 'chatbot_criar_regra' || currentURL === 'chatbot_editar_regra' || currentURL === 'chatbot_integrado_painel') {
            $('#chatbot_regras').addClass('active');
        }
        else {
            $('li').each(function () {
                if ($(this).attr('id') === currentURL) {
                    $(this).addClass('active');
                }
            });
        }
        
        $('.menuToggle, .menuToggle2').click(function () {
            $('.navigation').toggleClass('active');
            $('.page-content').toggleClass('active');
            $('.navbar').toggleClass('active');
        });

        $('.list').click(function () {
            $('.list').removeClass('active');
            $(this).addClass('active');
        });
        $('.list.sub').click(function () {
            $('.navigation').addClass('active');
            $('.page-content').addClass('active');
            $('.navbar').addClass('active');
        });
    });

    function addActiveClassOnLargeScreen() {
        const screenWidth = window.innerWidth;
        const elements = $('.navigation, .page-content, .navbar, .text-logo'); 
        if (screenWidth >= 768) { elements.addClass('active');
        } else { elements.removeClass('active'); }
    }
    $(document).ready(function () { addActiveClassOnLargeScreen(); });
    window.addEventListener('resize', () => { addActiveClassOnLargeScreen(); });
</script>

<script>
    var SESSION_TOKEN = '<?php echo isset($_SESSION['token']) ? $_SESSION['token'] : ""; ?>';
</script>

<script src="./js/sweetalert2.js"></script>
<script src="./js/custom.js?v=1"></script>

<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<main class="active overflow-auto page-content w-100" style="position: absolute; height: 100%;">
    
    
    
    
    
    
    
    
    
    
    <div class="modal fade" id="modal_p2p_info" tabindex="-1" aria-labelledby="modal_p2p_info_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title" id="modal_p2p_info_label">✅ Acesso Criado / Renovado!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <pre id="p2p-info-content" class="bg-dark text-white p-3 rounded" style="white-space: pre-wrap; word-break: break-all;"></pre>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="copiarInfoP2P()"><i class="fas fa-copy"></i> Copiar</button>
                <a id="p2p-whatsapp-link" href="#" target="_blank" class="btn btn-success"><i class="fab fa-whatsapp"></i> Enviar</a>
            </div>
        </div>
    </div>
</div>

<script>
function exibirModalP2P(titulo, mensagem) {
    document.getElementById('modal_p2p_info_label').innerText = titulo;
    document.getElementById('p2p-info-content').innerText = mensagem;
    document.getElementById('p2p-whatsapp-link').href = `https://api.whatsapp.com/send?text=${encodeURIComponent(mensagem)}`;
    
    var infoModal = new bootstrap.Modal(document.getElementById('modal_p2p_info'));
    infoModal.show();
}

function copiarInfoP2P() {
    var textoParaCopiar = document.getElementById('p2p-info-content').innerText;
    navigator.clipboard.writeText(textoParaCopiar).then(() => {
        // Usa a sua função de alerta existente, se tiver uma, ou um alert padrão.
        // Substitua SweetAlert3 pelo nome correto da sua função de alerta, se for diferente.
        if (typeof SweetAlert3 !== 'undefined') {
            SweetAlert3('Texto copiado para a área de transferência!', 'success');
        } else {
            alert('Texto copiado!');
        }
    }).catch(err => {
        console.error('Erro ao copiar: ', err);
    });
}
</script> 
    
    
    
    
    
    
    
    
    
    <div class="container-fluid">
