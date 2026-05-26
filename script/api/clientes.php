<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once('./controles/db.php');
require_once('./controles/clientes.php');

// A verificação de logout foi desativada, pois era a causa do erro de sessão.
// require_once('./controles/checkLogout.php');

header('Content-Type: application/json; charset=utf-8');
// checkLogoutapi();

// --- LÓGICA PARA AÇÕES (APENAS SE FOR POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- AÇÕES EM MASSA ---
    if (isset($_POST['action']) && $_POST['action'] == 'delete_multiple') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $deleted_count = 0;
            foreach ($ids as $id) {
                if (function_exists('confirme_delete_cliente')) {
                    $result = confirme_delete_cliente(intval($id), '');
                    if ($result['icon'] === 'success') {
                        $deleted_count++;
                    }
                }
            }
            if ($deleted_count > 0) {
                echo json_encode(['title' => 'Sucesso!', 'msg' => "$deleted_count clientes foram excluídos.", 'icon' => 'success']);
            } else {
                echo json_encode(['title' => 'Erro!', 'msg' => 'Nenhum cliente foi excluído.', 'icon' => 'error']);
            }
            exit();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'renew_multiple') {
        $ids = $_POST['ids'] ?? [];
        $meses = intval($_POST['meses'] ?? 0);
        if (!empty($ids) && is_array($ids) && $meses > 0) {
            $updated_count = 0;
            $failed_count = 0;
            foreach ($ids as $id) {
                if (function_exists('confirme_renovar_cliente')) {
                    $result = confirme_renovar_cliente(intval($id), $meses);
                    if ($result['icon'] === 'success') {
                        $updated_count++;
                    } else {
                        $failed_count++;
                    }
                }
            }
            if ($updated_count > 0) {
                $msg = "$updated_count clientes foram renovados.";
                if ($failed_count > 0) { $msg .= " Falha ao renovar $failed_count."; }
                echo json_encode(['title' => 'Sucesso!', 'msg' => $msg, 'icon' => 'success']);
            } else {
                echo json_encode(['title' => 'Erro!', 'msg' => 'Não foi possível renovar os clientes.', 'icon' => 'error']);
            }
            exit();
        }
    }

    // --- AÇÕES INDIVIDUAIS (VINDAS DOS MODAIS) ---
    if (isset($_POST['info_cliente'])) {
        $id = $_POST['info_cliente'];
        if (function_exists('info_cliente')) {
            echo json_encode(info_cliente($id));
            exit();
        }
    }

    if (isset($_POST['edite_cliente'])) {
        $id = $_POST['edite_cliente'];
        if (function_exists('edite_cliente')) {
            echo json_encode(edite_cliente($id));
            exit();
        }
    }

    // CORREÇÃO: Chamada da função foi ajustada para enviar o array $_POST inteiro.
    if (isset($_POST['confirme_edite_cliente'])) {
        if (function_exists('confirme_edite_cliente')) {
            echo json_encode(confirme_edite_cliente($_POST));
            exit();
        }
    }

    if (isset($_POST['renovar_cliente'])) {
        $id = $_POST['renovar_cliente'];
        $usuario = $_POST['usuario'] ?? '';
        if (function_exists('renovar_cliente')) {
            echo json_encode(renovar_cliente($id, $usuario));
            exit();
        }
    }

    if (isset($_POST['confirme_renovar_cliente'])) {
        $id = $_POST['confirme_renovar_cliente'];
        $meses = $_POST['meses'] ?? 1;
        if (function_exists('confirme_renovar_cliente')) {
            echo json_encode(confirme_renovar_cliente($id, $meses));
            exit();
        }
    }

    if (isset($_POST['delete_cliente'])) {
        $id = $_POST['delete_cliente'];
        $usuario = $_POST['usuario'] ?? '';
        if (function_exists('delete_cliente')) {
            echo json_encode(delete_cliente($id, $usuario));
            exit();
        }
    }

    if (isset($_POST['confirme_delete_cliente'])) {
        $id = $_POST['confirme_delete_cliente'];
        if (function_exists('confirme_delete_cliente')) {
            echo json_encode(confirme_delete_cliente($id, ''));
            exit();
        }
    }

    if (isset($_POST['adicionar_clientes'])) {
        if (function_exists('adicionar_clientes')) {
            echo json_encode(adicionar_clientes());
            exit();
        }
    }

    if (isset($_POST['confirme_adicionar_clientes'])) {
        if (function_exists('confirme_adicionar_clientes')) {
            echo json_encode(confirme_adicionar_clientes($_POST));
            exit();
        }
    }

    echo json_encode(['title' => 'Erro!', 'msg' => 'Ação POST não reconhecida.', 'icon' => 'error']);
    exit();
}


