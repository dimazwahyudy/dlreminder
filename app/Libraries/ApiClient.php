<?php
namespace App\Libraries;

/**
 * Simple HTTP client with file-based caching.
 * Usage:
 *  $c = new ApiClient();
 *  $resp = $c->fetchJson('GET', 'https://www.googleapis.com/calendar/v3/calendars/...', [], null, 300);
 */
class ApiClient
{
    protected $cacheDir;
    protected $defaultTtl;

    public function __construct($cacheDir = null, $defaultTtl = 300)
    {
        $base = __DIR__ . '/../../';
        $this->cacheDir = $cacheDir ?: (getenv('API_CLIENT_CACHE_DIR') ?: rtrim($base, '/') . '/storage/cache');
        $this->defaultTtl = (int)($defaultTtl ?: getenv('API_CLIENT_DEFAULT_TTL') ?: 300);
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    protected function cachePathForKey($key)
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', substr(md5($key), 0, 32));
        return rtrim($this->cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe . '.json';
    }

    protected function getCache($key)
    {
        $path = $this->cachePathForKey($key);
        if (!file_exists($path)) return null;
        $raw = @file_get_contents($path);
        if ($raw === false) return null;
        $obj = json_decode($raw, true);
        if (!$obj) return null;
        if (isset($obj['ts'], $obj['ttl']) && (time() - $obj['ts']) < $obj['ttl']) {
            return $obj['data'];
        }
        // expired
        @unlink($path);
        return null;
    }

    protected function setCache($key, $data, $ttl)
    {
        $path = $this->cachePathForKey($key);
        $obj = [
            'ts' => time(),
            'ttl' => $ttl,
            'data' => $data,
        ];
        @file_put_contents($path, json_encode($obj));
    }

    protected function buildKey($method, $url, $headers = [], $body = null)
    {
        $h = $headers;
        ksort($h);
        return strtoupper($method) . ' ' . $url . ' ' . md5(json_encode($h) . '|' . ($body ?? ''));
    }

    /**
     * Perform HTTP request. Uses cURL.
     * If $ttl > 0 and method is GET, response will be cached for $ttl seconds.
     * Returns array: [ 'status' => int, 'headers' => array, 'body' => string ]
     */
    public function fetch($method, $url, $headers = [], $body = null, $ttl = null)
    {
        $method = strtoupper($method);
        $ttl = is_null($ttl) ? $this->defaultTtl : (int)$ttl;
        $key = $this->buildKey($method, $url, $headers, $body);

        if ($method === 'GET' && $ttl > 0) {
            $cached = $this->getCache($key);
            if (!is_null($cached)) {
                return $cached;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $hdrs = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) $hdrs[] = $v; else $hdrs[] = $k . ': ' . $v;
        }
        if (!empty($hdrs)) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // collect headers
        $respHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$respHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) return $len;
            $name = trim($header[0]);
            $value = trim($header[1]);
            if (!isset($respHeaders[$name])) $respHeaders[$name] = $value; else $respHeaders[$name] .= ', ' . $value;
            return $len;
        });

        $bodyResp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $result = [
            'status' => $status,
            'headers' => $respHeaders,
            'body' => $bodyResp,
            'error' => $err,
        ];

        if ($method === 'GET' && $ttl > 0 && $status >= 200 && $status < 300) {
            $this->setCache($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * Convenience: fetch and JSON-decode body
     */
    public function fetchJson($method, $url, $headers = [], $body = null, $ttl = null)
    {
        $r = $this->fetch($method, $url, $headers, $body, $ttl);
        $decoded = null;
        if (!empty($r['body'])) {
            $decoded = json_decode($r['body'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // not JSON
                $decoded = $r['body'];
            }
        }
        return ['status' => $r['status'], 'headers' => $r['headers'], 'body' => $decoded, 'error' => $r['error']];
    }

    public function clearCache()
    {
        $files = glob(rtrim($this->cacheDir, '/') . '/*.json');
        foreach ($files as $f) @unlink($f);
    }
}
