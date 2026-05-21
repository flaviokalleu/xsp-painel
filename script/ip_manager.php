<?php
session_start();
// Apenas Admins podem aceder a esta página
if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) {
    header("Location: dashboard.php"); // Redireciona se não for admin
    exit;
}
require_once("menu.php");
?>

<style>
    .ip-manager-container { padding: 20px; }
    .ip-table-wrapper {
        background-color: var(--bg-card, #fff);
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        border: 1px solid var(--border-color, #dee2e6);
    }
    .ip-table { width: 100%; border-collapse: collapse; }
    .ip-table th, .ip-table td { padding: 12px; border-bottom: 1px solid var(--border-color, #dee2e6); text-align: left; }
    .ip-table th { font-weight: 600; }
    .whitelist-form { display: flex; gap: 10px; margin-top: 10px; }
</style>

<div class="ip-manager-container">
    <h2>Gestor de IPs</h2>
    <p>Gira os IPs bloqueados automaticamente pelo sistema e a sua lista de permissões (whitelist).</p>

    <div class="ip-table-wrapper">
        <h3><i class="fas fa-user-lock"></i> IPs Atualmente Bloqueados</h3>
        <table class="ip-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="check-all-banned"></th>
                    <th>Endereço IP</th>
                    <th>Motivo</th>
                    <th>Data do Bloqueio</th>
                </tr>
            </thead>
            <tbody id="banned-ips-table"></tbody>
        </table>
        
        <div class="block-actions-container mt-3" style="display: flex; justify-content: space-between; align-items: center;">
            
            <div class="block-manual-controls" style="display: flex; gap: 10px; width: 50%;">
                <input type="text" id="manual-block-ip-input" class="form-control" placeholder="Adicionar IP para bloqueio manual">
                <button id="block-manual-btn" class="btn btn-danger" style="min-width: 120px;">Bloquear IP</button>
            </div>

            <button id="unblock-selected-btn" class="btn btn-warning">Desbloquear Selecionados</button>
        </div>
        </div>

    <div class="ip-table-wrapper">
        <h3><i class="fas fa-shield-alt"></i> IPs Permitidos (Whitelist)</h3>
        <p>IPs nesta lista nunca serão bloqueados pelo sistema automático.</p>
        <div class="whitelist-form">
            <input type="text" id="whitelist-ip-input" class="form-control" placeholder="Adicionar IP (ex: 192.168.1.1)">
            <input type="text" id="whitelist-notes-input" class="form-control" placeholder="Notas (opcional)">
            <button id="add-whitelist-btn" class="btn btn-success">Adicionar</button>
        </div>
        <table class="ip-table mt-3">
            <thead>
                <tr>
                    <th>Endereço IP</th>
                    <th>Notas</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody id="allowed-ips-table"></tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const api = 'api/ip_manager.php';
    
    // NOVO: Referências aos novos elementos
    const manualBlockIpInput = document.getElementById('manual-block-ip-input');
    const blockManualBtn = document.getElementById('block-manual-btn');

    async function fetchData(action, body = {}) {
        const response = await fetch(`${api}?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return response.json();
    }

    function renderTables(data) {
        const bannedTable = document.getElementById('banned-ips-table');
        const allowedTable = document.getElementById('allowed-ips-table');
        
        bannedTable.innerHTML = '';
        if (data.banned && data.banned.length > 0) {
            data.banned.forEach(item => {
                bannedTable.innerHTML += `<tr>
                    <td><input type="checkbox" class="banned-ip-check" value="${item.ip_address}"></td>
                    <td>${item.ip_address}</td>
                    <td>${item.reason}</td>
                    <td>${item.ban_timestamp}</td>
                </tr>`;
            });
        } else {
            bannedTable.innerHTML = '<tr><td colspan="4">Nenhum IP bloqueado.</td></tr>';
        }

        allowedTable.innerHTML = '';
        if (data.allowed && data.allowed.length > 0) {
            data.allowed.forEach(item => {
                allowedTable.innerHTML += `<tr>
                    <td>${item.ip_address}</td>
                    <td>${item.notes}</td>
                    <td><button class="btn btn-danger btn-sm remove-allowed-btn" data-ip="${item.ip_address}">Remover</button></td>
                </tr>`;
            });
        } else {
            allowedTable.innerHTML = '<tr><td colspan="3">Nenhum IP na whitelist.</td></tr>';
        }
        
        // Adiciona listeners para os botões de remoção da whitelist após renderizar
        document.querySelectorAll('.remove-allowed-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const ip = e.target.dataset.ip;
                if (confirm(`Tem a certeza que quer remover o IP ${ip} da whitelist?`)) {
                    const result = await fetchData('remove_allowed', { ip });
                    alert(result.message || 'Operação concluída.');
                    loadLists();
                }
            });
        });
    }

    async function loadLists() {
        const data = await fetchData('get_lists');
        renderTables(data);
    }

    document.getElementById('unblock-selected-btn').addEventListener('click', async () => {
        const selectedIps = [...document.querySelectorAll('.banned-ip-check:checked')].map(cb => cb.value);
        if (selectedIps.length > 0) {
            const result = await fetchData('unblock_ips', { ips: selectedIps });
            alert(result.message || 'Operação concluída.');
            loadLists();
        } else {
            alert('Por favor, selecione pelo menos um IP para desbloquear.');
        }
    });

    // NOVO: Listener para o botão Bloquear IP
    blockManualBtn.addEventListener('click', async () => {
        const ip = manualBlockIpInput.value.trim();
        if (ip) {
            if (confirm(`Tem a certeza que quer bloquear o IP ${ip} manualmente?`)) {
                // Ação 'block_ip' é enviada para 'api/ip_manager.php'
                const result = await fetchData('block_ip', { ip, reason: 'Bloqueio Manual' }); 
                alert(result.message || 'Operação de Bloqueio concluída.');
                loadLists();
                manualBlockIpInput.value = ''; // Limpa o campo
            }
        } else {
            alert('Por favor, insira um endereço IP para bloquear.');
        }
    });
    
    document.getElementById('add-whitelist-btn').addEventListener('click', async () => {
        const ip = document.getElementById('whitelist-ip-input').value.trim();
        const notes = document.getElementById('whitelist-notes-input').value.trim();
        if (ip) {
            const result = await fetchData('allow_ip', { ip, notes });
            alert(result.message || 'Operação concluída.');
            loadLists();
            document.getElementById('whitelist-ip-input').value = '';
            document.getElementById('whitelist-notes-input').value = '';
        } else {
            alert('Por favor, insira um endereço IP.');
        }
    });

    document.getElementById('check-all-banned').addEventListener('change', (e) => {
        document.querySelectorAll('.banned-ip-check').forEach(cb => cb.checked = e.target.checked);
    });

    loadLists(); // Carrega as listas quando a página abre
});
</script>