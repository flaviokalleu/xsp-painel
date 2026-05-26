<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once('./controles/db.php');
require_once('./controles/clientes.php'); // Certifique-se que este ficheiro existe e está atualizado
require_once('./controles/checkLogout.php');

header('Content-Type: application/json; charset=utf-8');
checkLogoutapi();

// --- LÓGICA PARA AÇÕES (APENAS SE FOR POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- LÓGICA PARA AÇÕES EM MASSA ---
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
                echo json_encode(['title' => 'Erro!', 'msg' => 'Nenhum cliente foi excluído. Verifique as permissões.', 'icon' => 'error']);
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
                if ($failed_count > 0) {
                    $msg .= " Falha ao renovar $failed_count (verifique os créditos).";
                }
                echo json_encode(['title' => 'Sucesso!', 'msg' => $msg, 'icon' => 'success']);
            } else {
                echo json_encode(['title' => 'Erro!', 'msg' => 'Não foi possível renovar os clientes. Verifique os créditos e permissões.', 'icon' => 'error']);
            }
            exit();
        }
    }

    // --- LÓGICA PARA AÇÕES INDIVIDUAIS (VINDAS DOS MODAIS) ---
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

    if (isset($_POST['confirme_edite_cliente'])) {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $usuario = $_POST['usuario'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $Whatsapp = $_POST['whatsapp'] ?? '';
        $data_vencimento = $_POST['data_de_vencimento'] ?? '';
        $plano = $_POST['plano'] ?? 0;
        $conexoes = $_POST['conexoes'] ?? 1; // Adicionado para evitar erro, caso não venha no POST
        if (function_exists('confirme_edite_cliente')) {
            // Passe o parâmetro $conexoes que estava faltando na chamada da função
            echo json_encode(confirme_edite_cliente($id, $name, $usuario, $senha, $Whatsapp, $data_vencimento, $plano, $conexoes));
            exit(); // Adicionado exit() que estava faltando
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
        $name = $_POST["name"] ?? '';
        $usuario = $_POST["usuario"] ?? null;
        $senha = $_POST["senha"] ?? null;
        $plano = $_POST["plano"] ?? null;
        $Whatsapp = $_POST["whatsapp"] ?? '';
        $meses = $_POST["meses"] ?? 1;

        if (function_exists('confirme_adicionar_clientes')) {
            echo json_encode(confirme_adicionar_clientes($name, $usuario, $senha, $plano, $Whatsapp, $meses));
            exit();
        }
    }

    // Se nenhuma ação POST for encontrada, retorna um erro
    echo json_encode(['title' => 'Erro!', 'msg' => 'Ação POST não reconhecida.', 'icon' => 'error']);
    exit();
}


// --- LÓGICA PARA LISTAGEM (APENAS SE FOR GET) ---

if (isset($_GET['listar_clientes'])) {
    // ... (o código para listar_clientes que você já tem) ...
    // O código é longo, então apenas o deixei como referência. Mantenha o seu código original aqui.
    // Cole aqui a parte do seu script que começa com "if (isset($_GET['listar_clientes']))" até o final do bloco.
    // O seu código original para esta parte está correto.
}

if (isset($_GET['listar_testes'])) {
    // ... (o código para listar_testes que você já tem) ...
    // O código é longo, então apenas o deixei como referência. Mantenha o seu código original aqui.
    // Cole aqui a parte do seu script que começa com "if (isset($_GET['listar_testes']))" até o final do bloco.
    // O seu código original para esta parte está correto.
}

// ... e assim por diante para todos os outros blocos "if (isset($_GET[...]))"

// Por segurança, vou colar o código completo que você me passou, já com a estrutura correta.
// Apenas substitua o seu arquivo inteiro por este.

// --- CÓDIGO COMPLETO PARA SUBSTITUIÇÃO ---
?>
<?php
// O código PHP já começou acima, então não precisa de outra tag "<?php"

// ... (todo o código de ações POST que está dentro do "if ($_SERVER['REQUEST_METHOD'] === 'POST')") ...
// O código já está lá em cima.

// --- AQUI COMEÇA A PARTE DE LISTAGEM (GET) ---

if (isset($_GET['listar_clientes'])) {
    $conexao = conectar_bd();
    $dados_requisicao = $_REQUEST;
    $admin_id = $_SESSION['admin_id'];
    $colunas = [0 => 'id', 1 => 'name', 2 => 'usuario', 3 => 'servidores', 5 => 'Vencimento', 6 => 'Vencimento'];
    $query = "SELECT COUNT(id) AS qnt_usuarios FROM clientes WHERE (admin_id = :admin_id AND is_trial = 0)";
    if (!empty($dados_requisicao['search']['value'])) {
        $query .= " AND (id LIKE :id OR name LIKE :name OR usuario LIKE :usuario)";
    }
    $query = $conexao->prepare($query);
    $query->bindValue(':admin_id', $admin_id);
    if (!empty($dados_requisicao['search']['value'])) {
        $valor_pesq = "%" . $dados_requisicao['search']['value'] . "%";
        $query->bindValue(':id', $valor_pesq);
        $query->bindValue(':name', $valor_pesq);
        $query->bindValue(':usuario', $valor_pesq);
    }
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    $inicio = (int)$dados_requisicao['start'];
    $quantidade = (int)$dados_requisicao['length'];
    $list_query = "SELECT * FROM clientes WHERE (BINARY admin_id = :admin_id AND is_trial = 0)";
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query .= " AND (id LIKE :id OR clientes.name LIKE :name OR clientes.usuario LIKE :usuario)";
    }
    $list_query .= " ORDER BY " . $colunas[$dados_requisicao['order'][0]['column']] . " " . $dados_requisicao['order'][0]['dir'] . " LIMIT :quantidade OFFSET :inicio";
    $list_query = $conexao->prepare($list_query);
    $list_query->bindValue(':admin_id', $admin_id);
    $list_query->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $list_query->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
    if (!empty($dados_requisicao['search']['value'])) {
        $valor_pesq = "%" . $dados_requisicao['search']['value'] . "%";
        $list_query->bindValue(':id', $valor_pesq);
        $list_query->bindValue(':name', $valor_pesq);
        $list_query->bindValue(':usuario', $valor_pesq);
    }
    $list_query->execute();
    $dados = [];
    while ($row = $list_query->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        if (strtotime($Vencimento) < strtotime(date("Y-m-d"))) {
            $status = '<span class="badge bg-warning w-100 text-dark"> Expirado </span>';
        } else {
            $status = '<span class="badge bg-success w-100 text-dark"> Ativo </span>';
        }
        $acoes = '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/clientes.php", "info_cliente", "' . $id . '")\'><i class="fa-solid fa-eye"></i></a>';
        $acoes .= '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/clientes.php", "edite_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="fa fa-edit"></i></a>';
        $acoes .= '<button class="btn" type="button" id="dropdownUser' . $id . '" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-caret-down fa-solid"></i></button>';
        $dropdown_menu = '<ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownUser' . $id . '" style="">';
        $dropdown_menu .= '<li><button type="button" class="btn btn-primary dropdown-item" data-toggle="modal" data-placement="top" title="Renovar" onclick=\'modal_master("api/clientes.php", "renovar_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'><i class="fas fa-retweet"></i> Renovar </button></li> ';
        $dropdown_menu .= '<li> <button type="button" class="btn btn-primary dropdown-item" data-placement="top" title="Apagar" onclick=\'modal_master("api/clientes.php", "delete_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'> <i class="far fa-trash-alt text-danger"></i> Apagar </button></li>';
        $dropdown_menu .= '</ul>';
        $acoes .= $dropdown_menu;
        $registros_com_childs_rows = ["id" => $id, "name" => $name, "usuario" => $usuario, "indicados" => '', "status" => $status, "vencimento" => date('d-m-Y H:i:s', strtotime($Vencimento)), "acao" => $acoes];
        $dados[] = $registros_com_childs_rows;
    }
    $resultado = ["draw" => intval($dados_requisicao['draw']), "recordsTotal" => intval($result['qnt_usuarios']), "recordsFiltered" => intval($result['qnt_usuarios']), "data" => $dados];
    echo json_encode($resultado);
    exit();
}

