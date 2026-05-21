<?php
// Arquivo: api/controles/clientes.php - VERSÃO FINAL E COMPLETA E CORRIGIDA

function info_cliente($id)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $sql = "SELECT * FROM clientes WHERE id = :id AND admin_id = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([':id' => $id, ':admin_id' => $admin_id]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $template_file = $_SERVER['DOCUMENT_ROOT'] . '/template_mensagem.txt';
        $template = file_exists($template_file) ? file_get_contents($template_file) : "ERRO: template_mensagem.txt não encontrado!\n\nUsuário: #username#\nSenha: #password#";
        $portal_url = 'http://'.$_SERVER['HTTP_HOST'];
        $exp_date_formatted = date('d/m/Y H:i:s', strtotime($Vencimento));
        $replacements = [
            '#username#' => $usuario, '#password#' => $senha, '#url#' => $portal_url,
            '#exp_date#' => $exp_date_formatted, '#m3u_link#' => $portal_url.'/get.php?username='.$usuario.'&password='.$senha.'&type=m3u_plus&output=ts',
            '#m3u_link_hls#' => $portal_url.'/get.php?username='.$usuario.'&password='.$senha.'&type=m3u_plus&output=m3u8',
            '#m3u_encurtado#' => $portal_url.'/m3u-ts/'.$usuario.'/'.$senha, '#m3u_hls_encurtado#' => $portal_url.'/m3u-m3u8/'.$usuario.'/'.$senha,
            '#ssiptv_encurtado#' => $portal_url.'/ss-ts/'.$usuario.'/'.$senha,
        ];
        $mensagem_final = str_replace(array_keys($replacements), array_values($replacements), $template);
        $modal_body = '<div class="text-wrap" id="pre-'.$id.'" style="white-space: pre-wrap; font-family: monospace;">' . nl2br(htmlspecialchars($mensagem_final)) . '</div>';
        $modal_footer = "<button type='button' class='btn btn-info' onclick='copyText(\"pre-".$id."\")'>Copiar</button>";
        return ['modal_header_class'=> "d-block modal-header bg-info text-white", 'modal_titulo'=> "Informações do usuário (".$usuario.")", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
    }
    return ['title' => 'Erro!', 'msg' => 'Cliente não encontrado.', 'icon' => 'error'];
}

