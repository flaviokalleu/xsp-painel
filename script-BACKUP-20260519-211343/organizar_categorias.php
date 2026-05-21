<?php
require_once("menu.php");
require_once('./api/controles/db_connect.php');
$conexao = conectar_bd();

function get_all_categorias($conexao) {
    $stmt = $conexao->prepare("SELECT id, nome, type FROM categoria ORDER BY position ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$all_categorias = get_all_categorias($conexao);
$categorias_grouped_by_type = [];
foreach ($all_categorias as $cat) {
    if (!isset($categorias_grouped_by_type[$cat['type']])) {
        $categorias_grouped_by_type[$cat['type']] = [];
    }
    $categorias_grouped_by_type[$cat['type']][] = $cat;
}

$categorias_streams = $categorias_grouped_by_type['streams'] ?? [];
$categorias_movies = $categorias_grouped_by_type['movies'] ?? [];
$categorias_series = $categorias_grouped_by_type['series'] ?? [];
?>

<h4 class="align-items-center d-flex mb-4 text-primary text-uppercase">
    <i class="fa-solid fa-list-ol me-2 text-primary"></i> ORGANIZAR CATEGORIAS
</h4>
<div class="card p-4 shadow-sm border-primary">
    <p>Arraste e solte as categorias para organizá-las e depois clique em Salvar.</p>
    <hr class="mb-4">

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="streams-tab" data-bs-toggle="tab" data-bs-target="#streams" type="button" role="tab" aria-controls="streams" aria-selected="true">Streams</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="movies-tab" data-bs-toggle="tab" data-bs-target="#movies" type="button" role="tab" aria-controls="movies" aria-selected="false">Movies</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" type="button" role="tab" aria-controls="series" aria-selected="false">Series</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="streams" role="tabpanel" aria-labelledby="streams-tab">
            <ul id="sortable-streams" class="list-group mt-3">
                <?php foreach ($categorias_streams as $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?php echo $cat['id']; ?>">
                        <span class="fa fa-arrows-alt me-2"></span>
                        <?php echo htmlspecialchars($cat['nome']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="tab-pane fade" id="movies" role="tabpanel" aria-labelledby="movies-tab">
            <ul id="sortable-movies" class="list-group mt-3">
                <?php foreach ($categorias_movies as $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?php echo $cat['id']; ?>">
                        <span class="fa fa-arrows-alt me-2"></span>
                        <?php echo htmlspecialchars($cat['nome']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="tab-pane fade" id="series" role="tabpanel" aria-labelledby="series-tab">
            <ul id="sortable-series" class="list-group mt-3">
                <?php foreach ($categorias_series as $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?php echo $cat['id']; ?>">
                        <span class="fa fa-arrows-alt me-2"></span>
                        <?php echo htmlspecialchars($cat['nome']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <button id="save-order-btn" class="btn btn-primary mt-4">
        <i class="fa-solid fa-save me-2"></i> Salvar Ordem
    </button>
</div>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
<script>
    $(document).ready(function() {
        $("#sortable-streams, #sortable-movies, #sortable-series").sortable({
            placeholder: "ui-state-highlight"
        });

        $("#save-order-btn").on("click", function() {
            const activeTabId = $('button.nav-link.active').data('bs-target');
            const newOrder = $(activeTabId).find('li').map(function() {
                return $(this).data('id');
            }).get();

            let type;
            if (activeTabId === '#streams') type = 'streams';
            if (activeTabId === '#movies') type = 'movies';
            if (activeTabId === '#series') type = 'series';

            if (!type || newOrder.length === 0) {
                Swal.fire('Aviso', 'Não há categorias para salvar nesta aba.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Salvando...',
                text: 'Atualizando a ordem das categorias.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: './api/organizar_categorias.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    type: type,
                    order: newOrder
                },
                success: function(response) {
                    Swal.fire({
                        title: response.status === 'success' ? 'Sucesso!' : 'Erro!',
                        text: response.message,
                        icon: response.status,
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire('Erro de Comunicação!', 'Não foi possível se comunicar com o servidor.', 'error');
                }
            });
        });
    });
</script>

<div class="modal fade" id="modal_master" tabindex="-1" aria-labelledby="modal_master" aria-hidden="true">
    </div>
</body>
</html>