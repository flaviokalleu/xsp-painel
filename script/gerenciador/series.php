<?php
// ARQUIVO: /gerenciador/series.php (VERSÃO FINAL COMPLETA)
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

// Query baseada na tabela 'series'
$count_sql = "SELECT COUNT(s.id) FROM series s";
$sql = "SELECT s.id, s.name, s.cover, YEAR(s.release_date) as ano, c.nome AS category_name 
        FROM series s 
        LEFT JOIN categoria c ON s.category_id = c.id";

$whereClauses = [];
if (!empty($searchTermo)) { $whereClauses[] = "s.name LIKE :termo"; $params[':termo'] = '%' . $searchTermo . '%'; }
if (!empty($searchCategoria)) { $whereClauses[] = "s.category_id = :categoria_id"; $params[':categoria_id'] = $searchCategoria; }
if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
    $count_sql .= " WHERE " . implode(' AND ', $whereClauses);
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_series = $count_stmt->fetchColumn();
$total_pages = ceil($total_series / $limit);

$sql .= " ORDER BY s.id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
if (!empty($params)) { foreach ($params as $key => $val) { $stmt->bindValue($key, $val); } }
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$series = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Séries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
        .action-btn { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 3px; border: none; color: white; text-decoration: none; transition: transform 0.2s; cursor: pointer; }
        .btn-episodes { background-color: #ffc107; } .btn-edit { background-color: #0d6efd; } .btn-delete { background-color: #dc3545; }
        .badge.bg-primary { background-color: #198754 !important; }
        .poster-img { height: 50px; width: auto; max-width: 40px; object-fit: cover; border-radius: 5px; background-color: #e9ecef; vertical-align: middle; }
        
        @media (max-width: 768px) { .mobile-hide { display: none; } .action-btn { width: 34px; height: 34px; margin: 0 2px; } .card-body, .card-header { padding: 0.8rem !important; } h4 { font-size: 1.1rem; } }
        
        /* --- CSS PARA O TEMA ESCURO --- */
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
                <h4 class="mb-0"><i class="fas fa-tv me-2"></i>Gerenciar Séries</h4>
                <a href="serie_nova.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nova Série</a>
            </div>
            <div class="card-body">
                <form method="GET" action="series.php" class="row g-3 mb-4 align-items-center">
                    <div class="col-lg-3 col-md-6 mb-2"><select name="categoria_id" class="form-select" onchange="this.form.submit()"><option value="">Todas as categorias</option><?php foreach ($categorias as $cat): ?><option value="<?= htmlspecialchars($cat['id']) ?>" <?= ($searchCategoria == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-lg-4 col-md-6 mb-2"><input type="text" name="busca" class="form-control" placeholder="Pesquisar série..." value="<?= htmlspecialchars($searchTermo) ?>"></div>
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
                            <?php if (empty($series)): ?>
                                <tr><td colspan="7" class="text-center">Nenhuma série encontrada.</td></tr>
                            <?php else: foreach ($series as $serie): ?>
                            <tr id="serie-row-<?= htmlspecialchars($serie['id']) ?>">
                                <td><input type="checkbox" class="form-check-input row-checkbox" data-id="<?= htmlspecialchars($serie['id']) ?>"></td>
                                <td class="mobile-hide"><?= htmlspecialchars($serie['id']) ?></td>
                                <td><?= htmlspecialchars($serie['name']) ?></td>
                                <td><img src="<?= htmlspecialchars($serie['cover']) ?>" class="poster-img" alt="Poster"></td>
                                <td class="mobile-hide"><span class="badge bg-primary"><?= htmlspecialchars($serie['category_name'] ?? 'N/A') ?></span></td>
                                <td class="mobile-hide"><?= htmlspecialchars($serie['ano'] ?? 'N/A') ?></td>
                                <td class="text-center">
                                    <a href="serie_episodios.php?id=<?= htmlspecialchars($serie['id']) ?>" class="action-btn btn-episodes" title="Gerenciar Episódios"><i class="fas fa-list-ol"></i></a>
                                    <a href="serie_editar.php?id=<?= htmlspecialchars($serie['id']) ?>" class="action-btn btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                    <button onclick="deleteSerie(<?= htmlspecialchars($serie['id']) ?>, '<?= htmlspecialchars(addslashes($serie['name'])) ?>')" class="action-btn btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
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
// --- JAVASCRIPT DO TEMA ---
(function() { const themeKey = 'theme'; function applyTheme() { const savedTheme = localStorage.getItem(themeKey); document.body.classList.toggle('dark-mode', savedTheme === 'dark'); } applyTheme(); window.addEventListener('storage', e => { if (e.key === themeKey) applyTheme(); }); setInterval(applyTheme, 2000); })();

// --- JAVASCRIPT DAS AÇÕES ---
async function deleteSerie(id, name) {
    if (!confirm(`Excluir a série "${name}" e todos os seus episódios?`)) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        const response = await fetch('ajax/serie_excluir.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) { document.getElementById(`serie-row-${id}`).remove(); }
    } catch (e) { alert('Erro de comunicação.'); }
}

const selectAllCheckbox = document.getElementById('selectAllCheckbox');
const bulkDeleteButton = document.getElementById('bulkDeleteButton');
function updateBulkActions() { const checkedCount = document.querySelectorAll('.row-checkbox:checked').length; bulkDeleteButton.style.display = checkedCount > 0 ? 'inline-block' : 'none'; bulkDeleteButton.innerText = `Excluir Selecionados (${checkedCount})`; }
selectAllCheckbox.addEventListener('click', function() { document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = this.checked; }); updateBulkActions(); });
document.querySelectorAll('.row-checkbox').forEach(cb => { cb.addEventListener('click', updateBulkActions); });
bulkDeleteButton.addEventListener('click', async function() {
    const checkedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
    if (checkedIds.length === 0 || !confirm(`Excluir ${checkedIds.length} série(s) selecionada(s)?`)) return;
    try {
        const formData = new FormData();
        checkedIds.forEach(id => formData.append('ids[]', id));
        const response = await fetch('ajax/series_excluir_massa.php', { method: 'POST', body: formData });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (error) { alert('Erro de comunicação em massa.'); }
});
updateBulkActions();
</script>
</body>
</html>