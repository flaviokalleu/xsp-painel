<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli') {
    return;
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
if ($uri === '/healthz' || str_starts_with($uri, '/healthz')) {
    return;
}

function xsp_env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function xsp_fail(string $message): void {
    http_response_code(402);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'license_required',
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function xsp_hmac_key(string $secret): string {
    if ($secret !== '' && strlen($secret) % 2 === 0 && ctype_xdigit($secret)) {
        $raw = hex2bin($secret);
        if ($raw !== false) {
            return $raw;
        }
    }
    return $secret;
}

function xsp_compute_hwid_fallback(): string {
    $machineId = @trim((string)file_get_contents('/etc/machine-id'));
    $boardUuid = @trim((string)file_get_contents('/sys/class/dmi/id/product_uuid'));
    $diskUuid = @trim((string)shell_exec("blkid -s UUID -o value \$(findmnt -n -o SOURCE /) 2>/dev/null"));
    $mac = '';
    foreach (glob('/sys/class/net/*/address') ?: [] as $file) {
        if (basename(dirname($file)) === 'lo') {
            continue;
        }
        $candidate = trim((string)@file_get_contents($file));
        if ($candidate !== '' && $candidate !== '00:00:00:00:00:00') {
            $mac = $candidate;
            break;
        }
    }
    return hash('sha256', $machineId . "\x1f" . $boardUuid . "\x1f" . $diskUuid . "\x1f" . $mac);
}

// Retorna ['ok' => true, 'data' => array] ou ['ok' => false, 'transient' => bool, 'msg' => string]
// transient=true: erro temporário (429, rede) — não deve bloquear se tiver cache
// transient=false: erro definitivo (401, 403) — deve bloquear
function xsp_try_heartbeat(string $hwid, string $installId): array {
    $base = rtrim(xsp_env_value('XSP_API_BASE'), '/');
    $secret = xsp_env_value('XSP_PUBLIC_SECRET');
    if ($base === '' || $secret === '') {
        return ['ok' => false, 'transient' => false, 'msg' => 'license api not configured'];
    }

    $payload = json_encode([
        'hwid'          => $hwid,
        'panel_version' => xsp_env_value('XSP_VERSION', 'unknown'),
    ], JSON_UNESCAPED_UNICODE);

    $ts    = (string)time();
    $nonce = bin2hex(random_bytes(16));
    $sig   = hash_hmac('sha256', 'POST' . '/v1/heartbeat' . $payload . $ts . $nonce, xsp_hmac_key($secret));

    $ch = curl_init($base . '/v1/heartbeat');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Installation-ID: ' . $installId,
            'X-Timestamp: '       . $ts,
            'X-Nonce: '           . $nonce,
            'X-Signature: '       . $sig,
            'User-Agent: xsp-panel-github/' . xsp_env_value('XSP_VERSION', 'unknown'),
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $code === 0) {
        // Falha de rede — transitório
        return ['ok' => false, 'transient' => true, 'msg' => 'network error: ' . $curlErr];
    }

    if ($code === 429 || $code === 503 || $code === 502 || $code === 504) {
        // Rate limit ou servidor indisponível — transitório
        return ['ok' => false, 'transient' => true, 'msg' => 'http ' . $code];
    }

    if ($code >= 400) {
        // Licença inválida/expirada (401, 402, 403, 404) — definitivo
        return ['ok' => false, 'transient' => false, 'msg' => 'http ' . $code];
    }

    $data = json_decode((string)$response, true);
    return ['ok' => true, 'data' => is_array($data) ? $data : []];
}

$stateDir  = '/var/lib/xsp';
$cacheFile = $stateDir . '/license-cache.json';
$now       = time();

$cache     = null;
$cacheAge  = PHP_INT_MAX;

if (is_file($cacheFile)) {
    $decoded = json_decode((string)@file_get_contents($cacheFile), true);
    if (is_array($decoded)) {
        $cache    = $decoded;
        $cacheAge = $now - (int)($cache['cached_at'] ?? 0);
    }
}

// Cache ainda válido — passa direto
if ($cache !== null && (int)($cache['valid_until'] ?? 0) > $now) {
    return;
}

// Cache expirado ou inexistente — tenta heartbeat
$installId = xsp_env_value('XSP_INSTALLATION_ID');
$hwid      = xsp_env_value('XSP_HWID');
if ($hwid === '') {
    $hwid = xsp_compute_hwid_fallback();
}

if ($installId === '' || $hwid === '') {
    xsp_fail('panel not activated');
}

$result = xsp_try_heartbeat($hwid, $installId);

if ($result['ok']) {
    // Sucesso: renova cache por 6h
    @mkdir($stateDir, 0700, true);
    @file_put_contents($cacheFile, json_encode([
        'status'     => $result['data']['status'] ?? 'ok',
        'expires_at' => $result['data']['expires_at'] ?? null,
        'valid_until' => $now + 21600,
        'cached_at'  => $now,
    ], JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($cacheFile, 0600);
    return;
}

if ($result['transient']) {
    if ($cache !== null && $cacheAge < 86400) {
        // Erro temporário (429/rede) + cache < 24h: estende silenciosamente e deixa passar
        @file_put_contents($cacheFile, json_encode(array_merge($cache, [
            'valid_until' => $now + 21600,
            'cached_at'   => $cache['cached_at'] ?? $now,
        ]), JSON_UNESCAPED_UNICODE), LOCK_EX);
        return;
    }
    // Sem cache nenhum e API inacessível: bloqueia
    xsp_fail('license validation failed: ' . $result['msg']);
}

// Erro definitivo (licença inválida)
xsp_fail('license validation failed: ' . $result['msg']);
