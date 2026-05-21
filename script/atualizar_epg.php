<?php
/**
 * SISTEMA DE ATUALIZAÇÃO DE EPG v7.4 - SOMENTE POR URL XML
 * * FOCO: Reverte a lógica de log streaming no AJAX POST para forçar a execução em 
 * segundo plano quando o navegador perde a conexão por timeout do servidor.
 * * MANTÉM SOMENTE a lógica completa de Mapeamento e Importação de EPG Data via URL.
 * * REMOVIDA toda a função e campos de UPLOAD de arquivo XML.
 */

// --- LÓGICA DE ATUALIZAÇÃO (EXECUTADA VIA AJAX) ---
if (isset($_GET['start_epg'])) {
    
    // Configurações para processos longos - Crítico para execução em segundo plano
    set_time_limit(0); 
    ignore_user_abort(true); 
    ini_set('memory_limit', '1024M'); 
    
    session_start();
    require_once("./api/controles/db.php");
    
    // URL Padrão do EPG
    $default_epg_url = "http://iptv-epg.org/files/playlist-hcvnly.m3u";
    // Usa a URL enviada via POST/GET, ou a padrão.
    $epg_url = isset($_POST['epg_url']) && !empty($_POST['epg_url']) ? $_POST['epg_url'] : $default_epg_url;
    // Se a requisição for EventSource (GET), o URL pode vir via GET também.
    $epg_url = isset($_GET['epg_url']) && !empty($_GET['epg_url']) ? $_GET['epg_url'] : $epg_url;


    // VARIÁVEIS DE CONTROLE
    $updated_channel_names = []; 
    $epg_data_imported = 0; 
    
    // Funções de ajuda
    function epg_normalize_name($name) {
        $name = strtolower($name);
        $name = str_replace(['hd', 'fhd', 'sd', '4k', '(br)', '(pt)'], '', $name);
        $name = preg_replace('/[^a-z0-9]/', '', $name);
        return $name;
    }
    
    function epg_send_message($type, $data) {
        echo "event: " . $type . "\n";
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) { ob_flush(); }
        flush();
    }

    // Define os cabeçalhos para Server-Sent Events (SSE)
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');

    epg_send_message('log', "**[DEBUG] Conexão iniciada. O processo continua no servidor. Aguarde alguns minutos.**");
    
    try {
        $conexao = conectar_bd();
        $epg_content = false;
        $source_type = 'URL';
        
        // ----------------------------------------------------------------------------------
        // Lógica para carregar o XML via URL (MANTIDA)
        // ----------------------------------------------------------------------------------
        if (!empty($epg_url)) {
            
            epg_send_message('log', "Baixando arquivo EPG de: " . $epg_url . "...");
            $epg_content = @file_get_contents($epg_url);
            
            if ($epg_content !== false && (str_ends_with($epg_url, '.gz') || str_ends_with($epg_url, '.gzip'))) { 
                $epg_content = @gzdecode($epg_content);
            }
        }
        
        if ($epg_content === false) { 
            throw new Exception("Falha ao obter o arquivo EPG. Verifique o link: " . $epg_url); 
        }
        epg_send_message('log', "[OK] EPG obtido com sucesso via **{$source_type}**.");

        epg_send_message('log', 'Analisando o arquivo XML...');
        $xml = @simplexml_load_string($epg_content);
        if ($xml === false) {
             throw new Exception("Falha ao processar o XML. Verifique se o arquivo está bem formado.");
        }
        
        $epg_data = [];
        foreach ($xml->channel as $channel) {
            $epg_data[epg_normalize_name((string)$channel->{'display-name'})] = (string)$channel['id'];
        }
        epg_send_message('log', '[OK] Análise concluída. '. count($epg_data) ." canais encontrados no arquivo EPG.");

        // ----------------------------------------------------------------------------------
        // 1. ATUALIZAÇÃO DO MAPEMANTO DE CANAIS (epg_channel_id) 
        // ----------------------------------------------------------------------------------

        epg_send_message('log', 'Buscando seus canais no banco de dados...');
        $stmt = $conexao->query("SELECT id, name, epg_channel_id FROM streams WHERE stream_type = 'live'");
        $canais_do_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_canais = count($canais_do_banco);
        epg_send_message('log', "[OK] Encontrados $total_canais canais no seu painel.");
        epg_send_message('stats', ['total_canais' => $total_canais]);

        epg_send_message('log', "---------------------------------\nIniciando comparação e mapeamento...");
        
        $encontrados = 0;
        $processados = 0;
        $updates_to_run = [];

        foreach ($canais_do_banco as $canal) {
            $processados++;
            $nome_normalizado_banco = epg_normalize_name($canal['name']);
            
            if (isset($epg_data[$nome_normalizado_banco])) {
                $encontrados++;
                $epg_id = $epg_data[$nome_normalizado_banco];
                if ($canal['epg_channel_id'] !== $epg_id) {
                    $updates_to_run[$epg_id][] = $canal['id'];
                    $updated_channel_names[] = $canal['name']; 
                }
            }
            
            if ($processados % 100 == 0 || $processados == $total_canais) {
                $progresso = ($processados / ($total_canais ?: 1)) * 100;
                epg_send_message('progress', ['percent' => round($progresso), 'text' => "$processados/$total_canais"]);
            }
        }
        
        $atualizados = 0;
        if (!empty($updates_to_run)) {
            epg_send_message('log', "---------------------------------\nAplicando atualizações de mapeamento...");
            $conexao->beginTransaction();
            try {
                foreach ($updates_to_run as $epg_id_to_set => $channel_ids) {
                    $placeholders = implode(',', array_fill(0, count($channel_ids), '?'));
                    $update_stmt = $conexao->prepare("UPDATE streams SET epg_channel_id = ? WHERE id IN ($placeholders)");
                    
                    $params = $channel_ids;
                    array_unshift($params, $epg_id_to_set);
                    
                    $update_stmt->execute($params);
                    $atualizados += $update_stmt->rowCount();
                }
                $conexao->commit();
                epg_send_message('log', "[OK] Todas as {$atualizados} atualizações de mapeamento foram aplicadas com sucesso.");
                epg_send_message('stats', ['canais_atualizados' => $atualizados]);
            } catch (Exception $e) {
                $conexao->rollBack();
                throw new Exception("Falha ao aplicar as atualizações de mapeamento: " . $e->getMessage()); 
            }
        } else {
            epg_send_message('log', "Nenhum canal precisou ser atualizado no mapeamento.");
        }

        // ----------------------------------------------------------------------------------
        // 2. IMPORTAÇÃO DA GRADE DE PROGRAMAÇÃO (epg_data) - ESTRATÉGIA DE INSERÇÃO LENTA
        // ----------------------------------------------------------------------------------

        epg_send_message('log', "---------------------------------\nIniciando importação dos dados de programação (EPG Data)...");
        epg_send_message('log', "ESTRATÉGIA: Usando PREPARED STATEMENT com MICRO-PAUSAS para estabilidade máxima.");
        
        $stmt = $conexao->query("SELECT id, epg_channel_id FROM streams WHERE stream_type = 'live' AND epg_channel_id IS NOT NULL");
        $channel_map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (!empty($channel_map)) {
            
            // 1. Limpeza
            epg_send_message('log', "Limpando a tabela `epg_data` antes de importar os novos dados...");
            $conexao->exec("TRUNCATE TABLE epg_data");
            
            $insert_stmt = $conexao->prepare("
                INSERT INTO epg_data (channel_id, start_time, end_time, title, description) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $count_batch = 0;
            $BATCH_COMMIT_SIZE = 1000; 
            $SLEEP_INTERVAL = 100; 
            $SLEEP_TIME_US = 20000; 

            try {
                $conexao->beginTransaction();
                
                epg_send_message('log', "Lendo eventos, inserindo em micro-lotes (Commit: {$BATCH_COMMIT_SIZE}) com Pausa (Sleep: {$SLEEP_TIME_US}us a cada {$SLEEP_INTERVAL} eventos)...");
                
                foreach ($xml->programme as $programme) {
                    
                    $epg_channel_id_xml = (string)$programme['channel'];
                    if (!isset($channel_map[$epg_channel_id_xml])) {
                        continue; 
                    }
                    
                    $internal_channel_id = $channel_map[$epg_channel_id_xml];
                    
                    if (empty($internal_channel_id) || !is_numeric($internal_channel_id)) {
                        continue; 
                    }
                    
                    $start_dt = @DateTime::createFromFormat('YmdHis O', (string)$programme['start']);
                    $stop_dt = @DateTime::createFromFormat('YmdHis O', (string)$programme['stop']);
                    
                    if ($start_dt && $stop_dt) {
                        $start = $start_dt->format('Y-m-d H:i:s');
                        $stop = $stop_dt->format('Y-m-d H:i:s');
                        
                        $title = (string)$programme->title;
                        $desc = isset($programme->desc) ? (string)$programme->desc : '';

                        // Execução do Prepared Statement
                        $insert_stmt->execute([
                            $internal_channel_id, 
                            $start, 
                            $stop, 
                            $title, 
                            $desc
                        ]);

                        $epg_data_imported++; 
                        $count_batch++;
                        
                        // Executa a micro-pausa
                        if (($epg_data_imported % $SLEEP_INTERVAL) === 0) { 
                            usleep($SLEEP_TIME_US);
                        }
                        
                        // Executa o commit a cada 1000 eventos (Micro-transação)
                        if ($count_batch >= $BATCH_COMMIT_SIZE) { 
                            $conexao->commit();
                            $conexao->beginTransaction();
                            $count_batch = 0;
                        }
                    }
                } // Fim do foreach

                // Commit final para o que restou (Lote final)
                if ($count_batch > 0) {
                    $conexao->commit();
                } else {
                    $conexao->rollBack(); 
                }
                
                epg_send_message('log', "[OK] Importação concluída. Total de eventos inseridos: $epg_data_imported.");
            
            } catch (Exception $e) {
                if ($conexao->inTransaction()) {
                    $conexao->rollBack();
                }
                throw $e; 
            }

        } else {
            epg_send_message('log', "Nenhum canal mapeado, pulando importação da grade de programação.");
        }

        // ----------------------------------------------------------------------------------
        // 3. GERAÇÃO DO HTML DE RESULTADOS FINAIS
        // ----------------------------------------------------------------------------------
        $html_results = '';
            
        if (!empty($updated_channel_names)) {
             $list_html = '';
            $names_to_display = array_slice($updated_channel_names, 0, 50); 
            foreach ($names_to_display as $name) { 
                $list_html .= "<li style='border-bottom: 1px solid #555; padding: 4px 0;'>".htmlspecialchars($name)."</li>";
            }
            
            $html_results .= "<div style='padding: 15px; background: #3c3c3c; border-radius: 5px; margin-top: 20px; color: #fff; font-family: Arial, sans-serif;'>";
            $html_results .= "<h4><i class='fa fa-list'></i> Canais Mapeados/Atualizados</h4>";
            $html_results .= "<p>Total de canais que tiveram o EPG ID atualizado: **" . count($updated_channel_names) . "**</p>";
            if (count($updated_channel_names) > 50) {
                $html_results .= "<p style='font-size: 0.8em; color: #ccc;'>Exibindo os primeiros 50 nomes.</p>";
            }
            $html_results .= "<ul style='list-style-type: none; padding-left: 0; max-height: 200px; overflow-y: scroll; font-size: 0.9em;'>".$list_html."</ul>";
            $html_results .= "</div>";
        }

        $html_results .= "<div style='padding: 15px; background: #28a745; border-radius: 5px; margin-top: 10px; color: white; font-family: Arial, sans-serif;'>";
        $html_results .= "<h4><i class='fa fa-calendar-check-o'></i> Importação da Grade de Programação (EPG Data)</h4>";
        $html_results .= "<p>Total de **eventos de programação** inseridos na tabela `epg_data`: **" . $epg_data_imported . "**</p>"; 
        $html_results .= "<p>O processo de importação foi concluído com sucesso!</p>";
        $html_results .= "</div>";

        
        $taxa_sucesso = ($total_canais > 0) ? round(($encontrados / $total_canais) * 100) : 0;
        $ignorados = $total_canais - $encontrados;
        
        $resumo = "Resumo: Total: $total_canais | Processados: $processados | Encontrados: $encontrados | Atualizados: $atualizados | Inseridos EPG: $epg_data_imported | Ignorados: $ignorados";
        epg_send_message('log', "---------------------------------\nPROCESSO CONCLUÍDO!");
        
        epg_send_message('done', [ 
            'status' => 'Atualizado com sucesso!', 
            'horario' => date('d/m/Y H:i:s'), 
            'resumo_footer' => $resumo, 
            'taxa_sucesso' => $taxa_sucesso,
            'html_output' => $html_results 
        ]);

    } catch (Exception $e) {
        epg_send_message('log', "\n--- OCORREU UM ERRO FATAL ---\n" . $e->getMessage());
        epg_send_message('done', ['status' => 'Falha na atualização (Erro PHP)', 'horario' => date('d/m/Y H:i:s')]);
    }
    
    exit();
}

