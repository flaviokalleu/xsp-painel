<?php
// ======================================================================
//     GERENCIADOR DE BOUQUETS v2.2 - CONTAGEM DE CONTEÚDO E CSS MELHORADO
// ======================================================================

// --- 1. CONFIGURAÇÃO E CONEXÃO ---
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'xsp_panel';
$db_user = getenv('DB_USER') ?: 'xsp';
$db_pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

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
    die(json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]));
}


// --- 2. API INTERNA (AÇÕES VIA AJAX) ---
$action = $_POST['action'] ?? '';

if ($action) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        switch ($action) {
            case 'get_bouquets':
                $stmt = $pdo->query("SELECT id, bouquet_name FROM `bouquets` ORDER BY `bouquet_name` ASC");
                echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
                break;

            case 'get_details':
                $bouquet_id = intval($_POST['id'] ?? 0);
                
                // ============================================================
                // MODIFICAÇÃO AQUI: Consulta SQL para contar o conteúdo
                // ============================================================
                $sql = "
                    SELECT
                        c.id,
                        c.nome,
                        c.type,
                        CASE
                            WHEN c.type = 'series' THEN (SELECT COUNT(*) FROM `series` s WHERE s.category_id = c.id)
                            ELSE (SELECT COUNT(*) FROM `streams` st WHERE st.category_id = c.id AND st.stream_type = c.type)
                        END AS content_count
                    FROM
                        `categoria` c
                    ORDER BY
                        c.type, c.position ASC
                ";
                $all_cats_stmt = $pdo->query($sql);
                $all_categories = $all_cats_stmt->fetchAll();

                // Pega os IDs das categorias que JÁ PERTENCEM a este bouquet
                $selected_ids = [];
                if ($bouquet_id > 0) {
                    $selected_stmt = $pdo->prepare("SELECT category_id FROM `bouquet_items` WHERE bouquet_id = ?");
                    $selected_stmt->execute([$bouquet_id]);
                    $selected_ids = $selected_stmt->fetchAll(PDO::FETCH_COLUMN);
                }

                echo json_encode(['status' => 'success', 'data' => ['all' => $all_categories, 'selected' => $selected_ids]]);
                break;

            case 'save':
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $category_ids = $_POST['categories'] ?? [];

                if (empty($name)) {
                    throw new Exception('O nome do bouquet é obrigatório.');
                }

                $pdo->beginTransaction();

                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE `bouquets` SET bouquet_name = ? WHERE id = ?");
                    $stmt->execute([$name, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO `bouquets` (bouquet_name) VALUES (?)");
                    $stmt->execute([$name]);
                    $id = $pdo->lastInsertId();
                }

                $stmt_delete = $pdo->prepare("DELETE FROM `bouquet_items` WHERE bouquet_id = ?");
                $stmt_delete->execute([$id]);

                if (!empty($category_ids)) {
                    $stmt_insert = $pdo->prepare("INSERT INTO `bouquet_items` (bouquet_id, category_id) VALUES (?, ?)");
                    foreach ($category_ids as $cat_id) {
                        $stmt_insert->execute([$id, intval($cat_id)]);
                    }
                }

                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Bouquet salvo com sucesso!']);
                break;

            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID de bouquet inválido.');
                }
                
                $pdo->beginTransaction();
                $stmt1 = $pdo->prepare("DELETE FROM `bouquet_items` WHERE bouquet_id = ?");
                $stmt1->execute([$id]);
                $stmt2 = $pdo->prepare("DELETE FROM `bouquets` WHERE id = ?");
                $stmt2->execute([$id]);
                $pdo->commit();

                echo json_encode(['status' => 'success', 'message' => 'Bouquet apagado com sucesso!']);
                break;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    exit();
}


