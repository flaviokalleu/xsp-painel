<?php
// ======================================================================
//   GERENCIADOR DE BACKUP E RESTAURAÇÃO v2.2
//   Corrigido: Adicionado desativador de Foreign Key Checks no arquivo
//   de backup para resolver erros de restauração (Error 1451).
// ======================================================================
session_start();
if (empty($_SESSION['logged_in_fxtream'])) {
    header('Location: ./index.php');
    exit();
}

// --- FUNÇÃO PARA LER CREDENCIAIS ---
function get_db_credentials_from_file($filepath) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return null;
    }
    $content = file_get_contents($filepath);
    $credentials = [];
    if (preg_match("/\\\$endereco\s*=\s*'([^']+)';/", $content, $matches)) {
        $credentials['db_host'] = $matches[1];
    }
    if (preg_match("/\\\$banco\s*=\s*'([^']+)';/", $content, $matches)) {
        $credentials['db_name'] = $matches[1];
    }
    if (preg_match("/\\\$dbusuario\s*=\s*'([^']+)';/", $content, $matches)) {
        $credentials['db_user'] = $matches[1];
    }
    if (preg_match("/\\\$dbsenha\s*=\s*'([^']+)';/", $content, $matches)) {
        $credentials['db_pass'] = $matches[1];
    }
    if (count($credentials) === 4) {
        return $credentials;
    }
    return null;
}

// --- CONFIGURAÇÕES ---
$db_config_path = __DIR__ . '/api/controles/db.php';
$credentials = get_db_credentials_from_file($db_config_path);

if ($credentials === null) {
    die("ERRO CRÍTICO: Não foi possível ler as credenciais do arquivo '{$db_config_path}'. Verifique se o caminho está correto, se o arquivo existe e se as variáveis de conexão (\$endereco, \$banco, \$dbusuario, \$dbsenha) estão definidas corretamente dentro dele.");
}

$db_host = $credentials['db_host'];
$db_name = $credentials['db_name'];
$db_user = $credentials['db_user'];
$db_pass = $credentials['db_pass'];

$backup_dir = 'backups/';
$feedback = '';

// --- LÓGICA DE AÇÕES ---

// Ação para FAZER BACKUP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'do_backup') {
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    if (!is_writable($backup_dir)) {
        $feedback = '<div class="alert alert-danger">Erro de Permissão: A pasta de backups não tem permissão de escrita. Altere as permissões (CHMOD) da pasta \'backups/\' para 777.</div>';
    } else {
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;
        
        // --- LÓGICA DE BACKUP COM PHP PURO ---
        try {
            $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if ($mysqli->connect_error) {
                throw new Exception("Falha na conexão com o banco: " . $mysqli->connect_error);
            }
            $mysqli->set_charset('utf8');

            $handle = fopen($filepath, 'w+');
            if ($handle === false) {
                throw new Exception("Não foi possível abrir o arquivo para escrita: " . $filepath);
            }

            // Escreve o cabeçalho do arquivo SQL
            fwrite($handle, "-- Backup gerado via PHP em " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Banco de Dados: `{$db_name}`\n\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSET time_zone = \"+00:00\";\n\n");
            // Desativa a verificação de chaves estrangeiras para evitar erros na restauração
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = [];
            $result = $mysqli->query('SHOW TABLES');
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            $result->free();

            foreach ($tables as $table) {
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                $result = $mysqli->query("SHOW CREATE TABLE `{$table}`");
                $row = $result->fetch_row();
                fwrite($handle, $row[1] . ";\n\n");
                $result->free();

                $result = $mysqli->query("SELECT * FROM `{$table}`");
                $num_fields = $result->field_count;
                
                while ($row = $result->fetch_row()) {
                    fwrite($handle, "INSERT INTO `{$table}` VALUES(");
                    for ($j = 0; $j < $num_fields; $j++) {
                        if (isset($row[$j])) {
                            fwrite($handle, "'" . $mysqli->real_escape_string($row[$j]) . "'");
                        } else {
                            fwrite($handle, 'NULL');
                        }
                        if ($j < ($num_fields - 1)) {
                            fwrite($handle, ',');
                        }
                    }
                    fwrite($handle, ");\n");
                }
                fwrite($handle, "\n");
                $result->free();
            }

            // Reativa a verificação de chaves estrangeiras no final do arquivo
            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");

            fclose($handle);
            $mysqli->close();
            
            $feedback = '<div class="alert alert-success">Backup (via PHP) criado com sucesso: ' . htmlspecialchars($filename) . '</div>';

        } catch (Exception $e) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $feedback = '<div class="alert alert-danger">Falha ao criar o backup (via PHP). Erro: <pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>';
        }
    }
}

// Ação para DELETAR BACKUP
if (isset($_GET['delete'])) {
    $file_to_delete = basename($_GET['delete']);
    $filepath = $backup_dir . $file_to_delete;
    
    if (file_exists($filepath) && strpos(realpath($filepath), realpath($backup_dir)) === 0) {
        if (!unlink($filepath)) {
             $feedback = '<div class="alert alert-danger">Não foi possível apagar o arquivo. Verifique as permissões de escrita na pasta e no arquivo.</div>';
        }
    }
    header("Location: " . basename($_SERVER['PHP_SELF']));
    exit();
}

