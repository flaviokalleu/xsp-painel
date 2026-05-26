<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Garante que os arquivos sejam encontrados, pois agora temos certeza que estão na mesma pasta.
require_once(__DIR__ . "/menu.php");
require_once(__DIR__ . "/chatbot_integrado_funcoes.php");

if (!isset($_SESSION['admin_id'])) {
    // Redireciona ou mostra uma mensagem de erro mais estilizada
    echo "<!DOCTYPE html><html><head><title>Acesso Negado</title><style>body{font-family: sans-serif; background-color: #f1f5f9; color: #333; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;}.message{background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center;} h1{color: #dc3545;}</style></head><body><div class='message'><h1>Acesso Negado</h1><p>Você precisa estar logado como administrador para ver esta página.</p></div></body></html>";
    exit;
}

$admin_id = $_SESSION['admin_id'];
$feedback = '';

// Lógica para Adicionar/Editar/Excluir vinda do Painel Office
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_rule':
                if (empty($_POST['rule_type']) || empty($_POST['rule_action']) || empty($_POST['response']) || empty($_POST['messages'])) {
                    $feedback = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Todos os campos são obrigatórios.</div>";
                    break;
                }
                addChatBotRule($admin_id, $_POST['rule_type'], $_POST['rule_action'], $_POST['response'], $_POST['messages']);
                $feedback = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Nova regra criada com sucesso!</div>";
                break;

            case 'edit_rule':
                if (empty($_POST['rule_id']) || empty($_POST['rule_type']) || empty($_POST['rule_action']) || empty($_POST['response']) || empty($_POST['messages'])) {
                    $feedback = "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Todos os campos são obrigatórios.</div>";
                    break;
                }
                updateChatBotRule($admin_id, $_POST['rule_id'], $_POST['rule_type'], $_POST['rule_action'], $_POST['response'], $_POST['messages']);
                $feedback = "<div class='alert alert-info'><i class='fas fa-info-circle'></i> Regra atualizada com sucesso!</div>";
                break;
        }
    } catch (Exception $e) {
        $feedback = "<div class='alert alert-danger'><i class='fas fa-times-circle'></i> Erro: " . $e->getMessage() . "</div>";
    }
}

