<?php
session_start();
if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) { die("Acesso negado."); }
require_once("menu.php");
require_once("./api/controles/db.php");
$conexao = conectar_bd();

$upload_dir_icones = 'uploads/icones/';
$upload_dir_apks = 'uploads/apks/';
$feedback = '';

// Lógica para Adicionar ou Editar um App
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_app'])) {
    $id = intval($_POST['app_id'] ?? 0);
    $nome = trim($_POST['app_nome'] ?? '');
    $codigo = trim($_POST['app_codigo_downloader'] ?? '');
    $icone_atual = $_POST['icone_atual'] ?? '';
    $link_atual = $_POST['link_atual'] ?? '';
    $caminho_icone = $icone_atual;
    $link_final_do_app = '';
    
    // Lida com o upload do ÍCONE
    if (!empty($_FILES['app_icone']['name'])) {
        $nome_arquivo_icone = time() . '_' . basename($_FILES['app_icone']['name']);
        if (move_uploaded_file($_FILES['app_icone']['tmp_name'], $upload_dir_icones . $nome_arquivo_icone)) {
            if ($icone_atual && file_exists($icone_atual)) { unlink($icone_atual); }
            $caminho_icone = $upload_dir_icones . $nome_arquivo_icone;
        }
    }

    // Lida com o LINK ou UPLOAD do APK
    $upload_option = $_POST['upload_option'] ?? 'link';

    if ($upload_option === 'upload' && !empty($_FILES['app_arquivo_apk']['name'])) {
        $nome_arquivo_apk = time() . '_' . basename($_FILES['app_arquivo_apk']['name']);
        if (move_uploaded_file($_FILES['app_arquivo_apk']['tmp_name'], $upload_dir_apks . $nome_arquivo_apk)) {
            // Se existia um link local antigo, apaga o arquivo
            if ($link_atual && strpos($link_atual, $upload_dir_apks) !== false && file_exists($link_atual)) {
                unlink($link_atual);
            }
            // Define o link final como o caminho para o novo arquivo no servidor
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $link_final_do_app = $protocol . $_SERVER['HTTP_HOST'] . '/' . $upload_dir_apks . $nome_arquivo_apk;
        }
    } else {
        // Se a opção era usar link, pega o valor do campo de texto
        $link_final_do_app = trim($_POST['app_link_download'] ?? '');
    }

    if ($id > 0) { // Update
        $stmt = $conexao->prepare("UPDATE loja_apps SET app_nome=?, app_icone=?, app_link_download=?, app_codigo_downloader=? WHERE id=?");
        $stmt->execute([$nome, $caminho_icone, $link_final_do_app, $codigo, $id]);
        $feedback = '<div class="alert alert-success mt-3">Aplicativo atualizado com sucesso!</div>';
    } else { // Insert
        $stmt = $conexao->prepare("INSERT INTO loja_apps (app_nome, app_icone, app_link_download, app_codigo_downloader) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $caminho_icone, $link_final_do_app, $codigo]);
        $feedback = '<div class="alert alert-success mt-3">Aplicativo adicionado com sucesso!</div>';
    }
}

