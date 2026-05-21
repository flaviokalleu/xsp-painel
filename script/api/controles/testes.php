<?php
function info_cliente($id)
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;

    $sql = "SELECT c.* FROM clientes c WHERE c.id = :id AND c.admin_id = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $host = $_SERVER['HTTP_HOST'];
        $modal_body = "<pre class='pre-unique text-wrap' id='pre-{$id}' style='max-height: 380px;'>";
        $modal_body .= "*Usuário:* {$usuario}<br>";
        $modal_body .= "*Senha:* {$senha}<br>";
        $modal_body .= "*URL/Port:* http://{$host}<br>";
        $modal_body .= "*Data de validade:* " . date('d-m-Y H:i:s', strtotime($Vencimento)) . "<br><br>";
        $modal_body .= "*Link (M3U Encurtado):*<br>http://{$host}/m3u-ts/{$usuario}/{$senha}<br>";
        $modal_body .= "*Link (HLS Encurtado):*<br>http://{$host}/m3u-m3u8/{$usuario}/{$senha}<br>";
        $modal_body .= "</pre>";
        $modal_footer = "<button type='button' class='btn btn-info' onclick='copyText(\"pre-{$id}\")'>Copiar</button>";
        return ['modal_header_class' => "d-block modal-header bg-info text-white", 'modal_titulo' => "Lista do usuário ({$usuario})", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer];
    }
    return 0;
}

function add_tempo($id, $usuario)
{
    $modal_body = "<input type='hidden' name='confirme_add_tempo_testes' value='{$id}'>";
    $modal_body .= "<label>Adicionar Horas:</label>";
    $modal_body .= "<input type='number' name='tempo' class='form-control' value='1' min='-72' max='72'>";
    $modal_body .= "<small class='form-text text-muted'>Use valores negativos para remover horas.</small>";
    $modal_footer = "<button type='button' class='btn btn-success' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>Confirmar</button>";
    return ['modal_header_class' => "d-block modal-header bg-success text-white", 'modal_titulo' => "Adicionar Tempo para ({$usuario})", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer];
}

function confirme_add_tempo_testes($id, $tempo)
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    $tempo = min(max(intval($tempo), -72), 72);

    $stmt = $conexao->prepare("SELECT Vencimento FROM clientes WHERE id = :id AND admin_id = :admin_id");
    $stmt->execute([':id' => $id, ':admin_id' => $admin_id]);
    
    if ($cliente = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vencimento_atual = $cliente['Vencimento'];
        $nova_data = date("Y-m-d H:i:s", strtotime("{$vencimento_atual} {$tempo} hours"));
        
        $stmt_update = $conexao->prepare("UPDATE clientes SET Vencimento = :nova_data WHERE id = :id");
        if ($stmt_update->execute([':nova_data' => $nova_data, ':id' => $id])) {
            return ['title' => 'Concluído!', 'msg' => 'Tempo alterado com sucesso.', 'icon' => 'success'];
        }
    }
    return ['title' => 'Erro!', 'msg' => 'Não foi possível alterar o tempo. Cliente não encontrado ou sem permissão.', 'icon' => 'error'];
}

function ativar_teste($id, $usuario)
{
    $modal_body = "<input type='hidden' name='confirme_ativar_teste' value='{$id}'>";
    $modal_body .= "<label>Ativar por (meses):</label>";
    $modal_body .= "<input type='number' name='ativar_meses' class='form-control' value='1' min='1' max='12'>";
    $modal_footer = "<button type='button' class='btn btn-success' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>Ativar Cliente</button>";
    return ['modal_header_class' => "d-block modal-header bg-success text-white", 'modal_titulo' => "Ativar Cliente ({$usuario})", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer];
}

function confirme_ativar_teste($id, $meses)
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    $meses = intval($meses);

    $stmt_admin = $conexao->prepare("SELECT admin, creditos FROM admin WHERE id = :admin_id");
    $stmt_admin->execute([':admin_id' => $admin_id]);
    $admin_info = $stmt_admin->fetch(PDO::FETCH_ASSOC);

    if (!$admin_info) return ['title' => 'Erro!', 'msg' => 'Sessão inválida.', 'icon' => 'error'];

    $stmt = $conexao->prepare("SELECT c.*, p.valor FROM clientes c LEFT JOIN planos p ON c.plano = p.id WHERE c.id = :id AND c.admin_id = :admin_id");
    $stmt->execute([':id' => $id, ':admin_id' => $admin_id]);

    if ($cliente = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $custo = $meses * ($cliente['valor'] ?? 1);
        if ($admin_info['admin'] != 1 && $admin_info['creditos'] < $custo) {
            return ['title' => 'Erro!', 'msg' => 'Você não tem créditos suficientes.', 'icon' => 'error'];
        }

        $vencimento_atual = $cliente['Vencimento'];
        $base_data = (strtotime($vencimento_atual) > time()) ? $vencimento_atual : date("Y-m-d H:i:s");
        $nova_data = date("Y-m-d H:i:s", strtotime("{$base_data} +{$meses} month"));

        $stmt_update = $conexao->prepare("UPDATE clientes SET Vencimento = :nova_data, is_trial = 0, Ultimo_pagamento = NOW() WHERE id = :id");
        if ($stmt_update->execute([':nova_data' => $nova_data, ':id' => $id])) {
            if ($admin_info['admin'] != 1) {
                $stmt_creditos = $conexao->prepare("UPDATE admin SET creditos = creditos - :custo WHERE id = :admin_id");
                $stmt_creditos->execute([':custo' => $custo, ':admin_id' => $admin_id]);
            }
            return ['title' => 'Concluído!', 'msg' => "Cliente ativado por {$meses} mes(es). Custo: {$custo} crédito(s).", 'icon' => 'success'];
        }
    }
    return ['title' => 'Erro!', 'msg' => 'Não foi possível ativar o cliente.', 'icon' => 'error'];
}


