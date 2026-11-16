<?php

namespace App\Ai;

use Anthropic\Beta\Messages\BetaRawContentBlockDeltaEvent;
use Anthropic\Beta\Messages\BetaRawContentBlockStartEvent;
use Anthropic\Beta\Messages\BetaRawMessageDeltaEvent;
use Anthropic\Beta\Messages\BetaRawMessageStartEvent;
use Anthropic\Beta\Messages\BetaTextDelta;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\OverloadedException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Core\Exceptions\ServiceUnavailableException;
use Illuminate\Support\Facades\Log;

/**
 * Claude via the official Anthropic PHP SDK.
 *
 *  - structured outputs (output_config.format = json_schema) guarantee parseable JSON
 *  - streamed, so long generations never hit HTTP timeouts and progress can be reported
 *  - adaptive thinking (on by default on current models) with an explicit effort level
 *  - server-side refusal fallback ("default"), so a policy false-positive is retried on a
 *    fallback model instead of failing the run
 */
class ClaudeGenerator implements TextGenerator
{
    public function key(): string
    {
        return 'claude';
    }

    public function label(): string
    {
        return 'Claude ('.config('services.ai.model').')';
    }

    public function configured(): bool
    {
        return filled(config('services.ai.key'));
    }

    /** One stable schema for every site: compiled once, cached by the API for 24h. */
    public function narrowSchema(): bool
    {
        return false;
    }

    public function generate(string $system, string $user, array $schema, ?callable $onProgress = null): GenerationResult
    {
        if (! $this->configured()) {
            throw new AiException('أضف AI_API_KEY (مفتاح Anthropic) في ملف ‎.env لتفعيل توليد المحتوى.');
        }

        $client = new Client(apiKey: (string) config('services.ai.key'), baseUrl: config('services.ai.base_url') ?: null);
        $model = (string) config('services.ai.model', 'claude-sonnet-5-5');

        $blocks = [];       // index => ['type' => …, 'text' => …]
        $lastFallback = -1; // text before a fallback block belongs to the declined model
        $chars = 0;
        $inputTokens = $outputTokens = 0;
        $stopReason = null;
        $servedModel = $model;

        try {
            $stream = $client->beta->messages->createStream(
                maxTokens: (int) config('services.ai.max_tokens', 64000),
                messages: [['role' => 'user', 'content' => $user]],
                model: $model,
                system: $system,
                outputConfig: [
                    'effort' => (string) config('services.ai.effort', 'medium'),
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['timeout' => 900.0, 'maxRetries' => 2],
            );

            foreach ($stream as $event) {
                if ($event instanceof BetaRawMessageStartEvent) {
                    $inputTokens = $event->message->usage->inputTokens;
                    $servedModel = $event->message->model ?: $model;
                } elseif ($event instanceof BetaRawContentBlockStartEvent) {
                    $blocks[$event->index] = ['type' => $event->contentBlock->type, 'text' => ''];
                    if ($event->contentBlock->type === 'fallback') {
                        $lastFallback = $event->index;
                    }
                } elseif ($event instanceof BetaRawContentBlockDeltaEvent && $event->delta instanceof BetaTextDelta) {
                    $blocks[$event->index]['text'] = ($blocks[$event->index]['text'] ?? '').$event->delta->text;
                    $chars += strlen($event->delta->text);
                    $onProgress && $onProgress($chars, true);
                } elseif ($event instanceof BetaRawMessageDeltaEvent) {
                    $stopReason = $event->delta->stopReason ?? $stopReason;
                    $outputTokens = $event->usage->outputTokens;
                    $inputTokens = $event->usage->inputTokens ?? $inputTokens;
                } else {
                    $onProgress && $onProgress($chars, false);
                }
            }
        } catch (AuthenticationException|PermissionDeniedException) {
            throw new AiException('مفتاح AI_API_KEY غير صالح أو لا يملك صلاحية — راجع ملف ‎.env.');
        } catch (RateLimitException) {
            throw new AiException('تم تجاوز حد طلبات Anthropic، حاول بعد دقيقة.');
        } catch (OverloadedException|InternalServerException|ServiceUnavailableException) {
            throw new AiException('خدمة Claude مشغولة الآن، حاول مرة أخرى بعد قليل.');
        } catch (BadRequestException $e) {
            Log::warning('AI bad request', ['message' => $e->getMessage()]);

            throw new AiException('رفضت Anthropic الطلب: '.mb_substr($e->getMessage(), 0, 200));
        } catch (APIStatusException $e) {
            Log::warning('AI API error', ['message' => $e->getMessage()]);

            throw new AiException('خطأ من Anthropic: '.mb_substr($e->getMessage(), 0, 200));
        } catch (APIConnectionException) {
            throw new AiException('تعذّر الاتصال بـ Anthropic، تحقق من الإنترنت وحاول مرة أخرى.');
        }

        if ($stopReason === 'refusal') {
            throw new AiException('رفض النموذج كتابة هذا المحتوى — عدّل الوصف أو التعليمات وحاول مرة أخرى.');
        }

        if ($stopReason === 'max_tokens') {
            throw new AiException('المحتوى المطلوب أطول من الحد المسموح — جرّب توليد صفحات أو أقسام أقل في المرة.');
        }

        $text = collect($blocks)
            ->filter(fn ($b, $i) => $i > $lastFallback && $b['type'] === 'text')
            ->sortKeys()
            ->pluck('text')
            ->implode('');

        $data = json_decode($text, true);

        if (! is_array($data)) {
            Log::warning('AI returned unparseable JSON', ['head' => mb_substr($text, 0, 300)]);

            throw new AiException('وصل رد غير مكتمل من النموذج، حاول مرة أخرى.');
        }

        return new GenerationResult($data, $servedModel, $inputTokens, $outputTokens, $lastFallback >= 0);
    }
}
