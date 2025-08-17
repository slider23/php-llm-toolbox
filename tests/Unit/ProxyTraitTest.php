<?php

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;

class ProxyTraitTest extends TestCase
{
    /**
     * Вспомогательный тестовый класс, просто подключает трейт.
     */
    private function makeClient(): object
    {
        return new class {
            use ProxyTrait;
        };
    }

    private function getPrivate(object $obj, string $prop)
    {
        $ref = new ReflectionClass($obj);
        $p = $ref->getProperty($prop);
        $p->setAccessible(true);
        return $p->getValue($obj);
    }

    private function socksConst(): int
    {
        return defined('CURLPROXY_SOCKS5') ? CURLPROXY_SOCKS5 : 5;
    }
    private function httpConst(): int
    {
        return defined('CURLPROXY_HTTP') ? CURLPROXY_HTTP : 0;
    }
    private function assertState(object $c, string $host, ?string $port, int $type, ?string $login, ?string $pass)
    {
        $this->assertSame($host, $this->getPrivate($c, 'proxyHost'));
        $this->assertSame($port, $this->getPrivate($c, 'proxyPort'));
        $this->assertSame($login, $this->getPrivate($c, 'proxyLogin'));
        $this->assertSame($pass, $this->getPrivate($c, 'proxyPassword'));
        $this->assertSame($type, $this->getPrivate($c, 'proxyType'));
    }

    public function testSocksWithCredentialsInUrl()
    {
        $c = $this->makeClient();
        $c->setProxy('socks5://user:pass@10.0.0.1:1080');
        $this->assertState($c, '10.0.0.1', '1080', $this->socksConst(), 'user', 'pass');
    }

    public function testSocksWithoutPortGetsDefault1080()
    {
        $c = $this->makeClient();
        $c->setProxy('socks5://8.8.8.8');
        $this->assertState($c, '8.8.8.8', '1080', $this->socksConst(), null, null);
    }

    public function testSocks5hCustomPort()
    {
        $c = $this->makeClient();
        $c->setProxy('socks5h://domain.org:9051');
        $this->assertState($c, 'domain.org', '9051', $this->socksConst(), null, null);
    }

    public function testSocksDetectedByPort()
    {
        $c = $this->makeClient();
        $c->setProxy('127.0.0.1:9050');
        $this->assertState($c, '127.0.0.1', '9050', $this->socksConst(), null, null);
    }

    public function testHttpSimpleHostPort()
    {
        $c = $this->makeClient();
        $c->setProxy('111.11.11.111:3128');
        $this->assertState($c, '111.11.11.111', '3128', $this->httpConst(), null, null);
    }

    public function testHttpSchemeWithCredentialsOverride()
    {
        $c = $this->makeClient();
        $c->setProxy('http://u1:p1@proxy.local:8080', 'u2', 'p2');
        $this->assertState($c, 'proxy.local', '8080', $this->httpConst(), 'u2', 'p2');
    }

    public function testHttpSchemeNoPort()
    {
        $c = $this->makeClient();
        $c->setProxy('http://proxy.local');
        $this->assertState($c, 'proxy.local', null, $this->httpConst(), null, null);
    }
}
