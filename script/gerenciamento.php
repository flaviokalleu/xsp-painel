<?php
// Inclua o cabeçalho do seu painel. 
// O caminho pode variar dependendo da estrutura de pastas do seu painel.
require_once 'menu.php'; 
?>

<div class="page-title">
    <h3>Gerenciamento em Massa</h3>
    <p>Ferramentas para gerenciamento de conteúdo em grande escala.</p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body" style="padding: 0; height: 85vh;">
                <iframe 
                    src="/admin/index.php" 
                    width="100%" 
                    height="100%" 
                    style="border:none;">
                </iframe>
            </div>
        </div>
    </div>
</div>

<?php
// Inclua o rodapé do seu painel.
require_once 'menu.php'; 
?>