function edite_cliente($id)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $sql = "SELECT c.* FROM clientes c WHERE c.id = :id AND c.admin_id = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([':id' => $id, ':admin_id' => $admin_id]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        // --- CÓDIGO DE VERIFICAÇÃO DE ADMIN ---
        $admin_profile_stmt = $conexao->prepare("SELECT admin FROM admin WHERE id = :admin_id");
        $admin_profile_stmt->execute([':admin_id' => $admin_id]);
        $admin_profile = $admin_profile_stmt->fetch(PDO::FETCH_ASSOC);
        $logged_in_admin_level = $admin_profile['admin'] ?? 0; // 1 é Full Admin, outros são Revendedores
        $date_value = (isset($Vencimento) ? date("Y-m-d", strtotime($Vencimento)) : '');
        // --------------------------------------------

        $stmt_bouquets = $conexao->prepare("SELECT id, bouquet_name FROM bouquets ORDER BY bouquet_name ASC");
        $stmt_bouquets->execute();
        $lista_de_bouquets = $stmt_bouquets->fetchAll(PDO::FETCH_ASSOC);
        $plano1 = $conexao->prepare("SELECT * FROM planos WHERE admin_id = ?");
        $plano1->execute([$admin_id]);
        $planoOptions = "";
        while ($lista_plano = $plano1->fetch()) {
            $plano_selecionado = isset($plano) && $lista_plano['id'] == $plano;
            $planoOptions .= '<option value="'.$lista_plano['id'].'" '.($plano_selecionado ? "selected" : "").'>'.($plano_selecionado ? "Plano Atual => " : "").htmlspecialchars($lista_plano['nome']).' [R$: '.$lista_plano['valor'].']</option>';
        }
        $modal_body = '<input type="hidden" name="confirme_edite_cliente" value="1"><input type="hidden" name="id" value="'.$id.'">';
        $modal_body .= '<div class="form-group mb-2"><label>Nome do cliente:</label><input type="text" class="form-control" name="name" value="'.htmlspecialchars($name).'"></div>';
        $modal_body .= '<div class="row mb-2"><div class="form-group col-md-6"><label>Usuario</label><input type="text" class="form-control" name="usuario" value="'.htmlspecialchars($usuario).'"></div><div class="form-group col-md-6"><label>Senha</label><input type="text" class="form-control" name="senha" value="'.htmlspecialchars($senha).'"></div></div>';
        
        // --- RESTRIÇÃO DE DATA DE VENCIMENTO (EXISTENTE) ---
        if ($logged_in_admin_level == 1) { 
            // Full Admin vê e pode editar a data
            $modal_body .= '<div class="form-group mb-2"><label>Data de vencimento:</label><input type="date" class="form-control" name="data_de_vencimento" value="'.$date_value.'"></div>';
        } else {
            // Revendedor NÃO vê o campo, mas o valor atual é mantido oculto para o backend
            $modal_body .= '<input type="hidden" name="data_de_vencimento" value="'.$date_value.'">';
        }
        // ---------------------------------------------------

        // --- RESTRIÇÃO DE CONEXÕES MÁXIMAS (EXISTENTE NO CÓDIGO ANTERIOR) ---
        $modal_body .= '<div class="form-group mb-2"><label>Conexões máximas:</label>';
        if ($logged_in_admin_level == 1) {
            // Full Admin vê e pode editar o campo
            $modal_body .= '<input type="number" class="form-control" name="conexoes" value="'.$conexoes.'" min="1"></div>';
        } else {
            // Revendedor vê o campo, mas como somente leitura (readonly), e o 'name' garante que o valor seja submetido para preservação.
            $modal_body .= '<input type="number" class="form-control" name="conexoes" value="'.$conexoes.'" min="1" readonly></div>';
        }
        // --------------------------------------------------------------------

        $modal_body .= '<div class="form-group mb-2"><label>Conteudo adulto?</label><select class="form-select" name="adulto"><option value="0" '.($adulto == 0 ? "selected":"").'>NÃO</option><option value="1" '.($adulto == 1 ? "selected":"").'>SIM</option></select></div>';
        $modal_body .= '<div class="form-group mb-2"><label>Plano:</label><select class="form-select" name="plano">'.$planoOptions.'</select></div>';
        $modal_body .= '<div class="form-group"><label for="bouquet_id">Pacote (Bouquet):</label><select name="bouquet_id" class="form-select"><option value="">-- Acesso Total --</option>';
        foreach ($lista_de_bouquets as $bouquet) {
            $selecionado = (isset($bouquet_id) && $bouquet_id == $bouquet['id']) ? 'selected' : '';
            $modal_body .= '<option value="' . $bouquet['id'] . '" ' . $selecionado . '>' . htmlspecialchars($bouquet['bouquet_name']) . '</option>';
        }
        $modal_body .= '</select></div>';
        $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"testes.php\")' class='btn btn-info'>Salvar</button><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancelar</button>";
        return ['modal_header_class'=> "d-block modal-header bg-info text-white", 'modal_titulo'=> "Editar usuário ($usuario)", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
    }
    return ['title' => 'Erro!', 'msg' => 'Cliente não encontrado.', 'icon' => 'error'];
}

