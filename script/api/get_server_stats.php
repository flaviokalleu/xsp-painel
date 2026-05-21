<?php
// Define o cabeçalho como JSON para que o JavaScript entenda a resposta
header('Content-Type: application/json');

// Função para obter o uso da CPU (funciona em Linux)
function get_cpu_usage() {
    // Executa o comando 'top' uma vez em modo batch, pega a linha da CPU, extrai o % de 'idle' (ocioso)
    // e subtrai de 100 para obter o uso. É um método comum e eficaz.
    $cpu_load = shell_exec("top -bn1 | grep 'Cpu(s)' | sed 's/.*, *\\([0-9.]*\\)%* id.*/\\1/' | awk '{print 100 - $1}'");
    return round(floatval($cpu_load));
}

// Função para obter o uso da RAM (funciona em Linux)
function get_ram_usage() {
    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem, function($value) { return ($value !== null && $value !== false && $value !== ''); }); 
    $mem = array_merge($mem);
    $mem_total = $mem[1];
    $mem_used = $mem[2];
    $mem_usage = ($mem_used / $mem_total) * 100;
    return round($mem_usage);
}

// Função para obter o uso do disco para a partição raiz '/' (funciona em Linux)
function get_disk_usage() {
    // Executa o comando 'df' para a partição raiz '/' e extrai a porcentagem de uso.
    $disk_usage = shell_exec("df -h / | awk 'NR==2 {print $5}'");
    return intval(str_replace('%', '', $disk_usage));
}

// Monta um array com as estatísticas
$stats = [
    'cpu' => get_cpu_usage(),
    'ram' => get_ram_usage(),
    'disk' => get_disk_usage()
];

// Retorna os dados em formato JSON
echo json_encode($stats);

?>