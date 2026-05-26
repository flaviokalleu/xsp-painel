<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once("chatbot_integrado_funcoes.php"); // Inclui as funções do chatbot

// A LÓGICA PHP DEVE VIR ANTES DE QUALQUER HTML
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php"); // Redireciona para o login se não estiver logado
    exit;
}
$admin_id = $_SESSION['admin_id'];
$feedback = '';

// Lógica para processar a EXCLUSÃO de uma regra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_rule') {
    if(!empty($_POST['rule_id_to_delete'])) {
        if (deleteChatBotRule($admin_id, $_POST['rule_id_to_delete'])) {
            // Se a exclusão for bem-sucedida, redireciona para a mesma página com uma mensagem de sucesso
            header("Location: chatbot_regras.php?feedback=success_delete");
            exit;
        } else {
            // Se a exclusão falhar (ex: permissão negada), mostra um erro
            $feedback = "<div class='alert alert-danger'>Erro ao excluir a regra ou permissão negada.</div>";
        }
    }
}

// AGORA CARREGAMOS A PARTE VISUAL
require_once("menu.php");

// Lógica para mostrar mensagens de feedback (após o redirecionamento)
if(isset($_GET['feedback'])) {
    if($_GET['feedback'] == 'success_add') {
        $feedback = "<div class='alert alert-success'>Nova regra criada com sucesso!</div>";
    }
    if($_GET['feedback'] == 'success_edit') {
        $feedback = "<div class='alert alert-info'>Regra atualizada com sucesso!</div>";
    }
    if($_GET['feedback'] == 'success_delete') {
        $feedback = "<div class='alert alert-danger'>Regra excluída com sucesso!</div>";
    }
}

$lista_regras = getAllChatbotRulesByAdmin($admin_id);
$chatbot_url = getChatbotUrl($admin_id);
?>

<!-- CSS PARA O DESIGN PROFISSIONAL (integrado no seu menu.php) -->
<style>
    /* ... (O seu CSS de design aqui) ... */
</style>

<div class="page-content" style="padding: 20px;">
    <h3><i class="fab fa-whatsapp"></i> Chatbot Integrado</h3>
    <p class="text-muted">Sistema de respostas com base no Painel Office 3.8.</p>
    
    <?php echo $feedback; ?>

    <div class="alert alert-info">
        <h4 class="alert-heading"><i class="fas fa-mobile-alt"></i> Configuração do App (Auto Reply)</h4>
        <p>Use a URL abaixo no campo "Server URL" do seu aplicativo de resposta automática.</p>
        <div class="input-group mb-3">
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($chatbot_url); ?>" readonly>
            <button class="btn btn-primary" onclick="xspCopiar('<?php echo htmlspecialchars($chatbot_url); ?>')">Copiar</button>
        </div>
        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#installModal"><i class="fas fa-info-circle"></i> Ver Passo a Passo da Instalação</button>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-secondary">As Suas Regras Cadastradas</h4>
        <a href="chatbot_criar_regra.php" class="btn btn-success"><i class="fas fa-plus"></i> Criar Nova Regra</a>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr><th>ID</th><th>Tipo</th><th>Mensagens (Gatilhos)</th><th>Ação</th><th>Execuções</th><th>Status</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($lista_regras)): ?>
                        <tr><td colspan="7" class="text-center p-4">Nenhuma regra configurada. Clique em "Criar Nova Regra".</td></tr>
                    <?php endif; ?>
                    <?php foreach ($lista_regras as $regra): ?>
                    <tr>
                        <td><?php echo $regra['id']; ?></td>
                        <td><?php echo $regra['rule_type'] == 'equals' ? 'Igual a' : 'Contém'; ?></td>
                        <td>
                            <?php foreach($regra['messages'] as $msg): ?>
                                <span class="badge bg-secondary m-1"><?php echo htmlspecialchars($msg); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo htmlspecialchars($regra['rule_action']); ?></td>
                        <td><?php echo $regra['runs']; ?></td>
                        <td><span class="badge <?php echo $regra['status'] == 1 ? 'bg-success' : 'bg-danger'; ?>"><?php echo $regra['status'] == 1 ? 'Ativo' : 'Inativo'; ?></span></td>
                        <td>
                            <a href="chatbot_editar_regra.php?id=<?php echo $regra['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                            <!-- FORMULÁRIO DE EXCLUSÃO CORRIGIDO -->
                            <form method="POST" action="chatbot_regras.php" style="display:inline;" onsubmit="return confirm('Tem a certeza que deseja excluir esta regra?');">
                                <input type="hidden" name="action" value="delete_rule">
                                <input type="hidden" name="rule_id_to_delete" value="<?php echo $regra['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====================================================== -->
<!-- INÍCIO DO MODAL DE INSTRUÇÕES (AGORA COMPLETO) -->
<!-- ====================================================== -->
<div class="modal fade" id="installModal" tabindex="-1" aria-labelledby="installModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="installModalLabel"><i class="fas fa-mobile-alt"></i> Passo a Passo: Configurando o Auto Reply</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>O sistema foi desenhado para funcionar com o App <strong>Auto Reply</strong>. Siga estes passos (a função de servidor pode ser um recurso Premium).</p>
                <hr>
                <ol>
                    <li><strong>Baixe o App "Auto Reply"</strong> na sua loja de aplicativos.</li>
                    <li><strong>Abra o App</strong> e dê todas as permissões necessárias, especialmente o Acesso a Notificações.</li>
                    <li><strong>Apague as regras padrão</strong> e clique no ícone `+` para adicionar uma nova regra.</li>
                    <li><strong>Selecione o WhatsApp</strong> que você usa (Normal ou Business).</li>
                    <li>Para "Received message pattern" (Mensagem recebida), marque a opção <strong>"All"</strong>.</li>
                    <li>Role a tela e ative a opção <strong>"Connect to own Server"</strong>.</li>
                    <li>No campo "Server URL", cole a <strong>URL do Servidor</strong> que aparece no seu painel.</li>
                    <li>Clique no ícone `✔` para salvar.</li>
                    <li>Na tela principal, ative o chatbot.</li>
                </ol>
                <p><strong>Importante:</strong> Diferente de outros apps, o "Auto Reply" não precisa que você digite `{{json.reply}}`. Ele automaticamente usa a resposta que o servidor envia.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi!</button>
            </div>
        </div>
    </div>
</div>
<!-- ====================================================== -->
<!-- FIM DO MODAL -->
<!-- ====================================================== -->
