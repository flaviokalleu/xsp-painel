<?php
// Arquivo: api/controles/revendedores.php - VERSÃO FINAL E COMPLETA

// ======================================================================
// ### FUNÇÕES PARA EDITAR ADMIN E SENHA DE REVENDA (CORRIGIDAS) ###
// ======================================================================

function edite_admin() {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;

    $stmt = $conexao->prepare("SELECT user, pass FROM admin WHERE id = :id");
    $stmt->execute([':id' => $admin_id]);

    if ($admin_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modal_body = '
            <input type="hidden" name="confirme_edite_admin" value="1">
            <div class="form-group mb-3">
                <label for="usuario">Usuário Admin:</label>
                <input type="text" class="form-control" name="usuario" value="' . htmlspecialchars($admin_data['user']) . '" required>
            </div>
            <div class="form-group mb-3">
                <label for="senha">Nova Senha (deixe em branco para não alterar):</label>
                <input type="password" class="form-control" name="senha" placeholder="••••••••">
            </div>';
        
        $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"revendedores.php\")' class='btn btn-info'>Salvar Alterações</button>";
        
        return [
            'modal_header_class' => "modal-header bg-info text-white",
            'modal_titulo' => "Editar Credenciais de Admin",
            'modal_body' => $modal_body,
            'modal_footer' => $modal_footer
        ];
    }
    return false;
}

function confirme_edite_admin($usuario, $senha) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;

    if (empty($usuario)) {
        return ['title' => 'Erro!', 'msg' => 'O nome de usuário não pode estar vazio.', 'icon' => 'error'];
    }

    if (!empty($senha)) {
        $sql = "UPDATE admin SET user = :user, pass = :pass WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':pass', $senha);
    } else {
        $sql = "UPDATE admin SET user = :user WHERE id = :id";
        $stmt = $conexao->prepare($sql);
    }
    
    $stmt->bindParam(':user', $usuario);
    $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $_SESSION['username'] = $usuario;
        return ['title' => 'Sucesso!', 'msg' => 'Dados de admin atualizados com sucesso.', 'icon' => 'success'];
    }
    return ['title' => 'Erro!', 'msg' => 'Não foi possível atualizar os dados.', 'icon' => 'error'];
}

function edite_admin_revenda() {
    $modal_body = '
        <input type="hidden" name="confirme_edite_admin_revenda" value="1">
        <div class="form-group mb-3">
            <label for="senha_atual">Senha Atual:</label>
            <input type="password" class="form-control" name="senha_atual" required>
        </div>
        <div class="form-group mb-3">
            <label for="nova_senha">Nova Senha:</label>
            <input type="password" class="form-control" name="nova_senha" required>
        </div>
        <div class="form-group mb-3">
            <label for="confirme_senha">Confirmar Nova Senha:</label>
            <input type="password" class="form-control" name="confirme_senha" required>
        </div>';

    $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"revendedores.php\")' class='btn btn-info'>Alterar Senha</button>";
    
    return [
        'modal_header_class' => "modal-header bg-info text-white",
        'modal_titulo' => "Alterar Minha Senha",
        'modal_body' => $modal_body,
        'modal_footer' => $modal_footer
    ];
}

function confirme_edite_admin_revenda($senha_atual, $nova_senha) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;

    $stmt = $conexao->prepare("SELECT pass FROM admin WHERE id = :id");
    $stmt->execute([':id' => $admin_id]);
    $senha_db = $stmt->fetchColumn();

    if ($senha_db !== $senha_atual) {
        return ['title' => 'Erro!', 'msg' => 'A senha atual está incorreta.', 'icon' => 'error'];
    }

    $sql_update = "UPDATE admin SET pass = :nova_senha WHERE id = :id";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bindParam(':nova_senha', $nova_senha);
    $stmt_update->bindParam(':id', $admin_id, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        return ['title' => 'Sucesso!', 'msg' => 'Sua senha foi alterada com sucesso.', 'icon' => 'success'];
    }
    return ['title' => 'Erro!', 'msg' => 'Não foi possível alterar a senha.', 'icon' => 'error'];
}


// ======================================================================
// ### SUAS FUNÇÕES ORIGINAIS DE REVENDEDOR (RESTAURADAS) ###
// ======================================================================

