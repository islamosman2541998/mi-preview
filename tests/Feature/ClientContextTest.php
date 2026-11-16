<?php

namespace Tests\Feature;

use App\Support\ClientContext;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientContextTest extends TestCase
{
    private function request(string $peer, array $headers = []): Request
    {
        $server = ['REMOTE_ADDR' => $peer];
        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return Request::create('http://acme.localhost/', 'GET', server: $server);
    }

    #[Test]
    public function trusts_cf_connecting_ip_from_a_cloudflare_edge(): void
    {
        $ctx = ClientContext::from($this->request('162.158.1.10', ['CF-Connecting-IP' => '41.33.12.7']));

        $this->assertTrue($ctx->isFromCloudflare());
        $this->assertSame('41.33.12.7', $ctx->ip());
    }

    #[Test]
    public function trusts_cf_connecting_ip_from_a_cloudflare_ipv6_edge(): void
    {
        $ctx = ClientContext::from($this->request('2606:4700::1', ['CF-Connecting-IP' => '2c0f:fc88::5']));

        $this->assertSame('2c0f:fc88::5', $ctx->ip());
    }

    #[Test]
    public function ignores_spoofed_cf_connecting_ip_from_a_non_cloudflare_peer(): void
    {
        $ctx = ClientContext::from($this->request('203.0.113.9', ['CF-Connecting-IP' => '1.2.3.4']));

        $this->assertFalse($ctx->isFromCloudflare());
        $this->assertSame('203.0.113.9', $ctx->ip());
    }

    #[Test]
    public function ignores_garbage_cf_connecting_ip(): void
    {
        $ctx = ClientContext::from($this->request('162.158.1.10', ['CF-Connecting-IP' => 'not-an-ip']));

        $this->assertNotSame('not-an-ip', $ctx->ip());
    }

    #[Test]
    public function ip_hash_is_salted_sha256(): void
    {
        $ctx = ClientContext::from($this->request('162.158.1.10', ['CF-Connecting-IP' => '41.33.12.7']));

        $this->assertSame(hash('sha256', '41.33.12.7'.config('app.key')), $ctx->ipHash());
        $this->assertNotSame(hash('sha256', '41.33.12.7'), $ctx->ipHash());
    }

    #[Test]
    public function reads_country_and_drops_unknown_values(): void
    {
        $this->assertSame('EG', ClientContext::from($this->request('162.158.1.10', ['CF-IPCountry' => 'eg']))->country());
        $this->assertNull(ClientContext::from($this->request('162.158.1.10', ['CF-IPCountry' => 'XX']))->country());
        $this->assertNull(ClientContext::from($this->request('162.158.1.10', ['CF-IPCountry' => 'T1']))->country());
        $this->assertNull(ClientContext::from($this->request('162.158.1.10', ['CF-IPCountry' => 'EGY']))->country());
        $this->assertNull(ClientContext::from($this->request('162.158.1.10'))->country());
    }

    #[Test]
    public function forwarded_proto_is_trusted_only_from_cloudflare(): void
    {
        $this->assertTrue(
            $this->get('http://app.localhost/login', ['X-Forwarded-Proto' => 'https'])->isOk()
        );

        $fromCf = Request::create('http://app.localhost/', server: [
            'REMOTE_ADDR' => '162.158.1.10', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        $fromCf = $this->app->make(TrustProxies::class)
            ->handle($fromCf, fn ($r) => response($r->isSecure() ? 'secure' : 'plain'));
        $this->assertSame('secure', $fromCf->getContent());

        $spoofed = Request::create('http://app.localhost/', server: [
            'REMOTE_ADDR' => '203.0.113.9', 'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        $spoofed = $this->app->make(TrustProxies::class)
            ->handle($spoofed, fn ($r) => response($r->isSecure() ? 'secure' : 'plain'));
        $this->assertSame('plain', $spoofed->getContent());
    }
}
