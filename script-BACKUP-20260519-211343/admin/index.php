<?php
// Usando um caminho absoluto para o arquivo de conexão
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php';

// Ativar a exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = conectar_bd();
if (!$conn) {
    die("Falha fatal: Não foi possível conectar ao banco de dados.");
}

// --- CONFIGURAÇÃO GERAL DA PAGINAÇÃO ---
$itens_por_pagina = 100; // Quantidade de itens a serem exibidos por página

// Função auxiliar para construir os links de paginação corretamente
function build_pagination_query($new_params) {
    return http_build_query(array_merge($_GET, $new_params));
}

// --- LÓGICA DE PAGINAÇÃO PARA FILMES ---
$pagina_atual_filmes = isset($_GET['pagina_filmes']) ? (int)$_GET['pagina_filmes'] : 1;
$offset_filmes = ($pagina_atual_filmes - 1) * $itens_por_pagina;
$total_filmes = $conn->query("SELECT COUNT(id) FROM streams WHERE stream_type = 'movie'")->fetchColumn();
$total_paginas_filmes = ceil($total_filmes / $itens_por_pagina);
$filmes_stmt = $conn->prepare("SELECT id, name, stream_icon FROM streams WHERE stream_type = 'movie' ORDER BY name ASC LIMIT :limit OFFSET :offset");
$filmes_stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$filmes_stmt->bindValue(':offset', $offset_filmes, PDO::PARAM_INT);
$filmes_stmt->execute();
$filmes = $filmes_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LÓGICA DE PAGINAÇÃO PARA SÉRIES ---
$pagina_atual_series = isset($_GET['pagina_series']) ? (int)$_GET['pagina_series'] : 1;
$offset_series = ($pagina_atual_series - 1) * $itens_por_pagina;
$total_series = $conn->query("SELECT COUNT(id) FROM series")->fetchColumn();
$total_paginas_series = ceil($total_series / $itens_por_pagina);
$series_stmt = $conn->prepare("SELECT id, name, cover FROM series ORDER BY name ASC LIMIT :limit OFFSET :offset");
$series_stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$series_stmt->bindValue(':offset', $offset_series, PDO::PARAM_INT);
$series_stmt->execute();
$series = $series_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LÓGICA DE PAGINAÇÃO PARA CANAIS ---
$pagina_atual_canais = isset($_GET['pagina_canais']) ? (int)$_GET['pagina_canais'] : 1;
$offset_canais = ($pagina_atual_canais - 1) * $itens_por_pagina;
$total_canais = $conn->query("SELECT COUNT(id) FROM streams WHERE stream_type = 'live'")->fetchColumn();
$total_paginas_canais = ceil($total_canais / $itens_por_pagina);
$canais_stmt = $conn->prepare("SELECT id, name FROM streams WHERE stream_type = 'live' ORDER BY name ASC LIMIT :limit OFFSET :offset");
$canais_stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
$canais_stmt->bindValue(':offset', $offset_canais, PDO::PARAM_INT);
$canais_stmt->execute();
$canais = $canais_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Conteúdo em Massa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        html, body { height: 100%; background-color: transparent; }
        body { padding: 0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; }
        .content-list { max-height: 60vh; overflow-y: auto; }
        .list-group-item { display: flex; align-items: center; }
        .content-name { flex-grow: 1; margin: 0 1rem; }
        .cover-image { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; background-color: #343a40; }
        .container { padding: 20px !important; }
        .pagination .page-item.active .page-link { z-index: 1; }
    </style>
</head>
<body>

<main class="container">
    <div class="p-3 mb-4 bg-body-tertiary rounded-3 shadow-sm">
      <div class="container-fluid py-3">
        <h1 class="display-5 fw-bold"><i class="bi bi-eraser-fill"></i> Gerenciamento em Massa</h1>
        <p class="col-md-8 fs-4">Use as ferramentas abaixo para selecionar e excluir múltiplos itens ou limpar categorias inteiras de uma só vez. Use com cuidado.</p>
      </div>
    </div>
    
    <?php // Alerta de sucesso após a limpeza
    if (isset($_GET['limpeza']) && $_GET['limpeza'] === 'sucesso'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Sucesso!</strong> A categoria foi limpa completamente.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>


    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-film"></i> Filmes (<?php echo $total_filmes; ?>)</h5>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmacaoModal" data-tipo="filme" data-total="<?php echo $total_filmes; ?>">Limpar Tudo</button>
                </div>
                <div class="card-body d-flex flex-column">
                    <form action="excluir_massa.php" method="POST" onsubmit="return confirm('Excluir TODOS os filmes selecionados?');" class="flex-grow-1">
                        <input type="hidden" name="tipo" value="filme">
                        <ul class="list-group list-group-flush content-list">
                            <?php foreach ($filmes as $filme): ?>
                            <li class="list-group-item">
                                <img src="<?php echo htmlspecialchars($filme['stream_icon']); ?>" class="cover-image" loading="lazy">
                                <input type="checkbox" name="ids[]" value="<?php echo $filme['id']; ?>" class="form-check-input ms-3 chk-filme">
                                <span class="content-name"><?php echo htmlspecialchars($filme['name']); ?></span>
                                <a href="excluir.php?id=<?php echo $filme['id']; ?>&tipo=filme" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tem certeza?');"><i class="bi bi-trash"></i></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3">
                            <input type="checkbox" class="form-check-input" onclick="selecionarTodos(this, 'chk-filme')">
                            <label class="form-check-label ms-2">Selecionar Todos</label>
                            <button type="submit" class="btn btn-danger float-end">Excluir Selecionados</button>
                        </div>
                    </form>
                    <?php if ($total_paginas_filmes > 1): ?>
                    <nav class="mt-4">
                      <ul class="pagination pagination-sm justify-content-center flex-wrap">
                        <?php for ($i = 1; $i <= $total_paginas_filmes; $i++): ?>
                          <li class="page-item <?php if ($i == $pagina_atual_filmes) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo build_pagination_query(['pagina_filmes' => $i]); ?>"><?php echo $i; ?></a>
                          </li>
                        <?php endfor; ?>
                      </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-tv"></i> Séries (<?php echo $total_series; ?>)</h5>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmacaoModal" data-tipo="serie" data-total="<?php echo $total_series; ?>">Limpar Tudo</button>
                </div>
                <div class="card-body d-flex flex-column">
                    <form action="excluir_massa.php" method="POST" onsubmit="return confirm('Excluir TODAS as séries selecionadas?');" class="flex-grow-1">
                        <input type="hidden" name="tipo" value="serie">
                        <ul class="list-group list-group-flush content-list">
                            <?php foreach ($series as $serie): ?>
                            <li class="list-group-item">
                                <img src="<?php echo htmlspecialchars($serie['cover']); ?>" class="cover-image" loading="lazy">
                                <input type="checkbox" name="ids[]" value="<?php echo $serie['id']; ?>" class="form-check-input ms-3 chk-serie">
                                <span class="content-name"><?php echo htmlspecialchars($serie['name']); ?></span>
                                <a href="excluir.php?id=<?php echo $serie['id']; ?>&tipo=serie" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tem certeza?');"><i class="bi bi-trash"></i></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3">
                            <input type="checkbox" class="form-check-input" onclick="selecionarTodos(this, 'chk-serie')">
                            <label class="form-check-label ms-2">Selecionar Todos</label>
                            <button type="submit" class="btn btn-danger float-end">Excluir Selecionados</button>
                        </div>
                    </form>
                    <?php if ($total_paginas_series > 1): ?>
                    <nav class="mt-4">
                      <ul class="pagination pagination-sm justify-content-center flex-wrap">
                        <?php for ($i = 1; $i <= $total_paginas_series; $i++): ?>
                          <li class="page-item <?php if ($i == $pagina_atual_series) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo build_pagination_query(['pagina_series' => $i]); ?>"><?php echo $i; ?></a>
                          </li>
                        <?php endfor; ?>
                      </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-broadcast"></i> Canais (<?php echo $total_canais; ?>)</h5>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmacaoModal" data-tipo="canal" data-total="<?php echo $total_canais; ?>">Limpar Tudo</button>
                </div>
                <div class="card-body d-flex flex-column">
                    <form action="excluir_massa.php" method="POST" onsubmit="return confirm('Excluir TODOS os canais selecionados?');" class="flex-grow-1">
                        <input type="hidden" name="tipo" value="canal">
                        <ul class="list-group list-group-flush content-list">
                            <?php foreach ($canais as $canal): ?>
                            <li class="list-group-item">
                                <input type="checkbox" name="ids[]" value="<?php echo $canal['id']; ?>" class="form-check-input chk-canal">
                                <span class="content-name ms-2"><?php echo htmlspecialchars($canal['name']); ?></span>
                                <a href="excluir.php?id=<?php echo $canal['id']; ?>&tipo=canal" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tem certeza?');"><i class="bi bi-trash"></i></a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-3">
                            <input type="checkbox" class="form-check-input" onclick="selecionarTodos(this, 'chk-canal')">
                            <label class="form-check-label ms-2">Selecionar Todos</label>
                            <button type="submit" class="btn btn-danger float-end">Excluir Selecionados</button>
                        </div>
                    </form>
                    <?php if ($total_paginas_canais > 1): ?>
                    <nav class="mt-4">
                      <ul class="pagination pagination-sm justify-content-center flex-wrap">
                        <?php for ($i = 1; $i <= $total_paginas_canais; $i++): ?>
                          <li class="page-item <?php if ($i == $pagina_atual_canais) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo build_pagination_query(['pagina_canais' => $i]); ?>"><?php echo $i; ?></a>
                          </li>
                        <?php endfor; ?>
                      </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="confirmacaoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Ação Irreversível</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <form id="formLimparTudo" action="limpar_categoria.php" method="POST">
            <p>Você está prestes a apagar <strong id="totalItens"></strong> itens. O conteúdo será perdido para sempre.</p>
            <p>Para confirmar, digite a frase <strong class="text-danger">EXCLUIR TUDO</strong> no campo abaixo.</p>
            <input type="text" id="campoConfirmacao" name="confirmacao" class="form-control text-center mb-3" onkeyup="validarConfirmacao()">
            <input type="hidden" id="tipoCategoria" name="tipo">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="btnConfirmarExclusao" class="btn btn-danger" disabled>Confirmar Exclusão Total</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function selecionarTodos(source, checkboxClass) {
        const checkboxes = document.getElementsByClassName(checkboxClass);
        for (let i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    const confirmacaoModal = document.getElementById('confirmacaoModal');
    if (confirmacaoModal) {
        const campoConfirmacao = document.getElementById('campoConfirmacao');
        const FRASE_CONFIRMACAO = "EXCLUIR TUDO";

        function validarConfirmacao() {
            const input = campoConfirmacao.value;
            const btn = document.getElementById('btnConfirmarExclusao');
            btn.disabled = input !== FRASE_CONFIRMACAO;
        }

        campoConfirmacao.addEventListener('keyup', validarConfirmacao);

        confirmacaoModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const tipo = button.getAttribute('data-tipo');
            const total = button.getAttribute('data-total');
            const modalTotalItens = confirmacaoModal.querySelector('#totalItens');
            const modalTipoCategoria = confirmacaoModal.querySelector('#tipoCategoria');
            let nomeCategoria = tipo;
            if (tipo === 'canal') nomeCategoria = 'canais';
            if (tipo === 'filme') nomeCategoria = 'filmes';
            if (tipo === 'serie') nomeCategoria = 'séries';
            modalTotalItens.textContent = `${total} ${nomeCategoria}`;
            modalTipoCategoria.value = tipo;
            campoConfirmacao.value = '';
            validarConfirmacao();
        });
    }

    (function(){try{const theme=window.parent.localStorage.getItem('theme');if(theme){document.documentElement.setAttribute('data-bs-theme',theme)}}catch(e){const theme=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-bs-theme',theme);console.warn("Não foi possível acessar o tema do painel principal. Usando tema local.")}})();
</script>

</body>
</html>