function deletaradmins() {
    try {
        $conexao = conectar_bd();
        $sql = "SELECT id, criado_por FROM admin WHERE admin = 0";
        $stmt = $conexao->query($sql);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $id = $row['id'];
                $criado_por = $row['criado_por'];
                $count_sql = "SELECT COUNT(*) FROM admin WHERE id = :criado_por";
                $count_stmt = $conexao->prepare($count_sql);
                $count_stmt->bindParam(':criado_por', $criado_por, PDO::PARAM_INT);
                $count_stmt->execute();
                $total = $count_stmt->fetchColumn();
                if ($total == 0) {
                    $conexao->prepare("DELETE FROM admin WHERE id = :id")->execute([':id' => $id]);
                    $conexao->prepare("DELETE FROM clientes WHERE admin_id = :id")->execute([':id' => $id]);
                    $conexao->prepare("DELETE FROM planos WHERE admin_id = :id")->execute([':id' => $id]);
                }
            }
        }
    } catch (PDOException $e) { /* Tratar erro */ }
}

function edite_revendedor($id) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $sql = "SELECT user, pass, plano FROM admin WHERE id = :id AND criado_por = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($revendedor = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt_admin = $conexao->prepare("SELECT plano FROM admin WHERE id = :admin_id");
        $stmt_admin->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $stmt_admin->execute();
        $admin_plano = $stmt_admin->fetchColumn();
        $option = "";
        $planos_disponiveis = $conexao->prepare("SELECT id, nome FROM planos_admin WHERE id < :admin_plano");
        $planos_disponiveis->bindParam(':admin_plano', $admin_plano, PDO::PARAM_INT);
        $planos_disponiveis->execute();
        while ($p_admin = $planos_disponiveis->fetch(PDO::FETCH_ASSOC)) {
            $selected = ($p_admin['id'] == $revendedor['plano']) ? 'selected' : '';
            $option .= '<option value="' . htmlspecialchars($p_admin['id']) . '" ' . $selected . '>' . htmlspecialchars($p_admin['nome']) . '</option>';
        }
        $modal_body = '<input type="hidden" name="confirme_edite_revendedor" value="' . htmlspecialchars($id) . '"><div class="row"><div class="form-group col-md-6 mb-3"><label for="usuario">Usuário:</label><input type="text" class="form-control" name="usuario" value="' . htmlspecialchars($revendedor['user']) . '"></div><div class="form-group col-md-6 mb-3"><label for="senha">Senha:</label><input type="text" class="form-control" name="senha" value="' . htmlspecialchars($revendedor['pass']) . '"></div></div><div class="form-group"><label for="plano">Selecione um plano</label><select class="form-select" name="plano">' . $option . '</select></div>';
        $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"revendedores.php\")' class='btn btn-info'>Salvar</button><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancelar</button>";
        return [ 'modal_header_class' => "modal-header bg-info text-white", 'modal_titulo' => "Editar Revendedor", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer ];
    }
    return 0;
}

function confirme_editar_revendedor($id, $usuario, $senha, $plano) {
    $usuario = preg_replace('/[^a-zA-Z0-9#@!%&*]/', '', $usuario);
    $senha = preg_replace('/[^a-zA-Z0-9#@!%&*]/', '', $senha);
    if (empty($usuario) || empty($senha)) {
        return ['title' => 'Erro!', 'msg' => 'Usuário ou senha inválidos.', 'icon' => 'error'];
    }
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $sql = "UPDATE admin SET user = :user, pass = :pass, plano = :plano WHERE id = :id AND criado_por = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':user', $usuario); $stmt->bindParam(':pass', $senha);
    $stmt->bindParam(':plano', $plano, PDO::PARAM_INT); $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        return ['title' => 'Concluído!', 'msg' => 'Revendedor editado com sucesso.', 'icon' => 'success'];
    } else {
        return ['title' => 'Erro!', 'msg' => 'Não foi possível editar o revendedor ou nenhuma alteração foi feita.', 'icon' => 'error'];
    }
}

