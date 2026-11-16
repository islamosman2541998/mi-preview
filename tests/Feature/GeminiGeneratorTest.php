<?php

namespace Tests\Feature;

use App\Ai\AiException;
use App\Ai\GeminiGenerator;
use App\Ai\SectionSchema;
use App\Enums\SectionType;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeminiGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'g-key',
            'services.gemini.model' => 'gemini-3.8-flash',
            'services.gemini.thinking' => 'low',
            'services.gemini.free_tier' => false,
            'services.gemini.base_url' => 'https://generativelanguage.googleapis.com',
        ]);
    }

    /** generateContent body: a thought part, the JSON split over two parts, usage + finish. */
    private function reply(string $json, string $finish = 'STOP'): array
    {
        $half = intdiv(mb_strlen($json), 2);

        return [
            'candidates' => [[
                'content' => ['role' => 'model', 'parts' => [
                    ['text' => 'planning…', 'thought' => true],
                    ['text' => mb_substr($json, 0, $half)],
                    ['text' => mb_substr($json, $half)],
                ]],
                'finishReason' => $finish,
            ]],
            'usageMetadata' => ['promptTokenCount' => 2000, 'candidatesTokenCount' => 6000, 'thoughtsTokenCount' => 1000, 'totalTokenCount' => 9000],
        ];
    }

    #[Test]
    public function returns_structured_json_and_skips_thoughts(): void
    {
        $json = json_encode(['seo' => ['title_ar' => 'أكمي للمقاولات'], 'sections' => []], JSON_UNESCAPED_UNICODE);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->reply($json))]);
        $schema = SectionSchema::response([SectionType::Hero, SectionType::Cta]);
        $progress = 0;

        $result = (new GeminiGenerator)->generate('SYSTEM', 'USER', $schema, function () use (&$progress) {
            $progress++;
        });

        $this->assertSame('أكمي للمقاولات', $result->data['seo']['title_ar']);
        $this->assertSame('gemini-3.8-flash', $result->model);
        $this->assertSame(2000, $result->inputTokens);
        $this->assertSame(7000, $result->outputTokens, 'thinking tokens are billed as output');
        $this->assertSame(round((2000 * 0.75 + 7000 * 3.75) / 1e6, 4), $result->usage()['cost_usd']);
        $this->assertGreaterThan(0, $progress);

        Http::assertSent(function (Request $r) {
            $gen = $r['generationConfig'];
            $variant = $gen['responseJsonSchema']['properties']['sections']['items']['anyOf'][0];

            return str_ends_with($r->url(), '/v1beta/models/gemini-3.8-flash:generateContent')
                && $r->hasHeader('x-goog-api-key', 'g-key')
                && $r['systemInstruction']['parts'][0]['text'] === 'SYSTEM'
                && $r['contents'][0]['parts'][0]['text'] === 'USER'
                && $gen['responseMimeType'] === 'application/json'
                && $gen['thinkingConfig'] === ['thinkingLevel' => 'low']
                && $variant['properties']['type'] === ['type' => 'string', 'enum' => ['hero']] // const → enum
                && ! str_contains(json_encode($gen['responseJsonSchema']), '"const"');
        });
    }

    #[Test]
    public function free_tier_runs_cost_nothing(): void
    {
        config(['services.gemini.free_tier' => true]);
        Http::fake(['*' => Http::response($this->reply('{"seo":{},"sections":[]}'))]);

        $usage = (new GeminiGenerator)->generate('S', 'U', [])->usage();

        $this->assertTrue($usage['free']);
        $this->assertSame(0.0, $usage['cost_usd']);
    }

    #[Test]
    public function safety_blocks_are_reported_in_arabic(): void
    {
        Http::fake(['*' => Http::response($this->reply('{"seo"', 'SAFETY'))]);

        $this->expectExceptionMessage('رفض Gemini');
        (new GeminiGenerator)->generate('S', 'U', []);
    }

    #[Test]
    public function truncation_is_reported_in_arabic(): void
    {
        Http::fake(['*' => Http::response($this->reply('{"seo"', 'MAX_TOKENS'))]);

        $this->expectExceptionMessage('أطول من الحد');
        (new GeminiGenerator)->generate('S', 'U', []);
    }

    #[Test]
    public function api_errors_are_explained(): void
    {
        $cases = [
            [400, ['error' => ['code' => 400, 'message' => 'API key not valid. Please pass a valid API key.', 'status' => 'INVALID_ARGUMENT', 'details' => [['reason' => 'API_KEY_INVALID']]]], 'GEMINI_API_KEY غير صالح'],
            [429, ['error' => ['code' => 429, 'message' => 'Resource has been exhausted', 'status' => 'RESOURCE_EXHAUSTED']], 'حد Gemini المجاني'],
            [400, ['error' => ['code' => 400, 'message' => 'The specified schema produces a constraint that has too many states', 'status' => 'INVALID_ARGUMENT']], 'هيكل الرد'],
        ];

        $sequence = Http::sequence();
        foreach ($cases as [$status, $body]) {
            $sequence->push($body, $status);
        }
        Http::fake(['*' => $sequence]);

        foreach ($cases as [$status, , $expected]) {
            try {
                (new GeminiGenerator)->generate('S', 'U', []);
                $this->fail("HTTP {$status} should throw");
            } catch (AiException $e) {
                $this->assertStringContainsString($expected, $e->getMessage(), "HTTP {$status}");
            }
        }
    }

    #[Test]
    public function a_busy_model_is_retried_before_giving_up(): void
    {
        Sleep::fake();
        Http::fake(['*' => Http::sequence()
            ->push(['error' => ['code' => 503, 'message' => 'high demand', 'status' => 'UNAVAILABLE']], 503)
            ->push($this->reply('{"seo":{},"sections":[]}'))]);

        $result = (new GeminiGenerator)->generate('S', 'U', []);

        $this->assertSame([], $result->data['sections']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function a_model_that_stays_busy_is_reported(): void
    {
        Sleep::fake();
        Http::fake(['*' => Http::response(['error' => ['code' => 503, 'message' => 'high demand', 'status' => 'UNAVAILABLE']], 503)]);

        try {
            (new GeminiGenerator)->generate('S', 'U', []);
            $this->fail('should throw');
        } catch (AiException $e) {
            $this->assertStringContainsString('مشغولة', $e->getMessage());
        }

        Http::assertSentCount(3); // retry(3) = three attempts in total
    }

    #[Test]
    public function missing_key_explains_how_to_enable_it(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse((new GeminiGenerator)->configured());
        $this->expectExceptionMessage('GEMINI_API_KEY');
        (new GeminiGenerator)->generate('S', 'U', []);
    }
}