// Lógica para Apagar um App
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conexao->prepare("SELECT app_icone, app_link_download FROM loja_apps WHERE id = ?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();
    if ($app) {
        // Apaga o ícone
        if (!empty($app['app_icone']) && file_exists($app['app_icone'])) { unlink($app['app_icone']); }
        // Apaga o APK se for local
        if (!empty($app['app_link_download']) && strpos($app['app_link_download'], $_SERVER['HTTP_HOST']) !== false) {
            $local_path = str_replace(((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . '/', '', $app['app_link_download']);
            if (file_exists($local_path)) { unlink($local_path); }
        }
    }
    $conexao->prepare("DELETE FROM loja_apps WHERE id = ?")->execute([$id]);
    $feedback = '<div class="alert alert-danger mt-3">Aplicativo apagado com sucesso!</div>';
    echo '<script>window.location.href="admin_loja.php";</script>';
    exit();
}

$apps = $conexao->query("SELECT * FROM loja_apps ORDER BY app_nome ASC")->fetchAll();
?>
<h4 class="mb-4 text-muted text-uppercase">Gerenciar Loja de Aplicativos</h4>
<?php echo $feedback; ?>
<div class="card mb-4">
    <div class="card-header fw-bold" style="color: var(--text-primary);">Adicionar / Editar Aplicativo</div>
    <div class="card-body">
        <form method="POST" action="admin_loja.php" enctype="multipart/form-data">
            <input type="hidden" name="salvar_app" value="1">
            <input type="hidden" id="app_id" name="app_id">
            <input type="hidden" id="icone_atual" name="icone_atual">
            <input type="hidden" id="link_atual" name="link_atual">

            <div class="mb-3">
                <label for="app_nome" class="form-label">Nome do Aplicativo:</label>
                <input type="text" id="app_nome" name="app_nome" class="form-control" required style="background-color: var(--bg-card); color: var(--text-primary);">
            </div>
            <div class="mb-3">
                <label for="app_icone" class="form-label">Ícone do App (PNG, JPG): <span id="icone-preview"></span></label>
                <input type="file" id="app_icone" name="app_icone" class="form-control" accept="image/png, image/jpeg" style="background-color: var(--bg-card); color: var(--text-primary);">
            </div>

            <div class="mb-3">
                <label class="form-label">Origem do Aplicativo:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="upload_option" id="option_link" value="link" checked>
                    <label class="form-check-label" for="option_link">Usar Link Externo (URL)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="upload_option" id="option_upload" value="upload">
                    <label class="form-check-label" for="option_upload">Fazer Upload de Arquivo (.apk)</label>
                </div>
            </div>

            <div id="link_div" class="mb-3">
                <label for="app_link_download" class="form-label">Link de Download (URL do APK):</label>
                <input type="url" id="app_link_download" name="app_link_download" class="form-control" required placeholder="https://..." style="background-color: var(--bg-card); color: var(--text-primary);">
            </div>
            
            <div id="upload_div" class="mb-3" style="display:none;">
                <label for="app_arquivo_apk" class="form-label">Arquivo do Aplicativo (.apk):</label>
                <input type="file" id="app_arquivo_apk" name="app_arquivo_apk" class="form-control" accept=".apk" style="background-color: var(--bg-card); color: var(--text-primary);">
            </div>
            
            <div class="mb-3">
                <label for="app_codigo_downloader" class="form-label">Código para Downloader:</label>
                <input type="text" id="app_codigo_downloader" name="app_codigo_downloader" class="form-control" style="background-color: var(--bg-card); color: var(--text-primary);">
            </div>

            <button type="submit" id="form_submit_btn" class="btn btn-primary">Adicionar App</button>
            <button type="button" id="form_cancel_btn" class="btn btn-secondary" style="display: none;">Cancelar Edição</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header fw-bold" style="color: var(--text-primary);">Aplicativos Cadastrados</div>
    <div class="card-body table-responsive">
        <table class="table" style="color: var(--text-primary);">
            <thead>
                <tr><th>Ícone</th><th>Nome</th><th>Código Downloader</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach ($apps as $app): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($app['app_icone']); ?>" alt="ícone" style="width: 40px; height: 40px; border-radius: 5px; object-fit: cover;"></td>
                    <td><?php echo htmlspecialchars($app['app_nome']); ?></td>
                    <td><?php echo htmlspecialchars($app['app_codigo_downloader']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $app['id']; ?>" data-nome="<?php echo htmlspecialchars($app['app_nome']); ?>" data-link="<?php echo htmlspecialchars($app['app_link_download']); ?>" data-codigo="<?php echo htmlspecialchars($app['app_codigo_downloader']); ?>" data-icone="<?php echo htmlspecialchars($app['app_icone']); ?>">✏️ Editar</button>
                        <a href="admin_loja.php?delete_id=<?php echo $app['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja apagar este aplicativo?');">🗑️ Apagar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
$(document).ready(function(){
    // Lógica para alternar entre os campos de Link e Upload
    $('input[name="upload_option"]').on('change', function(){
        if (this.value === 'link') {
            $('#link_div').show();
            $('#app_link_download').prop('required', true);
            $('#upload_div').hide();
            $('#app_arquivo_apk').prop('required', false);
        } else {
            $('#link_div').hide();
            $('#app_link_download').prop('required', false);
            $('#upload_div').show();
            $('#app_arquivo_apk').prop('required', true);
        }
    });

    // Lógica para preencher o formulário de edição
    $('.edit-btn').on('click', function(){
        $('#form_submit_btn').text('Salvar Alterações');
        $('#form_cancel_btn').show();
        $('#app_id').val($(this).data('id'));
        $('#icone_atual').val($(this).data('icone'));
        $('#link_atual').val($(this).data('link')); // Guarda o link atual para apagar o arquivo se for o caso
        $('#app_nome').val($(this).data('nome'));
        $('#app_link_download').val($(this).data('link'));
        $('#app_codigo_downloader').val($(this).data('codigo'));

        // Volta a opção para 'link' por padrão na edição
        $('#option_link').prop('checked', true).trigger('change');

        if ($(this).data('icone')) {
            $('#icone-preview').html('<img src="' + $(this).data('icone') + '" style="width: 20px; height: 20px; margin-left: 10px;">');
        }
        window.scrollTo(0, 0);
    });

    // Lógica para cancelar a edição
    $('#form_cancel_btn').on('click', function(){
        $('#form_submit_btn').text('Adicionar App');
        $(this).hide();
        $('#app_id').val('');
        $('#icone_atual').val('');
        $('#link_atual').val('');
        $('#icone-preview').empty();
        $('form').trigger("reset");
        $('#option_link').prop('checked', true).trigger('change');
    });
});
</script>