function add_creditos($id, $usuario) {
    $modal_body = '<input type="hidden" name="confirme_add_creditos" value="' . htmlspecialchars($id) . '"><label for="creditos" class="form-label">Adicionar Créditos:</label><input type="number" name="creditos" class="form-control" placeholder="Créditos" value="10"><small class="form-text text-muted">Use um valor negativo para remover créditos.</small>';
    $modal_footer = "<button type='button' class='btn btn-success' onclick='enviardados(\"modal_master_form\", \"revendedores.php\")'>Confirmar</button>";
    return [ 'modal_header_class' => "modal-header bg-success text-white", 'modal_titulo' => "Adicionar Créditos para (" . htmlspecialchars($usuario ?? '') . ")", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer ];
}

function confirme_add_creditos($id, $addcreditos) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $stmt_user = $conexao->prepare("SELECT user FROM admin WHERE id = :id");
    $stmt_user->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt_user->execute();
    $revendedor_user = $stmt_user->fetchColumn();
    if (!$revendedor_user) {
        return ['title' => 'Erro!', 'msg' => 'Revendedor não encontrado.', 'icon' => 'error'];
    }
    $dados_operacao = [ 'usuario' => $revendedor_user, 'creditos_adicionados' => $addcreditos ];
    $stmt_admin_check = $conexao->prepare("SELECT admin FROM admin WHERE id = :admin_id");
    $stmt_admin_check->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt_admin_check->execute();
    $is_main_admin = ($stmt_admin_check->fetchColumn() == 1);

    if ($is_main_admin) {
        $sql = "UPDATE admin SET creditos = creditos + :creditos WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':creditos', $addcreditos, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['title' => 'Concluído!', 'msg' => 'Operação de crédito realizada com sucesso.', 'icon' => 'success', 'data' => $dados_operacao];
        } else {
            return ['title' => 'Erro!', 'msg' => 'Falha ao executar a operação de crédito.', 'icon' => 'error'];
        }
    }

    $stmt_check = $conexao->prepare("SELECT creditos FROM admin WHERE id = :admin_id");
    $stmt_check->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $creditos_admin = $stmt_check->fetchColumn();
    if ($creditos_admin >= $addcreditos) {
        $conexao->beginTransaction();
        try {
            $sql1 = "UPDATE admin SET creditos = creditos + :creditos WHERE id = :id AND criado_por = :admin_id";
            $stmt1 = $conexao->prepare($sql1);
            $stmt1->bindParam(':creditos', $addcreditos, PDO::PARAM_INT); $stmt1->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt1->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt1->execute();
            $sql2 = "UPDATE admin SET creditos = creditos - :creditos WHERE id = :admin_id";
            $stmt2 = $conexao->prepare($sql2);
            $stmt2->bindParam(':creditos', $addcreditos, PDO::PARAM_INT);
            $stmt2->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
            $stmt2->execute();
            $conexao->commit();
            return ['title' => 'Concluído!', 'msg' => 'Créditos transferidos com sucesso.', 'icon' => 'success', 'data' => $dados_operacao];
        } catch (Exception $e) {
            $conexao->rollBack();
            return ['title' => 'Erro!', 'msg' => 'Falha na transação de créditos.', 'icon' => 'error'];
        }
    } else {
        return ['title' => 'Erro!', 'msg' => 'Você não tem créditos suficientes para transferir.', 'icon' => 'error'];
    }
}

function add_revendedor() {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $stmt_admin = $conexao->prepare("SELECT plano FROM admin WHERE id = :admin_id");
    $stmt_admin->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    $stmt_admin->execute();
    $admin_plano = $stmt_admin->fetchColumn();
    $option = "";
    $planos_disponiveis = $conexao->prepare("SELECT id, nome FROM planos_admin WHERE id < :admin_plano");
    $planos_disponiveis->bindParam(':admin_plano', $admin_plano, PDO::PARAM_INT);
    $planos_disponiveis->execute();
    while ($p_admin = $planos_disponiveis->fetch(PDO::FETCH_ASSOC)) {
        $option .= '<option value="' . htmlspecialchars($p_admin['id']) . '">' . htmlspecialchars($p_admin['nome']) . '</option>';
    }
    $modal_body = '<input type="hidden" name="confirme_add_revendedor" value="1"><div class="row"><div class="form-group col-md-6 mb-3"><label for="usuario">Usuário:</label><input type="text" class="form-control" name="usuario"></div><div class="form-group col-md-6 mb-3"><label for="senha">Senha:</label><input type="text" class="form-control" name="senha"></div></div><div class="form-group"><label for="plano">Selecione um plano</label><select class="form-select" name="plano">' . $option . '</select></div>';
    $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"revendedores.php\")' class='btn btn-success'>Adicionar</button><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancelar</button>";
    return [ 'modal_header_class' => "modal-header bg-success text-white", 'modal_titulo' => "Adicionar Revendedor", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer ];
}