// --- LÓGICA DE LIMPEZA DE EPG (BOTÃO 'LIMPAR EPG') ---
if (isset($_GET['clear_epg'])) {
    session_start();
    require_once("./api/controles/db.php");
    
    header('Content-Type: application/json');
    
    try {
        $conexao = conectar_bd();
        
        $stmt = $conexao->prepare("UPDATE streams SET epg_channel_id = NULL WHERE stream_type = 'live'");
        $stmt->execute();
        $canais_limpos = $stmt->rowCount();
        
        $conexao->exec("TRUNCATE TABLE epg_data");
        
        echo json_encode([
            'success' => true,
            'message' => "EPG limpo com sucesso! $canais_limpos canais tiveram o EPG_ID removido e a tabela `epg_data` foi limpa.",
            'canais_limpos' => $canais_limpos
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "Falha ao limpar o EPG: " . $e->getMessage()
        ]);
    }
    exit();
}


// --- PÁGINA HTML (SOMENTE COM CAMPO URL) ---
require_once("menu.php");
require_once("./api/controles/db.php");
$conexao = conectar_bd();
$total_canais = $conexao->query("SELECT COUNT(id) FROM streams WHERE stream_type = 'live'")->fetchColumn();
$default_epg_url = "http://iptv-epg.org/files/playlist-hcvnly.m3u"; 
?>

