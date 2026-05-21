<?php
/**
 * API COMPLETA E ATUALIZADA PARA GERENCIAR CATEGORIAS
 * Funções: Listar, Salvar Ordem, Buscar para Editar, Salvar Edição e Apagar.
 * Versão: 3.0 - Definitiva. Compatível com o JavaScript moderno (listeners de classe).
 */

// --- 1. CONFIGURAÇÃO INICIAL ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// --- 2. CONFIGURAÇÃO DO BANCO DE DADOS ---
$db_host = 'localhost';
$db_name = 'u535247987_tvbox'; // Suas credenciais
$db_user = 'u535247987_tvbox'; // Suas credenciais
$db_pass = 'Jean#909110';      // Suas credenciais
$charset = 'utf8mb4';

// --- 3. CONEXÃO PDO ---
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]);
    exit();
}

// --- 4. ROTEADOR DE AÇÕES ---
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'listar':
        handle_list_categories($pdo);
        break;
    case 'save_order':
        handle_save_order($pdo);
        break;
    case 'get_category':
        handle_get_category($pdo);
        break;
    case 'save_edit':
        handle_save_edit($pdo);
        break;
    case 'delete_category':
        handle_delete_category($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação desconhecida ou inválida.']);
        break;
}

// --- 5. FUNÇÕES DE LÓGICA ---

function handle_list_categories($pdo) {
    $table_name = 'categoria';
    $category_type = $_POST['type'] ?? 'streams';
    $search_value = $_POST['search']['value'] ?? '';
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    
    $query_base = "FROM `{$table_name}`";
    $query_where = " WHERE `type` = :category_type";
    if (!empty($search_value)) {
        $query_where .= " AND (`nome` LIKE :search_value)";
    }

    $stmt_total = $pdo->prepare("SELECT COUNT(`id`) " . $query_base . " WHERE `type` = :category_type");
    $stmt_total->execute([':category_type' => $category_type]);
    $totalRecords = $stmt_total->fetchColumn();

    $stmt_filtered = $pdo->prepare("SELECT COUNT(`id`) " . $query_base . $query_where);
    $stmt_filtered->bindValue(':category_type', $category_type, PDO::PARAM_STR);
    if (!empty($search_value)) {
        $stmt_filtered->bindValue(':search_value', '%' . $search_value . '%', PDO::PARAM_STR);
    }
    $stmt_filtered->execute();
    $recordsFiltered = $stmt_filtered->fetchColumn();

    $query_data = "SELECT `id` as id_categoria, `nome` as category_name, `type` as tipo, `is_adult`, `position` "
        . $query_base . $query_where
        . " ORDER BY `position` ASC"
        . " LIMIT " . intval($start) . ", " . intval($length);

    $stmt_data = $pdo->prepare($query_data);
    $stmt_data->bindValue(':category_type', $category_type, PDO::PARAM_STR);
    if (!empty($search_value)) {
        $stmt_data->bindValue(':search_value', '%' . $search_value . '%', PDO::PARAM_STR);
    }
    $stmt_data->execute();
    $data = $stmt_data->fetchAll();

    $formatted_data = [];
    foreach($data as $row) {
        $row['is_adult'] = ($row['is_adult'] == 1) ? '<span class="badge bg-danger">Sim</span>' : '<span class="badge bg-success">Não</span>';
        $row['bg_ssiptv'] = '-';
        
        // CORREÇÃO APLICADA AQUI: Botões com 'class' e 'data-id' para o JavaScript moderno.
        $row['acoes'] = '
            <button type="button" class="btn btn-sm btn-warning edit-btn" data-id="' . $row['id_categoria'] . '"><i class="fas fa-edit"></i></button>
            <button type="button" class="btn btn-sm btn-danger remove-btn" data-id="' . $row['id_categoria'] . '"><i class="fas fa-trash-alt"></i></button>
        ';
        $formatted_data[] = $row;
    }

    $response = [
        "draw"            => intval($_POST['draw'] ?? 0),
        "recordsTotal"    => intval($totalRecords),
        "recordsFiltered" => intval($recordsFiltered),
        "data"            => $formatted_data
    ];

    echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

function handle_save_order($pdo) {
    $orderData = json_decode($_POST['order'] ?? '[]', true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($orderData)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Dados de ordenação inválidos.']);
        exit();
    }

    $pdo->beginTransaction();
    try {
        $sql = "UPDATE `categoria` SET `position` = CASE `id` ";
        $ids = [];
        $params = [];
        foreach ($orderData as $item) {
            $id = intval($item['id']);
            $ordem = intval($item['ordem']);
            $sql .= "WHEN ? THEN ? ";
            $params[] = $id;
            $params[] = $ordem;
            $ids[] = $id;
        }
        $id_placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= "END WHERE `id` IN ($id_placeholders)";
        $params = array_merge($params, $ids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Ordem salva! ' . $stmt->rowCount() . ' categorias atualizadas.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar a ordem: ' . $e->getMessage()]);
    }
    exit();
}

function handle_get_category($pdo) {
    $id = intval($_POST['id'] ?? 0);
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID da categoria não fornecido.']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT `id`, `nome`, `type`, `is_adult` FROM `categoria` WHERE `id` = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if ($category) {
        echo json_encode(['status' => 'success', 'data' => $category]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Categoria não encontrada.']);
    }
    exit();
}

function handle_save_edit($pdo) {
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $is_adult = intval($_POST['is_adult'] ?? 0);

    if (empty($id) || empty($nome)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID e Nome são obrigatórios.']);
        exit();
    }

    try {
        $sql = "UPDATE `categoria` SET `nome` = :nome, `is_adult` = :is_adult WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome, ':is_adult' => $is_adult, ':id' => $id]);
        echo json_encode(['status' => 'success', 'message' => 'Categoria atualizada com sucesso!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar a edição: ' . $e->getMessage()]);
    }
    exit();
}

function handle_delete_category($pdo) {
    $id = intval($_POST['id'] ?? 0);
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID da categoria não fornecido.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `categoria` WHERE `id` = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception('Nenhuma categoria encontrada com este ID para apagar.');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao apagar categoria: ' . $e->getMessage()]);
    }
    exit();
}
?>