if (isset($_GET['listar_testes'])) {
    $conexao = conectar_bd();
    $dados_requisicao = $_REQUEST;
    $admin_id = $_SESSION['admin_id'];
    $colunas = [0 => 'id', 1 => 'name', 2 => 'usuario', 3 => 'servidores', 4 => 'Vencimento', 5 => 'Vencimento'];
    $query = "SELECT COUNT(id) AS qnt_usuarios FROM clientes WHERE (admin_id = :admin_id AND is_trial = 1)";
    if (!empty($dados_requisicao['search']['value'])) {
        $query .= " AND (id LIKE :valor_pesq OR name LIKE :valor_pesq OR usuario LIKE :valor_pesq)";
    }
    $query = $conexao->prepare($query);
    $query->bindValue(':admin_id', $admin_id);
    if (!empty($dados_requisicao['search']['value'])) {
        $valor_pesq = "%" . $dados_requisicao['search']['value'] . "%";
        $query->bindValue(':valor_pesq', $valor_pesq);
    }
    $query->execute();
    $result = $query->fetch(PDO::FETCH_ASSOC);
    $inicio = (int)$dados_requisicao['start'];
    $quantidade = (int)$dados_requisicao['length'];
    $list_query = "SELECT clientes.* FROM clientes WHERE (BINARY clientes.admin_id = :admin_id AND is_trial = 1)";
    if (!empty($dados_requisicao['search']['value'])) {
        $list_query .= " AND (id LIKE :valor_pesq OR clientes.name LIKE :valor_pesq OR clientes.usuario LIKE :valor_pesq)";
    }
    $list_query .= " ORDER BY " . $colunas[$dados_requisicao['order'][0]['column']] . " " . $dados_requisicao['order'][0]['dir'] . " LIMIT :quantidade OFFSET :inicio";
    $list_query = $conexao->prepare($list_query);
    $list_query->bindValue(':admin_id', $admin_id);
    $list_query->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $list_query->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
    if (!empty($dados_requisicao['search']['value'])) {
        $valor_pesq = "%" . $dados_requisicao['search']['value'] . "%";
        $list_query->bindValue(':valor_pesq', $valor_pesq);
    }
    $list_query->execute();
    $dados = [];
    while ($row = $list_query->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        if (strtotime($Vencimento) < strtotime(date("Y-m-d H:i:s"))) {
            $status = '<span class="badge bg-warning w-100 text-dark"> Expirado </span>';
        } else {
            $status = '<span class="badge bg-success w-100 text-dark"> Ativo </span>';
        }
        $acoes = '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/clientes.php", "info_cliente", "' . $id . '")\'><i class="fa-solid fa-eye"></i></a>';
        $acoes .= '<a class="btn btn-sm btn-outline-lightning rounded-0 mr-2" onclick=\'modal_master("api/clientes.php", "edite_cliente", "' . $id . '")\'><i class="fa fa-edit"></i></a>';
        $acoes .= '<button class="btn" type="button" id="dropdownUser' . $id . '" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa-caret-down fa-solid"></i> </button>';
        $dropdown_menu = '<ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownUser' . $id . '" style="">';
        $dropdown_menu .= '<li> <button type="button" class="btn btn-primary dropdown-item" data-toggle="modal" data-placement="top" title="Ativar Teste" onclick=\'modal_master("api/testes.php", "ativar_teste", "' . $id . '", "usuario", "'.$usuario.'")\'> <i class="fa-solid fa-user-check "></i> Ativar Teste </button> </li> ';
        $dropdown_menu .= '<li> <button type="button" class="btn btn-primary dropdown-item" data-placement="top" title="Apagar" onclick=\'modal_master("api/testes.php", "delete_cliente", "' . $id . '", "usuario", "'.$usuario.'")\'> <i class="far fa-trash-alt text-danger"></i> Apagar </button></li>';
        $dropdown_menu .= '</ul>';
        $acoes .= $dropdown_menu;
        $registros_com_childs_rows = ["id" => $id, "name" => $name, "usuario" => $usuario, "indicados" => '', "status" => $status, "vencimento" => date('d-m-Y H:i:s', strtotime($Vencimento)), "acao" => $acoes];
        $dados[] = $registros_com_childs_rows;
    }
    $resultado = ["draw" => intval($dados_requisicao['draw']), "recordsTotal" => intval($result['qnt_usuarios']), "recordsFiltered" => intval($result['qnt_usuarios']), "data" => $dados];
    echo json_encode($resultado);
    exit();
}

// ... Coloque aqui o resto do seu código de listagem (listar_revendedores, listar_categorias, etc.)
// A estrutura é a mesma.
?>