function confirme_edite_cliente($post_data)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $id = $post_data['id'] ?? 0;
    if (empty($id) || empty($admin_id)) {
        return ['title' => "Erro!", 'msg' => "ID do cliente ou do administrador inválido.", 'icon' => "error"];
    }
    
    // --- CÓDIGO DE VERIFICAÇÃO DE ADMIN ---
    $admin_profile_stmt = $conexao->prepare("SELECT admin FROM admin WHERE id = :admin_id");
    $admin_profile_stmt->execute([':admin_id' => $admin_id]);
    $admin_profile = $admin_profile_stmt->fetch(PDO::FETCH_ASSOC);
    $logged_in_admin_level = $admin_profile['admin'] ?? 0; // 1 é Full Admin, outros são Revendedores
    // --------------------------------------------

    $updates = []; $params = [];
    $name = $post_data['name'] ?? ''; $usuario = $post_data['usuario'] ?? ''; $senha = $post_data['senha'] ?? '';
    $data_vencimento = $post_data['data_de_vencimento'] ?? ''; $conexoes = $post_data['conexoes'] ?? 1;
    $adulto = $post_data['adulto'] ?? 0; $plano = $post_data['plano'] ?? 0; $bouquet_id = $post_data['bouquet_id'] ?? null;
    if (empty($bouquet_id)) { $bouquet_id = null; }
    
    if (!empty($name)) { $updates[] = "name = :name"; $params[':name'] = $name; }
    if (!empty($usuario)) { $updates[] = "usuario = :usuario"; $params[':usuario'] = $usuario; }
    if (!empty($senha)) { $updates[] = "senha = :senha"; $params[':senha'] = $senha; }
    
    // --- RESTRIÇÃO DE SALVAMENTO DE DATA (EXISTENTE) ---
    // Apenas se for Full Admin (nível 1) E a data de vencimento não estiver vazia, a atualização é permitida.
    if ($logged_in_admin_level == 1 && !empty($data_vencimento)) { 
        $updates[] = "Vencimento = :Vencimento"; 
        $params[':Vencimento'] = date("Y-m-d 23:59:59", strtotime($data_vencimento)); 
    } else if ($logged_in_admin_level != 1) {
        // Se for revendedor, garantimos que NENHUMA alteração de data ocorra.
    }
    // ----------------------------------------------------

    // --- RESTRIÇÃO DE SALVAMENTO DE CONEXÕES (EXISTENTE NO CÓDIGO ANTERIOR) ---
    // Apenas Full Admin pode atualizar conexões.
    if ($logged_in_admin_level == 1 && isset($post_data['conexoes']) && !empty($conexoes)) {
        $updates[] = "conexoes = :conexoes"; 
        $params[':conexoes'] = $conexoes;
    }
    // --------------------------------------------------------------------------
    
    if (isset($post_data['adulto'])) { $updates[] = "adulto = :adulto"; $params[':adulto'] = $adulto; }
    if (!empty($plano)) { $updates[] = "plano = :plano"; $params[':plano'] = $plano; }
    $updates[] = "bouquet_id = :bouquet_id"; $params[':bouquet_id'] = $bouquet_id;
    if (count($updates) > 0) {
        $sql_update = "UPDATE clientes SET " . implode(', ', $updates) . " WHERE id = :id AND admin_id = :admin_id";
        $params[':id'] = $id; $params[':admin_id'] = $admin_id;
        $stmt_update = $conexao->prepare($sql_update);
        if ($stmt_update->execute($params)) {
            return ['title' => "Concluído!", 'msg' => "Cliente editado com sucesso", 'icon' => "success", 'data_table' => 'atualizar'];
        }
        return ['title' => "Erro de Banco de Dados!", 'msg' => "Não foi possível executar a atualização.", 'icon' => "error"];
    }
    return ['title' => "Atenção!", 'msg' => "Nenhum dado para alterar foi enviado.", 'icon' => "warning"];
}

