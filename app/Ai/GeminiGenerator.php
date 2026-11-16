<?php

namespace App\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini over REST (generateContent), with JSON-schema structured output.
 *
 * Gemini compiles the schema into a state machine with undocumented size limits, so it
 * gets a schema narrowed to the section types of the request (narrowSchema), and `const`
 * (not in its documented keyword list) is rewritten as a one-value `enum`.
 */
class GeminiGenerator implements TextGenerator
{
    public function key(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Gemini ('.config('services.gemini.model').')';
    }

    public function configured(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public function narrowSchema(): bool
    {
        return true;
    }

    public function generate(string $system, string $user, array $schema, ?callable $onProgress = null): GenerationResult
    {
        if (! $this->configured()) {
            throw new AiException('أضف GEMINI_API_KEY في ملف ‎.env لاستخدام Gemini.');
        }

        $model = (string) config('services.gemini.model', 'gemini-3.6-flash');
        $url = rtrim((string) config('services.gemini.base_url'), '/')."/v1beta/models/{$model}:generateContent";

        $onProgress && $onProgress(0, false);

        try {
            // One plain (non-streamed) request over cURL: Guzzle's streaming path uses PHP's
            // stream wrapper, which times out on long generations. A busy model (500/503) is
            // retried with a pause — a failed request is not billed.
            $response = Http::withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
                ->connectTimeout(20)
                ->timeout(600)
                ->retry(3, fn (int $attempt) => [5000, 15000, 30000][$attempt - 1] ?? 30000, function ($e) {
                    return $e instanceof RequestException && in_array($e->response->status(), [500, 503], true);
                }, throw: false)
                ->post($url, [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseJsonSchema' => self::adapt($schema),
                        'maxOutputTokens' => (int) config('services.gemini.max_tokens', 65536),
                        'thinkingConfig' => ['thinkingLevel' => (string) config('services.gemini.thinking', 'low')],
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Gemini connection failed', ['message' => mb_substr($e->getMessage(), 0, 500)]);

            throw new AiException('تعذّر الاتصال بـ Gemini، تحقق من الإنترنت وحاول مرة أخرى.');
        }

        if (! $response->successful()) {
            $this->fail($response->status(), (string) $response->body());
        }

        $json = (array) $response->json();
        $candidate = $json['candidates'][0] ?? [];
        $blocked = $json['promptFeedback']['blockReason'] ?? null;
        $finish = $candidate['finishReason'] ?? null;
        $usage = $json['usageMetadata'] ?? [];
        $text = collect($candidate['content']['parts'] ?? [])
            ->filter(fn ($part) => empty($part['thought']) && isset($part['text']))
            ->pluck('text')
            ->implode('');

        $onProgress && $onProgress(strlen($text), true);

        if ($blocked || in_array($finish, ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST', 'RECITATION', 'SPII'], true)) {
            throw new AiException('رفض Gemini كتابة هذا المحتوى — عدّل الوصف أو التعليمات، أو جرّب Claude.');
        }

        if ($finish === 'MAX_TOKENS') {
            throw new AiException('المحتوى المطلوب أطول من الحد المسموح — جرّب توليد صفحات أو أقسام أقل في المرة.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            Log::warning('Gemini returned unparseable JSON', ['finish' => $finish, 'head' => mb_substr($text, 0, 300)]);

            throw new AiException('وصل رد غير مكتمل من Gemini، حاول مرة أخرى.');
        }

        $result = new GenerationResult(
            $data,
            $model,
            (int) ($usage['promptTokenCount'] ?? 0),
            (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0), // thinking is billed as output
        );

        return $result;
    }

    /** Rewrite the schema for Gemini's JSON-schema subset. */
    public static function adapt(array $schema): array
    {
        if (array_key_exists('const', $schema)) {
            $schema['enum'] = [$schema['const']];
            unset($schema['const']);
        }

        foreach (['properties', 'items', 'anyOf'] as $key) {
            if (! isset($schema[$key]) || ! is_array($schema[$key])) {
                continue;
            }
            $schema[$key] = $key === 'items' && ! array_is_list($schema[$key])
                ? self::adapt($schema[$key])
                : array_map(fn ($child) => is_array($child) ? self::adapt($child) : $child, $schema[$key]);
        }

        return $schema;
    }

    private function fail(int $status, string $body): never
    {
        $json = json_decode($body, true);
        $message = (string) ($json['error']['message'] ?? $json[0]['error']['message'] ?? '');
        $reason = json_encode($json['error']['details'] ?? $json[0]['error']['details'] ?? []);

        Log::warning('Gemini API error', ['status' => $status, 'message' => $message]);

        throw new AiException(match (true) {
            str_contains($reason, 'API_KEY_INVALID') || str_contains($message, 'API key') || in_array($status, [401, 403], true) => 'مفتاح GEMINI_API_KEY غير صالح — راجع ملف ‎.env.',
            $status === 429 => 'وصلت لحد Gemini المجاني لهذه الدقيقة أو اليوم — حاول لاحقًا أو استخدم Claude.',
            $status === 400 && stripos($message, 'schema') !== false => 'Gemini رفض هيكل الرد لأنه كبير — جرّب توليد صفحة واحدة في المرة.',
            $status >= 500 => 'خدمة Gemini مشغولة الآن، حاول مرة أخرى بعد قليل.',
            default => 'خطأ من Gemini: '.mb_substr($message ?: "HTTP {$status}", 0, 200),
        });
    }
}
