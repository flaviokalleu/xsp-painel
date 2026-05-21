<?php
// ======================================================================
//      SISTEMA DRAG & DROP DE ORGANIZAÇÃO DE CATEGORIAS v3.0
//      Versão Definitiva com todas as correções de PHP e JavaScript
// ======================================================================

// --- 1. CONFIGURAÇÃO DO BANCO DE DADOS ---
$db_host = 'localhost';
$db_name = 'u535247987_tvbox'; // Suas credenciais
$db_user = 'u535247987_tvbox'; // Suas credenciais
$db_pass = 'Jean#909110';      // Suas credenciais
$charset = 'utf8mb4';

// --- 2. CONEXÃO PDO ---
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados.']);
        exit();
    }
    die("Erro de Conexão: " . $e->getMessage());
}

// --- 3. LÓGICA DA API INTERNA (PARA AJAX) ---
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// AÇÃO PARA SALVAR A NOVA ORDEM
if ($action == 'save_order') {
    header('Content-Type: application/json; charset=utf-8');
    // Espera uma string JSON e a decodifica
    $order_data = json_decode($_POST['order'] ?? '[]', true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($order_data) || empty($order_data)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Dados de ordenação inválidos ou mal-formados.']);
        exit();
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE `categoria` SET `position` = ? WHERE `id` = ?");
        foreach ($order_data as $index => $id) {
            $stmt->execute([$index + 1, intval($id)]);
        }
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Nova ordem salva com sucesso!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar a ordem no banco de dados.']);
    }
    exit();
}

// AÇÃO PARA BUSCAR A LISTA DE UMA ABA
if ($action == 'get_list') {
    $type = $_GET['type'] ?? 'streams';
    if (!in_array($type, ['streams', 'movie', 'series'])) {
        $type = 'streams';
    }

    $stmt = $pdo->prepare("SELECT `id`, `nome` FROM `categoria` WHERE `type` = ? ORDER BY `position` ASC");
    $stmt->execute([$type]);
    $categorias = $stmt->fetchAll();

    // Retorna apenas o HTML dos itens da lista
    foreach ($categorias as $cat) {
        echo '<li data-id="' . $cat['id'] . '"><span class="drag-handle"></span>' . htmlspecialchars($cat['nome']) . '</li>';
    }
    exit();
}

// --- 4. BUSCA OS DADOS INICIAIS (para a primeira aba 'Canais') ---
$categorias_iniciais = $pdo->query("SELECT `id`, `nome` FROM `categoria` WHERE `type` = 'streams' ORDER BY `position` ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizador de Categorias por Abas</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #ffffff; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #0056b3; }
        .tabs { margin-bottom: 20px; display: flex; border-bottom: 2px solid #ccc; }
        .tab-button { background: #e9e9e9; border: 1px solid #ccc; border-bottom: none; padding: 10px 20px; cursor: pointer; font-size: 16px; border-radius: 5px 5px 0 0; margin-right: 5px; }
        .tab-button.active { background: #fff; border-bottom: 2px solid #fff; position: relative; top: 2px; font-weight: bold; }
        #sortable-list { list-style: none; padding: 0; margin: 0; min-height: 100px; border: 1px solid #eee; padding: 10px; border-radius: 4px; }
        #sortable-list li { cursor: move; padding: 15px; margin-bottom: 5px; border: 1px solid #ddd; background-color: #fff; border-radius: 4px; display: flex; align-items: center; }
        .drag-handle { display: inline-block; width: 20px; height: 20px; background-color: #ccc; margin-right: 15px; border-radius: 3px; cursor: grab; }
        .ui-sortable-placeholder { border: 2px dashed #ccc; background-color: #f0f8ff; height: 50px; visibility: visible !important; }
        .save-button-container { text-align: center; margin-top: 20px; }
        #saveButton { font-size: 16px; font-weight: bold; padding: 12px 25px; color: #fff; background-color: #28a745; border: none; border-radius: 5px; cursor: pointer; }
        #saveButton:hover { background-color: #218838; }
        #saveButton:disabled { background-color: #aaa; cursor: not-allowed; }
        #message { text-align: center; font-weight: bold; margin-top: 15px; padding: 10px; border-radius: 5px; display: none; }
        #message.success { color: #155724; background-color: #d4edda; }
        #message.error { color: #721c24; background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Organizador de Categorias</h1>
        <div class="tabs">
            <button class="tab-button active" data-type="streams">Canais</button>
            <button class="tab-button" data-type="movie">Filmes</button>
            <button class="tab-button" data-type="series">Séries</button>
        </div>
        <ul id="sortable-list">
            <?php foreach ($categorias_iniciais as $cat): ?>
                <li data-id="<?php echo $cat['id']; ?>"><span class="drag-handle"></span><?php echo htmlspecialchars($cat['nome']); ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="save-button-container">
            <button id="saveButton">Salvar Ordem da Lista Atual</button>
        </div>
        <div id="message"></div>
    </div>

<script>
$(document).ready(function() {
    // Função para tornar a lista organizável
    function makeSortable() {
        $("#sortable-list").sortable({
            placeholder: "ui-sortable-placeholder"
        });
    }

    // Ativa a funcionalidade na lista inicial
    makeSortable();

    // LÓGICA DAS ABAS
    $('.tab-button').on('click', function() {
        var btn = $(this);
        var type = btn.data('type');
        $('.tab-button').removeClass('active');
        btn.addClass('active');
        $('#sortable-list').html('<li>Carregando...</li>');
        $('#message').hide();
        $.ajax({
            url: 'organizar_tudo.php', type: 'GET', data: { action: 'get_list', type: type },
            success: function(responseHtml) {
                $('#sortable-list').html(responseHtml);
                makeSortable();
            },
            error: function() { $('#sortable-list').html('<li><span style="color: red;">Erro ao carregar a lista.</span></li>'); }
        });
    });

    // LÓGICA DO BOTÃO SALVAR
    $('#saveButton').on('click', function() {
        var btn = $(this);
        var messageDiv = $('#message');
        btn.prop('disabled', true).text('Salvando...');
        messageDiv.hide();
        var order = $('#sortable-list li').map(function() { return $(this).data('id'); }).get();
        
        $.ajax({
            url: 'organizar_tudo.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_order',
                // CORREÇÃO FINAL APLICADA AQUI: Enviando os dados como uma string JSON
                order: JSON.stringify(order) 
            },
            success: function(response) {
                if (response.status === 'success') {
                    messageDiv.text(response.message).removeClass('error').addClass('success').fadeIn();
                } else {
                    messageDiv.text(response.message || 'Erro desconhecido.').removeClass('success').addClass('error').fadeIn();
                }
            },
            error: function() {
                messageDiv.text('Erro de comunicação.').removeClass('success').addClass('error').fadeIn();
            },
            complete: function() {
                setTimeout(function() {
                    btn.prop('disabled', false).text('Salvar Ordem da Lista Atual');
                    messageDiv.fadeOut();
                }, 2000);
            }
        });
    });
});
</script>

</body>
</html>