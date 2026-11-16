<?php

namespace App\Ai;

final class GenerationResult
{
    public function __construct(
        public readonly array $data,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly bool $fellBack = false,
    ) {}

    /** @return array{model: string, input_tokens: int, output_tokens: int, cost_usd: float|null, free: bool, fell_back: bool} */
    public function usage(): array
    {
        $gemini = str_starts_with($this->model, 'gemini');
        $free = $gemini && (bool) config('services.gemini.free_tier');
        // read the table as an array: model ids like "gemini-3.8-flash" contain dots, which config() would treat as nesting
        [$in, $out] = ((array) config($gemini ? 'services.gemini.prices' : 'services.ai.prices'))[$this->model] ?? [null, null];

        return [
            'model' => $this->model,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'cost_usd' => match (true) {
                $free => 0.0,
                $in === null => null,
                default => round(($this->inputTokens * $in + $this->outputTokens * $out) / 1_000_000, 4),
            },
            'free' => $free,
            'fell_back' => $this->fellBack,
        ];
    }
}
