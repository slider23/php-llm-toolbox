<?php

namespace Slider23\PhpLlmToolbox\Traits;

trait ProxyTrait
{
    private ?string $proxyHost = null;
    private ?string $proxyPort = null;
    private ?string $proxyLogin = null;
    private ?string $proxyPassword = null;
    private ?int $proxyType = null; // CURLPROXY_HTTP / CURLPROXY_SOCKS5

    /**
     * Parse proxy definition and fill internal attributes.
     * Supported input examples:
     *  - 111.11.11.111:3128
     *  - proxy.local
     *  - proxy.local:8080
     *  - http://proxy.local:8080
     *  - http://user:pass@proxy.local:8080
     *  - socks5://host
     *  - socks5://host:1080
     *  - socks5h://host:9050
     *  - socks5://login:password@111.11.11.111:1080
     * Credentials precedence: explicit method params > URL-embedded credentials.
     * Type detection:
     *  - scheme socks5 / socks5h => SOCKS5
     *  - scheme http / https => HTTP
     *  - else if port in [1080,1086,9050,9051] => SOCKS5
     *  - else => HTTP
     * Default port for SOCKS5 when omitted: 1080.
     *
     * @return $this
     */
    public function setProxy(string $url, ?string $login = null, ?string $password = null)
    {
        $scheme = null;
        $host = null;
        $port = null;
        $urlUser = null;
        $urlPass = null;

        // Parse URL if it contains scheme
        if (str_contains($url, '://')) {
            $parts = @parse_url($url);
            if ($parts !== false) {
                $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : null;
                $host   = $parts['host'] ?? null;
                $port   = isset($parts['port']) ? (string)$parts['port'] : null;
                $urlUser = $parts['user'] ?? null;
                $urlPass = $parts['pass'] ?? null;
            }
        }

        // Fallback parsing for simple forms like host:port or host
        if ($host === null) {
            if (preg_match('/^([^:]+):(\d+)$/', $url, $m)) {
                $host = $m[1];
                $port = $m[2];
            } else {
                $host = $url;
            }
        }

        // Detect proxy type
        $socksPorts = ['1080','1086','9050','9051'];
        $isSocks = false;

        if ($scheme && preg_match('/^socks5h?$/', $scheme)) {
            $isSocks = true;
        } elseif ($scheme && in_array($scheme, ['http','https'], true)) {
            $isSocks = false;
        } elseif ($port && in_array($port, $socksPorts, true)) {
            $isSocks = true;
        }

        // Default SOCKS port if missing
        if ($isSocks && !$port) {
            $port = '1080';
        }

        // Final credentials (method params override URL)
        $finalLogin = $login ?? $urlUser;
        $finalPass  = $password ?? $urlPass;

        $this->proxyHost = $host;
        $this->proxyPort = $port;
        $this->proxyLogin = $finalLogin;
        $this->proxyPassword = $finalPass;
        $this->proxyType = $isSocks
            ? (defined('CURLPROXY_SOCKS5') ? CURLPROXY_SOCKS5 : 5)
            : (defined('CURLPROXY_HTTP') ? CURLPROXY_HTTP : 0);

        return $this;
    }

    /**
     * Apply the proxy settings to the cURL handle.
     */
    public function applyProxy($curl): void
    {
        if (!$this->proxyHost) {
            return;
        }
        curl_setopt($curl, CURLOPT_PROXY, $this->proxyHost . ':' . $this->proxyPort);
        if ($this->proxyType !== null) {
            curl_setopt($curl, CURLOPT_PROXYTYPE, $this->proxyType);
        }
        if ($this->proxyLogin !== null) {
            $auth = $this->proxyLogin . ':' . ($this->proxyPassword ?? '');
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $auth);
        }
    }
}