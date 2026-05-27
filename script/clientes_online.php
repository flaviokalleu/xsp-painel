<?php
// Adiciona o menu do seu painel.
require_once("menu.php");

// =======================================================
// CONTROLE DE ACESSO: admin (nivel 1) vê tudo; revendedor (nivel 0) vê só os seus.
// Qualquer outro nível é bloqueado.
// =======================================================
if (!isset($_SESSION['nivel_admin']) || ($_SESSION['nivel_admin'] != 1 && $_SESSION['nivel_admin'] != 0)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8">
<title>Acesso Proibido</title></head><body style="background:#000;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;">
<div style="text-align:center;border:2px solid #dc3545;padding:30px 50px;border-radius:10px;">
<h2 style="color:#ff416e;">ACESSO RESTRITO (403)</h2>
<p>Você não possui permissão para visualizar esta página.</p>
<a href="dashboard.php" style="color:#4CAF50;">Voltar ao painel</a>
</div></body></html>';
    exit;
}

// Identifica o nível de acesso para uso nos includes da API
$_SESSION['dashboard_filter_by_admin'] = ($_SESSION['nivel_admin'] == 0);
// =======================================================
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard de Usuários Online</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Lê o tema (claro ou escuro) salvo pelo painel principal e o aplica.
(function() {
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);
})();
</script>
<style>
/* Variáveis de Tema (para funcionar com seu painel) */
:root {
--bg-main: #f0f2f5; --bg-card: #ffffff; --text-primary: #212529;
--text-secondary: #6c757d; --border-color: #dee2e6; --header-bg: #4a69bd;
--header-text: #ffffff; --icon-bg: #5d78ff; --icon-text: #ffffff;
--icon-bg-secondary: #ffc107; /* Cor secundária para o novo cartão */
}
[data-theme="dark"] {
--bg-main: #16191c; --bg-card: #2a2e33; --text-primary: #e4e6eb;
--text-secondary: #b0b3b8; --border-color: #3a3f44;
}

/* Estilos Gerais (Omitidos para brevidade, mas mantidos no código) */
body {
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
background-color: var(--bg-main);
margin: 0;
color: var(--text-primary);
}
.main-container { padding: 20px; }

/* Modificação para o cabeçalho: adiciona flexbox */
.main-header {
background-color: var(--header-bg);
color: var(--header-text);
padding: 15px 20px;
display: flex; /* Ativa o flexbox */
justify-content: space-between; /* Espaça título e botões */
align-items: center; /* Centraliza verticalmente */
}

/* Estilo para o Botão "Voltar ao Início" (mantido apenas por precaução, mas o elemento foi removido) */
.btn-inicio {
background-color: #28a745; /* Cor verde para destaque */
color: white;
padding: 8px 15px;
text-decoration: none;
border-radius: 5px;
font-size: 16px;
font-weight: 500;
transition: background-color 0.2s;
margin-left: 10px;
}
.btn-inicio:hover {
background-color: #218838;
}

/* NOVO ESTILO: Botão Derrubar Tudo (cor de alerta) */
.btn-kick-all {
background-color: #dc3545; /* Vermelho forte */
color: white;
padding: 8px 15px;
text-decoration: none;
border: none;
border-radius: 5px;
font-size: 16px;
font-weight: 500;
cursor: pointer;
transition: background-color 0.2s;
}
.btn-kick-all:hover {
background-color: #c82333;
}
/* Layout dos botões no cabeçalho */
.header-actions {
display: flex;
gap: 10px;
}

/* Layout para os cartões */
.cards-row {
display: flex;
gap: 20px;
flex-wrap: wrap;
margin-bottom: 20px;
}
.card {
background-color: var(--bg-card);
border: 1px solid var(--border-color);
border-radius: 8px;
box-shadow: 0 4px 8px rgba(0,0,0,0.05);
padding: 25px;
display: flex;
align-items: center;
gap: 20px;
flex: 1;
min-width: 250px;
}
.card .icon {
font-size: 32px; width: 60px; height: 60px; border-radius: 50%;
display: flex; align-items: center; justify-content: center;
color: var(--icon-text);
background-color: var(--icon-bg);
}
.card.total-connections .icon {
background-color: var(--icon-bg-secondary);
}