function delete_cliente($id, $usuario)
{
    $modal_body = "<input type='hidden' name='confirme_delete_cliente' value='{$id}'>";
    $modal_body .= "Tem certeza que deseja excluir o cliente ({$usuario})?";
    $modal_footer = "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button><button type='button' class='btn btn-danger' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>EXCLUIR</button>";
    return ['modal_header_class' => "d-block modal-header bg-danger text-white", 'modal_titulo' => "EXCLUIR CLIENTE", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer];
}

function confirme_delete_cliente($id, $usuario)
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    
    $stmt = $conexao->prepare("DELETE FROM clientes WHERE id = :id AND admin_id = :admin_id");
    if ($stmt->execute([':id' => $id, ':admin_id' => $admin_id])) {
        if ($stmt->rowCount() > 0) {
            return ['title' => 'Sucesso!', 'msg' => 'Cliente deletado com sucesso!', 'icon' => 'success'];
        }
    }
    return ['title' => 'Erro!', 'msg' => 'Não foi possível deletar o cliente ou você não tem permissão.', 'icon' => 'error'];
}

function adicionar_testes()
{
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    
    $planoOptions = "";
    $stmt_planos = $conexao->prepare("SELECT * FROM planos WHERE admin_id = ?");
    $stmt_planos->execute([$admin_id]);
    while ($plano = $stmt_planos->fetch()) {
        $planoOptions .= "<option value='{$plano['id']}'>{$plano['nome']}</option>";
    }
    
    $modal_body = "<input type='hidden' name='confirme_adicionar_testes' value='1'>";
    $modal_body .= "<div class='form-group'><label>Nome:</label><input type='text' class='form-control' name='name'></div>";
    $modal_body .= "<div class='row'><div class='form-group col-6'><label>Usuário:</label><input type='text' class='form-control' name='usuario' value='" . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8) . "'></div>";
    $modal_body .= "<div class='form-group col-6'><label>Senha:</label><input type='text' class='form-control' name='senha' value='" . substr(str_shuffle('0123456789'), 0, 6) . "'></div></div>";
    $modal_body .= "<div class='form-group'><label>Duração (Horas):</label><input type='number' class='form-control' name='tempo' value='3'></div>";
    $modal_body .= "<div class='form-group'><label>Plano:</label><select class='form-select' name='plano'>{$planoOptions}</select></div>";
    
    // ======================================================
    // INÍCIO DA ADIÇÃO DO CAMPO WHATSAPP
    // ======================================================
    $modal_body .= "<div class='form-group'><label>Whatsapp:</label><input type='text' class='form-control' name='whatsapp' placeholder='Ex: 5511999998888'></div>";
    // ======================================================
    // FIM DA ADIÇÃO
    // ======================================================

    $modal_footer = "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button><button type='button' class='btn btn-primary' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>Adicionar</button>";

    return ['modal_header_class'=> "d-block modal-header bg-primary text-white", 'modal_titulo'=> "Adicionar Novo Teste", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
}

function confirme_adicionar_testes($name, $usuario, $senha, $adulto, $plano, $Dispositivo, $App, $Forma_de_pagamento, $nome_do_pagador, $Whatsapp, $indicacao, $mac, $key, $tempo)
{  
    $conexao = conectar_bd();
    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0;
    
    if (!$admin_id) return ['title' => 'Erro!', 'msg' => 'Sessão inválida.', 'icon' => 'error'];

    $stmt_plano = $conexao->prepare("SELECT valor FROM planos WHERE id = :plano AND admin_id = :admin_id");
    $stmt_plano->execute([':plano' => $plano, ':admin_id' => $admin_id]);
    $valor_plano = $stmt_plano->fetchColumn();
    $valor = $valor_plano ?? 0;

    $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = :usuario");
    if ($stmt_check->execute([':usuario' => $usuario]) && $stmt_check->fetch()) {
        return ['title' => 'Erro!', 'msg' => 'Usuário já cadastrado.', 'icon' => 'error'];
    }
    
    $vencimento = date("Y-m-d H:i:s", strtotime("+" . abs(intval($tempo)) . " hour"));
    
    // CORREÇÃO: Adicionada a coluna `Whatsapp` no INSERT
    $sql_insert = "INSERT INTO clientes (admin_id, name, usuario, senha, Criado_em, Vencimento, is_trial, V_total, plano, Whatsapp) 
                   VALUES (:admin_id, :name, :usuario, :senha, NOW(), :vencimento, 1, :V_total, :plano, :whatsapp)";
    $stmt_insert = $conexao->prepare($sql_insert);
    
    if ($stmt_insert->execute([
        ':admin_id' => $admin_id, 
        ':name' => $name, 
        ':usuario' => $usuario, 
        ':senha' => $senha,
        ':vencimento' => $vencimento, 
        ':V_total' => $valor, 
        ':plano' => $plano,
        ':whatsapp' => $Whatsapp // Adicionado o parâmetro
    ])) {
        return ['title' => 'Concluído!', 'msg' => 'Teste inserido com sucesso', 'icon' => 'success'];
    } else {
        return ['title' => 'Erro!', 'msg' => 'Erro ao inserir cliente: ' . implode(" - ", $stmt_insert->errorInfo()), 'icon' => 'error'];
    }
}
?>