function adicionar_clientes()
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;

    // --- CÓDIGO DE VERIFICAÇÃO DE ADMIN (ADICIONADO) ---
    $admin_profile_stmt = $conexao->prepare("SELECT admin FROM admin WHERE id = :admin_id");
    $admin_profile_stmt->execute([':admin_id' => $admin_id]);
    $admin_profile = $admin_profile_stmt->fetch(PDO::FETCH_ASSOC);
    $logged_in_admin_level = $admin_profile['admin'] ?? 0; // 1 é Full Admin, outros são Revendedores
    // ----------------------------------------------------

    $plano1 = $conexao->prepare("SELECT * FROM planos WHERE admin_id = ? ORDER BY nome ASC");
    $plano1->execute([$admin_id]);
    $planoOptions = "<option value='' selected disabled>Selecione um plano</option>";
    while ($lista_plano = $plano1->fetch()) {
        $planoOptions .= '<option value="'.$lista_plano['id'].'">'.htmlspecialchars($lista_plano['nome']).' [R$: '.$lista_plano['valor'].']</option>';
    }
    $modal_body = '<input type="hidden" name="confirme_adicionar_clientes" value="1">';
    $modal_body .= '<div class="form-group mb-2"><label>Nome do cliente:</label><input type="text" class="form-control" name="name" placeholder="Nome completo do cliente" required></div>';
    $modal_body .= '<div class="row mb-2"><div class="form-group col-md-6"><label>Usuário:</label><input type="text" class="form-control" name="usuario" placeholder="login" required></div><div class="form-group col-md-6"><label>Senha:</label><input type="text" class="form-control" name="senha" placeholder="senha" required></div></div>';
    $modal_body .= '<div class="form-group mb-2"><label>Plano:</label><select class="form-select" name="plano" required>'.$planoOptions.'</select></div>';
    $modal_body .= '<div class="row mb-2"><div class="form-group col-md-6"><label>Validade (Meses):</label><input type="number" class="form-control" name="meses" value="1" min="1" required></div>';
    
    // --- RESTRIÇÃO DE CONEXÕES MÁXIMAS (NOVA) ---
    $modal_body .= '<div class="form-group col-md-6"><label>Conexões:</label>';
    if ($logged_in_admin_level == 1) {
        // Full Admin pode escolher o número de conexões
        $modal_body .= '<input type="number" class="form-control" name="conexoes" value="1" min="1"></div>';
    } else {
        // Revendedor tem o campo fixo em 1 (readonly)
        $modal_body .= '<input type="number" class="form-control" name="conexoes" value="1" min="1" readonly></div>';
    }
    // ---------------------------------------------
    
    $modal_body .= '</div>'; // Fecha a linha mb-2
    $modal_body .= '<div class="form-group mb-2"><label>Whatsapp (Opcional):</label><input type="text" class="form-control" name="whatsapp" placeholder="Ex: 5511999998888"></div>';
    $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"testes.php\")' class='btn btn-info'>Salvar</button><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancelar</button>";
    return ['modal_header_class'=> "d-block modal-header bg-info text-white", 'modal_titulo'=> "Adicionar Novo Cliente", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
}

function confirme_adicionar_clientes($post_data)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $name = trim($post_data["name"] ?? ''); $usuario = trim($post_data["usuario"] ?? ''); $senha = trim($post_data["senha"] ?? '');
    $plano = (int)($post_data["plano"] ?? 0); $whatsapp = preg_replace('/[^0-9]/', '', $post_data["whatsapp"] ?? '');
    $meses = (int)($post_data["meses"] ?? 1); $conexoes_post = (int)($post_data["conexoes"] ?? 1); // Armazena o valor enviado

    if (empty($name) || empty($usuario) || empty($senha) || empty($plano)) {
        return ['title' => 'Erro!', 'msg' => 'Nome, usuário, senha e plano são obrigatórios.', 'icon' => 'error'];
    }
    if (empty($admin_id)) { return ['title' => 'Erro!', 'msg' => 'Sessão de administrador inválida.', 'icon' => 'error']; }
    $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = ?");
    $stmt_check->execute([$usuario]);
    if ($stmt_check->fetch()) { return ['title' => 'Erro!', 'msg' => 'Este nome de usuário já está em uso.', 'icon' => 'error']; }
    $stmt_admin = $conexao->prepare("SELECT admin, creditos FROM admin WHERE id = ?");
    $stmt_admin->execute([$admin_id]);
    $admin_info = $stmt_admin->fetch();
    if ($admin_info['admin'] != 1 && $admin_info['creditos'] < $meses) {
        return ['title' => 'Erro!', 'msg' => 'Você não tem créditos suficientes.', 'icon' => 'error'];
    }

    // --- RESTRIÇÃO DE VALOR DE CONEXÕES (NOVA) ---
    if ($admin_info['admin'] != 1) {
        // Se não for Full Admin (é Revendedor), força o valor de conexões para 1
        $conexoes = 1;
    } else {
        // Se for Full Admin, usa o valor enviado, garantindo que seja pelo menos 1
        $conexoes = max(1, $conexoes_post);
    }
    // ---------------------------------------------
    
    $data_vencimento = date("Y-m-d 23:59:59", strtotime("+$meses month"));
    $sql = "INSERT INTO clientes (admin_id, name, usuario, senha, plano, conexoes, Vencimento, Whatsapp, is_trial, Criado_em, Ultimo_pagamento) 
             VALUES (:admin_id, :name, :usuario, :senha, :plano, :conexoes, :vencimento, :whatsapp, 0, NOW(), NOW())";
    $stmt_insert = $conexao->prepare($sql);
    $params = [ ':admin_id' => $admin_id, ':name' => $name, ':usuario' => $usuario, ':senha' => $senha, ':plano' => $plano, ':conexoes' => $conexoes, ':vencimento' => $data_vencimento, ':whatsapp' => $whatsapp ];
    $conexao->beginTransaction();
    try {
        $stmt_insert->execute($params);
        if ($admin_info['admin'] != 1) {
            $conexao->prepare("UPDATE admin SET creditos = creditos - ? WHERE id = ?")->execute([$meses, $admin_id]);
            $sql_log = "INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (?, ?, ?, ?, ?)";
            $conexao->prepare($sql_log)->execute([$admin_id, $admin_id, -$meses, time(), "Criação de cliente: " . $usuario]);
        }
        $conexao->commit();
        return ['title' => 'Sucesso!', 'msg' => 'Cliente adicionado com sucesso!', 'icon' => 'success', 'data_table' => 'atualizar'];
    } catch (Exception $e) {
        $conexao->rollBack();
        error_log("Erro ao adicionar cliente: " . $e->getMessage());
        return ['title' => 'Erro!', 'msg' => 'Não foi possível adicionar o cliente.', 'icon' => 'error'];
    }
}

