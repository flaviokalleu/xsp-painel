<?php
session_start();
require_once('../api/controles/db.php');
require_once('../api/controles/checkLogout.php');

checkLogoutapi();

$admin_id = $_SESSION['admin_id'] ?? 0;

// Parâmetros do DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$searchValue = $_POST['search']['value'] ?? '';
$orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// Mapeamento seguro das colunas para ordenação
$columns = [
    1 => 'id',
    2 => 'name',
    3 => 'usuario',
    6 => 'Vencimento'
];
$orderColumn = $columns[$orderColumnIndex] ?? 'id';

$conexao = conectar_bd();

// --- Contagem Total de Clientes ---
$totalRecordsStmt = $conexao->prepare("SELECT COUNT(id) FROM clientes WHERE is_trial = 0 AND admin_id = ?");
$totalRecordsStmt->execute([$admin_id]);
$totalRecords = $totalRecordsStmt->fetchColumn();

// --- Construção da Query Principal ---
$sql = "SELECT id, name, usuario, Vencimento FROM clientes WHERE is_trial = 0 AND admin_id = :admin_id";
$params = [':admin_id' => $admin_id];

// --- Filtro de Busca ---
if (!empty($searchValue)) {
    $sql .= " AND (name LIKE :search OR usuario LIKE :search)";
    $params[':search'] = "%{$searchValue}%";
}

// --- Contagem de Registos Filtrados ---
$countSql = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(id) FROM', $sql);
$filteredRecordsStmt = $conexao->prepare($countSql);
$filteredRecordsStmt->execute($params);
$filteredRecords = $filteredRecordsStmt->fetchColumn();

// --- Ordenação e Paginação ---
$sql .= " ORDER BY {$orderColumn} {$orderDir} LIMIT :start, :length";

$stmt = $conexao->prepare($sql);
$stmt->bindValue(':admin_id', $admin_id, PDO::PARAM_INT);
if (!empty($searchValue)) {
    $searchParam = "%{$searchValue}%";
    $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $vencimento_obj = new DateTime($row['Vencimento']);
    $hoje_obj = new DateTime();
    $status = ($vencimento_obj > $hoje_obj) ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Vencido</span>';

    $stmt_indicados = $conexao->prepare("SELECT COUNT(id) FROM clientes WHERE indicado_por = ?");
    $stmt_indicados->execute([$row['id']]);
    $indicados = $stmt_indicados->fetchColumn();

    // ======================================================
    // BOTÃO DE EDITAR ADICIONADO AQUI
    // ======================================================
    $acoes = '
        <button class="btn btn-sm btn-info" onclick=\'modal_master("api/clientes.php", "info_cliente", ' . $row['id'] . ')\' title="Info"><i class="fas fa-info-circle"></i></button>
        <button class="btn btn-sm btn-warning" onclick=\'modal_master("api/clientes.php", "edite_cliente", ' . $row['id'] . ')\' title="Editar"><i class="fas fa-edit"></i></button>
        <button class="btn btn-sm btn-primary" onclick=\'modal_master("api/clientes.php", "renovar_cliente", ' . $row['id'] . ', "' . htmlspecialchars($row['usuario']) . '")\' title="Renovar"><i class="fas fa-calendar-check"></i></button>
        <button class="btn btn-sm btn-danger" onclick=\'modal_master("api/clientes.php", "delete_cliente", ' . $row['id'] . ', "' . htmlspecialchars($row['usuario']) . '")\' title="Excluir"><i class="fas fa-trash"></i></button>
    ';
    // ======================================================

    $data[] = [
        "id" => $row['id'],
        "name" => htmlspecialchars($row['name']),
        "usuario" => htmlspecialchars($row['usuario']),
        "indicados" => $indicados,
        "status" => $status,
        "Vencimento" => $vencimento_obj->format('d/m/Y H:i'),
        "acoes" => $acoes
    ];
}

$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
?>
