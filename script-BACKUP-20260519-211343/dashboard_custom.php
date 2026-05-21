<?php
// Inclua seu arquivo de conexão e verificação de login
require_once('api/controles/db.php');
session_start();

// Conectar ao banco de dados
$conn = conectar_bd();

// Se a conexão falhar, o script para.
if (!$conn) {
    die("Ocorreu um erro crítico e não foi possível conectar ao banco de dados. Verifique os logs do servidor.");
}

// --- CONSULTAS PARA OS CONTADORES (USANDO PDO) ---
$stmt_canais_total = $conn->query("SELECT COUNT(id) as total FROM streams WHERE stream_type = 'live'");
$total_canais = $stmt_canais_total->fetch(PDO::FETCH_ASSOC)['total'];

$stmt_filmes_total = $conn->query("SELECT COUNT(id) as total FROM streams WHERE stream_type = 'movie'");
$total_filmes = $stmt_filmes_total->fetch(PDO::FETCH_ASSOC)['total'];

$stmt_series_total = $conn->query("SELECT COUNT(id) as total FROM series");
$total_series = $stmt_series_total->fetch(PDO::FETCH_ASSOC)['total'];

// --- CONSULTAS PARA AS LISTAS DE NOVIDADES (USANDO PDO) ---
// Novos Canais
$novos_canais_stmt = $conn->query("SELECT name, added FROM streams WHERE stream_type = 'live' ORDER BY added DESC LIMIT 20");

// Novos Filmes
$novos_filmes_stmt = $conn->query("SELECT name, stream_icon, added FROM streams WHERE stream_type = 'movie' ORDER BY added DESC LIMIT 20");

// Novas Séries
$novas_series_stmt = $conn->query("SELECT name, cover, release_date FROM series ORDER BY release_date DESC LIMIT 20");

// Verificação de permissão
$isAdmin = isset($_SESSION['nivel_admin']) && $_SESSION['nivel_admin'] == 1;