$lista_regras = getAllChatbotRulesByAdmin($admin_id);
$chatbot_url = getChatbotUrl($admin_id);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Integrado - Painel Office</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --bg-light: #f1f5f9;
            --bg-white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.1);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .page-content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h3 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h3 .icon-whatsapp {
            font-size: 2.5rem;
            color: #25D366;
        }

        .page-header .text-muted {
            font-size: 1rem;
            color: var(--text-muted);
        }

        .card {
            background-color: var(--bg-white);
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background: none;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .card-body {
             padding: 1.5rem;
        }
        .card-body.p-0 {
            padding: 0;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .alert-info { background-color: #e0f2fe; color: #0c4a6e; }
        .alert-success { background-color: #dcfce7; color: #15803d; }
        .alert-warning { background-color: #fef3c7; color: #b45309; }
        .alert-danger { background-color: #fee2e2; color: #b91c1c; }

        .alert .alert-heading {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 12px 24px;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-sm { padding: 8px 16px; font-size: 0.875rem; }

        .btn-primary { background: linear-gradient(45deg, var(--primary-color), #6366f1); }
        .btn-success { background: linear-gradient(45deg, var(--secondary-color), #34d399); }
        .btn-warning { background: linear-gradient(45deg, var(--warning-color), #fbbf24); }
        .btn-danger { background: linear-gradient(45deg, var(--danger-color), #f87171); }
        .btn-info { background: linear-gradient(45deg, var(--info-color), #60a5fa); }

        .table { width: 100%; border-collapse: collapse; }
        .table thead th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 1rem;
            text-align: left;
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover { background-color: #f8fafc; }

        .message-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            margin: 2px;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .badge-status-active { background-color: var(--secondary-color); color: white; }
        .badge-status-inactive { background-color: var(--text-muted); color: white; }

        .input-group { display: flex; }
        .input-group .form-control {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px 0 0 8px;
            background: #f8fafc;
        }
        .input-group .btn { border-radius: 0 8px 8px 0; }
        
        /* Estilo do Modal (Bootstrap) */
        .modal-header { border-bottom-color: var(--border-color); }
        .modal-footer { border-top-color: var(--border-color); }
        .modal-content { border-radius: 16px; border: none; }
        .modal-title { font-weight: 600; color: var(--text-dark); }
    </style>
</head>
<body>

<div class="page-content">
    <div class="page-header">
        <h3><i class="fab fa-whatsapp icon-whatsapp"></i> Chatbot Integrado</h3>
        <p class="text-muted">Gerencie as regras de resposta automática do seu WhatsApp com o Painel Office Xtream</p>
    </div>
    
    <?php echo $feedback; ?>

    <div class="alert alert-info">
        <h4 class="alert-heading"><i class="fas fa-mobile-alt"></i> Configuração do App (Auto Reply)</h4>
        <p>Use a URL abaixo no campo "Server URL" do seu aplicativo de resposta automática para conectar ao painel.</p>
        <div class="input-group">
            <input type="text" class="form-control" id="chatbotUrlInput" value="<?php echo htmlspecialchars($chatbot_url); ?>" readonly>
            <button class="btn btn-primary" id="copyUrlBtn" onclick="copyUrl()">
                <i class="fas fa-copy"></i>
                <span id="copyBtnText">Copiar</span>
            </button>
        </div>
        <p class="mt-2">
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#installModal"><i class="fas fa-book-open"></i> Ver Passo a Passo da Instalação</button>
        </p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-secondary" style="font-weight:600;">Suas Regras Cadastradas</h4>
        <a href="chatbot_criar_regra.php" class="btn btn-success"><i class="fas fa-plus"></i> Criar Nova Regra</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><i class="fas fa-filter"></i> Tipo</th>
                            <th><i class="fas fa-comment-dots"></i> Mensagens (Gatilhos)</th>
                            <th><i class="fas fa-reply"></i> Ação</th>
                            <th><i class="fas fa-rocket"></i> Execuções</th>
                            <th><i class="fas fa-power-off"></i> Status</th>
                            <th><i class="fas fa-cogs"></i> Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_regras)): ?>
                            <tr><td colspan="7" class="text-center p-5">
                                <i class="fas fa-folder-open fa-2x text-muted mb-2"></i><br>
                                Nenhuma regra configurada.<br> Clique em "Criar Nova Regra" para começar.
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($lista_regras as $regra): ?>
                            <tr>
                                <td><strong>#<?php echo $regra['id']; ?></strong></td>
                                <td><?php echo $regra['rule_type'] == 'equals' ? 'Igual a' : 'Contém'; ?></td>
                                <td>
                                    <?php if (isset($regra['messages']) && is_array($regra['messages'])) : ?>
                                        <?php foreach($regra['messages'] as $msg): ?>
                                            <span class="badge message-badge"><?php echo htmlspecialchars($msg); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($regra['rule_action']); ?>">
                                    <?php echo htmlspecialchars($regra['rule_action']); ?>
                                </td>
                                <td><?php echo $regra['runs']; ?></td>
                                <td><span class="badge-status <?php echo $regra['status'] == 1 ? 'badge-status-active' : 'badge-status-inactive'; ?>"><?php echo $regra['status'] == 1 ? 'Ativo' : 'Inativo'; ?></span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="chatbot_editar_regra.php?id=<?php echo $regra['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-pencil-alt"></i></a>
                                        <form method="POST" action="chatbot_deletar_regra.php" onsubmit="return confirm('Tem certeza que deseja excluir esta regra? Esta ação não pode ser desfeita.');">
                                            <input type="hidden" name="id" value="<?php echo $regra['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="installModal" tabindex="-1" aria-labelledby="installModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="installModalLabel"><i class="fas fa-mobile-alt"></i> Passo a Passo: Configurando o Auto Reply</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>O "Painel Office" recomenda o App <strong>Auto Reply</strong>. Siga estes passos (a função de servidor pode ser um recurso Premium).</p>
                <hr>
                <ol>
                    <li><strong>Baixe o App "Auto Reply"</strong> na sua loja de aplicativos.</li>
                    <li><strong>Abra o App</strong> e dê todas as permissões necessárias.</li>
                    <li><strong>Apague as regras padrão</strong> e clique no ícone <code>+</code> para adicionar uma nova regra.</li>
                    <li><strong>Selecione o WhatsApp</strong> que você usa (Normal ou Business).</li>
                    <li>Para "Received message pattern" (Mensagem recebida), marque a opção <strong>"All"</strong>.</li>
                    <li>Role a tela e ative a opção <strong>"Connect to own Server"</strong>.</li>
                    <li>No campo "Server URL", cole a <strong>URL do Servidor</strong> que aparece no seu painel.</li>
                    <li>Clique no ícone <code>✔</code> para salvar.</li>
                    <li>Na tela principal, ative o chatbot.</li>
                </ol>
                <p><strong>Importante:</strong> Diferente de outros apps, o "Auto Reply" não precisa que você digite <code>{{json.reply}}</code>. Ele automaticamente usa a resposta que o servidor envia.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi!</button>
            </div>
        </div>
    </div>
</div>

<script>
function copyUrl() {
    const urlInput = document.getElementById('chatbotUrlInput');
    const copyBtn = document.getElementById('copyUrlBtn');
    const copyBtnText = document.getElementById('copyBtnText');
    
    // Seleciona e copia o texto
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // Para dispositivos móveis
    navigator.clipboard.writeText(urlInput.value);

    // Feedback visual
    copyBtnText.innerText = 'Copiado!';
    copyBtn.classList.remove('btn-primary');
    copyBtn.classList.add('btn-success');
    
    // Volta ao normal após 2 segundos
    setTimeout(() => {
        copyBtnText.innerText = 'Copiar';
        copyBtn.classList.remove('btn-success');
        copyBtn.classList.add('btn-primary');
    }, 2000);
}
</script>

</body>
</html>