function renovar_cliente($id, $usuario)
{
    $modal_body = "<input type=\"hidden\" name=\"confirme_renovar_cliente\" value='$id'>";
    $modal_body .= "<label>Renovar por (meses):</label>";
    $modal_body .= "<input type='number' name='meses' class='form-control' value='1' min='-12' max='12'>";
    $modal_footer = "<button type='button' class='btn btn-info' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>Renovar</button>";
    return ['modal_header_class'=> "d-block modal-header bg-info text-white", 'modal_titulo'=> "Renovar Cliente ($usuario)", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
}

function confirme_renovar_cliente($id, $meses)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $sql = "SELECT c.*, a.admin as is_admin, a.creditos FROM clientes c LEFT JOIN admin a ON c.admin_id = a.id WHERE c.id = :id AND c.admin_id = :admin_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([':id' => $id, ':admin_id' => $admin_id]);
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        if ($is_admin != 1 && isset($creditos) && $creditos < $meses) {
            return ['title' => "Erro!", 'msg' => "Você não tem créditos suficientes.", 'icon' => "error"];
        }
        $base_data = (strtotime($Vencimento) > time()) ? $Vencimento : date("Y-m-d H:i:s");
        $nova_data = date("Y-m-d 23:59:59", strtotime("+$meses month", strtotime($base_data)));
        $conexao->beginTransaction();
        try {
            $stmt_update = $conexao->prepare("UPDATE clientes SET Vencimento = ?, Ultimo_pagamento = NOW() WHERE id = ?");
            $stmt_update->execute([$nova_data, $id]);
            
            if ($is_admin != 1) {
                $conexao->prepare("UPDATE admin SET creditos = creditos - ? WHERE id = ?")->execute([$meses, $admin_id]);
                $sql_log = "INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (?, ?, ?, ?, ?)";
                $conexao->prepare($sql_log)->execute([$admin_id, $admin_id, -$meses, time(), "Renovação do cliente: " . $usuario]);
            }
            
            $conexao->commit();

            // LÓGICA DA MENSAGEM DETALHADA RESTAURADA
            $mensagem = "Plano renovado com sucesso!\n\n👤 Usuário: $usuario\n🔑 Senha: $senha\n📅 Próximo vencimento: " . date("d/m/Y", strtotime($nova_data));
            $whatsapp_link = "";
            if (!empty($Whatsapp)) {
                $numero_wa = preg_replace('/[^0-9]/', '', $Whatsapp);
                $whatsapp_link = "<a href='https://wa.me/$numero_wa?text=".urlencode($mensagem)."' target='_blank' class='btn btn-success btn-sm mt-2 ms-2'><i class='fab fa-whatsapp'></i> Enviar WhatsApp</a>";
            }
            $html_msg = '<div id="msg-renovacao" style="text-align: left; white-space:pre-wrap;background:#f8f9fa;padding:15px;border-radius:5px; border:1px solid #dee2e6; color: #212529;">'.htmlspecialchars($mensagem).'</div>';
            $copy_button = '<button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'msg-renovacao\').textContent); SweetAlert3(\'Copiado!\', \'success\')" class="btn btn-primary btn-sm mt-2">Copiar Mensagem</button>';

            return ['title' => "Sucesso!", 'msg' => $html_msg . $copy_button . $whatsapp_link, 'icon' => "success", 'data_table' => 'atualizar'];

        } catch (Exception $e) {
            $conexao->rollBack();
            return ['title' => "Erro!", 'msg' => "Não foi possível renovar o cliente.", 'icon' => "error"];
        }
    }
    return ['title' => "Erro!", 'msg' => "Cliente não encontrado.", 'icon' => "error"];
}

