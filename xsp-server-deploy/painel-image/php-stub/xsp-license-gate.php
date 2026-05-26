<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli') {
    return;
}

$uri = $_SERVER['REQUEST_URI'] ?? '';
if (str_starts_with($uri, '/healthz')) {
    return;
}

// ─── helpers ─────────────────────────────────────────────────────────────────

function xsp_gate_env(string $k, string $d = ''): string {
    $v = getenv($k);
    return ($v === false || $v === '') ? $d : $v;
}

function xsp_gate_fail(string $msg): void {
    http_response_code(402);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'license_required', 'message' => $msg]);
    exit;
}

function xsp_gate_hmac_key(string $s): string {
    if ($s !== '' && strlen($s) % 2 === 0 && ctype_xdigit($s)) {
        $r = hex2bin($s);
        if ($r !== false) return $r;
    }
    return $s;
}

// ─── cache ───────────────────────────────────────────────────────────────────

$stateDir  = '/var/lib/xsp';
$cacheFile = $stateDir . '/license-cache.json';
$now       = time();
$cache     = null;

if (is_file($cacheFile)) {
    $tmp = json_decode((string)@file_get_contents($cacheFile), true);
    if (is_array($tmp)) $cache = $tmp;
}

// Cache ainda válido → passa sem chamar API
if ($cache !== null && (int)($cache['valid_until'] ?? 0) > $now) {
    return;
}

// ─── heartbeat (best-effort) ─────────────────────────────────────────────────

$installId = xsp_gate_env('XSP_INSTALLATION_ID');
$hwid      = xsp_gate_env('XSP_HWID');

if ($hwid === '') {
    $mid  = @trim((string)file_get_contents('/etc/machine-id'));
    $uuid = @trim((string)file_get_contents('/sys/class/dmi/id/product_uuid'));
    $disk = @trim((string)shell_exec("blkid -s UUID -o value \$(findmnt -n -o SOURCE /) 2>/dev/null"));
    $mac  = '';
    foreach (glob('/sys/class/net/*/address') ?: [] as $f) {
        if (basename(dirname($f)) === 'lo') continue;
        $c = trim((string)@file_get_contents($f));
        if ($c && $c !== '00:00:00:00:00:00') { $mac = $c; break; }
    }
    $hwid = hash('sha256', $mid . "\x1f" . $uuid . "\x1f" . $disk . "\x1f" . $mac);
}

if ($installId === '' || $hwid === '') {
    xsp_gate_fail('panel not activated');
}

$base     = rtrim(xsp_gate_env('XSP_API_BASE'), '/');
$secret   = xsp_gate_env('XSP_PUBLIC_SECRET');
$httpCode = 0;

if ($base !== '' && $secret !== '') {
    $body  = (string)json_encode(['hwid' => $hwid, 'panel_version' => xsp_gate_env('XSP_VERSION', 'unknown')]);
    $ts    = (string)$now;
    $nonce = bin2hex(random_bytes(16));
    $sig   = hash_hmac('sha256', 'POST/v1/heartbeat' . $body . $ts . $nonce, xsp_gate_hmac_key($secret));

    $ch = curl_init($base . '/v1/heartbeat');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Installation-ID: ' . $installId,
            'X-Timestamp: '       . $ts,
            'X-Nonce: '           . $nonce,
            'X-Signature: '       . $sig,
            'User-Agent: xsp-panel/' . xsp_gate_env('XSP_VERSION', 'unknown'),
        ],
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        // Sucesso: renova cache por 12h
        @mkdir($stateDir, 0700, true);
        @file_put_contents($cacheFile, (string)json_encode([
            'status'      => 'ok',
            'valid_until' => $now + 43200,
            'cached_at'   => $now,
        ]), LOCK_EX);
        @chmod($cacheFile, 0600);
        return;
    }

    if ($httpCode === 401 || $httpCode === 403) {
        // Licença definitivamente inválida — único caso que bloqueia
        xsp_gate_fail('license invalid (http ' . $httpCode . ')');
    }

    // 429, 5xx, 0 (rede): não bloqueia — cai no fallback abaixo
}

// ─── fallback ────────────────────────────────────────────────────────────────
// API indisponível ou rate-limited: se já validou alguma vez (< 7 dias), passa
if ($cache !== null && ($now - (int)($cache['cached_at'] ?? 0)) < 604800) {
    @file_put_contents($cacheFile, (string)json_encode([
        'status'      => $cache['status'] ?? 'ok',
        'valid_until' => $now + 43200,
        'cached_at'   => $cache['cached_at'] ?? $now,
    ]), LOCK_EX);
    return;
}

// Nunca validou antes E API inacessível → bloqueia
xsp_gate_fail('license unavailable (http ' . $httpCode . ')');