<style>
    .stat-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; text-align: center; }
    .stat-card h3 { font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 10px; font-weight: 600; text-transform: uppercase; }
    .stat-card p { font-size: 2.5rem; font-weight: 700; color: var(--text-primary); margin: 0; }
    .status-bar { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    [data-theme="dark"] .status-bar { background-color: #198754; color: #fff; border-color: #198754; }
    .status-bar.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    [data-theme="dark"] .status-bar.error { background-color: #dc3545; color: #fff; border-color: #dc3545; }
    .log-container { background-color: #212529; color: #f8f9fa; font-family: monospace;
                     padding: 20px; border-radius: 5px; height: 300px; overflow-y: scroll; 
                     white-space: pre-wrap; font-size: 14px; margin-top: 20px; }
    .epg-input-group { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
</style>

<h4 class="mb-4 text-muted text-uppercase">Sistema de EPG (Somente por URL)</h4>

<div class="card mb-3">
    <div class="card-body">
        <div class="row status-bar p-2 rounded align-items-center" id="status-bar">
            <div class="col-md-4"><strong>Status da atualização:</strong> <span id="status-text">Aguardando início</span></div>
            <div class="col-md-4"><strong>Horário da atualização:</strong> <span id="status-horario">--/--/---- --:--:--</span></div>
            <div class="col-md-4"><strong>Atualizado por:</strong> <span id="status-por"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="epg-input-group">
            <label for="epgUrl" class="form-label mb-0" style="min-width: 100px;">**COLOQUE AQUI SEU ARQUIVO EPG**</label>
            <input type="text" class="form-control" id="epgUrl" name="epg_url" placeholder="https://iptv-epg.org/files/playlist-hcvnly.m3u" value="<?php echo htmlspecialchars($default_epg_url); ?>">
        </div>
        <small class="text-danger mt-2">**IMPORTANTE:** Se a página congelar, o processo está rodando em segundo plano no servidor. Você deve aguardar **5 a 10 minutos** e depois recarregar a página para ver o resultado.</small>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <div class="progress" style="height: 30px;">
            <div id="progress-bar" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0%;">Progresso: 0/<?php echo $total_canais; ?> (0%)</div>
        </div>
    </div>
</div>

<div class="row mb-3 g-3">
    <div class="col-md-4">
        <div class="stat-card">
            <h3>Total de Canais</h3>
            <p id="total-canais"><?php echo $total_canais; ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <h3>Canais Atualizados</h3>
            <p id="canais-atualizados">0</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <h3>Uso de Memória</h3>
            <p id="uso-memoria">N/A</p>
        </div>
    </div>
</div>

<div class="text-center mb-3">
    <button id="startBtn" class="btn btn-success btn-lg">Iniciar Atualização do EPG</button>
    <button id="clearBtn" class="btn btn-danger btn-lg ml-3">Limpar EPG</button>
    <a href="/epg_importer.php" class="btn btn-primary btn-lg ml-3">Atualizar EPG 2</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="taxa-sucesso-check" disabled>
            <label class="form-check-label" for="taxa-sucesso-check" id="taxa-sucesso-label">Taxa de sucesso: 0%</label>
        </div>
        <div id="resumo-footer" class="mt-2 text-muted">
            Resumo: Total: <?php echo $total_canais; ?> | Processados: 0 | Encontrados: 0 | Atualizados: 0 | Inseridos EPG: 0 | Ignorados: 0
        </div>
    </div>
</div>

<div class="log-container" id="log-box">Aguardando início do processo...</div>

<script>
$(document).ready(function() {
    const startBtn = $('#startBtn');
    const clearBtn = $('#clearBtn');
    const epgUrlInput = $('#epgUrl');
    const logBox = $('#log-box');
    const progressBar = $('#progress-bar');
    const statusBar = $('#status-bar');
    
    // Função utilitária para codificar parâmetros de URL
    function encodeQueryData(data) {
        const ret = [];
        for (let d in data)
            ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
        return ret.join('&');
    }

    // Função para lidar com o evento 'done' e reativar botões
    function handleDone(data) {
        $('#status-text').text(data.status);
        $('#status-horario').text(data.horario);
        
        if(data.resumo_footer) $('#resumo-footer').text(data.resumo_footer);
        if(data.taxa_sucesso >= 0) {
            $('#taxa-sucesso-label').text('Taxa de sucesso: ' + data.taxa_sucesso + '%');
            $('#taxa-sucesso-check').prop('checked', true);
        }
        if (data.status.includes('Falha') || data.status.includes('Erro')) {
            statusBar.removeClass('status-bar').addClass('error');
        } else {
            statusBar.removeClass('error').addClass('status-bar');
        }
        
        if (data.html_output) {
            logBox.append('\n' + data.html_output + '\n');
            logBox.scrollTop(logBox[0].scrollHeight);
        }
        
        startBtn.prop('disabled', false).text('Atualização Concluída, Recarregar Página');
        startBtn.off('click').on('click', function(){ location.reload(); });
        clearBtn.prop('disabled', false);
        epgUrlInput.prop('disabled', false);
        $('.progress-bar').removeClass('progress-bar-animated');
    }

    // ===================================================================
    // LÓGICA DO BOTÃO INICIAR (START) - SOMENTE POR URL (EventSource)
    // ===================================================================
    startBtn.on('click', function() {
        startBtn.prop('disabled', true).text('Atualizando...');
        clearBtn.prop('disabled', true);
        epgUrlInput.prop('disabled', true);

        logBox.html('Iniciando conexão com o servidor...\n');
        $('.progress-bar').addClass('progress-bar-animated');
        statusBar.removeClass('error').addClass('status-bar');

        const customUrl = epgUrlInput.val();
        
        // Usamos o EventSource (GET) nativo para log em tempo real.
        // Enviamos a URL no GET
        const url_with_params = 'atualizar_epg.php?' + encodeQueryData({start_epg: 'true', epg_url: customUrl});
        const eventSource = new EventSource(url_with_params);

        eventSource.addEventListener('log', function(e) {
            logBox.append(JSON.parse(e.data) + '\n');
            logBox.scrollTop(logBox[0].scrollHeight);
        });

        eventSource.addEventListener('progress', function(e) {
            const data = JSON.parse(e.data);
            progressBar.css('width', data.percent + '%');
            progressBar.text('Progresso: ' + data.text + ' (' + data.percent + '%)');
        });

        eventSource.addEventListener('stats', function(e) {
            const data = JSON.parse(e.data);
            if(data.total_canais) $('#total-canais').text(data.total_canais);
            if(data.canais_atualizados >= 0) $('#canais-atualizados').text(data.canais_atualizados);
        });

        eventSource.addEventListener('done', function(e) {
            handleDone(JSON.parse(e.data));
            eventSource.close();
        });

        eventSource.onerror = function() {
            logBox.append('\n--- ERRO ---\nConexão com o servidor perdida. O processo pode continuar em segundo plano.');
            handleDone({'status': 'Falha na atualização (Erro de Conexão)', 'horario': new Date().toLocaleString('pt-BR')});
            eventSource.close();
        };
    });

    // ===================================================================
    // LÓGICA DO BOTÃO LIMPAR EPG (CLEAR)
    // ===================================================================
    clearBtn.on('click', function() {
        if (!confirm('Tem certeza que deseja limpar as informações de EPG de TODOS os canais E os dados de programação? Esta ação é irreversível.')) {
            return;
        }

        startBtn.prop('disabled', true);
        clearBtn.prop('disabled', true).text('Limpando...');
        epgUrlInput.prop('disabled', true);
        
        logBox.html('Iniciando processo de limpeza de EPG...\n');
        statusBar.removeClass('error').addClass('status-bar');
        $('#status-text').text('Limpando EPG...');
        
        $.ajax({
            url: 'atualizar_epg.php?clear_epg=true',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#status-text').text('EPG Limpo com Sucesso!');
                    $('#status-horario').text(new Date().toLocaleString('pt-BR'));
                    logBox.append('[OK] ' + response.message + '\n');
                    alert(response.message);
                } else {
                    $('#status-text').text('Falha ao Limpar EPG');
                    statusBar.removeClass('status-bar').addClass('error');
                    logBox.append('[ERRO] ' + response.message + '\n');
                    alert('Erro ao limpar EPG: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#status-text').text('Erro de Conexão/Servidor');
                statusBar.removeClass('status-bar').addClass('error');
                logBox.append('\n--- ERRO ---\\nFalha na requisição de limpeza: ' + error + '\n');
                alert('Erro de Conexão/Servidor ao tentar limpar EPG.');
            },
            complete: function() {
                startBtn.prop('disabled', false);
                clearBtn.prop('disabled', false).text('Limpar EPG');
                epgUrlInput.prop('disabled', false);
                logBox.scrollTop(logBox[0].scrollHeight);
            }
        });
    });
});
</script>