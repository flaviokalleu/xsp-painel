<?php
session_start();
require_once('./controles/db.php');

if (isset($_GET['listar_clientes'])) {
    header('Content-Type: application/json; charset=utf-8');
    $conexao = conectar_bd();
    $dados_requisicao = $_REQUEST;
    $admin_id = $_SESSION['admin_id'];
    $colunas = [ 0 => 'id', 1 => 'name', 2 => 'usuario', 3 => 'name', 4 => 'Vencimento', 5 => 'Vencimento' ];
    $query_total = $conexao->prepare("SELECT COUNT(id) AS qnt_usuarios FROM clientes WHERE (admin_id = :admin_id AND is_trial = 0)");
    $query_total->execute([':admin_id' => $admin_id]);
    $result_total = $query_total->fetch(PDO::FETCH_ASSOC);
    $total_records = $result_total['qnt_usuarios'];
    $list_query_str = "SELECT * FROM clientes WHERE (admin_id = :admin_id AND is_trial = 0)";
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query_str .= " AND (name LIKE :search OR usuario LIKE :search)";
    }
    $list_query_str .= " ORDER BY " . $colunas[$dados_requisicao['order'][0]['column']] . " " . $dados_requisicao['order'][0]['dir'] . " LIMIT " . (int)$dados_requisicao['length'] . " OFFSET " . (int)$dados_requisicao['start'];
    $list_query = $conexao->prepare($list_query_str);
    $list_query->bindValue(':admin_id', $admin_id);
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query->bindValue(':search', "%" . $dados_requisicao['search']['value'] . "%");
    }
    $list_query->execute();
    $total_filtered = $list_query->rowCount();
    $dados = [];
    while ($row = $list_query->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $status = (strtotime($Vencimento) < time()) ? '<span class="badge bg-warning w-100 text-dark"> Expirado </span>' : '<span class="badge bg-success w-100 text-dark"> Ativo </span>';
        $acoes = '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/testes.php", "info_cliente", "' . $id . '")\'><i class="fa-solid fa-eye"></i></a>';
        $acoes .= '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/testes.php", "edite_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="fa fa-edit"></i></a>';
        $acoes .= '<button class="btn" type="button" data-bs-toggle="dropdown"><i class="fa-caret-down fa-solid"></i></button>';
        $dropdown_menu = '<ul class="dropdown-menu dropdown-menu-left">';
        $dropdown_menu .= '<li><button type="button" class="btn btn-primary dropdown-item" onclick=\'modal_master("api/testes.php", "renovar_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="fas fa-retweet"></i> Renovar </button></li> ';
        $dropdown_menu .= '<li><button type="button" class="btn btn-primary dropdown-item" onclick=\'modal_master("api/testes.php", "delete_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="far fa-trash-alt text-danger"></i> Apagar </button></li>';
        $dropdown_menu .= '</ul>';
        $acoes .= $dropdown_menu;
        $dados[] = [ "id" => $id, "name" => $name, "usuario" => $usuario, "indicados" => '', "status" => $status, "vencimento" => date('d-m-Y H:i:s', strtotime($Vencimento)), "acao" => $acoes ];
    }
    echo json_encode(["draw" => intval($dados_requisicao['draw']), "recordsTotal" => intval($total_records), "recordsFiltered" => intval($total_records), "data" => $dados ]);
    exit();
}

if (isset($_GET['listar_testes'])) {
    header('Content-Type: application/json; charset=utf-8');
    $conexao = conectar_bd();
    $dados_requisicao = $_REQUEST;
    $admin_id = $_SESSION['admin_id'];
    $colunas = [ 0 => 'id', 1 => 'name', 2 => 'usuario', 3 => 'Vencimento', 4 => 'Vencimento' ];
    $query_total = $conexao->prepare("SELECT COUNT(id) AS qnt_usuarios FROM clientes WHERE (admin_id = :admin_id AND is_trial = 1)");
    $query_total->execute([':admin_id' => $admin_id]);
    $result_total = $query_total->fetch(PDO::FETCH_ASSOC);
    $total_records = $result_total['qnt_usuarios'];
    $list_query_str = "SELECT * FROM clientes WHERE (admin_id = :admin_id AND is_trial = 1)";
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query_str .= " AND (name LIKE :search OR usuario LIKE :search)";
    }
    $list_query_str .= " ORDER BY " . $colunas[$dados_requisicao['order'][0]['column']] . " " . $dados_requisicao['order'][0]['dir'] . " LIMIT " . (int)$dados_requisicao['length'] . " OFFSET " . (int)$dados_requisicao['start'];
    $list_query = $conexao->prepare($list_query_str);
    $list_query->bindValue(':admin_id', $admin_id);
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query->bindValue(':search', "%" . $dados_requisicao['search']['value'] . "%");
    }
    $list_query->execute();
    $total_filtered = $list_query->rowCount();
    $dados = [];
    while ($row = $list_query->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $status = (strtotime($Vencimento) < time()) ? '<span class="badge bg-warning w-100 text-dark"> Expirado </span>' : '<span class="badge bg-success w-100 text-dark"> Ativo </span>';
        $acoes = '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/testes.php", "info_cliente", "' . $id . '")\'><i class="fa-solid fa-eye"></i></a>';
        $acoes .= '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/testes.php", "edite_cliente", "' . $id . '")\'><i class="fa fa-edit"></i></a>';
        $acoes .= '<button class="btn" type="button" data-bs-toggle="dropdown"><i class="fa-caret-down fa-solid"></i> </button>';
        $dropdown_menu = '<ul class="dropdown-menu dropdown-menu-left">';
        $dropdown_menu .= '<li> <button type="button" class="btn btn-primary dropdown-item" onclick=\'modal_master("api/testes.php", "converter_teste", "' . $id . '", "usuario", "'.$usuario.'")\'> <i class="fa-solid fa-user-check text-success"></i> Converter em Cliente </button> </li> ';
        $dropdown_menu .= '<li> <button type="button" class="btn btn-primary dropdown-item" onclick=\'modal_master("api/testes.php", "delete_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'> <i class="far fa-trash-alt text-danger"></i> Apagar </button></li>';
        $dropdown_menu .= '</ul>';
        $acoes .= $dropdown_menu;
        $dados[] = [ "id" => $id, "name" => $name, "usuario" => $usuario, "status" => $status, "vencimento" => date('d-m-y H:i:s', strtotime($Vencimento)), "acao" => $acoes ];
    }
    echo json_encode([ "draw" => intval($dados_requisicao['draw']), "recordsTotal" => intval($total_records), "recordsFiltered" => intval($total_records), "data" => $dados ]);
    exit();
}

