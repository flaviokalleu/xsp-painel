<?php
// ARQUIVO: /gerenciador/filmes.php (NOVO PAINEL - VERSÃO FINAL COMPLETA)
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once('../api/controles/db.php');
$pdo = conectar_bd();
if (!$pdo) { die("Falha fatal ao conectar ao banco de dados."); }

// --- LÓGICA DE PAGINAÇÃO E FILTROS ---
$allowed_limits = [10, 50, 250, 500, 1000, 2000, 3000];
$limit = (isset($_GET['limit']) && in_array($_GET['limit'], $allowed_limits)) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$searchTermo = $_GET['busca'] ?? '';
$searchCategoria = $_GET['categoria_id'] ?? '';
$params = [];

$base_where = " WHERE f.stream_type = 'movie' ";
$whereClauses = [];

if (!empty($searchTermo)) {
    $whereClauses[] = "f.name LIKE :termo";
    $params[':termo'] = '%' . $searchTermo . '%';
}
if (!empty($searchCategoria)) {
    $whereClauses[] = "f.category_id = :categoria_id";
    $params[':categoria_id'] = $searchCategoria;
}

$where_string = empty($whereClauses) ? '' : ' AND ' . implode(' AND ', $whereClauses);

$count_sql = "SELECT COUNT(f.id) FROM streams f" . $base_where . $where_string;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_filmes = $count_stmt->fetchColumn();
$total_pages = ceil($total_filmes / $limit);

