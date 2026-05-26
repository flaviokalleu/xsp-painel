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

function xsp_call_license_api(string $method, string $path, array $payload, string $installId): array {
    $base = rtrim(xsp_env_value('XSP_API_BASE'), '/');
    $secret = xsp_env_value('XSP_PUBLIC_SECRET');
    if ($base === '' || $secret === '') {
        xsp_fail('license api not configured');
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        xsp_fail('license payload failed');
    }
    $ts = (string)time();
    $nonce = bin2hex(random_bytes(16));
    $signature = hash_hmac('sha256', $method . $path . $body . $ts . $nonce, xsp_hmac_key($secret));

    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Installation-ID: ' . $installId,
            'X-Timestamp: ' . $ts,
            'X-Nonce: ' . $nonce,
            'X-Signature: ' . $signature,
            'User-Agent: xsp-panel-github/' . xsp_env_value('XSP_VERSION', 'unknown'),
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $code >= 400) {
        xsp_fail('license validation failed: ' . ($error ?: ('http ' . $code)));
    }

    $data = json_decode((string)$response, true);
    if (!is_array($data)) {
        xsp_fail('invalid license response');
    }
    return $data;
}

$stateDir = '/var/lib/xsp';
$cacheFile = $stateDir . '/license-cache.json';
$now = time();

if (is_file($cacheFile)) {
    $cache = json_decode((string)@file_get_contents($cacheFile), true);
    if (is_array($cache) && (int)($cache['valid_until'] ?? 0) > $now) {
        return;
    }
}

$installId = xsp_env_value('XSP_INSTALLATION_ID');
$hwid = xsp_env_value('XSP_HWID');
if ($hwid === '') {
    $hwid = xsp_compute_hwid_fallback();
}
if ($installId === '' || $hwid === '') {
    xsp_fail('panel not activated');
}

$data = xsp_call_license_api('POST', '/v1/heartbeat', [
    'hwid' => $hwid,
    'panel_version' => xsp_env_value('XSP_VERSION', 'unknown'),
], $installId);

@mkdir($stateDir, 0700, true);
@file_put_contents($cacheFile, json_encode([
    'status' => $data['status'] ?? 'ok',
    'expires_at' => $data['expires_at'] ?? null,
    'valid_until' => $now + 21600,
], JSON_UNESCAPED_UNICODE), LOCK_EX);
@chmod($cacheFile, 0600);