if (isset($_GET['listar_revendedores'])) {
    header('Content-Type: application/json; charset=utf-8');
    $conexao = conectar_bd();
    $dados_requisicao = $_REQUEST;
    $admin_id = $_SESSION['admin_id'] ?? null;
    $colunas = [ 0 => 'id', 1 => 'user', 2 => 'creditos' ];
    $query_total = $conexao->prepare("SELECT COUNT(id) AS qnt_servidores FROM admin WHERE (criado_por = :admin_id)");
    $query_total->execute([':admin_id' => $admin_id]);
    $result_total = $query_total->fetch(PDO::FETCH_ASSOC);
    $total_records = $result_total['qnt_servidores'];
    $list_query_str = "SELECT a.*, p.nome as tipo_admin FROM admin a LEFT JOIN planos_admin p ON p.id = a.plano WHERE (BINARY criado_por = :admin_id)";
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query_str .= " AND (a.id LIKE :search OR user LIKE :search)";
    }
    $list_query_str .= " ORDER BY " . $colunas[$dados_requisicao['order'][0]['column']] . " " . $dados_requisicao['order'][0]['dir'] . " LIMIT " . (int)$dados_requisicao['length'] . " OFFSET " . (int)$dados_requisicao['start'];
    $list_query = $conexao->prepare($list_query_str);
    $list_query->bindValue(':admin_id', $admin_id);
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query->bindValue(':search', "%" . $dados_requisicao['search']['value'] . "%");
    }
    $list_query->execute();
    $total_filtered = $list_query->rowCount();
    $dados = [];
    while ($row = $list_query->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $qnt_revendedores_stmt = $conexao->prepare("SELECT COUNT(id) AS qnt FROM admin WHERE (criado_por = :id)");
        $qnt_revendedores_stmt->execute([':id' => $id]);
        $qnt_revendedores = $qnt_revendedores_stmt->fetch(PDO::FETCH_ASSOC);
        $qnt_clientes_stmt = $conexao->prepare("SELECT COUNT(id) AS qnt FROM clientes WHERE (admin_id = :id)");
        $qnt_clientes_stmt->execute([':id' => $id]);
        $qnt_clientes = $qnt_clientes_stmt->fetch(PDO::FETCH_ASSOC);
        $acoes = '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/revendedores.php", "adicionar_creditos", "' . $id . '", "usuario", "'.$user.'")\'><i class="fas fa-plus-circle"></i></a>';
        $acoes .= '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/revendedores.php", "edite_revendedor", "' . $id . '")\'><i class="fa fa-edit"></i></a>';
        $acoes .= '<button type="button" class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/revendedores.php", "delete_revendedor", "' . $id . '", "usuario", "'.$user.'")\'> <i class="fa fa-trash"></i></button>';
        $tipo_admin_final = trim(strstr($tipo_admin, ':'), ': ');
        $dados[] = [ "id" => $id, "usuario" => $user, "creditos" => $creditos, "tipo" => $tipo_admin_final, "qnt_revendedores" => $qnt_revendedores['qnt'], "qnt_clientes" => $qnt_clientes['qnt'], "acao" => $acoes ];
    }
    echo json_encode([ "draw" => intval($dados_requisicao['draw']), "recordsTotal" => intval($total_records), "recordsFiltered" => intval($total_records), "data" => $dados ]);
    exit();
}

if (isset($_GET['info_admin'])) {
    header('Content-Type: application/json; charset=utf-8');
    $admin_id = $_SESSION['admin_id'] ?? null;
    // Valida sessão pela presença do admin_id (autenticação já garantida pelo checkLogout).
    // A verificação por token foi removida: o token da sessão era invalidado por race condition
    // quando session_regenerate_id rodava em paralelo com requests AJAX do dashboard.
    if (empty($admin_id) || empty($_SESSION['logged_in_fxtream'])) {
        echo json_encode(['icon' => 'error', 'title' => 'Erro', 'msg' => 'Sessão inválida']);
        exit();
    }
    $conexao = conectar_bd();
    $sql = "SELECT *, p.nome as tipo_admin FROM admin a LEFT JOIN planos_admin p ON p.id = a.plano WHERE a.id = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([':admin_id' => $admin_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $creditos_finais = ($admin == 1) ? '<i class="fa-solid fa-infinity"></i>' : $creditos;
        $tipo_admin_final = ($admin == 1) ? "Administrador" : "Nivel: " . trim(strstr($tipo_admin, ':'), ': ');
        echo json_encode([ 'tipo_admin' => $tipo_admin_final, 'creditos' => $creditos_finais, 'icon' => 'success' ]);
    } else {
        echo json_encode(['icon' => 'error', 'title' => 'Erro', 'msg' => 'Admin não encontrado']);
    }
    exit();
}
?>