$sql = "SELECT f.id, f.name, f.stream_icon, f.link, f.year, c.nome AS category_name 
        FROM streams f 
        LEFT JOIN categoria c ON f.category_id = c.id" . $base_where . $where_string . " ORDER BY f.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if (!empty($params)) { foreach ($params as $key => $val) { $stmt->bindValue($key, $val); } }
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); 
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$filmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Filmes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
        .action-btn { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 3px; border: none; color: white; text-decoration: none; transition: transform 0.2s; cursor: pointer; }
        .btn-edit { background-color: #0d6efd; } .btn-link { background-color: #17a2b8; } .btn-delete { background-color: #dc3545; }
        .badge.bg-primary { background-color: #6f42c1 !important; }
        .poster-img { height: 50px; width: auto; max-width: 40px; object-fit: cover; border-radius: 5px; background-color: #e9ecef; vertical-align: middle; }
        @media (max-width: 768px) { .mobile-hide { display: none; } .action-btn { width: 34px; height: 34px; margin: 0 2px; } .card-body, .card-header { padding: 0.8rem !important; } h4 { font-size: 1.1rem; } }
        body.dark-mode { background-color: #121212; color: #e0e0e0; }
        body.dark-mode .card { background-color: #1e1e1e; border: 1px solid #2c2c2c; }
        body.dark-mode .card-header, body.dark-mode h4 { background-color: #1e1e1e !important; color: #ffffff; }
        body.dark-mode .table { color: #e0e0e0; } body.dark-mode .table-light { --bs-table-bg: #2a2a2a; --bs-table-border-color: #3c3c3c; color: #ffffff; }
        body.dark-mode .table-hover tbody tr:hover { background-color: #2c2c2c; }
        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #2a2a2a; color: #e0e0e0; border-color: #3c3c3c; }
        body.dark-mode .pagination .page-link { background-color: #2a2a2a; border-color: #3c3c3c; }
        body.dark-mode .pagination .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; }
        body.dark-mode .pagination .page-item.disabled .page-link { background-color: #1e1e1e; border-color: #3c3c3c; }
    </style>
</head>
<body>
    <div class="container-fluid p-md-4 p-2">
        <div class="card">
            <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-film me-2"></i>Gerenciar Filmes</h4>
                <a href="filme_novo.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Novo Filme</a>
            </div>
            <div class="card-body">
                <form method="GET" action="filmes.php" class="row g-3 mb-4 align-items-center">
                    <div class="col-lg-3 col-md-6 mb-2"><select name="categoria_id" class="form-select" onchange="this.form.submit()"><option value="">Todas as categorias</option><?php foreach ($categorias as $cat): ?><option value="<?= htmlspecialchars($cat['id']) ?>" <?= ($searchCategoria == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-lg-4 col-md-6 mb-2"><input type="text" name="busca" class="form-control" placeholder="Pesquisar filme..." value="<?= htmlspecialchars($searchTermo) ?>"></div>
                    <div class="col-lg-2 col-md-6 mb-2"><select name="limit" class="form-select" onchange="this.form.submit()"><?php foreach($allowed_limits as $lim): ?><option value="<?= $lim ?>" <?= ($limit == $lim) ? 'selected' : '' ?>><?= $lim ?> por página</option><?php endforeach; ?></select></div>
                    <div class="col-lg-3 col-md-6 mb-2"><button type="submit" class="btn btn-primary w-100">Buscar</button></div>
                </form>
                <div class="mb-3"><button id="bulkDeleteButton" class="btn btn-danger" style="display: none;"><i class="fas fa-trash-alt me-2"></i>Excluir Selecionados</button></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                             <tr>
                                <th><input type="checkbox" class="form-check-input" id="selectAllCheckbox"></th>
                                <th class="mobile-hide">ID</th><th>Título</th><th>Poster</th><th class="mobile-hide">Categoria</th><th class="mobile-hide">Ano</th><th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filmes)): ?><tr><td colspan="7" class="text-center">Nenhum filme encontrado.</td></tr>
                            <?php else: foreach ($filmes as $filme): ?>
                            <tr id="filme-row-<?= htmlspecialchars($filme['id']) ?>">
                                <td><input type="checkbox" class="form-check-input row-checkbox" data-id="<?= htmlspecialchars($filme['id']) ?>"></td>
                                <td class="mobile-hide"><?= htmlspecialchars($filme['id']) ?></td>
                                <td><?= htmlspecialchars($filme['name']) ?></td>
                                <td><img src="<?= htmlspecialchars($filme['stream_icon']) ?>" class="poster-img" alt="Poster"></td>
                                <td class="mobile-hide"><span class="badge bg-primary"><?= htmlspecialchars($filme['category_name'] ?? 'N/A') ?></span></td>
                                <td class="mobile-hide"><?= htmlspecialchars($filme['year'] ?? 'N/A') ?></td>
                                <td class="text-center">
                                    <a href="filme_editar.php?id=<?= htmlspecialchars($filme['id']) ?>" class="action-btn btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                    <a onclick="alert('URL do Stream:\n<?= htmlspecialchars($filme['link']) ?>')" class="action-btn btn-link" title="Ver Link"><i class="fas fa-link"></i></a>
                                    <button onclick="deleteMovie(<?= htmlspecialchars($filme['id']) ?>, '<?= htmlspecialchars(addslashes($filme['name'])) ?>')" class="action-btn btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="mt-3"><ul class="pagination justify-content-center"><?php if($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&busca=<?= $searchTermo ?>&categoria_id=<?= $searchCategoria ?>">Anterior</a></li><?php endif; ?><?php for($i = 1; $i <= $total_pages; $i++): ?><li class="page-item <?= ($i == $page) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>&busca=<?= $searchTermo ?>&categoria_id=<?= $searchCategoria ?>"><?= $i ?></a></li><?php endfor; ?><?php if($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&busca=<?= $searchTermo ?>&categoria_id=<?= $searchCategoria ?>">Próxima</a></li><?php endif; ?></ul></nav>
            </div>
        </div>
    </div>
<script>
(function() {
    const themeKey = 'theme'; 
    function applyTheme() {
        const savedTheme = localStorage.getItem(themeKey);
        document.body.classList.toggle('dark-mode', savedTheme === 'dark');
    }
    applyTheme();
    window.addEventListener('storage', e => { if (e.key === themeKey) applyTheme(); });
    setInterval(applyTheme, 2000);
})();

async function deleteMovie(id, name) {
    if (!confirm(`Você tem certeza que deseja excluir o filme "${name}"?`)) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        const response = await fetch('ajax/filme_excluir.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            document.getElementById(`filme-row-${id}`)?.remove();
        }
    } catch (error) {
        alert('Ocorreu um erro de comunicação.');
    }
}

const selectAllCheckbox = document.getElementById('selectAllCheckbox');
const bulkDeleteButton = document.getElementById('bulkDeleteButton');

function updateBulkActions() {
    const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
    bulkDeleteButton.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    bulkDeleteButton.innerText = `Excluir Selecionados (${checkedCount})`;
}

selectAllCheckbox.addEventListener('click', function() {
    document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = this.checked; });
    updateBulkActions();
});

document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('click', function() {
        if (!this.checked && selectAllCheckbox.checked) {
            selectAllCheckbox.checked = false;
        }
        updateBulkActions();
    });
});

bulkDeleteButton.addEventListener('click', async function() {
    const checkedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
    if (checkedIds.length === 0 || !confirm(`Excluir ${checkedIds.length} filme(s) selecionado(s)?`)) return;
    try {
        const formData = new FormData();
        checkedIds.forEach(id => formData.append('ids[]', id));
        const response = await fetch('ajax/filmes_excluir_massa.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (error) {
        alert('Ocorreu um erro de comunicação em massa.');
    }
});
updateBulkActions();
</script>
</body>
</html>