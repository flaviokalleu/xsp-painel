<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) { die("Acesso negado."); }
require_once("menu.php");

$template_file = 'template_mensagem.txt';
$feedback = '';

// Lógica para salvar o novo template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_content'])) {
    if (file_put_contents($template_file, $_POST['template_content']) !== false) {
        $feedback = '<div class="alert alert-success mt-3">Template salvo com sucesso!</div>';
    } else {
        $feedback = '<div class="alert alert-danger mt-3">Erro ao salvar o template. Verifique as permissões de escrita do arquivo.</div>';
    }
}

// Carrega o conteúdo atual do template, ou um padrão se não existir
$template_content = file_exists($template_file) ? file_get_contents($template_file) : "Seu Acesso foi criado com sucesso!\n\n✅ *Usuário:* #username#\n✅ *Senha:* #password#\n🟠 *URL/Port:* #url#\n🗓 *Valido até:* #exp_date#\n\n--------------------------------------\n\n🟢 *Link M3U:* #m3u_link#\n";
?>

<style>
    .variable-table td {
        padding: 7px;
        border: 1px solid var(--border-color);
    }
    .variable {
        font-family: monospace;
        background-color: rgba(13, 110, 253, 0.1);
        color: #ac1700;
        padding: 2px 5px;
        border-radius: 4px;
        cursor: pointer;
        user-select: all;
    }
    [data-theme="dark"] .variable {
        background-color: rgba(13, 110, 253, 0.3);
    }
</style>

<h4 class="mb-4 text-muted text-uppercase">Editor de Template de Mensagem Rápida</h4>
<?php echo $feedback; ?>
<div class="card">
    <div class="card-body">
        <form method="POST" action="editar_template.php">
            <div class="row">
                <div class="col-md-7">
                    <h5>Template mensagem rápida IPTV</h5>
                    <p class="text-muted">Edite o texto abaixo. Clique nas variáveis à direita para inseri-las facilmente no template.</p>
                    <textarea name="template_content" id="template_content" class="form-control" rows="20" style="background-color: var(--bg-card); color: var(--text-primary);"><?php echo htmlspecialchars($template_content); ?></textarea>
                </div>
                <div class="col-md-5">
                    <h5>Variáveis para Substituição <i class="fas fa-question-circle" title="Clique em uma variável para inseri-la no cursor do texto"></i></h5>
                    <p class="text-muted">Use as variáveis abaixo para inserir as informações do cliente.</p>
                    <table class="table" style="color: var(--text-primary);">
                        <thead>
                            <tr>
                                <th>Variável</th>
                                <th>Informação Inserida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><span class="variable">#username#</span></td><td>Usuário do cliente criado</td></tr>
                            <tr><td><span class="variable">#password#</span></td><td>Senha do cliente criado</td></tr>
                            <tr><td><span class="variable">#url#</span></td><td>A URL do seu portal (ex: http://exemplo.com)</td></tr>
                            <tr><td><span class="variable">#exp_date#</span></td><td>Data de validade do acesso (formato: dd-mm-aaaa)</td></tr>
                            <tr><td><span class="variable">#m3u_link#</span></td><td>Link M3U completo (com output=ts)</td></tr>
                            <tr><td><span class="variable">#m3u_link_hls#</span></td><td>Link M3U HLS completo (com output=m3u8)</td></tr>
                            <tr><td><span class="variable">#m3u_encurtado#</span></td><td>Link M3U encurtado</td></tr>
                            <tr><td><span class="variable">#m3u_hls_encurtado#</span></td><td>Link M3U HLS encurtado</td></tr>
                            <tr><td><span class="variable">#ssiptv_encurtado#</span></td><td>Link SSIPTV encurtado</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3 text-center">
                <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>Salvar Template</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.variable').on('click', function() {
        const variableText = $(this).text();
        const textarea = $('#template_content');
        const cursorPos = textarea.prop('selectionStart');
        const text = textarea.val();
        const textBefore = text.substring(0, cursorPos);
        const textAfter = text.substring(cursorPos, text.length);
        
        textarea.val(textBefore + variableText + textAfter);
        textarea.focus();
        textarea.prop('selectionEnd', cursorPos + variableText.length);
    });
});
</script>