function confirme_add_revendedor($usuario, $senha, $plano) {
    $usuario = preg_replace('/[^a-zA-Z0-9#@!%&*]/', '', $usuario);
    $senha = preg_replace('/[^a-zA-Z0-9#@!%&*]/', '', $senha);
    if (empty($usuario) || empty($senha)) {
        return ['title' => 'Erro!', 'msg' => 'Usuário ou senha inválidos.', 'icon' => 'error'];
    }
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $sql_insert = "INSERT INTO admin (user, pass, admin, creditos, criado_por, plano, data_criado) VALUES (:user, :pass, 0, 0, :criado_por, :plano, NOW())";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bindParam(':user', $usuario); $stmt_insert->bindParam(':pass', $senha);
    $stmt_insert->bindParam(':criado_por', $admin_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':plano', $plano, PDO::PARAM_INT);
    if ($stmt_insert->execute()) {
        $lastInsertedId = $conexao->lastInsertId();
        $sql_insert_planos = "INSERT INTO planos (nome, admin_id, valor, custo_por_credito) VALUES ('Completo', :admin_id, 30, 5)";
        $stmt_insert_planos = $conexao->prepare($sql_insert_planos);
        $stmt_insert_planos->bindParam(':admin_id', $lastInsertedId, PDO::PARAM_INT);
        $stmt_insert_planos->execute();
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $link_painel = $protocolo . $_SERVER['HTTP_HOST'];
        $dados_novo_revendedor = [ 'usuario' => $usuario, 'senha' => $senha, 'creditos_iniciais' => 0, 'link_painel' => $link_painel ];
        return [ 'title' => 'Concluído!', 'msg' => 'Revendedor criado com sucesso', 'icon' => 'success', 'data' => $dados_novo_revendedor ];
    } else {
        return [ 'title' => 'Erro!', 'msg' => 'Erro ao criar revendedor. Verifique se o usuário já existe.', 'icon' => 'error' ];
    }
}

function delete_revendedor($id, $usuario) {
    $modal_body = '<input type="hidden" name="confirme_delete_revendedor" value="' . htmlspecialchars($id) . '"><p>Tem certeza que deseja excluir o revendedor <strong>' . htmlspecialchars($usuario) . '</strong>?</p><p class="text-danger">Esta ação é irreversível e apagará todos os clientes e sub-revendedores associados a ele.</p>';
    $modal_footer = "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button><button type='button' class='btn btn-danger' onclick='enviardados(\"modal_master_form\", \"revendedores.php\")'>EXCLUIR</button>";
    return [ 'modal_header_class' => "modal-header bg-danger text-white", 'modal_titulo' => "EXCLUIR REVENDEDOR", 'modal_body' => $modal_body, 'modal_footer' => $modal_footer ];
}

function confirme_delete_revendedor($id) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $sql_delete = "DELETE FROM admin WHERE id = :id AND criado_por = :admin_id";
    $stmt = $conexao->prepare($sql_delete);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
    if ($stmt->execute() && $stmt->rowCount() > 0) {
        $conexao->prepare("DELETE FROM planos WHERE admin_id = :id")->execute([':id' => $id]);
        $conexao->prepare("DELETE FROM clientes WHERE admin_id = :id")->execute([':id' => $id]);
        deletaradmins();
        return ['title' => 'Sucesso!', 'msg' => 'Revendedor deletado com sucesso!', 'icon' => 'success'];
    } else {
        return ['title' => 'Erro!', 'msg' => 'Erro ao deletar Revendedor ou você não tem permissão.', 'icon' => 'error'];
    }
}
?>