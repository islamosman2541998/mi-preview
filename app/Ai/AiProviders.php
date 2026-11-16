<?php

namespace App\Ai;

/**
 * AI providers available for content generation, in admin order. The default is
 * AI_PROVIDER when it has a key, otherwise the first provider that has one.
 */
class AiProviders
{
    /** @var array<string, TextGenerator> */
    private array $providers = [];

    /** @param  list<TextGenerator>|null  $providers  (tests pass fakes) */
    public function __construct(?array $providers = null)
    {
        foreach ($providers ?? [app(ClaudeGenerator::class), app(GeminiGenerator::class)] as $p) {
            $this->providers[$p->key()] = $p;
        }
    }

    /** @return array<string, TextGenerator> */
    public function all(): array
    {
        return $this->providers;
    }

    /** @return array<string, TextGenerator> providers with an API key */
    public function configured(): array
    {
        return array_filter($this->providers, fn (TextGenerator $p) => $p->configured());
    }

    public function any(): bool
    {
        return $this->configured() !== [];
    }

    public function default(): ?TextGenerator
    {
        $configured = $this->configured();

        return $configured[(string) config('services.ai.provider')] ?? (array_values($configured)[0] ?? null);
    }

    /** A configured provider by key, or the default. */
    public function resolve(?string $key): TextGenerator
    {
        $provider = ($key ? ($this->configured()[$key] ?? null) : null) ?? $this->default();

        return $provider ?? throw new AiException(self::setupHint());
    }

    /** @return array<string, string> key => label, for a select */
    public function options(): array
    {
        return array_map(fn (TextGenerator $p) => $p->label(), $this->configured());
    }

    public static function setupHint(): string
    {
        return 'أضف AI_API_KEY (مفتاح Anthropic) أو GEMINI_API_KEY في ملف ‎.env لتفعيل توليد المحتوى.';
    }
}
