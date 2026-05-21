<?php
// ARQUIVO: /gerenciador/filmes.php (VERSÃO FINAL CORRIGIDA DO ERRO DE TELA BRANCA)
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$whereClauses = [];

if (!empty($searchTermo)) {
    $whereClauses[] = "f.titulo LIKE :termo";
    $params[':termo'] = '%' . $searchTermo . '%';
}
if (!empty($searchCategoria)) {
    $whereClauses[] = "f.categoria_id = :categoria_id";
    $params[':categoria_id'] = $searchCategoria;
}

$where_string = empty($whereClauses) ? '' : ' WHERE ' . implode(' AND ', $whereClauses);

$count_sql = "SELECT COUNT(f.id) FROM filmes f" . $where_string;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_filmes = $count_stmt->fetchColumn();
$total_pages = ceil($total_filmes / $limit);

$sql = "SELECT f.id, f.titulo, f.url_capa, f.url_stream, YEAR(f.data_lancamento) as ano, c.nome AS category_name 
        FROM filmes f 
        LEFT JOIN categoria c ON f.categoria_id = c.id" . $where_string . " ORDER BY f.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Associa os parâmetros dos filtros (se existirem)
if (!empty($params)) {
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
}
// Associa os parâmetros de paginação como NÚMEROS
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); 
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// *** AQUI ESTAVA O ERRO FATAL ***
// A execução agora é feita SEM PASSAR PARÂMETROS, pois eles já foram associados acima com bindValue.
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
    </body>
</html>