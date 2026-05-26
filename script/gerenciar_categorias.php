<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ======================================================================
//      ESTRUTURA DE API NO TOPO DO ARQUIVO
//      Decide se a requisição é para a API ou para carregar a página.
// ======================================================================
require_once("./api/controles/db.php");
$conexao = conectar_bd();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action) {
    switch ($action) {
        case 'get_list':
            header('Content-Type: text/html; charset=utf-8'); // Retorna HTML
            $type = $_GET['type'] ?? 'live'; // Padrão agora é 'live'
            
            // CORREÇÃO: A validação agora inclui 'live' em vez de 'streams'
            if (!in_array($type, ['live', 'movie', 'series'])) { $type = 'live'; }

            $stmt = $conexao->prepare("SELECT `id`, `nome` FROM `categoria` WHERE `type` = ? ORDER BY `position` ASC");
            $stmt->execute([$type]);
            foreach ($stmt->fetchAll() as $cat) {
                echo '<li data-id="' . $cat['id'] . '">
                          <input type="checkbox" class="category-checkbox form-check-input" data-id="' . $cat['id'] . '">
                          <span class="drag-handle">↕️</span>
                          <span class="category-name">' . htmlspecialchars($cat['nome']) . '</span>
                          <div class="item-actions">
                              <button class="edit-btn" data-id="' . $cat['id'] . '" title="Editar">✏️</button>
                              <button class="delete-btn" data-id="' . $cat['id'] . '" title="Excluir">🗑️</button>
                          </div>
                      </li>';
            }
            break;

        case 'save_order':
        case 'get_category_details':
        case 'save_edit':
        case 'delete_category':
        case 'delete_bulk':
        case 'add_category': // Lógica para adicionar categoria
            header('Content-Type: application/json; charset=utf-8'); // Retorna JSON
            
            if ($action == 'add_category') {
                $nome = trim($_POST['nome'] ?? '');
                // CORREÇÃO: Agora o 'type' é enviado explicitamente do modal
                $type = trim($_POST['type'] ?? ''); 
                
                if (empty($nome) || !in_array($type, ['live', 'movie', 'series'])) { 
                    http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Nome e Tipo são obrigatórios e válidos.']); exit(); 
                }

                // 1. Encontrar a próxima posição
                $stmt_pos = $conexao->prepare("SELECT COALESCE(MAX(`position`), 0) AS max_pos FROM `categoria` WHERE `type` = ?");
                $stmt_pos->execute([$type]);
                $max_pos = $stmt_pos->fetchColumn();
                $new_position = $max_pos + 1;

                // 2. Inserir a nova categoria
                $stmt = $conexao->prepare("INSERT INTO `categoria` (`nome`, `type`, `position`) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $type, $new_position]);
                
                echo json_encode(['status' => 'success', 'message' => 'Categoria "' . htmlspecialchars($nome) . '" adicionada com sucesso!', 'id' => $conexao->lastInsertId()]);
                exit();
            }

            if ($action == 'save_order') {
                $order_data = json_decode($_POST['order'] ?? '[]', true);
                if (empty($order_data)) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']); exit(); }
                $conexao->beginTransaction();
                try {
                    $stmt = $conexao->prepare("UPDATE `categoria` SET `position` = ? WHERE `id` = ?");
                    foreach ($order_data as $index => $id) { $stmt->execute([$index + 1, intval($id['id'] ?? $id)]); }
                    $conexao->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Ordem salva com sucesso!']);
                } catch (Exception $e) { $conexao->rollBack(); http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar a ordem.']); }
            }
            if ($action == 'get_category_details') {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $conexao->prepare("SELECT `id`, `nome` FROM `categoria` WHERE `id` = ?");
                $stmt->execute([$id]);
                $category = $stmt->fetch();
                if ($category) { echo json_encode(['status' => 'success', 'data' => $category]); } 
                else { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Categoria não encontrada.']); }
            }
            if ($action == 'save_edit') {
                $id = intval($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                if (empty($id) || empty($nome)) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'ID e Nome são obrigatórios.']); exit(); }
                $stmt = $conexao->prepare("UPDATE `categoria` SET `nome` = ? WHERE `id` = ?");
                $stmt->execute([$nome, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Categoria atualizada!']);
            }
            if ($action == 'delete_category') {
                $id = intval($_POST['id'] ?? 0);
                $stmt = $conexao->prepare("DELETE FROM `categoria` WHERE `id` = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success']);
            }
            if ($action == 'delete_bulk') {
                $ids = $_POST['ids'] ?? [];
                if (empty($ids) || !is_array($ids)) { http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Nenhum ID selecionado.']); exit(); }
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $conexao->prepare("DELETE FROM `categoria` WHERE `id` IN ($placeholders)");
                $stmt->execute($ids);
                echo json_encode(['status' => 'success', 'message' => count($ids) . ' categorias foram apagadas.']);
            }
            break;
    }
    exit(); // Importante: termina o script após uma requisição AJAX
}

// --- PÁGINA HTML (SÓ É EXECUTADA SE NÃO FOR UMA REQUISIÇÃO AJAX) ---
require_once("menu.php");
// CORREÇÃO: A busca inicial agora procura por 'live'
$categorias_iniciais = $conexao->query("SELECT `id`, `nome` FROM `categoria` WHERE `type` = 'live' ORDER BY `position` ASC")->fetchAll();
?>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Estilos específicos para esta página */
    .tabs { margin-bottom: 20px; display: flex; border-bottom: 2px solid var(--border-color); }
    .tab-button { background: transparent; border: 1px solid transparent; border-bottom: none; padding: 10px 20px; cursor: pointer; font-size: 16px; color: var(--text-secondary); }
    .tab-button.active { border-color: var(--border-color); border-bottom: 2px solid var(--bg-card); position: relative; top: 1px; font-weight: bold; color: var(--text-primary); border-radius: 5px 5px 0 0; }
    .list-container { border: 1px solid var(--border-color); padding: 10px; border-radius: 4px; }
    #sortable-list { list-style: none; padding: 0; margin: 0; min-height: 100px; }
    #sortable-list li { cursor: move; padding: 10px 15px; margin-bottom: 5px; border: 1px solid var(--border-color); background-color: var(--bg-card); border-radius: 4px; display: flex; align-items: center; justify-content: space-between; }
    .category-checkbox { margin-right: 15px; transform: scale(1.4); }
    .drag-handle { display: inline-block; width: 20px; cursor: grab; font-size: 20px; color: #aaa; }
    .category-name { flex-grow: 1; margin-left: 10px; }
    .item-actions button { background: none; border: none; cursor: pointer; font-size: 20px; margin-left: 10px;}
    .ui-sortable-placeholder { border: 2px dashed #0d6efd; background-color: rgba(13, 110, 253, 0.1); height: 45px; visibility: visible !important; }
    .controls-container { display: flex; justify-content: space-between; align-items: center; padding: 10px; background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); }
    .bulk-actions { display: flex; align-items: center; }
    #selectAll { margin-right: 10px; transform: scale(1.4); }
    .main-actions { text-align: center; padding-top: 20px; }
    .action-button { font-size: 16px; font-weight: bold; padding: 12px 25px; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
    #saveButton { background-color: #28a745; }
    #deleteSelectedButton { background-color: #dc3545; margin-left: 10px; }
    #addButton { background-color: #0d6efd; } /* NOVO ESTILO */
    /* Estilos do Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1050; }
    .modal-content-custom { background: var(--bg-card); padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; }
    .modal-content-custom h2 { margin-top: 0; }
    .modal-content-custom input[type="text"], .modal-content-custom select { width: 100%; padding: 10px; font-size: 16px; margin-bottom: 20px; box-sizing: border-box; } /* Ajuste para o select */
    .modal-actions { text-align: right; }
    .modal-actions button { margin-left: 10px; }
    /* Novo estilo para o cabeçalho */
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; } 
</style>

    <div class="card">
        <div class="card-body">
            <div class="tabs">
                <button class="tab-button active" data-type="live">Canais</button>
                <button class="tab-button" data-type="movie">Filmes</button>
                <button class="tab-button" data-type="series">Séries</button>
            </div>
            <div class="list-container">
                <div class="controls-container">
                    <div class="bulk-actions">
                        <input type="checkbox" id="selectAll" title="Selecionar Tudo" class="form-check-input">
                        <button id="deleteSelectedButton" class="btn btn-sm btn-danger ms-2">Apagar Selecionados</button>
                    </div>
                    <button id="addButton" class="action-button">Adicionar Nova Categoria</button>
                </div>
                <ul id="sortable-list">
                    <?php foreach ($categorias_iniciais as $cat): ?>
                        <li data-id="<?php echo $cat['id']; ?>">
                            <input type="checkbox" class="category-checkbox form-check-input" data-id="<?php echo $cat['id']; ?>">
                            <span class="drag-handle">↕️</span>
                            <span class="category-name"><?php echo htmlspecialchars($cat['nome']); ?></span>
                            <div class="item-actions">
                                <button class="edit-btn" data-id="<?php echo $cat['id']; ?>" title="Editar">✏️</button>
                                <button class="delete-btn" data-id="<?php echo $cat['id']; ?>" title="Excluir">🗑️</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="main-actions">
                <button id="saveButton" class="action-button">Salvar Ordem da Lista Atual</button>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-content-custom">
        <h2>Editar Categoria</h2>
        <input type="hidden" id="edit_id">
        <input type="text" id="edit_nome" placeholder="Nome da Categoria" class="form-control">
        <div class="modal-actions">
            <button id="cancelEditButton" class="btn btn-secondary">Cancelar</button>
            <button id="saveEditButton" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </div>
</div>

<div id="addModal" class="modal-overlay">
    <div class="modal-content-custom">
        <h2>Adicionar Nova Categoria</h2>
        
        <label for="add_nome" class="form-label">Nome da Categoria:</label>
        <input type="text" id="add_nome" placeholder="Ex: Notícias, Ação, Novelas" class="form-control">

        <label for="add_type" class="form-label">Tipo de Categoria:</label>
        <select id="add_type" class="form-select">
            <option value="live">Canais (Live)</option>
            <option value="movie">Filmes (VOD)</option>
            <option value="series">Séries (VOD)</option>
        </select>

        <div class="modal-actions">
            <button id="cancelAddButton" class="btn btn-secondary">Cancelar</button>
            <button id="saveAddButton" class="btn btn-primary">Adicionar</button>
        </div>
    </div>
</div>
    
<script>
$(document).ready(function() {
    const list = $("#sortable-list");
    const editModal = $('#editModal');
    const addModal = $('#addModal'); 
    const selectAllCheckbox = $('#selectAll');

    function makeSortable() { list.sortable({ handle: ".drag-handle", placeholder: "ui-sortable-placeholder" }); }
    makeSortable();

    function loadList(type) {
        list.html('<li>Carregando...</li>');
        $.get('gerenciar_categorias.php', { action: 'get_list', type: type }, function(responseHtml) {
            list.html(responseHtml);
            makeSortable();
            selectAllCheckbox.prop('checked', false);
        }).fail(function() { list.html('<li><span style="color: red;">Erro ao carregar.</span></li>'); });
    }

    // --- LÓGICA DE ADICIONAR CATEGORIA (REVISADA) ---
    $('#addButton').on('click', function() {
        $('#add_nome').val(''); // Limpa o campo
        
        // Define o tipo padrão do modal para a aba atualmente ativa
        let currentType = $('.tab-button.active').data('type');
        $('#add_type').val(currentType); 
        
        addModal.css('display', 'flex');
    });

    $('#cancelAddButton').on('click', () => addModal.hide());

    $('#saveAddButton').on('click', function() {
        let nome = $('#add_nome').val().trim();
        // CORREÇÃO: Pega o tipo do select do modal, e não da aba
        let type = $('#add_type').val(); 
        
        if (nome === '') {
            Swal.fire('Atenção', 'O nome da categoria é obrigatório.', 'warning');
            return;
        }

        $.post('gerenciar_categorias.php', { action: 'add_category', nome: nome, type: type }, function(res) {
            if (res.status === 'success') {
                addModal.hide();
                Swal.fire('Sucesso!', res.message, 'success').then(() => {
                    // Recarrega a lista **da aba que corresponde ao novo item criado**
                    let activeType = $('.tab-button.active').data('type');
                    // Se a aba ativa for a mesma do item criado, recarrega
                    if (activeType === type) {
                        loadList(type); 
                    } else {
                        // Se não for a mesma aba, apenas notifica o usuário
                        Swal.fire('Sucesso!', 'A categoria foi adicionada em "' + type.toUpperCase() + '".', 'success');
                    }
                });
            } else { Swal.fire('Erro!', res.message, 'error'); }
        }, 'json').fail(function() { Swal.fire('Erro!', 'Não foi possível adicionar a categoria.', 'error'); });
    });
    // --- FIM LÓGICA DE ADICIONAR CATEGORIA ---


    $('.tab-button').on('click', function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        loadList($(this).data('type'));
    });

    $('#saveButton').on('click', function() {
        let order = list.find('li').map(function() { return $(this).data('id'); }).get();
        $.post('gerenciar_categorias.php', { action: 'save_order', order: JSON.stringify(order) }, function(res) {
            Swal.fire('Sucesso!', res.message, 'success');
        }, 'json').fail(function() { Swal.fire('Erro!', 'Não foi possível salvar a ordem.', 'error'); });
    });

    list.on('click', '.edit-btn', function() {
        let id = $(this).data('id');
        $.post('gerenciar_categorias.php', { action: 'get_category_details', id: id }, function(res) {
            if (res.status === 'success') {
                $('#edit_id').val(res.data.id);
                $('#edit_nome').val(res.data.nome);
                editModal.css('display', 'flex');
            } else { Swal.fire('Erro!', res.message, 'error'); }
        }, 'json').fail(function() { Swal.fire('Erro!', 'Não foi possível buscar os dados.', 'error'); });
    });

    $('#saveEditButton').on('click', function() {
        let id = $('#edit_id').val();
        let nome = $('#edit_nome').val();
        $.post('gerenciar_categorias.php', { action: 'save_edit', id: id, nome: nome }, function(res) {
            if (res.status === 'success') {
                editModal.hide();
                Swal.fire('Sucesso!', res.message, 'success');
                list.find('li[data-id="' + id + '"] .category-name').text(nome);
            } else { Swal.fire('Erro!', res.message, 'error'); }
        }, 'json').fail(function() { Swal.fire('Erro!', 'Não foi possível salvar.', 'error'); });
    });
    
    $('#cancelEditButton').on('click', () => editModal.hide());

    list.on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let li_item = $(this).closest('li');
        Swal.fire({
            title: 'Tem certeza?', text: "Esta ação não pode ser desfeita!", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sim, apagar!', cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('gerenciar_categorias.php', { action: 'delete_category', id: id }, function(res) {
                    if (res.status === 'success') {
                        li_item.fadeOut(400, function() { $(this).remove(); });
                    } else { Swal.fire('Erro!', res.message, 'error'); }
                }, 'json').fail(function() { Swal.fire('Erro!', 'Não foi possível apagar.', 'error'); });
            }
        });
    });

    selectAllCheckbox.on('click', function() {
        list.find('.category-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#deleteSelectedButton').on('click', function() {
        let selectedIds = list.find('.category-checkbox:checked').map(function() { return $(this).data('id'); }).get();
        if (selectedIds.length === 0) {
            Swal.fire('Atenção', 'Selecione pelo menos uma categoria para apagar.', 'info'); return;
        }
        Swal.fire({
            title: 'Tem certeza?', text: `Você está prestes a apagar ${selectedIds.length} categorias!`, icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sim, apagar!', cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('gerenciar_categorias.php', { action: 'delete_bulk', ids: selectedIds }, function(res) {
                    if (res.status === 'success') {
                        loadList($('.tab-button.active').data('type'));
                        Swal.fire('Apagado!', res.message, 'success');
                    } else { Swal.fire('Erro!', res.message, 'error'); }
                }, 'json').fail(function() { Swal.fire('Erro!', 'Não foi possível apagar os itens selecionados.', 'error'); });
            }
        });
    });
});
</script>