function delete_cliente($id, $usuario)
{
    $modal_body = "<input type=\"hidden\" name=\"confirme_delete_cliente\" value='$id'>";
    $modal_body .= "Tem certeza de que deseja excluir o usuário ($usuario) ?";
    $modal_footer = "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button><button type='button' class='btn btn-danger' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>EXCLUIR</button>";
    return ['modal_header_class'=> "d-block modal-header bg-danger text-white", 'modal_titulo'=> "EXCLUIR USUÁRIO", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
}

function confirme_delete_cliente($id, $usuario)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $stmt = $conexao->prepare("DELETE FROM clientes WHERE id = :id AND admin_id = :admin_id");
    if ($stmt->execute([':id' => $id, ':admin_id' => $admin_id]) && $stmt->rowCount() > 0) {
        return ['title' => "Sucesso!", 'msg' => "Usuário deletado com sucesso!", 'icon' => "success", 'data_table' => 'atualizar'];
    }
    return ['title' => "Erro!", 'msg' => "Erro ao deletar usuário ou permissão negada.", 'icon' => "error"];
}

/**
 * Funções Corrigidas para Testes
 * 1. Adicionado verificação de nível de Admin (Revendedor vs Full Admin).
 * 2. Adicionado 'disabled' no campo 'Horas' (Validade).
 * 3. Adicionado 'readonly' no campo 'Conexões' para Revendedores.
 */
function adicionar_testes()
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;

    // --- CÓDIGO DE VERIFICAÇÃO DE ADMIN ---
    $admin_profile_stmt = $conexao->prepare("SELECT admin FROM admin WHERE id = :admin_id");
    $admin_profile_stmt->execute([':admin_id' => $admin_id]);
    $admin_profile = $admin_profile_stmt->fetch(PDO::FETCH_ASSOC);
    $logged_in_admin_level = $admin_profile['admin'] ?? 0; // 1 é Full Admin
    // ------------------------------------

    $plano_id_teste = 64; $plano_nome = "Plano Teste";
    $modal_body = '<input type="hidden" name="confirme_adicionar_testes" value="1">';
    $modal_body .= '<div class="form-group mb-2"><label>Nome do Teste:</label><input type="text" class="form-control" name="name" placeholder="Ex: Teste João" required></div>';
    $modal_body .= '<div class="row mb-2"><div class="form-group col-md-6"><label>Usuário:</label><input type="text" class="form-control" name="usuario" placeholder="login" required></div><div class="form-group col-md-6"><label>Senha:</label><input type="text" class="form-control" name="senha" placeholder="senha" required></div></div>';
    $modal_body .= "<input type='hidden' name='plano' value='{$plano_id_teste}'>";
    $modal_body .= "<div class='form-group mb-2'><label>Plano:</label><input type='text' class='form-control' value='{$plano_nome}' readonly></div>";
    
    // CORREÇÃO FRONTEND: Horas Fixas e Desabilitadas (disabled)
    $input_horas_disabled = 'disabled'; // Trava o campo para 4 horas
    
    // CORREÇÃO FRONTEND: Conexões readonly se não for Full Admin
    $input_conexoes_readonly = ($logged_in_admin_level != 1) ? 'readonly' : '';
    
    // Campo de Horas agora é fixo em 4 e desabilitado (disabled)
    $modal_body .= '<div class="row mb-2"><div class="form-group col-md-6"><label>Validade (Horas):</label><input type="number" class="form-control" name="horas" value="4" min="1" required '.$input_horas_disabled.'></div><div class="form-group col-md-6"><label>Conexões:</label><input type="number" class="form-control" name="conexoes" value="1" min="1" '.$input_conexoes_readonly.'></div></div>';
    
    $modal_body .= '<div class="form-group mb-2"><label>Whatsapp (Opcional):</label><input type="text" class="form-control" name="whatsapp" placeholder="Ex: 5511999998888"></div>';
    $modal_footer = "<button type='button' onclick='enviardados(\"modal_master_form\", \"testes.php\")' class='btn btn-info'>Salvar Teste</button><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>Cancelar</button>";
    return ['modal_header_class'=> "d-block modal-header bg-info text-white", 'modal_titulo'=> "Adicionar Novo Teste", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
}

