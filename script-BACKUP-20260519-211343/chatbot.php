<?php
session_start();
require_once('./api/controles/db.php');
require_once("menu.php");

if (!isset($_SESSION['admin_id'])) {
    echo "<div class='page-content' style='padding: 20px;'>Acesso Negado.</div>";
    exit;
}

$admin_id = $_SESSION['admin_id'];
$feedback = '';
$conn = conectar_bd();

// Lógica de manipulação de POST (Adicionar, Editar, Excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add_edit':
                $gatilho = strtolower(trim($_POST['gatilho']));
                $resposta = trim($_POST['resposta']);
                $ativo = $_POST['ativo'];
                $id = $_POST['id'] ?? null;

                if (empty($gatilho) || empty($resposta)) {
                    $feedback = "<div class='alert alert-warning'>Os campos Gatilho e Resposta são obrigatórios.</div>";
                    break;
                }

                if (empty($id)) {
                    $stmt = $conn->prepare("INSERT INTO chatbot_respostas (admin_id, gatilho, resposta, ativo) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$admin_id, $gatilho, $resposta, $ativo]);
                    $feedback = "<div class='alert alert-success'>Resposta adicionada com sucesso!</div>";
                } else {
                    $stmt = $conn->prepare("UPDATE chatbot_respostas SET gatilho = ?, resposta = ?, ativo = ? WHERE id = ? AND admin_id = ?");
                    $stmt->execute([$gatilho, $resposta, $ativo, $id, $admin_id]);
                    $feedback = "<div class='alert alert-info'>Resposta atualizada com sucesso!</div>";
                }
                break;
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM chatbot_respostas WHERE id = ? AND admin_id = ?");
                $stmt->execute([$id, $admin_id]);
                $feedback = "<div class='alert alert-danger'>Resposta apagada com sucesso!</div>";
                break;
        }
    } catch (PDOException $e) {
        $feedback = "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
    }
}

// Busca a Chave de API (token)
$stmt_key = $conn->prepare("SELECT token FROM admin WHERE id = ?");
$stmt_key->execute([$admin_id]);
$api_key = $stmt_key->fetchColumn();

// Gera a URL do servidor com a Chave de API
$server_url = "https://" . $_SERVER['HTTP_HOST'] . "/api/chatbot_api.php?id_usuario=" . urlencode($api_key);

// Busca as respostas do usuário logado
$respostas = $conn->prepare("SELECT * FROM chatbot_respostas WHERE admin_id = ? ORDER BY id DESC");
$respostas->execute([$admin_id]);
$lista_respostas = $respostas->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-content" style="padding: 20px;">
    <h3><i class="fab fa-whatsapp"></i> Chatbot (IA)</h3>
    <p>Configure respostas automáticas para o seu WhatsApp.</p>
    
    <?php echo $feedback; ?>

    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#responseModal" onclick="prepareAddModal()">Adicionar Nova Resposta</button>
        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#howToUseModal">Como usar?</button>
    </div>

    <div class="alert alert-secondary">
        <h4>Informações de Conexão</h4>
        <p class="mb-2"><strong>URL para seu App (AutoResponder, etc):</strong></p>
        <div class="input-group">
            <input type="text" id="serverUrlInput" class="form-control" value="<?php echo htmlspecialchars($server_url); ?>" readonly>
            <button class="btn btn-primary" onclick="copyUrl()">Copiar</button>
        </div>
        <small class="mt-2 d-block">Use esta URL no campo "Servidor de Respostas" do seu aplicativo.</small>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Gatilho</th><th>Resposta</th><th>Ativo</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lista_respostas)): ?>
                        <tr><td colspan="5" class="text-center">Nenhuma resposta configurada ainda.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($lista_respostas as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['gatilho']); ?></td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($item['resposta']); ?></td>
                        <td><span class="badge <?php echo $item['ativo'] == 'Sim' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $item['ativo']; ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#responseModal" onclick='prepareEditModal(<?php echo json_encode($item); ?>)'>Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta resposta?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
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

<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_edit">
                <input type="hidden" name="id" id="responseId">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">Adicionar Nova Resposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="gatilho" class="form-label">Gatilho (Mensagem recebida)</label>
                        <input type="text" class="form-control" id="gatilho" name="gatilho" placeholder="Ex: ola" required>
                        <small>Use * para responder a qualquer mensagem.</small>
                    </div>
                    <div class="mb-3">
                        <label for="resposta" class="form-label">Resposta (Mensagem a ser enviada)</label>
                        <textarea class="form-control" id="resposta" name="resposta" rows="4" placeholder="Ex: Olá! Como posso ajudar?" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="ativo" class="form-label">Status</label>
                        <select class="form-select" id="ativo" name="ativo">
                            <option value="Sim">Ativo</option>
                            <option value="Não">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="howToUseModal" tabindex="-1" aria-labelledby="howToUseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="howToUseModalLabel">Como Configurar o Chatbot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Para que o chatbot funcione, você precisa de um aplicativo no seu celular que conecte o WhatsApp à sua URL de servidor. Recomendamos o <strong>AutoResponder for WA</strong>.</p>
                <hr>
                <h5>Passo 1: Instale o App</h5>
                <p>Baixe o "AutoResponder for WA" na Play Store. A função de servidor de respostas pode exigir a versão Premium.</p>

                <h5>Passo 2: Crie uma Regra</h5>
                <ol>
                    <li>Abra o AutoResponder e clique no botão <strong>+</strong>.</li>
                    <li>Para <strong>"Mensagem Recebida"</strong>, coloque <strong>*</strong> para responder a todas as mensagens.</li>
                </ol>

                <h5>Passo 3: Conecte seu Servidor</h5>
                <ol>
                    <li>Na seção <strong>"Mensagem de Resposta"</strong>, clique no ícone de globo 🌐 (Servidor de Respostas).</li>
                    <li>Cole a sua URL do servidor (disponível nesta página) no campo URL.</li>
                    <li>**IMPORTANTE:** Adicione <code>&mensagem=%message%</code> ao final da sua URL. Ficará assim: <br>
                    <code style="font-size: 0.8rem;"><?php echo htmlspecialchars($server_url); ?>&mensagem=%message%</code></li>
                </ol>

                <h5>Passo 4: Configure a Resposta</h5>
                <ol>
                    <li>Volte para a caixa de texto principal da <strong>"Mensagem de Resposta"</strong>.</li>
                    <li>Apague tudo e escreva exatamente: <code>{{json.reply}}</code></li>
                    <li>Isso fará com que o app exiba a resposta vinda do seu servidor.</li>
                </ol>
                <h5>Passo 5: Salve e Teste!</h5>
                <p>Salve a regra clicando no ✔️ e envie uma mensagem para o seu número de outro WhatsApp para testar.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendi!</button>
            </div>
        </div>
    </div>
</div>
<script>
function copyUrl() {
    const urlInput = document.getElementById('serverUrlInput');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // Para mobile
    document.execCommand('copy');
    alert('URL copiada para a área de transferência!');
}

function prepareAddModal() {
    document.getElementById('responseModalLabel').innerText = 'Adicionar Nova Resposta';
    document.getElementById('responseId').value = '';
    document.getElementById('gatilho').value = '';
    document.getElementById('resposta').value = '';
    document.getElementById('ativo').value = 'Sim';
}

function prepareEditModal(item) {
    document.getElementById('responseModalLabel').innerText = 'Editar Resposta';
    document.getElementById('responseId').value = item.id;
    document.getElementById('gatilho').value = item.gatilho;
    document.getElementById('resposta').value = item.resposta;
    document.getElementById('ativo').value = item.ativo;
}
</script>