.card .info h3 { margin: 0; font-size: 16px; color: var(--text-secondary); font-weight: 500; }
.card .info p { margin: 5px 0 0; font-size: 28px; font-weight: 600; color: var(--text-primary); }
.table-container {
background-color: var(--bg-card);
border: 1px solid var(--border-color);
border-radius: 8px;
box-shadow: 0 4px 8px rgba(0,0,0,0.05);
padding: 20px;
overflow-x: auto; /* Garante responsividade da tabela */
}
.activity-table { width: 100%; border-collapse: collapse; }
.activity-table th, .activity-table td {
padding: 12px 15px; text-align: left;
border-bottom: 1px solid var(--border-color); vertical-align: middle;
}
.activity-table th { font-weight: 600; color: var(--text-secondary); }
.status-online {
background-color: #28a745; color: white; padding: 4px 10px;
border-radius: 12px; font-size: 12px; font-weight: 600;
display: inline-block;
}

/* Estilo do Botão de Ação (Kick Session) */
.btn-kick-session {
background: #dc3545; /* Vermelho */
color: white;
border: none;
padding: 5px 8px;
border-radius: 50%; /* Faz um círculo */
width: 30px;
height: 30px;
cursor: pointer;
display: flex;
align-items: center;
justify-content: center;
transition: background-color 0.2s;
font-size: 14px;
}
.btn-kick-session:hover {
background-color: #c82333;
}

/* Responsividade para celular */
@media (max-width: 768px) {
/* Ajusta o cabeçalho no celular para empilhar os botões */
.main-header {
flex-direction: column;
align-items: flex-start;
}
.header-actions {
margin-top: 10px;
width: 100%;
justify-content: space-between;
}
.cards-row { flex-direction: column; }
.card { max-width: 100%; }
.main-header { font-size: 18px; }
}
</style>
</head>
<body>

<div class="main-header">
<span style="font-size: 20px; font-weight: 500;">Monitor de Usuários Online
<?php if ($_SESSION['nivel_admin'] == 0): ?>
<small style="font-size:13px;font-weight:400;opacity:.8;">(Meus Clientes)</small>
<?php endif; ?>
</span>
<div class="header-actions">
<button id="kickAllButton" class="btn-kick-all">Derrubar Tudo</button>
</div>
</div>

<div class="main-container">
<div class="cards-row">
<div class="card">
<div class="icon"><i class="fas fa-users"></i></div>
<div class="info">
<h3>Total de Conexões Online</h3>
<p id="total-online">0</p>
</div>
</div>

<div class="card total-connections">
<div class="icon" style="background-color: var(--icon-bg-secondary);"><i class="fas fa-tv"></i></div>
<div class="info">
<h3>Clientes com Múltiplas Conexões</h3>
<p id="clientes-multiplas-conexoes">0</p>
</div>
</div>
</div>

<div class="table-container">
<h3>Detalhes das Conexões Ativas</h3>
<table class="activity-table">
<thead>
<tr>
<th>Usuário</th>
<th>Endereço IP</th>
<th>Canal Atual 📺</th>
<th>Conexões Ativas</th>
<th>Última Atividade</th>
<th>Status</th>
<th>Ação</th> </tr>
</thead>
<tbody id="tabela-atividade"></tbody>
</table>
</div>
</div>

<script>
// Função principal para buscar e exibir dados
async function atualizarDados() {
try {
const response = await fetch('api/api_dashboard.php');

const contentType = response.headers.get("content-type");
if (!contentType || !contentType.includes("application/json")) {
const errorText = await response.text();
throw new Error("Resposta inesperada do servidor. O PHP pode ter erros. Conteúdo: " + errorText.substring(0, 100) + '...');
}

const data = await response.json();

if (data.error) throw new Error('API: ' + data.error);

// 1. Atualiza os contadores
document.getElementById('total-online').innerText = data.online_count || 0;
document.getElementById('clientes-multiplas-conexoes').innerText = data.multi_connection_count || 0;

const tabelaCorpo = document.getElementById('tabela-atividade');
tabelaCorpo.innerHTML = '';

const activity = Array.isArray(data.activity) ? data.activity : [];

// 2. Itera sobre os dados e cria as linhas da tabela
activity.forEach(item => {
const sessionId = item.id;

let nomeCanal = item.canal_atual || 'N/A';

const statusInativos = ["Menu Principal", "Nenhum canal ativo", "N/A", "Menu/Offline", "menu", "offline", ""];

if (!statusInativos.includes(nomeCanal)) {
nomeCanal = `<strong>${nomeCanal}</strong>`;
}

const conexoes = item.conexoes_total || 1;

const linha = `
<tr data-session-id="${sessionId}">
<td>${item.usuario}</td>
<td>${item.ip}</td>
<td>${nomeCanal}</td>
<td>${conexoes}</td>
<td>${item.ultima_atividade}</td>
<td><span class="status-online">Online</span></td>
<td>
<button
class="btn-kick-session"
data-id="${sessionId}"
title="Derrubar Sessão (Revólver)"
>
<i class="fas fa-power-off"></i>
</button>
</td>
</tr>
`;
tabelaCorpo.innerHTML += linha;
});

} catch (error) {
console.error("Erro ao buscar dados:", error);
document.getElementById('tabela-atividade').innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc3545; font-weight: bold; padding: 20px;">ERRO: ${error.message}</td></tr>`;
}
}