// Ação para DOWNLOAD SEGURO
if (isset($_GET['download'])) {
    $file_to_download = basename($_GET['download']);
    $filepath = $backup_dir . $file_to_download;

    if (file_exists($filepath) && strpos(realpath($filepath), realpath($backup_dir)) === 0) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
    die("Arquivo não encontrado ou acesso negado.");
}

// Ação para RESTAURAR BACKUP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'do_restore') {
    $file_to_restore = $_POST['backup_file'] ?? '';
    $filepath = $backup_dir . basename($file_to_restore);

    if (file_exists($filepath) && strpos(realpath($filepath), realpath($backup_dir)) === 0) {
        $command = sprintf(
            "mysql --host=%s --user=%s --password=%s %s < %s 2>&1",
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            escapeshellarg($db_pass),
            escapeshellarg($db_name),
            escapeshellarg($filepath)
        );
        
        $output = shell_exec($command);
        
        if (empty($output)) {
            $feedback = '<div class="alert alert-success">Banco de dados restaurado com sucesso a partir de: ' . htmlspecialchars(basename($file_to_restore)) . '</div>';
        } else {
            $feedback = '<div class="alert alert-danger">Falha ao restaurar o backup. O servidor retornou um erro: <pre>' . htmlspecialchars($output) . '</pre></div>';
        }
    } else {
        $feedback = '<div class="alert alert-danger">Arquivo de backup não encontrado para restauração.</div>';
    }
}

$backup_files = glob($backup_dir . '*.sql');
if ($backup_files) {
    rsort($backup_files);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup e Restauração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
    <style>
        body {
            transition: background-color 0.3s, color 0.3s;
        }
        .card {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 12px;
            border: 0;
        }
        .list-group-item {
            transition: background-color 0.2s;
        }
    </style>
</head>
<body class="p-3 p-md-4">
    <div class="d-grid mb-4">
            <a href="/dashboard.php" class="btn btn-success btn-lg" style="background-color: #28a745; border-color: #28a745;">
                <i class="fas fa-home"></i> Voltar ao Início
            </a>
        </div>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h2 class="mb-0"><i class="fas fa-database"></i> Gerenciador de Banco de Dados</h2>
            </div>
            <div class="card-body p-4">
                <?php if ($feedback) echo $feedback; ?>
                <div class="text-center mb-4">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="do_backup">
                        <button type="submit" class="btn btn-lg btn-success"><i class="fas fa-plus-circle"></i> Criar Novo Backup</button>
                    </form>
                </div>
                <h4 class="mb-3">Backups Existentes</h4>
                <div class="list-group">
                    <?php if (empty($backup_files)): ?>
                        <div class="list-group-item">Nenhum backup encontrado.</div>
                    <?php else: foreach ($backup_files as $file): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="text-break">
                                <i class="fas fa-file-alt text-secondary me-2"></i>
                                <strong><?php echo htmlspecialchars(basename($file)); ?></strong>
                                <small class="text-muted ms-2">(<?php echo round(filesize($file) / 1024 / 1024, 2); ?> MB)</small>
                            </div>
                            <div class="btn-group" role="group">
                                <a href="?download=<?php echo urlencode(basename($file)); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Baixar</a>
                                <button class="btn btn-sm btn-outline-warning restore-btn" data-file="<?php echo htmlspecialchars(basename($file)); ?>"><i class="fas fa-history"></i> Restaurar</button>
                                <a href="?delete=<?php echo urlencode(basename($file)); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('TEM CERTEZA ABSOLUTA que deseja apagar este arquivo de backup?');"><i class="fas fa-trash"></i> Apagar</a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <form id="restoreForm" method="POST" action="" style="display: none;">
                    <input type="hidden" name="action" value="do_restore">
                    <input type="hidden" id="backup_file_input" name="backup_file">
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('.restore-btn').on('click', function() {
                const fileToRestore = $(this).data('file');
                Swal.fire({
                    title: 'TEM CERTEZA ABSOLUTA?',
                    html: `Você está prestes a restaurar o banco de dados com o arquivo:<br><strong>${fileToRestore}</strong><br><br><strong style="color: red; font-size: 1.2rem;">TODOS OS DADOS ATUAIS SERÃO PERMANENTEMENTE APAGADOS E SUBSTITUÍDOS.</strong><br><br>Digite "CONFIRMAR" no campo abaixo para prosseguir.`,
                    icon: 'warning',
                    input: 'text',
                    inputPlaceholder: 'CONFIRMAR',
                    confirmButtonText: 'Restaurar Agora',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Cancelar',
                    showCancelButton: true,
                    preConfirm: (login) => {
                        if (login !== 'CONFIRMAR') {
                            Swal.showValidationMessage('Você precisa digitar "CONFIRMAR" para prosseguir.')
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#backup_file_input').val(fileToRestore);
                        $('#restoreForm').submit();
                    }
                })
            });
        });
    </script>
</body>
</html>