/**
 * Funções Corrigidas para Testes
 * 1. Implementa a regra de segurança: Força conexões = 1 para Revendedores.
 * 2. CORREÇÃO DE CRÉDITO: custo_credito_teste foi alterado de 1 para 0.
 */
function confirme_adicionar_testes($post_data)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $name = trim($post_data["name"] ?? ''); $usuario = trim($post_data["usuario"] ?? ''); $senha = trim($post_data["senha"] ?? '');
    $plano = (int)($post_data["plano"] ?? 0); $whatsapp = preg_replace('/[^0-9]/', '', $post_data["whatsapp"] ?? '');
    $horas = (int)($post_data["horas"] ?? 4); 
    $conexoes_post = (int)($post_data["conexoes"] ?? 1); // Valor enviado pelo formulário
    $custo_credito_teste = 0; // CORREÇÃO APLICADA AQUI: Testes manuais não devem cobrar crédito

    if (empty($name) || empty($usuario) || empty($senha) || empty($plano)) {
        return ['title' => 'Erro!', 'msg' => 'Nome, usuário, senha e plano são obrigatórios.', 'icon' => 'error'];
    }
    if (empty($admin_id)) { return ['title' => 'Erro!', 'msg' => 'Sessão de administrador inválida.', 'icon' => 'error']; }
    $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = ?");
    $stmt_check->execute([$usuario]);
    if ($stmt_check->fetch()) { return ['title' => 'Erro!', 'msg' => 'Este nome de usuário já está em uso.', 'icon' => 'error']; }
    $stmt_admin = $conexao->prepare("SELECT admin, creditos FROM admin WHERE id = ?");
    $stmt_admin->execute([$admin_id]);
    $admin_info = $stmt_admin->fetch();
    
    // A verificação de crédito ainda é feita, mas agora com custo 0.
    if ($admin_info['admin'] != 1 && $admin_info['creditos'] < $custo_credito_teste) {
        return ['title' => 'Erro!', 'msg' => 'Você não tem créditos suficientes.', 'icon' => 'error'];
    }
    
    // CORREÇÃO BACKEND: Implementa a regra de segurança
    if ($admin_info['admin'] != 1) {
        // Se for revendedor, o valor de conexões é forçado a 1
        $conexoes = 1;
    } else {
        // Se for Full Admin, usa o valor do post, garantindo que seja pelo menos 1
        $conexoes = max(1, $conexoes_post);
    }
    // O restante do código usa a variável $conexoes (que já está validada/forçada)
    
    $data_vencimento = date("Y-m-d H:i:s", strtotime("+$horas hours"));
    $sql = "INSERT INTO clientes (admin_id, name, usuario, senha, plano, conexoes, Vencimento, Whatsapp, is_trial, Criado_em) 
             VALUES (:admin_id, :name, :usuario, :senha, :plano, :conexoes, :vencimento, :whatsapp, 1, NOW())";
    $stmt_insert = $conexao->prepare($sql);
    $params = [ ':admin_id' => $admin_id, ':name' => $name, ':usuario' => $usuario, ':senha' => $senha, ':plano' => $plano, ':conexoes' => $conexoes, ':vencimento' => $data_vencimento, ':whatsapp' => $whatsapp ];
    $conexao->beginTransaction();
    try {
        $stmt_insert->execute($params);
        if ($admin_info['admin'] != 1) {
            // A dedução só ocorrerá se custo_credito_teste for > 0 (o que não será mais o caso para testes)
            if ($custo_credito_teste > 0) {
                $conexao->prepare("UPDATE admin SET creditos = creditos - ? WHERE id = ?")->execute([$custo_credito_teste, $admin_id]);
                $sql_log = "INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (?, ?, ?, ?, ?)";
                $conexao->prepare($sql_log)->execute([$admin_id, $admin_id, -$custo_credito_teste, time(), "Criação de teste: " . $usuario]);
            }
        }
        $conexao->commit();
        return ['title' => 'Sucesso!', 'msg' => 'Teste adicionado com sucesso!', 'icon' => 'success', 'data_table' => 'atualizar'];
    } catch (Exception $e) {
        $conexao->rollBack();
        error_log("Erro ao adicionar teste: " . $e->getMessage());
        return ['title' => 'Erro!', 'msg' => 'Não foi possível adicionar o teste.', 'icon' => 'error'];
    }
}