// --- 3. DADOS INICIAIS PARA A PÁGINA ---
$initial_bouquets = $pdo->query("SELECT id, bouquet_name FROM `bouquets` ORDER BY `bouquet_name` ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador de Bouquets</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ============================================================ */
        /* CSS MELHORADO COM CORES MODERNAS                     */
        /* ============================================================ */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        :root {
            --primary-color: #4a90e2;
            --primary-hover: #357ABD;
            --success-color: #28a745;
            --success-hover: #218838;
            --danger-color: #dc3545;
            --cancel-color: #6c757d;
            --cancel-hover: #5a6268;
            --bg-color: #f4f7f6;
            --container-bg: #ffffff;
            --text-color: #333;
            --text-light: #555;
            --border-color: #dee2e6;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
            color: var(--text-color);
        }
        .container {
            max-width: 1100px;
            margin: 20px auto;
            background: var(--container-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        
        /* NOVOS ESTILOS PARA O CABEÇALHO E BOTÃO VOLTAR AO INÍCIO */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color); /* Linha divisória */
        }
        .page-title {
            font-size: 1.8em;
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }
        .btn-inicio {
            background-color: #3c8dbc; /* Azul claro/padrão */
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-inicio:hover {
            background-color: #367fa9;
        }
        /* FIM DOS NOVOS ESTILOS */

        h1 { /* Mantido para não quebrar a estrutura existente, mas será substituído pelo .page-title */
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }
        .top-actions {
            text-align: right;
            margin-bottom: 20px;
        }
        .action-button {
            font-size: 14px;
            font-weight: 500;
            padding: 12px 24px;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .action-button:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        #createBouquetBtn { background-color: var(--primary-color); }
        #createBouquetBtn:hover { background-color: var(--primary-hover); }

        #bouquetsTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        #bouquetsTable th, #bouquetsTable td {
            border: 1px solid var(--border-color);
            padding: 15px;
            text-align: left;
        }
        #bouquetsTable th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .actions-cell button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            margin: 0 8px;
            color: var(--text-light);
            transition: color 0.3s;
        }
        .actions-cell button:hover {
            color: var(--primary-color);
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: var(--container-bg);
            padding: 30px;
            border-radius: 12px;
            width: 95%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .modal-content h2 {
            margin-top: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        #bouquet_name_input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            margin-bottom: 25px;
            box-sizing: border-box;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        .modal-actions {
            text-align: right;
            margin-top: 30px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        .modal-actions button {
            margin-left: 10px;
        }
        #saveButton { background-color: var(--success-color); }
        #saveButton:hover { background-color: var(--success-hover); }
        #cancelButton { background-color: var(--cancel-color); }
        #cancelButton:hover { background-color: var(--cancel-hover); }
        
        .category-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .category-column {
            background: #fdfdfd;
            border: 1px solid #f0f0f0;
            padding: 15px;
            border-radius: 8px;
        }
        .category-column h3 {
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            font-size: 1.1em;
            font-weight: 600;
        }
        .category-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .category-list label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            padding: 8px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .category-list label:hover {
            background-color: #e9f3fe;
        }
        .select-all-label {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .category-count {
            color: var(--text-light);
            font-size: 0.9em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="page-header">
            <h1 class="page-title">Gerenciador de Bouquets (Pacotes)</h1>
            <a href="/dashboard.php" class="btn-inicio">Voltar ao Início</a>
        </div>
        <div class="top-actions">
            <button id="createBouquetBtn" class="action-button">Criar Novo Bouquet</button>
        </div>
        <table id="bouquetsTable">
            <thead>
                <tr>
                    <th>Nome do Bouquet</th>
                    <th width="120px">Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div id="bouquetModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalTitle"></h2>
            <input type="hidden" id="bouquet_id_input">
            <input type="text" id="bouquet_name_input" placeholder="Nome do Pacote (ex: Plano Básico)">
            <div class="category-columns">
                <div class="category-column" id="streams-column">
                    <h3>Canais</h3>
                    <label class="select-all-label"><input type="checkbox" class="select-all-checkbox"> Selecionar Tudo</label>
                    <div class="category-list"></div>
                </div>
                <div class="category-column" id="movie-column">
                    <h3>Filmes</h3>
                    <label class="select-all-label"><input type="checkbox" class="select-all-checkbox"> Selecionar Tudo</label>
                    <div class="category-list"></div>
                </div>
                <div class="category-column" id="series-column">
                    <h3>Séries</h3>
                    <label class="select-all-label"><input type="checkbox" class="select-all-checkbox"> Selecionar Tudo</label>
                    <div class="category-list"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button id="cancelButton" class="action-button">Cancelar</button>
                <button id="saveButton" class="action-button">Salvar</button>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    const modal = $('#bouquetModal');
    const tableBody = $('#bouquetsTable tbody');

    function apiRequest(action, data, callback) {
        $.post('<?= basename(__FILE__) ?>', { action, ...data })
            .done(callback)
            .fail(function(jqXHR) {
                const errorMsg = jqXHR.responseJSON?.message || 'Ocorreu um erro desconhecido.';
                Swal.fire('Erro!', errorMsg, 'error');
            });
    }

    function loadBouquets() {
        tableBody.html('<tr><td colspan="2">Carregando...</td></tr>');
        apiRequest('get_bouquets', {}, function(res) {
            tableBody.empty();
            if (res.status === 'success' && res.data.length > 0) {
                res.data.forEach(function(b) {
                    tableBody.append(`
                        <tr data-id="${b.id}">
                            <td>${$('<textarea />').html(b.bouquet_name).text()}</td>
                            <td class="actions-cell">
                                <button class="edit-btn" title="Editar">✏️</button>
                                <button class="delete-btn" title="Apagar">🗑️</button>
                            </td>
                        </tr>`);
                });
            } else {
                tableBody.html('<tr><td colspan="2">Nenhum bouquet encontrado. Crie um novo.</td></tr>');
            }
        });
    }

    function openModal(id = 0, name = '') {
        $('#bouquet_id_input').val(id);
        $('#bouquet_name_input').val(name);
        $('#modalTitle').text(id ? 'Editar Bouquet' : 'Criar Novo Bouquet');
        
        $('.category-list').empty().html('Carregando...');
        $('.select-all-checkbox').prop('checked', false);

        apiRequest('get_details', { id }, function(res) {
            if (res.status === 'success') {
                $('.category-list').empty();
                
                if (res.data.all.length === 0) {
                    $('.category-list').html('Nenhuma categoria cadastrada.');
                    return;
                }

                res.data.all.forEach(function(cat) {
                    const isChecked = res.data.selected.includes(cat.id);
                    // ============================================================
                    // MODIFICAÇÃO AQUI: Exibindo a contagem de conteúdo
                    // ============================================================
                    const displayName = `${$('<textarea />').html(cat.nome).text()} <span class="category-count">(${cat.content_count})</span>`;
                    const checkboxHtml = `
                        <label>
                            <input type="checkbox" class="category-checkbox" value="${cat.id}" ${isChecked ? 'checked' : ''}>
                            ${displayName}
                        </label>`;
                    
                    let targetColumn = '';
                    if (cat.type === 'live') targetColumn = '#streams-column';
                    else if (cat.type === 'movie') targetColumn = '#movie-column';
                    else if (cat.type === 'series') targetColumn = '#series-column';

                    if (targetColumn) {
                        $(targetColumn).find('.category-list').append(checkboxHtml);
                    }
                });
            }
        });

        modal.css('display', 'flex');
    }

    // --- EVENTOS ---
    $('#createBouquetBtn').on('click', () => openModal());

    tableBody.on('click', '.edit-btn', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const name = row.find('td:first').text();
        openModal(id, name);
    });

    $('#cancelButton').on('click', () => modal.hide());

    $('#saveButton').on('click', function() {
        const id = $('#bouquet_id_input').val();
        const name = $('#bouquet_name_input').val();
        const selectedCategories = [];
        $('.category-checkbox:checked').each(function() {
            selectedCategories.push($(this).val());
        });
        
        apiRequest('save', { id, name, categories: selectedCategories }, function(res) {
            if (res.status === 'success') {
                modal.hide();
                Swal.fire('Sucesso!', res.message, 'success');
                loadBouquets();
            }
        });
    });

    tableBody.on('click', '.delete-btn', function() {
        const id = $(this).closest('tr').data('id');
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não pode ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, apagar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                apiRequest('delete', { id }, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Apagado!', res.message, 'success');
                        loadBouquets();
                    }
                });
            }
        });
    });

    $('.select-all-checkbox').on('change', function() {
        const isChecked = $(this).prop('checked');
        $(this).closest('.category-column').find('.category-checkbox').prop('checked', isChecked);
    });

    loadBouquets();
});
</script>
</body>
</html>