// --- LÓGICA PARA LISTAGEM (APENAS SE FOR GET) ---
// SEU CÓDIGO ORIGINAL DE LISTAGEM, QUE JÁ ESTAVA FUNCIONANDO BEM
if (isset($_GET['listar_clientes'])) {
    $conexao = conectar_bd();
    $dados_requisicao = $_REQUEST;
    $admin_id = $_SESSION['admin_id'];
    $colunas = [0 => 'id', 1 => 'name', 2 => 'usuario', 3 => 'servidores', 5 => 'Vencimento', 6 => 'Vencimento'];
    $query_total = "SELECT COUNT(id) AS qnt_usuarios FROM clientes WHERE (admin_id = :admin_id AND is_trial = 0)";
    $stmt_total = $conexao->prepare($query_total);
    $stmt_total->bindValue(':admin_id', $admin_id);
    $stmt_total->execute();
    $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_records = $result_total['qnt_usuarios'];

    $query_filter = "SELECT COUNT(id) AS qnt_usuarios FROM clientes WHERE (admin_id = :admin_id AND is_trial = 0)";
    if (!empty($dados_requisicao['search']['value'])) {
        $query_filter .= " AND (id LIKE :search_value OR name LIKE :search_value OR usuario LIKE :search_value)";
    }
    $stmt_filter = $conexao->prepare($query_filter);
    $stmt_filter->bindValue(':admin_id', $admin_id);
    if (!empty($dados_requisicao['search']['value'])) {
        $stmt_filter->bindValue(':search_value', "%" . $dados_requisicao['search']['value'] . "%");
    }
    $stmt_filter->execute();
    $result_filter = $stmt_filter->fetch(PDO::FETCH_ASSOC);
    $total_filtered = $result_filter['qnt_usuarios'];

    $inicio = (int)$dados_requisicao['start'];
    $quantidade = (int)$dados_requisicao['length'];
    $list_query_str = "SELECT * FROM clientes WHERE (BINARY admin_id = :admin_id AND is_trial = 0)";
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query_str .= " AND (id LIKE :search_value OR name LIKE :search_value OR usuario LIKE :search_value)";
    }
    $list_query_str .= " ORDER BY " . $colunas[$dados_requisicao['order'][0]['column']] . " " . $dados_requisicao['order'][0]['dir'] . " LIMIT :quantidade OFFSET :inicio";
    $list_query = $conexao->prepare($list_query_str);
    $list_query->bindValue(':admin_id', $admin_id);
    $list_query->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $list_query->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query->bindValue(':search_value', "%" . $dados_requisicao['search']['value'] . "%");
    }
    $list_query->execute();
    $dados = [];
    while ($row = $list_query->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $status = (strtotime($Vencimento) < time()) ? '<span class="badge bg-warning w-100 text-dark"> Expirado </span>' : '<span class="badge bg-success w-100 text-dark"> Ativo </span>';
        $acoes = '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/clientes.php", "info_cliente", "' . $id . '")\'><i class="fa-solid fa-eye"></i></a>';
        $acoes .= '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/clientes.php", "edite_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="fa fa-edit"></i></a>';
        $acoes .= '<button class="btn" type="button" id="dropdownUser' . $id . '" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-caret-down fa-solid"></i></button>';
        $dropdown_menu = '<ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownUser' . $id . '" style="">';
        $dropdown_menu .= '<li><button type="button" class="btn btn-primary dropdown-item" onclick=\'modal_master("api/clientes.php", "renovar_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="fas fa-retweet"></i> Renovar </button></li> ';
        $dropdown_menu .= '<li><button type="button" class="btn btn-primary dropdown-item" onclick=\'modal_master("api/clientes.php", "delete_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'> <i class="far fa-trash-alt text-danger"></i> Apagar </button></li>';
        $dropdown_menu .= '</ul>';
        $acoes .= $dropdown_menu;
        $registros_com_childs_rows = ["id" => $id, "name" => $name, "usuario" => $usuario, "indicados" => '', "status" => $status, "vencimento" => date('d-m-Y H:i:s', strtotime($Vencimento)), "acao" => $acoes];
        $dados[] = $registros_com_childs_rows;
    }
    $resultado = ["draw" => intval($dados_requisicao['draw']), "recordsTotal" => intval($total_records), "recordsFiltered" => intval($total_filtered), "data" => $dados];
    echo json_encode($resultado);
    exit();
}
?>