function converter_teste($id, $usuario)
{
    $modal_body = "<input type=\"hidden\" name=\"confirme_converter_teste\" value='$id'>";
    $modal_body .= "Deseja converter o teste do usuário <strong>($usuario)</strong> em um cliente ativo?<br><br>";
    $modal_body .= "Esta ação consumirá <strong>1 crédito</strong> e definirá o vencimento para daqui a <strong>1 mês</strong>.";
    $modal_footer = "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button><button type='button' class='btn btn-success' onclick='enviardados(\"modal_master_form\", \"testes.php\")'>Confirmar Conversão</button>";
    return ['modal_header_class'=> "d-block modal-header bg-success text-white", 'modal_titulo'=> "Converter Teste em Cliente", 'modal_body'=> $modal_body, 'modal_footer'=> $modal_footer];
}

function confirme_converter_teste($id)
{
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $custo_credito = 1;
    if (empty($admin_id)) { return ['title' => "Erro!", 'msg' => "Sessão inválida.", 'icon' => "error"]; }
    $stmt_admin = $conexao->prepare("SELECT admin, creditos FROM admin WHERE id = ?");
    $stmt_admin->execute([$admin_id]);
    $admin_info = $stmt_admin->fetch();
    if ($admin_info['admin'] != 1 && $admin_info['creditos'] < $custo_credito) {
        return ['title' => "Erro!", 'msg' => "Você não tem créditos suficientes.", 'icon' => "error"];
    }
    $nova_data = date("Y-m-d 23:59:59", strtotime("+1 month"));
    $conexao->beginTransaction();
    try {
        $stmt_update = $conexao->prepare("UPDATE clientes SET Vencimento = ?, is_trial = 0, Ultimo_pagamento = NOW() WHERE id = ? AND admin_id = ?");
        $stmt_update->execute([$nova_data, $id, $admin_id]);
        if ($admin_info['admin'] != 1) {
            $conexao->prepare("UPDATE admin SET creditos = creditos - ? WHERE id = ?")->execute([$custo_credito, $admin_id]);
            $stmt_log = $conexao->prepare("INSERT INTO credits_log (target_id, admin_id, amount, date, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt_log->execute([$admin_id, $admin_id, -$custo_credito, time(), "Conversão de teste para cliente ID: " . $id]);
        }
        $conexao->commit();
        return ['title' => "Sucesso!", 'msg' => "Teste convertido em cliente com sucesso!", 'icon' => "success", 'data_table' => 'atualizar'];
    } catch (Exception $e) {
        $conexao->rollBack();
        return ['title' => "Erro!", 'msg' => "Não foi possível converter o teste.", 'icon' => "error"];
    }
}
