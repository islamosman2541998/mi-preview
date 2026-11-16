<?php

namespace Tests\Feature;

use App\Ai\AiException;
use App\Ai\ClaudeGenerator;
use App\Ai\SectionSchema;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Exercises the real SDK call path (request shape, streaming, fallback handling, errors)
 * against a local fake of POST /v1/messages — no API key or network needed.
 */
class ClaudeGeneratorTest extends TestCase
{
    private ?Process $server = null;

    private string $capture;

    private string $response;

    protected function tearDown(): void
    {
        $this->server?->stop(0);
        parent::tearDown();
    }

    private function serve(string $mode, array $json = ['seo' => ['title_ar' => 'أكمي'], 'sections' => []]): void
    {
        $this->capture = tempnam(sys_get_temp_dir(), 'cap');
        $this->response = tempnam(sys_get_temp_dir(), 'res');
        file_put_contents($this->response, json_encode($json, JSON_UNESCAPED_UNICODE));

        $port = random_int(20000, 40000);
        $this->server = new Process(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", base_path('tests/Fixtures/fake-anthropic.php')],
            null,
            ['FAKE_AI_MODE' => $mode, 'FAKE_AI_CAPTURE' => $this->capture, 'FAKE_AI_RESPONSE' => $this->response],
        );
        $this->server->start();

        for ($i = 0; $i < 50 && ! @fsockopen('127.0.0.1', $port); $i++) {
            usleep(100_000);
        }

        config([
            'services.ai.key' => 'sk-ant-test',
            'services.ai.base_url' => "http://127.0.0.1:{$port}",
            'services.ai.model' => 'claude-opus-5-5',
            'services.ai.effort' => 'medium',
        ]);
    }

    private function request(): array
    {
        return json_decode(file_get_contents($this->capture), true);
    }

    #[Test]
    public function sends_a_streamed_structured_request_and_parses_the_json(): void
    {
        $json = ['seo' => ['title_ar' => 'أكمي للمقاولات', 'title_en' => 'Acme', 'description_ar' => 'و', 'description_en' => 'd'], 'sections' => [['id' => 3, 'type' => 'cta', 'content' => ['ar' => ['title' => 'اطلب عرض سعر'], 'en' => ['title' => 'Get a quote']]]]];
        $this->serve('ok', $json);
        $progress = [];

        $result = (new ClaudeGenerator)->generate('SYSTEM', 'USER', SectionSchema::response(), function ($chars, $writing) use (&$progress) {
            $progress[] = [$chars, $writing];
        });

        $this->assertSame($json, $result->data);
        $this->assertSame(1500, $result->inputTokens);
        $this->assertSame(2400, $result->outputTokens);
        $this->assertFalse($result->fellBack);
        $this->assertSame(0.054, $result->usage()['cost_usd'], '1500 in × $4 + 2400 out × $20 per MTok');
        $this->assertNotEmpty(array_filter($progress, fn ($p) => $p[1]), 'progress reported while writing');

        $req = $this->request();
        $this->assertSame('/v1/messages', parse_url($req['path'], PHP_URL_PATH));
        $this->assertSame('Bearer sk-ant-test', $req['headers']['authorization'], 'SDK 1.x sends the key as a bearer token');
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $req['headers']['anthropic-beta']);

        $body = $req['body'];
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertTrue($body['stream']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame('medium', $body['output_config']['effort']);
        $this->assertSame('json_schema', $body['output_config']['format']['type']);
        $this->assertSame(SectionSchema::response()['required'], $body['output_config']['format']['schema']['required']);
        $this->assertSame('SYSTEM', is_array($body['system']) ? $body['system'][0]['text'] : $body['system']);
        $this->assertSame([['role' => 'user', 'content' => 'USER']], $body['messages']);
        $this->assertArrayNotHasKey('thinking', $body, 'Opus 5.5 thinks adaptively by default; disabling it is a 400');
        $this->assertArrayNotHasKey('temperature', $body);
    }

    #[Test]
    public function text_from_a_declined_model_before_a_fallback_block_is_discarded(): void
    {
        $this->serve('fallback', ['seo' => ['title_ar' => 'ok'], 'sections' => []]);

        $result = (new ClaudeGenerator)->generate('S', 'U', SectionSchema::response());

        $this->assertSame('ok', $result->data['seo']['title_ar']);
        $this->assertTrue($result->fellBack);
    }

    #[Test]
    public function refusal_is_reported_in_arabic(): void
    {
        $this->serve('refusal');

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('رفض النموذج');
        (new ClaudeGenerator)->generate('S', 'U', SectionSchema::response());
    }

    #[Test]
    public function truncated_output_is_reported(): void
    {
        $this->serve('max_tokens');

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('أطول من الحد');
        (new ClaudeGenerator)->generate('S', 'U', SectionSchema::response());
    }

    #[Test]
    public function a_bad_key_is_reported_without_retrying_forever(): void
    {
        $this->serve('auth');

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('AI_API_KEY غير صالح');
        (new ClaudeGenerator)->generate('S', 'U', SectionSchema::response());
    }

    #[Test]
    public function missing_key_explains_how_to_enable_it(): void
    {
        config(['services.ai.key' => null]);

        $this->assertFalse((new ClaudeGenerator)->configured());
        $this->expectExceptionMessage('AI_API_KEY');
        (new ClaudeGenerator)->generate('S', 'U', []);
    }
}