// ==========================================================
// LÓGICA DO BOTÃO DE DERRUBAR SESSÃO INDIVIDUAL (AGORA COM SWEETALERT2)
// ==========================================================
document.addEventListener('click', async function(event) {
if (event.target.closest('.btn-kick-session')) {
const button = event.target.closest('.btn-kick-session');
const sessionId = button.dataset.id;
const userName = button.closest('tr').children[0].innerText;

// 2. SweetAlert2 para confirmação
const result = await Swal.fire({
    title: 'Confirmar Derrubada?',
    html: `Tem certeza que deseja encerrar a sessão **${sessionId}** do usuário **${userName}**?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545', // Vermelho para a ação de derrubar
    cancelButtonColor: '#6c757d',  // Cinza para cancelar
    confirmButtonText: '<i class="fas fa-power-off"></i> Sim, Derrubar!',
    cancelButtonText: 'Cancelar'
});

// 3. Verifica se o usuário confirmou
if (result.isConfirmed) {
    try {
        const response = await fetch(`api/kick_session.php?session_id=${sessionId}`);
        const result_api = await response.json();

        if (result_api.success) {
            // 4. SweetAlert2 para sucesso
            Swal.fire({
                title: 'Sucesso!',
                text: `Sessão ID ${sessionId} derrubada.`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            // Remove a linha da tabela e atualiza os contadores
            button.closest('tr').remove();
            atualizarDados();
        } else {
            // 5. SweetAlert2 para erro
            Swal.fire('Erro!', 'Erro ao derrubar sessão: ' + (result_api.message || 'Erro desconhecido.'), 'error');
        }
    } catch (error) {
        console.error("Erro na chamada de derrubar sessão:", error);
        Swal.fire('Erro de Comunicação', 'Erro ao conectar ao servidor. Verifique o kick_session.php.', 'error');
    }
}
}
});

// ==========================================================
// LÓGICA DO BOTÃO DE DERRUBAR TODAS AS SESSÕES (AGORA COM SWEETALERT2)
// ==========================================================
document.getElementById('kickAllButton').addEventListener('click', async function() {
const result = await Swal.fire({
    title: 'ATENÇÃO CRÍTICA!',
    html: 'Você está prestes a derrubar **TODAS** as sessões ativas. Isso afetará todos os clientes. **Tem certeza?**',
    icon: 'error', // Icone de erro/perigo
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="fas fa-fire-extinguisher"></i> Sim, Derrubar TUDO!',
    cancelButtonText: 'Cancelar'
});

if (result.isConfirmed) {
    try {
        const response = await fetch('api/kick_all_sessions.php');
        const result_api = await response.json();

        if (result_api.success) {
             Swal.fire({
                title: 'SUCESSO TOTAL!',
                text: result_api.message || 'Todas as sessões foram derrubadas com sucesso!',
                icon: 'success'
            });
            atualizarDados();
        } else {
            Swal.fire('Erro Grave', 'Erro ao derrubar todas as sessões: ' + (result_api.message || 'Erro desconhecido.'), 'error');
        }
    } catch (error) {
        console.error("Erro na chamada de derrubar todas as sessões:", error);
        Swal.fire('Erro de Comunicação', 'Erro ao conectar ao servidor. Verifique se o arquivo api/kick_all_sessions.php existe.', 'error');
    }
}
});

atualizarDados();
// Atualiza a cada 5 segundos
setInterval(atualizarDados, 5000);
</script>

</body>
</html>