// INCLUSÃO DO MENU
require_once("menu.php");

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Conteúdo</title>
    <link rel="stylesheet" href="path/to/your/panel/style.css"> 
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Lê o tema (claro ou escuro) salvo pelo painel principal e o aplica.
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <style>
        /* --- VARIÁVEIS DE TEMA --- */
        :root {
            /* Tema Claro */
            --bg-body: #002c6f;
            --bg-list: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-list: #dee2e6;
            --border-header: #dee2e6;
            --btn-copy-bg: #59a59b;
        }
        [data-theme="dark"] {
            /* Tema Escuro */
            --bg-body: #16191c;
            --bg-list: #2a2e33;
            --text-primary: #e4e6eb;
            --text-secondary: #b0b3b8;
            --border-list: #3a3f44;
            --border-header: #3a3f44;
            --btn-copy-bg: #4a69bd;
        }

        /* --- ESTILOS GERAIS ADAPTADOS AO TEMA --- */
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Teko", Arial, sans-serif; 
            background-color: var(--bg-body); 
            color: var(--text-primary); 
            margin: 0; 
            padding: 20px; 
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-header);
        }
        .page-title {
            font-size: 1.8em;
            color: var(--text-primary);
            margin: 0;
        }
        
        .container { display: flex; flex-wrap: wrap; gap: 20px; }
        
        .counter-box { 
            flex: 1; 
            min-width: 250px; 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
        }
        .counter-box h1 { font-size: 3em; margin: 0 0 10px 0; }
        
        .content-list { 
            flex: 1; 
            min-width: 320px; 
            background-color: var(--bg-list); 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            color: var(--text-primary);
        }
        .content-list h3 { 
            border-bottom: 1px solid var(--border-list); 
            padding-bottom: 10px; 
            margin-top: 0; 
            color: var(--text-primary);
        }
        .content-list p, .content-list span {
             color: var(--text-primary);
        }
        .content-list small {
             color: var(--text-secondary);
        }

        .content-item { display: flex; align-items: center; margin-bottom: 12px; font-size: 0.9em; }
        .content-item img { width: 40px; height: 60px; margin-right: 15px; border-radius: 4px; object-fit: cover; background-color: var(--border-list); }
        .content-item div { display: flex; flex-direction: column; }
        
        .content-list button { 
            width: 100%; 
            background-color: var(--btn-copy-bg); 
            color: white; 
            border: none; 
            padding: 12px; 
            border-radius: 5px; 
            cursor: pointer; 
            margin-top: 10px; 
            font-size: 1em; 
            transition: background-color 0.2s;
        }
        .content-list button:hover { 
            background-color: #367fa9; /* Cor um pouco mais escura no hover */
        }

        /* Ajuste de responsividade */
        @media (max-width: 600px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    
    <div class="page-header">
        <h2 class="page-title">Dashboard de Conteúdo</h2>
    </div>
    <div class="container" style="margin-bottom: 20px;">
        <div class="counter-box" style="background: linear-gradient(45deg, #0073b7, #044762);">
            <h1><?php echo $total_canais; ?></h1>
            <p>Total de Canais</p>
        </div>
        <div class="counter-box" style="background: linear-gradient(45deg, #00a65a, #00ca6d);">
            <h1><?php echo $total_filmes; ?></h1>
            <p>Total de Filmes</p>
        </div>
        <div class="counter-box" style="background: linear-gradient(45deg, #607d8b, #78909c);">
            <h1><?php echo $total_series; ?></h1>
            <p>Total de Séries</p>
        </div>
    </div>

    <div class="container">
        <div class="content-list">
            <h3>Novos Canais</h3>
            <div id="lista-canais">
                <?php while($canal = $novos_canais_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <p><?php echo htmlspecialchars($canal['name']); ?> - <small><?php echo date('d/m/Y', $canal['added']); ?></small></p>
                <?php endwhile; ?>
            </div>
            <?php if ($isAdmin): ?>
                <button onclick="copiarTexto('lista-canais')">Copiar Novidades</button>
            <?php endif; ?>
        </div>

        <div class="content-list">
            <h3>Novos Filmes</h3>
            <div id="lista-filmes">
                <?php while($filme = $novos_filmes_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="content-item">
                        <img src="<?php echo htmlspecialchars($filme['stream_icon']); ?>" alt="">
                        <div>
                            <span><?php echo "🎥 " . htmlspecialchars($filme['name']); ?></span>
                            <small><?php echo date('d/m/Y', $filme['added']); ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php if ($isAdmin): ?>
                <button onclick="copiarTexto('lista-filmes')">Copiar Novidades</button>
            <?php endif; ?>
        </div>

        <div class="content-list">
            <h3>Novas Séries</h3>
            <div id="lista-series">
                <?php while($serie = $novas_series_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="content-item">
                        <img src="<?php echo htmlspecialchars($serie['cover']); ?>" alt="">
                        <div>
                            <span><?php echo "🎥 " . htmlspecialchars($serie['name']); ?></span>
                            <small><?php echo ($serie['release_date'] > 0) ? date('d/m/Y', $serie['release_date']) : 'Sem data'; ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php if ($isAdmin): ?>
                <button onclick="copiarTexto('lista-series')">Copiar Novidades</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copiarTexto(elementId) {
            const listElement = document.getElementById(elementId);
            let textToCopy = '';
            
            // 1. Extrai o texto da lista
            listElement.querySelectorAll('.content-item, p').forEach(item => {
                let cleanText = item.innerText.trim().replace(/\s+/g, ' ');
                textToCopy += cleanText + '\n';
            });

            // 2. Tenta copiar o texto para a área de transferência
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    // 3. Usa SweetAlert2 para notificação de Sucesso (AGORA CENTRALIZADO)
                    Swal.fire({
                        title: 'Copiado!',
                        text: 'A lista de novidades foi copiada com sucesso para a área de transferência.',
                        icon: 'success',
                        toast: true,
                        // MUDANÇA AQUI: de 'top-end' para 'center'
                        position: 'center', 
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                }).catch(err => {
                    console.error('Erro ao copiar: ', err);
                    // 4. Usa SweetAlert2 para notificação de Erro (Já é centralizado por padrão)
                    Swal.fire({
                        title: 'Erro de Cópia!',
                        text: 'Não foi possível copiar a lista. Tente selecionar o texto manualmente.',
                        icon: 'error',
                        confirmButtonText: 'Entendi'
                    });
                });
            } else {
                // Notificação de erro para navegadores antigos (Já é centralizado por padrão)
                 Swal.fire({
                    title: 'Navegador Antigo',
                    text: 'A função de cópia automática não é suportada. Por favor, selecione e copie o texto manualmente.',
                    icon: 'warning',
                    confirmButtonText: 'Ok'
                });
            }
        }
    </script>
</body>
</html>